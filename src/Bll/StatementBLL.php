<?php

namespace ByJG\AccountStatements\Bll;

use ByJG\AccountStatements\DTO\StatementDTO;
use ByJG\AccountStatements\Entity\StatementEntity;
use ByJG\AccountStatements\Exception\AccountException;
use ByJG\AccountStatements\Exception\AmountException;
use ByJG\AccountStatements\Exception\StatementException;
use ByJG\AccountStatements\Repository\AccountRepository;
use ByJG\AccountStatements\Repository\StatementRepository;
use ByJG\MicroOrm\ConnectionManager;
use ByJG\MicroOrm\Exception\TransactionException;
use ByJG\Serializer\Exception\InvalidArgumentException;
use Exception;

class StatementBLL
{
    /**
     * @var StatementRepository
     */
    protected $statementRepository;

    /**
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * StatementBLL constructor.
     * @param StatementRepository $statementRepository
     * @param AccountRepository $accountRepository
     */
    public function __construct(StatementRepository $statementRepository, AccountRepository $accountRepository)
    {
        $this->statementRepository = $statementRepository;
        $this->accountRepository = $accountRepository;
    }


    /**
     * Get a Statement By ID.
     *
     * @param int|string $idStatement Optional. empty, return all all ids.
     * @return mixed
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getById($idStatement)
    {
        return $this->statementRepository->getById($idStatement);
    }

    /**
     * Add funds to an account
     *
     * @param StatementDTO $dto
     * @return int Statement ID
     * @throws AmountException
     * @throws TransactionException
     */
    public function addFunds(StatementDTO $dto)
    {
        // Validations
        if ($dto->getAmount() <= 0) {
            throw new AmountException('Amount needs to be greater than zero');
        }

        // Get an Account
        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($dto->getIdaccount());
            if (is_null($account) || $account->getIdAccount() == "") {
                throw new AccountException("addFunds: Account " . $dto->getIdaccount() . " not found");
            }

            // Update Values in an account
            $account->setGrossBalance($account->getGrossBalance() + $dto->getAmount());
            $account->setNetBalance($account->getNetBalance() + $dto->getAmount());
            $this->accountRepository->save($account);

            // Add the new line
            $statement = new StatementEntity();
            $statement->setAmount($dto->getAmount());
            $statement->setIdType(StatementEntity::DEPOSIT);
            $statement->setDescription($dto->getDescription());
            $statement->setReference($dto->getReference());
            $statement->setCode($dto->getCode());
            $statement->attachAccount($account);

            // Save to DB
            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getIdStatement();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Withdraw funds from an account
     *
     * @param StatementDTO $dto
     * @return int Statement ID
     * @throws AmountException
     * @throws TransactionException
     */
    public function withdrawFunds(StatementDTO $dto)
    {
        if ($dto->getAmount() <= 0) {
            throw new AmountException('Amount needs to be greater than zero');
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($dto->getIdaccount());
            if (is_null($account)) {
                throw new AccountException('addFunds: Account not found');
            }

            // Cannot withdraw above the account balance.
            if ($account->getNetBalance() - $dto->getAmount() < $account->getMinValue()) {
                throw new AmountException('Cannot withdraw above the account balance.');
            }

            // Update balances
            $account->setGrossBalance($account->getGrossBalance() - $dto->getAmount());
            $account->setNetBalance($account->getNetBalance() - $dto->getAmount());
            $this->accountRepository->save($account);

            // Create the Statement
            $statement = new StatementEntity();
            $statement->setIdAccount($dto->getIdaccount());
            $statement->setAmount($dto->getAmount());
            $statement->setIdType(StatementEntity::WITHDRAW);
            $statement->setDescription($dto->getDescription());
            $statement->setReference($dto->getReference());
            $statement->setCode($dto->getCode());
            $statement->attachAccount($account);

            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getIdStatement();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Reserve funds to future withdrawn. It affects the net balance but not the gross balance
     *
     * @param StatementDTO $dto
     * @return int Statement ID
     * @throws AmountException
     * @throws TransactionException
     */
    public function reserveFundsForWithdraw(StatementDTO $dto)
    {
        // Validations
        if ($dto->getAmount() <= 0) {
            throw new AmountException('Amount needs to be greater than zero');
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($dto->getIdaccount());
            if (is_null($account)) {
                throw new AccountException('reserveFundsForWithdraw: Account not found');
            }

            // Cannot withdraw above the account balance.
            if ($account->getNetBalance() - $dto->getAmount() < $account->getMinValue()) {
                throw new AmountException('Cannot withdraw above the account balance.');
            }

            // Update Balance
            $account->setUnCleared($account->getUnCleared() + $dto->getAmount());
            $account->setNetBalance($account->getNetBalance() - $dto->getAmount());
            $this->accountRepository->save($account);

            // Create Statement
            $statement = new StatementEntity();
            $statement->setIdAccount($dto->getIdaccount());
            $statement->setAmount($dto->getAmount());
            $statement->setIdType(StatementEntity::WITHDRAWBLOCKED);
            $statement->setDescription($dto->getDescription());
            $statement->setReference($dto->getReference());
            $statement->setCode($dto->getCode());
            $statement->attachAccount($account);

            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getIdStatement();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Reserve funds to future deposit. Update net balance but not gross balance.
     *
     * @param StatementDTO $dto
     * @return int Statement ID
     * @throws AmountException
     * @throws TransactionException
     */
    public function reserveFundsForDeposit(StatementDTO $dto)
    {
        // Validações
        if ($dto->getAmount() <= 0) {
            throw new AmountException('Amount needs to be greater than zero');
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($dto->getIdaccount());
            if (is_null($account)) {
                throw new AccountException('reserveFundsForDeposit: Account not found');
            }

            // Update Balances
            $account->setUnCleared($account->getUnCleared() - $dto->getAmount());
            $account->setNetBalance($account->getNetBalance() + $dto->getAmount());
            $this->accountRepository->save($account);

            // Create Statement
            $statement = new StatementEntity();
            $statement->setIdAccount($dto->getIdaccount());
            $statement->setAmount($dto->getAmount());
            $statement->setIdType(StatementEntity::DEPOSITBLOCKED);
            $statement->setDescription($dto->getDescription());
            $statement->setReference($dto->getReference());
            $statement->setCode($dto->getCode());
            $statement->attachAccount($account);

            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getIdStatement();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Accept a reserved fund and update gross balance
     *
     * @param int $statementId
     * @param string $description
     * @param null $code
     * @return int Statement ID
     * @throws TransactionException
     */
    public function acceptFundsById($statementId, $description = null, $code = null)
    {
        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $statement = $this->statementRepository->getById($statementId);
            if (is_null($statement)) {
                throw new StatementException('acceptFundsById: Statement not found');
            }

            // Validate if statement can be accepted.
            if ($statement->getIdType() != StatementEntity::WITHDRAWBLOCKED && $statement->getIdType() != StatementEntity::DEPOSITBLOCKED) {
                throw new StatementException("The statement id doesn't belongs to a reserved fund.");
            }

            // Validate if the statement has been already accepted.
            if ($this->statementRepository->getByIdParent($statementId) != null) {
                throw new StatementException('The statement has been accepted already');
            }

            // Get values and apply the updates
            $signal = $statement->getIdType() == StatementEntity::DEPOSITBLOCKED ? 1 : -1;

            $account = $this->accountRepository->getById($statement->getIdAccount());
            $account->setUnCleared($account->getUnCleared() + ($statement->getAmount() * $signal));
            $account->setGrossBalance($account->getGrossBalance() + ($statement->getAmount() * $signal));
            $account->setEntryDate(null);
            $this->accountRepository->save($account);

            // Update data
            $statement->setIdStatementParent($statement->getIdStatement());
            $statement->setIdStatement(null); // Poder criar um novo registro
            $statement->setDate(null);
            $statement->setIdType($statement->getIdType() == StatementEntity::WITHDRAWBLOCKED ? StatementEntity::WITHDRAW : StatementEntity::DEPOSIT);
            $statement->attachAccount($account);
            if (!empty($description)) {
                $statement->setDescription($description);
            }
            if (!empty($code)) {
                $statement->setCode($code);
            }
            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getIdStatement();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Reject a reserved fund and return the net balance
     *
     * @param int $statementId
     * @param string $description
     * @param null $code
     * @return int Statement ID
     * @throws TransactionException
     */
    public function rejectFundsById($statementId, $description = null, $code = null)
    {
        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $statement = $this->statementRepository->getById($statementId);
            if (is_null($statement)) {
                throw new StatementException('acceptFundsById: Statement not found');
            }

            // Validate if statement can be accepted.
            if ($statement->getIdType() != StatementEntity::WITHDRAWBLOCKED && $statement->getIdType() != StatementEntity::DEPOSITBLOCKED) {
                throw new StatementException("The statement id doesn't belongs to a reserved fund.");
            }

            // Validate if the statement has been already accepted.
            if ($this->statementRepository->getByIdParent($statementId) != null) {
                throw new StatementException('The statement has been accepted already');
            }

            // Update Account
            $signal = $statement->getIdType() == StatementEntity::DEPOSITBLOCKED ? -1 : +1;

            $account = $this->accountRepository->getById($statement->getIdAccount());
            $account->setUnCleared($account->getUnCleared() - ($statement->getAmount() * $signal));
            $account->setNetBalance($account->getNetBalance() + ($statement->getAmount() * $signal));
            $account->setEntryDate(null);
            $this->accountRepository->save($account);

            // Update Statement
            $statement->setIdStatementParent($statement->getIdStatement());
            $statement->setIdStatement(null); // Poder criar um novo registro
            $statement->setDate(null);
            $statement->setIdType(StatementEntity::REJECT);
            $statement->attachAccount($account);
            if (!empty($description)) {
                $statement->setDescription($description);
            }
            if (!empty($code)) {
                $statement->setCode($code);
            }
            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getIdStatement();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Update all blocked (reserved) transactions
     *
     * @param int $idAccount
     * @return StatementEntity[]
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getUnclearedStatements($idAccount = null)
    {
        return $this->statementRepository->getUnclearedStatements($idAccount);
    }

    public function getByDate($idAccount, $startDate, $endDate)
    {
        return $this->statementRepository->getByDate($idAccount, $startDate, $endDate);
    }

    /**
     * This statement is blocked (reserved)
     *
     * @param int $idStatement
     * @return bool
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function isStatementUncleared($idStatement = null)
    {
        return null === $this->statementRepository->getByIdParent($idStatement, true);
    }

    /**
     * @return StatementRepository
     */
    public function getRepository()
    {
        return $this->statementRepository;
    }
}
