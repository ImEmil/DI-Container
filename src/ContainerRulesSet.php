<?php

namespace ImEmil\Container;

use ImEmil\Container\Contracts\ContainerRulesInterface;

class ContainerRulesSet implements ContainerRulesInterface
{
    /**
     * The container configuration rules
     * 
     * @var array
     */
    private $rules = [];

    /**
     * Register a new rule
     * 
     * @param  string            $rule 
     * @param  string|array|bool $value 
     * @return $this
     */
    public function set($rule, $value = true)
    {
        if($rule == 'constructParams' && !is_array($value)) // make sure the constructParams value always is an array before adding it
            $value = [$value];

        $this->rules[$rule] = $value;

        return $this;
    }

    /**
     * Get all configuration rules
     * 
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Whether a single instance is used throughout the container
     * 
     * @see https://r.je/dice.html#example3
     * 
     * @return $this
     */
    public function shared()
    {
        return $this->set('shared');
    }

    /**
     * Additional parameters passed to the constructor
     * 
     * @see https://r.je/dice.html#example3
     * 
     * @param  array $arguments 
     * @return $this
     */
    public function withArguments($arguments)
    {
        return $this->set('constructParams', $arguments);
    }

    /**
     * Whether the rule will also apply to subclasses (defaults to true)
     * 
     * @see https://r.je/dice.html#example3
     * 
     * @param  bool $inherit 
     * @return $this
     */
    public function inherit($inherit)
    {
        return $this->set('inherit', (bool)$inherit);
    }
}
