<?php

namespace tables;

class databaseCheck
{
    public static $update = [
//        [
//            'check' => 'SHOW KEYS FROM bill_of_lading WHERE Key_name= "newOrderId"',
//            'update' => 'ALTER TABLE bill_of_lading ADD UNIQUE (newOrderId)',
//        ],
        [
            'check' => 'SELECT id FROM inventory_cartons WHERE id = 0',
            'update' => 'DROP TABLE IF EXISTS `inv_keys`;
                
                        CREATE TABLE IF NOT EXISTS `inv_keys` (
                            `id` INT(10) NOT NULL AUTO_INCREMENT,
                            `sku` VARCHAR(25) NULL DEFAULT NULL,
                            `uom` INT(3) NULL DEFAULT NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
                        
                        DROP TABLE IF EXISTS `inv_keys_info`;
                        DROP TABLE IF EXISTS `inv_keys_cartons`;
                        
                        CREATE TABLE IF NOT EXISTS `inv_keys_cartons` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT,
                            `invKey` INT(10) NOT NULL,
                            `locID` INT(7) NULL DEFAULT NULL,
                            `plate` INT(9) NULL DEFAULT NULL,
                            `statusID` INT(2) NOT NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
                        
                        INSERT INTO inv_keys (
                            uom, 
                            sku
                        ) 
                        SELECT    uom,
                                  sku
                        FROM 	  inventory_cartons ca
                        LEFT JOIN inventory_batches b ON b.id = ca.batchID
                        GROUP BY sku, uom;

                        INSERT INTO inv_keys_cartons (
                            invKey, 
                            locID, 
                            statusID, 
                            plate
                        )
                        SELECT ik.id, locID, statusID, plate
                        FROM (SELECT    ca.locID, 
                                        ca.plate, 
                                        ca.statusID, 
                                        ca.uom,
                                        b.sku
                              FROM 	    inventory_cartons ca
                              LEFT JOIN inventory_batches b ON b.id = ca.batchID) iki
                        LEFT JOIN inv_keys ik ON ik.uom = iki.uom AND ik.sku = iki.sku;
                              

                        ',
        ],
    ];

    /*
    ****************************************************************************
    */

    static function check($app)
    {
        $output = [];
        
        foreach (self::$update as $value) {
            $results = $app->queryResults($value['check']);
            if ($results) {
                $output[] = [
                    'check' => $value['check'],
                    'update' => FALSE,
                ];
            } else {
                $app->runQuery($value['update'], NULL, 
                        'Error running update:<br>' . $value['update']);
                $output[] = [
                    'check' => $value['check'],
                    'update' => $value['update'],
                ];
            }
        }
        
        return $output;
    }    
}






