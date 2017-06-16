<?php

namespace models;

use \assembler;

class templates
{
    
    static function standardHeader($app) 
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head><?php
            if (getDefault($app->metaRefresh)) { ?>
                <meta http-equiv="refresh" content="
                    <?php echo $app->metaRefresh; ?>"><?php
            } ?>
            
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title><?php echo $app->getTitle(); ?></title><?php 
            
            $app->loadCSS();

            // Get css for page
            assembler::loadIncludes('css'); 
            
            echo config::getSetting('debug', 'dumpJSVars') 
                ? '<!--' . vardump($app->jsVars) . '-->' 
                : NULL; 
            ?>
            
            <script>
                var jsVars = <?php echo json_encode($app->jsVars); ?>
            </script>
            
            <?php

            $app->loadJS(); 
            
            // Get javascript for page
            assembler::loadIncludes('js');
            ?>
        </head>
        <body>
        <?php
    }
    
    /*
    ****************************************************************************
    */
    
    static function standardFooter() 
    {
        ?>
        </body>
        </html>
        <?php
    }
    
    /*
    ****************************************************************************
    */
}