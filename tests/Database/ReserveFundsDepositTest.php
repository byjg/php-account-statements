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


class ReserveFundsDepositTest extends TestCase
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

    public function testReserveForDepositFunds()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $statementId = $this->statementBLL->reserveFundsForDeposit(
            StatementDTO::instance($accountId, 350)
                ->setDescription('Test Deposit')
                ->setReferenceId('Referencia Deposit')
                ->setReferenceSource('Source Deposit')
            );

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Deposit');
        $statement->setGrossBalance('1000.00000');
        $statement->setAccountId($accountId);
        $statement->setStatementId($statementId);
        $statement->setTypeId('DB');
        $statement->setNetBalance('1350.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('-350.00000');
        $statement->setReferenceId('Referencia Deposit');
        $statement->setReferenceSource('Source Deposit');
        $statement->setAccountTypeId('USDTEST');
        $statement->setStatementParentId("");

        $actual = $this->statementBLL->getById($statementId);
        $statement->setDate($actual->getDate());

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    public function testReserveForDepositFunds_Invalid()
    {
        $this->expectException(AmountException::class);

        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->reserveFundsForDeposit(StatementDTO::instance($accountId, -50)->setDescription('Test Withdraw')->setReferenceId('Referencia Withdraw'));
    }

    public function testReserveForDepositFunds_Negative()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", -200, 1, -400);
        $statementId = $this->statementBLL->reserveFundsForDeposit(
            StatementDTO::instance($accountId, 300)
                ->setDescription('Test Deposit')
                ->setReferenceId('Referencia Deposit')
                ->setReferenceSource('Source Deposit')
            );

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('300.00000');
        $statement->setDate('2015-01-24');
        $statement->setDescription('Test Deposit');
        $statement->setGrossBalance('-200.00000');
        $statement->setAccountId($accountId);
        $statement->setStatementId($statementId);
        $statement->setTypeId('DB');
        $statement->setNetBalance('100.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('-300.00000');
        $statement->setReferenceId('Referencia Deposit');
        $statement->setReferenceSource('Source Deposit');
        $statement->setAccountTypeId('USDTEST');

        $actual = $this->statementBLL->getById($statementId);
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
//        $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
//
//        $this->statementBLL->acceptFundsById(2);
//    }
//

public function testAcceptFundsById_InvalidType()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $statementId = $this->statementBLL->addFunds(
            StatementDTO::instance($accountId, 200)
                ->setDescription('Test Deposit')
                ->setReferenceId('Referencia Deposit')
                ->setReferenceSource('Source Deposit')
            );

        $this->statementBLL->acceptFundsById($statementId);
    }

    public function testAcceptFundsById_HasParentTransation()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->addFunds(StatementDTO::instance($accountId, 150)->setDescription('Test Deposit')->setReferenceId('Referencia Deposit'));
        $statementId = $this->statementBLL->reserveFundsForDeposit(StatementDTO::instance($accountId, 350)->setDescription('Test Deposit')->setReferenceId('Referencia Deposit'));

        // Executar ação
        $this->statementBLL->acceptFundsById($statementId);

        // Provar o erro:
        $this->statementBLL->acceptFundsById($statementId);
    }

    public function testAcceptFundsById_OK()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->addFunds(
            StatementDTO::instance($accountId, 150)
                ->setDescription('Test Deposit')
                ->setReferenceId('Referencia Deposit')
                ->setReferenceSource('Source Deposit')
            );
        $statementId = $this->statementBLL->reserveFundsForDeposit(
            StatementDTO::instance($accountId, 350)
                ->setDescription('Test Deposit')
                ->setReferenceId('Referencia Deposit')
                ->setReferenceSource('Source Deposit')
            );

        // Executar ação
        $actualId = $this->statementBLL->acceptFundsById($statementId);
        $actual = $this->statementBLL->getById($actualId);

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDescription('Test Deposit');
        $statement->setGrossBalance('1500.00000');
        $statement->setAccountId($accountId);
        $statement->setStatementId($actualId);
        $statement->setStatementParentId($statementId);
        $statement->setTypeId('D');
        $statement->setNetBalance('1500.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReferenceId('Referencia Deposit');
        $statement->setReferenceSource('Source Deposit');
        $statement->setDate($actual->getDate());
        $statement->setAccountTypeId('USDTEST');

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

    public function testRejectFundsById_InvalidType()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $statementId = $this->statementBLL->addFunds(StatementDTO::instance($accountId, 300));

        $this->statementBLL->rejectFundsById($statementId);
    }

    public function testRejectFundsById_HasParentTransation()
    {
        $this->expectException(StatementException::class);

        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->addFunds(StatementDTO::instance($accountId, 150)->setDescription('Test Deposit')->setReferenceId('Referencia Deposit'));
        $statementId = $this->statementBLL->reserveFundsForDeposit(StatementDTO::instance($accountId, 350)->setDescription('Test Deposit')->setReferenceId('Referencia Deposit'));

        // Executar ação
        $this->statementBLL->rejectFundsById($statementId);

        // Provocar o erro:
        $this->statementBLL->rejectFundsById($statementId);
    }

    public function testRejectFundsById_OK()
    {
        // Populate Data!
        $accountId = $this->accountBLL->createAccount('USDTEST', "___TESTUSER-1", 1000);
        $this->statementBLL->addFunds(
            StatementDTO::instance($accountId, 150)
                ->setDescription('Test Deposit')
                ->setReferenceId('Referencia Deposit')
                ->setReferenceSource('Source Deposit')
            );
        $statementId = $this->statementBLL->reserveFundsForDeposit(
            StatementDTO::instance($accountId, 350)
                ->setDescription('Test Deposit')
                ->setReferenceId('Referencia Deposit')
                ->setReferenceSource('Source Deposit')
            );

        // Executar ação
        $actualId = $this->statementBLL->rejectFundsById($statementId);
        $actual = $this->statementBLL->getById($actualId);

        // Objeto que é esperado
        $statement = new StatementEntity();
        $statement->setAmount('350.00000');
        $statement->setDescription('Test Deposit');
        $statement->setGrossBalance('1150.00000');
        $statement->setAccountId($accountId);
        $statement->setStatementId($actualId);
        $statement->setStatementParentId($statementId);
        $statement->setTypeId('R');
        $statement->setNetBalance('1150.00000');
        $statement->setPrice('1.00000');
        $statement->setUnCleared('0.00000');
        $statement->setReferenceId('Referencia Deposit');
        $statement->setReferenceSource('Source Deposit');
        $statement->setDate($actual->getDate());
        $statement->setAccountTypeId('USDTEST');

        // Executar teste
        $this->assertEquals($statement->toArray(), $actual->toArray());
    }

}
