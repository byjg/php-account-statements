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
     * @param int $accountTypeId Opcional. Se não for passado obtém todos
     * @return AccountTypeEntity|AccountTypeEntity[]
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function getById(string $accountTypeId): array|AccountTypeEntity
    {
        return $this->accountTypeRepository->getById($accountTypeId);
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
        $accountTypeId = $object->getAccountTypeId();

        if (empty($object->getAccountTypeId())) {
            throw new AccountTypeException('Id account type não pode ser em branco');
        }

        if (empty($object->getName())) {
            throw new AccountTypeException('Nome não pode ser em branco');
        }

        $this->accountTypeRepository->save($object);

        return $accountTypeId;
    }

    public function getRepository()
    {
        return $this->accountTypeRepository;
    }
}
