<?php

namespace tables\tests;

class requests extends \tables\_default
{
    public $displaySingle = 'Request';

    public $ajaxModel = 'tests\\requests';

    public $primaryKey = 'r.id';

    public $customSearchController = 'updateTesterList';
    
    public $fields = [
        'id' => [
            'select' => 'r.id',
            'display' => 'Request ID',
            'ignore' => TRUE,
            'noEdit' => TRUE,
        ],
        'seriesID' => [
            'select' => 's.id',
            'display' => 'Series ID',
            'ignore' => TRUE,
            'noEdit' => TRUE,
        ],
        'description' => [
            'display' => 'Series Description',
            'noEdit' => TRUE,
        ],
        'isJSON' => [
            'select' => 'IF(isJSON, "Yes", "No")',
            'display' => 'JSON Request',
            'searcherDD' => 'statuses\\boolean',
            'ddField' => 'displayName',
            'update' => 'isJSON',
            'noEdit' => TRUE,
        ],
        'active' => [
            'select' => 'IF(r.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\\boolean',
            'ddField' => 'displayName',
            'update' => 'active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $mainField = 'description';

    /*
    ****************************************************************************
    */

    function table()
    {
        $dbName = $this->app->getDBName('tests');

        return '' . $dbName . '.series s
                LEFT JOIN ' . $dbName . '.series_requests r ON r.seriesID = s.id';

        
    }

    /*
    ****************************************************************************
    */
}