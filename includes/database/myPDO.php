<?php

namespace database;

// This can have its one directory when there are more database classes
class myPDO extends \PDO
{ 
    // Keep track of what database each PDO connection is targeting
    public $dbName;

    /*
    ****************************************************************************
    */

    function setDBName($name)
    {   
        $this->exec('USE '.$name);
        $this->dbName = $name;
    }
    /*
    ****************************************************************************
    */

    function getDBName()
    {   
        return $this->dbName;
    }

    /*
    ****************************************************************************
    */

}
