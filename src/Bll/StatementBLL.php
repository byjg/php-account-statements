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
     * @param int|string $statementId Optional. empty, return all all ids.
     * @return mixed
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getById($statementId)
    {
        return $this->statementRepository->getById($statementId);
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
        if (!$dto->hasAccount()) {
            throw new StatementException('Account is required');
        }
        if ($dto->getAmount() <= 0) {
            throw new AmountException('Amount needs to be greater than zero');
        }

        // Get an Account
        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($dto->getAccountId());
            if (is_null($account) || $account->getAccountId() == "") {
                throw new AccountException("addFunds: Account " . $dto->getAccountId() . " not found");
            }

            // Update Values in an account
            $account->setGrossBalance($account->getGrossBalance() + $dto->getAmount());
            $account->setNetBalance($account->getNetBalance() + $dto->getAmount());
            $this->accountRepository->save($account);

            // Add the new line
            $statement = new StatementEntity();
            $statement->setAmount($dto->getAmount());
            $statement->setTypeId(StatementEntity::DEPOSIT);
            $statement->setDescription($dto->getDescription());
            $statement->setReferenceId($dto->getReferenceId());
            $statement->setReferenceSource($dto->getReferenceSource());
            $statement->setCode($dto->getCode());
            $statement->attachAccount($account);

            // Save to DB
            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getStatementId();
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
        // Validations
        if (!$dto->hasAccount()) {
            throw new StatementException('Account is required');
        }
        if ($dto->getAmount() <= 0) {
            throw new AmountException('Amount needs to be greater than zero');
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($dto->getAccountId());
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
            $statement->setAccountId($dto->getAccountId());
            $statement->setAmount($dto->getAmount());
            $statement->setTypeId(StatementEntity::WITHDRAW);
            $statement->setDescription($dto->getDescription());
            $statement->setReferenceId($dto->getReferenceId());
            $statement->setReferenceSource($dto->getReferenceSource());
            $statement->setCode($dto->getCode());
            $statement->attachAccount($account);

            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getStatementId();
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
        if (!$dto->hasAccount()) {
            throw new StatementException('Account is required');
        }
        if ($dto->getAmount() <= 0) {
            throw new AmountException('Amount needs to be greater than zero');
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($dto->getAccountId());
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
            $statement->setAccountId($dto->getAccountId());
            $statement->setAmount($dto->getAmount());
            $statement->setTypeId(StatementEntity::WITHDRAW_BLOCKED);
            $statement->setDescription($dto->getDescription());
            $statement->setReferenceId($dto->getReferenceId());
            $statement->setReferenceSource($dto->getReferenceSource());
            $statement->setCode($dto->getCode());
            $statement->attachAccount($account);

            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getStatementId();
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
        if (!$dto->hasAccount()) {
            throw new StatementException('Account is required');
        }
        if ($dto->getAmount() <= 0) {
            throw new AmountException('Amount needs to be greater than zero');
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($dto->getAccountId());
            if (is_null($account)) {
                throw new AccountException('reserveFundsForDeposit: Account not found');
            }

            // Update Balances
            $account->setUnCleared($account->getUnCleared() - $dto->getAmount());
            $account->setNetBalance($account->getNetBalance() + $dto->getAmount());
            $this->accountRepository->save($account);

            // Create Statement
            $statement = new StatementEntity();
            $statement->setAccountId($dto->getAccountId());
            $statement->setAmount($dto->getAmount());
            $statement->setTypeId(StatementEntity::DEPOSIT_BLOCKED);
            $statement->setDescription($dto->getDescription());
            $statement->setReferenceId($dto->getReferenceId());
            $statement->setReferenceSource($dto->getReferenceSource());
            $statement->setCode($dto->getCode());
            $statement->attachAccount($account);

            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getStatementId();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Accept a reserved fund and update gross balance
     *
     * @param int $statementId
     * @param StatementDTO $statementDto
     * @return int Statement ID
     * @throws TransactionException
     */
    public function acceptFundsById($statementId, $statementDto = null)
    {
        if (is_null($statementDto)) {
            $statementDto = StatementDTO::createEmpty();
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $statement = $this->statementRepository->getById($statementId);
            if (is_null($statement)) {
                throw new StatementException('acceptFundsById: Statement not found');
            }

            // Validate if statement can be accepted.
            if ($statement->getTypeId() != StatementEntity::WITHDRAW_BLOCKED && $statement->getTypeId() != StatementEntity::DEPOSIT_BLOCKED) {
                throw new StatementException("The statement id doesn't belongs to a reserved fund.");
            }

            // Validate if the statement has been already accepted.
            if ($this->statementRepository->getByParentId($statementId) != null) {
                throw new StatementException('The statement has been accepted already');
            }

            if ($statementDto->hasAccount() && $statementDto->getAccountId() != $statement->getAccountId()) {
                throw new StatementException('The statement account is different from the informed account in the DTO. Try createEmpty().');
            }

            // Get values and apply the updates
            $signal = $statement->getTypeId() == StatementEntity::DEPOSIT_BLOCKED ? 1 : -1;

            $account = $this->accountRepository->getById($statement->getAccountId());
            $account->setUnCleared($account->getUnCleared() + ($statement->getAmount() * $signal));
            $account->setGrossBalance($account->getGrossBalance() + ($statement->getAmount() * $signal));
            $account->setEntryDate(null);
            $this->accountRepository->save($account);

            // Update data
            $statement->setStatementParentId($statement->getStatementId());
            $statement->setStatementId(null); // Poder criar um novo registro
            $statement->setDate(null);
            $statement->setTypeId($statement->getTypeId() == StatementEntity::WITHDRAW_BLOCKED ? StatementEntity::WITHDRAW : StatementEntity::DEPOSIT);
            $statement->attachAccount($account);
            $statementDto->setToStatement($statement);
            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getStatementId();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Reject a reserved fund and return the net balance
     *
     * @param int $statementId
     * @param StatementDTO $statementDto
     * @return int Statement ID
     * @throws TransactionException
     */
    public function rejectFundsById($statementId, $statementDto = null)
    {
        if (is_null($statementDto)) {
            $statementDto = StatementDTO::createEmpty();
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $statement = $this->statementRepository->getById($statementId);
            if (is_null($statement)) {
                throw new StatementException('rejectFundsById: Statement not found');
            }

            // Validate if statement can be accepted.
            if ($statement->getTypeId() != StatementEntity::WITHDRAW_BLOCKED && $statement->getTypeId() != StatementEntity::DEPOSIT_BLOCKED) {
                throw new StatementException("The statement id doesn't belongs to a reserved fund.");
            }

            // Validate if the statement has been already accepted.
            if ($this->statementRepository->getByParentId($statementId) != null) {
                throw new StatementException('The statement has been accepted already');
            }

            if ($statementDto->hasAccount() && $statementDto->getAccountId() != $statement->getAccountId()) {
                throw new StatementException('The statement account is different from the informed account in the DTO. Try createEmpty().');
            }

            // Update Account
            $signal = $statement->getTypeId() == StatementEntity::DEPOSIT_BLOCKED ? -1 : +1;

            $account = $this->accountRepository->getById($statement->getAccountId());
            $account->setUnCleared($account->getUnCleared() - ($statement->getAmount() * $signal));
            $account->setNetBalance($account->getNetBalance() + ($statement->getAmount() * $signal));
            $account->setEntryDate(null);
            $this->accountRepository->save($account);

            // Update Statement
            $statement->setStatementParentId($statement->getStatementId());
            $statement->setStatementId(null); // Poder criar um novo registro
            $statement->setDate(null);
            $statement->setTypeId(StatementEntity::REJECT);
            $statement->attachAccount($account);
            $statementDto->setToStatement($statement);
            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getStatementId();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Update all blocked (reserved) transactions
     *
     * @param int $accountId
     * @return StatementEntity[]
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getUnclearedStatements($accountId = null)
    {
        return $this->statementRepository->getUnclearedStatements($accountId);
    }

    public function getByDate($accountId, $startDate, $endDate)
    {
        return $this->statementRepository->getByDate($accountId, $startDate, $endDate);
    }

    /**
     * This statement is blocked (reserved)
     *
     * @param int $statementId
     * @return bool
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function isStatementUncleared($statementId = null)
    {
        return null === $this->statementRepository->getByParentId($statementId, true);
    }

    /**
     * @return StatementRepository
     */
    public function getRepository()
    {
        return $this->statementRepository;
    }
}
