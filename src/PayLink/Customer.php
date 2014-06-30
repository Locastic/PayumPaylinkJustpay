<?php

namespace PayLink;

class Customer
{
    private $_firstName;
    private $_lastName;
    private $_streetAddress;
    private $_city;
    private $_email;

    public function setFirstName ($firstName) { $this->_firstName = $firstName; return $this; }
    public function setLastName ($lastName) { $this->_lastName = $lastName; return $this; }
    public function setStreetAddress ($streetAddress) { $this->_streetAddress = $streetAddress; return $this; }
    public function setCity ($city) { $this->_city = $city; return $this; }
    public function setEmail ($email) { $this->_email = $email; return $this; }

    public function getFirstName () { return $this->_firstName; }
    public function getLastName () { return $this->_lastName; }
    public function getStreetAddress () { return $this->_streetAddress; }
    public function getCity () { return $this->_city; }
    public function getEmail () { return $this->_email; }
}