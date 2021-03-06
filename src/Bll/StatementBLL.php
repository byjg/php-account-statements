<?php

namespace ByJG\AccountStatements\Bll;

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
     * Obtém um Statement por ID.
     * Se o ID não for passado, então devolve todos os Statements.
     *
     * @param int|string $idStatement Opcional. Se não for passado obtém todos
     * @return mixed
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getById($idStatement)
    {
        return $this->statementRepository->getById($idStatement);
    }

    /**
     * Adiciona fundos a uma conta
     *
     * @param int $idaccount
     * @param float $amount
     * @param string $description
     * @param string $reference
     * @return int Id do Statement Adicionado
     * @throws AmountException
     * @throws TransactionException
     * @throws Exception
     */
    public function addFunds($idaccount, $amount, $description = null, $reference = null)
    {
        // Validações
        if ($amount <= 0) {
            throw new AmountException('Amount precisa ser maior que zero');
        }

        // Obtem DAL de Account
        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($idaccount);
            if (is_null($account) || $account->getIdAccount() == "") {
                throw new AccountException("addFunds: Account $idaccount not found");
            }

            // Atualiza os valores em Account
            $account->setGrossBalance($account->getGrossBalance() + $amount);
            $account->setNetBalance($account->getNetBalance() + $amount);
            $this->accountRepository->save($account);

            // Adciona uma nova linha com os novos dados.
            $statement = new StatementEntity();
            $statement->setAmount($amount);
            $statement->setIdType(StatementEntity::DEPOSIT);
            $statement->setDescription($description);
            $statement->setReference($reference);
            $statement->attachAccount($account);

            // Salva em banco
            $result = $this->statementRepository->save($statement);

            $connectionManager->commitTransaction();

            return $result->getIdStatement();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Saca fundos de uma conta
     *
     * @param int $idaccount
     * @param float $amount
     * @param string $description
     * @param string $reference
     * @return int Id do Statement Adicionado
     * @throws AmountException
     * @throws TransactionException
     * @throws Exception
     */
    public function withdrawFunds($idaccount, $amount, $description = null, $reference = null)
    {
        if ($amount <= 0) {
            throw new AmountException('Amount precisa ser maior que zero');
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($idaccount);
            if (is_null($account)) {
                throw new AccountException('addFunds: Account not found');
            }

            // Se o valor a ser retirado é negativo, então dá um erro.
            if ($account->getNetBalance() - $amount < $account->getMinValue()) {
                throw new AmountException('O valor de retirada é maior que o saldo disponível em conta');
            }

            // Atualiza os dados
            $account->setGrossBalance($account->getGrossBalance() - $amount);
            $account->setNetBalance($account->getNetBalance() - $amount);
            $this->accountRepository->save($account);

            // Cria o statement
            $statement = new StatementEntity();
            $statement->setIdAccount($idaccount);
            $statement->setAmount($amount);
            $statement->setIdType(StatementEntity::WITHDRAW);
            $statement->setDescription($description);
            $statement->setReference($reference);
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
     * Reserva fundos para serem sacados (abate do valor líquido, mas não do Bruto)
     *
     * @param int $idaccount
     * @param float $amount
     * @param string $description
     * @param string $reference
     * @return int id do statement adicionado
     * @throws AmountException
     * @throws TransactionException
     * @throws Exception
     */
    public function reserveFundsForWithdraw($idaccount, $amount, $description = null, $reference = null)
    {
        // Validações
        if ($amount <= 0) {
            throw new AmountException('Amount precisa ser maior que zero');
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($idaccount);
            if (is_null($account)) {
                throw new AccountException('reserveFundsForWithdraw: Account not found');
            }

            // Se o valor a ser retirado é negativo, então dá um erro.
            if ($account->getNetBalance() - $amount < $account->getMinValue()) {
                throw new AmountException('O valor de retirada é maior que o saldo disponível em conta');
            }

            // Atualiza os dados
            $account->setUnCleared($account->getUnCleared() + $amount);
            $account->setNetBalance($account->getNetBalance() - $amount);
            $this->accountRepository->save($account);

            // Cria o statement
            $statement = new StatementEntity();
            $statement->setIdAccount($idaccount);
            $statement->setAmount($amount);
            $statement->setIdType(StatementEntity::WITHDRAWBLOCKED);
            $statement->setDescription($description);
            $statement->setReference($reference);
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
     * Reserva fundos para serem sacados (abate do valor líquido, mas não do Bruto)
     *
     * @param int $idaccount
     * @param float $amount
     * @param string $description
     * @param string $reference
     * @return int id do statement adicionado
     * @throws AmountException
     * @throws TransactionException
     * @throws Exception
     */
    public function reserveFundsForDeposit($idaccount, $amount, $description = null, $reference = null)
    {
        // Validações
        if ($amount <= 0) {
            throw new AmountException('Amount precisa ser maior que zero');
        }

        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $account = $this->accountRepository->getById($idaccount);
            if (is_null($account)) {
                throw new AccountException('reserveFundsForDeposit: Account not found');
            }

            // Atualiza os dados
            $account->setUnCleared($account->getUnCleared() - $amount);
            $account->setNetBalance($account->getNetBalance() + $amount);
            $this->accountRepository->save($account);

            // Cria o statement
            $statement = new StatementEntity();
            $statement->setIdAccount($idaccount);
            $statement->setAmount($amount);
            $statement->setIdType(StatementEntity::DEPOSITBLOCKED);
            $statement->setDescription($description);
            $statement->setReference($reference);
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
     * Aceita um fundo bloqueado e retira o montando do bruto.
     *
     * @param int $statementId
     * @param string $description
     * @return int id do statement gerado
     * @throws TransactionException
     * @throws Exception
     */
    public function acceptFundsById($statementId, $description = null)
    {
        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $statement = $this->statementRepository->getById($statementId);
            if (is_null($statement)) {
                throw new StatementException('acceptFundsById: Statement not found');
            }

            // Verifica se o statement é de um depósito bloqueado.
            if ($statement->getIdType() != StatementEntity::WITHDRAWBLOCKED && $statement->getIdType() != StatementEntity::DEPOSITBLOCKED) {
                throw new StatementException('O Id passado não é de um fundo bloqueado');
            }

            // Verifica se já foi realizado anteriormente esse processo.
            if ($this->statementRepository->getByIdParent($statementId) != null) {
                throw new StatementException('O Id passado já possui uma transação associada');
            }

            // Obtém os dados de account e faz os ajustes
            $signal = $statement->getIdType() == StatementEntity::DEPOSITBLOCKED ? 1 : -1;

            $account = $this->accountRepository->getById($statement->getIdAccount());
            $account->setUnCleared($account->getUnCleared() + ($statement->getAmount() * $signal));
            $account->setGrossBalance($account->getGrossBalance() + ($statement->getAmount() * $signal));
            $account->setEntryDate(null);
            $this->accountRepository->save($account);

            // Atualiza os dados
            $statement->setIdStatementParent($statement->getIdStatement());
            $statement->setIdStatement(null); // Poder criar um novo registro
            $statement->setDate(null);
            $statement->setIdType($statement->getIdType() == StatementEntity::WITHDRAWBLOCKED ? StatementEntity::WITHDRAW : StatementEntity::DEPOSIT);
            $statement->attachAccount($account);
            if (!empty($description)) {
                $statement->setDescription($description);
            }
            $result = $this->statementRepository->save($statement);

            // Cria o statement

            $connectionManager->commitTransaction();

            return $result->getIdStatement();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Rejeita um fundo bloqueado, devolvendo o montante para o valor líquido
     *
     * @param int $statementId
     * @param string $description
     * @return int id do statement adicionado
     * @throws TransactionException
     * @throws Exception
     */
    public function rejectFundsById($statementId, $description = null)
    {
        $connectionManager = new ConnectionManager();
        $connectionManager->beginTransaction();
        try {
            $statement = $this->statementRepository->getById($statementId);
            if (is_null($statement)) {
                throw new StatementException('acceptFundsById: Statement not found');
            }

            // Verifica se o statement é de um depósito bloqueado.
            if ($statement->getIdType() != StatementEntity::WITHDRAWBLOCKED && $statement->getIdType() != StatementEntity::DEPOSITBLOCKED) {
                throw new StatementException('O Id passado não é de um fundo bloqueado');
            }

            // Verifica se já foi realizado anteriormente esse processo.
            if ($this->statementRepository->getByIdParent($statementId) != null) {
                throw new StatementException('O Id passado já possui uma trnasação associada');
            }

            // Obtém os dados de account e faz os ajustes
            $signal = $statement->getIdType() == StatementEntity::DEPOSITBLOCKED ? -1 : +1;

            $account = $this->accountRepository->getById($statement->getIdAccount());
            $account->setUnCleared($account->getUnCleared() - ($statement->getAmount() * $signal));
            $account->setNetBalance($account->getNetBalance() + ($statement->getAmount() * $signal));
            $account->setEntryDate(null);
            $this->accountRepository->save($account);

            // Atualiza os dados
            $statement->setIdStatementParent($statement->getIdStatement());
            $statement->setIdStatement(null); // Poder criar um novo registro
            $statement->setDate(null);
            $statement->setIdType(StatementEntity::REJECT);
            $statement->attachAccount($account);
            if (!empty($description)) {
                $statement->setDescription($description);
            }
            $result = $this->statementRepository->save($statement);

            // Cria o statement

            $connectionManager->commitTransaction();

            return $result->getIdStatement();
        } catch (Exception $ex) {
            $connectionManager->rollbackTransaction();

            throw $ex;
        }
    }

    /**
     * Obtém todas as transações que estão bloqueadas
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
     * Obtém todas as transações que estão bloqueadas
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
