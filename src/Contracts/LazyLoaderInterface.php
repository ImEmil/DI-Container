<?php

namespace ImEmil\Container\Contracts;

interface LazyLoaderInterface
{
    public function getProxy($class, $rules);
}