<?php

namespace ImEmil\Container\Contracts;

interface ContainerInterface
{
    /**
     * Invoke a callable via the container.
     *
     * @param  callable|string $callable
     * @param  array           $args
     * @return mixed
     */
    public function call($callable, array $args = []);

    /**
     * Description
     * 
     * @param  type $abstract 
     * @return type
     */
    public function bound($abstract);

    /**
     * Description
     * 
     * @param  type $abstract 
     * @param  type $alias 
     * @param  \Closure|null $callback 
     * @return type
     */
    public function alias($abstract, $alias, \Closure $callback = null);

    /**
     * Description
     * 
     * @param  type $name 
     * @return type
     */
    public function getAlias($name);

    /**
     * Description
     * 
     * @param  type $name 
     * @return type
     */
    public function isAlias($name);

    /**
     * Description
     * 
     * @param  type $abstract 
     * @param  type $instance 
     * @return type
     */
    public function instance($abstract, $instance);


    /**
     * Register a binding
     * 
     * @param  string $interface 
     * @param  string $abstract 
     * @return mixed
     */
    public function bind($interface, $abstract);

    /**
     * Description
     * 
     * @param  type $abstract 
     * @param  type|array $args 
     * @param  array|array $share 
     * @return type
     */
    public function make($abstract, $args = [], array $share = []);

    /**
     * Description
     * 
     * @param  type $abstract 
     * @param  \Closure|null $callback 
     * @return type
     */
    public function singleton($abstract, \Closure $callback = null);
}