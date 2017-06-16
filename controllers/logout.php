<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{
    function logoutController()
    {
        $forced = TRUE;

        checkLogout($this, $forced);
    }
}

