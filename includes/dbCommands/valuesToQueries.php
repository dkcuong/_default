<?php

namespace dbCommands;

use \models\config;

class valuesToQueries
{
    public $db = NULL;
    public $inputs = [];

    public $check = NULL;
    public $command = NULL;
    public $display = FALSE;

    /*
    ****************************************************************************
    */

    function __construct($params)
    {
        $this->display = getDefault($params['displayMode']);

        $this->db = $params['app']->getDB([
            'dbAlias' => $params['dbKey'],
        ]);
    }

    /*
    ****************************************************************************
    */

    function call($params)
    {
        $this->inputs = $params['dataInputs'];

        $method = $params['command']['model'];

        $classMethod = 'dbCommands\\valuesToQueries::'.$method;

        switch ($classMethod) {
            case 'dbCommands\valuesToQueries::page':
            case 'dbCommands\valuesToQueries::test':
            case 'dbCommands\valuesToQueries::group':
            case 'dbCommands\valuesToQueries::status':
            case 'dbCommands\valuesToQueries::dealSite':
            case 'dbCommands\valuesToQueries::cronTask':
                $this->$method();
                break;
            default:
                die('Invalid Method Passed to valuesToQueries');
        }

        return [
            'sql' => $this->command,
            'check' => model::removeQuerySpaces($this->check),
        ];
    }

    /*
    ****************************************************************************
    */

    function addCommand($sql)
    {
        $this->command .= model::removeQuerySpaces($sql, 'parallelLines') .
            PHP_EOL;
    }

    /*
    ****************************************************************************
    */

    function test()
    {
        $name = $this->inputs['name'];
        $inputSeries = $this->inputs['series'];

        $test = new \tables\tests\tests($this->db);

        $this->check = $test->search([
            'term' => $name,
            'search' => 'displayName',
            'returnQuery' => TRUE,
        ]);

        // Get series descs and check if they already exist

        $descriptions = array_column($inputSeries, 'description');

        $sql = 'SELECT description,
                       id
                FROM   series
                WHERE  description IN ('.$this->db->getQMarkString($descriptions).')';

        $foundDescs = $this->db->queryResults($sql, $descriptions);

        $testID = $test->getNextID('tests');

        // It is possible that all the tests series already exist
        $foundAllSeries = count($foundDescs) == count($descriptions);
        $nextSeriesID = $foundAllSeries ? NULL : $test->getNextID('series');
        $nextRequestID = $foundAllSeries ? NULL : $test->getNextID('series_requests');

        $seriesOrdering = 1;

        foreach ($descriptions as $index => $desc) {

            $foundID = getDefault($foundDescs[$desc]['id']);

            $seriesID = $foundID ? $foundID : $nextSeriesID;

            $series = $inputSeries[$index];

            // Create test series relationships
            $this->addCommand('
                INSERT INTO test_series (
                    testID, seriesID, seriesOrder
                ) VALUES (
                    "'.$testID.'",
                    "'.$seriesID.'",
                    "'.$seriesOrdering++.'"
                );
            ');

            // Create test series result
            $this->addCommand('
                INSERT INTO test_results (
                    testID, seriesID, json
                ) VALUES (
                    "'.$testID.'",
                    "'.$seriesID.'",
                    '.$this->jsonDisplay($series['results']['json']).'
                );
            ');

            if ($foundID) {
                continue;
            }

            // Create series
            $this->addCommand('
                INSERT INTO series (
                    description, outputName
                ) VALUES (
                    "'.$desc.'",
                    "'.$series['outputName'].'"
                );
            ');

            // Create requuests
            foreach ($series['requests'] as $request) {
                $this->addCommand('
                    INSERT INTO series_requests (
                        seriesID, isJSON
                    ) VALUES (
                        "'.$nextSeriesID.'",
                        "'.$request['isJSON'].'"
                    );
                ');

                foreach ($request['inputs'] as $type => $json) {
                    $this->addCommand('
                        INSERT INTO request_inputs (
                            requestID, type, json
                        ) VALUES (
                            "'.$nextRequestID.'",
                            "'.$type.'",
                            '.$this->jsonDisplay($json).'
                        );
                    ');
                }
                $nextRequestID++;
            }


            // create ignore fields
            $ignoreFields = getDefault($series['ignoreFields'], []);
            foreach ($ignoreFields as $field) {
                $this->addCommand('
                    INSERT INTO ignore_fields (
                        seriesID, ignoreField
                    ) VALUES (
                        "'.$nextSeriesID.'",
                        "'.$field.'"
                    );
                ');
            }

            $foundDescs[$desc] = $nextSeriesID++;
        }

        // Create the test

        $this->addCommand('
            INSERT INTO tests (displayName)
            VALUES ("'.$name.'");
        ');
    }

    /*
    ****************************************************************************
    */

    public function jsonDisplay($json)
    {
        if (! $this->display) {
            return $this->db->quote($json);
        }

        ob_start();
        ?><span title="<?php echo htmlentities($json); ?>">[JSON Value]</span><?php
        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function cronTask()
    {
        $displayName = $this->inputs['displayName'];
        $cronTasks = new \tables\crons\tasks($this->db);

        // Confirm the task is for the current app
        $page = \sitePages::check($this->inputs);
        $rightApp = $this->inputs['app'] == config::get('site', 'appName');

        $this->check = $page && $rightApp ?
        $cronTasks->search([
            'selectField' => 'displayName',
            'searchTerms' => [
                'displayName' => $displayName,
            ],
            'returnQuery' => TRUE,
        ]) :
        'SELECT "Don\'t insert. Cron is for different app.";';

        $this->addCommand('
            INSERT INTO tasks (
                displayName, server, site, app,
                class, method, frequency, active
            ) VALUES (
                "'.$this->inputs['displayName'].'",
                "'.config::getServerVar('SERVER_NAME').'",
                "'.config::get('site', 'mvc').'",
                "'.config::get('site', 'appName').'",
                "'.$this->inputs['class'].'",
                "'.$this->inputs['method'].'",
                "'.$this->inputs['frequency'].'",
                "'.$this->inputs['active'].'"
            );
        ');
    }

    /*
    ****************************************************************************
    */

    function dealSite()
    {
        $dealSites = new \tables\dealSites($this->db);

        $this->check = $dealSites->search([
            'selectField' => 'displayName',
            'search' => 'displayName',
            'term' => $this->inputs['displayName'],
            'returnQuery' => TRUE,
        ]);

        $this->addCommand('
            INSERT INTO deal_sites (
                displayName, imageName
            ) VALUES (
                "'.$this->inputs['displayName'].'",
                "'.$this->inputs['imageName'].'"
            );
        ');
    }

    /*
    ****************************************************************************
    */

    function status()
    {
        $statuses = new \tables\statuses($this->db);

        $this->check = $statuses->search([
            'selectField' => 'shortName',
            'search' => 'shortName',
            'term' => $this->inputs['shortName'],
            'returnQuery' => TRUE,
        ]);

        $this->addCommand('
            INSERT INTO statuses (
                category, displayName, shortName
            ) VALUES (
                "'.$this->inputs['category'].'",
                "'.$this->inputs['displayName'].'",
                "'.$this->inputs['shortName'].'"
            );
        ');
    }

    /*
    ****************************************************************************
    */

    function group()
    {
        $groups = new \tables\users\groups($this->db);

        $this->check = $groups->search([
            'selectField' => 'hiddenName',
            'search' => 'hiddenName',
            'term' => $this->inputs['hiddenName'],
            'returnQuery' => TRUE,
        ]);

        $this->addCommand('
            INSERT INTO groups (
                groupName, hiddenName, description, active
            ) VALUES (
                "'.$this->inputs['groupName'].'",
                "'.$this->inputs['hiddenName'].'",
                "'.$this->inputs['description'].'",
                '.$this->inputs['active'].'
            );
        ');
    }

    /*
    ****************************************************************************
    */

    function page()
    {
        $pages = new \tables\users\pages($this->db);
        $submenus = new \tables\users\submenus($this->db);

        $submenu = $submenus->search([
            'selectField' => 'id',
            'search' => 'hiddenName',
            'term' => $this->inputs['submenuHidden'],
            'oneResult' => TRUE,
        ]);

        $this->check = $pages->search([
            'selectField' => 'hiddenName',
            'searchTerms' => [
                'hiddenName' => $this->inputs['hiddenName'],
                'displayName' => $this->inputs['displayName'],
            ],
            'returnQuery' => TRUE,
        ]);

        $ordering = 1;
        if ($this->inputs['itemBefore'] && $this->inputs['itemAfter']) {

            $sql = 'SELECT AVG(displayOrder) AS ordering
                    FROM   pages
                    WHERE  hiddenName IN (?, ?)';

            $result = $this->db->queryResult($sql, [
                $this->inputs['itemAfter'],
                $this->inputs['itemBefore']
            ]);

            $ordering = $result['ordering'];

        } else if ($this->inputs['itemBefore']) {
            $itemBefore = $pages->search([
                'selectField' => 'displayOrder',
                'search' => 'hiddenName',
                'term' => $this->inputs['itemBefore'],
                'oneResult' => TRUE,
            ]);

            $ordering = $itemBefore['displayOrder'] + 1;

        } else if ($this->inputs['itemAfter']) {
            $itemAfter = $pages->search([
                'selectField' => 'displayOrder',
                'search' => 'hiddenName',
                'term' => $this->inputs['itemAfter'],
                'oneResult' => TRUE,
            ]);

            $ordering = $itemAfter['displayOrder'] / 2;
        }

        // Insert if the row is not found but update if it has wrong display
        $page = $pages->search([
            'selectField' => 'hiddenName',
            'search' => 'hiddenName',
            'term' => $this->inputs['hiddenName'],
            'oneResult' => TRUE,
        ]);

        $page ?
        $this->addCommand('
            UPDATE pages p
            JOIN   submenu_pages sp ON sp.pageID = p.id
            SET    subMenuID = '.$submenu['id'].',
                   displayName = "'.$this->inputs['displayName'].'",
                   displayOrder = '.$ordering.',
                   class = "'.$this->inputs['class'].'",
                   method = "'.$this->inputs['method'].'",
                   red = '.$this->inputs['red'].'
            WHERE  hiddenName = "'.$this->inputs['hiddenName'].'";
        ') :
        $this->addCommand('
            INSERT INTO pages (
                displayName, displayOrder,
                hiddenName, class, method, red
            ) VALUES (
                "'.$this->inputs['displayName'].'",
                '.($ordering ? $ordering : 0).', 
                "'.$this->inputs['hiddenName'].'",
                "'.$this->inputs['class'].'",
                "'.$this->inputs['method'].'",
                "'.$this->inputs['red'].'"
            );
        ');

        $nextPageID = $pages->getNextID();

        if (! $page) {
            $this->addCommand('
                INSERT INTO submenu_pages
                SET pageID = ' . $nextPageID .',
                    subMenuID = ' . $submenu['id'] . ';
            ');
        }

        $params = getDefault($this->inputs['params']);

        if (! $params) {
            return;
        }

        $pageParams = new \tables\users\pageParams($this->db);
        $pagesParams = $pageParams->getPageParams($page['id'], 'returnRow');

        $pageID = $page['id'];

        foreach ($params as $name => $value) {

            $paramInfo = getDefault($pagesParams[$pageID][$name]);

            $paramInfo ?
            $this->addCommand('
                UPDATE page_params (
                SET    pageID = '.$pageID.',
                       name = "'.$name.'",
                       value = "'.$value.'",
                       active = 1
                WHERE  id = '.$paramInfo['id'].';
            ') :
            $this->addCommand('
                INSERT INTO page_params (
                    pageID, name, value, active
                ) VALUES (
                    '.$nextPageID.',
                    "'.$name.'",
                    "'.$value.'",
                    1
                );
            ');
        }
    }

    /*
    ****************************************************************************
    */

}
