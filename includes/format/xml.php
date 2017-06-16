<?php

namespace format;

class xml
{
    // function to convert an array to XML using SimpleXML
    static function fromArray($array, $name) {
        $xml = new \SimpleXMLElement('<'.$name.'/>');

        self::elementToXML($array, $xml);
        
        return $xml->asXML();
    }
    
    static function elementToXML($array, &$xml, &$var = '')
    {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(! is_numeric($key)){
                    $subnode = $xml->addChild($key);
                    self::elementToXML($value, $subnode);
                } else {
                    self::elementToXML($value, $xml);
                }
            } else {
                $xml->addChild($key, $value);
            }
        }
    }
}
