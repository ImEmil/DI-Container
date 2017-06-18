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
use ImEmil\Container\ServiceProvider\ServiceProviderAggregate;
use ImEmil\Container\ServiceProvider\ServiceProviderAggregateInterface;

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

    /**
     * The service provider manager
     * 
     * @var \ImEmil\Container\ServiceProvider\ServiceProviderAggregateInterface
     */
    protected $providers;

    /**
     * All of the global resolving callbacks
     *
     * @var array
     */
    protected $globalResolvingCallbacks = [];

    /**
     * All of the global after resolving callbacks
     *
     * @var array
     */
    protected $globalAfterResolvingCallbacks = [];

    /**
     * All of the resolving callbacks by class type
     *
     * @var array
     */
    protected $resolvingCallbacks = [];

    /**
     * All of the after resolving callbacks by class type.
     *
     * @var array
     */
    protected $afterResolvingCallbacks = [];

    public function __construct(array $aliases = [])
    {
        $this->registerDefaultAliases($aliases);

        $this->providers = (new ServiceProviderAggregate)->setContainer($this);
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

    /**
     * Description
     * 
     * @param  string        $abstract 
     * @param  \Closure|null $rules 
     * @return mixed
     */
    public function lazyLoad($abstract, Closure $rules = null)
    {
        $abstract = $this->getAlias($abstract);

        $this->setLazyLoader(function() { // set the lazy loader if not set
            return new LazyLoader($this);
        });

        return $this->getLazyLoader()->getProxy($abstract, $rules);
    }

    /**
     * {@inheritdoc}
     */
    public function addServiceProvider($provider)
    {
        $this->providers->add($provider);

        return $this;
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
     * 
     * @throws \LogicException
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
     * 
     * @throws \LogicException
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
     * @param  string $binding 
     * @param  string $abstract 
     * @return mixed
     */
    public function bind($binding, $abstract)
    {
        $abstract = $this->getAlias($abstract);

        if($this->has($abstract))
        {
            $this->instances[$binding] = $this->instances[$abstract];
        }
        else
        {
            $this->instances[$binding] = $abstract; // _?_?_?
        }

        return $this;
    }

    /**
     * Resolve all dependencies for the given type
     * 
     * @param  string             $abstract 
     * @param \Closure|null|array $args 
     * @param  array|array        $share 
     * @return mixed
     * 
     * @throws \NotFoundException
     */
    public function make($abstract, $args = [], array $share = [])
    {
        $abstract = $this->getAlias($abstract);

        if($this->providers->provides($abstract))
        {
            $this->providers->register($abstract);
        }

        if($this->has($abstract)) // do we have a shared instance?
            return $this->instances[$abstract];

        try
        {

            if($args instanceof Closure)
            {
                $this->addRule($abstract, $args(new ContainerRulesSet)->getRules());
                $args = [];
            }

            $object = parent::create($abstract, $args, $share);

            $this->fireResolvingCallbacks($abstract, $object);

            return $object;
        }
        catch(ReflectionException $e)
        {
            throw new NotFoundException($e);
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
        $abstract = $this->getAlias($abstract);

        if($this->has($abstract))
            return $this->instances[$abstract];

        $this->addRule($abstract, ['shared' => true]); // shared = return the same instace, => singleton

        return $this->instances[$abstract] = $callback($this);
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
     * 
     * @throws \BadMethodCallException
     */
    public function offsetSet($key, $value)
    {
        //$this->bind($key, $value);
        throw new \BadMethodCallException('Method: ' . __METHOD__ . ' currently disabled');
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

    /*## My experimental methods here lol ##*/

    /**
     * @see https://r.je/dice.html#example3-1
     * 
     * @param  string        $class 
     * @param  string        $needs 
     * @param  string|object $implementation 
     * @return $this
     */
    public function substitute($class, $needs, $implementation)
    {
        // this is basically almost like the 'bind' method, but here you have more flexibility.
        // Bind only allows you to bind interfaces.

        if(is_object($implementation))
        {
            $rule = [
                'substitutions' => [$needs => $implementation]
            ];
        }
        else
        {
            $rule = [
                'substitutions' => [$needs => ['instance' => $implementation]]
            ];
        }

        $abstract = ($class == '*') // wildcard = apply the rule to all existing rules
            ? '*'
            : $this->getAlias($class);

        $this->addRule($abstract, $rule);

        return $this;
    }

    /**
     * Register a new resolving callback.
     *
     * @param  string    $abstract
     * @param  \Closure|null  $callback
     * @return void
     * 
     * @see https://github.com/illuminate/container
     */
    public function resolving($abstract, Closure $callback = null)
    {
        if(is_string($abstract))
        {
            $abstract = $this->getAlias($abstract);
        }

        if(is_null($callback) && $abstract instanceof Closure)
        {
            $this->globalResolvingCallbacks[] = $abstract;
        }
        else
        {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new after resolving callback for all types.
     *
     * @param  string   $abstract
     * @param  \Closure|null $callback
     * @return void
     * 
     * @see https://github.com/illuminate/container
     */
    public function afterResolving($abstract, Closure $callback = null)
    {
        if(is_string($abstract))
        {
            $abstract = $this->getAlias($abstract);
        }

        if($abstract instanceof Closure && is_null($callback))
        {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        }
        else
        {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Fire all of the resolving callbacks.
     *
     * @param  string  $abstract
     * @param  mixed   $object
     * @return void
     * 
     * @see https://github.com/illuminate/container
     */
    protected function fireResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
        );

        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    /**
     * Fire all of the after resolving callbacks.
     *
     * @param  string  $abstract
     * @param  mixed   $object
     * @return void
     * 
     * @see https://github.com/illuminate/container
     */
    protected function fireAfterResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }

    /**
     * Get all callbacks for a given type.
     *
     * @param  string  $abstract
     * @param  object  $object
     * @param  array   $callbacksPerType
     *
     * @return array
     * 
     * @see https://github.com/illuminate/container
     */
    protected function getCallbacksForType($abstract, $object, array $callbacksPerType)
    {
        $results = [];

        foreach($callbacksPerType as $type => $callbacks)
        {
            if($type === $abstract || $object instanceof $type)
            {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    /**
     * Fire an array of callbacks with an object.
     *
     * @param  mixed  $object
     * @param  array  $callbacks
     * @return void
     * 
     * @see https://github.com/illuminate/container
     */
    protected function fireCallbackArray($object, array $callbacks)
    {
        foreach($callbacks as $callback)
        {
            $callback($object, $this);
        }
    }
}
