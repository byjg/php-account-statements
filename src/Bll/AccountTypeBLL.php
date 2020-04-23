<?php

namespace ByJG\AccountStatements\Bll;

use ByJG\AccountStatements\Entity\AccountTypeEntity;
use ByJG\AccountStatements\Repository\AccountTypeRepository;
use InvalidArgumentException;

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
     * @return AccountTypeEntity|\ByJG\AccountStatements\Entity\AccountTypeEntity[]
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
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
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\MicroOrm\Exception\OrmBeforeInvalidException
     * @throws \ByJG\MicroOrm\Exception\OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update($data)
    {
        $object = new AccountTypeEntity($data);
        $idAccountType = $object->getIdAccountType();

        if (empty($object->getIdAccountType())) {
            throw new InvalidArgumentException('Id account type não pode ser em branco');
        }

        if (empty($object->getName())) {
            throw new InvalidArgumentException('Nome não pode ser em branco');
        }

        $this->accountTypeRepository->save($object);

        return $idAccountType;
    }

    public function getRepository()
    {
        return $this->accountTypeRepository;
    }
}
