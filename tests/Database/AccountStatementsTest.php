<?php

namespace Test;

use ByJG\AccountStatements\DTO\StatementDTO;
use Test\BaseDALTrait;
use ByJG\AccountStatements\Entity\AccountEntity;
use ByJG\AccountStatements\Entity\StatementEntity;
use ByJG\AccountStatements\Exception\AmountException;
use ByJG\AccountStatements\Repository\AccountTypeRepository;
use ByJG\Serializer\BinderObject;
use ByJG\Serializer\SerializerObject;
use DomainException;
use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;
use UnderflowException;

require_once(__DIR__ . '/../BaseDALTrait.php');


class AccountStatementsTest extends TestCase
{
    use BaseDALTrait;


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->dbSetUp();
        $this->prepareObjects();
        $this->createDummyData();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $this->dbClear();
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetAccountType()
    {
        $accountTypeRepo = $this->accountTypeBLL->getRepository();
        $list = $accountTypeRepo->getAll(null, null, null,  [["accounttypeid like '___TEST'", []]]);

        $this->assertEquals(3, count($list));

        $this->assertEquals(
            [
                [
                    'accounttypeid' => 'ABCTEST',
                    'name' => 'Test 3'
                ],
                [
                    'accounttypeid' => 'BRLTEST',
                    'name' => 'Test 2'
                ],
                [
                    'accounttypeid' => 'USDTEST',
                    'name' => 'Test 1'
                ],
            ],
            SerializerObject::instance($list)->serialize()
        );

        $dto = $this->accountTypeBLL->getById('USDTEST');
        $this->assertEquals('Test 1', $dto->getName());
        $this->assertEquals('USDTEST', $dto->getAccountTypeId());
    }

    public function testGetById()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $statementId = $this->statementBLL->withdrawFunds(
            StatementDTO::instance($accountId, 10)
                ->setDescription( 'Test')
                ->setReferenceId('Referencia')
                ->setReferenceSource('Source')
                ->setCode('XYZ')
            );

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('10.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test');
        $statement->setGrossBalance('990.00000');
        $statement->setAccountId($accountId);
        $statement->setStatementId($statementId);
        $statement->setTypeId('W');
        $statement->setNetBalance('990.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReferenceId('Referencia');
        $statement->setReferenceSource('Source');
        $statement->setCode('XYZ');
        $statement->setAccountTypeId('USDTEST');

        $actual = $this->statementBLL->getById($statementId);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    public function testGetById_NotFound()
    {
        // Executar teste
        $this->assertEquals($this->statementBLL->getById(2), null);
    }

    public function testGetAll()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $statementId = $this->statementBLL->withdrawFunds(
            StatementDTO::instance($accountId, 10)
                ->setDescription( 'Test')
                ->setReferenceId('Referencia')
                ->setReferenceSource('Source')
            );
        $this->statementBLL->withdrawFunds(
            StatementDTO::instance($accountId, 50)
                ->setDescription('Test')
                ->setReferenceId('Referencia')
                ->setReferenceSource('Source')
            );

        $statement = [];

        // Objetos que são esperados
        $statement[] = new StatementEntity;
        $statement[0]->setAmount('1000.00000');
        $statement[0]->setDate('2015-01-24');
        $statement[0]->setDescription('Opening Balance');
        $statement[0]->setCode('BAL');
        $statement[0]->setGrossBalance('1000.00000');
        $statement[0]->setAccountId($accountId);
        $statement[0]->setStatementId(2);
        $statement[0]->setTypeId('D');
        $statement[0]->setNetBalance('1000.00000');
        $statement[0]->setPrice('1.00000');
        $statement[0]->setUnCleared('0.00000');
        $statement[0]->setReferenceId('');
        $statement[0]->setReferenceSource('');
        $statement[0]->setAccountTypeId('USDTEST');

        $statement[] = new StatementEntity;
        $statement[1]->setAmount('10.00000');
        $statement[1]->setDate('2015-01-24');
        $statement[1]->setDescription('Test');
        $statement[1]->setGrossBalance('990.00000');
        $statement[1]->setAccountId($accountId);
        $statement[1]->setStatementId($statementId);
        $statement[1]->setTypeId('W');
        $statement[1]->setNetBalance('990.00000');
        $statement[1]->setPrice('1.00000');
        $statement[1]->setUnCleared('0.00000');
        $statement[1]->setReferenceId('Referencia');
        $statement[1]->setReferenceSource('Source');
        $statement[1]->setAccountTypeId('USDTEST');

        $statement[] = new StatementEntity;
        $statement[2]->setAmount('50.00000');
        $statement[2]->setDate('2015-01-24');
        $statement[2]->setDescription('Test');
        $statement[2]->setGrossBalance('940.00000');
        $statement[2]->setAccountId($accountId);
        $statement[2]->setStatementId('4');
        $statement[2]->setTypeId('W');
        $statement[2]->setNetBalance('940.00000');
        $statement[2]->setPrice('1.00000');
        $statement[2]->setUnCleared('0.00000');
        $statement[2]->setReferenceId('Referencia');
        $statement[2]->setReferenceSource('Source');
        $statement[2]->setAccountTypeId('USDTEST');

        $listAll = $this->statementBLL->getRepository()->getAll(null, null, null, [["accounttypeid = :id",["id" => 'USDTEST']]]);

        for ($i=0; $i<count($statement); $i++) {
            $statement[$i]->setDate(null);
            $statement[$i]->setStatementId(null);
            $listAll[$i]->setDate(null);
            $listAll[$i]->setStatementId(null);
        }

        // Testar método
        $this->assertEquals(
            $statement,
            $listAll
        );
    }

    public function testAddFunds()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $statementId = $this->statementBLL->addFunds(
            StatementDTO::instance($accountId, 250)
                ->setDescription('Test Add Funds')
                ->setReferenceId('Referencia Add Funds')
                ->setReferenceSource('Source Add Funds')
            );

        // Check
        $statement = new StatementEntity;
        $statement->setAmount('250.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Add Funds');
        $statement->setGrossBalance('1250.00000');
        $statement->setAccountId($accountId);
        $statement->setStatementId($statementId);
        $statement->setTypeId('D');
        $statement->setNetBalance('1250.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReferenceId('Referencia Add Funds');
        $statement->setReferenceSource('Source Add Funds');
        $statement->setAccountTypeId('USDTEST');

        $actual = $this->statementBLL->getById($statementId);
        $statement->setDate($actual->getDate());

        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    public function testAddFunds_Invalid()
    {
        $this->expectException(AmountException::class);

        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        // Check;
        $this->statementBLL->addFunds(StatementDTO::instance($accountId, -15));
    }

    public function testWithdrawFunds()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $statementId = $this->statementBLL->withdrawFunds(
            StatementDTO::instance($accountId, 350)
                ->setDescription( 'Test Withdraw')
                ->setReferenceId('Referencia Withdraw')
                ->setReferenceSource('Source Withdraw')
            );

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Withdraw');
        $statement->setGrossBalance('650.00000');
        $statement->setAccountId($accountId);
        $statement->setStatementId($statementId);
        $statement->setTypeId('W');
        $statement->setNetBalance('650.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReferenceId('Referencia Withdraw');
        $statement->setReferenceSource('Source Withdraw');
        $statement->setAccountTypeId('USDTEST');

        $actual = $this->statementBLL->getById($statementId);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    public function testWithdrawFunds_Invalid()
    {
        $this->expectException(AmountException::class);

        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        // Check
        $this->statementBLL->withdrawFunds(StatementDTO::instance($accountId, -15));
    }

    public function testWithdrawFunds_Negative()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000, 1, -400);
        $statementId = $this->statementBLL->withdrawFunds(
            StatementDTO::instance($accountId, 1150)
                ->setDescription('Test Withdraw')
                ->setReferenceId('Referencia Withdraw')
                ->setReferenceSource('Source Withdraw')
            );

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('1150.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Withdraw');
        $statement->setGrossBalance('-150.00000');
        $statement->setAccountId($accountId);
        $statement->setStatementId($statementId);
        $statement->setTypeId('W');
        $statement->setNetBalance('-150.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReferenceId('Referencia Withdraw');
        $statement->setReferenceSource('Source Withdraw');
        $statement->setAccountTypeId('USDTEST');

        $actual = $this->statementBLL->getById($statementId);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    public function testWithdrawFunds_NegativeInvalid()
    {
        $this->expectException(AmountException::class);

        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000, 1, -400);
        $this->statementBLL->withdrawFunds(StatementDTO::instance($accountId, 1401)->setDescription('Test Withdraw')->setReferenceId('Referencia Withdraw'));
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
    public function testGetAccountByUserId()
    {
        $accountId = $this->accountBLL->createAccount(
            'USDTEST',
            "___TESTUSER-10",
            1000,
            1,
            0,
            'Extra Information'
        );

        $account = $this->accountBLL->getByUserId("___TESTUSER-10");
        $account[0]->setEntryDate(null);

        $accountEntity = new AccountEntity([
            "accountid" => $accountId,
            "accounttypeid" => "USDTEST",
            "userid" => "___TESTUSER-10",
            "grossbalance" => 1000,
            "uncleared" => 0,
            "netbalance" => 1000,
            "price" => 1,
            "extra" => "Extra Information",
            "entrydate" => null,
            "minvalue" => "0.00000"
        ]);

        $this->assertEquals([
           $accountEntity
        ], $account);
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
    public function testGetAccountByAccountType()
    {
        $accountId = $this->accountBLL->createAccount(
            'ABCTEST',
            "___TESTUSER-10",
            1000,
            1,
            0,
            'Extra Information'
        );

        $account = $this->accountBLL->getByAccountTypeId('ABCTEST');
        $account[0]->setEntryDate(null);

        $accountEntity = new AccountEntity([
            "accountid" => $accountId,
            "accounttypeid" => "ABCTEST",
            "userid" => "___TESTUSER-10",
            "grossbalance" => 1000,
            "uncleared" => 0,
            "netbalance" => 1000,
            "price" => 1,
            "extra" => "Extra Information",
            "entrydate" => null,
            "minvalue" => "0.00000"
        ]);

        $this->assertEquals([
            $accountEntity
        ], $account);
    }

    public function testOverrideFunds()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        $statementId = $this->accountBLL->overrideBalance($accountId, 650);
        $account = $this->accountBLL->getById($accountId)->toArray();
        unset($account["entrydate"]);

        // Executar teste
        $this->assertEquals([
                'accountid' => $accountId,
                'accounttypeid' => 'USDTEST',
                'userid' => "___TESTUSER-1",
                'grossbalance' => '650.00000',
                'uncleared' => '0.00000',
                'netbalance' => '650.00000',
                'price' => '1.00000',
                'extra' => '',
                'minvalue' => '0.00000',
            ],
            $account
        );

        $statement = $this->statementBLL->getById($statementId)->toArray();
        unset($statement["date"]);

        $this->assertEquals([
                'accountid' => $accountId,
                'accounttypeid' => 'USDTEST',
                'grossbalance' => '650.00000',
                'uncleared' => '0.00000',
                'netbalance' => '650.00000',
                'price' => '1.00000',
                'statementid' => $statementId,
                'typeid' => 'B',
                'amount' => '650.00000',
                'description' => 'Reset Balance',
                'statementparentid' => '',
                'code' => 'BAL',
                'referenceid' => '',
                'referencesource' => ''
            ],
            $statement
        );
    }

    public function testPartialFunds()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        $statementId = $this->accountBLL->partialBalance($accountId, 650);
        $account = $this->accountBLL->getById($accountId)->toArray();
        unset($account["entrydate"]);

        // Executar teste
        $this->assertEquals([
                'accountid' => $accountId,
                'accounttypeid' => 'USDTEST',
                'userid' => "___TESTUSER-1",
                'grossbalance' => '650.00000',
                'uncleared' => '0.00000',
                'netbalance' => '650.00000',
                'price' => '1.00000',
                'extra' => '',
                'minvalue' => '0.00000',
            ],
            $account
        );

        $statement = $this->statementBLL->getById($statementId)->toArray();
        unset($statement["date"]);

        $this->assertEquals([
                'accountid' => $accountId,
                'accounttypeid' => 'USDTEST',
                'grossbalance' => '650.00000',
                'uncleared' => '0.00000',
                'netbalance' => '650.00000',
                'price' => '1.00000',
                'statementid' => $statementId,
                'typeid' => 'W',
                'amount' => '350.00000',
                'description' => 'Partial Balance',
                'statementparentid' => '',
                'referenceid' => '',
                'referencesource' => '',
                'code' => ''
            ],
            $statement
        );

    }

    public function testCloseAccount()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        $this->statementBLL->addFunds(StatementDTO::instance($accountId, 400));
        $this->statementBLL->addFunds(StatementDTO::instance($accountId, 200));
        $this->statementBLL->withdrawFunds(StatementDTO::instance($accountId, 300));

        $statementId = $this->accountBLL->closeAccount($accountId);

        $account = $this->accountBLL->getById($accountId)->toArray();
        unset($account["entrydate"]);

        // Executar teste
        $this->assertEquals([
            'accountid' => $accountId,
            'accounttypeid' => 'USDTEST',
            'userid' => "___TESTUSER-1",
            'grossbalance' => '0.00000',
            'uncleared' => '0.00000',
            'netbalance' => '0.00000',
            'price' => '0.00000',
            'extra' => '',
            'minvalue' => '0.00000',
        ],
            $account
        );

        $statement = $this->statementBLL->getById($statementId)->toArray();
        unset($statement["date"]);

        $this->assertEquals(
            [
                'accountid' => $accountId,
                'accounttypeid' => 'USDTEST',
                'grossbalance' => '0.00000',
                'uncleared' => '0.00000',
                'netbalance' => '0.00000',
                'price' => '0.00000',
                'statementid' => $statementId,
                'typeid' => 'B',
                'amount' => '0.00000',
                'description' => 'Reset Balance',
                'statementparentid' => '',
                'referenceid' => '',
                'referencesource' => '',
                'code' => 'BAL'
            ],
            $statement
        );

    }

    public function testGetByDate()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->addFunds(StatementDTO::instance($accountId, 400));
        $this->statementBLL->withdrawFunds(StatementDTO::instance($accountId, 300));

        $ignore = $this->accountBLL->createAccount('BRLTEST', "___TESTUSER-999", 1000); // I dont want this account
        $this->statementBLL->addFunds(StatementDTO::instance($ignore, 200));

        $startDate = date('Y'). "/" . date('m') . "/01";
        $endDate = (date('Y') + (date('m') == 12 ? 1 : 0)) . "/" . (date('m') == 12 ? 1 : date('m') + 1) . "/01";

        $statementList = $this->statementBLL->getByDate($accountId, $startDate, $endDate);

        // Executar teste
        $this->assertEquals(
            [
                [
                    'accountid' => $accountId,
                    'accounttypeid' => 'USDTEST',
                    'grossbalance' => '1000.00000',
                    'uncleared' => '0.00000',
                    'netbalance' => '1000.00000',
                    'price' => '1.00000',
                    'statementid' => '2',
                    'typeid' => 'D',
                    'amount' => '1000.00000',
                    'description' => 'Opening Balance',
                    'referenceid' => '',
                    'referencesource' => '',
                    'statementparentid' => '',
                    'code' => 'BAL'
                ],
                [
                    'accountid' => $accountId,
                    'accounttypeid' => 'USDTEST',
                    'grossbalance' => '1400.00000',
                    'uncleared' => '0.00000',
                    'netbalance' => '1400.00000',
                    'price' => '1.00000',
                    'statementid' => '3',
                    'typeid' => 'D',
                    'amount' => '400.00000',
                    'description' => '',
                    'referenceid' => '',
                    'referencesource' => '',
                    'statementparentid' => '',
                    'code' => ''
                ],
                [
                    'accountid' => $accountId,
                    'accounttypeid' => 'USDTEST',
                    'grossbalance' => '1100.00000',
                    'uncleared' => '0.00000',
                    'netbalance' => '1100.00000',
                    'price' => '1.00000',
                    'statementid' => '4',
                    'typeid' => 'W',
                    'amount' => '300.00000',
                    'description' => '',
                    'referenceid' => '',
                    'referencesource' => '',
                    'statementparentid' => '',
                    'code' => ''
                ],
            ],
            array_map(
                function ($value) {
                    $value = $value->toArray();
                    unset($value["date"]);
                    return $value;
                },
                $statementList
            )
        );

        $statementList = $this->statementBLL->getByDate($accountId, '1900/01/01', '1900/02/01');

        $this->assertEquals([], $statementList);

    }

    public function testGetByStatementId()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $statementId = $this->statementBLL->addFunds(StatementDTO::instance($accountId, 400));
        $this->statementBLL->withdrawFunds(StatementDTO::instance($accountId, 300));

        $ignore = $this->accountBLL->createAccount('BRLTEST', "___TESTUSER-999", 1000); // I dont want this account
        $this->statementBLL->addFunds(StatementDTO::instance($ignore, 200));

        $accountRepo = $this->accountBLL->getRepository();

        $accountResult = $accountRepo->getByStatementId($statementId);
        $accountExpected = $accountRepo->getById($accountId);

        // Executar teste
        $this->assertEquals($accountExpected, $accountResult);
    }

    public function testGetByStatementIdNotFound()
    {
        $accountRepo = $this->accountBLL->getRepository();
        $accountResult = $accountRepo->getByStatementId(12345); // Dont exists
        $this->assertNull($accountResult);
    }

}
