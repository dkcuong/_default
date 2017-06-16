<?php

namespace tables\tests;

class ignoreFields extends \tables\_default
{
    public $displaySingle = 'Ignore Field';

    public $ajaxModel = 'tests\\ignoreFields';

    public $primaryKey = 'i.id';

    public $customSearchController = 'updateTesterList';

    public $fields = [
        'seriesID' => [
            'select' => 'description',
            'display' => 'Series Name',
            'searcherDD' => 'tests\\requests',
            'ddField' => 'description',
            'update' => 'i.seriesID',
            'noEdit' => TRUE,
        ],
        'ignoreField' => [
            'display' => 'Ignore Field',
            'noEdit' => TRUE,
        ],
        'active' => [
            'select' => 'IF(i.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\\boolean',
            'ddField' => 'displayName',
            'update' => 'i.active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $mainField = 'ignoreField';

    /*
    ****************************************************************************
    */

    function table()
    {
        $dbName = $this->app->getDBName('tests');

        $this->insertTable = $dbName . '.ignore_fields';

        return $dbName . '.ignore_fields i
            JOIN      ' . $dbName . '.series s ON s.id = i.seriesID';
    }

    /*
    ****************************************************************************
    */

    function getTestIgnoreFields()
    {
        $sql = 'SELECT    i.id,
                          ignoreField
                FROM      ' . $this->table() . '
                WHERE     i.active';

        $results = $this->app->queryResults($sql);

        $return = [];

        foreach ($results as $key => $value) {
            $return[$key] = $value['ignoreField'] ;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

}