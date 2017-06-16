<?php

namespace objects;

class myString extends myType
{
    function __toString()
    {
        return $this->value;
    }
    
    //**************************************************************************

    function get()
    {
        return $this->value;
    }
}