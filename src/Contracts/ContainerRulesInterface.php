<?php

namespace ImEmil\Container\Contracts;

interface ContainerRulesInterface
{
	/**
	 * Description
	 * 
	 * @param  type $rule 
	 * @param  type|bool $value 
	 * @return type
	 */
    public function set($rule, $value = true);

    /**
     * Description
     * 
     * @return array
     */
    public function getRules();
}