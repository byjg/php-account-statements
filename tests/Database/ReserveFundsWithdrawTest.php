<?php

namespace Test;

use ByJG\AccountStatements\DTO\StatementDTO;
use Test\BaseDALTrait;
use ByJG\AccountStatements\Entity\AccountEntity;
use ByJG\AccountStatements\Entity\StatementEntity;
use ByJG\AccountStatements\Exception\AmountException;
use ByJG\AccountStatements\Exception\StatementException;
use ByJG\AccountStatements\Repository\AccountTypeRepository;
use ByJG\Serializer\BinderObject;
use DomainException;
use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;
use UnderflowException;

require_once(__DIR__ . '/../BaseDALTrait.php');


class ReserveFundsWithdrawTest extends TestCase
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

    public function testReserveForWithdrawFunds()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $idStatement = $this->statementBLL->reserveFundsForWithdraw(StatementDTO::instance($idAccount, 350)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));

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

    public function testReserveForWithdrawFunds_Invalid()
    {
        $this->expectException(AmountException::class);

        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->reserveFundsForWithdraw(StatementDTO::instance($idAccount, -50)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));
    }

    public function testReserveForWithdrawFunds_Negative()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000, 1, -400);
        $idStatement = $this->statementBLL->reserveFundsForWithdraw(StatementDTO::instance($idAccount, 1150)->setDescription('Test Withdraw')->setReference( 'Referencia Withdraw'));

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

    public function testReserveForWithdrawFunds_NegativeInvalid()
    {
        $this->expectException(AmountException::class);

        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000, 1, -400);
        $this->statementBLL->reserveFundsForWithdraw(StatementDTO::instance($idAccount, 1401)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));
    }

    public function testAcceptFundsById_InvalidId()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        $this->statementBLL->acceptFundsById(2);
    }

    public function testAcceptFundsById_InvalidType()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $idStatement = $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 200)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));

        $this->statementBLL->acceptFundsById($idStatement);
    }

    public function testAcceptFundsById_HasParentTransation()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 150)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));
        $idStatement = $this->statementBLL->reserveFundsForWithdraw(StatementDTO::instance($idAccount, 350)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));

        // Executar ação
        $this->statementBLL->acceptFundsById($idStatement);

        // Provar o erro:
        $this->statementBLL->acceptFundsById($idStatement);    
    }

    public function testAcceptFundsById_OK()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 150)->setDescription( 'Test Withdraw')->setReference('Referencia Withdraw'));
        $idStatement = $this->statementBLL->reserveFundsForWithdraw(StatementDTO::instance($idAccount, 350)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));

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

    public function testRejectFundsById_InvalidId()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);

        $this->statementBLL->rejectFundsById(5);
    }

    public function testRejectFundsById_InvalidType()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $idStatement = $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 300));

        $this->statementBLL->rejectFundsById($idStatement);
    }

    public function testRejectFundsById_HasParentTransation()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 150)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));
        $idStatement = $this->statementBLL->reserveFundsForWithdraw(StatementDTO::instance($idAccount, 350)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));

        // Executar ação
        $this->statementBLL->rejectFundsById($idStatement);

        // Provocar o erro:
        $this->statementBLL->rejectFundsById($idStatement);
    }

    public function testRejectFundsById_OK()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->withdrawFunds(StatementDTO::instance($idAccount, 150)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));
        $idStatement = $this->statementBLL->reserveFundsForWithdraw(StatementDTO::instance($idAccount, 350)->setDescription('Test Withdraw')->setReference('Referencia Withdraw'));

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

}
