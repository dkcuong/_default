<?php

namespace tables\tests;

class tests extends \tables\_default
{
    public $displaySingle = 'Test';

    public $ajaxModel = 'tests\\tests';

    public $primaryKey = 'id';

    public $customSearchController = 'updateTesterList';

    public $fields = [
        'displayName' => [
            'display' => 'Test Description',
        ],
        'active' => [
            'insertDefaultValue' => TRUE,
            'select' => 'IF(active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\\boolean',
            'ddField' => 'displayName',
            'update' => 'active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $mainField = 'displayName';

    /*
    ****************************************************************************
    */

    function table()
    {
        $dbName = $this->app->getDBName('tests');

        return $this->insertTable = $dbName . '.tests';
    }

    /*
    ****************************************************************************
    */

    function getByName($testName)
    {
        if (! $testName) {
            return [];
        }
        
        $sql = 'SELECT    id,
                          displayName
                FROM      ' . $this->table() . '
                WHERE     displayName = ?
                AND       active';

        $results = $this->app->queryResult($sql, [$testName]);
        
        return $results;
    }

    /*
    ****************************************************************************
    */

}