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
use ReflectionException;

class AccountRepository extends BaseRepository
{
    /**
     * AccountRepository constructor.
     *
     * @param DbDriverInterface $dbDriver
     * @param string $accountEntity
     * @param FieldMapping[] $fieldMappingList
     * @throws OrmModelInvalidException
     * @throws ReflectionException
     */
    public function __construct(DbDriverInterface $dbDriver, string $accountEntity, array $fieldMappingList = [])
    {
        $this->repository = new Repository($dbDriver, $accountEntity);

        $mapper = $this->repository->getMapper();
        foreach ($fieldMappingList as $fieldMapping) {
            $mapper->addFieldMapping($fieldMapping);
        }
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getMapper(): Mapper
    {
        return $this->repository->getMapper();
    }

    /**
     * @param string $userId
     * @param string $accountType
     * @return array
     */
    public function getByUserId(string $userId, string $accountType = ""): array
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
     * @param string $accountTypeId
     * @return array
     */
    public function getByAccountTypeId(string $accountTypeId): array
    {
        $query = Query::getInstance()
            ->table($this->repository->getMapper()->getTable())
            ->where("accounttypeid = :acctype", ["acctype" => $accountTypeId])
        ;


        return $this->repository
            ->getByQuery($query);
    }

    /**
     * @param int $statementId
     * @return AccountEntity|null
     * @throws InvalidArgumentException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function getByStatementId(int $statementId): ?AccountEntity
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
