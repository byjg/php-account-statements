<?php


namespace ByJG\AccountStatements\DTO;


class StatementDTO
{
    protected $accountId;
    protected $amount;

    protected $description = null;
    protected $referenceid = null;
    protected $referencesource = null;
    protected $code = null;

    /**
     * StatementDTO constructor.
     * @param $accountId
     * @param $amount
     */
    public function __construct($accountId, $amount)
    {
        $this->accountid = $accountId;
        $this->amount = $amount;
    }

    public static function instance($accountId, $amount)
    {
        return new StatementDTO($accountId, $amount);
    }

    /**
     * @return mixed
     */
    public function getaccountId()
    {
        return $this->accountid;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getReferenceId()
    {
        return $this->referenceid;
    }

    /**
     * @return string
     */
    public function getReferenceSource()
    {
        return $this->referencesource;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $description
     * @return StatementDTO
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $referenceid
     * @return StatementDTO
     */
    public function setReferenceId($referenceid)
    {
        $this->referenceid = $referenceid;
        return $this;
    }

    /**
     * @param string $referencesource
     * @return StatementDTO
     */
    public function setReferenceSource($referencesource)
    {
        $this->referencesource = $referencesource;
        return $this;
    }

    /**
     * @param string $code
     * @return StatementDTO
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }


}