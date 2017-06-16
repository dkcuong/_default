<?php

/*
********************************************************************************
* CONFIG.PHP                                                                   *
********************************************************************************
*/

namespace models;

use \assembler;
use \appConfig;

class config
{
    static $dateTimes = [];

    static $stored = [
        'site' => [
            'uri' => NULL,
            'appURL' => NULL,
            'appEnv' => NULL,
            'uriBase' => NULL,
            'appName' => NULL,
            'appRoot' => NULL,
            'homePage' => NULL,
            'jsonBase' => NULL,
            'requestURI' => NULL,
            'jsonRequest' => NULL,
            'requestPage' => NULL,
            'requestClass' => NULL,
            'requestMethod' => NULL,
        ],
        'request' => [
            'cron' => NULL,
        ],
    ];

    static $statuses = [
        'active' => 1,
        'inactive' => 0,
    ];

    /*
    ****************************************************************************
    */

    static function init()
    {
        // Report all errors
        error_reporting(E_ALL);

        date_default_timezone_set('America/New_York');

        $debugStandard = [];

        self::getSiteInfo();

        // In production, never display errors and kill script on errors
        self::$stored['site']['appEnv'] = $appEnv = \environment::get();

        switch ($appEnv) {
            case 'local':
            case 'development':
                $debugStandard = appConfig::$settings['testingDebug'];
                break;
            case 'production':
                $debugStandard = appConfig::$settings['productionDebug'];
        }

        ini_set('display_errors', $debugStandard['displayErrors']);

        self::getRequestInfo();
    }

    /*
    ****************************************************************************
    */

    static function getStatus($name)
    {
        return static::$statuses[$name];
    }

    /*
    ****************************************************************************
    */

    static function getRequestInfo()
    {
        $parts = [
            'class' => 'requestClass',
            'method' => 'requestMethod',
        ];

        $requests = $defaults = [];

        foreach ($parts as $part => $request) {
            $defaults[$part] = appConfig::$settings['defaultPage'][$request];
            $requests[$part] = self::$stored['site'][$request] =
                getDefault($_GET[$part], $defaults[$part]);
        }

        self::$stored['site']['homePage'] =
            makeLink($defaults['class'], $defaults['method']);

        self::$stored['request']['cron'] = $requests['class'] == 'appCrons';

        // Only capitolize class if method is set
        self::$stored['site']['requestPage'] = self::formRequest($requests);
    }

    /*
    ****************************************************************************
    */

    static function formRequest($params)
    {
        $class = getDefault($params['method']) ? ucfirst($params['class']) :
            $params['class'];

        return $params['method'] . $class;
    }

    /*
    ****************************************************************************
    */

    static function getServerVar($index)
    {
        return $_SERVER[$index];
    }

    /*
    ****************************************************************************
    */

    static function getImagesDir()
    {
        return self::$stored['site']['uriBase'].'/custom/images';
    }

    /*
    ****************************************************************************
    */

    static function checkRequestMethod($perferedMethod)
    {
        return $_SERVER['REQUEST_METHOD'] == $perferedMethod;
    }

    /*
    ****************************************************************************
    */

    static function getSiteInfo()
    {
        self::$stored['site']['requestURI'] = $_SERVER['REQUEST_URI'];

        $jsonDir = '/json';
        $indexDir = '/index';

        // Get URI base

        $uriJsonDirPos = strpos($_SERVER['REQUEST_URI'], $jsonDir);
        $uriIndexDirPos = strpos($_SERVER['REQUEST_URI'], $indexDir);
        $isAjaxRequest = self::isAjaxRequest();

        self::$stored['site']['jsonRequest'] = $uriJsonDirPos || $isAjaxRequest
                ? TRUE : FALSE;

        $uriDirPos = $uriIndexDirPos ? $uriIndexDirPos : $uriJsonDirPos;

        self::$stored['site']['uri'] = $uri =
            substr($_SERVER['REQUEST_URI'], 0, $uriDirPos);

        $uriBase = basename($uri);

        $dirName = dirName($uri);
        self::$stored['site']['mvc'] = trim($dirName, '/\\');


        self::$stored['site']['appName'] = $uriBase;
        assembler::addAppDir($uriBase);
        assembler::setLoadChecks();

        self::$stored['site']['jsonBase'] = $uri . $jsonDir;
        self::$stored['site']['uriBase'] = $uri . $indexDir;
    }

    /*
    ****************************************************************************
    */

    static function get($category, $name=FALSE)
    {
        return $name ? self::$stored[$category][$name] :
            self::$stored[$category];
    }

    /*
    ****************************************************************************
    */

    static function getSetting($category, $name=FALSE)
    {
        return $name ? appConfig::$settings[$category][$name] :
            appConfig::$settings[$category];
    }

    /*
    ****************************************************************************
    */

    static function getAppURL()
    {
        if (! self::$stored['site']['appURL']) {

            // IIS uses https = off to signify http protocal

            $https = getDefault($_SERVER['HTTPS'], 'off');
            $serverProtocol = $https == 'off' ? 'http' : 'https';
            $serverName = $serverProtocol.'://' . self::getServerName();

            self::$stored['site']['appURL'] =
                $serverName . self::$stored['site']['uriBase'];
        }

        return self::$stored['site']['appURL'];
    }

    /*
    ****************************************************************************
    */

    static function getAppRoot()
    {
        if (! self::$stored['site']['appRoot']) {

            $dirName = dirname($_SERVER['PHP_SELF']);
            $displayRoot = $_SERVER['DOCUMENT_ROOT'].$dirName;

            // Get url before 'application' dir
            $appDirPos = strpos($displayRoot, 'application');

            // File location of application
            self::$stored['site']['appRoot'] =
                substr($displayRoot, 0, $appDirPos);
        }

        return self::$stored['site']['appRoot'];
    }

    /*
    ****************************************************************************
    */


    static function getDateTime($name)
    {
        if (! isset(self::$dateTimes[$name])) {
            $format = NULL;
            switch ($name) {
                case 'currentTime':
                    self::$dateTimes[$name] = time();
                    return self::$dateTimes[$name];
                case 'date':
                    $format = 'Y-m-d';
                    break;
                case 'dateTime':
                    $format = 'Y-m-d H:i:s';
                    break;
                case 'time':
                    $format = 'H:i:s';
                    break;
                case 'numberDate':
                    $format = 'mdy';
                    break;
                default:
                    die('Invalid Date/Time');
            }

            self::$dateTimes[$name] = date($format);
        }

        return self::$dateTimes[$name];
    }

    /*
    ****************************************************************************
    */

    static function getServerName() {
        $serverPort = $_SERVER['SERVER_PORT']!='80' ? ':'.$_SERVER['SERVER_PORT'] : NULL;
        $serverName = $_SERVER['SERVER_NAME'] . $serverPort;
        return $serverName;
    }

    /*
    ****************************************************************************
    */

    static function isAjaxRequest ()
    {
        $strXmlRequest = 'xmlhttprequest';

        if (! isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return FALSE;
        }

        if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return FALSE;
        }

        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== $strXmlRequest) {
            return FALSE;
        }

        return TRUE;
    }

}