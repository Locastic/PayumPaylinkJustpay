<?php

namespace PayLink;

use \PayLink\Result;
use \PayLink\PayLinkException;

class PayLink
{
    private $_urls = array(
        'test' => 'https://test.ctpe.net',
        'live' => 'https://live.ctpe.net');

    private $_channel_id = null;
    private $_sender_id = null;
    private $_user_login = null;
    private $_user_password = null;

    private $isTestMode = true;

    private function getHost () { return ($this->isTestMode ? 'https://test.ctpe.net' : 'https://live.ctpe.net'); }
    private function getTransactionMode () { return ($this->isTestMode ? 'INTEGRATOR_TEST' : 'LIVE'); }

    public function __construct ($channel_id, $sender_id, $user_login, $user_password, $testModel = true)
    {
        if (!preg_match('/^[0-9a-fA-F]{32}$/', $channel_id))
            throw new \InvalidArgumentException("Invalid channel ID (must be 32-digit hexadecimal)");

        if (!preg_match('/^[0-9a-fA-F]{32}$/', $sender_id))
            throw new \InvalidArgumentException("Invalid sender ID (must be 32-digit hexadecimal)");

        if (!preg_match('/^[0-9a-fA-F]{32}$/', $user_login))
            throw new \InvalidArgumentException("Invalid user ID (must be 32-digit hexadecimal)");

        $this->_channel_id = $channel_id;
        $this->_sender_id = $sender_id;
        $this->_user_login = $user_login;
        $this->_user_password = $user_password;
        $this->isTestMode = (bool) $testModel;
        return $this;
    }

    public function getChannelId () { return $this->_channel_id; }
    public function getSenderId () { return $this->_sender_id; }
    public function getUserLogin () { return $this->_user_login; }
    public function getUserPassword () { return $this->_user_password; }


    /**
     * Check whether the passed token is in a valid format.
     * @param  string  $token The token provided by the client.
     * @return boolean        Whether the passed token is in a valid format.
     */
    private function isValidTokenFormat ($token)
    {
        return preg_match('/^[A-Za-z0-9\.\-\_]{1,300}$/', $token);
    }


    /**
     * Perform a server request using GET to the supplied URL.
     * @param  string $url The relative URL to get.
     * @return Result      A \LinkPay\Result object representing the server response.
     */
    private function get ($url)
    {
        $url = $this->getHost() . $url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return new Result($result);
    }


    /**
     * Perform a server request using POST and the provided details.
     * @param  string  $url                 The URL relative to the server root.
     * @param  array   $postData            An array of POST fields to pass in the request, if any.
     * @param  boolean $provideCredentials  Whether or not to include credentials among the POST data.
     * @return Result                       A \LinkPay\Result object representing the server response.
     */
    private function post ($url, $postData = array(), $provideCredentials = false)
    {
        $url = $this->getHost() . $url;

        $parameters = ($provideCredentials
            ? array_merge($postData, array(
                            'SECURITY.SENDER' => $this->getSenderId(),
                            'TRANSACTION.CHANNEL' => $this->getChannelId(),
                            'TRANSACTION.MODE' => $this->getTransactionMode(),
                            'USER.LOGIN' => $this->getUserLogin(),
                            'USER.PWD' => $this->getUserPassword()))
            : $postData);

        // Build the string from the POST data fields
        $postString = '';
        foreach ($parameters as $k => $v)
            $postString .= urlencode($k) . '=' . urlencode($v) . '&';
        substr_replace($postString, '', -1);

        $postParams = array('http' => array('method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $postString));

        $ctx = stream_context_create($postParams);
        $fp = fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            throw new PayLinkException("Problem with $url");
        }

        $response = stream_get_contents($fp);
        if ($response === false) {
            throw new PayLinkException("Problem reading data from $url");
        }
        return new Result($response);
    }


    /**
     * Get status for the provided token.
     * @param  string  $token  The token for which to check status.
     * @return Result          A \LinkPay\Result object representing the server response.
     */
    public function getStatus ($token)
    {
        if (!$this->isValidTokenFormat($token))
        {
            throw new \InvalidArgumentException("Invalid token");
        }
        return $this->get('/frontend/GetStatus;jsessionid=' . urlencode($token));
    }


    /**
     * Generate a new token for a debit transaction.
     * @param  Transaction  $transaction  The transaction for which to generate a token. Transaction Customer is optional.
     * @return Result                     A \LinkPay\Result object representing the server response.
     */
    public function generateToken ($transaction)
    {
        $data = array(
                'PAYMENT.TYPE' => 'DB',
                'PRESENTATION.AMOUNT' => $transaction->getAmount(),
                'PRESENTATION.CURRENCY' => $transaction->getCurrency(),
                'PRESENTATION.USAGE' => $transaction->getDescription());

        if ($transaction->getCustomer())
        {
            $data['ADDRESS.STREET'] = $transaction->getCustomer()->getStreetAddress();
            $data['ADDRESS.CITY'] = $transaction->getCustomer()->getCity();
            $data['NAME.GIVEN'] = $transaction->getCustomer()->getFirstName();
            $data['NAME.FAMILY'] = $transaction->getCustomer()->getLastName();
            $data['CONTACT.EMAIL'] = $transaction->getCustomer()->getEmail();
        }

        return $this->post('/frontend/GenerateToken', $data, true);
    }

    /**
     * Generate a new token for a pre-authorization transaction.
     * @param  Transaction  $transaction  The transaction for which to generate a token. Transaction Customer is optional.
     * @return Result                     A \LinkPay\Result object representing the server response.
     */
    public function generatePreauthorizationToken ($transaction)
    {
    	$data = array(
    			'PAYMENT.TYPE' => 'PA',
    			'PRESENTATION.AMOUNT' => $transaction->getAmount(),
    			'PRESENTATION.CURRENCY' => $transaction->getCurrency(),
    			'PRESENTATION.USAGE' => $transaction->getDescription());
    
    	if ($transaction->getCustomer())
    	{
    		$data['ADDRESS.STREET'] = $transaction->getCustomer()->getStreetAddress();
    		$data['ADDRESS.CITY'] = $transaction->getCustomer()->getCity();
    		$data['NAME.GIVEN'] = $transaction->getCustomer()->getFirstName();
    		$data['NAME.FAMILY'] = $transaction->getCustomer()->getLastName();
    		$data['CONTACT.EMAIL'] = $transaction->getCustomer()->getEmail();
    	}
    
    	return $this->post('/frontend/GenerateToken', $data, true);
    }
    
    /**
     * Charge an existing token to an already registered card using a card reference. Create a token with generateToken first.
     * @param  string  $token          The token to which the payment should be charged.
     * @param  string  $cardReference  The reference string of the card to charge.
     * @return Result                  A \LinkPay\Result object representing the server response.
     */
    public function executePayment ($token, $cardReference)
    {
        return $this->post("/frontend/ExecutePayment;jsessionid=$token", array(
                    'ACCOUNT.REGISTRATION' => $cardReference,
                    'PAYMENT.METHOD' => 'CC',
                    'FRONTEND.VERSION' => '2',
                    'FRONTEND.MODE' => 'ASYNC'), false);
    }

    /**
     * Create a token for registering card data.
     * @param  Customer  $customer  The details on the customer for which to save card data.
     * @return Result               A \LinkPay\Result object representing the server response.
     */
    public function generateRegistrationToken ($customer)
    {
        return $this->post('/frontend/GenerateToken', array(
                'PAYMENT.TYPE' => 'RG',
                'ADDRESS.STREET' => $customer->getStreetAddress(),
                'ADDRESS.CITY' => $customer->getCity(),
                'NAME.GIVEN' => $customer->getFirstName(),
                'NAME.FAMILY' => $customer->getLastName(),
                'CONTACT.EMAIL' => $customer->getEmail()), true);
    }

    /**
     * Utility method for creating a transaction and charging an existing card using a card reference.
     * @param  Transaction  $transaction    The transaction that will be used to charge the card. Must not contain Customer data.
     * @param  string       $cardReference  The reference of the card to charge.
     * @return Result                       Whether the card was successfully charged.
     */
    public function createAndCharge ($transaction, $cardReference)
    {
        if ($transaction->getCustomer())
        {
            throw new PayLinkException("Transaction for createAndCharge method cannot have a customer defined when using card reference");
        }

        // First, generate a token for the transaction.
        $tokenResult = $this->generateToken($transaction);

        // Then, execute the payment for the provided card reference
        $paymentResult = $this->executePayment($tokenResult->getToken(), $cardReference);

        // And finally return the status of the token
        return $this->getStatus($tokenResult->getToken());
    }
}
