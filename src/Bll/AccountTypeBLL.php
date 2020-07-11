<?php

namespace ByJG\AccountStatements\Bll;

use ByJG\AccountStatements\Entity\AccountTypeEntity;
use ByJG\AccountStatements\Exception\AccountTypeException;
use ByJG\AccountStatements\Repository\AccountTypeRepository;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\Serializer\Exception\InvalidArgumentException;

class AccountTypeBLL
{

    protected $accountTypeRepository;

    /**
     * AccountTypeBLL constructor.
     * @param $accountTypeRepository
     */
    public function __construct(AccountTypeRepository $accountTypeRepository)
    {
        $this->accountTypeRepository = $accountTypeRepository;
    }


    /**
     * Obtém um AccountType por ID.
     * Se o ID não for passado, então devolve todos os AccountTypes.
     *
     * @param int|string $idAccountType Opcional. Se não for passado obtém todos
     * @return AccountTypeEntity|AccountTypeEntity[]
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getById($idAccountType)
    {
        return $this->accountTypeRepository->getById($idAccountType);
    }

    /**
     * Salvar ou Atualizar um AccountType
     *
     * @param mixed $data
     * @return int Id do objeto inserido atualizado
     * @throws AccountTypeException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws InvalidArgumentException
     */
    public function update($data)
    {
        $object = new AccountTypeEntity($data);
        $idAccountType = $object->getIdAccountType();

        if (empty($object->getIdAccountType())) {
            throw new AccountTypeException('Id account type não pode ser em branco');
        }

        if (empty($object->getName())) {
            throw new AccountTypeException('Nome não pode ser em branco');
        }

        $this->accountTypeRepository->save($object);

        return $idAccountType;
    }

    public function getRepository()
    {
        return $this->accountTypeRepository;
    }
}
