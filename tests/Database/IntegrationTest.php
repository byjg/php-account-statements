<?php

namespace Test;

use Test\BaseDALTrait;
use ByJG\AccountStatements\Entity\AccountEntity;
use ByJG\AccountStatements\Entity\StatementEntity;
use ByJG\AccountStatements\Repository\AccountTypeRepository;
use ByJG\Serializer\BinderObject;
use DomainException;
use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;
use UnderflowException;

require_once(__DIR__ . '/../BaseDALTrait.php');


class IntegrationTest extends TestCase
{
    use BaseDALTrait;


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->dbSetUp();
        $this->prepareObjects();
        $this->createDummyData();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
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
            BinderObject::toArrayFrom($list)
        );

        $dto = $this->accountTypeBLL->getById('USDTEST');
        $this->assertEquals('Test 1', $dto->getName());
        $this->assertEquals('USDTEST', $dto->getIdAccountType());
    }

    public function testGetById()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->withdrawFunds($idAccount, 10, 'Test', 'Referencia');

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
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->withdrawFunds($idAccount, 10, 'Test', 'Referencia');
        $this->statementBLL->withdrawFunds($idAccount, 50, 'Test', 'Referencia');

        $statement = [];

        // Objetos que são esperados
        $statement[] = new StatementEntity;
        $statement[0]->setAmount('1000.00000');
        $statement[0]->setDate('2015-01-24');
        $statement[0]->setDescription('Opening Balance');
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
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->addFunds($idAccount, 250, 'Test Add Funds', 'Referencia Add Funds');

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

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddFunds_Invalid()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);

        // Check;
        $this->statementBLL->addFunds($idAccount, -15);
    }

    public function testWithdrawFunds()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->withdrawFunds($idAccount, 350, 'Test Withdraw', 'Referencia Withdraw');

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

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithdrawFunds_Invalid()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);

        // Check
        $this->statementBLL->withdrawFunds($idAccount, -15);
    }

    public function testWithdrawFunds_Negative()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000, 1, -400);
        $idStatement = $this->statementBLL->withdrawFunds($idAccount, 1150, 'Test Withdraw', 'Referencia Withdraw');

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

    /**
     * @expectedException UnderflowException
     */
    public function testWithdrawFunds_NegativeInvalid()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000, 1, -400);
        $this->statementBLL->withdrawFunds($idAccount, 1401, 'Test Withdraw', 'Referencia Withdraw');
    }

    public function testReserveForWithdrawFunds()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->reserveFundsForWithdraw($idAccount, 350, 'Test Withdraw', 'Referencia Withdraw');

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Withdraw');
        $statement->setGrossBalance('1000.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idStatement);
        $statement->setIdType('WB');
        $statement->setNetBalance('650.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('350.00000');
        $statement->setReference('Referencia Withdraw');
        $statement->setIdAccountType('USDTEST');

        $actual = $this->statementBLL->getById($idStatement);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testReserveForWithdrawFunds_Invalid()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->reserveFundsForWithdraw($idAccount, -50, 'Test Withdraw', 'Referencia Withdraw');
    }

    public function testReserveForWithdrawFunds_Negative()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000, 1, -400);
        $idStatement = $this->statementBLL->reserveFundsForWithdraw($idAccount, 1150, 'Test Withdraw', 'Referencia Withdraw');

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('1150.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Withdraw');
        $statement->setGrossBalance('1000.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idStatement);
        $statement->setIdType('WB');
        $statement->setNetBalance('-150.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('1150.00000');
        $statement->setReference('Referencia Withdraw');
        $statement->setIdAccountType('USDTEST');

        $actual = $this->statementBLL->getById($idStatement);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    /**
     * @expectedException UnderflowException
     */
    public function testReserveForWithdrawFunds_NegativeInvalid()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000, 1, -400);
        $this->statementBLL->reserveFundsForWithdraw($idAccount, 1401, 'Test Withdraw', 'Referencia Withdraw');
    }

    /**
     * @expectedException OutOfRangeException
     */
    public function testAcceptFundsById_InvalidId()
    {
        // Populate Data!
        $this->accountBLL->createAccount('USDTEST', -1, 1000);

        $this->statementBLL->acceptFundsById(2);
    }

    /**
     * @expectedException OutOfRangeException
     */
    public function testAcceptFundsById_InvalidType()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->withdrawFunds($idAccount, 200, 'Test Withdraw', 'Referencia Withdraw');

        $this->statementBLL->acceptFundsById($idStatement);
    }

    /**
     * @expectedException DomainException
     */
    public function testAcceptFundsById_HasParentTransation()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->withdrawFunds($idAccount, 150, 'Test Withdraw', 'Referencia Withdraw');
        $idStatement = $this->statementBLL->reserveFundsForWithdraw($idAccount, 350, 'Test Withdraw', 'Referencia Withdraw');

        // Executar ação
        $this->statementBLL->acceptFundsById($idStatement);

        // Provar o erro:
        $this->statementBLL->acceptFundsById($idStatement);    
    }

    public function testAcceptFundsById_OK()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->withdrawFunds($idAccount, 150, 'Test Withdraw', 'Referencia Withdraw');
        $idStatement = $this->statementBLL->reserveFundsForWithdraw($idAccount, 350, 'Test Withdraw', 'Referencia Withdraw');

        // Executar ação
        $idActual = $this->statementBLL->acceptFundsById($idStatement);
        $actual = $this->statementBLL->getById($idActual);

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDescription('Test Withdraw');
        $statement->setGrossBalance('500.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idActual);
        $statement->setIdStatementParent($idStatement);
        $statement->setIdType('W');
        $statement->setNetBalance('500.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReference('Referencia Withdraw');
        $statement->setDate($actual->getDate());
        $statement->setIdAccountType('USDTEST');

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRejectFundsById_InvalidId()
    {
        // Populate Data!
        $this->accountBLL->createAccount('USDTEST', -1, 1000);

        $this->statementBLL->rejectFundsById(5);
    }

    /**
     * @expectedException OutOfRangeException
     */
    public function testRejectFundsById_InvalidType()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->withdrawFunds($idAccount, 300);

        $this->statementBLL->rejectFundsById($idStatement);
    }

    /**
     * @expectedException DomainException
     */
    public function testRejectFundsById_HasParentTransation()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->withdrawFunds($idAccount, 150, 'Test Withdraw', 'Referencia Withdraw');
        $idStatement = $this->statementBLL->reserveFundsForWithdraw($idAccount, 350, 'Test Withdraw', 'Referencia Withdraw');

        // Executar ação
        $this->statementBLL->rejectFundsById($idStatement);

        // Provocar o erro:
        $this->statementBLL->rejectFundsById($idStatement);
    }

    public function testRejectFundsById_OK()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->withdrawFunds($idAccount, 150, 'Test Withdraw', 'Referencia Withdraw');
        $idStatement = $this->statementBLL->reserveFundsForWithdraw($idAccount, 350, 'Test Withdraw', 'Referencia Withdraw');

        // Executar ação
        $idActual = $this->statementBLL->rejectFundsById($idStatement);
        $actual = $this->statementBLL->getById($idActual);

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDescription('Test Withdraw');
        $statement->setGrossBalance('850.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idActual);
        $statement->setIdStatementParent($idStatement);
        $statement->setIdType('R');
        $statement->setNetBalance('850.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReference('Referencia Withdraw');
        $statement->setDate($actual->getDate());
        $statement->setIdAccountType('USDTEST');

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
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
            -10,
            1000,
            1,
            0,
            'Extra Information'
        );

        $account = $this->accountBLL->getUserId(-10);
        $account[0]->setEntryDate(null);

        $accountEntity = new AccountEntity([
            "idaccount" => $accountId,
            "idaccounttype" => "USDTEST",
            "iduser" => -10,
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
            -10,
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
            "iduser" => -10,
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
}
