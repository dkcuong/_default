<?php

namespace logger;

use \models\directories;

class model
{
    /*
    ****************************************************************************
    */
    
    static function modelLog($params)
    {
        $path = directories::getDir('logs', $params['info']['logDir']);
       
        $logPath = getDefault($params['dated']) ? 
            directories::getLogDateFile('logs', $params['info']['logDir']) : 
            $path.'/'.$params['info']['filename'];
        
        $logFound = self::checkLog($path, $params);
        
        error_log($params['message']."\r\n", 3, $logPath);
        
        return $logFound;
    }
    
    /*
    ****************************************************************************
    */
    
    static function checkLog($path, $params)
    {
        if ($params['logFound']) {
            return;
        }

        $logFound = file_exists($path);

        if (! $logFound) {
            $message = 'Logger - Specified Log Directory Not Found: <br>'.$path;
            die($message); 
        }
        
        return $logFound;
    }
    
}
