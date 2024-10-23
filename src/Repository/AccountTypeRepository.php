<?php

namespace ByJG\AccountStatements\Repository;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;
use ReflectionException;

class AccountTypeRepository extends BaseRepository
{
    /**
     * AccountTypeRepository constructor.
     *
     * @param DbDriverInterface $dbDriver
     * @param string $accountTypeEntity
     * @throws OrmModelInvalidException
     * @throws ReflectionException
     */
    public function __construct(DbDriverInterface $dbDriver, string $accountTypeEntity)
    {
        $this->repository = new Repository($dbDriver, $accountTypeEntity);
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getMapper(): Mapper
    {
        return $this->repository->getMapper();
    }

}
