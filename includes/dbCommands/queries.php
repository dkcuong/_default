<?php

namespace dbCommands;

class queries
{
    static $models = [
        
        // ## wlll have quotes
        // ** wlll not have quotes
        // ++ wlll have quotes on checks but not sql commands
        
        'updateJoinRow' => [
            'display' => 'Update Joined Row',
            'sql' => '
                UPDATE  **Table** a
                JOIN    **Join Table** b 
                    ON  a.**Table On** = b.**Join On**
                SET     a.**Update Field** = ##Update Value##
                WHERE   a.**Search Field** = ##Search Value##
                AND     b.**Join Field** = ##Join Value##;',
            'check' => '
                SELECT  "Found"
                FROM    **Table** a
                JOIN    **Join Table** b 
                    ON  a.**Table On** = b.**Join On**
                WHERE   a.**Update Field** != ##Update Value##
                AND     a.**Search Field** = ##Search Value##
                AND     b.**Join Field** = ##Join Value##;',
            'callback' => 'emptyResults',
        ],
        'dropIndex' => [
            'display' => 'Drop Index',
            'sql' => '
                ALTER TABLE **Table Name** 
                DROP INDEX ##Search Value##',
            'check' => '
                SHOW KEYS 
                FROM **Table Name**
                WHERE key_name = ##Search Value##',
            'callback' => 'emptyResults',
        ],
        'replaceIndex' => [
            'display' => 'Replace Indexes',
            'sql' => NULL,
            'check' => '
                SHOW KEYS 
                FROM **Table Name**
                WHERE **Search Name** = ##Search Value##',
            'callback' => 'rowAssert',
        ],
        'addIndexes' => [
            'display' => 'Add Indexes',
            'sql' => NULL,
            'check' => '
                SHOW KEYS 
                FROM **Table Name**
                WHERE **Search Name** = ##Search Value##',
            'callback' => 'hasResults',
        ],
        'addDatabase' => [
            'display' => 'Add Database',
            'sql' => '
                CREATE DATABASE ++Database Alias++
                CHARSET=utf8 COLLATE=utf8_general_ci',
            'check' => '
                SHOW DATABASES LIKE ++Database Alias++',
            'callback' => 'hasResults',
        ],
        'moveTable' => [
            'display' => 'Move Table',
            'sql' => '
                CREATE TABLE ++Table Name++
                SELECT * FROM **Database Alias**.++Table Name++;
                DROP TABLE **Database Alias**.++Table Name++;',
            'check' => '
                SHOW TABLES LIKE ++Table Name++',
            'callback' => 'hasResults',
        ],
        'dropTable' => [
            'display' => 'Drop Table',
            'sql' => '
                DROP TABLE ++Table Name++;',
            'check' => '
                SHOW TABLES LIKE ++Table Name++;',
            'callback' => 'emptyResults',
        ],
        'addField' => [
            'display' => 'Add Field',
            'sql' => NULL,
            'check' => '
                SHOW FIELDS
                FROM **Table**
                WHERE `Field` = ##Field Name##',
            'callback' => 'hasResults',
        ],
        'updateField' => [
            'display' => 'Update Field',
            'sql' => NULL,
            'check' => '
                SHOW FIELDS
                FROM **Table**
                WHERE `Field` = ##Field Name##',
            'callback' => 'rowAssert',
        ],
        'removeField' => [
            'display' => 'Remove Field',
            'sql' => '
                ALTER TABLE **Table** 
                DROP ++Drop Field++',
            'check' => '
                SHOW FIELDS
                FROM **Table**
                WHERE Field = ++Drop Field++',
            'callback' => 'emptyResults',
        ],
        'updateFieldValue' => [
            'display' => 'Update Field Value',
            'sql' => '
                UPDATE **Table**
                SET    **Update Field** = ##Update Value##
                WHERE  **Search Field** = ##Search Value##',
            'check' => '
                SELECT "Found"
                FROM   **Table**
                WHERE  **Update Field** = ##Update Value##',
            'callback' => 'hasResults',
        ],
        'updateRowField' => [
            'display' => 'Update Row Field',
            'sql' => '
                UPDATE **Table**
                SET    **Update Field** = ##Update Value##
                WHERE  **Confirm Field** = ##Confirm Value##',
            'check' => '
                SELECT "Found"
                FROM   **Table**
                WHERE  **Update Field** != ##Update Value##
                AND    **Confirm Field** = ##Confirm Value##',
            'callback' => 'emptyResults',
        ],
        'updateCaseSensitive' => [
            'display' => 'Update Case Sensitive',
            'sql' => '
                UPDATE **Table**
                SET    **Update Field** = ##Update Value##
                WHERE  **Confirm Field** = ##Confirm Value##',
            'check' => '
                SELECT "Found"
                FROM   **Table**
                WHERE  BINARY **Update Field** != ##Update Value##
                AND    **Confirm Field** = ##Confirm Value##',
            'callback' => 'emptyResults',
        ],
        'updateSearchTwo' => [
            'display' => 'Update Row Field Search Two',
            'sql' => '
                UPDATE **Table**
                SET    **Update Field** = ##Update Value##
                WHERE  **Confirm Field** = ##Confirm Value##
                AND    **Second Field** = ##Second Value##',
            'check' => '
                SELECT "Found"
                FROM   **Table**
                WHERE  **Update Field** != ##Update Value##
                AND    **Confirm Field** = ##Confirm Value##
                AND    **Second Field** = ##Second Value##',
            'callback' => 'emptyResults',
        ],
        'dropField' => [
            'display' => 'Drop Field',
            'sql' => '
                ALTER TABLE **Table**
                DROP ++Drop Field++',
            'check' => '
                SHOW FIELDS 
                FROM **Table**
                WHERE Field = ++Drop Field++',
            'callback' => 'emptyResults',
        ],
        'addTable' => [
            'display' => 'Add Table',
            'sql' => NULL,
            'check' => '
                SHOW TABLES LIKE ##Table Name##',
            'callback' => 'hasResults',
        ],
    ];

    /*
    ****************************************************************************
    */

    static function get($index=FALSE)
    {
        return $index ? self::$models[$index] : self::$models;
    }
    
}
