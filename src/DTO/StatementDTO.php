<?php


namespace ByJG\AccountStatements\DTO;


class StatementDTO
{
    protected $accountId = null;
    protected $amount = null;

    protected $description = null;
    protected $referenceId = null;
    protected $referenceSource = null;
    protected $code = null;

    /**
     * StatementDTO constructor.
     * @param $accountId
     * @param $amount
     */
    public function __construct($accountId, $amount)
    {
        $this->accountId = $accountId;
        $this->amount = $amount;
    }

    public static function create($accountId, $amount)
    {
        return new StatementDTO($accountId, $amount);
    }

    public static function createEmpty()
    {
        return new StatementDTO(null, null);
    }

    public function hasAccount()
    {
        return !empty($this->accountId) && !empty($this->amount);
    }

    public function setToStatement($statement)
    {
        if (!empty($this->getDescription())) {
            $statement->setDescription($this->getDescription());
        }
        if (!empty($this->getCode())) {
            $statement->setCode($this->getCode());
        }
        if (!empty($this->getReferenceId())) {
            $statement->setReferenceId($this->getReferenceId());
        }
        if (!empty($this->getReferenceSource())) {
            $statement->setReferenceSource($this->getReferenceSource());
        }
    }

    /**
     * @return mixed
     */
    public function getAccountId()
    {
        return $this->accountId;
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
        return $this->referenceId;
    }

    /**
     * @return string
     */
    public function getReferenceSource()
    {
        return $this->referenceSource;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;
        return $this;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
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
     * @param string $referenceId
     * @return StatementDTO
     */
    public function setReferenceId($referenceId)
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    /**
     * @param string $referenceSource
     * @return StatementDTO
     */
    public function setReferenceSource($referenceSource)
    {
        $this->referenceSource = $referenceSource;
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