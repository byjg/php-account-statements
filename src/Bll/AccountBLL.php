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
use ByJG\MicroOrm\Exception\TransactionException;
use ByJG\MicroOrm\TransactionManager;
use ByJG\Serializer\Exception\InvalidArgumentException;
use Exception;
use PDOException;

class AccountBLL
{
    /**
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * @var AccountTypeBLL
     */
    protected $accountTypeBLL;

    /**
     * @var StatementBLL
     */
    protected $statementBLL;

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
     * @throws InvalidArgumentException
     */
    public function getById($accountId)
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
    public function getByUserId($userId, $accountType = "")
    {
        

        return $this->accountRepository->getByUserId($userId, $accountType);
    }

    /**
     * Obtém uma lista  AccountEntity pelo Account Type ID
     *
     * @param int $accountTypeId
     * @return AccountEntity[]
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getByAccountTypeId($accountTypeId)
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
     * @param int $minValue
     * @param string $extra
     * @return int
     * @throws AccountException
     * @throws AccountTypeException
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws TransactionException
     * @throws AmountException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function createAccount($accountTypeId, $userId, $balance, $price = 1, $minValue = 0, $extra = null)
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
            if (strpos($ex->getMessage(), "Duplicate entry") !== false) {
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
     * @throws Exception
     * @return int
     */
    public function overrideBalance(
        $accountId,
        $newBalance,
        $newPrice = 1,
        $newMinValue = 0,
        $description = "Reset Balance"
    ) {
        
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
     * @return int
     * @throws Exception
     */
    public function closeAccount($accountId)
    {
        return $this->overrideBalance($accountId, 0, 0, 0);
    }

    /**
     * @param $accountId
     * @param $balance
     * @param string $description
     * @return int
     * @throws InvalidArgumentException
     * @throws TransactionException
     * @throws AmountException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function partialBalance($accountId, $balance, $description = "Partial Balance")
    {
        $account = $this->getById($accountId);

        $amount = $balance - $account->getNetBalance();

        if ($amount > 0) {
            $statementId = $this->statementBLL->addFunds(StatementDTO::create($accountId, $amount)->setDescription($description));
        } elseif ($amount < 0) {
            $statementId = $this->statementBLL->withdrawFunds(StatementDTO::create($accountId, abs($amount))->setDescription($description));
        }

        return $statementId;
    }

    public function transferFunds($accountSource, $accountTarget, $amount)
    {
        $refSource = bin2hex(openssl_random_pseudo_bytes(16));

        $statementSourceDTO = StatementDTO::createEmpty();
        $statementSourceDTO->setAccountId($accountSource);
        $statementSourceDTO->setAmount($amount);
        $statementSourceDTO->setCode('T_TO');
        $statementSourceDTO->setReferenceSource('transfer_to');
        $statementSourceDTO->setReferenceId("$accountTarget:$refSource");
        $statementSourceDTO->setDescription('Transfer to account id ' . $accountTarget);

        $statementTargetDTO = StatementDTO::createEmpty();
        $statementTargetDTO->setAccountId($accountTarget);
        $statementTargetDTO->setAmount($amount);
        $statementTargetDTO->setCode('T_FROM');
        $statementTargetDTO->setReferenceSource('transfer_from');
        $statementTargetDTO->setReferenceId("$accountSource:$refSource");
        $statementTargetDTO->setDescription('Transfer from account id ' . $accountSource);

        $statementSourceId = $this->statementBLL->withdrawFunds($statementSourceDTO);
        $statementTargetId = $this->statementBLL->addFunds($statementTargetDTO);

        return [ $statementSourceId, $statementTargetId ];
    }

    public function getRepository()
    {
        return $this->accountRepository;
    }
}
