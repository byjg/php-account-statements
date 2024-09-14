<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\AccountStatements\Exception\AmountException;
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\FieldReadOnlyAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\Serializer\BaseModel;

/**
 * @OA\Definition(
 *   description="Account",
 * )
 *
 * @object:NodeName account
 */
#[TableAttribute('account')]
class AccountEntity extends BaseModel
{
    /**
     * @var int
     * @OA\Property()
     */
    #[FieldAttribute(primaryKey: true)]
    protected $accountid;

    /**
     * @var string
     * @OA\Property()
     */
    protected $accounttypeid;

    /**
     * @var string
     * @OA\Property()
     */
    protected $userid;

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
     * @var float
     * @OA\Property()
     */
    protected $price;

    /**
     * @var string
     * @OA\Property()
     */
    protected $extra;

    /**
     * @var string
     * @OA\Property()
     */
    #[FieldAttribute(syncWithDb: false)]
    protected $entrydate;

    /**
     * @var float
     * @OA\Property()
     */
    protected $minvalue;

    public function getAccountId()
    {
        return $this->accountid;
    }

    public function getAccountTypeId()
    {
        return $this->accounttypeid;
    }

    public function getUserId()
    {
        return $this->userid;
    }

    public function getGrossBalance()
    {
        return $this->grossbalance;
    }

    public function getUnCleared()
    {
        return $this->uncleared;
    }

    public function getNetBalance()
    {
        return $this->netbalance;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getExtra()
    {
        return $this->extra;
    }

    public function getEntrydate()
    {
        return $this->entrydate;
    }

    public function getMinValue()
    {
        return $this->minvalue;
    }

    public function setAccountId($accountid)
    {
        $this->accountid = $accountid;
    }

    public function setAccountTypeId($accounttypeid)
    {
        $this->accounttypeid = $accounttypeid;
    }

    public function setUserId($userid)
    {
        $this->userid = $userid;
    }

    public function setGrossBalance($grossbalance)
    {
        $this->grossbalance = $grossbalance;
    }

    public function setUncleared($unCleared)
    {
        $this->uncleared = $unCleared;
    }

    public function setNetBalance($netbalance)
    {
        $this->netbalance = $netbalance;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    public function setEntryDate($entryDate)
    {
        $this->entrydate = $entryDate;
    }

    public function setMinValue($minvalue)
    {
        $this->minvalue = $minvalue;
    }

    /**
     *
     * @throws AmountException
     */
    public function validate()
    {
        $minValue = $this->getMinValue();

        if ($this->getNetBalance() < $minValue
            || $this->getGrossBalance() < $minValue
            || $this->getUnCleared() < $minValue
        ) {
            throw new AmountException('Valor não pode ser menor que ' . $minValue);
        }
    }
}
