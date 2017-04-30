<?php

namespace ImEmil\Container;

use ReflectionParameter;
use ReflectionFunctionAbstract;
use ImEmil\Container\Contracts\ContainerInterface;
use ImEmil\Container\Exception\NotFoundException;

class MethodCaller
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Invoke a callable via the container.
     *
     * @param  callable|string $callable
     * @param  array           $args
     * @return mixed
     */
    public function call($callable, array $args = [])
    {
        if($this->isCallableWith($callable, '::'))
            $callable = explode('::', $callable, 2);
        
        if($this->isCallableWith($callable, '@'))
            $callable = explode('@', $callable, 2);

        return $this->runReflector($callable, $args);
    }

    /**
     * Check if the callable is divided with given delimiter (e.g: class@method)
     * 
     * @param  callable|string $callable 
     * @param  string          $delimiter 
     * @return bool
     */
    public function isCallableWith($callable, $delimiter)
    {
        return is_string($callable) && strpos($callable, $delimiter) !== false;
    }

    /**
     * Invoke the given callable with arguments
     * 
     * @param  callable $callable 
     * @param  array    $args 
     * @return mixed
     */
    protected function runReflector(callable $callable, $args)
    {
        $reflectArgs = function($reflection) use($args) {
            return $this->reflectMethodDependency($reflection, $args);
        };

        if(is_array($callable))
        {
            if(is_string($callable[0]))
                $callable[0] = $this->container->make($callable[0]);

            $reflection = new \ReflectionMethod($callable[0], $callable[1]);

            if($reflection->isStatic())
                $callable[0] = null;

            return $reflection->invokeArgs($callable[0], $reflectArgs($reflection));
        }

        if(is_object($callable))
        {
            $reflection = new \ReflectionMethod($callable, '__invoke');

            return $reflection->invokeArgs($callable, $reflectArgs($reflection));
        }

        $reflection = new \ReflectionFunction($callable);

        return $reflection->invokeArgs($reflectArgs($reflection));
    }

    /**
     * Description
     * 
     * @param  callable $callable 
     * @param  array $args 
     * @return mixed
     */
    private function getReflection($callable, $args)
    {
        //
    }

    /**
     * Reflect all the parameters for the given method
     * 
     * @param  \ReflectionFunctionAbstract $method 
     * @param  array|array                 $args 
     * @return array
     */
    protected function reflectMethodDependency(ReflectionFunctionAbstract $method, array $args = [])
    {
        $args = $this->normalizeArgs($args);

        $dependencies = [];

        foreach($method->getParameters() as $param)
        {
            $this->resolveDependencies($param, $args, $dependencies);
        }

        return array_merge($dependencies, $args);
    }

    /**
     * Resolve all the required dependencies for the given method
     * 
     * @param  \ReflectionParameter $param 
     * @param  array|array          &$args 
     * @param  array                &$dependencies 
     * @return void
     */
    protected function resolveDependencies(ReflectionParameter $param, array &$args = [], &$dependencies)
    {
        $name = $param->name;

        if(array_key_exists($name, $args))
        {
            $dependencies[] = $args[$name];
            unset($args[$name]);
        }
        elseif($class = $param->getClass())
        {
            $dependencies[] = $this->container->make($class->name);
        }
        elseif($param->isDefaultValueAvailable())
        {
            $dependencies[] = $param->getDefaultValue();
        }
    }

    /**
     * Normalize the method arguments
     * 
     * @param  array $args 
     * @return array
     */
    protected function normalizeArgs(array $args)
    {
        foreach($args as $arg => $value)
        {
            if(strpos($arg, '$') === false)
                continue;

            unset($args[$arg]);

            $args[str_replace('$', '', $arg)] = $value;
        }

        return $args;
    }
}
