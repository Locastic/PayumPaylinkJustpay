<?php

namespace PayLink;

class Transaction
{
    private $_customer;
    private $_amount;
    private $_currency;
    private $_description;

    public function setCustomer (\PayLink\Customer $customer) { $this->_customer = $customer; return $this; }
    public function setAmount ($amount) { $this->_amount = $amount; return $this; }
    public function setCurrency ($currency) { $this->_currency = $currency; return $this; }
    public function setDescription ($description) { $this->_description = $description; return $this; }

    public function getCustomer () { return $this->_customer; }
    public function getAmount () { return $this->_amount; }
    public function getCurrency () { return $this->_currency; }
    public function getDescription () { return $this->_description; }
}