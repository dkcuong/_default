<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base
{
    public $requests = [];

    public $postVars = [];

    public $testURLs = [];
    
    public $type = NULL;

    public $testID = 0;
    public $targetID = 0;
    
    public $addButton = TRUE;
    
    public $id = NULL;

    /*
    ****************************************************************************
    */

    function modelGetTestInfo()
    {
        $byTest = $this->type == 'test';
        $fieldClause = $byTest ? 't.id = ?' : 's.id = ?';

        $clause = $this->targetID ? $fieldClause : 1;
        
        $testFields = $byTest ? 't.id AS testID,
                                 t.displayName,' : NULL;
        $testTables = $byTest ? 
               'JOIN test_series ts ON s.id = ts.seriesID
                JOIN tests t ON t.id = ts.testID' : NULL;
        $testOrdering = $byTest ? 'seriesOrder,' : NULL;
        
        $sql = 'SELECT    i.id,
                          r.id AS requestID,
                          description,
                          json,
                          outputName,
                          '.$testFields.'
                          type,
                          isJSON
                FROM      series s
                JOIN      series_requests r ON r.seriesID = s.id
                JOIN      request_inputs i ON i.requestID = r.id
                '.$testTables.'
                WHERE     ' . $clause . '
                AND       r.active
                AND       i.active
                ORDER BY  '.$testOrdering.'
                          r.id';

        $results = $this->testDB->queryResults($sql, [$this->targetID]);

        $processedTests = [];
        foreach ($results as $row) {

            $this->testID = getDefault($row['testID']);

            $requestID = $row['requestID'];

            if (! isset($processedTests[$requestID])) {

                $this->requests[] = [
                    'requestID' => $requestID,
                    'request' => $row,
                ];

                $processedTests[$requestID] = TRUE;
            }

            switch ($row['type']) {
                case 'get':

                    $linkInfo = json_decode($row['json']);
                    
                    $getVars = $this->obtainGetVars($linkInfo);
                    
                    $linkFunc = $row['isJSON'] ? 'customJSONLink' : 'makeLink';
                    
                    $this->testURLs[$requestID] = 
                        $linkFunc($linkInfo->class, $linkInfo->method, $getVars);
                    
                    break;
                case 'post':
                    if ($row['json']) {
                        $this->postVars[$requestID] = $row['json'];
                    }
                    break;
                case 'session':
                    break;
            }
        }
    }

    /*
    ****************************************************************************
    */

    function obtainGetVars($linkInfo)
    {
        $getVars = getDefault($linkInfo->query, []);

        if (! $getVars) {

            $skipProperties = ['class', 'method', 'query'];

            foreach ($linkInfo as $properyName => $properyValue) {
                if (! in_array($properyName, $skipProperties)) {
                    $getVars[$properyName] = $properyValue;
                }
            }
        }
        
        return $getVars;
    }

    /*
    ****************************************************************************
    */  
}
