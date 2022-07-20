<?php

namespace ActiveCollab\Quickbooks\Data;

class BatchEntity implements \JsonSerializable
{

    protected $data = [];

    private $batchId;

    /**
     * Construct entity
     *
     * @param array $data
     * @param       $batchId
     */
    public function __construct(array $data, $batchId)
    {
        $this->data = $data;
        $this->batchId = $batchId;
    }

    public function getBatchId()
    {
        return $this->batchId;
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