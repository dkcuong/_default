<?php

namespace objects;

class myNumber extends myType
{
    function __toString()
    {
        return $this->value;
    }
    
    //**************************************************************************

    function increment($inc=1)
    {
        $this->value += $inc;
        return $this;
    }
    
    //**************************************************************************

    function assertGreater($value, $callback)
    {
        $this->lastAssertResults = 'failed';
        $this->value > $value ? $this->call($callback) : NULL;
        return $this;
    }
    
    //**************************************************************************

}