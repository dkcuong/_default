<?php

namespace models;

// Good for managing variables

class vars
{
    const TRACE = '';
    const SHOW_TRACE_COUNT = TRUE;
    const BACKTRACE_TRACE = 0;
    
    public $values = [];
    public $name = NULL;
    public $target = NULL;
    public $traceCount = 1;

    /*
    ****************************************************************************
    */

    static function init()
    {
        return new static();
    }
    
    /*
    ****************************************************************************
    */
    
    function debug($params)
    {
        if (! self::TRACE) {
            return $this;
        }
        
        $isArray = is_array($params['name']) ;
        
        $match = ! $isArray && self::TRACE == $params['name'];
        $inArray = $isArray && in_array(self::TRACE, $params['name']);
        
        $params['name'] = $inArray ? 
            implode('->', $params['name']) : $params['name'];
        
        if (! $match && ! $inArray) {
            return $this;
        }
        
        if (self::SHOW_TRACE_COUNT) {
            $params['traceCount'] = $this->traceCount++;
            self::BACKTRACE_TRACE == $params['traceCount'] ? backtrace() : NULL;
        }

        varDump($params);
        
        return $this;
    }
    
    /*
    ****************************************************************************
    */
    
    function get($name=FALSE, $getDef=FALSE, $default=NULL)
    {
        $names = is_array($name) ? $name : [$name];

        $keys = $name ? $names : [];
        
        $target = $this->values;
        foreach ($keys as $subKey) {
            if ($getDef && ! isset($target[$subKey])) {
                return $default;
            }
            
            $target = $target[$subKey];
        }
            
        $this->debug([
            'action' => 'getting variable',
            'name' => $name,
            'foundValue' => $target,
        ]);
        
        return $target;
    }

    /*
    ****************************************************************************
    */
    
    function set($name, $value)
    {
        $this->arrayVal($name);
        $this->target = $value;
        return $this->debug([
            'action' => 'setting variable',
            'name' => $name,
            'setValue' => $value,
        ]);
    }
    
    /*
    ****************************************************************************
    */
    
    function arrayVal($name)
    {
        $this->target = &$this->values;

        $keys = is_array($name) ? $name : [$name];
        
        foreach ($keys as $subKey) {
            $this->target[$subKey] = isset($this->target[$subKey]) ? 
                $this->target[$subKey] : [];
            $this->target = &$this->target[$subKey];
        }
        
        return $this;
    }
    
    /*
    ****************************************************************************
    */
    
    function setArray($source, $passedNames=[])
    {
        $names = $passedNames ? $passedNames : array_keys($source);
        
        foreach ($names as $name) {
            $this->set($name, $source[$name]);
        }
        
        return $this;
    }
    
    /*
    ****************************************************************************
    */
    
    function getName()
    {
        return $this->name;
    }
    
    /*
    ****************************************************************************
    */
    
    function required($names, $errorCallback)
    {
        foreach ($names as $name) {
            $this->name = $name;
            if (! $this->get($name)) {
                call_user_func($errorCallback);
            }
        }
        
        return $this;
    }
    
    /*
    ****************************************************************************
    */
    
    function check($name)
    {
        return isset($this->values[$name]);
    }
    
    /*
    ****************************************************************************
    */
    
    function remove($name)
    {
        unset($this->values[$name]);
        return $this;
    }
    
    /*
    ****************************************************************************
    */
    
    function push($name, $value)
    {
        $this->arrayVal($name);
        array_push($this->target, $value);
        return $this->debug([
            'action' => 'push variable',
            'name' => $name,
            'pushValue' => $value,
        ]);
    }
    
    /*
    ****************************************************************************
    */
    
    function out($name=FALSE)
    {
        $value = $this->get($name);
        varDump($value);
        $this;
    }
    
    /*
    ****************************************************************************
    */
    
    function getParams($names)
    {
        $params = [];
 
        foreach ($names as $name) {
            $params[] = $this->get($name);
        }

        return $params;
    }
    
    /*
    ****************************************************************************
    */
    
    function splice($name, $params)
    {
        $this->arrayVal($name);
        
        $length = isset($params['length']) ? $params['length'] : 0;
        $replaces = isset($params['replacement']) ? $params['replacement'] : [];

        array_splice($this->target, $params['offset'], $length, $replaces);
        
        return $this;
    }
    
    /*
    ****************************************************************************
    */
    
}