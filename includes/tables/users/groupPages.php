<?php

namespace tables\users;

class groupPages extends \tables\_default
{
    public $displaySingle = 'Group Pages';
    
    public $ajaxModel = 'users\\groupPages';
    
    public $primaryKey = 'gp.id';
    
    public $fields = [
        'groupName' => [
            'display' => 'Group Name',
            'searcherDD' => 'users\\groups',
            'ddField' => 'groupName',
            'update' => 'groupID',
        ],
        'displayName' => [
            'display' => 'Page Name',
            'searcherDD' => 'users\\pages',
            'ddField' => 'displayName',
            'update' => 'pageID',
        ],
        'active' => [
            'select' => 'IF(gp.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'gp.active',
            'updateOverwrite' => TRUE,
        ],       
    ];

    public $table = 'group_pages gp
                JOIN      pages p ON p.id = gp.pageID
                JOIN      groups g ON g.id = gp.groupID';
    
    public $mainField = 'displayName';
    
    public $customInsert = 'users\\groupPages';
 
    /*
    ****************************************************************************
    */    
    
    function customInsert($post)
    {
        $groupID = $post['groupName'];
        $pageID = $post['displayName'];
        $statusID = $post['active'];
        
        $sql = 'INSERT INTO group_pages (
                    pageID, groupID, active
                ) VALUES (
                    ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    groupID = ?,
                    active = ?';

        $ajaxRequest = TRUE;

        $param = [$pageID, $groupID, $statusID, $groupID, $statusID];
        
        $this->app->runQuery($sql, $param, $ajaxRequest);
    }
    
    
    /*
    ****************************************************************************
    */  
    
}