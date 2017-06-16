<?php

namespace tables\tests;

class series extends \tables\_default
{
    public $displaySingle = 'Series';

    public $ajaxModel = 'tests\\series';

    public $primaryKey = 'id';

    public $customSearchController = 'updateTesterList';

    public $fields = [
        'id' => [
            'select' => 'id',
            'display' => 'Series ID',
            'ignore' => TRUE,
            'noEdit' => TRUE,
        ],
        'description' => [
            'display' => 'Series Description',
        ],
        'outputName' => [
            'display' => 'Output Variable',
            'optional' => TRUE,
        ],
    ];

    public $mainField = 'description';

    /*
    ****************************************************************************
    */

    function table()
    {
        $dbName = $this->app->getDBName('tests');

        $this->insertTable = $dbName . '.series';
        
        return '' . $dbName . '.series';

        
    }

    /*
    ****************************************************************************
    */
}