<?php

namespace database;

// This can have its one directory when there are more database classes
class connectionHolder extends \dbInfo
{
    public $pdo;
    public $info = [];

    /*
    ****************************************************************************
    */

    function __construct($params)
    {
        $this->pdo = $params['pdo'];
        $this->info['user'] = $params['user'];
        $this->info['host'] = $params['host'];
        $this->info['dbName'] = $params['dbName'];
        $this->info['port'] = $params['port'];
    }

    /*
    ****************************************************************************
    */

    function getPDO()
    {
        // Check if the db needs to be swtiched
        $pdoDB = $this->pdo->getDBName();
        $holderDB = $this->info['dbName'];

        $pdoDB != $holderDB ? $this->pdo->setDBName($holderDB) : NULL;

        return $this->pdo;
    }

    /*
    ****************************************************************************
    */

}
