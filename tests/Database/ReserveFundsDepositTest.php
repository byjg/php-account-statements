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


class ReserveFundsDepositTest extends TestCase
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

    public function testReserveForDepositFunds()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->reserveFundsForDeposit($idAccount, 350, 'Test Deposit', 'Referencia Deposit');

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Deposit');
        $statement->setGrossBalance('1000.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idStatement);
        $statement->setIdType('DB');
        $statement->setNetBalance('1350.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('-350.00000');
        $statement->setReference('Referencia Deposit');
        $statement->setIdAccountType('USDTEST');
        $statement->setIdStatementParent("");

        $actual = $this->statementBLL->getById($idStatement);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    /**
     * @expectedException \ByJG\AccountStatements\Exception\AmountException
     */
    public function testReserveForDepositFunds_Invalid()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->reserveFundsForDeposit($idAccount, -50, 'Test Withdraw', 'Referencia Withdraw');
    }

    public function testReserveForDepositFunds_Negative()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, -200, 1, -400);
        $idStatement = $this->statementBLL->reserveFundsForDeposit($idAccount, 300, 'Test Deposit', 'Referencia Deposit');

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('300.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Deposit');
        $statement->setGrossBalance('-200.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idStatement);
        $statement->setIdType('DB');
        $statement->setNetBalance('100.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('-300.00000');
        $statement->setReference('Referencia Deposit');
        $statement->setIdAccountType('USDTEST');

        $actual = $this->statementBLL->getById($idStatement);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }
//
//    /**
//     * @expectedException OutOfRangeException
//     */
//    public function testAcceptFundsById_InvalidId()
//    {
//        // Populate Data!
//        $this->accountBLL->createAccount('USDTEST', -1, 1000);
//
//        $this->statementBLL->acceptFundsById(2);
//    }
//
    /**
     * @expectedException \ByJG\AccountStatements\Exception\StatementException
     */
    public function testAcceptFundsById_InvalidType()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->addFunds($idAccount, 200, 'Test Deposit', 'Referencia Deposit');

        $this->statementBLL->acceptFundsById($idStatement);
    }

    /**
     * @expectedException \ByJG\AccountStatements\Exception\StatementException
     */
    public function testAcceptFundsById_HasParentTransation()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->addFunds($idAccount, 150, 'Test Deposit', 'Referencia Deposit');
        $idStatement = $this->statementBLL->reserveFundsForDeposit($idAccount, 350, 'Test Deposit', 'Referencia Deposit');

        // Executar ação
        $this->statementBLL->acceptFundsById($idStatement);

        // Provar o erro:
        $this->statementBLL->acceptFundsById($idStatement);
    }

    public function testAcceptFundsById_OK()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->addFunds($idAccount, 150, 'Test Deposit', 'Referencia Deposit');
        $idStatement = $this->statementBLL->reserveFundsForDeposit($idAccount, 350, 'Test Deposit', 'Referencia Deposit');

        // Executar ação
        $idActual = $this->statementBLL->acceptFundsById($idStatement);
        $actual = $this->statementBLL->getById($idActual);

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDescription('Test Deposit');
        $statement->setGrossBalance('1500.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idActual);
        $statement->setIdStatementParent($idStatement);
        $statement->setIdType('D');
        $statement->setNetBalance('1500.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReference('Referencia Deposit');
        $statement->setDate($actual->getDate());
        $statement->setIdAccountType('USDTEST');

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    /**
     * @expectedException \ByJG\AccountStatements\Exception\StatementException
     */
    public function testRejectFundsById_InvalidType()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $idStatement = $this->statementBLL->addFunds($idAccount, 300);

        $this->statementBLL->rejectFundsById($idStatement);
    }

    /**
     * @expectedException \ByJG\AccountStatements\Exception\StatementException
     */
    public function testRejectFundsById_HasParentTransation()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->addFunds($idAccount, 150, 'Test Deposit', 'Referencia Deposit');
        $idStatement = $this->statementBLL->reserveFundsForDeposit($idAccount, 350, 'Test Deposit', 'Referencia Deposit');

        // Executar ação
        $this->statementBLL->rejectFundsById($idStatement);

        // Provocar o erro:
        $this->statementBLL->rejectFundsById($idStatement);
    }

    public function testRejectFundsById_OK()
    {
        // Populate Data!
        $idAccount = $this->accountBLL->createAccount('USDTEST', -1, 1000);
        $this->statementBLL->addFunds($idAccount, 150, 'Test Deposit', 'Referencia Deposit');
        $idStatement = $this->statementBLL->reserveFundsForDeposit($idAccount, 350, 'Test Deposit', 'Referencia Deposit');

        // Executar ação
        $idActual = $this->statementBLL->rejectFundsById($idStatement);
        $actual = $this->statementBLL->getById($idActual);

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDescription('Test Deposit');
        $statement->setGrossBalance('1150.00000');
        $statement->setIdAccount($idAccount);
        $statement->setIdStatement($idActual);
        $statement->setIdStatementParent($idStatement);
        $statement->setIdType('R');
        $statement->setNetBalance('1150.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReference('Referencia Deposit');
        $statement->setDate($actual->getDate());
        $statement->setIdAccountType('USDTEST');

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

}
