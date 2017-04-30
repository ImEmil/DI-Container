<?php

namespace ImEmil\Container;

use Closure;
use LogicException;
use ArrayAccess;
use ReflectionException;
use ImEmil\Container\Dice;
use ImEmil\Container\LazyLoader;
use ImEmil\Container\MethodCaller;
use ImEmil\Container\ContainerRulesSet;
use ImEmil\Container\Exception\NotFoundException;
use ImEmil\Container\Traits\LazyLoaderAwareTrait;
use ImEmil\Container\Contracts\ContainerInterface;

class Container extends Dice implements ArrayAccess, ContainerInterface
{
    use LazyLoaderAwareTrait;

    /**
     * The global container instance
     * 
     * @var static
     */
    protected static $instance;

    /**
     * Registered class aliases
     * 
     * @var array
     */
    protected $aliases = [];

    /**
     * Register any default aliases
     * 
     * @var array
     */
    protected $defaultAliases = [
        //
    ];

    public function __construct(array $aliases = [])
    {
        $this->registerDefaultAliases($aliases);
    }

    /**
     * Magically retrieve an instance
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Magically set an instance
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

    /**
     * Invoke a callable via the container
     *
     * @param  callable|string $callable
     * @param  array           $args
     * @return mixed
     */
    public function call($callable, array $args = [])
    {
        return (new MethodCaller($this))->call($callable, $args);
    }

    public function lazyLoad($abstract, Closure $rules = null)
    {
        $abstract = $this->getAlias($abstract);

        $this->setLazyLoader(function() { // set the lazy loader if not set
            return new LazyLoader($this);
        });

        return $this->getLazyLoader()->getProxy($abstract, $rules);
    }

    /**
     * Get the current instance
     * 
     * @param  null|array $aliases 
     * @return static
     */
    public static function getInstance($aliases = [])
    {
        if(!isset(static::$instance))
            static::$instance = new static($aliases);

        return static::$instance;
    }

    /**
     * Set the container instance
     *
     * @param  \ImEmil\Container\Contracts\ContainerInterface $container
     * @return static
     */
    public static function setInstance(ContainerInterface $instance)
    {
        return static::$instance = $instance;
    }

    /**
     * Register default class aliases
     * 
     * @param  array $aliases 
     * @return void
     */
    public function registerDefaultAliases($aliases)
    {
        $aliases = array_merge($aliases, $this->defaultAliases);

        foreach($aliases as $abstract => $alias)
            $this->alias($abstract, $alias);
    }

    /**
     * Register a class alias
     * 
     * @param  string $abstract 
     * @param  string $alias 
     * @param  \Closure|null $callback for what?
     * @return void
     */
    public function alias($abstract, $alias, Closure $callback = null)
    {
        if($this->isAlias($alias))
            throw new LogicException("Alias ({$alias}) does already exist.");

        $this->aliases[$alias] = $abstract;
    }

    /**
     * Get the alias name (if available)
     * 
     * @param  string $name 
     * @return string
     */
    public function getAlias($name)
    {
        return $this->isAlias($name) ? $this->aliases[$name] : $name;
    }

    /**
     * Check if an alias already exists
     * 
     * @param  string $name 
     * @return bool
     */
    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Register aliases returned from given file
     * 
     * @param  string $file 
     * @return void
     */
    public function loadAliases($file)
    {
        $aliases = require $file;

        if(!is_array($aliases))
            throw new LogicException("Aliases must be a type of array.");

        $this->registerDefaultAliases($aliases);
    }

    /**
     * Register an existing instance as shared
     * 
     * @param  string $abstract 
     * @param  mixed  $instance 
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        return $this->instances[$this->getAlias($abstract)] = $instance;
    }

    /**
     * Register a binding
     * 
     * @param  string   $abstract 
     * @param  \Closure $callback 
     * @param  bool     $share 
     * @return mixed
     */
    public function bind($abstract, Closure $callback, $share = false)
    {
        $abstract = $this->getAlias($abstract);

        if($share && $this->has($abstract))
            return $this->instances[$abstract];

        if(!$share)
            return $callback($this);

        return $this->instances[$abstract] = $callback($this);
    }

    /**
     * Resolve all dependencies for the given type
     * 
     * @param  string             $abstract 
     * @param \Closure|null|array $args 
     * @param  array|array        $share 
     * @return mixed
     */
    public function make($abstract, $args = [], array $share = [])
    {
        $abstract = $this->getAlias($abstract);

        if($this->has($abstract)) // do we have a shared instance?
            return $this->instances[$abstract];

        try {

            if($args instanceof Closure)
            {
                $this->addRule($abstract, $args(new ContainerRulesSet)->getRules());
                $args = [];
            }

            return parent::create($abstract, $args, $share);
        }
        catch(ReflectionException $e) {
            throw $e;
            //todo: handle exception
        }
    }

    /**
     * Register a shared instance
     * 
     * @param  string       $abstract 
     * @param \Closure|null $callback 
     * @return mixed
     */
    public function singleton($abstract, Closure $callback = null)
    {
        $this->addRule($abstract = $this->getAlias($abstract), ['shared' => true]); // shared = return the same instace, => singleton

        return $this->bind($abstract, $callback, true);
    }

    /**
     * Check if an instance already exist
     * 
     * @param  string $abstract 
     * @return bool
     */
    protected function has($abstract)
    {
        return isset($this->instances[$abstract]);
    }

    /**
     * Flush all registered and resolved instances, rules and cache
     * 
     * @return void
     */
    public function flush()
    {
        $this->instances = [];
        $this->cache     = [];
        $this->rules     = [];
        $this->aliases   = [];
    }

    /**
     * Check if a given offset exists
     *
     * @param  string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->bound($key);
    }

    /**
     * Get the value at a given offset
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->bind($key, $value);
    }

    /**
     * Unset the value(s) at a given offset
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->instances[$key], $this->cache[$key], $this->aliases[$key], $this->rules[$key]);
    }

    /**
     * Check if the given abstract type has been registered already
     *
     * @param  string $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return $this->has($abstract) || $this->isAlias($abstract);
    }
}
