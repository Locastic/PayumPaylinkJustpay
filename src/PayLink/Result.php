<?php

namespace PayLink;

class Result
{
    private $_raw = null;
    private $_data = null;

    public function __construct ($serverResponse)
    {
        $this->_raw = $serverResponse;
        $this->_data = json_decode($serverResponse, true);
    }

    public function getData ()
    {
        return $this->_data;
    }

    public function getRaw ()
    {
        return $this->_raw;
    }

    public function getCardReference ()
    {
        return $this->_data['transaction']['identification']['uniqueId'];
    }

    public function getTransactionReferenceId ()
    {
    	return $this->_data['transaction']['identification']['uniqueId'];
    }
    
    public function getToken ()
    {
        return $this->_data['transaction']['token'];
    }

    public function isSuccess ()
    {
        return (bool)(isset($this->_data['transaction'])
            && isset($this->_data['transaction']['processing'])
            && isset($this->_data['transaction']['processing']['result'])
            && ($this->_data['transaction']['processing']['result'] == 'ACK'));
    }
}



/*

Example results:

// Successful transaction

{
    "transaction":{
        "channel":"c1c021a4bfca258d4da22a655dc42966",
        "identification":{
            "shopperid":"admin",
            "shortId":"7307.0292.8546",
            "transactionid":"20130129120736562fb049d9e1aee0686f9005f4515f2e",
            "uniqueId":"40288b163c865d30013c86600d6d0002"
        },
        "mode":"CONNECTOR_TEST",
        "payment":{
            "code":"CC.DB"
        },
        "processing":{
            "code":"CC.DB.90.00",
            "reason":{
                "code":"00",
                "message":"Successful Processing"
            },
            "result":"ACK",
            "return":{
                "code":"000.100.112",
                "message":"Request successfully processed in Merchant in Connector Test Mode"
            },
            "timestamp":"2013-01-29 12:55:14"
        },
        "response":"SYNC"
    }
}

// Rejected transaction

{
    "transaction":{
        "channel":"c1c021a4bfca258d4da22a655dc42966",
        "identification":{
            "shopperid":"admin",
            "shortId":"0435.0816.1186",
            "transactionid":"20130129120736562fb049d9e1aee0686f9005f4515f2e",
            "uniqueId":"40288b163c865d30013c866d69a2002a"
        },
        "mode":"CONNECTOR_TEST",
        "payment":{
            "code":"CC.DB"
        },
        "processing":{
            "code":"CC.DB.70.40",
            "reason":{
                "code":"40",
                "message":"Account Validation"
            },
            "result":"NOK",
            "return":{
                "code":"100.100.700",
                "message":"invalid cc number/brand combination"
            },
            "timestamp":"2013-01-29 13:09:42"
        },
        "response":"SYNC"
    }
}

 */