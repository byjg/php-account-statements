<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\Serializer\BaseModel;

/**
 * @OA\Definition(
 *   description="AccountType",
 * )
 *
 * @object:nodename accounttype
 */
#[TableAttribute('accounttype')]
class AccountTypeEntity extends BaseModel
{

    /**
     * @var string
     * @OA\Property()
     */
    #[FieldAttribute(primaryKey: true)]
    protected $accounttypeid;

    /**
     * @var string
     * @OA\Property()
     */
    protected $name;
    
    public function getAccountTypeId()
    {
        return $this->accounttypeid;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setAccountTypeId($accounttypeid)
    {
        $this->accounttypeid = $accounttypeid;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
