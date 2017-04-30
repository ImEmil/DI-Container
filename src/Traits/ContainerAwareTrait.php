<?php

namespace ImEmil\Container\Traits;

trait ContainerAwareTrait
{
    /**
     * @var \ImEmil\Container\Contracts
     */
    protected $container;

    /**
     * Set the container instance
     *
     * @param  \ImEmil\Container\Contracts\ContainerInterface $container
     * @return $this
     */
    public function setContainer(\ImEmil\Container\Contracts\ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }
    /**
     * Get the container instance
     *
     * @return \ImEmil\Container\Contracts\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}
