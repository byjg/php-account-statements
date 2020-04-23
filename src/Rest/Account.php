<?php

namespace ByJG\AccountStatements\Rest;

use ByJG\AccountStatements\Bll\AccountBLL;

class Account
{

    /**
     * Obtém um Account por ID. Se o ID não for passado, então devolve todos os Accounts.
     *
     * @SWG\Get(
     *     path="/account/{id}",
     *     produces={"application/json"},
     *     tags={"account"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="O Id do account a ser obtido",
     *         required=true,
     *         type="integer",
     *         format="int64",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O objeto Id Account",
     *         @SWG\Schema(ref="#/definitions/AccountEntity")
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
        $idAccount = $request->get('id');

        $bll = new AccountBLL();
        $object = $bll->getById($idAccount);

        if (!is_null($object)) {
            $response->write($object);
        }
    }

    /**
     * Obtém um Account pelo UserID
     *
     * @SWG\Get(
     *     path="/account/userid/{id}",
     *     produces={"application/json"},
     *     tags={"account"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="O Id do user",
     *         required=true,
     *         type="integer",
     *         format="int64",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O objeto Id Account",
     *         @SWG\Schema(
     *             type="array", @SWG\Items(ref="#/definitions/AccountEntity")
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
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getUserId($response, $request)
    {
        $idUser = $request->get('id');

        $bll = new AccountBLL();
        $object = $bll->getUserId($idUser, $request->get('secondid'));

        if (!is_null($object)) {
            $response->write($object);
        }
    }

    /**
     * Obtém uma lista de Account pelo AccountType
     *
     * @SWG\Get(
     *     path="/account/type/{id}",
     *     produces={"application/json"},
     *     tags={"account"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="O Account Type ID",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O objeto Id Account",
     *         @SWG\Schema(
     *             type="array", @SWG\Items(ref="#/definitions/AccountEntity")
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
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getType($response, $request)
    {
        $idAccountType = $request->get('id');

        $bll = new AccountBLL();
        $object = $bll->getAccountTypeId($idAccountType);

        if (!is_null($object)) {
            $response->write($object);
        }
    }

    /**
     * Salvar ou Atualizar um Account
     * @SWG\Post(
     *     path="/account",
     *     produces={"application/json"},
     *     tags={"account"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="data",
     *         description="O Account a ser inserido ou atualizado",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="idaccounttype", type="string"),
     *             @SWG\Property(property="iduser", type="integer"),
     *             @SWG\Property(property="balance", type="number"),
     *             @SWG\Property(property="price", type="number"),
     *             @SWG\Property(property="minvalue", type="number"),
     *             @SWG\Property(property="extra", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O Id Account atualizado",
     *         @SWG\Schema(
     *             @SWG\Property(property="idaccount", type="integer")
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
     * @throws \ByJG\MicroOrm\Exception\OrmBeforeInvalidException
     * @throws \ByJG\MicroOrm\Exception\OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function post($response, $request)
    {
        $postData = $request->payload();

        $data = json_decode($postData);

        $bll = new AccountBLL();
        $result = $bll->createAccount(
            $data->idaccounttype,
            $data->iduser,
            $data->balance,
            $data->price,
            $data->minvalue,
            $data->extra
        );

        $response->write(["idaccount" => $result]);
    }

    /**
     * Sobrescreve o os valores de uma conta com novos valores
     * @SWG\Post(
     *     path="/account/overridebalance",
     *     produces={"application/json"},
     *     tags={"account"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="data",
     *         description="Os dados a serem atualizados",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="idaccount", type="integer"),
     *             @SWG\Property(property="balance", type="number"),
     *             @SWG\Property(property="price", type="number"),
     *             @SWG\Property(property="minvalue", type="number"),
     *             @SWG\Property(property="description", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Se a operação foi efetuada com sucesso",
     *         @SWG\Schema(
     *             @SWG\Property(property="result", type="boolean")
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function postOverrideBalance($response, $request)
    {
        $postData = $request->payload();

        $data = json_decode($postData);

        $bll = new AccountBLL();
        $result = $bll->overrideBalance(
            $data->idaccount,
            $data->balance,
            $data->price,
            $data->minvalue,
            $data->description
        );

        $response->write(["result" => $result]);
    }

    /**
     * Atualiza o saldo de uma conta
     * @SWG\Post(
     *     path="/account/partialbalance",
     *     produces={"application/json"},
     *     tags={"account"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="data",
     *         description="Os dados a serem atualizados",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="idaccount", type="integer"),
     *             @SWG\Property(property="balance", type="number"),
     *             @SWG\Property(property="description", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Se a operação foi efetuada com sucesso",
     *         @SWG\Schema(
     *             @SWG\Property(property="result", type="boolean")
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
    public function postPartialBalance($response, $request)
    {
        $postData = $request->payload();

        $data = json_decode($postData);

        $bll = new AccountBLL();
        $bll->partialBalance($data->idaccount, $data->balance, $data->description);

        $response->write(["result" => true]);
    }

    /**
     * Fecha uma conta zerando todos os valores
     * @SWG\Post(
     *     path="/account/closeaccount/{id}",
     *     produces={"application/json"},
     *     tags={"account"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="O Id do account a ser fechado",
     *         required=true,
     *         type="integer",
     *         format="int64",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Se a operação foi efetuada com sucesso",
     *         @SWG\Schema(
     *             @SWG\Property(property="result", type="boolean")
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function postCloseAccount($response, $request)
    {
        $idAccount = $request->get('id');

        $bll = new AccountBLL();
        $bll->closeAccount($idAccount);

        $response->write(["result" => true]);
    }
}
