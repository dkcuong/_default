<?php 

class template extends model
{   
    
    function header() 
    {
        foreach ($this->logs as $log) {
            error_log($log);
        }
    }

    /*
    ****************************************************************************
    */

    function footer() 
    {
        
    }
}