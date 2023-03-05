<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\AccountStatements\Exception\AmountException;
use ByJG\Serializer\BaseModel;

/**
 * @OA\Definition(
 *   description="Account",
 * )
 *
 * @object:NodeName account
 */
class AccountEntity extends BaseModel
{
    /**
     * @var int
     * @OA\Property()
     */
    protected $idaccount;

    /**
     * @var string
     * @OA\Property()
     */
    protected $idaccounttype;

    /**
     * @var string
     * @OA\Property()
     */
    protected $iduser;

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
    protected $entrydate;

    /**
     * @var float
     * @OA\Property()
     */
    protected $minvalue;

    public function getIdAccount()
    {
        return $this->idaccount;
    }

    public function getIdAccountType()
    {
        return $this->idaccounttype;
    }

    public function getIdUser()
    {
        return $this->iduser;
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

    public function setIdAccount($idaccount)
    {
        $this->idaccount = $idaccount;
    }

    public function setIdAccountType($idaccounttype)
    {
        $this->idaccounttype = $idaccounttype;
    }

    public function setIdUser($iduser)
    {
        $this->iduser = $iduser;
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
