<?php

namespace barcodephp\html\config;

class bcgCode128
{

    static $classFile = 'BCGcode128.php';
    static $className = 'BCGcode128';
    static $baseClassFile = 'BCGBarcode1D.php';
    static $codeVersion = '5.2.0';

    static function customSetup($barcode, $get) 
    {
        
        if (isset($get['start'])) {
            $barcode->setStart($get['start'] === 'NULL' ? null : $get['start']);
        }
    }
    
}
