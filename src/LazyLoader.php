<?php

namespace ImEmil\Container;

use ImEmil\Container\Contracts\ContainerInterface;
use ImEmil\Container\Contracts\LazyLoaderInterface;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * Runtime lazy loading proxy generator.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 */
class LazyLoader implements LazyLoaderInterface
{
    /**
     * The container instance
     * 
     * @var \ImEmil\Container\Contracts\ContainerInterface
     */
    protected $container;

    /**
     * The factory instance
     * 
     * @var \ProxyManager\Factory\LazyLoadingValueHolderFactory
     */
    protected $factory;

    public function __construct(ContainerInterface $container)
    {
        $this->factory   = new LazyLoadingValueHolderFactory();
        $this->container = $container;
    }

    /**
     * Return a proxy from the factory (? wat D:)
     * 
     * @param  string        $class 
     * @param  \Closure|null $rules 
     * @return mixed
     */
    public function getProxy($class, $rules)
    {
        $rules = is_null($rules)
            ? []
            : $rules;

        return $this->factory->createProxy($class, function(&$wrappedObject, LazyLoadingInterface $proxy) use($class, $rules)
        {
            $wrappedObject = $this->container->make($class, $rules);

            $proxy->setProxyInitializer(null);

            return true; // confirm that initialization occurred correctly
        });
    }
}
