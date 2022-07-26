<?php

namespace ActiveCollab\Quickbooks\Data;

class Entity implements \JsonSerializable
{

    protected $data = [];

    /**
     * Construct entity
     * 
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return id
     * 
     * @return int
     */
    public function getId()
    {
        return isset($this->data['Id']) ? (integer) $this->data['Id'] : null;
    }

    /**
     * Return RateValue
     *
     * @return int
     */
    public function getRateValue()
    {
        return isset($this->data['RateValue']) ? (integer) $this->data['RateValue'] : null;
    }

    /**
     * Return raw data
     * 
     * @return array
     */
    public function getRawData()
    {
        return $this->data;
    }

    /**
     * Serialize data
     * 
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}
