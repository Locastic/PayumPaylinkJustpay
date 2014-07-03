<?php
namespace Locastic\PayLinkPayum\Action;

use PayLink\Customer;
use PayLink\PayLink;
use PayLink\Transaction;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Request\CaptureRequest;
use Payum\Core\Request\GetHttpQueryRequest;
use Payum\Core\Request\ResponseInteractiveRequest;
use Payum\Core\Request\SecuredCaptureRequest;

class CaptureAction extends PaymentAwareAction implements ApiAwareInterface
{
    /**
     * @var PayLink
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof PayLink) {
            throw new UnsupportedApiException('Expected instance of PayLink object.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request CaptureRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $rawModel = (array) $model;
        if (isset($rawModel['transaction']['token'])) {
            $this->payment->execute($getQuery = new GetHttpQueryRequest);
            if (isset($getQuery['token'])) {
                $model['token'] = $getQuery['token'];

                $model->replace($this->api->getStatus($getQuery['token'])->getData());

                return;
            }

            throw new ResponseInteractiveRequest($this->getCreditCardHtml($model['RETURN_URL'], $rawModel['transaction']['token']));
        }

        $customer = new Customer();
        $customer->setFirstName($model['NAME.GIVEN']);
        $customer->setLastName($model['NAME.FAMILY']);
        $customer->setStreetAddress($model['ADDRESS.STREET']);
        $customer->setCity($model['ADDRESS.CITY']);
        $customer->setEmail($model['CONTACT.EMAIL']);

        $transaction = new Transaction();
        $transaction->setAmount($model['PRESENTATION.AMOUNT']);
        $transaction->setCurrency($model['PRESENTATION.CURRENCY']);
        $transaction->setDescription($model['PRESENTATION.USAGE']);
        $transaction->setCustomer($customer);

        $model->replace($this->api->generateToken($transaction)->getData());
        $rawModel = (array) $model;

        if (false == $model['RETURN_URL'] && $request instanceof SecuredCaptureRequest) {
            $model['RETURN_URL'] = $request->getToken()->getTargetUrl();
        }

        if (false == isset($rawModel['transaction']['token'])) {
            throw new LogicException('Token is required to finish the payment');
        }
        if (false == $model['RETURN_URL']) {
            throw new LogicException('RETURN_URL is missing. Pass RETURN_URL explicitly or pass SecuredCaptureRequest');
        }

        throw new ResponseInteractiveRequest($this->getCreditCardHtml($model['RETURN_URL'], $rawModel['transaction']['token']));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CaptureRequest &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }

    /**
     * @param string $returnUrl
     * @param string $token
     *
     * @return string
     */
    protected function getCreditCardHtml($returnUrl, $token)
    {
        return <<<HTML
<html>
<head>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet">

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>
    <script src="https://test.ctpe.net/frontend/widget/v2/widget.js?compressed=false&language=en&style=card"></script>
</head>
<body>
    <form action="{$returnUrl}" id="{$token}">VISA MASTER AMEX</form>
</body>
</html>
HTML;
    }
}
