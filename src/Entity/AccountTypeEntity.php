<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\Serializer\BaseModel;

/**
 * @OA\Definition(
 *   description="AccountType",
 * )
 *
 * @object:nodename accounttype
 */
class AccountTypeEntity extends BaseModel
{

    /**
     * @var string
     * @OA\Property()
     */
    protected $idaccounttype;

    /**
     * @var string
     * @OA\Property()
     */
    protected $name;
    
    public function getIdAccountType()
    {
        return $this->idaccounttype;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setIdAccountType($idaccounttype)
    {
        $this->idaccounttype = $idaccounttype;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
