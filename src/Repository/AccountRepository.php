<?php

namespace ByJG\AccountStatements\Repository;

use ByJG\AccountStatements\Entity\AccountEntity;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;
use ByJG\MicroOrm\FieldMapping;
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
     * @param string $accountEntity
     * @param FieldMapping[] $fieldMappingList
     * @throws OrmModelInvalidException
     * @throws \ReflectionException
     */
    public function __construct(DbDriverInterface $dbDriver, string $accountEntity, array $fieldMappingList = [])
    {
        $this->repository = new Repository($dbDriver, $accountEntity);

        $mapper = $this->repository->getMapper();
        foreach ($fieldMappingList as $fieldMapping) {
            $mapper->addFieldMapping($fieldMapping);
        }
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

    /**
     * @param $userId
     * @param string $statementId
     * @return AccountEntity|null
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getByStatementId($statementId)
    {
        $query = Query::getInstance()
            ->fields(['account.*'])
            ->table($this->repository->getMapper()->getTable())
            ->join('statement', 'statement.accountid = account.accountid')
            ->where('statementid = :statementid', ['statementid' => $statementId])
        ;

        $result = $this->repository
            ->getByQuery($query);

        if (empty($result)) {
            return null;
        }

        return $result[0];
    }
}
