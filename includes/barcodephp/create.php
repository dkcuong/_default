<?php

namespace barcodephp;

use barcodephp\classes\BCGColor;
use barcodephp\classes\BCGDrawing;
use barcodephp\html\includes\functions;


class create
{
    static $requiredKeys = [
        'dpi' => TRUE,
        'code' => TRUE, 
        'text' => TRUE,
        'scale' => TRUE,
        'rotation' => TRUE,
        'fontSize' => TRUE,
        'filetype' => TRUE, 
        'fontFamily' => TRUE,
    ];
    
    static function showError()
    {
        backtrace();
        die;        
    }
    
    static function display($params=[])
    {
        // Check if everything is present in the request
        if (array_diff(self::$requiredKeys, $params)) {
            self::showError();
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $params['code'])) {
            self::showError();
        }

        $code = $params['code'];

        // Dynamic classes... bleh
        $codeClass = 'barcodephp\html\config\\'.$code;
        $codeClassName = 'barcodephp\classes\\'.$code;

        $filetypes = [
            'PNG' => BCGDrawing::IMG_FORMAT_PNG, 
            'GIF' => BCGDrawing::IMG_FORMAT_GIF,
            'JPEG' => BCGDrawing::IMG_FORMAT_JPEG, 
        ];

        $drawException = null;
        try {
            $color_black = new BCGColor(0, 0, 0);
            $color_white = new BCGColor(255, 255, 255);

            // Jonathan Sapp 
            // Modifcation to remove text from output
            $code_generated = new $codeClassName();
            isset($params['noText']) ? $code_generated->setNoText() : FALSE;

            if (function_exists('baseCustomSetup')) {
                
                baseCustomSetup($code_generated, $params);
            }
            
            $codeClass::customSetup($code_generated, $params);

            $code_generated->setScale(max(1, min(4, $params['scale'])));
            $code_generated->setBackgroundColor($color_white);
            $code_generated->setForegroundColor($color_black);

            if ($params['text'] !== '') {
                $text = functions::convertText($params['text']);
                $code_generated->parse($text);
            }
        } catch(Exception $exception) {
            $drawException = $exception;
        }

        $drawing = new BCGDrawing('', $color_white);
        if($drawException) {
            $drawing->drawException($drawException);
        } else {
            $drawing->setBarcode($code_generated);
            $drawing->setRotationAngle($params['rotation']);
            $drawing->setDPI($params['dpi'] === 'NULL' ? null : max(72, min(300, intval($params['dpi']))));
            $drawing->draw();
        }

        if (isset($params['returnOutput'])) {
            ob_start();
            $drawing->finish($filetypes[$params['filetype']]);
            return $image = ob_get_clean();
        }
        
        switch ($params['filetype']) {
            case 'PNG':
                header('Content-Type: image/png');
                break;
            case 'JPEG':
                header('Content-Type: image/jpeg');
                break;
            case 'GIF':
                header('Content-Type: image/gif');
                break;
        }
        
        $drawing->finish($filetypes[$params['filetype']]);
    }
}
