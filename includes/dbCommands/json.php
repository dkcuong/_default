<?php

namespace dbCommands;

class json
{
    /*
    ****************************************************************************
    */

    static function add($app)
    {
        $dbKeys = \database\model::listDBKeys();
        $post = $app->getArray('post');
        
        $newCommand = $errors = [];

        $newCommand['commandType'] = $commandType = 
            getDefault($post['commandType']);
        
//        $commandType == 'modifyStructure' ? self::modifyStructure() : NULL;

        $errors['noDB'] = ! in_array($post['database'], $dbKeys);
        
        $trimmedQueryInfo = array_map('trim', $post['queryInfo']);
        
        if ($commandType == 'modifyStructure') {

            $model = getDefault($post['command']['model']);

            $errors['noModel'] = ! $model;


            $inputs = model::queryForm($model, ['getParams' => TRUE]);

            $filtered = array_filter($trimmedQueryInfo, function ($value) {
                return $value !== NULL;
            });
            

            $keyDiff = array_diff_key($inputs, $filtered);
            $errors['missingQueryValues'] = array_values($keyDiff);

            $errors['noDesc'] = ! getDefault($post['command']['index']);

            $filteredErrors = array_filter($errors);

            if ($filteredErrors) {
                return ['errors' => $filteredErrors];
            }
        } else {

            $model = getDefault($post['command']['dataType']);

            $errors['noModel'] = ! $model;


            $dataType = dataTypes::get($model);

            $inputs = [];
            foreach ($dataType['targets'] as $target) {
                $inputs[$target] = TRUE;
            }

            $filtered = array_filter($post['dataInputs'][$model]);
            
            if (! $post['database']) {
                return ['errors' => $errors];
            }

            $getDataValues = new getDataValues($app, $post['database']);
            $newCommand['dataInputs'] = $getDataValues->call($model, $filtered);

            $keyDiff = array_diff_key($inputs, $filtered);
            $errors['missingQueryValues'] = array_values($keyDiff);

            $errors['noDesc'] = ! getDefault($post['command']['index']);

            $filteredErrors = array_filter($errors);

            if ($filteredErrors) {
                return ['errors' => $filteredErrors];
            }
            
        }

        $newCommand['command'] = [
            'model' => $model,
            'index' => $post['command']['index'],
            'negates' => $post['command']['negates'],
        ];
        
        $results = json::get($post['database']);
        $previous = $results['array'];
        
        // Don't need database
        
        $modelInputValues = array_intersect_key($filtered, $inputs);
        
        $rowAssert = getDefault($post['rowAssert'][$model]);
        
        if ($rowAssert) {
            $noQuotes = str_replace(['`', '"', "'"], NULL, $rowAssert);
            $findOrs = str_replace(' or ', ' OR ', $noQuotes);
            $lines = explode(' OR ', $findOrs);
            $pieces = [];
            foreach ($lines as $line) {
                $pieces[] = self::loopLines($line);
            }
            
            $newCommand['rowAssert'] = $pieces;
        } 
        
        if ($commandType == 'modifyStructure') {
            $newCommand['queryInfo'] = $modelInputValues;
        }
        
        $custom = getDefault($post['customQuery'][$model]);
        
        $trimmed = trim($custom);
        $queryInfoString = serialize([
            'queryInfo' => $modelInputValues,
            'customQuery' => preg_replace('/\s+/', ' ', $trimmed),
        ]);

        if ($custom) {
            $newCommand['customQuery'] = explode(PHP_EOL, $custom);
        } 

        $newCommand['hash'] = md5($queryInfoString);
        $hashes = array_column($previous, 'hash');
        
        if (in_array($newCommand['hash'], $hashes)) {
            return [
                'errors' => ['alreadyExists' => TRUE]
            ];
        }
        
        $allInfo = array_merge($previous, [$newCommand]);
        $json = json_encode($allInfo, JSON_PRETTY_PRINT);

//        $asComment = '/*'.$json.'*/';
        
        return file_put_contents($results['file'], $json);
    }
    
    /*
    ****************************************************************************
    */

    static function modifyStructure()
    {
    }
    
    /*
    ****************************************************************************
    */

    static function loopLines($line)
    {
        foreach (['!=', '=', 'IS NOT NULL'] as $comparison) {
            $found = strpos($line, $comparison);
            if ($found) {

                $pieces = explode($comparison, $line);
                $start = reset($pieces);
                $end = end($pieces);
                
                return [
                    'name' => trim($start),
                    'compare' => $comparison,
                    'value' => trim($end),
                ];
            }
        }

        die('No Comparison Found In Asserts');
    }
    
    /*
    ****************************************************************************
    */

    static function get($database)
    {
        $file = \models\config::get('site', 'appRoot') . 
            'applications\_default\sql\dbCommandsInfo\\'.$database.'.json';
        $fileExisted = file_exists($file);
        $contents = $fileExisted ? file_get_contents($file) : NULL;
//        $notCommnents = trim($contents, '/*');
        $decoded = json_decode($contents, 'array');
        
        return [
            'file' => $file,
            'array' => $decoded ? $decoded : [],
        ];

    }
    
}
