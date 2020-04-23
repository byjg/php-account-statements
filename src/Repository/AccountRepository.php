<?php

namespace ByJG\AccountStatements\Repository;

use ByJG\AccountStatements\Entity\AccountEntity;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;

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
            'idaccount'
        );

        $this->repository = new Repository($dbDriver, $mapper);
    }

    /**
     * @param $idUser
     * @param string $accountType
     * @return mixed
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getUserId($idUser, $accountType = "")
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where('iduser = :iduser', ['iduser' => $idUser])
        ;

        if (!empty($accountType)) {
            $query->where("idaccounttype = :acctype", ["acctype" => $accountType]);
        }

        return $this->repository
            ->getByQuery($query);
    }

    /**
     * @param $idAccountType
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getAccountTypeId($idAccountType)
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where("idaccounttype = :acctype", ["acctype" => $idAccountType])
        ;


        return $this->repository
            ->getByQuery($query);
    }
}
