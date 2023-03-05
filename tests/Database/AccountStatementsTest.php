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
        $list = $accountTypeRepo->getAll(null, null, null,  [["idaccounttype like '___TEST'", []]]);

        $this->assertEquals(3, count($list));

        $this->assertEquals(
            [
                [
                    'idaccounttype' => 'ABCTEST',
                    'name' => 'Test 3'
                ],
                [
                    'idaccounttype' => 'BRLTEST',
                    'name' => 'Test 2'
                ],
                [
                    'idaccounttype' => 'USDTEST',
                    'name' => 'Test 1'
                ],
            ],
            SerializerObject::instance($list)->serialize()
        );

        $dto = $this->accountTypeBLL->getById('USDTEST');
        $this->assertEquals('Test 1', $dto->getName());
        $this->assertEquals('USDTEST', $dto->getIdAccountType());
    }

    public function testGetById()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $idStatement = $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 10)->setDescription( 'Test')->setReference('Referencia')->setCode('XYZ'));

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('10.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test');
        $statement->setGrossBalance('990.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idStatement);
        $statement->setIdType('W');
        $statement->setNetBalance('990.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReference('Referencia');
        $statement->setCode('XYZ');
        $statement->setIdAccountType('USDTEST');

        $actual = $this->statementBLL->getById($idStatement);
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
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $idStatement = $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 10)->setDescription( 'Test')->setReference('Referencia'));
        $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 50)->setDescription('Test')->setReference('Referencia'));

        $statement = [];

        // Objetos que são esperados
        $statement[] = new StatementEntity;
        $statement[0]->setAmount('1000.00000');
        $statement[0]->setDate('2015-01-24');
        $statement[0]->setDescription('Opening Balance');
        $statement[0]->setCode('BAL');
        $statement[0]->setGrossBalance('1000.00000');
        $statement[0]->setIdAccount($idAccount);
        $statement[0]->setIdStatement(2);
        $statement[0]->setIdType('D');
        $statement[0]->setNetBalance('1000.00000');
        $statement[0]->setPrice('1.00000');
        $statement[0]->setUnCleared('0.00000');
        $statement[0]->setReference('');
        $statement[0]->setIdAccountType('USDTEST');

        $statement[] = new StatementEntity;
        $statement[1]->setAmount('10.00000');
        $statement[1]->setDate('2015-01-24');
        $statement[1]->setDescription('Test');
        $statement[1]->setGrossBalance('990.00000');
        $statement[1]->setIdAccount($idAccount);
        $statement[1]->setIdStatement($idStatement);
        $statement[1]->setIdType('W');
        $statement[1]->setNetBalance('990.00000');
        $statement[1]->setPrice('1.00000');
        $statement[1]->setUnCleared('0.00000');
        $statement[1]->setReference('Referencia');
        $statement[1]->setIdAccountType('USDTEST');

        $statement[] = new StatementEntity;
        $statement[2]->setAmount('50.00000');
        $statement[2]->setDate('2015-01-24');
        $statement[2]->setDescription('Test');
        $statement[2]->setGrossBalance('940.00000');
        $statement[2]->setIdAccount($idAccount);
        $statement[2]->setIdStatement('4');
        $statement[2]->setIdType('W');
        $statement[2]->setNetBalance('940.00000');
        $statement[2]->setPrice('1.00000');
        $statement[2]->setUnCleared('0.00000');
        $statement[2]->setReference('Referencia');
        $statement[2]->setIdAccountType('USDTEST');

        $listAll = $this->statementBLL->getRepository()->getAll(null, null, null, [["idaccounttype = :id",["id" => 'USDTEST']]]);

        for ($i=0; $i<count($statement); $i++) {
            $statement[$i]->setDate(null);
            $statement[$i]->setIdStatement(null);
            $listAll[$i]->setDate(null);
            $listAll[$i]->setIdStatement(null);
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
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $idStatement = $this->statementBLL->addFunds(StatementDTO::instance($idAccount, 250)->setDescription('Test Add Funds')->setReference('Referencia Add Funds'));

        // Check
        $statement = new StatementEntity;
        $statement->setAmount('250.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Add Funds');
        $statement->setGrossBalance('1250.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idStatement);
        $statement->setIdType('D');
        $statement->setNetBalance('1250.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReference('Referencia Add Funds');
        $statement->setIdAccountType('USDTEST');

        $actual = $this->statementBLL->getById($idStatement);
        $statement->setDate($actual->getDate());

        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    public function testAddFunds_Invalid()
    {
        $this->expectException(AmountException::class);

        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        // Check;
        $this->statementBLL->addFunds(StatementDTO::instance($idAccount, -15));
    }

    public function testWithdrawFunds()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $idStatement = $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 350)->setDescription( 'Test Withdraw')->setReference('Referencia Withdraw'));

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Withdraw');
        $statement->setGrossBalance('650.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idStatement);
        $statement->setIdType('W');
        $statement->setNetBalance('650.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReference('Referencia Withdraw');
        $statement->setIdAccountType('USDTEST');

        $actual = $this->statementBLL->getById($idStatement);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    public function testWithdrawFunds_Invalid()
    {
        $this->expectException(AmountException::class);

        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        // Check
        $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, -15));
    }

    public function testWithdrawFunds_Negative()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000, 1, -400);
        $idStatement = $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 1150)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('1150.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Withdraw');
        $statement->setGrossBalance('-150.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idStatement);
        $statement->setIdType('W');
        $statement->setNetBalance('-150.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReference('Referencia Withdraw');
        $statement->setIdAccountType('USDTEST');

        $actual = $this->statementBLL->getById($idStatement);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    public function testWithdrawFunds_NegativeInvalid()
    {
        $this->expectException(AmountException::class);

        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000, 1, -400);
        $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 1401)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));
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

        $account = $this->accountBLL->getUserId("___TESTUSER-10");
        $account[0]->setEntryDate(null);

        $accountEntity = new AccountEntity([
            "idaccount" => $accountId,
            "idaccounttype" => "USDTEST",
            "iduser" => "___TESTUSER-10",
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

        $account = $this->accountBLL->getAccountTypeId('ABCTEST');
        $account[0]->setEntryDate(null);

        $accountEntity = new AccountEntity([
            "idaccount" => $accountId,
            "idaccounttype" => "ABCTEST",
            "iduser" => "___TESTUSER-10",
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
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        $idStatement = $this->accountBLL->overrideBalance($idAccount, 650);
        $account = $this->accountBLL->getById($idAccount)->toArray();
        unset($account["entrydate"]);

        // Executar teste
        $this->assertEquals([
                'idaccount' => $idAccount,
                'idaccounttype' => 'USDTEST',
                'iduser' => "___TESTUSER-1",
                'grossbalance' => '650.00000',
                'uncleared' => '0.00000',
                'netbalance' => '650.00000',
                'price' => '1.00000',
                'extra' => '',
                'minvalue' => '0.00000',
            ],
            $account
        );

        $statement = $this->statementBLL->getById($idStatement)->toArray();
        unset($statement["date"]);

        $this->assertEquals([
                'idaccount' => $idAccount,
                'idaccounttype' => 'USDTEST',
                'grossbalance' => '650.00000',
                'uncleared' => '0.00000',
                'netbalance' => '650.00000',
                'price' => '1.00000',
                'idstatement' => $idStatement,
                'idtype' => 'B',
                'amount' => '650.00000',
                'description' => 'Reset Balance',
                'idstatementparent' => '',
                'reference' => '',
                'code' => 'BAL'
            ],
            $statement
        );
    }

    public function testPartialFunds()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        $idStatement = $this->accountBLL->partialBalance($idAccount, 650);
        $account = $this->accountBLL->getById($idAccount)->toArray();
        unset($account["entrydate"]);

        // Executar teste
        $this->assertEquals([
                'idaccount' => $idAccount,
                'idaccounttype' => 'USDTEST',
                'iduser' => "___TESTUSER-1",
                'grossbalance' => '650.00000',
                'uncleared' => '0.00000',
                'netbalance' => '650.00000',
                'price' => '1.00000',
                'extra' => '',
                'minvalue' => '0.00000',
            ],
            $account
        );

        $statement = $this->statementBLL->getById($idStatement)->toArray();
        unset($statement["date"]);

        $this->assertEquals([
                'idaccount' => $idAccount,
                'idaccounttype' => 'USDTEST',
                'grossbalance' => '650.00000',
                'uncleared' => '0.00000',
                'netbalance' => '650.00000',
                'price' => '1.00000',
                'idstatement' => $idStatement,
                'idtype' => 'W',
                'amount' => '350.00000',
                'description' => 'Partial Balance',
                'idstatementparent' => '',
                'reference' => '',
                'code' => ''
            ],
            $statement
        );

    }

    public function testCloseAccount()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        $this->statementBLL->addFunds(StatementDTO::instance($idAccount, 400));
        $this->statementBLL->addFunds(StatementDTO::instance($idAccount, 200));
        $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 300));

        $idStatement = $this->accountBLL->closeAccount($idAccount);

        $account = $this->accountBLL->getById($idAccount)->toArray();
        unset($account["entrydate"]);

        // Executar teste
        $this->assertEquals([
            'idaccount' => $idAccount,
            'idaccounttype' => 'USDTEST',
            'iduser' => "___TESTUSER-1",
            'grossbalance' => '0.00000',
            'uncleared' => '0.00000',
            'netbalance' => '0.00000',
            'price' => '0.00000',
            'extra' => '',
            'minvalue' => '0.00000',
        ],
            $account
        );

        $statement = $this->statementBLL->getById($idStatement)->toArray();
        unset($statement["date"]);

        $this->assertEquals(
            [
                'idaccount' => $idAccount,
                'idaccounttype' => 'USDTEST',
                'grossbalance' => '0.00000',
                'uncleared' => '0.00000',
                'netbalance' => '0.00000',
                'price' => '0.00000',
                'idstatement' => $idStatement,
                'idtype' => 'B',
                'amount' => '0.00000',
                'description' => 'Reset Balance',
                'idstatementparent' => '',
                'reference' => '',
                'code' => 'BAL'
            ],
            $statement
        );

    }

    public function testGetByDate()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->addFunds(StatementDTO::instance($idAccount, 400));
        $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 300));

        $ignore = $this->accountBLL->createAccount('BRLTEST', "___TESTUSER-999", 1000); // I dont want this account
        $this->statementBLL->addFunds(StatementDTO::instance($ignore, 200));

        $startDate = date('Y'). "/" . date('m') . "/01";
        $endDate = (date('Y') + (date('m') == 12 ? 1 : 0)) . "/" . (date('m') == 12 ? 1 : date('m') + 1) . "/01";

        $statementList = $this->statementBLL->getByDate($idAccount, $startDate, $endDate);

        // Executar teste
        $this->assertEquals(
            [
                [
                    'idaccount' => $idAccount,
                    'idaccounttype' => 'USDTEST',
                    'grossbalance' => '1000.00000',
                    'uncleared' => '0.00000',
                    'netbalance' => '1000.00000',
                    'price' => '1.00000',
                    'idstatement' => '2',
                    'idtype' => 'D',
                    'amount' => '1000.00000',
                    'description' => 'Opening Balance',
                    'reference' => '',
                    'idstatementparent' => '',
                    'code' => 'BAL'
                ],
                [
                    'idaccount' => $idAccount,
                    'idaccounttype' => 'USDTEST',
                    'grossbalance' => '1400.00000',
                    'uncleared' => '0.00000',
                    'netbalance' => '1400.00000',
                    'price' => '1.00000',
                    'idstatement' => '3',
                    'idtype' => 'D',
                    'amount' => '400.00000',
                    'description' => '',
                    'reference' => '',
                    'idstatementparent' => '',
                    'code' => ''
                ],
                [
                    'idaccount' => $idAccount,
                    'idaccounttype' => 'USDTEST',
                    'grossbalance' => '1100.00000',
                    'uncleared' => '0.00000',
                    'netbalance' => '1100.00000',
                    'price' => '1.00000',
                    'idstatement' => '4',
                    'idtype' => 'W',
                    'amount' => '300.00000',
                    'description' => '',
                    'reference' => '',
                    'idstatementparent' => '',
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

        $statementList = $this->statementBLL->getByDate($idAccount, '1900/01/01', '1900/02/01');

        $this->assertEquals([], $statementList);

    }


}
