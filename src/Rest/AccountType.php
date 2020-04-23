<?php

namespace ByJG\AccountStatements\Rest;

use ByJG\AccountStatements\Bll\AccountTypeBLL;

class AccountType
{

    /**
     * Obtém um AccountType por ID. Se o ID não for passado, então devolve todos os AccountTypes.
     *
     * @SWG\Get(
     *     path="/accounttype/{id}",
     *     produces={"application/json"},
     *     tags={"accounttype"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="O Id do account type a ser obtido",
     *         required=true,
     *         type="string",
     *         format="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O objeto Account Type obtido",
     *         @SWG\Schema(ref="#/definitions/AccountTypeEntity")
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
        $idAccountType = $request->get('id');

        $bll = new AccountTypeBLL();
        $object = $bll->getById($idAccountType);

        if (!is_null($object)) {
            $response->write($object);
        }
    }

    /**
     * Salvar ou Atualizar um AccountType
     *
     * @SWG\Post(
     *     path="/accounttype",
     *     produces={"application/json"},
     *     tags={"accounttype"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="data",
     *         description="O Account Type a ser inserido ou atualizado",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/AccountTypeEntity")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="O Id Account Type atualizado",
     *         @SWG\Schema(
     *             @SWG\Property(property="idaccounttype", type="string")
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
     * @throws \ByJG\MicroOrm\Exception\OrmBeforeInvalidException
     * @throws \ByJG\MicroOrm\Exception\OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function post($response, $request)
    {
        $postData = $request->payload();

        $bll = new AccountTypeBLL();
        $result = $bll->update(json_decode($postData));

        $response->write(["idaccounttype" => $result]);
    }
}
