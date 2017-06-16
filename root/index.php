<?php

// index.php

/*
********************************************************************************
* LOGIC                                                                        *
********************************************************************************
*/

// Gonna need session vars
session_start();

// Hold output incase of redirects and what not
ob_start();

// Set URL Variables
include '../includes/assembler.php';

assembler::setAutoloader();

/*
********************************************************************************
* MISC FUNCTIONS                                                               *
********************************************************************************
*/

assembler::load('models', 'generalModel');

/*
********************************************************************************
* GENERAL CONFIGURATIONS                                                       *
********************************************************************************
*/

use models\config;

config::init();

$timeRequest = config::getSetting('debug', 'timeRequest');
$startPage = $timeRequest ? timeThis() : NULL;

/*
********************************************************************************
* SET ERROR LOG FILE                                                           *
********************************************************************************
*/

// If this fails due to fatal error the default log is /logs/default/default.log
models\directories::setDefaultLog();
       
// Use site pages array to confirm page exists
access::checkPage();

/*
********************************************************************************
* ASSEMBLE THE MVC OBJECT                                                      *
********************************************************************************
*/

$requestObject = assembler::getMVC();

/*
********************************************************************************
* LOG OUT METHOD                                                               *
********************************************************************************
*/

checkLogout($requestObject);

/*
********************************************************************************
* Check User Access                                                            *
********************************************************************************
*/

access::getUserFromSession($requestObject);

/*
********************************************************************************
* Set Page Title                                                               *
********************************************************************************
*/

$requestObject->setTitle();

/*
********************************************************************************
* Select a database for test runs                                              *
********************************************************************************
*/

assembler::getTestDB($requestObject);

/*
********************************************************************************
* SET CRON LOG FILE                                                           *
********************************************************************************
*/

cronLogging($requestObject);

/*
********************************************************************************
* CHECK ACCESS                                                                 *
********************************************************************************
*/

// Try login if the user credentials have been sent via post vars, 
loginContinue($requestObject);

// If the user doesn't have auth for page, no access
access::required([
    'app' => $requestObject, 
    'terminal' => TRUE,
]);

/*
********************************************************************************
* SITE REDIRECT                                                                *
********************************************************************************
*/

// If a site has been forwarded to a new site, redirect after user access check
siteRedirect();

/*
********************************************************************************
* PRE-OUTPUT LOGIC                                                             *
********************************************************************************
*/

assembler::callMethod($requestObject, 'model');

// Running possible tests
new test\pages($requestObject);

assembler::callMethod($requestObject, 'controller');

// See which classes the assembler has loaded for the controller
assembler::showIncludedClass();

// After controller remove session if this is an API request
access::unsetAPISession();

// Running possible tests
$testResults = new test\pages($requestObject, $afterController=TRUE);
$testErrors = $testResults->errors();

/*
********************************************************************************
* START OUTPUT                                                                 *
********************************************************************************
*/

// No more possible redirects, flush output
ob_end_flush();

/*
********************************************************************************
* DISPLAY                                                                      *
********************************************************************************
*/

// Default page header
! $testErrors ? assembler::callTemplateMethod($requestObject, 'header') : 
    models\templates::standardHeader($requestObject);

/*
********************************************************************************
* REQUEST PAGE VIEW                                                            *
********************************************************************************
*/

! $testErrors ? assembler::callMethod($requestObject, 'view') : NULL;
echo $testErrors;

// Default page footer
! $testErrors ? assembler::callTemplateMethod($requestObject, 'footer') : 
    models\templates::standardFooter($requestObject);

/*
********************************************************************************
* RECORD SQL LOGS                                                              *
********************************************************************************
*/

database\model::writeQueries();
echo $timeRequest ? timeThis($startPage) : NULL;


