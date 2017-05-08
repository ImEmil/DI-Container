<?php

namespace ImEmil\Container\ServiceProvider;

use ImEmil\Container\Traits\ContainerAwareTrait;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $provides = [];

    /**
     * {@inheritdoc}
     */
    public function provides($alias = null)
    {
        if(!is_null($alias))
        {
            return in_array($alias, $this->provides);
        }

        return $this->provides;
    }
}
