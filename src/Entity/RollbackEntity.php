<?php

namespace ByJG\AccountStatements\Entity;

use ByJG\Serializer\BaseModel;
use ByJG\Serializer\Exception\InvalidArgumentException;

/**
 * @OA\Definition(
 *   description="Rollback",
 * )
 *
 * @object:NodeName account
 */
class RollbackEntity extends BaseModel
{

    /**
     * @var string
     * @OA\Property()
     */
    protected $class;

    /**
     * @var string
     * @OA\Property()
     */
    protected $method;

    /**
     * @var string
     * @OA\Property()
     */
    protected $args = [];

    public function __construct($class = null, $method = null, $args = [])
    {
        $this->class = $class;
        $this->method = $method;
        $this->args = $args;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function setClass($class)
    {
        $this->class = $class;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setArgs($args)
    {
        $this->args = $args;
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public function encode()
    {
        return base64_encode(json_encode($this->toArray()));
    }

    /**
     * @param $code
     * @throws InvalidArgumentException
     */
    public function decode($code)
    {
        $data = json_decode(base64_decode($code));
        $this->bind($data);
    }
}
