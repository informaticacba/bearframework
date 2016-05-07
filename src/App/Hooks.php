<?php

/*
 * Bear Framework
 * http://bearframework.com
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace BearFramework\App;

/**
 * Provides functionality for notifications and data requests
 */
class Hooks
{

    /**
     * Registered hook callbacks
     * @var array 
     */
    private $data = [];

    /**
     * Registers callback for the name specified
     * @param string $name The name
     * @param callable $callback The function to be called where the event happens
     * @param array $options Contains a priority option (default value: 100). Hooks with lower priority will be executed first.
     * @throws \InvalidArgumentException
     * @return void No value is returned
     */
    public function add($name, $callback, $options = [])
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('');
        }
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('');
        }
        if (!is_array($options)) {
            throw new \InvalidArgumentException('');
        }
        if (!isset($this->data[$name])) {
            $this->data[$name] = [];
        }
        if (!isset($options['priority'])) {
            $options['priority'] = 100;
        }
        $this->data[$name][] = [$callback, $options];
    }

    /**
     * Triggers execution of all callbacks hooked to the name specified
     * @param string $name The name
     * @throws \InvalidArgumentException
     * @return void No value is returned
     */
    public function execute($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('');
        }
        if (isset($this->data[$name])) {
            $arguments = func_get_args();
            unset($arguments[0]);
            $callbacks = $this->data[$name];
            if(isset($callbacks[1])){
                usort($callbacks, function($a, $b) {
                    return $a[1]['priority'] < $b[1]['priority'] ? -1 : 1;
                });
            }
            foreach ($callbacks as $callback) {
                call_user_func_array($callback[0], $arguments);
            }
        }
    }

}
