<?php

namespace jQuery;

use \models\config;

class loginDialog 
{
    
    /*
    ****************************************************************************
    */
    
    function __construct($mvc)
    {
        $this->mvc = $mvc;
        
        $mvc->includeJS['js/crypto/md5.js'] = TRUE;
        $mvc->includeJS['js/jQuery/loginDialog.js'] = TRUE;
        
        $mvc->includeCSS['css/jQuery/loginDialog.css'] = TRUE;

        $mvc->jsVars['sessionDuration'] = 
            config::getSetting('durations', 'session');
        
        $mvc->jsVars['urls']['dialogLogin'] = jsonLink('dialogLogin');

        $requestClass = config::get('site', 'requestClass');
        $requestMethod = config::get('site', 'requestMethod');
        
        $mvc->jsVars['urls']['loginPage'] = 
            makeLink($requestClass, $requestMethod);
        
        $this->setDialogHTML();
    }

    /*
    ****************************************************************************
    */
    
    function setDialogHTML()
    {
        ob_start(); ?>
        <div id="loginDialog" title="Log In">
        <div id="loginDialogMessage">
        Your session has expired.<br>
        If you would like to continue, please log in.
        </div>
        Username<br>
        <input class="text ui-widget-content ui-corner-all" id="loginDialogUsername" type="text"><br>
        Password<br>
        <input class="text ui-widget-content ui-corner-all" id="loginDialogPassword" type="password"><br>
        </div>
        <?php return $this->mvc->dialogHTML = ob_get_clean();
    }
}
