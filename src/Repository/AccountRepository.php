<?php

namespace ByJG\AccountStatements\Repository;

use ByJG\AccountStatements\Entity\AccountEntity;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;
use ByJG\Serializer\Exception\InvalidArgumentException;

class AccountRepository extends BaseRepository
{
    /**
     * AccountRepository constructor.
     *
     * @param DbDriverInterface $dbDriver
     */
    public function __construct(DbDriverInterface $dbDriver)
    {
        $mapper = new Mapper(
            AccountEntity::class,
            'account',
            'accountid'
        );

        $mapper->addFieldMap("entrydate", "entrydate", function () { return false; }, function () { return false; });

        $this->repository = new Repository($dbDriver, $mapper);
    }

    /**
     * @param $userId
     * @param string $accountType
     * @return mixed
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getByUserId($userId, $accountType = "")
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where('userid = :userid', ['userid' => $userId])
        ;

        if (!empty($accountType)) {
            $query->where("accounttypeid = :acctype", ["acctype" => $accountType]);
        }

        return $this->repository
            ->getByQuery($query);
    }

    /**
     * @param $accountTypeId
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getByAccountTypeId($accountTypeId)
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where("accounttypeid = :acctype", ["acctype" => $accountTypeId])
        ;


        return $this->repository
            ->getByQuery($query);
    }
}
