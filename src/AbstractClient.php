<?php
declare(strict_types=1);

namespace Xhtkyy\GrpcClient;

use Hyperf\Context\Context;
use OpenTracing\Span;
use OpenTracing\Tracer;
use const OpenTracing\Formats\TEXT_MAP;

/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 * @method array _simpleRequest(string $method, \Google\Protobuf\Internal\Message $argument, $deserialize, array $metadata = [], array $options = [])
 * @method \Hyperf\GrpcClient\ClientStreamingCall _clientStreamRequest(string $method, $deserialize, array $metadata = [], array $options = [])
 * @method \Hyperf\GrpcClient\ServerStreamingCall _serverStreamRequest(string $method, $deserialize, array $metadata = [], array $options = [])
 * @method \Hyperf\GrpcClient\BidiStreamingCall  _bidiRequest(string $method, $deserialize, array $metadata = [], array $options = [])
 */
class AbstractClient
{
    public function __construct(
        protected ClientManager $clientManager
    )
    {
    }

    public function __call(string $name, array $arguments)
    {
        match ($name) {
            '_simpleRequest' => [$method, , , &$metadata] = $arguments,
            default => [$method, , &$metadata] = $arguments
        };
        // with trace
        $metadata = $this->withTrace($metadata);
        // get grpc client
        $client = $this->clientManager->get($method);
        return $client->{$name}(...$arguments);
    }

    private function withTrace(array $metadata): array
    {
        /**
         * @var Span $root
         */
        if ('' != ($root = Context::get('tracer.root'))) {
            $carrier = [];
            // Injects the context into the wire
            \Hyperf\Context\ApplicationContext::getContainer()
                ->get(Tracer::class)
                ->inject(
                    $root->getContext(),
                    TEXT_MAP,
                    $carrier
                );
            $metadata["tracer.root"] = json_encode($carrier);
        }
        return $metadata;
    }
}