<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\AccountStatements\Exception\AmountException;
use ByJG\Serializer\BaseModel;

/**
 * @OA\Definition(
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
     * @OA\Property()
     */
    protected $idstatement;

    /**
     * @var int
     * @OA\Property()
     */
    protected $idaccount;

    /**
     * @var string
     * @OA\Property()
     */
    protected $idtype;

    /**
     * @var float
     * @OA\Property()
     */
    protected $amount;

    /**
     * @var float
     * @OA\Property()
     */
    protected $price;

    /**
     * @var string
     * @OA\Property()
     */
    protected $date;

    /**
     * @var float
     * @OA\Property()
     */
    protected $grossbalance;

    /**
     * @var float
     * @OA\Property()
     */
    protected $uncleared;

    /**
     * @var float
     * @OA\Property()
     */
    protected $netbalance;

    /**
     * @var string
     * @OA\Property()
     */
    protected $code;

    /**
     * @var string
     * @OA\Property()
     */
    protected $description;

    /**
     * @var string
     * @OA\Property()
     */
    protected $idstatementparent;

    /**
     * @var string
     * @OA\Property()
     */
    protected $reference;

    /**
     * @var string
     * @OA\Property()
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
    public function getCode()
    {
        return $this->code;
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

    public function setCode($code)
    {
        $this->code = $code;
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
