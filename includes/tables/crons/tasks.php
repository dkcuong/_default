<?php

namespace tables\crons;

class tasks extends \tables\_default
{
    public $displaySingle = 'Tasks';
    
    public $ajaxModel = 'crons\\tasks';
    
    public $primaryKey = 'id';
    
    public $fields = [
        'displayName' => [
            'display' => 'Task Name',
        ],
        'server' => [
            'display' => 'Server',
        ],
        'site' => [
            'display' => 'Site',
        ],
        'app' => [
            'display' => 'App',
        ],
        'class' => [
            'display' => 'Class',
        ],
        'method' => [
            'display' => 'Method',
        ],
        'frequency' => [
            'display' => 'Frequency',
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

    public $mainField = 'displayName';
    
    public $customInsert = 'crons\\tasks';
 
    /*
    ****************************************************************************
    */
    
    function table()
    {
        $cronsDB = $this->app->getDBName('crons');

        return $cronsDB.'.tasks';
    }

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
        $server = $post['server'];
        $site = $post['site'];
        $app = $post['app'];
        $class = $post['class'];
        $method = $post['method'];
        $frequency = $post['frequency'];
        $active = $post['active'];

        $sql = 'INSERT INTO '.$this->table().' (
                    displayName, 
                    server,
                    site,
                    app,
                    class,
                    method,
                    frequency, 
                    active
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?
                )';

        $ajaxRequest = TRUE;
        
        $param = [
            $displayName, 
            $server, 
            $site, 
            $app, 
            $class, 
            $method, 
            $frequency, 
            $active, 
        ];

        $this->app->runQuery($sql, $param, $ajaxRequest);
    }
        
    /*
    ****************************************************************************
    */
    
}