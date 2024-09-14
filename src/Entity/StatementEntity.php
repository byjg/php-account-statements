<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\AccountStatements\Exception\AmountException;
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\Serializer\BaseModel;

/**
 * @OA\Definition(
 *   description="Statement",
 * )
 *
 * @object:NodeName statement
 */
#[TableAttribute('statement')]
class StatementEntity extends BaseModel
{

    const BALANCE = "B"; // Inicia um novo valor desprezando os antigos
    const DEPOSIT = "D"; // Adiciona um valor imediatamente ao banco
    const WITHDRAW = "W";
    const REJECT = "R";
    const DEPOSIT_BLOCKED = "DB";
    const WITHDRAW_BLOCKED = "WB";

    /**
     * @var int
     * @OA\Property()
     */
    #[FieldAttribute(primaryKey: true)]
    protected $statementid;

    /**
     * @var int
     * @OA\Property()
     */
    protected $accountid;

    /**
     * @var string
     * @OA\Property()
     */
    protected $typeid;

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
    #[FieldAttribute(syncWithDb: false)]
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
    protected $statementparentid;

    /**
     * @var string
     * @OA\Property()
     */
    protected $referenceid;

    /**
     * @var string
     * @OA\Property()
     */
    protected $referencesource;

    /**
     * @var string
     * @OA\Property()
     */
    protected $accounttypeid;

    public function getStatementId()
    {
        return $this->statementid;
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountid;
    }

    /**
     * @return string
     */
    public function getTypeId()
    {
        return $this->typeid;
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
    public function getStatementParentId()
    {
        return $this->statementparentid;
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
    public function getAccountTypeId()
    {
        return $this->accounttypeid;
    }

    public function setStatementId($statementid)
    {
        $this->statementid = $statementid;
    }

    public function setAccountId($accountid)
    {
        $this->accountid = $accountid;
    }

    public function setTypeId($typeid)
    {
        $this->typeid = $typeid;
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

    public function setStatementParentId($statementparentid)
    {
        $this->statementparentid = $statementparentid;
    }

    public function setAccountTypeId($accounttypeid)
    {
        $this->accounttypeid = $accounttypeid;
    }

    public function setReferenceId($referenceid)
    {
        $this->referenceid = $referenceid;
    }

    public function setReferenceSource($referencesource)
    {
        $this->referencesource = $referencesource;
    }

    /**
     * @var AccountEntity
     */
    protected $account;

    public function attachAccount(AccountEntity $account)
    {
        $this->setAccountId($account->getAccountId());
        $this->setAccountTypeId($account->getAccountTypeId());
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
