<?php 

class model extends base 
{
    public $defaultValues = [
        'filetype' => 'PNG',
        'dpi' => '72',
        'scale' => '1',
        'rotation' => '0',
        'font_family' => 'Arial.ttf',
        'font_size' => '10',
        'thickness' => '30',
        'checksum' => '',
        'code' => 'BCGcode128'
    ];
    
    public $imageKeys = [];

    public $codeVersion = '5.2.0';

    function customSetup($barcode, $get) {
        if (isset($get['start'])) {
            $barcode->setStart($get['start'] === 'NULL' ? null : $get['start']);
        }
    }

    function showError() {
              backtrace(); die;

        header('Content-Type: image/png');
        readfile('error.png');
        exit;
    }
    
    function registerImageKey($key, $value) {
        $this->imageKeys[$key] = $value;
    }

    function getImageKeys() {
        return $this->imageKeys;
    }

    function getElementHtml($tag, $attributes, $content = false) {
        $code = '<' . $tag;
        foreach ($attributes as $attribute => $value) {
            $code .= ' ' . $attribute . '="' . htmlentities(stripslashes($value), ENT_COMPAT) . '"';
        }

        if ($content === false || $content === null) {
            $code .= ' />';
        } else {
            $code .= '>' . $content . '</' . $tag . '>';
        }

        return $code;
    }

    function getInputTextHtml($name, $currentValue, $attributes = []) {
        $defaultAttributes = array(
            'id' => $name,
            'name' => $name
        );

        $finalAttributes = array_merge($defaultAttributes, $attributes);
        if ($currentValue !== null) {
            $finalAttributes['value'] = $currentValue;
        }

        return getElementHtml('input', $finalAttributes, false);
    }

    function getOptionGroup($options, $currentValue) {
        $content = '';
        foreach ($options as $optionKey => $optionValue) {
            if (is_array($optionValue)) {
                $content .= '<optgroup label="' . $optionKey . '">' .
                    getOptionGroup($optionValue, $currentValue) . '</optgroup>';
            } else {
                $optionAttributes = [];
                if ($currentValue == $optionKey) {
                    $optionAttributes['selected'] = 'selected';
                }
                $content .= getOptionHtml($optionKey, $optionValue, $optionAttributes);
            }
        }

        return $content;
    }

    function getOptionHtml($value, $content, $attributes = []) {
        $defaultAttributes = array(
            'value' => $value
        );

        $finalAttributes = array_merge($defaultAttributes, $attributes);

        return getElementHtml('option', $finalAttributes, $content);
    }

    function getSelectHtml($name, $currentValue, $options, $attributes = []) {
        $defaultAttributes = array(
            'size' => 1,
            'id' => $name,
            'name' => $name
        );

        $finalAttributes = array_merge($defaultAttributes, $attributes);
        $content = getOptionGroup($options, $currentValue);

        return getElementHtml('select', $finalAttributes, $content);
    }

    function getCheckboxHtml($name, $currentValue, $attributes = []) {
        $defaultAttributes = array(
            'type' => 'checkbox',
            'id' => $name,
            'name' => $name,
            'value' => isset($attributes['value']) ? $attributes['value'] : 'On'
        );

        $finalAttributes = array_merge($defaultAttributes, $attributes);
        if ($currentValue == $finalAttributes['value']) {
            $finalAttributes['checked'] = 'checked';
        }

        return getElementHtml('input', $finalAttributes, false);
    }

    function getButton($value, $output = null) {
        $escaped = false;
        $finalValue = $value[0] === '&' ? $value : htmlentities($value);
        if ($output === null) {
            $output = $value;
        } else {
            $escaped = true;
        }

        $code = '<input type="button" value="' . $finalValue . '" data-output="'
            . $output . '"' . ($escaped ? ' data-escaped="true"' : '') . ' />';
        return $code;
    }

    /**
     * Returns the fonts available for drawing.
     *
     * @return string[]
     */
    function listfonts($folder) {
        $array = [];
        if (($handle = opendir($folder)) !== false) {
            while (($file = readdir($handle)) !== false) {
                if(substr($file, -4, 4) === '.ttf') {
                    $array[$file] = $file;
                }
            }
        }
        closedir($handle);

        array_unshift($array, 'No Label');

        return $array;
    }

    /**
     * Returns the barcodes present for drawing.
     *
     * @return string[]
     */
    function listbarcodes() {
        include_once('barcode.php');

        $availableBarcodes = [];
        foreach ($supportedBarcodes as $file => $title) {
            if (file_exists($file)) {
                $availableBarcodes[$file] = $title;
            }
        }

        return $availableBarcodes;
    }

    function findValueFromKey($haystack, $needle) {
        foreach ($haystack as $key => $value) {
            if (strcasecmp($key, $needle) === 0) {
                return $value;
            }
        }

        return null;
    }

    function convertText($text) {
        $text = stripslashes($text);
        if (function_exists('mb_convert_encoding')) {
            $text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        }

        return $text;
    }    
}