<?php

namespace EDI;

class shipping 
{
    public $interface = [
        'ISA' => [
           'name' => 'begin transaction',
           'values' => 'store message info',
        ],
        'GS' => [
           'values' => 'store message info',
        ],
        'ST' => [
           'name' => 'start order',
           'values' => [
              'format',
              'messageOrderID',
           ],
        ],
        'W05' => [
           'name' => 'shipping order identification',
           'values' => [
              'original boolean',
              'depositor order ID',
              'customer po id',
           ],
        ],
        'N1' => [
            'name' => 'Shipping info',
            'values' => [
                'SF' => [
                 //   'Ship From',
                    'warehouse',  
                    'ship from qualifier', 
                    'warehouse code',
                ],
                'BY' => [
                //    'Buying Party',
                    'empty',
                    'buyer qualifier',
                    'customer code',
                ],
                'ST' => [
                //    'Ship To',
                    'ship to',
                    'ship to qualifier',
                    'store number',
                ],
            ],
        ],
        'N2' => [
            'name' => 'Additional Name Information – Ship To',
            'values' => [
                'Additional Name Information',
            ],
        ],
        'N3' => [
            'name' => 'Address Information – Ship To',
            'values' => [
                'Street Address',
                'Address Information'
            ],
        ],
        'N4' => [
            'values' => [
                'city',
                'state',
                'zip',
                'country',
            ],
        ],
        'N9' => [
            'values' => [
                'ZZ' => [
                    'empty',
                    'instructions',
                ],
                'DI' => [
                    'empty',
                    'internal code',
                ],
            ],
        ],
        'G62' => [
           'name' => 'qualifiers',
           'values' => [
                '10' => [
                    'requested ship date'
                ],
                '02' => [
                    'delivery requested on this date'
                ],
                '04' => [
                    'delivery requested on this date'
                ],
            ],   
        ],
        'W66' => [
           'name' => 'Warehouse Carrier Information',
           'values' => [
                'Ship Method of Payment',
                'mutually agreed upon qualifier',
                'empty',
                'empty',
                'empty',
                'point of origin qualifier',
                'FOB Point',
                'empty',
                'empty',
                'Carrier SCAC',
            ],
        ],
        'LX' => [
            'name' => 'assigned number',
            'values' => [
                'assigned number',
            ],
        ],
        'W01' => [
            'name' => 'line item detail',
            'values' => [
                'quantity ordered',
                'unit/base measurment code',
                'upc case code',
                'upc qualifier',
                'product code',
                'vendor item qualifier',
                'item number',
            ],
        ],
        'G69' => [
            'name' => 'Line Item Detail',
            'values' => [
                'Free Form Description',
            ],
        ],
        'W76' => [
            'name' => 'Total Shipping Order',
            'values' => [
                'Total Quantity Ordered',
            ],
        ],
        'SE' => [
            'name' => 'Transaction Set Trailer',
            'values' => [
                'Number of Included Segments',
                'Transaction  Set Control Number',
            ],
        ],
        'GE' => [
            'values' => [
                'ge',
                'gID',
            ],
        ],
        'IEA' => [
            'values' => [
                'iea',
                'iaID',
            ],
        ],
    ];
    
    /*
    ****************************************************************************
    */

    function __construct($db)
    {
        $this->db = $db;
    }
       
    /*
    ****************************************************************************
    */

    function insertOrders()
    {
        $db = $this->db;
        $transmissionIDs = array_keys($this->data);

        $sql = 'SELECT column_13,
                       id
                FROM   edi.appliance_messages
                WHERE  column_13 IN ('.$db->getQMarkString($transmissionIDs).')';

        $foundIDs = $db->queryResults($sql, $transmissionIDs);
        
        foreach ($this->data as $tranID => $transaction) {
            $transacionInfo = array_merge(
                [NULL],
                $transaction['ISA'],
                $transaction['GS']
            );
            
            $lastID = NULL;
            if (isset($foundIDs[$tranID])) {
                $lastID = $foundIDs[$tranID]['id'];
            } else {
                $sql = 'INSERT INTO edi.appliance_messages
                        VALUES ('.$db->getQMarkString($transacionInfo).')';

                $db->runQuery($sql, $transacionInfo);

                $lastID = $db->lastInsertID();
            }
            
            foreach ($transaction['orders'] as $order) {
                $order['transactionID'] = $lastID;
                $dbFields = [];
                foreach (array_keys($order) as $key) { 
                    $dbFields[] = str_replace([' ', '/'], '_', $key);
                }
                
                $sql = 'INSERT IGNORE edi.appliance_orders
                        ('.implode(', ', $dbFields).')
                        VALUES ('.$db->getQMarkString($order).')';

                $db->runQuery($sql, array_values($order));
            }            
            
            $sql = 'INSERT IGNORE edi.appliance_files
                    (name, messageID) VALUES (?, ?)';

            $db->runQuery($sql, [$this->fileName, $lastID]);
        }
    }

    /*
    ****************************************************************************
    */

    function parseTransaction($fileName)
    {
        $this->fileName = basename($fileName);
        
        $content = file_get_contents($fileName);
        
        $lines = explode('~', $content);
        $timmedLines = array_map('trim', $lines);

        $data = [];
        $messageID = NULL;
        $messageOrderID = NULL;
        foreach ($timmedLines as $lineNumber => $line) {
            $elements = explode('*', $line);
            $lineID = array_shift($elements);
            
            $values = getDefault($this->interface[$lineID]['values'], []);
            
            switch ($lineID) {
                case 'ISA':
                    $messageID = $elements[12];
                case 'GS':
                    $data[$messageID][$lineID] = $elements;
                    break;
                case 'ST':
                    $elements = array_combine($values, $elements);
                    $messageOrderID = $elements['messageOrderID'];
                    $data[$messageID]['orders'][$messageOrderID] = $elements;
                    break;
                case 'W05':
                case 'N2':
                case 'N3':
                case 'N4':
                case 'W66':
                case 'LX':
                case 'W01':
                case 'G69':
                case 'W76':
                case 'SE':
                case 'GE':
                case 'IEA':
                    $this->checkArrays($values, $elements);
                    $elements = array_combine($values, $elements);
                    $data[$messageID]['orders'][$messageOrderID] += $elements;
                    break;
                case 'N1':
                case 'N9':
                case 'G62':
                    $type = array_shift($elements);
                    $values = $this->interface[$lineID]['values'][$type];
                    if (! isset($this->interface[$lineID]['values'][$type])) {
                        vardump($lineID);
                    }
                    $this->checkArrays($values, $elements);
                    $elements = array_combine($values, $elements);
                    if ($type == 'ZZ') {
                        foreach ($elements as $name => $value) {
                            $data[$messageID]['orders'][$messageOrderID][$name]
                                = isset($data[$messageID]['orders'][$messageOrderID][$name])
                                ? $data[$messageID]['orders'][$messageOrderID][$name] : NULL;
                            $data[$messageID]['orders'][$messageOrderID][$name] .= $value;
                        }
                    } else {
                        $data[$messageID]['orders'][$messageOrderID] += $elements;
                    }
                    break;
                default:
                    $data[$messageID][$lineID] = $elements;                    
            }
        }

        $this->data = $data;
        
        $this->insertOrders();
    }
    
    /*
    ****************************************************************************
    */

    function checkArrays($values, $elements) 
    {
        if (count($values) != count($elements)) {
            vardump($values);
            vardump($elements);
        }
    }

    /*
    ****************************************************************************
    */


}
