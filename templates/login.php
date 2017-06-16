<?php 

class template extends model
{   
    
    function header()
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta http-equiv="Content-Type" 
                content="text/html; charset=utf-8" /> 
            <!-- CSS -->
            <?php
            // Get css for page
            assembler::loadIncludes('css');
            ?>
        </head>
        <body>
            <table width="100%">
            <tr>
            <td width="100%" style="padding-top:5px" valign="top">
        <?php
    }  
    
    /*
    ****************************************************************************
    */
    
    function footer() 
    {
        ?>
            </td>
            </tr>
            </table
        </body>
        </html>
        <?php
    }
}