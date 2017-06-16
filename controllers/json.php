<?php

use models\config;
use models\directories;
use dbCommands\model as dbCommands;
use \dbCommands\json as dbCommandsJSON;

/*
********************************************************************************
* JSON CLASS CONTROLLER METHODS                                                *
********************************************************************************
*/

class controller extends template
{
    function dialogLoginJSONController()
    {
        $checking = $this->getVar('sessionCheck', 'getDef');
        if ($checking) {
            $seconds = access::checkTimeOut($this, 'getDiff');
            $expirationTime = $seconds['lastUpdateTime'] + $seconds['duration'];
            return $this->results = [
                'remaining' => $expirationTime - $seconds['currentTime']
            ];
        }
        $this->results = login([
            'database' => $this,
            'ajaxRequest' => TRUE
        ]);
    }

    /*
    ****************************************************************************
    */

    function sessionRequestJSONController()
    {
        $this->results = login([
            'database' => $this,
            'sessionRequest' => TRUE
        ]);
    }

    /*
    ****************************************************************************
    */

    function datatablesJSONController()
    {
        $this->datatables();
    }

    /*
    ****************************************************************************
    */

    function updateTesterListJSONController()
    {
        $this->datatables();
    }

    /*
    ****************************************************************************
    */

    function dtEditableAddJSONController()
    {
        $model = $this->createModelObject();

        $model->editableAddRow();
    }

    /*
    ****************************************************************************
    */

    function dtEditableJSONController()
    {
        $model = $this->createModelObject();

        $notice = "Your submission is invalid.\nUpdate has failed.";

        $post = $this->post;

        $result = $model->update(
                $post['columnId'], $post['value'], $post['id'], $notice
        );

        if (! $result) {
            return;
        }

        echo $post['value'];
    }

    /*
    ****************************************************************************
    */

    function filterSearcherJSONController()
    {
        $term = getDefault($this->get['term']);

        $field = getDefault($this->get['type']);
        $field or die('No Field');

        $model = $this->createModelObject();

        if (! $model->fields) {
            $model->fields();
        }

        $results = $model->searchByField($field, $term);

        $values = array_keys($results);

        if (! is_string(reset($values))) {
            foreach ($values as &$value) {
                $value = [
                    'value' => $value,
                ];
            }
        }

        $this->results =  $values;
    }

    /*
    ****************************************************************************
    */

    function clearTestDBJSONController()
    {
        if (getDefault($this->post['clearTestDB'])) {
            test\recorder::emptyTestRunDB($this);
        }
    }

    /*
    ****************************************************************************
    */

    function switchIgnoreFieldJSONController()
    {
        test\pages::switchIgnoreField($this);
    }

    /*
    ****************************************************************************
    */

    function getTestingInfoJSONController()
    {
        $term = $this->getVar('term', 'getDef');
        $isTestJSON = $this->getVar('isTest', 'getDef');
        $isTest = json_decode($isTestJSON);

        $series = $isTest ? new \tables\tests\tests($this) :
            new \tables\tests\series($this);

        $secondFiedl = $isTest ? 'displayName' : 'description';

        $this->results = $series->search([
            'term' => [$term, $term],
            'search' => ['id', $secondFiedl],
            'oneResult' => TRUE,
            'glue' => 'OR',
        ]);
    }

    /*
    ****************************************************************************
    */

    function updateRequestIDJSONController()
    {
        $requestID = $this->postVar('requestID', 'getDef');

        $requests = new \tables\tests\requests($this);

        $result = $requests->search([
            'term' => $requestID,
            'search' => 'r.id',
            'oneResult' => TRUE,
        ]);

        $resultID = $result['id'];

        $recordTest = $this->postVar('recordTest', 'getDef');
        $recording = $resultID && json_decode($recordTest);
        $seriesID = $resultID ? $result['seriesID'] : NULL;

        $this->storeSession('seriesID', $seriesID);
        $this->storeSession('requestID', $resultID);
        $this->storeSession('recordTest', $recording);

        $this->results = $result;
    }

    /*
    ****************************************************************************
    */

    function updateSeriesIDJSONController()
    {
        $seriesID = $this->postVar('seriesID', 'getDef');

        $series = new \tables\tests\series($this);

        $result = $series->search([
            'term' => [$seriesID, $seriesID],
            'search' => ['id', 'description'],
            'oneResult' => TRUE,
            'glue' => 'OR',
        ]);

        $resultID = $result['id'];

        if ($resultID) {
            // Deactivate previous requests and inputs when starting a
            // recording for a new series

            $requests = new \tables\tests\requestInputs($this);

            $requests->runUpdate([
                'idField' => 's.id',
                'idSearch' => $resultID,
                'fieldUpdates' => [
                    'r.active' => FALSE,
                    'i.active' => FALSE,
                ],
            ]);
        }

        $recordTest = $this->postVar('recordTest', 'getDef');
        $recording = $resultID && json_decode($recordTest);

        $this->storeSession('testID', FALSE);
        $this->storeSession('seriesID', $resultID);

        $this->storeSession('requestCount', 0);
        $this->storeSession('recordTest', $recording);

        ! $recording ? $this->storeSession('requestID', FALSE) : NULL;

        $this->results = $result;
    }

    /*
    ****************************************************************************
    */

    function changeTestModeJSONController()
    {
        $_SESSION['testMode'] = $this->get['mode'];

        unset($_SESSION['seriesID']);

        $results = FALSE;

        if ($_SESSION['testMode'] == 'run') {

            $testSeries = new \tables\tests\testSeries($this);

            $results = $testSeries->getCases();
        }

        $this->results = $results;
    }

    /*
    ****************************************************************************
    */

    function autocompleteJSONController()
    {
        $model = $this->createModelObject();

        $term = getDefault($this->get['term']);
        $field = getDefault($this->get['field']);
        $secondField = getDefault($this->get['secondField']);

        $field or die('No Field');

        $results = $model->searchByField($field, $term, $secondField);

        $values = $results ? array_keys($results) : [];

        if (! is_string(reset($values))) {
            foreach ($values as &$value) {
                $value = [
                    'value' => $value,
                ];
            }
        }

        $this->results =  $values;
    }

    /*
    ****************************************************************************
    */

    function mailerJSONController()
    {
        $this->results = PHPMailer\send::jsonAPI($this);
    }

    /*
    ****************************************************************************
    */

    function ajaxErrorSubmitJSONController()
    {
        $className = getDefault($this->post['classUrl']);
        $message = getDefault($this->post['message']);

        $numberDate = config::getDateTime('numberDate');
        $dateTime = date("Y-m-d H:i:s");

        $filePath = directories::getDir('logs', 'errorAjax');

        $file = $filePath.'/'.$numberDate.'.log';
        $content = $dateTime.' - Error: '.$message.' in Class: '.$className."\n";

        file_put_contents($file, $content, FILE_APPEND | LOCK_EX);

        return $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function runDBUpdateJSONController()
    {
        $command = dbCommands::get($this);

        $this->results = $command['pdo']->runQuery($command['results']['sql']);
    }

    /*
    ****************************************************************************
    */

    function runDBCheckJSONController()
    {
        $command = dbCommands::get($this);

        $results = $command['pdo']->queryResults(
            $command['results']['check'], [], pdo::FETCH_ASSOC
        );

        // Validate pass value using PHP
        $passed = dbCommands::runCallback($command['results'], $results);

        $this->results = [
            'passed' => $passed,
            'results' => json_encode($results, JSON_PRETTY_PRINT),
        ];
    }

    /*
    ****************************************************************************
    */

    function toggleTesterSessionJSONController()
    {
        $viewPropertiesJSON = $this->postVar('viewProperties', 'getDef');

        $toggleJSON = getDefault($this->post['toggle']);
        $toggleOn = json_decode($toggleJSON);

        $viewProperties = json_decode($viewPropertiesJSON);

        if ($viewPropertiesJSON) {
            $this->storeSession('viewProperties', $toggleOn);
            return $this->results = $viewProperties;
        }

        $this->storeSession('seriesID', 0);

        $access = accessCheck($this, 'developer');
        $tester = $_SESSION['tester'] = $access && $toggleOn;

        $this->results = [
            'noAccess' => ! $access,
            'on' => $tester,
        ];
    }

    /*
    ****************************************************************************
    */

    function addTestJSONController()
    {
        $this->results = dbCommandsJSON::add($this);
    }

    /*
    ****************************************************************************
    */

    function removeTestJSONController()
    {
        $appName = 'tests';

        $testName = $this->post['testName'];

        $tests = new \tables\tests\tests($this);

        $testResult = $tests->getByName($testName);

        if ($testResult) {

            $json = dbCommands::getJsonCommands($appName);

            $jsonFile = $json['file'];
            $jsonData = $json['data'];

            $testID = $testResult['id'];

            unset($jsonData[$testID]);

            $file = fopen($jsonFile, 'w');

            fwrite($file, json_encode($jsonData));

            fclose($file);

            $this->results = [
                'action' => 'removed',
            ];
        } else {
            $this->results = FALSE;
        }
    }

    /*
    ****************************************************************************
    */

    function getDirJSONController()
    {
        $post = $this->getArray('post');
        $this->results = directories::getDir($post['type'], $post['assoc']);
    }

    /*
    ****************************************************************************
    */

}