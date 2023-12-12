<?php
declare(strict_types=1);

namespace Xhtkyy\GrpcClient;

use Hyperf\Context\Context;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Grpc\StatusCode;
use Hyperf\GrpcClient\Exception\GrpcClientException;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Psr\EventDispatcher\EventDispatcherInterface;
use Xhtkyy\GrpcClient\Event\GrpcCallEvent;
use Xhtkyy\GrpcClient\Reply\ErrorReply;
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
        protected ClientManager            $clientManager,
        protected StdoutLoggerInterface    $logger,
        protected EventDispatcherInterface $dispatcher
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
        $metadata = $this->withTrace($metadata ?? []);
        // get grpc client
        $client = $this->clientManager->get($method);
        //
        try {
            if ($name == '_simpleRequest') {
                $startAt = microtime(true);
                $result = $client->{$name}(...$arguments);
                [$reply, $status, $response] = [$result[0] ?? '', $result[1] ?? 0, $result[2] ?? null];
                if ($status != StatusCode::OK) {
                    // handle reply
                    $reply = new ErrorReply($reply);
                    // log error
                    $this->logger->debug("[grpc]{$method} [status-code]{$status} [error]code:{$reply->getCode()} message:{$reply->getMessage()}");
                }
                // Dispatch gRPC Call
                $this->dispatcher->dispatch(new GrpcCallEvent($status, $method, $status != StatusCode::OK ? $reply->getMessage() : '', (float)microtime(true) - $startAt));
                // return
                return [$reply, $status, $response];
            } else {
                return $client->{$name}(...$arguments);
            }
        } catch (\Throwable $exception) {
            $this->logger->error("[grpc]{$method} [instance]{$client->getHostName()}  [error]{$exception->getMessage()}");
            if ($exception instanceof GrpcClientException) {
                if (str_contains($exception->getMessage(), 'error=Connection refused')) {
                    //connect fail remove instance
                    $this->clientManager->remove($method, $client->getHostName());
                }
            }
            return [new ErrorReply("-1#service fail"), StatusCode::ABORTED, null];
        }
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