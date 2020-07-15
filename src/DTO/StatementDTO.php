<?php


namespace ByJG\AccountStatements\DTO;


class StatementDTO
{
    protected $idaccount;
    protected $amount;

    protected $description = null;
    protected $reference = null;
    protected $code = null;

    /**
     * StatementDTO constructor.
     * @param $idaccount
     * @param $amount
     */
    public function __construct($idaccount, $amount)
    {
        $this->idaccount = $idaccount;
        $this->amount = $amount;
    }

    public static function instance($idaccount, $amount)
    {
        return new StatementDTO($idaccount, $amount);
    }

    /**
     * @return mixed
     */
    public function getIdaccount()
    {
        return $this->idaccount;
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