<?php

namespace get;

class string
{
    static $storedDates = [];
    
    /*
    ****************************************************************************
    */
    
    static function date($quantity, $duration)
    {
        $storedDate = getDefault(self::$storedDates[$quantity][$duration]);
        
        if ($storedDate) {
            return $storedDate;
        }
        
        $time = $quantity.' '.$duration;
        
        $stringDate = strtotime($time);
        self::$storedDates[$quantity][$duration] = date('Y-m-d', $stringDate); 
        
        return self::$storedDates[$quantity][$duration];
    }

    /*
    ****************************************************************************
    */
}
