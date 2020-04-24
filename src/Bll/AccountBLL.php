<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ByJG\AccountStatements\Bll;

use ByJG\AccountStatements\Entity\AccountEntity;
use ByJG\AccountStatements\Entity\StatementEntity;
use ByJG\AccountStatements\Exception\AccountException;
use ByJG\AccountStatements\Exception\AccountTypeException;
use ByJG\AccountStatements\Exception\StatementException;
use ByJG\AccountStatements\Repository\AccountRepository;
use ByJG\AccountStatements\Repository\AccountTypeRepository;
use ByJG\AccountStatements\Repository\StatementRepository;
use Exception;
use InvalidArgumentException;
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
     * Obtém um Account pelo seu ID.
     * Se o ID não for passado, então devolve todos os Accounts.
     *
     * @param int $idAccount Opcional. Se não for passado obtém todos
     * @return AccountEntity|\ByJG\AccountStatements\Entity\AccountEntity[]
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getById($idAccount)
    {
        
        return $this->accountRepository->getById($idAccount);
    }

    /**
     * Obtém uma lista  AccountEntity pelo Id do Usuário
     *
     * @param int $idUser
     * @param string $accountType Tipo de conta
     * @return AccountEntity[]
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getUserId($idUser, $accountType = "")
    {
        

        return $this->accountRepository->getUserId($idUser, $accountType);
    }

    /**
     * Obtém uma lista  AccountEntity pelo Account Type ID
     *
     * @param int $idAccountType
     * @return AccountEntity[]
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getAccountTypeId($idAccountType)
    {
        

        return $this->accountRepository->getAccountTypeId($idAccountType);
    }

    /**
     * Cria uma nova conta no sistema
     *
     * @param string $idAccountType
     * @param int $idUser
     * @param float $balance
     * @param float|int $price
     * @param int $minValue
     * @param string $extra
     * @return int
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\MicroOrm\Exception\OrmBeforeInvalidException
     * @throws \ByJG\MicroOrm\Exception\OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function createAccount($idAccountType, $idUser, $balance, $price = 1, $minValue = 0, $extra = null)
    {
        // Faz as validações
        if ($this->accountTypeBLL->getById($idAccountType) == null) {
            throw new AccountTypeException('IdAccountType ' . $idAccountType . ' não existe');
        }

        // Define os dados
        $model = new AccountEntity();
        $model->setIdAccountType($idAccountType);
        $model->setIdUser($idUser);
        $model->setGrossBalance(0);
        $model->setNetBalance(0);
        $model->setUncleared(0);
        $model->setPrice($price);
        $model->setExtra($extra);
        $model->setMinValue($minValue);

        // Persiste os dados.
        
        try {
            $result = $this->accountRepository->save($model);
            $idAccount = $result->getIdAccount();
        } catch (PDOException $ex) {
            if (strpos($ex->getMessage(), "Duplicate entry") !== false) {
                throw new AccountException("Usuário $idUser já possui uma conta do tipo $idAccountType");
            } else {
                throw $ex;
            }
        }

        if ($balance > 0) {
            $this->statementBLL->addFunds($idAccount, $balance, "Opening Balance");
        } elseif ($balance < 0) {
            $this->statementBLL->withdrawFunds($idAccount, abs($balance), "Opening Balance");
        }

        return $idAccount;
    }

    /**
     * Reinicia o balanço
     *
     * @param int $idAccount
     * @param float $newBalance
     * @param float|int $newPrice
     * @param float|int $newMinValue
     * @param string $description
     * @throws Exception
     * @return boolean
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function overrideBalance(
        $idAccount,
        $newBalance,
        $newPrice = 1,
        $newMinValue = 0,
        $description = "Reset Balance"
    ) {
        
        $model = $this->accountRepository->getById($idAccount);

        if (empty($model)) {
            throw new AccountException('Id da conta não existe. Não é possível fechar a conta');
        }

        // Get total value reserved
        $unclearedValues = 0;
        $qtd = 0;
        $object = $this->statementBLL->getUnclearedStatements($model->getIdAccount());
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
        $statement->setIdAccount($model->getIdAccount());
        $statement->setDescription(empty($description) ? "Reset Balance" : $description);
        $statement->setIdType(StatementEntity::BALANCE);
        $statement->setGrossBalance($newBalance);
        $statement->setNetBalance($newBalance - $unclearedValues);
        $statement->setUnCleared($unclearedValues);
        $statement->setPrice($newPrice);
        $statement->setIdAccountType($model->getIdAccountType());
        $this->statementBLL->getRepository()->save($statement);

        return $statement->getIdStatement();
    }

    /**
     * Encerra (Zera) uma conta
     *
     * @param int $idAccount
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function closeAccount($idAccount)
    {
        return $this->overrideBalance($idAccount, 0, 0, 0);
    }

    /**
     * @param $idaccount
     * @param $balance
     * @param string $description
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function partialBalance($idaccount, $balance, $description = "Partial Balance")
    {
        $account = $this->getById($idaccount);

        $amount = $balance - $account->getNetBalance();

        if ($amount > 0) {
            $idStatement = $this->statementBLL->addFunds($idaccount, $amount, $description);
        } elseif ($amount < 0) {
            $idStatement = $this->statementBLL->withdrawFunds($idaccount, abs($amount), $description);
        }

        return $idStatement;
    }
}
