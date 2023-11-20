<?php
declare(strict_types=1);

namespace Xhtkyy\GrpcClient\Services;

use Hyperf\LoadBalancer\Exception\NoNodesAvailableException;
use Hyperf\LoadBalancer\LoadBalancerInterface;
use Hyperf\LoadBalancer\Node;
use Hyperf\ServiceGovernance\DriverManager;

/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */
class Service
{
    private LoadBalancerInterface $loadBalancer;

    private DriverManager $driverManager;

    private bool $isSubscribe = false;

    public function __construct(protected \Hyperf\Contract\ContainerInterface $container, public string $serviceName, public string $algorithm = 'random', public string $driver = 'nacos-grpc')
    {
        // set balancer
        $this->loadBalancer = $this->container->get(\Hyperf\LoadBalancer\LoadBalancerManager::class)->getInstance($this->serviceName, $this->algorithm);
        // get nodes
        $this->driverManager = $this->container->get(DriverManager::class);
        // subscribe
        $this->checkSubscribe();
    }

    /**
     * @return array<Node>
     */
    public function get(): array
    {
        $this->checkSubscribe();
        return $this->loadBalancer->getNodes();
    }

    /**
     * @return Node|null
     */
    public function select(): ?Node
    {
        $this->checkSubscribe();
        return $this->loadBalancer->select();
    }

    private function checkSubscribe(): void
    {
        if (!$this->isSubscribe) {
            $nodes = $this->driverManager->get($this->driver)->getNodes('', $this->serviceName, ['protocol' => 'grpc']); //todo 暂时只支持 nacos
            $this->set($nodes);
            if (!empty($nodes) && $this->driver !== 'nacos-grpc' && !$this->loadBalancer->isAutoRefresh()) {
                // set refresh
                $this->loadBalancer->refresh(fn() => array_map(
                    fn($node) => new Node($node['host'], $node['port'], $node['weight'] ?? 1),
                    $this->driverManager->get($this->driver)->getNodes('', $this->serviceName, ['protocol' => 'grpc'])
                ));
                $this->isSubscribe = true;
            }
        }
    }

    /**
     * @param array $nodes
     * @param bool $isSubscribe
     * @return LoadBalancerInterface
     */
    public function set(array $nodes, bool $isSubscribe = false): LoadBalancerInterface
    {
        $this->isSubscribe = $isSubscribe;
        $tmp = [];
        foreach ($nodes as $node) {
            if (!empty($node['host']) && !empty($node['port'])) {
                $tmp[] = new Node($node['host'], $node['port'], $node['weight'] ?? 1);
            }
        }
        return $this->loadBalancer->setNodes($tmp);
    }
}