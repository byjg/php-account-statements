<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\AccountStatements\Exception\AmountException;
use ByJG\MicroOrm\Attributes\FieldAttribute;
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
     * @var int|null
     * @OA\Property()
     */
    #[FieldAttribute(primaryKey: true)]
    protected ?int $accountid = null;

    /**
     * @var string|null
     * @OA\Property()
     */
    protected ?string $accounttypeid = null;

    /**
     * @var string|null
     * @OA\Property()
     */
    protected ?string $userid = null;

    /**
     * @var float|null
     * @OA\Property()
     */
    protected ?float $grossbalance = null;

    /**
     * @var float|null
     * @OA\Property()
     */
    protected ?float $uncleared = null;

    /**
     * @var float|null
     * @OA\Property()
     */
    protected ?float $netbalance = null;

    /**
     * @var float|null
     * @OA\Property()
     */
    protected ?float $price = null;

    /**
     * @var string|null
     * @OA\Property()
     */
    protected ?string $extra = null;

    /**
     * @var string|null
     * @OA\Property()
     */
    #[FieldAttribute(syncWithDb: false)]
    protected ?string $entrydate = null;

    /**
     * @var float|null
     * @OA\Property()
     */
    protected ?float $minvalue = null;

    public function getAccountId(): ?int
    {
        return $this->accountid;
    }

    public function getAccountTypeId(): ?string
    {
        return $this->accounttypeid;
    }

    public function getUserId(): ?string
    {
        return $this->userid;
    }

    public function getGrossBalance(): ?float
    {
        return $this->grossbalance;
    }

    public function getUnCleared(): ?float
    {
        return $this->uncleared;
    }

    public function getNetBalance(): ?float
    {
        return $this->netbalance;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getExtra(): ?string
    {
        return $this->extra;
    }

    public function getEntrydate(): ?string
    {
        return $this->entrydate;
    }

    public function getMinValue(): ?float
    {
        return $this->minvalue;
    }

    public function setAccountId($accountid): void
    {
        $this->accountid = $accountid;
    }

    public function setAccountTypeId(?string $accounttypeid): void
    {
        $this->accounttypeid = $accounttypeid;
    }

    public function setUserId(?string $userid): void
    {
        $this->userid = $userid;
    }

    public function setGrossBalance(?float $grossbalance): void
    {
        $this->grossbalance = $grossbalance;
    }

    public function setUncleared(?float $unCleared): void
    {
        $this->uncleared = $unCleared;
    }

    public function setNetBalance(?float $netbalance): void
    {
        $this->netbalance = $netbalance;
    }

    public function setPrice(?float $price): void
    {
        $this->price = $price;
    }

    public function setExtra(?string $extra): void
    {
        $this->extra = $extra;
    }

    public function setEntryDate(?string $entryDate): void
    {
        $this->entrydate = $entryDate;
    }

    public function setMinValue(?float $minvalue): void
    {
        $this->minvalue = $minvalue;
    }

    /**
     *
     * @throws AmountException
     */
    public function validate(): void
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
