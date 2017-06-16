<?php

namespace objects;

class myType
{
    public $type;
    public $value;
    public $model;
    public $varName;
    public $setKey = NULL;
    public $lastAssertResults;
    
    //**************************************************************************

    function __construct($model, $value)
    {
        $this->model = $model;
        $this->value = $value;
        return $this;
    }
    
    //**************************************************************************

    function __get($func)
    {
        return $this->$func();
    }
    
    //**************************************************************************

    function __call($method, $params=NULL)
    {
        
        return count($params) > 1 ? 
            $this->model->$method($params[0], $params[1]) : 
            $this->model->$method($params);
    }
    
    //**************************************************************************

    function __debugInfo()
    {
        return array_filter([
            'varName' => $this->varName,
            'type' => $this->type,
            'value' => $this->value,
            'setKey' => $this->setKey,
        ]);
    }

    //**************************************************************************

    static function makeVar($model, $value)
    {
        return new static($model, $value);
    }
        
    //**************************************************************************

    function store($name)
    {
        $this->varName = $name; 
        $this->model->set($name, $this);
        return $this;
    }

    //**************************************************************************
    
    function getName()
    {
        return $this->varName;
    }

    //**************************************************************************
    
    function getValue()
    {
        return $this->value;
    }

    //**************************************************************************
    
    function setType($type)
    {
        $this->type = $type; 
        return $this;
    }
    
    //**************************************************************************

    function cloneTo($index)
    {
        $that = clone($this);
        return $that->store($index);
    }
    
    //**************************************************************************

    function call($callback, $value=FALSE, $key=FALSE)
    {
        $this->lastAssertResults = 'passed';
        
        if ($value) {
        return is_string($callback) ? 
            call_user_func([$this->model, $callback], $value, $key, $this) : 
            $callback($value, $key, $this);            
        }
        
        return is_string($callback) ? 
            call_user_func([$this->model, $callback], $this) : $callback($this);
    }
    
    //**************************************************************************

    function assert($callback, $data=[], $not=FALSE)
    {
        $this->lastAssertResults = 'failed';
        $this->data = $data;
        ($not xor $this->value) ?  $this->call($callback) : NULL;

        return $this;
    }
    
    //**************************************************************************

    function failed($callback, $data=[])
    {
        $this->data = $data;
        $this->lastAssertResults == 'failed' ? $this->call($callback) : NULL;
        
        return $this;
    }
    
    //**************************************************************************

    function assertNot($callback, $data=[])
    {
        return self::assert($callback, $data, 'not');
    }
    
    //**************************************************************************

    function trigger($trigger, $callback)
    {
        $trigger ? $callback() : NULL;
        return $this;
    }

    //**************************************************************************

    function dump($depth=FALSE)
    {
        varDump($this, $depth);
        return $this;
    }
    
    //**************************************************************************

    function stop()
    {
        $this->dump();
        die;
    }    

    //**************************************************************************

    function assertEqual($value, $callback) 
    {
        $this->value == $value ? $this->call($callback, $this->value) : NULL;
    }
    
    //**************************************************************************

    function assertNotEqual($value, $callback) 
    {
        $this->value != $value ? $this->call($callback, $this->value) : NULL;
    }
    
    //**************************************************************************

}