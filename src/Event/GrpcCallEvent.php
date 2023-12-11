<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Xhtkyy\GrpcClient\Event;

class GrpcCallEvent
{
    public function __construct(
        public int $code,
        public string $path,
        public string $error,
        public float $at,
    )
    {
        if($this->at < 0) {
            $this->at = 0.0;
        }
    }
}