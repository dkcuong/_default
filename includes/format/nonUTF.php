<?php

namespace format;

class nonUTF 
{
    //If there is non-UTF encoding, return TRUE;  otherwise, return NULL
    static function check($input) 
    {
        static $badInput = FALSE;
        $isArray = is_array($input);
        if ($isArray) {
            foreach ($input as $content) {
                $is2DArray = is_array($content);
                if ($is2DArray) { 
                    self::check($content); 
                } else if (strlen($content) != mb_strlen($content, 'utf-8')) {    
                    $badInput = TRUE; 
                }
            }    
        } else if (strlen($input) != mb_strlen($input, 'utf-8')) {    
            $badInput = TRUE;    
        }

        if ($badInput) {
            $badInput = FALSE;
            return TRUE;
        }
    }
}
