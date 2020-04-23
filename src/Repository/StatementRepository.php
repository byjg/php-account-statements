<?php

namespace ByJG\AccountStatements\Repository;

use ByJG\AccountStatements\Entity\StatementEntity;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Query;

class StatementRepository extends BaseRepository
{
    /**
     * StatementRepository constructor.
     *
     * @param DbDriverInterface $dbDriver
     */
    public function __construct(DbDriverInterface $dbDriver)
    {
        $mapper = new Mapper(
            StatementEntity::class,
            'statement',
            'idstatement'
        );

        $this->repository = new \ByJG\MicroOrm\Repository($dbDriver, $mapper);
    }

    /**
     * Obtém um Statement pelo seu ID.
     *
     * @param int $idParent
     * @param bool $forUpdate
     * @return StatementEntity
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getByIdParent($idParent, $forUpdate = false)
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where('idstatementparent = :id', ['id' => $idParent])
        ;

        if ($forUpdate) {
            $query->forUpdate();
        }

        $result = $this->repository->getByQuery($query);

        if (count($result) > 0) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * @param int $idAccount
     * @param int $limit
     * @return StatementEntity[]
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getByAccountId($idAccount, $limit = 20)
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where("idaccount = :id", ["id" => $idAccount])
            ->limit(0, $limit)
        ;

        return $this->repository->getByQuery($query);
    }

    /**
     * @param null $idaccount
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getUnclearedStatements($idaccount = null)
    {
        $query = Query::getInstance()
            ->fields([
                "st1.*",
                "ac.idaccounttype",
            ])
            ->table($this->repository->getMapper()->getTable() . " st1")
            ->join("account ac", "st1.idaccount = ac.idaccount")
            ->leftJoin("statement st2", "st1.idstatement = st2.idstatementparent")
            ->where("st1.idtype = 'WB'")
            ->where("st2.idstatement is null")
            ->orderBy(["st1.date desc"])
        ;

        if (!empty($idaccount)) {
            $query->where("st1.idaccount = :id", ["id" => $idaccount]);
        }

        return $this->repository->getByQuery($query);
    }
}
