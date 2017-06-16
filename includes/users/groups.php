<?php

namespace users;

class groups
{
    static $glue;

    static $userDB;

    static $groupDB;

    static $dbTerms = [];

    static $userTable;

    static $categoryDB;

    static $groupTable;

    static $searchTerm;

    static $warehouseDB;

    static $userDBTable;

    static $searchField;

    static $groupDBTable;

    static $selectFields = [];

    static $categoryTable;

    static $warehouseTable;

    static $allowedDBTerms = [
        NULL,
        'u.id',
        'info',
        'groups',
        'vendor',
        'groupID',
        'vendors',
        'username',
        'vendorID',
        'vendorName',
        'warehouses',
        'u.username',
        'client_users',
        'user_groups',
        'c.hiddenName',
        'CONCAT(w.shortName, "_", vendorName)',
    ];

    static $categoryDBTable;

    static $warehouseDBTable;

    /*
    ****************************************************************************
    */

    static function commonGroupLookUp($db, $hiddenName)
    {
        $usersGroups = self::getGroupUserInfo([
            'db' => $db,
            'glue' => 'groupID',
            'userDB' => 'users',
            'groupDB' => 'users',
            'userTable' => 'info',
            'categoryDB' => 'app',
            'groupTable' => 'user_groups',
            'selectFields' => ['c.hiddenName'],
            'categoryTable' => 'groups',
            'activeFieldsRequered' => TRUE,
            'searchField' => 'u.id',
            'searchTerm' => \access::getUserID(),
        ]);
        
        return isset($usersGroups[$hiddenName]);
    }
    
    /*
    ****************************************************************************
    */

    static function commonClientLookUp($db)
    {
        return self::getGroupUserInfo([
            'db' => $db,
            'glue' => 'vendorID',
            'userDB' => 'users',
            'groupDB' => 'users',
            'userTable' => 'info',
            'categoryDB' => 'app',
            'groupTable' => 'client_users',
            'selectFields' => ['vendorID'],
            'categoryTable' => 'vendors',
            'activeFieldsRequered' => TRUE,
            'searchField' => 'u.id',
            'searchTerm' => \access::getUserID(),
        ]);
    }

    /*
    ****************************************************************************
    */

    static function storeParams($params)
    {
        $db = $params['db'];

        self::$selectFields = isset($params['selectFields']) ?
            $params['selectFields'] : self::$selectFields;

        self::$dbTerms['glue'] = self::$glue = isset($params['glue']) ?
            $params['glue'] : self::$glue;

        self::$userDB = isset($params['userDB']) ?
            $db->getDBName($params['userDB']) : self::$userDB;

        self::$groupDB = isset($params['groupDB']) ?
            $db->getDBName($params['groupDB']) : self::$groupDB;

        self::$categoryDB = isset($params['categoryDB']) ?
            $db->getDBName($params['categoryDB']) : self::$categoryDB;

        self::$warehouseDB = isset($params['warehouseDB']) ?
            $db->getDBName($params['warehouseDB']) : self::$warehouseDB;

        self::$dbTerms['userTable'] = self::$userTable =
            isset($params['userTable']) ? $params['userTable'] :
            self::$userTable;

        self::$dbTerms['groupTable'] = self::$groupTable =
            isset($params['groupTable']) ? $params['groupTable'] :
            self::$groupTable;

        self::$dbTerms['categoryTable'] = self::$categoryTable =
            isset($params['categoryTable']) ? $params['categoryTable'] :
            self::$categoryTable;

        self::$dbTerms['warehouseTable'] = self::$warehouseTable =
            isset($params['warehouseTable']) ? $params['warehouseTable'] :
            self::$warehouseTable;

        self::$dbTerms['searchField'] = self::$searchField =
            $params['searchField'];

        self::$userDBTable = self::$userDB.'.'.self::$userTable;
        self::$groupDBTable = self::$groupTable;
        self::$categoryDBTable = self::$categoryDB.'.'.self::$categoryTable;
        self::$warehouseDBTable = self::$warehouseDB ?
            self::$warehouseDB.'.'.self::$warehouseTable : NULL;

        self::$dbTerms += self::$selectFields;

        $invalidName = array_diff(self::$dbTerms, self::$allowedDBTerms);

        if ($invalidName) {
            vardump($invalidName);
            echo isDev() ? 'Invalid fields/tables sent to user groups class.' :
                NULL;

            backTrace();

            die;
        }
    }

    /*
    ****************************************************************************
    */

    static function getGroupUserInfo($params)
    {
        self::storeParams($params);

        $warehouseJoin = self::$warehouseDBTable ?
            'JOIN ' . self::$warehouseDBTable . ' w ON w.id = c.warehouseID' :
            NULL;

        $activeFieldsRequered = getDefault($params['activeFieldsRequered']);

        $clause = $activeFieldsRequered ?
                'AND    g.active
                 AND    c.active
                 AND    u.active' : NULL;

        $sql = 'SELECT ' . implode(',', self::$selectFields) . '
                FROM   ' . self::$userDBTable . ' u
                JOIN   ' . self::$groupDBTable . ' g ON u.id = g.userID
                JOIN   ' . self::$categoryDBTable . ' c ON c.id = g.' . self::$glue . '
                ' . $warehouseJoin . '
                WHERE  ' . self::$searchField . ' = ?
                ' . $clause;

        $searchTerm = $params['searchTerm'];

        return $params['db']->queryResults($sql, [$searchTerm]);
    }
}
