<?php

namespace ImEmil\Container\Traits;

trait LazyLoaderAwareTrait
{
    /**
     * @var \ImEmil\Container\Contracts\LazyLoaderInterface
     */
    protected $lazyLoader;

    /**
     * Set the lazy loader instance
     *
     * @param  \Closure $loader
     * @return $this
     */
    public function setLazyLoader($loader)
    {
        if(!isset($this->lazyLoader))
            $this->lazyLoader = $loader();

        return $this;
    }

    /**
     * Get the lazy loader instance
     *
     * @return \ImEmil\Container\Contracts\LazyLoaderInterface 
     */
    public function getLazyLoader()
    {
        return $this->lazyLoader;
    }
}
