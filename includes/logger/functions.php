<?php

namespace logger;

class functions extends model
{
    const DATED = FALSE;
    const LOG_SEPARATORS = TRUE;

    const LOG_DIR = 'functions';
    const FILENAME = 'functions.log';
    
    static $headSet = FALSE;
    static $logFound = FALSE;
    
    /*
    ****************************************************************************
    */
    
    static function record($name=FALSE)
    {
        // Call this method at the beginning of every method to see when it is
        // called in a log
        
        self::setHead();
        
        if (! $name) {
            $references = debug_backtrace();

            array_shift($references);

            $reference = reset($references);
         
            $name = $reference['function'];
        }
        
        $message = $name."\r\n";
        
        self::log($message);
    }
        
    /*
    ****************************************************************************
    */
    
    static function setHead()
    {
        if (! self::LOG_SEPARATORS || self::$headSet) {
            return;
        }

        $message = "\r\nNew Log: ".date('Y-m-d H:i:s')."\r\n";
        self::log($message);
        
        self::$headSet = TRUE;
    }
    
    /*
    ****************************************************************************
    */
    
    static function log($message)
    {
        self::$logFound = self::modelLog([
            'info' => [
                'logDir' => self::LOG_DIR,
                'filename' => self::FILENAME,
            ],
            'dated' => self::DATED,
            'message' => $message,
            'logFound' => self::$logFound,
        ]);
    }
    
}
