<?php
declare(strict_types=1);

namespace Xhtkyy\GrpcClient;

/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 * @method array _simpleRequest(string $method, \Google\Protobuf\Internal\Message $argument, $deserialize, array $metadata = [], array $options = [])
 * @method \Hyperf\GrpcClient\ClientStreamingCall _clientStreamRequest(string $method, $deserialize, array $metadata = [], array $options = [])
 * @method \Hyperf\GrpcClient\ServerStreamingCall _serverStreamRequest(string $method, $deserialize, array $metadata = [], array $options = [])
 * @method \Hyperf\GrpcClient\BidiStreamingCall  _bidiRequest(string $method, $deserialize, array $metadata = [], array $options = [])
 */
class Client extends \Hyperf\GrpcClient\BaseClient
{
    public function __construct(private string $hostname, private array $options = [])
    {
        parent::__construct($this->hostname, $this->options);
    }

    public function __call($name, $arguments)
    {
        return match ($name) {
            '_simpleRequest' => $this->_simpleRequest(...$arguments),
            '_clientStreamRequest' => $this->_clientStreamRequest(...$arguments),
            '_serverStreamRequest' => $this->_serverStreamRequest(...$arguments),
            '_bidiRequest' => $this->_bidiRequest(...$arguments),
            default => $this->_getGrpcClient()->{$name}(...$arguments)
        };
    }

    public function getHostName(): string
    {
        return $this->hostname;
    }
}