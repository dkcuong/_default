<?php

namespace tables\tests;

class testSeries extends \tables\_default
{
    public $displaySingle = 'Test Series';

    public $ajaxModel = 'tests\\testSeries';

    public $primaryKey = 'ts.id';

    public $customSearchController = 'updateTesterList';

    public $fields = [
        'testID' => [
            'select' => 'displayName',
            'display' => 'Test Name',
            'searcherDD' => 'tests\\tests',
            'ddField' => 'displayName',
            'updateError' => 'There is already a series in the selected 
                test with the same ordering value.',
            'update' => 'ts.testID',
        ],
        'seriesID' => [
            'select' => 'description',
            'display' => 'Series Description',
            'searcherDD' => 'tests\\series',
            'ddField' => 'description',
            'update' => 'ts.seriesID',
        ],
        'seriesOrder' => [
            'display' => 'Request Order',
        ],
        'active' => [
            'insertDefaultValue' => TRUE,
            'select' => 'IF(ts.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\\boolean',
            'ddField' => 'displayName',
            'update' => 'ts.active',
        ],
    ];

    public $mainField = 'displayName';

    /*
    ****************************************************************************
    */

    function table()
    {
        $dbName = $this->app->getDBName('tests');

        $this->insertTable = $dbName . '.test_series';

        return $dbName . '.test_series ts
            JOIN      ' . $dbName . '.tests t ON t.id = ts.testID
            JOIN      ' . $dbName . '.series s ON s.id = ts.seriesID';
    }

    /*
    ****************************************************************************
    */

    function getCases()
    {
        $sql = 'SELECT    s.id,
                          displayName
                FROM      ' . $this->table() . '
                WHERE     sr.active
                AND       r.active
                GROUP BY  s.id
                ORDER BY  displayName';

        $results = $this->app->queryResults($sql);

        $return = [];

        foreach ($results as $key => $value) {
            $return[$key] = $value['displayName'] ;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getByTestID($testID)
    {
        if (! $testID) {
            return [];
        }
        
        $sql = 'SELECT    seriesID,
                          description,
                          outputName,
                          seriesOrder
                FROM      ' . $this->table() . '
                WHERE     testID = ?
                AND       ts.active
                AND       t.active
                ORDER BY  seriesOrder ASC';

        $results = $this->app->queryResults($sql, [$testID]);
            
        return $results;
    }

    /*
    ****************************************************************************
    */

}