<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Xhtkyy\GrpcClient\Reply;

class ErrorReply
{
    protected int $errCode = -1;
    protected string $errMsg = "fail";

    public function __construct(?string $reply)
    {
        if (is_string($reply)) {
            [$errCode, $errMsg] = str_contains($reply, "#") ? explode("#", $reply) : [-1, $reply];
            $this->errCode = intval($errCode);
            $this->errMsg = (string)$errMsg;
        }
    }

    public function getCode(): int
    {
        return $this->errCode;
    }

    public function getMessage(): string
    {
        return $this->errMsg;
    }


    public function __toString(): string
    {
        return "{$this->errMsg}";
    }
}