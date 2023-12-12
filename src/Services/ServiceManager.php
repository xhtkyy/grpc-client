<?php
declare(strict_types=1);

namespace Xhtkyy\GrpcClient\Services;

/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */
class ServiceManager
{
    /**
     * @var array<Service>
     */
    private array $services = [];

    protected \Hyperf\Contract\ConfigInterface $config;

    public function __construct(protected \Hyperf\Contract\ContainerInterface $container)
    {
        $this->config = $this->container->get(\Hyperf\Contract\ConfigInterface::class);
    }

    public function get($serviceName): Service
    {
        if (!isset($this->services[$serviceName]) || !$this->services[$serviceName]) {
            $this->services[$serviceName] = new Service($this->container, $serviceName, $this->config->get('grpc.register.algorithm', 'random'), $this->config->get('grpc.register.driver', 'nacos-grpc'));
        }
        return $this->services[$serviceName];
    }

    public function remove($serviceName): void
    {
        if(isset($this->services[$serviceName])) {
            unset($this->services[$serviceName]);
        }
    }
}