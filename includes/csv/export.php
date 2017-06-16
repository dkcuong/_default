<?php

namespace csv;

class export 
{
    static function downloadHeader($fileName)
    {
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename='.$fileName.'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    /*
    ****************************************************************************
    */
    
    static function queryToCSV($params)
    {
        $db = $params['db'];
        $sql = $params['sql'];
        $fileName = $params['fileName'];
        $fieldKeys = $params['fieldKeys'];
        $queryParams = $params['queryParams'];
        $model = $params['model'];
        
        $fields = $model->fields;

        $columnTitles = array_column($fieldKeys, 'title');

        $titleKeys = array_keys($fields);
        
        $displayTitles = $skipColumn = [];

        self::downloadHeader($fileName);

        foreach ($columnTitles as $key => $title) {
            
            $title = $titleKeys[$key];
            $info = $fields[$title];
           
            if (isset($info['csvSkipExport'])) {
                $skipColumn[$key] = TRUE;
            } else {
                $displayTitles[] = $info['display'];
            }            
        }

        echo implode(',', $displayTitles)."\n";        

        $sth = $db->runQuery($sql, $queryParams);
        
        $lineFeed = chr(10);
        $carriageReturn = chr(13);
        
        $both = $carriageReturn . $lineFeed;
        
        $data = [];
        
        while ($row = $sth->fetch(\pdo::FETCH_NUM)) {
            $data[] = $row;
        }
        
        if (getDefault($model->csvExportHandle)) {

            $function = [
                $model, 
                $model->csvExportHandle
            ];

            $data = call_user_func($function, $data);            
        }

        foreach ($data as $row) {
            
            $rowText = [];
            
            foreach ($row as $key => $value) {
                if (isset($skipColumn[$key])) {
                    continue;
                }

                $title = $titleKeys[$key];
                
                $rowText[] = self::getExportValue([
                    'value' => $value,
                    'lineFeed' => $lineFeed,
                    'carriageReturn' => $carriageReturn,
                    'both' => $both,
                    'info' => $fields[$title],
                    'model' => $model,
                    'row' => $row,
                ]);
            }
            
            echo implode(',', $rowText)."\n";
        }
        
        die;
    }
    
    /*
    ****************************************************************************
    */
    
    static function getExportValue($data)
    {
        $value = $data['value'];
        $lineFeed = $data['lineFeed'];
        $carriageReturn = $data['carriageReturn'];
        $both = $data['both'];
        $info = $data['info'];
        $model = $data['model'];
        $row = $data['row'];
        
        if (strpos($value, $lineFeed) !== FALSE 
        ||  strpos($value, $carriageReturn) !== FALSE) {

            $value = str_replace($both, ' ', $value);
            $value = str_replace($lineFeed, ' ', $value);
            $value = str_replace($carriageReturn, ' ', $value);
        }                

        if (strpos($value, ',') !== FALSE) {
            $value = str_replace(',', ' ', $value);
        }                

        if (! isset($info['csvExportSkipTrailSpace']) && intval($value) > 0
         && (strlen($value) > 11 || substr(trim($value), 0, 1) == '0')) {

            $value = $value . ' ';
        }

        if (isset($info['exportFunction'])) {

            $function = [
                $model, 
                $info['exportFunction']
            ];

            $value = call_user_func($function, $row);
        }
        
        return $value;
    }
    
    /*
    ****************************************************************************
    */
    
    static function write($array, $uploadDir, $fileName)
    {
        $testPath = \models\directories::getDir('uploads', $uploadDir);
        
        $target = $testPath.'/'.$fileName;
        
        $fp = fopen($target, 'w');
        
        foreach ($array as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);
        
        return $target;
    }
    
    /*
    ****************************************************************************
    */
    
    static function exportArray($array, $fileName='template')
    {
        self::downloadHeader($fileName);

        reset($array);
        $key = key($array);
        
        if (is_array($array[$key])) {
            foreach ($array as $values) {
                echo implode(',', $values)."\n";
            }            
        } else {
            echo implode(',', $array)."\n";
        }        
        
        die;
    }
    
    /*
    **************************************************************************** 
    */

    static function ArrayToFile($params)
    {
        $data = $params['data'];
        $fileName = $params['fileName'];
        $fieldKeys = getDefault($params['fieldKeys'], []);

        self::downloadHeader($fileName);

        $columnTitles = array_column($fieldKeys, 'title');

        echo implode(',', $columnTitles)."\n";

        foreach ($data as $row) {
            echo implode(',', $row)."\n";
        }

        exit;
    }

    /*
    ****************************************************************************
    */
    
}
