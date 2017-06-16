<?php

namespace test;

use \models\config;
use \models\directories;

class pages
{
    const DEBUG = FALSE;

    public $testID = 0;
    public $seriesID = 0;
    public $requetsID = 0;
    
    static $targetDB;

    public $testDB;

    public $testInfo = [];

    public $children = [
        'element' => '[',
        'property' => '->',
    ];

    public $skipRequests = [
        'menuMain' => TRUE,
        'listTester' => TRUE,
        'clearTestDBJson' => TRUE,
        'updateSeriesIDJson' => TRUE,
        'changeTestModeJson' => TRUE,
        'updateTesterListJson' => TRUE,
        'changeTestRecordModeJson' => TRUE,
        'autoSaveContainerAppJson' => TRUE,
    ];

    // Don't record these session variables while test recording

    public $skipSessions = [
        'token' => TRUE,
        'tester' => TRUE,
        'appName' => TRUE,
        'request' => TRUE,
        'seriesID' => TRUE,
        'testMode' => TRUE,
        'onScanner' => TRUE,
        'requestID' => TRUE,
        'recordTest' => TRUE,
        'queryString' => TRUE,
        'testRunMode' => TRUE,
        'requestCount' => TRUE,
        'autoSaveContainer' => TRUE,
        
        'requestID' => TRUE,

    ];

    public $errorHTML = NULL;

    /*
    ****************************************************************************
    */

    function __construct($mvc, $afterController=FALSE)
    {
        $mvc->includeJS['js/test/pages.js'] = TRUE;
        $mvc->includeCSS['css/test/pages.css'] = TRUE;

        $appEnv = config::get('site', 'appEnv');

        if ($appEnv == 'production') {
            // do not run tests on production
            return;
        }

        self::debug($_SESSION);


        $isTester = \access::isTester($mvc);
        self::debug(['isTester' => $isTester]);

        if (! $isTester) {
            // allow "tester" user with "Developer" access level only
            return;
        }

        $this->mvc = $mvc;

        $this->showMVCProperties($afterController);

        $pageRequest = \appConfig::get('site', 'requestPage');

        self::debug([
            'method' => $pageRequest,
            'skipRequests' => $this->skipRequests,
        ]);

        if (isset($this->skipRequests[$pageRequest])) {
            return;
        }

        // Record / overwrite test values if recordRequestID is set
        // Check against previous values if seriesID is set
        $testID = $this->testID = $mvc->sessionVar('testID', 'getDef');
        $seriesID = $this->seriesID = $mvc->sessionVar('seriesID', 'getDef');
        $requestID = $this->requestID = $mvc->sessionVar('requestID', 'getDef');
        $testRunMode = $mvc->sessionVar('testRunMode', 'getDef');

        $recordTest = $mvc->sessionVar('recordTest', 'getDef');
        
        $recordMode = $recordTest || $testID;
        $comparing = $testRunMode == 'compare';

        self::debug([
            '$testID' => $testID,
            'seriesID' => $seriesID,
            'recordingSession' => $recordMode,
        ]);
        
        if ($recordMode && ! $requestID && ! $seriesID) {
            // "test record" mode needs requestID
            return;
        }

        $recording = $recordMode && ($requestID || $seriesID);

        $this->testDB = $this->mvc->getDB(['dbAlias' => 'tests']);

        $request = $this->getTest([
            'types' => 'get',
            'requestID' => $requestID,
        ]);

        self::debug([
            'request' => $request,
            'recording' => $recording,
        ]);

        if (! $recording && ! $request) {
            return;
        }

        self::debug([
            'running' => ! $recording,
            'recording' => $recording,
            'beforeController' => ! $afterController,
            'afterController' => $afterController,
        ]);
        
        $requestGet = getDefault($request['get']['outputName']);
        
        $recordingTestResults = ! $comparing && $recording && 
            $afterController && $testID;
        
        // Only compare the last request of a series
        $lastRequest = FALSE;
        if  ($comparing && $afterController && $requestID 
        || $recordingTestResults
        ) {
            $sql = 'SELECT   id
                    FROM     series_requests
                    WHERE    seriesID = ?
                    AND      active
                    ORDER BY id DESC
                    LIMIT    1';

            $result = $this->testDB->queryResult($sql, [$this->seriesID]);
            $lastRequest = $requestID == $result['id'];
        }
        
        // Only do this when recording a testing
        $lastRequest && $recordingTestResults ? 
            $this->updateTestResults($requestGet) : NULL;

        // Don't want to add more series requests when testing
        $recording && ! $afterController && ! $testID ? 
            $this->recordInput() : NULL;
        
        ! $recording && ! $afterController ? $this->setInputs() : NULL;
        
        $afterController && $lastRequest && $requestID ? 
            $this->compare($requestGet) : NULL;
        
        $recording && $testID ? $this->setInputs() : NULL;
    }

    /*
    ****************************************************************************
    */

    function debug($values)
    {
        self::DEBUG ? varDump($values) : NULL;
    }

    /*
    ****************************************************************************
    */

    function setInputs()
    {
        // Set the input values before running a test
        $results = $this->getTest([
            'types' => ['post', 'files', 'session'],
            'requestID' => $this->requestID,
        ]);

        foreach ($results as $type => $row) {
            
            $values = json_decode($row['json'], 'array');

            if (! $values) {
                continue;
            }
            
            $type == 'files' ? $this->useStoredFiles($values) : NULL;

            $previous = $this->mvc->getArray($type);
            
            $merged = array_merge($values, $previous);
            
            $this->mvc->setArray($type, $merged);
        }
    }

    /*
    ****************************************************************************
    */

    function useStoredFiles(&$files)
    {
        $key = key($files);
        $quantity = count($files[$key]['tmp_name']);
        $files[$key]['tmp_name'] = $this->testFileNames([
            'quantity' => $quantity,
            'possiblyOne' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

    function testFileNames($params)
    {
        $fileNumber = getDefault($params['fileNumber']);
        
        $range = isset($params['quantity']) ? 
            range(1, $params['quantity']) : [$fileNumber];
        
        $filenames = [];
        
        foreach ($range as $fileNumber) {
            $filenames[] = directories::getDir('uploads', 'testUploads') .
                '/request'.$this->mvc->sessionVar('seriesID').'file'.$fileNumber;
        }

        return isset($params['possiblyOne']) && count($filenames) == 1 ? 
            reset($filenames) : $filenames;
    }

    /*
    ****************************************************************************
    */

    function recordInput()
    {
        $requestCount = $this->mvc->sessionVar('requestCount');

        $series = new \tables\tests\requests($this->testDB);
        
        // Get the next request ID of this series or create it
        $row = $series->search([
            'term' => $this->seriesID,
            'limit' => $requestCount.', 1',
            'search' => 's.id',
            'orderBy' => 'r.id ASC',
            'oneResult' => TRUE,
        ]);

        $requestID = $row['id'] ? $row['id'] : 
            $series->getNextID('series_requests');
        
        if ($row['id']) {
            $sql = 'UPDATE  series_requests
                    SET     isJSON = ?,
                            active = 1
                    WHERE   id = ?';

            $this->testDB->runQuery($sql, [
                config::get('site', 'jsonRequest'),
                $requestID,
            ]);
        } else {
            $sql = 'INSERT INTO series_requests (seriesID, isJSON) 
                    VALUES (?, ?)';

            $this->testDB->runQuery($sql, [
                $this->seriesID,
                config::get('site', 'jsonRequest'),
            ]);
        }

        foreach (['get', 'post', 'files', 'session'] as $name) {

            $values = $this->mvc->getArray($name);
            
            $diff = $name == 'session' ? 
                array_diff_key($values, $this->skipSessions) : $values;

            if (! $diff) {
                continue;                
            }

            $diff && $name == 'files' ? new \files\move([
                'app' => $this->mvc,
                'model' => $this,
            ]) : NULL;
            

            $sql = 'INSERT INTO request_inputs (
                        requestID,
                        json,
                        type
                    ) VALUES (
                        ?, ?, ?
                    )
                    ON DUPLICATE KEY UPDATE
                        json = VALUES(json),
                        active = 1';

            $this->testDB->runQuery($sql, [
                $requestID,
                json_encode($diff),
                $name,
            ]);
        }
        
        $this->mvc->storeSession('requestCount', $requestCount + 1);
   
    }

    /*
    ****************************************************************************
    */

    function updateTestResults($requestGet)
    {
        $currentData = $this->parseVarName($requestGet);
        $json = json_encode($currentData);

        // Give notice if JSON is empty on the last series requests
        if (! $json) { ?>
            <span class="failedMessage">
                Invalid test series. Last request did not yield a result
            </span><?php
        }
        
        $sqk = 'SELECT id
                FROM   test_results
                WHERE  testID = ?
                AND    seriesID = ?';
        
        $result = 
            $this->testDB->queryResult($sqk, [$this->testID, $this->seriesID]);
        
        $sql = $result ? 
               'UPDATE test_results
                SET    json = ?,
                       active = 1
                WHERE  testID = ?
                AND    seriesID = ?' : 
               'INSERT INTO test_results (json, testID, seriesID) 
                VALUES (?, ?, ?)';

        return $this->testDB->runQuery($sql, [
            $json,
            $this->testID, 
            $this->seriesID,
        ]);
    }

    /*
    ****************************************************************************
    */

    function compare($requestGet)
    {
        $seriesID = $this->seriesID;

        $ignoreFields = new \tables\tests\ignoreFields($this->testDB);

        // No output when running a request series
        $result = $this->getTestResult();

        $old = json_decode($result['json'], TRUE);

        $testIgnoreFields = $ignoreFields->getTestIgnoreFields($seriesID);

        $ignoreFieldKeys = array_flip($testIgnoreFields);

        $isExpectedArray = is_array($old);

        $currentData = $this->parseVarName($requestGet);
        
        $isActualArray = is_array($currentData);

        if ($old == $currentData) {
            if (! $isExpectedArray) {

                ob_start(); ?>

                <table><tr>
                    <td class="testCells" valign="top"><pre>
                        <?php echo json_encode($old, JSON_PRETTY_PRINT); ?>
                    </pre></td>
                </tr></table>

                <?php

                $html = ob_get_clean();

                return $this->errorHTML = $html;
            }
        } else {

            $first = $isExpectedArray ? $old : [
                        $result['outputName'] => $old
                    ];

            $second = $isActualArray ? $currentData : [
                        $result['outputName'] => $currentData
                    ];

            $output = $this->arrayRecursiveOutput($first, $second);

            ob_start(); ?>

            <a href="#" class="toggleErrorRows">Discrepancies only</a>

            <?php

            $isError = $this->displayTestResults([
                'output' => $output,
                'ignoreFieldKeys' => $ignoreFieldKeys,
                'isTop' => TRUE,
            ]);

            $html = ob_get_clean();

            if ($isError) {
                // display error table if there are unsuppressed errors
                return $this->errorHTML = $html;
            }
        }
    }

    /*
    ****************************************************************************
    */

    function displayTestResults($data)
    {
        $output = $data['output'];
        $ignoreFieldKeys = $data['ignoreFieldKeys'];
        $isTop = getDefault($data['isTop']);
        $parent = getDefault($data['parent'], NULL);
        $isError = getDefault($data['isError']);

        $style = $isTop ? NULL : 'border-style: hidden;';
        $subClass = $isTop ? NULL : 'arrayTable'; ?>

        <table border="1" class="resultTable <?php echo $subClass; ?>"
               style="<?php echo $style; ?>"
               data-series-id="<?php echo $this->seriesID; ?>">

        <?php

        $count = 0;

        foreach ($output as $key => $values) {
            if (array_key_exists('first', $values)) {
                if (! $count++) { ?>

                <tr>
                    <th width="130px">Field</th>
                    <th>Expected Value</th>
                    <th>Actual Value</th>
                    <th width="100px">Ignore Error</th>
                </tr>

                <?php }

                $isError = $this->outputRow([
                    'key' => $key,
                    'values' => $values,
                    'parent' => $parent,
                    'isTop' => $isTop,
                    'ignoreFieldKeys' => $ignoreFieldKeys,
                    'isError' => $isError,
                ]);
            } else { ?>

            <tr>
                <td width="10%" class="toggleCell">
                    <input type="button" value="+" class="collapseExpand"
                           style="display: none;">
                    <input type="button" value="-" class="collapseExpand">

                    <?php echo $key; ?>

                </td>
                <td width="90%" colspan="3">

                <?php

                $parent = $isTop ? $key : $parent . '[\'' . $key + '\']';

                $isError = $this->displayTestResults([
                    'output' => $values,
                    'ignoreFieldKeys' => $ignoreFieldKeys,
                    'parent' => $parent,
                    'isError' => $isError,
                ]); ?>

                </td>
            </tr>

            <?php }
        } ?>

        </table>

        <?php

        return $isError;
    }

    /*
    ****************************************************************************
    */

    function outputRow($data)
    {
        $key = $data['key'];
        $values = $data['values'];
        $parent = $data['parent'];
        $isTop = getDefault($data['isTop']);
        $ignoreFieldKeys = $data['ignoreFieldKeys'];
        $isError = $data['isError'];

        $first = ! is_array($values['first']) ? $values['first'] :
                '<pre>' . json_encode($values['first'], JSON_PRETTY_PRINT) . '</pre>';

        $second = ! is_array($values['second']) ? $values['second'] :
                '<pre>' . json_encode($values['second'], JSON_PRETTY_PRINT) . '</pre>';

        $field = $isTop ? $key : $parent . '[\'' . $key . '\']';

        $ignoreError = isset($ignoreFieldKeys[$field]);

        $passed = $values['first'] == $values['second'];

        $rowClass = 'validRow';

        if (! $passed && ! $ignoreError) {

            $rowClass = 'errorRow';

            $isError = TRUE;
        } ?>

        <tr class="<?php echo $rowClass; ?>">
            <td width="130px"><?php echo $key; ?></td>
            <td><?php echo $first; ?></td>
            <td><?php echo $second; ?></td>
            <td width="100px">

        <?php if (! $passed) {

            $caption = $ignoreError ? 'Consider Error' : 'Ignore Error'; ?>

                <input type="button" class="switchIgnore"
                       value="<?php echo $caption; ?>"
                       data-field="<?php echo $field; ?>"
                       title="Useful for false-flag variables like dates">

        <?php } ?>

            </td>
        </tr>

        <?php

        return $isError;
    }

    /*
    ****************************************************************************
    */

    function arrayRecursiveOutput($first, $second)
    {
        $output = [];

        foreach ($first as $firstKey => $firstValue) {

            $secondValue = getDefault($second[$firstKey], NULL);

            $isFirstArray = is_array($firstValue);
            $isSecondArray = is_array($secondValue);

            if ($isFirstArray || $isSecondArray) {

                $output[$firstKey] = $isFirstArray && $isSecondArray ?
                        $this->arrayRecursiveOutput($firstValue, $secondValue) :
                        [
                            'first' => $firstValue,
                            'second' => $secondValue,
                        ];

                unset($first[$firstKey]);
                unset($second[$firstKey]);

                continue;
            }

            if (! array_key_exists($firstKey, $second)) {

                $output[$firstKey] = [
                    'first' => $firstValue,
                    'second' => NULL,
                ];

                unset($first[$firstKey]);

                continue;
            }

            foreach ($second as $secondKey => $secondValue) {
                if ($firstKey == $secondKey) {

                    $output[$secondKey] = [
                        'first' => getDefault($first[$secondKey], NULL),
                        'second' => $secondValue,
                    ];

                    unset($first[$secondKey]);
                    unset($second[$secondKey]);

                    break;
                }
            }
        }

        foreach ($second as $secondKey => $secondValue) {

            $isSecondArray = is_array($secondValue);

            if ($isSecondArray) {

                $output[$secondKey] = $this->arrayRecursiveOutput([], $secondValue);

                unset($second[$secondKey]);

                continue;
            }

            foreach ($second as $secondKey => $secondValue) {

                $output[$secondKey] = [
                    'first' => NULL,
                    'second' => $secondValue,
                ];

                unset($second[$secondKey]);
            }
        }

        return $output;
    }

    /*
    ****************************************************************************
    */

    function parseVarName($varName)
    {
        if (! $varName) {
            return FALSE;
        }

        // First child will be a property of the request object
        $indicators = ['property'];

        // Won't need closing key indicators
        $string = str_replace(['\']', ']'], NULL, $varName);

        // Make an array to track order of properies / elements
        foreach ($this->children as $child => $indicator) {
            $offset = 0;
            while ($pos = strpos($string, $indicator, $offset++)) {
                $indicators[$pos] = $child;
            }
        }

        ksort($indicators);
        $indicators = array_values($indicators);

        // Make all indicators the same to explode string and get children names
        $string = str_replace(['->', '[\''], '[', $string);
        $varNames = explode('[', $string);

        // Parse the variable children
        $value = $this->mvc;

        foreach ($indicators as $key => $indicator) {
            $varName = $varNames[$key];
            switch ($indicator) {
                case 'element':
                    $value = $value[$varName];
                    break;
                case 'property':
                    $value = $value->$varName;
                    break;
            }
        }

        return $value;
    }

    /*
    ****************************************************************************
    */

    static function switchIgnoreField($app)
    {
        $testDB = $app->getDB(['dbAlias' => 'tests']);

        $active = ! json_decode($app->post['isError']);

        $sql = 'INSERT INTO ignore_fields (
                    seriesID,
                    ignoreField,
                    active
                ) VALUES (
                    ?, ?, ?
                ) ON DUPLICATE KEY
                UPDATE active = ?';

        $testDB->runQuery($sql, [
            $app->post['seriesID'],
            $app->post['ignoreField'],
            $active,
            $active,
        ]);
    }

    /*
    ****************************************************************************
    */

    function getTest($params)
    {
        $seriesID = getDefault($params['seriesID']);
        $id = getDefault($params['requestID'], $seriesID);
        
        $searchField = $seriesID ? 's.id' : 'r.id';
        
        $types = $params['types'];
        $queryParams = is_array($types) ? $types : [$types];
        
        $sql = 'SELECT    type,
                          json, 
                          outputName
                FROM      series s
                LEFT JOIN series_requests r ON r.seriesID = s.id
                LEFT JOIN request_inputs i ON i.requestID = r.id
                WHERE     '.$searchField.' = ?
                AND       r.active
                AND       i.active
                AND       i.type IN ('.$this->mvc->getQMarkString($queryParams).')';

        array_unshift($queryParams, $id);
        
        $this->testInfo = $this->testDB->queryResults($sql, $queryParams);

        return $this->testInfo;
    }

    /*
    ****************************************************************************
    */

    function getTestResult()
    {
        $sql = 'SELECT r.id,
                       outputName,
                       json
                FROM   test_results r 
                JOIN   series s ON s.id = r.seriesID
                WHERE  testID = ?
                AND    seriesID = ?';

        $result = $this->testDB->queryResult($sql, [
            $this->testID, 
            $this->seriesID,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function showMVCProperties($afterController)
    {
        $jsonRequest = config::get('site', 'jsonRequest');
        $viewPropertySession = 
            $this->mvc->sessionVar('viewProperties', 'getDef');
        
        if (! $afterController || $jsonRequest || ! $viewPropertySession) {
            return;
        }

        $properties = get_object_vars($this->mvc);
        $filtered = array_filter($properties);
        ksort($filtered);
        foreach ($filtered as $key => $property) {

            ?>    
            <div class="toggleParamDisplay">
            <div>+ <?php echo $key; ?></div>
            <div class="divShow hidden">
            <pre><?php 
            $propString = varDump($property, ['printMode' => TRUE]);
            echo htmlentities($propString);
            ?></pre>
            </div>
            </div>
            <?php
        }
    }

    /*
    ****************************************************************************
    */

    function errors()
    {
        return $this->errorHTML;
    }

    /*
    ****************************************************************************
    */

}
