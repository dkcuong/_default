<?php

namespace tables\tests;

class seriesRequests extends \tables\_default
{
    public $displaySingle = 'Series Request';

    public $ajaxModel = 'tests\\seriesRequests';

    public $primaryKey = 'ss.id';

    public $customSearchController = 'updateTesterList';

    public $fields = [
        'subcategoryID' => [
            'select' => 'displayName',
            'display' => 'Test Name',
            'searcherDD' => 'tests\\tests',
            'ddField' => 'displayName',
            'update' => 'ss.subcategoryID',
            'updateError' => 'There is already a request in the selected '.
                'test name with the same ordering value.'
        ],
        'seriesID' => [
            'select' => 'description',
            'display' => 'Request Name',
            'searcherDD' => 'tests\\requests',
            'ddField' => 'description',
            'update' => 'ss.seriesID',
        ],
        'seriesOrder' => [
            'display' => 'Request Order',
        ],
        'active' => [
            'insertDefaultValue' => TRUE,
            'select' => 'IF(ss.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\\boolean',
            'ddField' => 'displayName',
            'update' => 'ss.active',
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

        $this->insertTable = $dbName . '.subcategory_series';

        return $dbName . '.subcategory_series ss
            JOIN      ' . $dbName . '.test t ON t.id = ss.test
            JOIN      ' . $dbName . '.series se ON se.id = ss.seriesID';
    }

    /*
    ****************************************************************************
    */

}