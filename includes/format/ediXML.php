<?php

namespace format;

class ediXML extends xml
{
    static function createEDI($data,
                              $ver = 'xml version="1.0"',
                              $tag = 'File',
                              $file = '')
    {
        $xml = new \SimpleXMLElement('<?'.$ver.'?>'.'<'.$tag.'></'.$tag.'>');

        self::elementToXML($data, $xml);
        if ($file) {
            $xml->asXML($file);
        } else {
            return $xml->asXML();
        }
    }
}
