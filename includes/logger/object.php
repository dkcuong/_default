<?php

namespace logger;

class object extends model
{
    const DATED = TRUE;

    public $info = [
        'logDir' => NULL,
        'filename' => NULL,
    ];
    
    public $logFound = FALSE;
    
    /*
    ****************************************************************************
    */
    
    function __construct($info)
    {
        $this->info = $info;
    }
    
    /*
    ****************************************************************************
    */
    
    function log($message)
    {
        $this->logFound = self::modelLog([
            'info' => $this->info,
            'dated' => self::DATED,
            'message' => $message,
            'logFound' => $this->logFound,
        ]);
    }
    
}
