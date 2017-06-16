<?php

namespace tables\users;

class clients extends \tables\users
{
    public $displaySingle = 'Client Access';
    
    public $ajaxModel = 'users\\clients';
    
    public $primaryKey = 'vu.id';
    
    public $fields = [
        'employer' => [
            'update' => 'vu.vendorID',
            'select' => 's.displayName',
            'display' => 'Employer',
            'ignore' => TRUE,
            'noEdit' => TRUE,
        ],
        'userID' => [
            'update' => 'vu.userID',
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'username',
        ],
        'vendorID' => [
            'update' => 'vu.vendorID',
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'display' => 'Client',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", v.vendorName)',
        ],
        'active' => [
            'select' => 'IF(vu.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'vu.active',
            'updateOverwrite' => TRUE,
        ],
    ];
        
    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');
        
        return 'client_users vu
               JOIN '.$userDB.'.info u ON vu.userID = u.id
               JOIN vendors v ON v.id = vu.vendorID
               JOIN warehouses w ON v.warehouseID = w.id
               JOIN statuses s ON s.id = u.employer';
    }

    /*
    ****************************************************************************
    */

    function insertTable()
    {
        return 'client_users';
    }
    
    /*
    ****************************************************************************
    */

}