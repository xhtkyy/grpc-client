<?php
declare(strict_types=1);

namespace Xhtkyy\GrpcClient;

use Hyperf\Contract\ConfigInterface;
use Hyperf\LoadBalancer\Exception\NoNodesAvailableException;
use Xhtkyy\GrpcClient\Exception\ServiceNotFoundException;
use Xhtkyy\GrpcClient\Services\ServiceManager;

/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */
class ClientManager
{
    /**
     * @var array<Client>
     */
    private array $clients = [];

    private array $serviceProxy = [];

    private array $serviceAlias = [];

    private array $hostProxy = [];

    public function __construct(
        protected \Hyperf\Contract\StdoutLoggerInterface $logger,
        protected ServiceManager                         $serviceManager,
        protected ConfigInterface                        $config
    )
    {
        $this->serviceAlias = $this->config->get('grpc.discovery.service_alias', []);
        $this->hostProxy = $this->config->get('hosts', []);
    }

    public function get($method): Client
    {
        $hostname = null;
        $servicePath = current(explode('/', trim($method, '/')));
        if (isset($this->serviceProxy[$servicePath])) {
            $hostname = $this->getHostName($this->serviceProxy[$servicePath]);
        } else {
            $tmp = explode('.', $servicePath);
            for ($i = count($tmp); $i > 0; $i--) {
                if (isset($tmp[$i])) {
                    unset($tmp[$i]);
                }
                $serviceName = implode('.', $tmp) . '.grpc'; //todo config
                //rename
                isset($this->serviceAlias[$serviceName]) && $serviceName = $this->serviceAlias[$serviceName];
                $hostname = $this->getHostName($serviceName);
                if ($hostname) {
                    //存在就 更新进代理
                    $this->serviceProxy[$servicePath] = $serviceName;
                    break;
                }
            }
        }
        if (!$hostname) {
            throw new ServiceNotFoundException("[method] $method [service] $servicePath [error] can not get service instance!");
        }

        //get grpc client
        if (!isset($this->clients[$hostname]) || !$this->clients[$hostname]) {
            $this->clients[$hostname] = new Client($hostname);
        }
        return $this->clients[$hostname];
    }

    /**
     * @param string $serviceName
     * @return string|null
     */
    private function getHostName(string $serviceName): ?string
    {
        // find host proxy
        if (isset($this->hostProxy[$serviceName])) return $this->hostProxy[$serviceName];
        // remote
        $hostname = null;
        try {
            //get node
            $node = $this->serviceManager->get($serviceName)->select();
            $hostname = "{$node->host}:{$node->port}";
        } catch (NoNodesAvailableException) {
        }
        return $hostname;
    }
}