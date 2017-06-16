<?php

namespace dbCommands;

class getDataValues
{
    public $db = NULL;
    public $inputs = [];
    public $values = [];
    
    /*
    ****************************************************************************
    */

    function __construct($app, $alias)
    {
        $this->db = $app->getDB(['dbAlias' => $alias]);
    }
    
    /*
    ****************************************************************************
    */
    
    function call($method, $inputs)
    {
        $this->inputs = $inputs;
        
        $classMethod = 'dbCommands\\getDataValues::'.$method;
        
        switch ($classMethod) {
            case 'dbCommands\getDataValues::page':
            case 'dbCommands\getDataValues::test':
            case 'dbCommands\getDataValues::group':
            case 'dbCommands\getDataValues::status':
            case 'dbCommands\getDataValues::submenu':
            case 'dbCommands\getDataValues::dealSite':
            case 'dbCommands\getDataValues::cronTask':
                self::$method();
                break;
            default: 
                die('Invalid Method Passed to getDataValues');
        }
        
        return $this->values;
    }

    /*
    ****************************************************************************
    */
    
    function test()
    {
        $test = $this->inputs['displayName'];

        // Get test series
        
        $sql = 'SELECT    r.id,
                          r.isJSON,
                          r.seriesID,
                          ts.seriesOrder,
                          s.description,
                          s.outputName
                FROM      tests t 
                JOIN      test_series ts ON t.id = ts.testID
                JOIN      series s ON ts.seriesID = s.id
                JOIN      series_requests r ON r.seriesID = s.id
                WHERE     t.displayName = ? 
                AND       ts.active
                AND       r.active
                ORDER BY  seriesOrder,
                          r.id';
        
        $requests = $this->db->queryResults($sql, [$test]);

        $requestIDs = array_keys($requests);
        
        $series = [];
        foreach ($requests as $row) {
            $seriesID = $row['seriesID'];
            $series[$seriesID] = [
                'seriesOrder' => $row['seriesOrder'], 
                'description' => $row['description'], 
                'outputName' => $row['outputName'], 
            ];
        }
        
        $seriesIDs = array_keys($series);

        // Get series requests inputs
        $sql = 'SELECT id,
                       requestID,
                       type,
                       json
                FROM   request_inputs
                WHERE  requestID IN ('.$this->db->getQMarkString($requests).')
                AND    active';
        
        $requestInputs = $this->db->queryResults($sql, $requestIDs);
        
        $inputsByRequestID = [];
        foreach ($requestInputs as $row) {
            $type = $row['type'];
            $requestID = $row['requestID'];
            $inputsByRequestID[$requestID][$type] = $row['json'];
        }
        
        // Get ignore fields
        $sql = 'SELECT seriesID,
                       ignoreField AS field
                FROM   ignore_fields
                WHERE  seriesID IN ('.$this->db->getQMarkString($series).')
                AND    active';

        $ignoreFields = $this->db->queryResults($sql, $seriesIDs);
        
        
        // Get test results
        $results = new \tables\tests\results($this->db);

        $testResults = $results->search([
            'selectField' => 'seriesID, description, json',
            'searchTerms' => [
                'displayName' => $test,
                'r.active' => TRUE,
            ],
        ]);
        
        $finalResults = $finalRequests = $finalSeries = [];

        foreach ($testResults as $row) {
            $seriesID = $row['seriesID'];
            $finalResults[$seriesID] = [
                'json' => $row['json'],
                'seriesDesc' => $row['description'],
            ];
        }
        
        foreach ($requests as $requestID => $row) {
            $seriesID = $row['seriesID'];
            $eachRequest = [
                'isJSON' => $row['isJSON'],
                'inputs' => $inputsByRequestID[$requestID],
            ];

            if (isset($ignoreFields[$seriesID])) {
                $eachRequest['ignoreFields'] = $ignoreFields[$seriesID]['field'];
            }
            $finalRequests[$seriesID][] = $eachRequest;
        }
        
        
        foreach ($series as $seriesID => $row) {
            
            $eachSeries = $row;
            
            $eachSeries['requests'] = $finalRequests[$seriesID];

            if (isset($ignoreFields[$seriesID])) {
                $eachSeries['ignoreFields'] = $ignoreFields[$seriesID]['field'];
            }
            
            if (isset($finalResults[$seriesID])) {
                $eachSeries['results'] = $finalResults[$seriesID];
            }
            
            $finalSeries[] = $eachSeries;
        }
        
        $this->values = [
            'name' => $test,
            'series' => $finalSeries,
        ];
    }

    /*
    ****************************************************************************
    */
    
    function cronTask()
    {
        $displayName = $this->inputs['displayName'];     
        $cronTasks = new \tables\crons\tasks($this->db);

        $this->values = $cronTasks->search([
            'term' => $displayName,
            'search' => 'displayName',
            'oneResult' => TRUE,
        ]);
        
        unset($this->values['id']);
        unset($this->values['server']);
        unset($this->values['site']);

        $bool = new \tables\statuses\boolean($this->db);
        $this->values['active'] = $bool->getKey($this->values['active']);
    }

    /*
    ****************************************************************************
    */
    
    function submenu()
    {
        $displayName = $this->inputs['displayName'];     
        $submenus = new \tables\users\submenus($this->db);

        $this->values = $submenus->search([
            'term' => $displayName,
            'search' => 'displayName',
            'oneResult' => TRUE,
        ]);
        
        diedump($this->values);
    }

    /*
    ****************************************************************************
    */
    
    function dealSite()
    {
        $displayName = $this->inputs['displayName'];     
        $dealSites = new \tables\dealSites($this->db);

        $this->values = $dealSites->search([
            'term' => $displayName,
            'search' => 'displayName',
            'oneResult' => TRUE,
        ]);
        
        unset($this->values['id']);
    }

    /*
    ****************************************************************************
    */    

    function status()
    {
        $displayName = $this->inputs['displayName'];     

        $statuses = new \tables\statuses($this->db);
        $this->values = $statuses->search([
            'term' => $displayName,
            'search' => 'displayName',
            'oneResult' => TRUE,
        ]);
        
        unset($this->values['id']);
    }

    /*
    ****************************************************************************
    */    

    function group()
    {
        $description = $this->inputs['description'];
        $groups = new \tables\users\groups($this->db);
        
        $group = $groups->search([
            'term' => $description,
            'search' => 'description',
            'oneResult' => TRUE,
        ]);
        
        $bool = new \tables\statuses\boolean($this->db);

        $this->values = [
            'groupName' => $group['groupName'],
            'hiddenName' => $group['hiddenName'],
            'description' => $group['description'],
            'active' => $bool->getKey($group['active']),
        ];
    }

    /*
    ****************************************************************************
    */    

    function page()
    {
        $hiddenName = $this->inputs['hiddenName'];     
        $displayName = $this->inputs['displayName'];     
        $params = new \tables\users\pageParams($this->db);

        // Get pages submenu info
        $sql = 'SELECT    sm.id AS submenuID,
                          sm.hiddenName as submenuHidden,
                          p.id,
                          p.red,
                          p.class,
                          p.method,
                          sp.active,
                          p.hiddenName,
                          p.displayName,
                          p.displayOrder
                FROM      pages p
                JOIN      submenu_pages sp ON sp.pageID = p.id
                JOIN      submenus sm ON sm.id = sp.subMenuID
                WHERE     p.hiddenName = ?
                AND       p.displayName = ?';
            
        $page = $this->db->queryResult($sql, [$hiddenName, $displayName]);
        
        $page or die('Page Not Found');

        $this->values = [
            'red' => $page['red'],
            'class' => $page['class'],
            'method' => $page['method'],
            'active' => $page['active'],
            'hiddenName' => $hiddenName,
            'displayName' => $page['displayName'],
            'submenuHidden' => $page['submenuHidden'],
        ];
        
        $pageParams = $params->getPageParams($page['id']);
        
        if ($pageParams) {
            $this->values['params'] = reset($pageParams);
        }
        
        // Get the page before and after the link
        $sql = 'SELECT hiddenName
                FROM   pages p
                JOIN   submenu_pages sp ON sp.pageID = p.id
                WHERE  submenuID = ?
                AND    displayOrder < ?
                ORDER BY displayOrder DESC
                LIMIT  1';

        $this->values['itemBefore'] = $this->db->queryResult($sql, [
            $page['submenuID'],
            $page['displayOrder']
        ], 'hiddenName');
        
        // Get the page before and after the link
        $sql = 'SELECT hiddenName
                FROM   pages p
                JOIN   submenu_pages sp ON sp.pageID = p.id
                WHERE  submenuID = ?
                AND    displayOrder > ?
                ORDER BY displayOrder ASC
                LIMIT  1';

        $this->values['itemAfter'] = $this->db->queryResult($sql, [
            $page['submenuID'],
            $page['displayOrder']
        ], 'hiddenName');
    }

    /*
    ****************************************************************************
    */

}
