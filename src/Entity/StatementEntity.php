<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\AccountStatements\Exception\AmountException;
use ByJG\Serializer\BaseModel;

/**
 * @SWG\Definition(
 *   description="Statement",
 * )
 *
 * @object:NodeName statement
 */
class StatementEntity extends BaseModel
{

    const BALANCE = "B"; // Inicia um novo valor desprezando os antigos
    const DEPOSIT = "D"; // Adiciona um valor imediatamente ao banco
    const WITHDRAW = "W";
    const REJECT = "R";
    const DEPOSITBLOCKED = "DB";
    const WITHDRAWBLOCKED = "WB";

    /**
     * @var int
     * @SWG\Property()
     */
    protected $idstatement;

    /**
     * @var int
     * @SWG\Property()
     */
    protected $idaccount;

    /**
     * @var string
     * @SWG\Property()
     */
    protected $idtype;

    /**
     * @var float
     * @SWG\Property()
     */
    protected $amount;

    /**
     * @var float
     * @SWG\Property()
     */
    protected $price;

    /**
     * @var string
     * @SWG\Property()
     */
    protected $date;

    /**
     * @var float
     * @SWG\Property()
     */
    protected $grossbalance;

    /**
     * @var float
     * @SWG\Property()
     */
    protected $uncleared;

    /**
     * @var float
     * @SWG\Property()
     */
    protected $netbalance;

    /**
     * @var string
     * @SWG\Property()
     */
    protected $description;

    /**
     * @var string
     * @SWG\Property()
     */
    protected $idstatementparent;

    /**
     * @var string
     * @SWG\Property()
     */
    protected $reference;

    /**
     * @var string
     * @SWG\Property()
     */
    protected $idaccounttype;

    public function getIdStatement()
    {
        return $this->idstatement;
    }

    /**
     * @return int
     */
    public function getIdAccount()
    {
        return $this->idaccount;
    }

    /**
     * @return string
     */
    public function getIdType()
    {
        return $this->idtype;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return float
     */
    public function getGrossBalance()
    {
        return $this->grossbalance;
    }

    /**
     * @return float
     */
    public function getUnCleared()
    {
        return $this->uncleared;
    }

    /**
     * @return float
     */
    public function getNetBalance()
    {
        return $this->netbalance;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getIdStatementParent()
    {
        return $this->idstatementparent;
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
    public function getIdAccountType()
    {
        return $this->idaccounttype;
    }

    public function setIdStatement($idstatement)
    {
        $this->idstatement = $idstatement;
    }

    public function setIdAccount($idaccount)
    {
        $this->idaccount = $idaccount;
    }

    public function setIdType($idtype)
    {
        $this->idtype = $idtype;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function setGrossBalance($grossbalance)
    {
        $this->grossbalance = $grossbalance;
    }

    public function setUnCleared($uncleared)
    {
        $this->uncleared = $uncleared;
    }

    public function setNetBalance($netbalance)
    {
        $this->netbalance = $netbalance;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setIdStatementParent($idstatementparent)
    {
        $this->idstatementparent = $idstatementparent;
    }

    public function setIdAccountType($idaccounttype)
    {
        $this->idaccounttype = $idaccounttype;
    }

    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @var AccountEntity
     */
    protected $account;

    public function attachAccount(AccountEntity $account)
    {
        $this->setIdAccount($account->getIdAccount());
        $this->setIdAccountType($account->getIdAccountType());
        $this->setGrossBalance($account->getGrossBalance());
        $this->setNetBalance($account->getNetBalance());
        $this->setUnCleared($account->getUnCleared());
        $this->setPrice($account->getPrice());

        $this->account = $account;
    }

    /**
     *
     * @throws AmountException
     * @throws AmountException
     */
    public function validate()
    {
        if ($this->getAmount() < 0) {
            throw new AmountException('Amount não pode ser menor que zero');
        }

        if (empty($this->account)) {
            return;
        }

        if ($this->getNetBalance() < $this->account->getMinValue()
            || $this->getGrossBalance() < $this->account->getMinValue()
            || $this->getUnCleared() < $this->account->getMinValue()
        ) {
            throw new AmountException('Valor não pode ser menor que ' . $this->account->getMinValue());
        }
    }
}
