<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ByJG\AccountStatements\Bll;

use ByJG\AccountStatements\DTO\StatementDTO;
use ByJG\AccountStatements\Entity\AccountEntity;
use ByJG\AccountStatements\Entity\StatementEntity;
use ByJG\AccountStatements\Exception\AccountException;
use ByJG\AccountStatements\Exception\AccountTypeException;
use ByJG\AccountStatements\Exception\AmountException;
use ByJG\AccountStatements\Exception\StatementException;
use ByJG\AccountStatements\Repository\AccountRepository;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\RepositoryReadOnlyException;
use ByJG\MicroOrm\Exception\UpdateConstraintException;
use ByJG\Serializer\Exception\InvalidArgumentException;
use PDOException;

class AccountBLL
{
    /**
     * @var AccountRepository
     */
    protected AccountRepository $accountRepository;

    /**
     * @var AccountTypeBLL
     */
    protected AccountTypeBLL $accountTypeBLL;

    /**
     * @var StatementBLL
     */
    protected StatementBLL $statementBLL;

    /**
     * AccountBLL constructor.
     * @param AccountRepository $accountRepository
     * @param AccountTypeBLL $accountTypeBLL
     * @param StatementBLL $statementBLL
     */
    public function __construct(AccountRepository $accountRepository, AccountTypeBLL $accountTypeBLL, StatementBLL $statementBLL)
    {
        $this->accountRepository = $accountRepository;

        $this->accountTypeBLL = $accountTypeBLL;
        $this->statementBLL = $statementBLL;
    }


    /**
     * Get an account by ID.
     *
     * @param int $accountId Optional id empty return all. 
     * @return AccountEntity|AccountEntity[]
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function getById(int $accountId): array|AccountEntity
    {
        
        return $this->accountRepository->getById($accountId);
    }

    /**
     * Obtém uma lista AccountEntity pelo Id do Usuário
     *
     * @param string $userId
     * @param string $accountType Tipo de conta
     * @return AccountEntity[]
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getByUserId(string $userId, string $accountType = ""): array
    {
        

        return $this->accountRepository->getByUserId($userId, $accountType);
    }

    /**
     * Obtém uma lista  AccountEntity pelo Account Type ID
     *
     * @param string $accountTypeId
     * @return AccountEntity[]
     * @throws InvalidArgumentException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function getByAccountTypeId(string $accountTypeId): array
    {
        return $this->accountRepository->getByAccountTypeId($accountTypeId);
    }

    /**
     * Cria uma nova conta no sistema
     *
     * @param string $accountTypeId
     * @param string $userId
     * @param float $balance
     * @param float|int $price
     * @param float|int $minValue
     * @param string|null $extra
     * @return int
     * @throws AccountException
     * @throws AccountTypeException
     * @throws AmountException
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     * @throws StatementException
     * @throws UpdateConstraintException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function createAccount(string $accountTypeId, string $userId, float $balance, float|int $price = 1, float|int $minValue = 0, string $extra = null): int
    {
        // Faz as validações
        if ($this->accountTypeBLL->getById($accountTypeId) == null) {
            throw new AccountTypeException('AccountTypeId ' . $accountTypeId . ' não existe');
        }

        // Define os dados
        $model = new AccountEntity();
        $model->setAccountTypeId($accountTypeId);
        $model->setUserId($userId);
        $model->setGrossBalance(0);
        $model->setNetBalance(0);
        $model->setUncleared(0);
        $model->setPrice($price);
        $model->setExtra($extra);
        $model->setMinValue($minValue);

        // Persiste os dados.
        
        try {
            $result = $this->accountRepository->save($model);
            $accountId = $result->getAccountId();
        } catch (PDOException $ex) {
            if (str_contains($ex->getMessage(), "Duplicate entry")) {
                throw new AccountException("Usuário $userId já possui uma conta do tipo $accountTypeId");
            } else {
                throw $ex;
            }
        }

        if ($balance > 0) {
            $this->statementBLL->addFunds(StatementDTO::create($accountId, $balance)->setDescription("Opening Balance")->setCode('BAL'));
        } elseif ($balance < 0) {
            $this->statementBLL->withdrawFunds(StatementDTO::create($accountId, abs($balance))->setDescription("Opening Balance")->setCode('BAL'));
        }

        return $accountId;
    }

    /**
     * Reinicia o balanço
     *
     * @param int $accountId
     * @param float $newBalance
     * @param float|int $newPrice
     * @param float|int $newMinValue
     * @param string $description
     * @return int|null
     * @throws AccountException
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     * @throws StatementException
     * @throws UpdateConstraintException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function overrideBalance(
        int       $accountId,
        float     $newBalance,
        float|int $newPrice = 1,
        float|int $newMinValue = 0,
        string $description = "Reset Balance"
    ): ?int
    {
        
        $model = $this->accountRepository->getById($accountId);

        if (empty($model)) {
            throw new AccountException('Id da conta não existe. Não é possível fechar a conta');
        }

        // Get total value reserved
        $unclearedValues = 0;
        $qtd = 0;
        $object = $this->statementBLL->getUnclearedStatements($model->getAccountId());
        foreach ($object as $stmt) {
            $qtd++;
            $unclearedValues += $stmt->getAmount();
        }

        if ($newBalance - $unclearedValues < $newMinValue) {
            throw new StatementException(
                "Nâo é possível alterar para esse valor pois ainda existem $qtd transações pendentes " .
                "totalizando $unclearedValues milhas"
            );
        }

        // Update object Account
        $model->setGrossBalance($newBalance);
        $model->setNetBalance($newBalance - $unclearedValues);
        $model->setUnCleared($unclearedValues);
        $model->setPrice($newPrice);
        $model->setMinValue($newMinValue);
        $this->accountRepository->save($model);

        // Create new Statement
        $statement = new StatementEntity();
        $statement->setAmount($newBalance);
        $statement->setAccountId($model->getAccountId());
        $statement->setDescription(empty($description) ? "Reset Balance" : $description);
        $statement->setTypeId(StatementEntity::BALANCE);
        $statement->setCode('BAL');
        $statement->setGrossBalance($newBalance);
        $statement->setNetBalance($newBalance - $unclearedValues);
        $statement->setUnCleared($unclearedValues);
        $statement->setPrice($newPrice);
        $statement->setAccountTypeId($model->getAccountTypeId());
        $this->statementBLL->getRepository()->save($statement);

        return $statement->getStatementId();
    }

    /**
     * Encerra (Zera) uma conta
     *
     * @param int $accountId
     * @return int|null
     * @throws AccountException
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     * @throws StatementException
     * @throws UpdateConstraintException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function closeAccount(int $accountId): ?int
    {
        return $this->overrideBalance($accountId, 0, 0);
    }

    /**
     * @param int $accountId
     * @param float $balance
     * @param string $description
     * @return int|null
     * @throws AccountException
     * @throws AmountException
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     * @throws StatementException
     * @throws UpdateConstraintException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function partialBalance(int $accountId, float $balance, string $description = "Partial Balance"): ?int
    {
        $account = $this->getById($accountId);

        $amount = $balance - $account->getNetBalance();

        if ($amount >= 0) {
            $statementId = $this->statementBLL->addFunds(StatementDTO::create($accountId, $amount)->setDescription($description));
        } else {
            $statementId = $this->statementBLL->withdrawFunds(StatementDTO::create($accountId, abs($amount))->setDescription($description));
        }

        return $statementId;
    }

    /**
     * @param int $accountSource
     * @param int $accountTarget
     * @param float $amount
     * @return array
     * @throws AccountException
     * @throws AmountException
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     * @throws StatementException
     * @throws UpdateConstraintException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function transferFunds(int $accountSource, int $accountTarget, float $amount): array
    {
        $refSource = bin2hex(openssl_random_pseudo_bytes(16));

        $statementSourceDTO = StatementDTO::createEmpty();
        $statementSourceDTO->setAccountId($accountSource);
        $statementSourceDTO->setAmount($amount);
        $statementSourceDTO->setCode('T_TO');
        $statementSourceDTO->setReferenceSource('transfer_to');
        $statementSourceDTO->setReferenceId($refSource);
        $statementSourceDTO->setDescription('Transfer to account id ' . $accountTarget);

        $statementTargetDTO = StatementDTO::createEmpty();
        $statementTargetDTO->setAccountId($accountTarget);
        $statementTargetDTO->setAmount($amount);
        $statementTargetDTO->setCode('T_FROM');
        $statementTargetDTO->setReferenceSource('transfer_from');
        $statementTargetDTO->setReferenceId($refSource);
        $statementTargetDTO->setDescription('Transfer from account id ' . $accountSource);

        $statementSourceId = $this->statementBLL->withdrawFunds($statementSourceDTO);
        $statementTargetId = $this->statementBLL->addFunds($statementTargetDTO);

        return [ $statementSourceId, $statementTargetId ];
    }

    public function getRepository(): AccountRepository
    {
        return $this->accountRepository;
    }
}
