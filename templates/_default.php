<?php

class template extends model
{
    
    function header() 
    {
        models\templates::standardHeader($this);
    }
    
    /*
    ****************************************************************************
    */
    
    function footer() 
    {
        models\templates::standardFooter();
    }
    
    /*
    ****************************************************************************
    */
}