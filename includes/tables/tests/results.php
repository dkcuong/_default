<?php

namespace tables\tests;

class results extends \tables\_default
{
    public $displaySingle = 'Test Results';

    public $ajaxModel = 'tests\\results';

    public $primaryKey = 'r.id';

    public $customSearchController = 'updateTesterList';

    public $fields = [
        'testID' => [
            'display' => 'Test ID',
            'noEdit' => TRUE,
        ],
        'testDesc' => [
            'select' => 't.displayName',
            'display' => 'Test Name',
            'searcherDD' => 'tests\\tests',
            'ddField' => 'displayName',
            'noEdit' => TRUE,
        ],
        'seriesID' => [
            'display' => 'Series ID',
            'noEdit' => TRUE,
        ],
        'seriesDesc' => [
            'select' => 's.description',
            'display' => 'Series Name',
            'searcherDD' => 'tests\\series',
            'ddField' => 'description',
            'noEdit' => TRUE,
        ],
        'json' => [
            'select' => 'IF(
                CHAR_LENGTH(json) > 50,
                CONCAT(LEFT(json, 50), " ..."),
                json
            )',
            'display' => 'JSON Output',
            'noEdit' => TRUE,
        ],
        'active' => [
            'select' => 'IF(r.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\\boolean',
            'ddField' => 'displayName',
            'noEdit' => TRUE,
        ],
    ];

    public $mainField = 'displayName';

    /*
    ****************************************************************************
    */

    function table()
    {
        $dbName = $this->app->getDBName('tests');

        $this->insertTable = $dbName . '.test_results';

        return $dbName.'.test_results r
        JOIN '.$dbName.'.series s ON r.seriesID = s.id
        JOIN '.$dbName.'.tests t ON r.testID = t.id';
    }

    /*
    ****************************************************************************
    */

}