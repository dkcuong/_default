<?php 

class template extends model
{   
    
    function header()
    {
        echo isset($this->results) ? json_encode($this->results) : NULL;
    }

    /*
    ****************************************************************************
    */

    function footer() 
    {
        
    }
}