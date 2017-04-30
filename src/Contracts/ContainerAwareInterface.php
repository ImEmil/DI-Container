<?php

namespace ImEmil\Container\Contracts;

use ImEmil\Container\Contracts\ContainerInterface;

interface ContainerAwareInterface
{
    /**
     * Set a container instance
     *
     * @param \ImEmil\Container\ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container);

    /**
     * Get the container instance
     *
     * @return \ImEmil\Container\Contracts\ContainerInterface
     */
    public function getContainer();
}