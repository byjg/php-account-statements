<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\Serializer\BaseModel;

/**
 * @SWG\Definition(
 *   description="AccountType",
 * )
 *
 * @object:nodename accounttype
 */
class AccountTypeEntity extends BaseModel
{

    /**
     * @var string
     * @SWG\Property()
     */
    protected $idaccounttype;

    /**
     * @var string
     * @SWG\Property()
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
