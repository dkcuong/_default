<?php

namespace dbCommands;

class callbacks
{
    /*
    ****************************************************************************
    */

    static function hasResults($results)
    {
        return $results;
    }
    
    /*
    ****************************************************************************
    */

    static function emptyResults($results)
    {
        return ! $results;
    }

    /*
    ****************************************************************************
    */

    static function rowAssert($params)
    {
        foreach ($params['results'] as $row) {
            // Passes if one row is found that meets asserts
            $passed = TRUE;
            
            if (! $params['rowAssert']) {
                return $passed;
            }
            
            foreach ($params['rowAssert'] as $assert) {

                $name = $assert['name'];
                $rowValue = $row[$name];
                
                switch ($assert['compare']) {
                    case '=':
                        $passed = $rowValue == $assert['value'] ? 
                            FALSE : $passed;
                        break;
                    case '!=':
                        $passed = $rowValue != $assert['value'] ? 
                            FALSE : $passed;
                        break;
                    case 'IS NOT NULL':
                        $passed = $rowValue ? FALSE : $passed;
                        break;
                    default:
                       die ('Asert Type Not Found');
                }
            }
            
            if ($passed) {
                // Found the row
                return $passed;
            }
        }
        
        return FALSE;
    }
    

}
