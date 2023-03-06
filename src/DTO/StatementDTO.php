<?php


namespace ByJG\AccountStatements\DTO;


class StatementDTO
{
    protected $accountId;
    protected $amount;

    protected $description = null;
    protected $reference = null;
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
    public function getReference()
    {
        return $this->reference;
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
     * @param string $reference
     * @return StatementDTO
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
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