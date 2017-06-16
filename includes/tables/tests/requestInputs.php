<?php

namespace tables\tests;

class requestInputs extends \tables\_default
{
    public $displaySingle = 'Request Input';

    public $ajaxModel = 'tests\\requestInputs';

    public $primaryKey = 'r.id';

    public $customSearchController = 'updateTesterList';

    public $fields = [
        'seriesDesc' => [
            'select' => 's.description',
            'display' => 'Series Name',
            'searcherDD' => 'tests\\series',
            'ddField' => 'description',
            'noEdit' => TRUE,
        ],
        'series' => [
            'select' => 'seriesID',
            'display' => 'Series ID',
            'noEdit' => TRUE,
        ],
        'requestID' => [
            'display' => 'Request ID',
            'noEdit' => TRUE,
        ],
        'active' => [
            'select' => 'IF(i.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\\boolean',
            'ddField' => 'displayName',
            'noEdit' => TRUE,
        ],
        'type' => [
            'display' => 'Method Type',
            'noEdit' => TRUE,
        ],
        'json' => [
            'display' => 'JSON Output',
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

        $this->insertTable = $dbName . '.request_inputs';

        return '   ' . $dbName . '.series s
                LEFT JOIN ' . $dbName . '.series_requests r ON r.seriesID = s.id
                LEFT JOIN ' . $dbName . '.request_inputs i ON i.requestID = r.id';
    }

    /*
    ****************************************************************************
    */

}