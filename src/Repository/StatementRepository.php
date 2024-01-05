<?php

namespace ByJG\AccountStatements\Repository;

use ByJG\AccountStatements\Entity\StatementEntity;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;
use ByJG\Serializer\Exception\InvalidArgumentException;

class StatementRepository extends BaseRepository
{
    /**
     * StatementRepository constructor.
     *
     * @param DbDriverInterface $dbDriver
     * @param FieldMapping[] $fieldMappingList
     */
    public function __construct(DbDriverInterface $dbDriver, array $fieldMappingList = [])
    {
        $mapper = new Mapper(
            StatementEntity::class,
            'statement',
            'statementid'
        );

        $mapper->addFieldMapping(FieldMapping::create("date")->withUpdateFunction(Mapper::doNotUpdateClosure()));
        foreach ($fieldMappingList as $fieldMapping) {
            $mapper->addFieldMapping($fieldMapping);
        }

        $this->repository = new Repository($dbDriver, $mapper);
    }

    /**
     * Obtém um Statement pelo seu ID.
     *
     * @param int $parentId
     * @param bool $forUpdate
     * @return StatementEntity
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getByParentId($parentId, $forUpdate = false)
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where('statementparentid = :id', ['id' => $parentId])
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
     * @param int $accountId
     * @param int $limit
     * @return StatementEntity[]
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getByAccountId($accountId, $limit = 20)
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where("accountid = :id", ["id" => $accountId])
            ->limit(0, $limit)
        ;

        return $this->repository->getByQuery($query);
    }

    /**
     * @param null $accountId
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getUnclearedStatements($accountId = null)
    {
        $query = Query::getInstance()
            ->fields([
                "st1.*",
                "ac.accounttypeid",
            ])
            ->table($this->repository->getMapper()->getTable() . " st1")
            ->join("account ac", "st1.accountid = ac.accountid")
            ->leftJoin("statement st2", "st1.statementid = st2.statementparentid")
            ->where("st1.typeid in ('WB', 'DB')")
            ->where("st2.statementid is null")
            ->orderBy(["st1.date desc"])
        ;

        if (!empty($accountId)) {
            $query->where("st1.accountid = :id", ["id" => $accountId]);
        }

        return $this->repository->getByQuery($query);
    }

    /**
     * @param null $accountId
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getByDate($accountId, $startDate, $endDate)
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where("date between :start and :end", ["start" => $startDate, "end" => $endDate])
            ->where("accountid = :id", ["id" => $accountId])
            ->orderBy(["date"])
        ;

        return $this->repository->getByQuery($query);
    }

    public function getByCode($accountId, $code, $startDate = null, $endDate = null)
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where("code = :code", ["code" => $code])
            ->where("accountid = :id", ["id" => $accountId])
            ->orderBy(["date"])
        ;

        if (!empty($startDate)) {
            $query->where("date >= :start", ["start" => $startDate]);
        }

        if (!empty($endDate)) {
            $query->where("date <= :end", ["end" => $endDate]);
        }

        return $this->repository->getByQuery($query);
    }

    public function getByReferenceId($accountId, $referenceSource, $referenceId, $startDate = null, $endDate = null)
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where("referencesource = :source", ["source" => $referenceSource])
            ->where("referenceid = :id", ["id" => $referenceId])
            ->where("accountid = :accountid", ["accountid" => $accountId])
            ->orderBy(["date"])
        ;

        if (!empty($startDate)) {
            $query->where("date >= :start", ["start" => $startDate]);
        }

        if (!empty($endDate)) {
            $query->where("date <= :end", ["end" => $endDate]);
        }

        return $this->repository->getByQuery($query);
    }
}
