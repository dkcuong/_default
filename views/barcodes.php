<?php

/*
********************************************************************************
* EMPTY CLASS VIEW METHODS                                                     *
********************************************************************************
*/

class view extends controller
{

    function displayBarcodesView()
    {
        switch ($this->get['filetype']) {
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

        $this->drawing->finish($this->filetypes[$this->get['filetype']]);
    }
    
    /*
    ****************************************************************************
    */

    function method2_EmptyView()
    {
        ?>
        
        <?php    
    }    

}