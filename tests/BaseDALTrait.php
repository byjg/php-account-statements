<?php

namespace Test;

use ByJG\AccountStatements\Bll\AccountBLL;
use ByJG\AccountStatements\Bll\AccountTypeBLL;
use ByJG\AccountStatements\Bll\StatementBLL;
use ByJG\AccountStatements\Entity\AccountTypeEntity;
use ByJG\AccountStatements\Repository\AccountRepository;
use ByJG\AccountStatements\Repository\AccountTypeRepository;
use ByJG\AccountStatements\Repository\StatementRepository;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\DbMigration\Database\MySqlDatabase;
use ByJG\DbMigration\Migration;
use ByJG\Util\Uri;

trait BaseDALTrait
{

    /**
     * @var AccountBLL
     */
    protected $accountBLL;

    /**
     * @var AccountTypeBLL
     */
    protected $accountTypeBLL;

    /**
     * @var StatementBLL
     */
    protected $statementBLL;

    public function prepareObjects()
    {
        $accountRepository = new AccountRepository($this->dbDriver);
        $accountTypeRepository = new AccountTypeRepository($this->dbDriver);
        $statementRepository = new StatementRepository($this->dbDriver);

        $this->accountTypeBLL = new AccountTypeBLL($accountTypeRepository);
        $this->statementBLL = new StatementBLL($statementRepository, $accountRepository);
        $this->accountBLL = new AccountBLL($accountRepository, $this->accountTypeBLL, $this->statementBLL);
    }

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var DbDriverInterface
     */
    protected $dbDriver;

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\DbMigration\Exception\DatabaseDoesNotRegistered
     * @throws \ByJG\DbMigration\Exception\DatabaseIsIncompleteException
     * @throws \ByJG\DbMigration\Exception\DatabaseNotVersionedException
     * @throws \ByJG\DbMigration\Exception\InvalidMigrationFile
     * @throws \ByJG\DbMigration\Exception\OldVersionSchemaException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function dbSetUp()
    {
        $uriMySqlTest = getenv('MYSQL_TEST_URI') ? getenv('MYSQL_TEST_URI') : "mysql://root:mysqlp455w0rd@127.0.0.1/accounttest";
        $this->uri = new Uri($uriMySqlTest);

        Migration::registerDatabase(MySqlDatabase::class);

        $migration = new Migration($this->uri, __DIR__ . "/../db");
        $migration->prepareEnvironment();
        $migration->reset();

        $this->dbDriver = $migration->getDbDriver();
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function dbClear()
    {
        $this->dbDriver->execute(
            'DELETE statement FROM `account` INNER JOIN statement ' .
            "WHERE account.accountid = statement.accountid and account.userid like '___TESTUSER-%' and statementparentid is not null;"
        );

        $this->dbDriver->execute(
            'DELETE statement FROM `account` INNER JOIN statement ' .
            "WHERE account.accountid = statement.accountid and account.userid like '___TESTUSER-%'"
        );

        $this->dbDriver->execute("DELETE FROM `account` where account.userid like '___TESTUSER-%'");

        $this->dbDriver->execute("DELETE FROM `accounttype` WHERE accounttypeid like '___TEST'");
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\MicroOrm\Exception\OrmBeforeInvalidException
     * @throws \ByJG\MicroOrm\Exception\OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function createDummyData()
    {
        $dto1 = new AccountTypeEntity();
        $dto1->setAccountTypeId('USDTEST');
        $dto1->setName('Test 1');

        $dto2 = new AccountTypeEntity();
        $dto2->setAccountTypeId('BRLTEST');
        $dto2->setName('Test 2');

        $dto3 = new AccountTypeEntity();
        $dto3->setAccountTypeId('ABCTEST');
        $dto3->setName('Test 3');

        $this->accountTypeBLL->update($dto1);
        $this->accountTypeBLL->update($dto2);
        $this->accountTypeBLL->update($dto3);

        $this->accountBLL->createAccount('BRLTEST', '___TESTUSER-1', 1000, 1);
    }
}
