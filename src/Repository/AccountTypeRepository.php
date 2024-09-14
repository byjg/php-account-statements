<?php

namespace ByJG\AccountStatements\Repository;

use ByJG\AccountStatements\Entity\AccountTypeEntity;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;

class AccountTypeRepository extends BaseRepository
{
    /**
     * AccountTypeRepository constructor.
     *
     * @param DbDriverInterface $dbDriver
     */
    public function __construct(DbDriverInterface $dbDriver, string $accountTypeEntity)
    {
        $this->repository = new Repository($dbDriver, $accountTypeEntity);
    }
}
