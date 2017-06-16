<?php

namespace test;

class recorder
{
    const OUTPUT_TABLE_BY_NAME = NULL;
    const DEBUG = FALSE;

    private $testMenu = [
        'series' => 'Request Series',
        'requests' => 'Requests',
        'requestInputs' => 'Request Inputs',
        'tests' => 'Test',
        'testSeries' => 'Test Series',
        'results' => 'Test Results',
        'ignoreFields' => 'Ignore Fields',
    ];

    private $links = [];

    // Tables that will be emptied before a test run with starting increments
    static private $emptyTables = [
        'adjustment_logs' => 1,
        'client_emails' => 1,
        'consolidation_waves' => 1,
        'consolidations' => 1,
        'costs' => 1,
        'group_pages' => 1,
        'client_users' => 1,
        'groups' => 1,
        'history' => 1,
        'history_values' => 1,
        'inventory_batches' => 10000001,
        'inventory_cartons' => 1,
        'inventory_containers' => 10000001,
        'inventory_control' => 1,
        'inventory_merge_converse' => 1,
        'inventory_splits' => 1,
        'inventory_unsplits' => 1,
        'label_batches' => 1,
        'licenseplate' => 10000001,
        'logs_adds' => 1,
        'logs_cartons' => 1,
        'logs_orders' => 1,
        'logs_scan_input' => 1,
        'logs_values' => 1,
        'logs_workorders' => 1,
        'masterlabel' => 1,
        'min_max' => 1,
        'min_max_ranges' => 1,
        'neworder' => 1,
        'neworderlabel' => 100001,
        'nsi' => 1,
        'nsi_po_batches' => 1,
        'nsi_pos' => 1,
        'nsi_receiving' => 1,
        'nsi_receiving_pallets' => 1,
        'nsi_shipping' => 1,
        'nsi_shipping_batches' => 1,
        'online_orders' => 1,
        'online_orders_exports' => 1,
        'online_orders_exports_bill_to' => 1,
        'online_orders_exports_orders' => 1,
        'online_orders_exports_packages' => 1,
        'online_orders_exports_providers' => 1,
        'online_orders_exports_services' => 1,
        'online_orders_exports_signatures' => 1,
        'online_orders_fails' => 1,
        'online_orders_fails_update' => 1,
        'order_batches' => 1000001,
        'order_picks_fails' => 1,
        'orders_shipping_info' => 1,
        'pallet_sheet_batches' => 1,
        'pallet_sheets' => 1,
        'pick_cartons' => 1,
        'pick_errors' => 1,
        'pick_orders' => 1,
        'pick_waves' => 1,
        'plate_batches' => 100001,
        'receiving_numbers' => 1,
        'receivings' => 1,
        'receiving_attachment' => 1,
        'receiving_containers' => 1,
        'reports' => 1,
        'receivings' => 1,
        'reports_data' => 1,
        'tallies' => 1,
        'tally_cartons' => 1,
        'tally_rows' => 1,
        'transfer_cartons' => 1,
        'transfer_items' => 1,
        'transfers' => 1,
        'upcs' => 1,
        'upcs_assigned' => 1,
        'upcs_checkout' => 1,
        'user_groups' => 1,
        'vendors' => 10001,
        'workorder' => 1,
        'workorderlabel' => 100001,
    ];

    public $testMode = FALSE;

    /*
    ****************************************************************************
    */

    function __construct($mvc)
    {
        if (! accessCheck($mvc, 'developer')) {
            return NULL;
        }

        $this->testMode = \access::isTester($mvc);

        $this->mvc = $mvc;
        $mvc->includeJS['js/test/recorder.js'] = TRUE;
        $mvc->includeCSS['css/test/recorder.css'] = TRUE;

        $mvc->jsVars['urls']['toggleTesterSessionJSON'] =
            jsonLink('toggleTesterSession');
        $mvc->jsVars['viewProperties'] =
            $mvc->sessionVar('viewProperties', 'getDef');

        $mvc->jsVars['urls']['clearTestDB'] = jsonLink('clearTestDB');
        $mvc->jsVars['urls']['testAutocomplete'] = jsonLink('autocomplete', [
            'modelName' => 'tests\\tests',
            'field' => 'displayName',
            'secondField' => 'id',
        ]);
        $mvc->jsVars['urls']['seriesAutocomplete'] = jsonLink('autocomplete', [
            'modelName' => 'tests\\series',
            'field' => 'description',
            'secondField' => 'id',
        ]);

        $mvc->jsVars['urls']['updateSeriesID'] = jsonLink('updateSeriesID');
        $mvc->jsVars['urls']['updateRequestID'] = jsonLink('updateRequestID');

        $mvc->jsVars['urls']['changeTestMode'] = jsonLink('changeTestMode');
        $mvc->jsVars['urls']['runSeries'] = jsonLink('seriesID');
        $mvc->jsVars['urls']['getTestingInfo'] = jsonLink('getTestingInfo');


        $mvc->jsVars['urls']['changeTestRecordMode'] =
                jsonLink('changeTestRecordMode');
        $mvc->jsVars['urls']['runTests'] = jsonLink('runTests');
        $mvc->jsVars['urls']['runPageTests'] = makeLink('tester', 'pages');

        $mvc->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->setterHTML();
    }

    /*
    ****************************************************************************
    */

    function setterHTML()
    {
        $seriesID = $this->mvc->sessionVar('seriesID', 'getDef');

        $this->createLinks();

        $runTestsLink = $this->links['runTests'];

        $showTester = $this->testMode ? NULL : 'hidden';

        ob_start(); ?>

        <div id="testSetter" class="<?php echo $showTester; ?>">
            <div id="testerMenu">
                <input type="button" id="expandButton" style="display: none;"
                       class="collapseExpand ui-icon ui-icon-plus"
                       data-counter="collapseButton">
                <input type="button" id="collapseButton"
                       class="collapseExpand ui-icon ui-icon-minus"
                       data-counter="expandButton">
                <span class="collapsable">
                <?php
                $count = 0;
                foreach($this->testMenu as $table => $caption) {
                    echo $count++ == 4 ? '<br>' : NULL; ?>

                        <a href="<?php echo $this->links['tables'][$table]; ?>"
                           target="<?php echo $table; ?>" class="message"><?php echo $caption; ?></a>

                <?php } ?>
                <a id="endSession" class="message">End Session</a>
                </span>
            </div>
            <div id="tester" class="collapsable">
                <div id="testMode" class="message">
                    <select id="testingOptions">
                        <option>Select Testing Option</option>
                        <option data-mode="record"
                                data-type="series">Record Request Series</option>
                        <option data-mode="run"
                                data-type="series">Run Request Series</option>
                        <option data-mode="record"
                                data-type="test">Record Test Results</option>
                        <option data-mode="compare"
                                data-type="test">Run Test Comparison</option>
                    </select>
                </div>
                <div id="recordMode">
                    <div id="instructions" class="testOptions">Enter Description or ID</div>
                    <input id="seriesID" class="testOptions" value="<?php echo $seriesID; ?>"
                           placeholder="(autocomplete)">
                    <input id="testID" class="testOptions" value="<?php echo $seriesID; ?>"
                           placeholder="(autocomplete)">
                    <button id="recordButton" class="testOptions">
                        Record</button>
                    <button id="runButton" class="testOptions">
                        Run</button>
                    <div id="series" class="successMessage testOptions"></div>
                    <button id="switchRecordMode" class="testOptions">Start Recording</button>
                    <hr class="testOptions">
                    <div>
                    <span class="testerInfo ui-icon ui-icon-info" title="Empty
                          all test tables that can be populated by WMS users and
                          copy all other tables from your local WMS database.
                          Tables that will emptied include inventory tables and
                          clients. Tables that will be copied over include
                          locations and original UPCs"></span>
                    <button id="clearTestDB">Reset Test Database</button>
                    </div>
                    <div>
                    <span class="testerInfo ui-icon ui-icon-info" title="Display
                          MVC Request Properties for non-AJAX requests. This can
                          be used in advance to find which information you will
                          be recording when creating a test series."></span>
                    <button id="viewProperties">View Request Properties</button>
                    </div>
                </div>
                <div id="runMode" style="display: none;">
                    <table>
                        <tr>
                            <td class="testMenuPages">Test:</td>
                            <td class="testMenuPages">
                                <select id="tests"></select>
                            </td>
                        </tr>
                    </table>
                    <hr>
                    <a href="<?php echo $runTestsLink; ?>" target="_blank"
                       id="runTestsLink" class="message"
                       data-link="<?php echo $runTestsLink; ?>">Run Tests</a>
                </div>
            </div>
        </div>

        <?php

        return $this->mvc->testRecorderSetterHTML = ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function createLinks()
    {
        $this->links['runTests'] = makeLink('tester', 'pages');

        $testClasses = array_keys($this->testMenu);

        foreach($testClasses as $class) {
            $this->links['tables'][$class] = makeLink('tester', 'list', [
                'show' => $class,
                'editable' => 'display'
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    static function emptyTestRunDB($mvc)
    {
        $testRunDB = $mvc->getDBName('testRuns');
        $sourceDB = $mvc->getDBName();

        $qMarks = $mvc->getQMarkString(self::$emptyTables);

        $tables = array_keys(self::$emptyTables);

        $badStructures = $results = [];

        $schemaClause =
            'WHERE table_schema IN ("'.$sourceDB.'", "'.$testRunDB.'")';

        $clauses = $schemaClause.' AND table_name IN ('.$qMarks.')';

        foreach ([
            [
                'name' => 'hadTables',
                'sql' => 'SELECT   table_name,
                                    table_schema,
                                    auto_increment
                          FROM     information_schema.tables
                          '.$schemaClause.'
                          -- Get test DB values second
                          ORDER BY FIELD (table_schema, "'.$testRunDB.'")',
                'fetchType' => \pdo::FETCH_ASSOC,
            ], [
                'name' => 'badStructures',
                'sql' => 'SELECT column_name,
                                 table_name
                          FROM   information_schema.columns
                          '.$schemaClause.'
                          GROUP BY column_name,
                                   ordinal_position,
                                   data_type,
                                   table_name,
                                   column_type
                          HAVING   COUNT(1) = 1',
                'fetchType' => \pdo::FETCH_ASSOC,
            ], [
                'name' => 'badIndexes',
                'sql' => 'SELECT    index_name,
                                    table_name
                          FROM      information_schema.statistics
                          '.$clauses.'
                          GROUP BY index_name,
                                    table_name,
                                    column_name,
                                    seq_in_index
                          HAVING    COUNT(1) = 1',
                'fetchType' => \pdo::FETCH_ASSOC,
            ]
        ] as $row) {
            $name = $row['name'];
            $results[$name] =
                $mvc->queryResults($row['sql'], $tables, $row['fetchType']);
        }

        $testAIs = $rowCounts = $sourceAIs = $aiMismatches = $badRowCount =
            $missingTestTables = $allTables = $subqueries = [];

        $subqueryCount = 0;
        foreach ($results['hadTables'] as $row) {

            $subqueries[] = 'SELECT "'.$subqueryCount++.'" AS id,
                                    "'.$row['table_name'].'" AS tableName,
                                    "'.$row['table_schema'].'" AS dbName,
                                    COUNT(1) AS rowCount
                             FROM   '.$row['table_schema'].'.'.$row['table_name'];
        }

        $sql = ' '.implode(' UNION ', $subqueries);
        $rowCountResults = $mvc->queryResults($sql);
        foreach ($rowCountResults as $row) {
            $table = $row['tableName'];
            $db = $row['dbName'];
            $rowCounts[$db][$table] = $row['rowCount'];
        }

        foreach ($results['hadTables'] as $row) {
            $allTables[] = $table = $row['table_name'];
            $autoIncrement = $row['auto_increment'];
            if ($row['table_schema'] == $sourceDB) {
                $missingTestTables[$table] = TRUE;
                $sourceAIs[$table] = $autoIncrement;
            } else {
                unset($missingTestTables[$table]);

                $testAIs[$table] = $autoIncrement;

                $hasDefaultAI = getDefault(self::$emptyTables[$table]);

                // Not if there is a default AI and rows are 0
                $badRowCount[$table] =
                    $hasDefaultAI &&  $rowCounts[$testRunDB][$table] ||
                    ! $hasDefaultAI &&
                    $rowCounts[$sourceDB][$table] != $rowCounts[$testRunDB][$table];

                $aiMismatches[$table] =
                    $autoIncrement &&
                    $sourceAIs[$table] != $autoIncrement;
            }
        }

        $allTablesUnique = array_unique($allTables);

        foreach (['badStructures', 'badIndexes'] as $error) {
            foreach ($results[$error] as $row) {
                $tableName = $row['table_name'];
                $badStructures[$tableName] = TRUE;
            }
        }

        $mvc->beginTransaction();

        $insertSelects = [];

        foreach ($allTablesUnique as $table) {

            $autoIncrement = getDefault(self::$emptyTables[$table]);

            $target = $testRunDB . '.' . $table;
            $source = $sourceDB . '.' . $table;

            $missingTestTable = isset($missingTestTables[$table]);
            $defaultAI = getDefault(self::$emptyTables[$table]);

            $hasDefaultAI = $missingTestTable ? FALSE :
                    $defaultAI == $testAIs[$table];

            $aiMismatch = getDefault($aiMismatches[$table]);
            $badStructure = isset($badStructures[$table]);

            $dropTable = ! $missingTestTable && $badStructure;

            if ($dropTable) {
                $sql = 'DROP TABLE IF EXISTS ' . $target;
                self::runQuery($mvc, $sql);
            }

            $copyStructure = $dropTable && ! $aiMismatch || $missingTestTable;

            if ($copyStructure) {
                $sql = 'CREATE TABLE ' . $target . ' LIKE ' . $source;
                self::runQuery($mvc, $sql);

                // Update whether matching with 1 the new AI
                $aiMismatch = $sourceAIs[$table] != 1;
            }

            // Don't copy if its an empty table

            $badRow = ! isset($badRowCount[$table]) || $badRowCount[$table];
            if (! $defaultAI && $badRow) {
                $insertSelects[] = 'INSERT INTO '.$target.'
                                    SELECT *
                                    FROM   '.$source;
            }

            $emptyTable = ! $missingTestTable && ! $badStructure &&
                ! $hasDefaultAI && $badRowCount[$table];

            if ($emptyTable) {
                $sql = 'TRUNCATE ' . $target;
                self::runQuery($mvc, $sql);
            }

            $changeAI = $defaultAI && (
                $emptyTable || $missingTestTable && ! $hasDefaultAI
            );

            if ($changeAI) {
                $sql = 'ALTER TABLE ' . $target . ' AUTO_INCREMENT = '
                        . $autoIncrement;
                self::runQuery($mvc, $sql);
            }

            self::tableDump($table, [
                '$table' => $table,
                '$missingTestTables' => isset($missingTestTables[$table]),
                '$badStructures' => isset($badStructures[$table]),
                '$hasDefaultAI' => $hasDefaultAI,
                '$defaultAI' => getDefault(self::$emptyTables[$table]),
                '$aiMismatch' => $aiMismatches[$table],
                '$dropTable' => $dropTable,
                '$copyStructure' => $copyStructure,
                '$badRowCount' => $badRow,
                '$emptyTable' => $emptyTable,
                '$changeAI' => $changeAI,
            ]);
        }

        $mvc->commit();

        foreach ($insertSelects as $sql) {
                self::runQuery($mvc, $sql);
        }
    }

    /*
    ****************************************************************************
    */

    static function tableDump($table, $value)
    {
        $table == self::OUTPUT_TABLE_BY_NAME ? vardump($value) : NULL;
    }

    /*
    ****************************************************************************
    */

    static function runQuery($mvc, $sql)
    {
        self::DEBUG ? varDump($sql, [
            'depth' => 3
        ]) : $mvc->runQuery($sql);
    }

    /*
    ****************************************************************************
    */
}