<?php

namespace ByJG\AccountStatements\Rest;

use ByJG\AccountStatements\Bll\StatementBLL;
use ByJG\AccountStatements\Entity\RollbackEntity;
use ByJG\RestServer\Exception\Error400Exception;
use ByJG\Serializer\BinderObject;
use stdClass;

class Statement
{

    /**
     * Obtém um Statement por ID.Se o ID não for passado, então devolve todos os Statements.
     * @SWG\Get(
     *     path="/statement/{id}",
     *     produces={"application/json"},
     *     tags={"statement"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="O Id do statement a ser obtido",
     *         required=true,
     *         type="integer",
     *         format="int64",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O objeto Account Type obtido",
     *         @SWG\Schema(ref="#/definitions/StatementEntity")
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Qualquer Erro ao obter a resposta",
     *         @SWG\Schema(ref="#/definitions/error")
     *     )
     * )
     *
     * @param \ByJG\RestServer\HttpResponse $response
     * @param \ByJG\RestServer\HttpRequest $request
     * @return void
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($response, $request)
    {
        $idStatement = $request->get('id');

        $bll = new StatementBLL();
        $object = $bll->getById($idStatement);

        if (!is_null($object)) {
            $response->write($object);
        }
    }

    /**
     * Adiciona fundos a uma conta
     * @SWG\Post(
     *     path="/statement/addfunds",
     *     produces={"application/json"},
     *     tags={"statement"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="data",
     *         description="O valor a ser atualizado",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="idaccount", type="integer"),
     *             @SWG\Property(property="amount", type="number"),
     *             @SWG\Property(property="reference", type="string"),
     *             @SWG\Property(property="description", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O Id Statement atualizado",
     *         @SWG\Schema(
     *             @SWG\Property(property="idstatement", type="integer"),
     *             @SWG\Property(property="rollback", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Qualquer Erro ao obter a resposta",
     *         @SWG\Schema(ref="#/definitions/error")
     *     )
     * )
     *
     * @param \ByJG\RestServer\HttpResponse $response
     * @param \ByJG\RestServer\HttpRequest $request
     * @return void
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\TransactionException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function postAddFunds($response, $request)
    {
        $postData = $this->getPayload($request);

        $data = json_decode($postData);

        $bll = new StatementBLL();
        $result = $bll->addFunds($data->idaccount, $data->amount, $data->description, $data->reference);

        $data->description = "Add funds rollback from $result";
        $rollback = new RollbackEntity(
            get_class(),
            'postWithdrawFunds',
            BinderObject::toArrayFrom($data)
        );

        $response->write(["idstatement" => $result, "rollback" => $rollback->encode()]);
    }

    /**
     * Retira (saca) fundos de uma conta
     * @SWG\Post(
     *     path="/statement/withdrawfunds",
     *     produces={"application/json"},
     *     tags={"statement"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="data",
     *         description="o valor a ser inserido atualizado",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="idaccount", type="integer"),
     *             @SWG\Property(property="amount", type="number"),
     *             @SWG\Property(property="reference", type="string"),
     *             @SWG\Property(property="description", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O Id Statement atualizado",
     *         @SWG\Schema(
     *             @SWG\Property(property="idstatement", type="integer"),
     *             @SWG\Property(property="rollback", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Qualquer Erro ao obter a resposta",
     *         @SWG\Schema(ref="#/definitions/error")
     *     )
     * )
     *
     * @param \ByJG\RestServer\HttpResponse $response
     * @param \ByJG\RestServer\HttpRequest $request
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\TransactionException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function postWithdrawFunds($response, $request)
    {
        $postData = $this->getPayload($request);

        $data = json_decode($postData);

        $bll = new StatementBLL();
        $result = $bll->withdrawFunds($data->idaccount, $data->amount, $data->description, $data->reference);

        $data->description = "Withdraw rollback from $result";
        $rollback = new RollbackEntity(
            get_class(),
            'postAddFunds',
            BinderObject::toArrayFrom($data)
        );

        $response->write(["idstatement" => $result, "rollback" => $rollback->encode()]);
    }

    /**
     * Reserva fundos para retirada (saque)
     * @SWG\Post(
     *     path="/statement/reservefundsforwithdraw",
     *     produces={"application/json"},
     *     tags={"statement"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="data",
     *         description="o valor a ser inserido atualizado",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="idaccount", type="integer"),
     *             @SWG\Property(property="amount", type="number"),
     *             @SWG\Property(property="reference", type="string"),
     *             @SWG\Property(property="description", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O Id Statement atualizado",
     *         @SWG\Schema(
     *             @SWG\Property(property="idstatement", type="integer"),
     *             @SWG\Property(property="rollback", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Qualquer Erro ao obter a resposta",
     *         @SWG\Schema(ref="#/definitions/error")
     *     )
     * )
     *
     * @param \ByJG\RestServer\HttpResponse $response
     * @param \ByJG\RestServer\HttpRequest $request
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\TransactionException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function postReserveFundsForWithdraw($response, $request)
    {
        $postData = $this->getPayload($request);

        $data = json_decode($postData);

        $bll = new StatementBLL();
        $result = $bll->reserveFundsForWithdraw($data->idaccount, $data->amount, $data->description, $data->reference);

        $rollback = new RollbackEntity(
            get_class(),
            'postRejectFunds',
            [
                'id'          => $result,
                'description' => "Reserve funds rollback from $result",
            ]
        );

        $response->write(["idstatement" => $result, "rollback" => $rollback->encode()]);
    }

    /**
     * Aceita um fundo previamente reservado e efetua o saque.
     * @SWG\Post(
     *     path="/statement/acceptfunds/{id}",
     *     produces={"application/json"},
     *     tags={"statement"},
     *     @SWG\Parameter(
     *         in="path",
     *         name="id",
     *         description="O Id statement a ser aprovado",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O Id Statement atualizado",
     *         @SWG\Schema(
     *             @SWG\Property(property="idstatement", type="integer"),
     *             @SWG\Property(property="rollback", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Qualquer Erro ao obter a resposta",
     *         @SWG\Schema(ref="#/definitions/error")
     *     )
     * )
     *
     * @param \ByJG\RestServer\HttpResponse $response
     * @param \ByJG\RestServer\HttpRequest $request
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\MicroOrm\Exception\TransactionException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function postAcceptFunds($response, $request)
    {
        $idStatement = $request->get('id');

        $postData = $request->payload();

        if (!empty($postData)) {
            $data = json_decode($postData);
        } else {
            $data = new stdClass;
        }
        $bll = new StatementBLL();
        $result = $bll->acceptFundsById($idStatement, isset($data->description) ? $data->description : null);

        $statement = $bll->getById($idStatement);

        $rollback = new RollbackEntity(
            get_class(),
            'postReserveFundsForWithdraw',
            [
                'idaccount'   => $statement->getIdAccount(),
                'amount'      => $statement->getAmount(),
                'description' => "Accept funds rollback from $result",
                'reference'   => $statement->getReference(),
            ]
        );

        $response->write(["idstatement" => $result, "rollback" => $rollback->encode()]);
    }

    /**
     * Rejeita um fundo previamente reservado e retorna o valor à conta.
     * @SWG\Post(
     *     path="/statement/rejectfunds/{id}",
     *     produces={"application/json"},
     *     tags={"statement"},
     *     @SWG\Parameter(
     *         in="path",
     *         name="id",
     *         description="O Id statement a ser rejeitado",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O Id Statement atualizado",
     *         @SWG\Schema(
     *             @SWG\Property(property="idstatement", type="integer"),
     *             @SWG\Property(property="rollback", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Qualquer Erro ao obter a resposta",
     *         @SWG\Schema(ref="#/definitions/error")
     *     )
     * )
     *
     * @param \ByJG\RestServer\HttpResponse $response
     * @param \ByJG\RestServer\HttpRequest $request
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\MicroOrm\Exception\TransactionException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function postRejectFunds($response, $request)
    {
        $idStatement = $request->get('id');

        $postData = $this->getPayload($request);

        if (!empty($postData)) {
            $data = json_decode($postData);
        } else {
            $data = new stdClass;
        }

        if (empty($idStatement) && isset($data->id)) {
            $idStatement = $data->id;
        }

        $bll = new StatementBLL();
        $result = $bll->rejectFundsById($idStatement, isset($data->description) ? $data->description : null);

        $statement = $bll->getById($idStatement);

        $rollback = new RollbackEntity(
            get_class(),
            'postReserveFundsForWithdraw',
            [
                'idaccount'   => $statement->getIdAccount(),
                'amount'      => $statement->getAmount(),
                'description' => "Reject funds rollback from $result",
                'reference'   => $statement->getReference(),
            ]
        );

        $response->write(["idstatement" => $result, "rollback" => $rollback->encode()]);
    }

    /**
     * Obtem todos os statements que estão reservados em um determinado account
     * @SWG\Get(
     *     path="/statement/unclearedstatements/{id}",
     *     produces={"application/json"},
     *     tags={"statement"},
     *     @SWG\Parameter(
     *         in="path",
     *         name="id",
     *         description="O Id Account a ser verificado",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O Id Statement atualizado",
     *         @SWG\Schema(
     *             type="array", @SWG\Items(ref="#/definitions/StatementEntity")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Qualquer Erro ao obter a resposta",
     *         @SWG\Schema(ref="#/definitions/error")
     *     )
     * )
     *
     * @param \ByJG\RestServer\HttpResponse $response
     * @param \ByJG\RestServer\HttpRequest $request
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getUnclearedStatements($response, $request)
    {
        $idAccount = $request->get('id');

        $bll = new StatementBLL();
        $result = $bll->getUnclearedStatements($idAccount);

        $response->write($result);
    }

    /**
     * Retorna true se o statement ainda está reservado, ou falso caso ja tenha sido aceito ou rejeitado
     * @SWG\Get(
     *     path="/statement/isstatementuncleared/{id}",
     *     produces={"application/json"},
     *     tags={"statement"},
     *     @SWG\Parameter(
     *         in="path",
     *         name="id",
     *         description="O Id Statement a ser verificado",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Retorna true ou false",
     *         @SWG\Schema(
     *             @SWG\Property(property="result", type="bool")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Qualquer Erro ao obter a resposta",
     *         @SWG\Schema(ref="#/definitions/error")
     *     )
     * )
     *
     * @param \ByJG\RestServer\HttpResponse $response
     * @param \ByJG\RestServer\HttpRequest $request
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getIsStatementUncleared($response, $request)
    {
        $idStatement = $request->get('id');

        $bll = new StatementBLL();
        $result = $bll->isStatementUncleared($idStatement);

        $response->write(["result" => $result]);
    }

    /**
     * @param \ByJG\RestServer\HttpRequest $request
     * @return string
     */
    protected function getPayload($request)
    {
        if (empty($this->rollbackInfo)) {
            return $request->payload();
        } else {
            return json_encode($this->rollbackInfo);
        }
    }

    protected $rollbackInfo = null;

    /**
     * Algumas operações permitem que seja feito o ROLLBACK após a transação ser executada.
     * Nesses casos, um parâmetro adicional "rollback" é retornado.
     * Atenção: A operação de rollback aqui descrita é apenas uma reversão da operação executada.
     * Sendo assim, é possível que não possa ser desfeita caso demore muito tempo para ser executada.
     * @SWG\Post(
     *     path="/statement/rollback",
     *     produces={"application/json"},
     *     tags={"statement","rollback"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="data",
     *         description="o valor a ser inserido atualizado",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="idaccount", type="integer"),
     *             @SWG\Property(property="amount", type="number"),
     *             @SWG\Property(property="reference", type="string"),
     *             @SWG\Property(property="description", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O Id Statement atualizado",
     *         @SWG\Schema(
     *             @SWG\Property(property="idstatement", type="integer"),
     *             @SWG\Property(property="rollback", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Qualquer Erro ao obter a resposta",
     *         @SWG\Schema(ref="#/definitions/error")
     *     )
     * )
     *
     * @param \ByJG\RestServer\HttpResponse $response
     * @param \ByJG\RestServer\HttpRequest $request
     * @throws \ByJG\RestServer\Exception\Error400Exception
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function postRollback($response, $request)
    {
        $postData = $request->payload();

        $rollback = new RollbackEntity();
        $rollback->decode($postData);

        if ($rollback->getClass() !== get_class()) {
            throw new Error400Exception('A informação de rollback é inválida - classe');
        }

        if (!method_exists($this, $rollback->getMethod())) {
            throw new Error400Exception('A informação de rollback é inválida - método');
        }

        $this->rollbackInfo = $rollback->getArgs();

        $method = $rollback->getMethod();

        $this->$method();
    }
}
