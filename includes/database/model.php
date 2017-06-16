<?php

/*
********************************************************************************
* DATABASE                                                                     *
********************************************************************************
*/

namespace database;

use pdo;
use access;
use dbInfo;
use PDOException;
use models\config;
use models\directories;
use logger\model as logger;

class model
{
    public $queries = [];
    public $debugTransactions = FALSE;
    public $debugMissingTransctions = FALSE;

    public $outQueries = FALSE;
    public $disableMods = FALSE;

    const LOG_SOURCE = 2;
    const SHORT_ERROR = 2;
    const LOG_DESTINATION = 3;
    const OUT_STORED_SOURCES = FALSE;
    const SHOW_STORED_QUERIES = FALSE;
    const SHOW_STORED_QUERY_PARAMS = FALSE;

    public $pdos = [];
    public $connID;
    public $logger;
    public $holders = [];

    public $pdoOpts = [];

    static $sqlLogs = [];

    static $testMode = FALSE;

    static $currentDate = NULL;

    public $primaryPDO = [];

    public $inTransaction = FALSE;

    public $recordQueries = FALSE;

    public $logCallersBlacklist = ['updateSession'];

    public $nonTransactionQueries = [];
    public $nonTransactionQueriesCount = 0;

    // Store all transactions for the end of controller
    public $storeMode = FALSE;
    public $stored = [
        'queries' => [],
        'transactions' => [],
    ];

    /*
    ************************************************************************
    */

    function getConnID()
    {
        if (! $this->connID) {
            $sql = 'SELECT CONNECTION_ID() AS connID';
            $result = $this->queryResult($sql);
            $this->connID = $result['connID'];
        }

        return $this->connID;
    }

    /*
    ****************************************************************************
    */

    function getTransactionLogger()
    {
        if (! $this->logger) {
            $this->logger = new \logger\object([
                'logDir' => 'transactions',
                'filename' => 'transactions',
            ]);
        }

        return $this->logger;
    }

    /*
    ************************************************************************
    */

    function debugMode($debugUser=FALSE)
    {
        $username = access::getUserInfoValue('username');
        if (! $debugUser || $debugUser == $username) {
            // Call this on a controller where you want to see all the queries
            // but don't want them committed
            $this->outQueries = TRUE;
            $this->disableMods = TRUE;
        }
    }

    /*
    ************************************************************************
    */

    function beginStore()
    {
        $this->storeMode = TRUE;
    }

    /*
    ************************************************************************
    */

    function endStore()
    {
        $this->storeMode = FALSE;
    }

    /*
    ************************************************************************
    */

    function storeQuery($sql, $params)
    {
        $queryIndex = array_search($sql, $this->stored['queries']);

        if ($queryIndex === FALSE) {
            $queryIndex = count($this->stored['queries']);
            $this->stored['queries'][] = $sql;
        }

        $transaction = [
            'params' => $params,
            'queryIndex' => $queryIndex,
        ];

        if (self::OUT_STORED_SOURCES) {
            $sourceInfo = traceInfo(['file', 'line'], ['depth' => 3]);
            $transaction['source'] = $sourceInfo['file'].': '.$sourceInfo['line'];
        }

        $this->stored['transactions'][] = $transaction;
    }

    /*
    ************************************************************************
    */

    function commitStored()
    {
        $this->endStore();

        self::SHOW_STORED_QUERIES || self::SHOW_STORED_QUERY_PARAMS ?
            varDump($this->stored['queries']) : NULL;

        $this->beginTransaction();

        $previousID = NULL;

        foreach ($this->stored['transactions'] as $row) {

            self::OUT_STORED_SOURCES ? vardump($row['source']) : NULL;

            $sqlID = $row['queryIndex'];

            $sql = $this->stored['queries'][$sqlID];
            $this->runQuery($sql, $row['params']);

            self::SHOW_STORED_QUERY_PARAMS && $previousID != $sqlID?
                varDump('Query: '.$sql) : NULL;
            $previousID = $sqlID;

            self::SHOW_STORED_QUERY_PARAMS ? varDump($row['params']) : NULL;

            self::OUT_STORED_SOURCES ? vardump($row['params']) : NULL;
        }

        $this->commit();
    }

    /*
    ************************************************************************
    */

    function prep($sql)
    {
        $pdo = $this->getHoldersPDO();
        return $pdo->prepare($sql);
    }

    /*
    ************************************************************************
    */

    function beginTransaction()
    {
        $transactionCaller = traceInfo(['file', 'line', 'function']);

        $blacklisted = in_array($transactionCaller['function'],
            $this->logCallersBlacklist);

        if (! $blacklisted) {
            $message = json_encode([
                'user' => \access::getUserInfoValue('username'),
                'class' => config::get('site', 'requestClass'),
                'method' => config::get('site', 'requestMethod'),
                'connID' => $this->getConnID(),
                'transactionCaller' => $transactionCaller,
            ], JSON_PRETTY_PRINT);

            $logger = $this->getTransactionLogger();

            $logger->log($message);
        }

        $this->inTransaction = TRUE;

        // Queries are not commited in store mode, no transaction necessary
        if ($this->storeMode) {
            return;
        }

        $pdo = $this->getHoldersPDO();

        $this->outQueries ? vardump('beginTransaction') : NULL;

        $pdo->beginTransaction();
    }

    /*
    ************************************************************************
    */

    function query($sql, $columnClass=0)
    {
        return $pdo = $this->getHoldersPDO()->query($sql, $columnClass);
    }

    /*
    ************************************************************************
    */

    function commit()
    {
        $this->outQueries ? vardump('commit') : NULL;

        $this->inTransaction = FALSE;

        if ($this->storeMode) {
            return;
        }

        $pdo = $this->getHoldersPDO();

        $pdo->commit();
    }

    /*
    ************************************************************************
    */

    function lastInsertID()
    {
        $this->dieException([
            'condition' => $this->debugTransactions && $this->inTransaction,
            'message' => 'Trying to use lastInsertID method in Transaction',
        ]);

        $pdo = $this->getHoldersPDO();

        return $pdo->lastInsertID();
    }

    /*
    ************************************************************************
    */

    function queryResults($sql, $params=NULL, $options=FALSE)
    {
        $fetchOptions = $options ? $options : pdo::FETCH_ASSOC|pdo::FETCH_UNIQUE;
        $sth = $this->runQuery($sql, $params);
        $results = $sth->fetchAll($fetchOptions);
        return $results;
    }

    /*
    ************************************************************************
    */

    function queryResult($sql, $params=NULL, $index=FALSE)
    {
        $sth = $this->runQuery($sql, $params);
        $results = $sth->fetch(pdo::FETCH_ASSOC|pdo::FETCH_UNIQUE);
        return $index ? $results[$index] : $results;
    }

    /*
    ************************************************************************
    */

    function ajaxQueryResult($sql, $params=NULL)
    {
        $sth = $this->runQuery($sql, $params);
        $results = $sth->fetch(pdo::FETCH_NUM);
        return $results;
    }

    /*
    ************************************************************************
    */

    function ajaxQueryResults($sql, $params=NULL)
    {
        $sth = $this->runQuery($sql, $params);
        $results = $sth ? $sth->fetchAll(pdo::FETCH_NUM) : [];
        return $results;
    }

    /*
    ************************************************************************
    */

    function setTestMode($on=TRUE)
    {
        self::$testMode = $on;
    }

    /*
    ************************************************************************
    */

    function showQueries()
    {
        foreach ($this->queries as $sql => $quantity) {
            vardump($quantity);
            vardump($sql);
        }
    }

    /*
    ************************************************************************
    */

    function dieException($params)
    {
        if ($params['condition']) {
            echo $params['message'];
            backTrace();
            die;
        }
    }

    /*
    ************************************************************************
    */

    function getPrimaryPDO()
    {
        return $this->primaryPDO;
    }

    /*
    ************************************************************************
    */

    function getPrimeHolder()
    {
        $host = $this->primaryPDO['host'];
        $user = $this->primaryPDO['user'];
        $dbName = $this->primaryPDO['dbName'];
        return $this->holders[$host][$user][$dbName];
    }

    /*
    ************************************************************************
    */

    function getHoldersPDO()
    {
        // Only the MVC will have a primaryPDO, connection holders will
        // reference themselves
        $holder = $this->primaryPDO ? $this->getPrimeHolder() : $this;

        return $holder->getPDO();
    }

    /*
    ************************************************************************
    */

    function setOpts($option)
    {
        $this->pdoOpts[$option] = TRUE;
    }

    /*
    ************************************************************************
    */

    function runQuery($sql, $params=NULL, $customNotice=FALSE)
    {
        if ($this->storeMode && $this->inTransaction) {
            return $this->storeQuery($sql, $params);
        }

        $this->outQueries ? vardump($sql, ['depth' => 4]) : NULL;

        if ($this->disableMods && $this->inTransaction) {
            return;
        }

        if ($this->recordQueries) {
            $this->queries[$sql] = isset($this->queries[$sql]) ?
                $this->queries[$sql] + 1 : 1;
        }

        $showQuery = strpos($sql, 'SHOW') !== FALSE;
        $selectQuery = strpos($sql, 'SELECT') !== FALSE;

        $breaksTransaction = $showQuery || $selectQuery;


        $insertQuery = strpos($sql, 'INSERT') !== FALSE;
        $updateQuery = strpos($sql, 'UPDATE') !== FALSE;

        $badModQuery = ($updateQuery || $insertQuery) && ! $this->inTransaction;

        if ($badModQuery) {
            $this->nonTransactionQueriesCount++;
            $this->nonTransactionQueries[] = $sql;
        }

        $tooManyBadMods = $this->debugMissingTransctions &&
            $this->nonTransactionQueriesCount == 15;

        $badSelect = $this->debugTransactions && $this->inTransaction &&
            $breaksTransaction;

        if ($tooManyBadMods) {
            varDump($this->nonTransactionQueries);
        }

        $this->dieException([
            'condition' => $tooManyBadMods,
            'message' => 'Too many queries with no PDO Transaction',
        ]);

        $this->dieException([
            'condition' => $badSelect,
            'message' => 'Select query in transaction',
        ]);

        $this->dieException([
            'condition' => $params && ! is_array($params),
            'message' => 'A non-array was passed to PDO execute method',
        ]);

        $this->dieException([
            'condition' => $params && is_array($params[0]),
            'message' => 'A 2D array was passed to PDO execute method',
        ]);

        $message = 'An object has been passed instead of query parameters';

        $this->dieException([
            'message' => $message,
            'condition' => $params && is_object($params[0]),
        ]);

        $showQueryTimes = config::getSetting('debug', 'queryTimes');

        $startTime = $showQueryTimes ? timeThis() : NULL;

        $pdo = $this->getHoldersPDO();

        try {
            $sth = $pdo->prepare($sql);
            $sth->execute($params);
        } catch (PDOException $exception) {

            if (self::$testMode) {
                return;
            }

            $customNotice ? NULL : backTrace();

            $queryError = getDefault($exception->errorInfo[self::SHORT_ERROR]);
            echo $error = is_string($customNotice) ? $customNotice :
                'Query failed: '.$queryError;

            $logFile = directories::getLogDateFile('logs', 'queryErrors');

            logger::modelLog([
                'info' => [
                    'logDir' => 'queryErrors',
                    'filename' => 'error.log',
                ],
                'dated' => TRUE,
                'message' => $error,
                'logFound' => $logFile,
            ]);

            die;
        }

        $endTime = $showQueryTimes ? timeThis($startTime) : NULL;

        $this->logQuery([
            'sql' => $sql,
            'time' => $endTime,
            'paramsCount' => count($params),
        ]);

        return $sth;
    }

    /*
    ************************************************************************
    */

    function quote($term)
    {
        $pdo = $this->getHoldersPDO();

        return $pdo->quote($term);
    }

    /*
    ************************************************************************
    */

    function getQMarkString($row)
    {
        return $qMarks = implode(',', $this->getQMarks($row));
    }

    /*
    ************************************************************************
    */

    function getQMarks($row)
    {
        return $qMarks = array_fill(0, count($row), '?');
    }

    /*
    ************************************************************************
    */

    // Store connections by server name here
    public $links = array();

    /*
    ************************************************************************
    */

    function changeServerDB($server, $neededDBName, $link)
    {
        // Change the database for the server link
        mysql_select_db($neededDBName, $link) or die(mysql_error());

        // Record that the selected database has changed
        $this->servers[$server] = $neededDBName;
    }

    /*
    ************************************************************************
    */

    function createPDO($params)
    {
        try {
            $dns = 'mysql:dbname='.$params['dbName'].';host='.$params['host'];

            if (getDefault($params['port'])) {
                $dns .= ';port='.$params['port'];
            }

            $pdo = new myPDO($dns, $params['user'], $params['pass'],
                $this->pdoOpts);

            // Set the PDO objects database name
            $pdo->setDBName($params['dbName']);

            // Change the PDO exception handler
            $pdo->setAttribute($pdo::ATTR_ERRMODE, $pdo::ERRMODE_EXCEPTION);

            // Set debug transaction boolean
            $this->debugTransactions =
                config::getSetting('debug', 'debugTransactions');

            $this->debugMissingTransctions =
                config::getSetting('debug', 'debugMissingTransactions');

        } catch  (PDOException $exception) {
            echo 'Connection failed: ' . $exception->getMessage();
        }

        // Change the exception handler back to whatever it was before
        // restore_exception_handler();
        return $pdo;
    }

    /*
    ************************************************************************
    */

    function getDB($params=[])
    {
        $dbAlias = getDefault($params['dbAlias'], 'app');
        $serverAlias = getDefault($params['server']);
        $primaryPDO = getDefault($params['primaryPDO']);
        $changeDB = getDefault($params['changeDB']);

        $server = dbInfo::getDBInfo($serverAlias);

        // Get database credentials
        $dbInfo = $server['credentials'];

        $host = $dbInfo['host'];
        $user = $dbInfo['user'];
        $port = getDefault($dbInfo['port'], NULL);

        $dbName = $server['databases'][$dbAlias];

        // If there is a need for a new holder with an old PDO
        $pdo = getDefault($this->pdos[$host][$user]);

        if ($changeDB || ! $pdo) {

            $dbInfo['dbName'] = $dbName;

            $pdo = $this->pdos[$host][$user] = $this->createPDO($dbInfo);

            $this->primaryPDO  = ! $primaryPDO ? $this->primaryPDO : [
                'host' => $host,
                'user' => $user,
                'dbName' => $dbName,
                'port' => $port,
            ];
        }

        $holder = getDefault($this->holders[$host][$user][$dbName]);

        if ($changeDB || ! $holder) {
            $holder = $this->holders[$host][$user][$dbName] =
            new connectionHolder([
                'pdo' => $pdo,
                'host' => $host,
                'user' => $user,
                'dbName' => $dbName,
                'port' => $port,
            ]);
        }

        return $holder;
    }

    /*
    ************************************************************************
    */

    function logQuery($params)
    {
        $backTrace = debug_backtrace();

        $sourceData = $backTrace[self::LOG_SOURCE];

        $file = $sourceData['file'];
        $line = $sourceData['line'];

        self::$currentDate = self::$currentDate ?
            self::$currentDate : date('Y-m-d');

        $params['date'] = self::$currentDate;
        $params['dbName'] = $this->getDBName();

        self::$sqlLogs[$file][$line] = $params;
    }

    /*
    ************************************************************************
    */

    static function writeQueries()
    {
        $json = json_encode(self::$sqlLogs, JSON_PRETTY_PRINT).',';

        $logFile = directories::getLogDateFile('logs', 'ByLocation');

        error_log($json, self::LOG_DESTINATION, $logFile);
    }

    /*
    ************************************************************************
    */

    function queryUnionResults($params)
    {
        $subquery = getDefault($params['subquery']);

        $subqueries = $subquery ? [$subquery] : $params['subqueries'];

        $subqueryCount = $params['subqueryCount'];

        if (! $subqueries || ! $subqueryCount) {
            return [];
        }

        $sql = NULL;

        $limits = getDefault($params['limits'], []);

        $mysqlParams = getDefault($params['mysqlParams'], []);

        $queryIterator = $limitIterator = 0;

        $uniqueLimitCount = count($limits);
        $uniqueQueryCount = count($subqueries);

        for ($i = 0; $i < $subqueryCount; $i++) {

            $nextQuery = $this->iterateArray($subqueries,
                $uniqueQueryCount, $queryIterator);

            $nextLimit = $this->iterateArray($limits,
                $uniqueLimitCount, $limitIterator);

            $query = $nextLimit ? $nextQuery.' LIMIT '.$nextLimit
                : $nextQuery;

            $sql .= $sql ? ' UNION ' : NULL;

            $sql .= '('.$query.')';
        }

        return $this->queryResults($sql, $mysqlParams);
    }

    /*
    ************************************************************************
    */

    static function listDBKeys()
    {
        $keys = [];

        foreach (dbInfo::$serverEnvs as $servers) {
            foreach ($servers as $server) {
                $keys += $server['databases'];
            }
        }

        return array_keys($keys);
    }

    /*
    ************************************************************************
    */

    function iterateArray($array, $size, &$iterator)
    {
        $nextIndex = $iterator++ % $size;
        return getDefault($array[$nextIndex]);
    }

    /*
    ************************************************************************
    */

}