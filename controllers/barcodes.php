<?php 

/*
********************************************************************************
* EMPTY CLASS CONTROLLER METHODS                                               *
********************************************************************************
*/

class controller extends template
{

    function displayBarcodesController()
    {
        $requiredKeys = array('code', 'filetype', 'dpi', 'scale', 'rotation', 
            'font_family', 'font_size', 'text');

        $this->get = array_merge($this->defaultValues, $this->get);
        // Check if everything is present in the request
        foreach ($requiredKeys as $key) {
            if (! isset($this->get[$key])) {
                $this->showError();
            }
        }

        if (! preg_match('/^[A-Za-z0-9]+$/', $this->get['code'])) {
            $this->showError();
        }

        $this->filetypes = array(
            'PNG' => barcodephp\classes\BCGDrawing::IMG_FORMAT_PNG, 
            'JPEG' => barcodephp\classes\BCGDrawing::IMG_FORMAT_JPEG, 
            'GIF' => barcodephp\classes\BCGDrawing::IMG_FORMAT_GIF
        );

        $drawException = null;
        try {
            $color_black = new barcodephp\classes\BCGColor(0, 0, 0);
            $color_white = new barcodephp\classes\BCGColor(255, 255, 255);

            // Jonathan Sapp 
            // Modifcation to remove text from output
            $code_generated = new barcodephp\classes\BCGcode128();
            isset($this->get['noText']) ? $code_generated->setNoText() : FALSE;

            if (function_exists('baseCustomSetup')) {
                baseCustomSetup($code_generated, $this->get);
            }

            if (function_exists('customSetup')) {
                customSetup($code_generated, $this->get);
            }

            $code_generated->setScale(max(1, min(4, $this->get['scale'])));
            $code_generated->setBackgroundColor($color_white);
            $code_generated->setForegroundColor($color_black);

            if ($this->get['text'] !== '') {
                $text = $this->convertText($this->get['text']);
                $code_generated->parse($text);
            }
        } catch (Exception $exception) {
            $drawException = $exception;
        }

        $drawing = new barcodephp\classes\BCGDrawing('', $color_white);
        if ($drawException) {
            $drawing->drawException($drawException);
        } else {
            $drawing->setBarcode($code_generated);
            $drawing->setRotationAngle($this->get['rotation']);
            
            $validDpi = $this->get['dpi'] === 'NULL';
            $maxValue = max(72, min(300, intval($this->get['dpi'])));
            $drawing->setDPI($validDpi ? null : $maxValue);
            $drawing->draw();
        }

        $this->drawing = $drawing;
    }

    /*
    ****************************************************************************
    */
}