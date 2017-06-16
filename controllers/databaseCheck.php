<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/


class controller extends template
{
    function runDatabaseCheckController()
    {
        if (getDefault($this->post['runCheck'])) {
            $this->checkResults = tables\databaseCheck::check($this);
        }
    }

    /*
    ****************************************************************************
    */

    function method2EmptyController()
    {
    }

    /*
    ****************************************************************************
    */
}