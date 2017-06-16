<?php

namespace tables\users;

class subMenus extends \tables\_default
{
    public $displaySingle = 'Pages';
    
    public $ajaxModel = 'users\\subMenus';
    
    public $primaryKey = 'id';
    
    public $fields = [
        'displayName' => [
            'display' => 'Submenu Name',
        ],
        'displayOrder' => [
            'display' => 'Submenu Order',
            'noEdit' => TRUE,
        ],
        'active' => [
            'select' => 'IF(active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'active',
            'updateOverwrite' => TRUE,
        ],       
    ];

    public $table = 'subMenus';
    
    public $mainField = 'displayName';
    
    public $customInsert = 'users\\subMenus';
 
    /*
    ****************************************************************************
    */
    
    function insertTable()
    {
        return $this->table;
    }
    
    /*
    ****************************************************************************
    */
    
    function customInsert($post)
    {
        $displayName = $post['displayName'];
        $displayOrder = $post['displayOrder'];
        $active = $post['active'];
        
        $sql = 'INSERT INTO subMenus (
                    displayName, displayOrder, active
                ) VALUES (
                    ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    displayOrder = ?,
                    active = ?';

        $ajaxRequest = TRUE;

        $param = [$displayName, $displayOrder, $active, $displayOrder, $active];

        $this->app->runQuery($sql, $param, $ajaxRequest);
    }
        
    /*
    ****************************************************************************
    */    
}