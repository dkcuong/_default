<?php

/*
********************************************************************************
* ASSEMBLER CLASS
********************************************************************************
*/

use models\config;

class assembler
{
    static $includedClasses = [];
    static $storeIncludedClasses = FALSE;

    // List of include classes directories
    static $mvcClasses = [
        'model',
        'template',
        'controller',
        'view',
    ];

    static $configAvailable = FALSE;

    // App Dirs will only be _default until the app name is available
    static $appDirs = ['_default'];

    // No load checks until the app config can provide a setting
    static $loadChecks = FALSE;

    /*
    ****************************************************************************
    */

    static function getMVC()
    {
        $class = config::get('site', 'requestClass');

        $classNames = [
            $class,
            '_default',
        ];

        $appName = config::get('site', 'appName');

        $dirNames = [
            $appName,
            '_default',
        ];

        // Get the database functionality
        self::load('includes', 'database');

        // Get the database information
        self::load('includes', 'dbInfo');

        // Get the default base
        self::load('includes', 'base');

        // Keep track of which class was included last so new clases can extend
        // from it
        $lastIncludedClass = 'base';

        // Include each class file
        foreach (self::$mvcClasses as $include) {
            self::loadNextMVCClass($lastIncludedClass, [
                'include' => $include,
                'dirNames' => $dirNames,
                'classNames' => $classNames,
            ]);
        }

        // Exception for old outdated dbInfo file
        ! isset(dbInfo::$dbNames) or die('<br>Your DB Info File is outdated.');

        // Instantiate final class
        $mvc = new $lastIncludedClass();
        $mvc->getDB(['primaryPDO' => TRUE]);

        // Values stored in the jsVars array-property will be passed to javascripts
        $mvc->setJSVars();

        $mvc->setImageDir();

        $mvc->storeRequestValues();

        return $mvc;

    }
    
    /*
    ****************************************************************************
    * LOADERS
    ****************************************************************************
    */

    static function addAppDir($directory)
    {
        self::$appDirs[] = $directory;
    }

    /*
    ****************************************************************************
    */

    static function setLoadChecks()
    {
        self::$loadChecks = config::getSetting('debug', 'loadChecks');
    }

    /*
    ****************************************************************************
    */

    static function load($dir, $class=NULL) 
    {   
        foreach (self::$appDirs as $appDir) {

            // _default: Root has non default dir for optional additional files
            // as APPLICATION_NAME: Custom class extends the default class for
            // all applications
            
            $extension = $class ? '.php' : NULL;
            
            $class = str_replace('\\', '/', $class);
			
            $class = str_replace('PHPMailer/SMTP', 'PHPMailer/smtp', $class );

            $dirFile = '../../'.$appDir.'/'.$dir.'/'.$class.$extension;

            $dirFileLower = '../../'.$appDir.'/'.$dir.'/'.strtolower($class).$extension;

            self::$loadChecks ? var_dump('Checking: '.$includeFile) : NULL;

            if (file_exists($dirFile) || file_exists($dirFileLower)) {

                $includeFile = file_exists($dirFile) ? $dirFile : $dirFileLower;
                if (! $class) {
                    return $includeFile;
                }

                self::$loadChecks ? var_dump('Found: '.$includeFile) : NULL;

                self::includeOnce($includeFile);
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function storeIncludedClass() 
    { 
        // See which classes the assembler has loaded for the controller
        self::$storeIncludedClasses = TRUE;
    }
    
    /*
    ****************************************************************************
    */

    static function includeOnce($includeFile) 
    { 
        if (self::$storeIncludedClasses) {
            self::$includedClasses[] = $includeFile;
        }
        
        include_once $includeFile;
    }

    /*
    ****************************************************************************
    */

    static function showIncludedClass() 
    { 
        if (self::$storeIncludedClasses) {
            varDump(self::$includedClasses);
        }
    }

    /*
    ****************************************************************************
    */

    static function loadNextMVCClass(&$lastIncludedClass, $params) 
    { 
        $include = $params['include'];
        $dirNames = $params['dirNames'];
        $classNames = $params['classNames'];

        // Check custom class, default class, custom default, then default
        // default (int that order)
        foreach ($classNames as $className) {
            foreach ($dirNames as $dir) {
                $includeFile = '../../'.$dir.'/'.$include.'s/'.$className.'.php';
                self::$loadChecks ? varDump('Checking: '.$includeFile) : NULL;
                if (! class_exists($include) && file_exists($includeFile)) {
                    self::includeOnce($includeFile);
                    self::$loadChecks ? varDump('Found: '.$includeFile) : NULL;
                    $lastIncludedClass = $include;
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function autoload($class)
    {
        $class = str_replace('_', '\\', $class);
        // Need to get rid of _default class
        $class = str_replace('\\\\', '\\_', $class);

        // PHPExcel and TCPDF are not designed to be portable
        $class = strpos(strtolower($class), 'tcpdf') !== FALSE ?
            'pdf\\tcpdf\\'.$class : $class;
        $class = strpos($class, 'PHPExcel') !== FALSE ? 'excel\\'.$class :
                $class;

        // Check both custom and default dirs for classes
        self::load('includes', $class);
    }

    /*
    ****************************************************************************
    */

    static function callMethod($object, $include)
    {
        switch ($include) {
            case 'model':
                // All model methods are shared
                $method = config::get('site', 'requestClass') . $include;
                if (method_exists($object, $method)) {
                    return $object->$method();
                }
                break;
            case 'view' :
            case 'controller' :
                $method = config::get('site', 'requestPage').$include;
                if (method_exists($object, $method)) {
                    return $object->$method();
                }
                break;
        }
    }

    /*
    ****************************************************************************
    */

    static function setAutoloader()
    {
        spl_autoload_register(['self', 'autoload']);
    }

    /*
    ****************************************************************************
    */

    static function callTemplateMethod($object, $headerFooter)
    {
        // Default page header/footer
        return $object->$headerFooter();
    }

    /*
    ****************************************************************************
    */

    static function loadIncludes($type)
    {
        // Check-for and load default/app css and js files

        $apps = [
            '_default',
            config::get('site', 'appName'),
        ];

        $files = [
            '_default',
            config::get('site', 'requestClass'),
        ];

        $jsToken = self::getJSToken();

        $uriBase = config::get('site', 'uriBase');

        foreach ($apps as $app) {
            foreach ($files as $file) {
                $customDir = $app == '_default' ? NULL : 'custom/';
                $includesDir = $file == '_default' ? NULL : 'includes/';

                $includeFile = '../../'.$app.'/root/'.$type.'/'.$includesDir.$file.'.'.$type;
                if (file_exists($includeFile)) {
                    switch ($type) {
                        case 'css':
                            echo '<link type="text/css" href="'.$uriBase.'/'
                                .$customDir.'css/'.$includesDir.$file.'.css'
                                .$jsToken.'" rel="stylesheet">';
                            break;
                        case 'js':
                            echo '<script src="'.$uriBase.'/'
                                .$customDir.'js/'.$includesDir.$file.'.js'
                                .$jsToken.'"></script>';
                            break;
                    }
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function getJSToken()
    {
        $appEnv = config::get('site', 'appEnv');
        $isNotProduction = $appEnv != 'production';

        $notNotLogged = ! isset($_SESSION['token']);

        $token = $isNotProduction || $notNotLogged ? date('is') :
            md5($_SESSION['token']);

        //return '?ver='.$token;
    }

    /*
    ****************************************************************************
    */

    static function getTestDB($mvc)
    {
        if (! access::isTester($mvc)) {
            return;
        }
    
        $priamryPDO = $mvc->getPrimaryPDO();
        $currentDB = $priamryPDO['dbName'];
        
        $testRunDB = $mvc->getDBName('testRuns');

        if ($currentDB != $testRunDB) {
            // change current working DB to record / run tests
            $mvc->getDB([
                'dbAlias' => 'testRuns',
                'primaryPDO' => TRUE,
                'changeDB' => TRUE,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

}