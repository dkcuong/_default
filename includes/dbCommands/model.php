<?php

namespace dbCommands;

class model
{
    // Databases names need to reference config file
    static $dbValueNames = [
        'Database Alias' => TRUE,
    ];
    
    const SHOW_RESULTS = FALSE;

    /*
    ****************************************************************************
    */

    static function runCallback($command, $results)
    {
        $callback = $command['callback'];
        
        $callbackArray = ['dbCommands\\callbacks', $callback];

        switch ($callback) {
            case 'hasResults':
            case 'emptyResults':
                return call_user_func($callbackArray, $results) ? TRUE : FALSE;
            case 'rowAssert':
                return call_user_func($callbackArray, [
                    'results' => $results,
                    'rowAssert' => $command['rowAssert'],
                ]) ? TRUE : FALSE;
            default: die('Invalid Callback Requested');
        }
    }
    
    /*
    ****************************************************************************
    */

    static function getForms()
    {
        $forms = [];
        $queries = queries::get();
        foreach (array_keys($queries) as $queryType) {
            $forms[] = self::queryForm($queryType);
        }
        return $forms;
    }
    
    /*
    ****************************************************************************
    */

    static function buildQuery($params)
    {
        $commandModel = $params['command']['model'];

        $inputNames = self::queryForm($commandModel, ['getParams' => TRUE]);

        $formattedQueries = self::queryForm($commandModel, [
            'plainQuery' => TRUE
        ]);

        $returnQueries = [];
        $dbKeys = \database\model::listDBKeys();
        foreach (['sql', 'check'] as $queryIndex) {
            $queries = queries::get($commandModel);
            $query = $queries[$queryIndex];

            $finalQuery = $query ? self::replaceTokens([
                'query' => $formattedQueries[$queryIndex],
                'dbKeys' => $dbKeys,
                'inputNames' => $inputNames,
                'queryIndex' => $queryIndex,
                'queryInfo' => $params['queryInfo'],
            ]) : implode(PHP_EOL, $params['customQuery']);
            
            $returnQueries[$queryIndex] = trim($finalQuery);
        }

        return $returnQueries;
    }
    
    /*
    ****************************************************************************
    */

    static function replaceTokens($params)
    {
        $searches = $replaces = [];
        foreach ($params['inputNames'] as $camel => $display) {
            foreach (['**', '##', '++'] as $delimeter) {

                $searches[] = $delimeter.$display.$delimeter;
                // Quotes for ## and ++ when checking
                $quote = $delimeter == '##' || $delimeter == '++' && 
                    $params['queryIndex'] == 'check' ? '"' : NULL;
                
                $finalValue = isset(self::$dbValueNames[$display]) ? 
                    \dbInfo::getDBName($params['queryInfo'][$camel]) : 
                    $params['queryInfo'][$camel];
                
                $replaces[] = $quote.$finalValue.$quote;
            }
        }

        return str_replace($searches, $replaces, $params['query']);
    }
    
    /*
    ****************************************************************************
    */

    static function queryForm($queryType, $options=[])
    {
        $getParams = getDefault($options['getParams']);
        $plainQuery = getDefault($options['plainQuery']);
        $tokenString = getDefault($options['tokenString']);
        
        ob_start(); ?>
        <div id="<?php echo $queryType; ?>" class="queryInput hidden"><?php
        $startDiv = ob_get_clean();
        $output = $tokenString ? NULL : $startDiv;
        
        $inputs = [];
        $queries = queries::get($queryType);
        
        foreach ([
            'sql' => 'Command',
            'check' => 'Check',
        ] as $queryIndex => $display) {
            self::createCommand($output, $inputs, $queries, [
                'display' => $display, 
                'queryType' => $queryType, 
                'plainQuery' => $plainQuery, 
                'queryIndex' => $queryIndex, 
                'tokenString' => $tokenString, 
            ]);
        }

        $callback = getDefault($queries['callback']);

        if ($callback == 'rowAssert') { 
            ob_start(); ?>
            <h3>Row Assertions</h3>
            <textarea name="rowAssert[<?php echo $queryType; ?>]"
            ></textarea><?php
            $output .= ob_get_clean();
        }
        
        if ($tokenString || $plainQuery) {
            return $queries;
        }
        
        if ($getParams) {
            return array_filter($inputs);
        }
        
        ob_start(); ?>
        </div><?php
        $endDiv = ob_get_clean();
        $output .= $tokenString ? NULL : $endDiv;

        return $output;
    }
    
    /*
    ****************************************************************************
    */

    static function whitespaceLength($line)
    {
        $trimmed = lTrim($line);
        return strLen($line) - strLen($trimmed);
    }
    
    /*
    ****************************************************************************
    */

    static function removeQuerySpaces($sql, $parallelLines=FALSE)
    {
        $lines = explode(PHP_EOL, $sql);
        
        if (count($lines) == 1) {
            return $sql;
        }

        $filtered = array_filter($lines, 'trim');

        $first = reset($filtered);

        if ($parallelLines) {
            $whitespace = self::whitespaceLength($first);
        } else {
            array_shift($filtered);
            $whitespaces = 
                array_map('dbCommands\model::whitespaceLength', $filtered);
            $whitespace = min($whitespaces);
        }
        
        foreach ($filtered as &$line) {
            $line = subStr($line, $whitespace);
        }
        
        ! $parallelLines ? array_unshift($filtered, $first) : NULL;

        return implode(PHP_EOL, $filtered);
    }
    
    /*
    ****************************************************************************
    */

    static function createCommand(&$output, &$inputs, &$queries, $params)
    {
        $display = $params['display'];
        $queryType = $params['queryType'];
        $queryIndex = $params['queryIndex'];
        $plainQuery = $params['plainQuery']; 
        $tokenString = $params['tokenString']; 

        $sql = getDefault($queries[$queryIndex]);

        $lines = explode(PHP_EOL, $sql);

        $filtered = array_filter($lines, 'trim');

        $first = reset($filtered);

        $trimmed = lTrim($first);
        $whitespaces = strLen($first) - strLen($trimmed);

        foreach ($filtered as &$line) {
            $line = subStr($line, $whitespaces);
        }

        $removedWhitespace = implode(PHP_EOL, $filtered);
        
        if ($plainQuery) {
            return $queries[$queryIndex] = $removedWhitespace;
        }

        $odds = $evens = $params = [];

        $treatValuesTheSame = 
            str_replace(['##', '++'], '**', $removedWhitespace);
        $pieces = explode('**', $treatValuesTheSame);
        while ($pieces) {
            $odds[] = array_shift($pieces);
            $evens[] = array_shift($pieces);
        }

        ob_start(); ?>
        <h3><?php echo $display; ?></h3><pre><?php
        $output .= ob_get_clean();

        // Odds will be empty if this is a custom input
        if (! array_filter($odds)) {    
            ob_start(); 
            ?><textarea name="customQuery[<?php echo $queryType; ?>]" 
                        placeholder="Custom Query"><?php
            ?></textarea><?php 
            $output .= ob_get_clean();            
        }

        foreach($odds as $index => $odd) {
            $output .= $odd;
            $queries[$queryIndex] .= $odd; 
            $even = $evens[$index];
            $namePieces = explode(' ', $even);

            array_walk($namePieces, function (&$value, $index) {
                $value = $index ? ucWords($value) : strToLower($value);
            });

            $camelCase = implode(NULL, $namePieces);
            $inputs[$camelCase] = $even;

            ob_start(); 
            ?><input class="queryInputs <?php echo $camelCase; ?>"
                name="queryInfo[<?php echo $camelCase; ?>]"
                type="text" placeholder="<?php echo $even; ?>"><?php
            $clean = ob_get_clean();
            $input = $even ? $clean : NULL;


            $output .= $tokenString ? $camelCase : $input;
            $queries[$queryIndex] .= $tokenString ? $camelCase : $input;
        }
        ob_start(); ?>
        </pre><?php

        $output .= ob_get_clean();
    }
    
    /*
    ****************************************************************************
    */

    public static function get($app, $displayMode=FALSE)
    {
        $db = getDefault($app->post['db']);
        $index = getDefault($app->post['id']);

        $dbCommands = [];

        $dbKeys = $db ? [$db] : \dbInfo::listDBKeys();
        
        
        foreach ($dbKeys as $dbKey) {
            
            $results = json::get($dbKey);
            
            foreach ($results['array'] as $row) {
                
                $commandType = getDefault($row['commandType']);
                if ($commandType == 'addData') {
                    $valuesToQueries = new valuesToQueries([
                        'app' => $app, 
                        'dbKey' => $dbKey, 
                        'displayMode' => $displayMode, 
                    ]);
                    
                    $command = $valuesToQueries->call($row);

                    $command['callback'] = 'hasResults';
                } else {
                    $command = self::buildQuery($row);
                    $queryInfo = queries::get($row['command']['model']);
                    $command['callback'] = $queryInfo['callback'];
                }

                if ($row['command']['negates']) {
                    $command['negates'] = $row['command']['negates'];
                }
                
                $command['rowAssert'] = getDefault($row['rowAssert']);

                $command['rowAssertDisplay'] = 
                    self::getRowAssertDisplay($command, $row);

                $command['description'] = $row['command']['index'];

                $dbCommands[$dbKey][] = $command;
            }
        }
        
        $results = $index ? 
            self::getByIndex($dbCommands[$dbKey], $index) : $dbCommands;

        self::SHOW_RESULTS ? vardump($results) : FALSE;

        return [
            'pdo' => $db ? $app->getDB(['dbAlias' => $db]) : NULL,
            'results' => $results,
        ];
    }

    /*
    ****************************************************************************
    */

    static function getRowAssertDisplay($command, $row)
    {
        if (! $command['rowAssert']) {
            return NULL;
        }

        $asserts = [];
        foreach ($row['rowAssert'] as $assert) {
            $asserts[] = htmlentities($assert['name'].' ' .
                $assert['compare'].' '.$assert['value']);
        }

        return implode(PHP_EOL, $asserts);
    }

    /*
    ****************************************************************************
    */

    static function getByIndex($dbCommands, $index)
    {
        $indexes = array_column($dbCommands, 'description');
        $commandID = array_search($index, $indexes);
        return $dbCommands[$commandID];
    }

    /*
    ****************************************************************************
    */

}
