<?php

namespace pdf;

class creator extends \tcpdf 
{
    public $html;
    
    public $cellAttrs = [
        'ln',
        'fill',
        'link',
        'text',
        'align',
        'width',
        'height',
        'border',
        'stretch',
        'centerAlign',
        'verticalAlign',
        'ignoreMinHeight',
    ];
    
    public $multiCellAttrs = [
        'width',
        'height',
        'text',
        'border',
        'align',
        'fill',
        'ln',
        'x',
        'y',
        'reseth',
        'stretch',
        'ishtml',
        'autopadding',
        'maxh',
        'valign',
        'fitcell',
    ];
    
    public $attrValues = [
        'passed' => [],
        'stored' => [],
        'default' => [
            'centerAlign' => 'T',
            'verticalAlign' => 'M',
        ],
    ];
        
    /*
    ****************************************************************************
    */

    function writePDFPage($orientation=NULL)
    {
        $this->setPrintHeader(FALSE);
        $this->setPrintFooter(FALSE);
        $this->addPage($orientation, ['PZ' => .20]);        
        $this->writeHTML($this->html);
        return $this;
    }
        
    /*
    ****************************************************************************
    */
    
    function addLicensePlate($plate, $date)
    {
        $imagePath = 'http://localhost/wms/classes/barcodephp/html/image.php'
            .'?filetype=PNG&dpi=72&scale=1&rotation=0&noText'
            .'&font_family=Arial.ttf&font_size=10&thickness=30'
            .'&checksum=&code=BCGcode128&text='.$plate;
        ob_start(); ?>
        <html>
        <style>
            body {
                text-align: center;
                font-size: 40px;
            }
            img {
                width: 700px;
                height: 250px;
            }
        </style>
        <body>
            <div>License Plate <?php echo $plate.' '.$date; ?></div>
            <div><img src="<?php echo $imagePath; ?>"></div>
        </body>
        </html>
        <?php 
        $this->html = ob_get_clean();
        $this->writePDFPage('L')->writePDFPage('L')
             ->writePDFPage('L')->writePDFPage('L');
        
        return $this;
    }

    /*
    ****************************************************************************
    */
    
    function addModel($model, $target, $date)
    {
        return call_user_func([$model, 'addToPDF'], $target, $date);
    }

    /*
    ****************************************************************************
    */

    function setStoredAttr($name, $value)
    {
        $this->attrValues['stored'][$name] = $value;
    }
        
    /*
    ****************************************************************************
    */

    function htmlCell($params)
    {
        $attrs = [];
        
        $this->attrValues['passed'] = $params;
        
        foreach ($this->cellAttrs as $name) {
            $attrs[$name] = $this->getAttr($name);
        }
        
        $this->cell($attrs['width'], $attrs['height'], $attrs['text'], 
            $attrs['border'], $attrs['ln'], $attrs['align'], $attrs['fill'], 
            $attrs['link'], $attrs['stretch'], $attrs['ignoreMinHeight'], 
            $attrs['centerAlign'], $attrs['verticalAlign']);
    }

    /*
    ****************************************************************************
    */
    
    function htmlMultiCell($params)
    {
        $attrs = [];
        
        $this->attrValues['passed'] = $params;
        
        foreach ($this->multiCellAttrs as $name) {
            $attrs[$name] = $this->getAttr($name);
        }
  
        $this->MultiCell($attrs['width'], $attrs['height'], $attrs['text'], 
            $attrs['border'], $attrs['align'], $attrs['fill'], $attrs['ln'], 
            $attrs['x'], $attrs['y'], $attrs['reseth'], $attrs['stretch'], 
            $attrs['ishtml'], $attrs['autopadding'], $attrs['maxh'], 
            $attrs['valign'], $attrs['fitcell']);
    }

    /*
    ****************************************************************************
    */
    
    function getAttr($name)
    {
        // Use passed values first
        // Then use the stored value if it is available
        // Then use defaults
        // Otherwise nothing

        foreach ($this->attrValues as $values) {
            if (isset($values[$name])) {
                return $values[$name];
            }
        }

        return NULL;
    }

    /*
    ****************************************************************************
    */
}
