<?php

namespace models;

use \appConfig;

class directories
{
    static $defaultLog;

    static $types = ['logs', 'uploads'];
    static $missing = 'The directory was not found: ';
    
    static $directoryPaths = [];
    static $missingHeader = FALSE;
    
    // Put all universal directories here
    static $directories = [
        'logDirs' => [
            'crons' => 'crons',
            'functions' => 'logger/functions',
            'errorAjax' => 'errorAjax',
            'ByLocation' => 'queries/ByLocation',
            'transactions' => 'logger/transactions',
        ],
        'uploadDirs' => [
            'testUploads' => 'testUploads',            
        ],
    ];

    static $aliases = [
        'logs' => 'logDirs',
        'uploads' => 'uploadDirs',
    ];
    
    /*
    ****************************************************************************
    */

    static function setDefaultLog()
    {
        $numberDate = config::getDateTime('numberDate');
        
        if (! self::$defaultLog) {
            $appRoot = config::getAppRoot();
            self::$defaultLog = 
                $appRoot.'logs/default/'.$numberDate.'.log';
        }
        
        ini_set('error_log', self::$defaultLog);
    }
    
    /*
    ****************************************************************************
    */

    static function getLogDateFile($type, $assoc)
    {
        $logDir = self::getDir($type, $assoc);

        $numberDate = config::getDateTime('numberDate');
        
        return $logDir.'/'.$numberDate.'.log';
    }
    
    /*
    ****************************************************************************
    */

    static function getDir($type, $assoc)
    {
        $storedPath = getDefault(self::$directoryPaths[$type][$assoc]);
        
        if ($storedPath) {
            return $storedPath;
        }
        
        $typeAlias = self::$aliases[$type];
        
        $path = getDefault(self::$directories[$typeAlias][$assoc]) .
            getDefault(appConfig::$settings[$typeAlias][$assoc]);
        
        if (! $path) {
            echo self::$missing . $assoc;
            die;
        }

        $appRoot = config::getAppRoot();
        $appName = config::get('site', 'appName');
        
        return $appRoot.$type.'/'.$appName.'/'.$path;
    }
    
    /*
    ****************************************************************************
    */
    
    static function checkAll()
    {
        self::check();
        self::check(appConfig::$settings);
    }
    
    /*
    ****************************************************************************
    */
    
    static function check($passedDir=FALSE)
    {
        $directories = $passedDir ? $passedDir : self::$directories;
        
        $appRoot = config::getAppRoot();
        $appName = config::get('site', 'appName');
        foreach (self::$types as $type) {

            $typeAlias = self::$aliases[$type];
            
            foreach ($directories[$typeAlias] as $assoc => $path) {
                
                $fullPath = $appRoot.$type.'/' . 
                    $appName.'/'.$path;
                
                $fileExists = file_exists($fullPath);
                
                // Either note that dir is missing, or save it for later
                self::$directoryPaths[$assoc] = $fileExists ? 
                    $fullPath : self::outPath($fullPath);
            }
        }
    }
    
    /*
    ****************************************************************************
    */
    
    static function outPath($fullPath) 
    {
        if (! self::$missingHeader) {
            echo 'Directories not found:<br>';
            self::$missingHeader = TRUE;
        }

        echo $fullPath.'<br>';
        
        return FALSE;
    }
    
    /*
    ****************************************************************************
    */
}
