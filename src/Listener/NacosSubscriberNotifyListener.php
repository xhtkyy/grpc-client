<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Xhtkyy\GrpcClient\Listener;

use Crayxn\ServiceGovernanceNacosGrpc\Event\NacosSubscriberNotify;
use Crayxn\ServiceGovernanceNacosGrpc\Response\NotifySubscriberRequest;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Xhtkyy\GrpcClient\Services\ServiceManager;

#[Listener]
class NacosSubscriberNotifyListener implements ListenerInterface
{
    public function __construct(
        protected ServiceManager $serviceManager
    )
    {
    }

    public function listen(): array
    {
        return [
            NacosSubscriberNotify::class
        ];
    }


    public function process(object $event): void
    {
        $request = $event?->request;
        if ($request instanceof NotifySubscriberRequest) {
            $serviceName = $request->serviceInfo['name'] ?? '';
            $hosts = $request->serviceInfo['hosts'] ?? [];
            $nodes = [];
            foreach ($hosts as $host) {
                if (($host['healthy'] ?? 0) == 1 && ($host['enabled'] ?? 0) == 1) {
                    $nodes[] = [
                        'host' => $host['ip'],
                        'port' => $host['port'],
                        'weight' => intval(100 * ($host['weight'] ?? 1))
                    ];
                }
            }
            $this->serviceManager->get($serviceName)->set($nodes, true);
        }
    }
}