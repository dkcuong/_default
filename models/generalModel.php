<?php

// GeneralModel.php

use models\config;

/*
********************************************************************************
* PARSE                                                                        *
********************************************************************************
*/

function parseDateOption(&$name)
{
    // Return if the name is not an array
    if (! strpos($name, '[')) {
        return [
            'name' => $name,
            'dateCompare' => FALSE,
        ];
    }

    $option = [];
    parse_str($name, $option);
    $optionName = array_keys($option);
    $array = reset($option);
    $subValue = array_keys($array);
    return [
        'name' => reset($optionName),
        'dateCompare' => reset($subValue),
    ];
}

/*
********************************************************************************
* DISPLAY                                                                      *
********************************************************************************
*/

function gcm($class)
{
    vardump(get_class_methods($class));
}

/*
********************************************************************************
*/

function getClass($object)
{
    $classPath = get_class($object);
    $classPath = str_replace('\\', '/', $classPath);
    return basename($classPath);
}

/*
********************************************************************************
*/

function getMicroTime()
{
    return microtime(TRUE);
}

/*
********************************************************************************
*/

function timeThis($start = FALSE)
{
    return $start ? getMicroTime() - $start : getMicroTime();
}

/*
********************************************************************************
*/

function displayArray($array, $showTitles=TRUE)
{
    ?>
    <table border="1" style="border-collapse: collapse;">
    <?php if ($showTitles) { ?>
        <tr bgcolor="#dddddd">
        <?php foreach ($array[0] as $title => $cell) { ?>
            <td><?php echo $title;?></td>
        <?php } ?>
        </tr>
    <?php } ?>
    <?php foreach ($array as $row) { ?>
        <tr>
        <?php foreach ($row as $cell) { ?>
            <td><?php echo $cell;?></td>
        <?php } ?>
        </tr>
    <?php } ?>
    </table>
    <?php
}

/*
********************************************************************************
*/

function userVarDump($variable, $username, $params=[])
{
    $loggedUser = access::getUserInfoValue('username');

    $params['depth'] = 3;

    if ($username != $loggedUser) {
        return;
    }

    varDump($variable, $params);

    ! isset($params['die']) or die('VarDump Die Request');
}

/*
********************************************************************************
*/

// Typing underscore in var dump is annoying
function varDump($variable, $params=[])
{
    $dumpDepth = getDefault($params['depth']);
    $printMode = getDefault($params['printMode']);

    $info = $dumpDepth ? callerLine(['depth' => $dumpDepth]) : callerLine();

    if ($printMode) {
        ob_start();
        var_dump($variable);
        return ob_get_clean();
    }

    echo '<pre>'.$info.'<br>';
    var_dump($variable);
    echo '</pre>';

}

/*
********************************************************************************
*/

// Typing underscore in var dump is annoying
function dieDump($variable, $params=[])
{
    $params['depth'] = getDefault($params['depth'], 3);

    varDump($variable, $params);
    die;
}

/*
********************************************************************************
*/

function colapsedParam($arg, $type)
{
    ?>
    <div style="cursor:pointer;" onclick="toggleParamDisplay(this);">
    <div id="divHide"><pre><?php echo $type; ?> (click to show)</pre></div>
    <div id="divShow" style="display: none;">
    <pre><?php echo $type; ?> (click to hide):</pre><?php varDump($arg); ?>
    </div>
    </div>
    <?php
}

/*
********************************************************************************
*/

function callerLine($params=[])
{
    $depth = getDefault($params['depth'], 2);
    $sourceInfo = traceInfo(['file', 'line'], ['depth' => $depth]);
    return $sourceInfo['file'].': '.$sourceInfo['line'];
}

/*
********************************************************************************
*/

function traceInfo($values=[], $params=[])
{
    $depth = getDefault($params['depth'], 2);
    $keys = $values ? array_flip($values) : [];
    $backtrace = debug_backtrace();
    $traceInfo = $backtrace[$depth];
    return $keys ? array_intersect_key($traceInfo, $keys) : $traceInfo;
}

/*
********************************************************************************
*/

function backTrace(
    $sql=FALSE,
    $debugFronttrace=array(),
    $error=FALSE,
    $queryTime=FALSE,
    $selectConnection=FALSE
) {

    if (empty($debugFronttrace)) {
        $debugBacktrace = debug_backtrace();
        $debugFronttrace = array_reverse($debugBacktrace);
    }

    $resultArray = array();

    // Have to reproduce the results otherwise the orignial results will get
    // used up
    if ($sql) {
        $result = $selectConnection ?
            mysql_query($sql,$selectConnection) : mysql_query($sql);
    }

    ?><style type="text/css">
    .debugTable td {
        background: #EDC;
        padding: 5px;
    }
    #functionCell {
        background: #CDB;
    }
    #argumentCell {
        background: #CDE;
    }
    #locationCell {
        background: #EEC;
    }
    #emptyCell {
        background: none;
        height: 30px;
    }
    </style>
    <script>
    function toggleParamDisplay(theDiv)
    {
        var childDivs = theDiv.getElementsByTagName('div');
        ['divHide', 'divShow'].map(function (divID) {
            if (typeof(childDivs[divID].style) != 'undefined') {
                var changeDisplay =
                    childDivs[divID].style.display != 'none' ?
                    'none' : 'block';
                childDivs[divID].style.display = changeDisplay;
            }
        });
    }
    </script>
    <table class="debugTable" width="750"><?php
    if ($_SERVER['QUERY_STRING']) { ?>
        <tr>
            <td><b>URL Query String:</b> <?php
                echo $_SERVER['QUERY_STRING']; ?></td>
        </tr><?php
    }
    foreach ($debugFronttrace as $entry) { ?>
        <tr>
            <td id="locationCell"><b>Location:</b>
            <a target="_blank" href="<?php echo $entry['file']; ?>" style="text-decoration: none;color:#440">
            <?php echo $entry['file']; ?>
            line <?php echo $entry['line']; ?></td>
        </tr>
        <tr>
            <td id="functionCell"><b>Function:</b> <?php
                echo $entry['function']; ?></td>
        </tr>
        <?php
        if (isset($entry['args'][0])) {
            foreach ($entry['args'] as $arg) { ?>
                <tr>
                <td id="argumentCell"><b>Argument:</b><?php
                // get rid of spaces before newlines in queries
                if (is_object($arg)) {
                    colapsedParam($arg, 'Object');
                } else if (is_array($arg)) {
                    colapsedParam($arg, 'Array');
                } else {
                    varDump(preg_replace("/\n(\s+)/", "\n", $arg));
                } ?>
                </td>
                </tr><?php
            }
        }
    }

    // Display results
    if (isset($result)
    &&  is_resource($result)
    &&  mysql_num_rows($result) > 0
    ) {
        $resultArray = mysqlArray($result, $indexColumns=array(0), $glue=NULL, MYSQL_ASSOC);
    }

    if (! empty($resultArray)) { ?>
        <tr>
            <td><b>Associative Result(s):</b>
            <?php varDump($resultArray); ?></td>
        </tr>
    <?php }

    // If set display query times
    $displayingQueryTimes = config::getSetting('debug', 'queryTimes');
    if ($displayingQueryTimes && $queryTime) { ?>
        <tr>
            <td><b>Query Time:</b>
            <?php echo $queryTime.' seconds'; ?></td>
        </tr>
    <?php }
    if ($error) { ?>
        <tr>
            <td><b>Error:</b> <?php
                echo $error; ?></td>
        </tr><?php
    }
    ?>

    <tr>
        <td id="emptyCell"></td>
    </tr></table><?php
}

/*
********************************************************************************
*/

function downloadLink($prefix, $displayName, $id, $inputName, $class=FALSE)
{
    $classText = $class ? ' class="'.$class.'"' : NULL;
    return '<a'.$classText.' href="'
            .makeLink('downloads', 'get', 'target/'.$prefix.'/id/'.$id.'/name/'.$inputName)
            .'">'.$displayName.'</a>';
}

/*
********************************************************************************
*/

function makeAppLink($app, $class, $params=[])
{
    $var = getDefault($params['var']);
    $method = getDefault($params['method']);
    $absolutePath = getDefault($params['absolutePath']);


    if (is_array($var)) {
        $vars = [];
        foreach ($var as $name => $value) {
            $vars[] = $name;
            $vars[] = $value;
        }
        $var = implode('/', $vars);
    }

    $queryArray = [$class, $method, $var];

    $finalQuery = array_filter($queryArray);

    $siteInfo = config::get('site');

    $base = getBase($absolutePath);

    $appBase = $app == $siteInfo['appName'] ? $base :
        str_replace($siteInfo['appName'], $app, $base);

    $url = $appBase.'/'.implode('/', $finalQuery);

    return $url;
}

/*
********************************************************************************
*/

function makeLink($class, $method=NULL, $var=NULL, $absolutePath=FALSE)
{
    $app = config::get('site', 'appName');

    return makeAppLink($app, $class, [
        'method' => $method,
        'var' => $var,
        'absolutePath' => $absolutePath,
    ]);
}

/*
********************************************************************************
*/

function customJSONLink($class, $method=NULL, $vars=NULL, $absolutePath=FALSE)
{
    $varString = is_array($vars) ? http_build_query($vars) : $vars;

    $base = getBase($absolutePath);

    $url = $base.'?class='.$class.'&method='.$method.'&'.$varString;

    return $url;
}

/*
********************************************************************************
*/

function getBase($absolutePath)
{
    return $absolutePath ? config::getAppURL() : config::get('site', 'uriBase');
}

/*
********************************************************************************
*/

function jsonLink($method, $vars=[])
{
    $qsVars = NULL;
    if ($vars) {
        foreach ($vars as $name => $value) {
            $qsVars .= '&'.$name.'='.$value;
        }
    }

    $jsonBase = config::get('site', 'jsonBase');
    $url = $jsonBase.'?method='.$method.$qsVars;

    return $url;
}

/*
********************************************************************************
*/

function showLink($display, $class, $method=NULL, $var=NULL, $absolutePath=FALSE)
{
    $link = '<a href="'.makeLink($class, $method, $var, $absolutePath).'">'.$display.'</a>';

    return $link;
}

/*
********************************************************************************
*/

function classVarName()
{
    return 'class';
}

/*
********************************************************************************
* DEBUG                                                                        *
********************************************************************************
*/

function redirect($query=NULL)
{
    $crawlModel = config::getSetting('debug', 'crawlMode');

    if ($crawlModel) {
        backtrace();
        echo '<a href="'.$query.'"><pre>
           *****************************************************************
           ********DEBUG MODE ACTIVE: CLICK HERE FOR MANUAL REDIRECT********
           *****************************************************************
        </pre></a>';
    } else {
        header('Location: '.$query);
    }
    die();
}

/*
********************************************************************************
*/

function siteRedirect()
{
    $redirectSite = config::getSetting('misc', 'redirectSite');

    if ($redirectSite) {
        redirect($redirectSite);
    }
}

/*
********************************************************************************
* FORMATTING/VALIDATION                                                        *
********************************************************************************
*/

function getSubDirs($sessionToken)
{
    return array(
        substr($sessionToken, 0, 1),
        substr($sessionToken, 1, 2),
    );
}

/*
********************************************************************************
*/

function getExtension($string, $lowerCase = TRUE)
{
    $explosion = explode('.', $string);
    $extension = array_pop($explosion);
    return $lowerCase ? strtolower($extension) : $extension;
}

/*
********************************************************************************
*/

function getFirstElement($array=array(), $trigger=TRUE)
{
    return $trigger ? array_pop($array) : $array;
}

/*
********************************************************************************
*/

function nameArray($fullName)
{
    if (! $fullName) {
        return FALSE;
    }
    $nameArray = explode(' ', $fullName);
    $employee = array();
    $employee['firstName'] = array_shift($nameArray);
    $employee['lastName'] = array_pop($nameArray);
    return $employee;
}

/*
********************************************************************************
*/

function formatQueryString($string)
{
    return $string ? '?'.$string : NULL;
}

/*
********************************************************************************
*/

function changeNewLines($string, $replace="\n")
{
    $newLineASCIIs = array(
        "\n",
        "\r\n",
        "\r",
    );

    return str_replace($newLineASCIIs, $replace, $string);
}

/*
********************************************************************************
*/

function setDefaultArray(
    $array,
    $methodArray,
    $default=NULL,
    $checkFunction=FALSE,
    $returnFunction=FALSE
) {
    $returnArray = array();

    // Similar to getDefault but compares an array to a method array (GET/POST)
    foreach ($array as $name => $value) {
        // Is it an associative array?
        if (isset($methodArray[$name])) {
            $check = $checkFunction ? call_user_func($checkFunction, $methodArray[$name]) : $value;

            $returnValue = $returnFunction ? call_user_func($returnFunction, $methodArray[$name]) : $value;

            // If default variable is set to 'previous', use the initial value as default
            $default = $default == 'previous' ? $value : $default;

            $returnArray[$name] = $check ? $returnValue : $default;
        } elseif (isset($methodArray[$value])) {
            $check = $checkFunction ? call_user_func($checkFunction, $methodArray[$value]) : NULL;

            $returnValue = $returnFunction ? call_user_func($returnFunction, $methodArray[$value]) : NULL;

            // If default variable is set to 'previous', use the initial value as default
            $default = $default == 'previous' ? NULL : $default;

            $returnArray[$value] = $check ? $returnValue : $default;
        }

    }

    return $returnArray;
}

/*
********************************************************************************
*/

function setDefault(&$variable, &$value=NULL, $default=NULL, $function=FALSE)
{
    // Make variable the default if it isnt set
    $variable = isset($variable) ? $variable : $default;

    // If the value and function are set, run the function no the value
    $value = isset($value) && $function ? call_user_func($function, $value) : $value;

    // Set the variable to the value if it is set
    $variable = $value ? $value : $variable;
}

/*
********************************************************************************
*/

function setMethodArray(
    &$interface,         // Used as a blueprint
    $methodArray,        // The array to get the values from
    $default='previous', // Default value for missing indices
    $function=FALSE      // Apply a function to the value before returning
) {
    $retunrArray = array();
    $originalDefault = $default;

    // Similar to getDefault but compares an array to a method array (GET/POST)
    foreach ($interface as $name => $value) {
        // Is it an associative array?
        if (isset($methodArray[$name])) {
            // To use the original array as default values set default to 'previous'
            $default = $originalDefault == 'previous' ? $value : $default;

            $returnValue = isset($methodArray[$name]) ? $methodArray[$name] : $default;

            // If a function has been passed apply it to the value
            $interface[$name] = $function ? call_user_func($function, $returnValue) : $returnValue;
        } elseif (isset($methodArray[$value])) {

            $returnValue = isset($methodArray[$value]) ? $methodArray[$value] : $default;

            // If a function has been passed apply it to the value
            $interface[$value] = $function ? call_user_func($function, $returnValue) : $returnValue;
            unset($interface[$name]);
        }
    }
}

/*
********************************************************************************
*/

function checkVar($variable) {
    // call_user_func doesn't like 'isset'
    return $variable === FALSE || $variable === NULL  ? FALSE : TRUE;
}

/*
********************************************************************************
*/

function camelToTitle($string)
{
    return ucfirst(
        preg_replace(
            '/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]|[0-9]{1,}/',
            ' $0',
            $string
        )
    );
}

/*
********************************************************************************
*/

function getDefaultArray(
    $defaultArray,
    &$valuesArray,
    $function=FALSE
) {
    $retunrArray = array();

    // Similar to getDefault but compares an array to a method array (GET/POST)
    foreach ($defaultArray as $index => $value) {
        // Set to valueArray if index is set and not empty, otherwise use default
        $returnArray[$index] = isset($valuesArray[$index]) &&
        $valuesArray[$index] ? $valuesArray[$index] : $value;
    }
    return $returnArray;
}

/*
********************************************************************************
*/

function getFilteredDefaultArray(
    $defaultArray,
    &$valuesArray,
    $function=FALSE
) {
    $array = getDefaultArray($defaultArray, $valuesArray, $function);
    return $filteredArray = array_filter($array);
}

/*
********************************************************************************
*/

function getDefault(&$value, $default=FALSE, $function=FALSE)
{
    $returnValue = isset($value) ? $value : $default;
    return $function ? call_user_func($function, $returnValue) : $returnValue;
}

/*
********************************************************************************
*/

// Change date format to other format
function changeDateFormat($date, $preferedFormat=FALSE, $checkLength=FALSE)
{
    $newDate = $date;
    $dateArray = array();

    // If not ten char long unexceptable date format
    if ($checkLength && strlen($date) != 10) {
        return FALSE;
    }

    $currentFormat = strpos($date, '/') ? '/' : NULL;
    $currentFormat = strpos($date, '-') ? '-' : $currentFormat;
    if (! $currentFormat) {
        return FALSE;
    }

    // If there are slashes in the string and slashes are not prefered
    // 01/02/2003 -> 2003-01-02
    if ($currentFormat == '/' && $preferedFormat != '/') {
        // Replace slashes with hyphens and move year to front
        $dateArray = explode('/', $date);
        $newDate = $dateArray[2].'-'.$dateArray[0].'-'.$dateArray[1];
    }

    // 2003-01-02 -> 01/02/2003
    if ($currentFormat == '-' && $preferedFormat != '-') {
        $dateArray = explode('-', $date);
        $newDate = $dateArray[1].'/'.$dateArray[2].'/'.$dateArray[0];

    }

    return $newDate;
}

/*
********************************************************************************
*/

function limitString($string, $maxLength)
{
    $stringLength = strlen($string);
    if ($stringLength > $maxLength) {
        while (! ctype_alpha(
                    substr($string, - 1)
               ) && strlen($string) > 1
        ) {
            $shorterLength = strlen($string) - 1;
            $string = substr($string, 0, $shorterLength);
        }
        return $string;
    }

    return $string;
}

/*
********************************************************************************
*/

function numbersOnly($string)
{
    $pattern = '/[^0-9]*/';
    return preg_replace($pattern, NULL, $string);
}

/*
********************************************************************************
*/

function validSocNum($string)
{
    $formatted = numbersOnly($string);
    $stringLength = strlen($formatted);

    if ($stringLength == 9) {
        return $formatted;
    } else {
        return FALSE;
    }
}

/*
********************************************************************************
*/

function getLastFour($string)
{
    $formatted = numbersOnly($string);
    $stringLength = strlen($formatted);
    return substr($formatted, $stringLength-4, 4);
}

/*
********************************************************************************
* FILE SYSTEM                                                                  *
********************************************************************************
*/

function isDev()
{
    $environment = environment::get();
    return $environment != 'production' ? TRUE : FALSE;
}

/*
********************************************************************************
*/

function cronLogging($mvc)
{
    $runningCron = config::get('request', 'cron');

    if (! $runningCron) {
        return;
    }

    // All cron requests must have seesion tokens in the post variable
    $userSessionToken = access::getUserInfoValue('sessionToken');
    // Removing line to set session token to post value. Otherwise debug cron
    // mode is broken
    $postSessionToken = getDefault($mvc->post['token']);
    $postTokenCorrect = $userSessionToken == $postSessionToken;

    config::getSetting('debug', 'debugCrons') || $postTokenCorrect
        or die('Crons can only be called from CLI');
    $cronLog = models\directories::getLogDateFile('logs', 'crons');

    ini_set('error_log', $cronLog);
}

/*
********************************************************************************
*/

function checkMappedDrive($driveLetter)
{
    return is_dir($driveLetter.':') ? TRUE : FALSE;
}

/*
********************************************************************************
*/

function unmapDrive($driveLetter)
{
    if (! $driveLetter) {
        return FALSE;
    }
    $error = system('net use '.$driveLetter.': /DELETE /Y 2> NUL 2>$1');

    // Return true if theres no error
    return $error ? $error : TRUE;
}

/*
********************************************************************************
*/

function getMappedDrive($database, $driveLetter, $path, $username = FALSE, $password = FALSE)
{
    // If a password wasn't passed, check the db
    if (! $password) {
        $sessionToken = getDefault($_SESSION['token']);
        $password = access::getUserPassword($database);
    }

    if (! $username) {
        $username = access::getUserInfoValue('username');
    }

    // Must have all params
    if (! $driveLetter || ! $path || ! $username || ! $password) {
        return FALSE;
    }

    // Do nothing if drive is already mapped
    if (checkMappedDrive($driveLetter)) {
        return TRUE;
    }

    $command = 'net use '.$driveLetter.': "'.$path.'" '.
               '/user:'.$username.' '.$password.' /persistent:no>nul 2>&1';

    $output = system($command);

    // If the connection fails count it as a failed login
    if (! $isDir = is_dir($driveLetter.':')) {
        access::logFailedAttempt([
            'db' => $database,
            'username' => $username,
        ]);
    }

    return $isDir ? TRUE : FALSE;
}

/*
********************************************************************************
* ACCESS CONTROL                                                               *
********************************************************************************
*/

function getIDFromSession($database, $prefix)
{
    $prefixVars = getPrefixVars($database, $prefix);

    $sessionID = $prefixVars['sessionID'];
    $sessionToken = $prefixVars['sessionToken'];

    // Get the appID using the app's session token
    $sql = 'SELECT '.$sessionID.'
            FROM   ?
            WHERE  sessionToken = ?';

    $params = [
        $prefixVars['sessionsTable'],
        $sessionToken
    ];

    $session = $database->queryResult($sql, $params);

    if (! $session[$sessionID]) {
        redirect(HOME_PAGE);
    }
    return $session[$sessionID];
}

/*
********************************************************************************
*/

function getPrefixVars($database, $prefix)
{
    $escapedPrefix = $prefix ? $database->quote($prefix, 'escape') : FALSE;
    $sessionID = $camelID = $escapedPrefix.'ID';
    $underScorePrefix = $prefix ? $escapedPrefix.'_' : FALSE;
    $tokenName = $underScorePrefix.'token';

    $sessionToken = getDefault($_SESSION[$tokenName]);
    $uploadsTable = $underScorePrefix.'uploads';
    $sessionsTable = $underScorePrefix.'sessions';

    // Get default session if the prefix session isn't set
    if (! $sessionToken) {
        $sessionToken = getDefault($_SESSION['token']);
        $userDB = $database->getDBName('users');
        $sessionsTable = $userDB.'.sessions';
        $sessionID = 'id';
    }

    return array(
        'camelID' => $camelID,
        'sessionID' => $sessionID,
        'tokenName' => $tokenName,
        'sessionToken' => $sessionToken,
        'uploadsTable' => $uploadsTable,
        'sessionsTable' => $sessionsTable,
        'escapedPrefix' => $escapedPrefix,
        'underScorePrefix' => $underScorePrefix,
    );
}

/*
********************************************************************************
*/

function loginContinue($database)
{
    $runningCron = config::get('request', 'cron');

    if (! $runningCron || ! $database->post) {
        return FALSE;
    }

    $username = getDefault($database->post['username']);
    $password = getDefault($database->post['password']);

    if ($username && $password) {
        login([
            'continue' => TRUE,
            'database' => $database,
        ]);
    }
}

/*
********************************************************************************
*/

function validADUsername($username)
{
    // Username can not be empty
    if (! $username) {
        return FALSE;
    }

    // Only non alpha allowed is the hyphen
    $noHyphen = str_replace('-', NULL, $username);
    if (! ctype_alnum($noHyphen)) {
        return FALSE;
    }

    return TRUE;
}

/*
********************************************************************************
*/

function validADPassword($password)
{
    // Password can not be empty
    if (! $password) {
        return FALSE;
    }

    return TRUE;
}

/*
********************************************************************************
*/

function storePassword($database, $username, $password)
{
    //$ecryptedPassword = openSSLencrypt($password, FALSE);

    $sql = 'UPDATE info
            SET    password = ?
            WHERE  username = ?';

    $params = [
        shaHash($password, $username),
        $username
    ];

    return $database->runQuery($sql, $params);
}

/*
********************************************************************************
*/

function shaHash($string, $salt=NULL)
{
    return hash('sha384', $salt.$string);
}

/*
********************************************************************************
*/

function login($params)
{
    $database = $params['database'];
    $continue = isset($params['continue']);
    $ajaxRequest = isset($params['ajaxRequest']);
    $sessionRequest = isset($params['sessionRequest']);

    $stored = FALSE;

    $sessionToken = NULL;

    $post = getDefault($database->post, []);

    $errorMessage = $database->getVar('login', 'getDef') ?
        'User Not Found<br><br>' : NULL;

    // URL query the user entered before having to login
    $appName = config::get('site', 'appName');
    $rightApp = $appName == getDefault($_SESSION['appName'])
        ? TRUE : FALSE;

    $queryString = isset($_SESSION['queryString']) && $rightApp ?
        $_SESSION['queryString'] : config::get('site', 'homePage');

    // If the user is logged in and this isn't password check, send the user
    // to the initial requested page
    $result = access::checkTimeOut($database);

    $storedUsername = access::getUserInfoValue('username');
    if (! $ajaxRequest &&  ! $continue
    &&  ! $result['expired'] && $storedUsername
    ) {
        redirect($queryString);
    }

    $fullUsername = getDefault($post['username']);

    if (! $fullUsername) {
        return;
    }

    $username = basename($fullUsername);

    $orinialPass = getDefault($post['password']);

    $passwordHash = $orinialPass ? access::passwordHash($orinialPass) : NULL;

    // Ajax requests have to hash the password before sending
    $password = $ajaxRequest ? $orinialPass : md5($orinialPass);

    // If the user has too many failed attempts, disable ability to login
    $tooManyFails = access::getFailedAttempts([
        'db' => $database,
        'username' => $username,
        'checkTooMany' => TRUE,
    ]);

    if ($username && $password && ! $tooManyFails ) {

        // Check that creds are valid
        $usernameFlag = validADUsername($username) ? FALSE : $errorMessage;
        $loginFlag = validADPassword($password) ? $usernameFlag : $errorMessage;

        if (! $loginFlag) {
            // Custom login if it exists
            $succesfulLogin =
                access::passwordCheck($database, $username, $password);

            if ($succesfulLogin) {

                $sessionToken = access::createSession([
                    'database' => $database,
                    'username' => $username,
                    'passwordHash' => $passwordHash,
                ]);

                if ($sessionRequest && $sessionToken) {
                    return $sessionToken;
                }

                if ($ajaxRequest) {
                    return ['success' => TRUE];
                } else {
                    // Don't forward if this is a login and continue request
                    if ($continue) {
                        return access::setAPIRequest();
                    }
                    redirect($queryString);
                }
            }

            // Use login info to check for user in employees/user access
            $userInfo = TRUE;

            // Use login info to check for user in active directory
            $adEntry = FALSE ? adSearch([
                'username' => $username,
                'password' => $orinialPass,
                'searchTerm' => $username,
            ]) : NULL;

            if ($userInfo && $adEntry) {
                // Store password if required
                $stored = $ajaxRequest ? FALSE
                    : storePassword($database, $username, $password);

                // If not checking pass, send the user to the initial requested
                // and create their DB session
                if (! $ajaxRequest) {
                    $sessionToken = access::createSession([
                        'database' => $database,
                        'username' => $username,
                    ]);

                    redirect($queryString);
                }
            }
        }

        // If this is a pass store and the pass was not found, log fail
        // OR this wasnt a store pass, log failed
        if (! $ajaxRequest || ! $stored) {
            // Record the failed log attempt
            access::logFailedAttempt([
                'db' => $database,
                'username' => $username,
            ]);
        }

        if ($ajaxRequest) {
            return ['success' => FALSE];
        } else {
            // If login was not successful and no redirect, log error
            loginPageRedirect('unsuccessful');
        }
    }

    $message = 'Your account has been temporarily locked due to failed '
        . 'atttempts. Please try again later.';

    return [
        'message' => $message,
        'tooManyFails' => $tooManyFails,
    ];
}

/*
********************************************************************************
*/

function checkLoginPage()
{
    $requestClass = config::get('site', 'requestClass');

    // Test for logout
    // When a user logs out and logs back in, they go to the default page
    return $requestClass == 'login' ? TRUE : FALSE;
}

/*
********************************************************************************
*/

function loginPageRedirect($unsuccessful=FALSE)
{

    $loginPage = checkLoginPage();

    if ($loginPage) {
        return;
    }

    $addParams = $unsuccessful ? ['login' => 'unsuccessful'] : [];

    setQueryStringSession();

    // Set the default page mobile menu if this is on a scanner gun
    $link = getDefault($_SESSION['onScanner']) ?
        makeLink('main', 'mobileMenu', $addParams) :
        makeLink('login', 'user', $addParams);

    return redirect($link);
}

/*
********************************************************************************
*/

function setQueryStringSession()
{
    // Don't store the query string for a script
    $appURL = config::getAppURL();
    $isScript = strpos($appURL, '/scripts') !== FALSE ? TRUE : FALSE;
    $isAjaxRequest = config::get('site', 'jsonRequest');

    $requestURI = config::get('site', 'requestURI');

    // Don't store the query string if the request page is logout
    $logoutRequest = config::get('site', 'requestClass') == 'logout';

    // Store the query string and app name of the requested page
    $_SESSION['queryString'] = ! $logoutRequest && $requestURI && ! $isScript
            && ! $isAjaxRequest ? $requestURI : NULL;

    $appName = config::get('site', 'appName');
    $_SESSION['appName'] = $appName && ! $isScript ? $appName : NULL;

    return $isScript;
}

/*
********************************************************************************
*/

function getAuthUser($database)
{

    // Check the db for a user session
    $userInfo = access::getUserFromSession($database);

    // Use intranet 'auth user' var if its set and not empty
    $username = access::getUserInfoValue('username');
    if (! $userInfo && $username) {

        $userInfo = adSearch([
            'username' => AD_GLOBAL_USER,
            'password' => AD_GLOBAL_PASS,
            'searchTerm' => $username,
        ]);
    }

    // This may be redundant because getUserFromSession makes this call
    empty($userInfo) ? loginPageRedirect() : NULL;

    return $userInfo;
}

/*
********************************************************************************
*/

function adSearch($params)
{
    $sort = getDefault($params['sort']);
    $login = $params['username'];
    $index = getDefault($params['index']);
    $password = $params['password'];
    $searchTerm = $params['searchTerm'];
    $searchTarget = getDefault($params['searchTarget']);

    if (! function_exists('ldap_connect')) {
        return;
    }

    // Default search target is employee username
    $searchTarget = $searchTarget ? $searchTarget : 'samaccountname';

    // If index is 'useTarget', use the target as an index
    $index = $index == 'useTarget' ? $searchTarget : $index;

    // We connect via the IP address, you may be able to use a server name here
    $adConnection = ldap_connect(AD_HOST) or die(AD_ERROR_MESSAGE);

    // Bind to the server using credentials supplied
    if ($bind = @ldap_bind($adConnection, AD_DOMAIN.'\\'.$login, $password)) {

        // Find the entries that match the criteria
        $result = ldap_search(
            $adConnection,
            AD_DISTINGUISHED_NAME,
            // Search by ldap attribute
            $filter = strpos($searchTerm, '=') !== FALSE ?
                // If the search term has an equal sign, its a filter
                $searchTerm : '('.$searchTarget.'='.$searchTerm.')',
            // Get this array of attributes for each user
            $attributes = array(
                'sn',
                'mail',
                'givenName',
                'displayname',
                'samaccountname',
            )
        );

        if ($sort) {
            ldap_sort($adConnection, $result, $sort);
        }

        $entries = ldap_get_entries($adConnection, $result);
    } else {
        // If unable to connect to server with creds
        return FALSE;
    }

    if (! $index) {
        return $entries;
    } else {
        $entriesByName = array();
        foreach ($entries as $entry) {
            $fullName = $entry[$index][0];
            $entriesByName[$fullName] = $entry;
        }
        return $entriesByName;
    }
}

/*
********************************************************************************
*/

function accessCheck($database, $requiredLevel)
{
    // Grant access if there is no level required
    if (! $requiredLevel) {
        return TRUE;
    }

    // Check for posted creds
    $postedCreds = isset($database->post['username']) &&
        isset($database->post['password']);

    $token = access::getSessionToken($database);

    // Send them to login if they don't have a session
    $token || $postedCreds or loginPageRedirect();

    $loggedUsername = access::getLoggedUsername($database);

    // If there is a required access level and no username is defined,
    // force a login with Share Point Credentials
    if ($requiredLevel && ! $loggedUsername) {
        return FALSE;
    }

    $level = access::getUserInfoValue('level');

    if ($level) {
        $employeeLevel = getDefault($level, 0);
        return $employeeLevel && ($employeeLevel <= $requiredLevel);
    }

    $sql = 'SELECT  id
            FROM    users_access
            WHERE   levelID >= '.intval($requiredLevel).'
            AND     levelID != 0';

    return $access = $database->queryResults($sql) ? TRUE : FALSE;

}

/*
********************************************************************************
*/

function getUserByUsername($database, $passUsername=FALSE)
{
    $username = $passUsername ? $passUsername :
        access::getUserInfoValue('username');

    if ($username) {
        return FALSE;
    }

    $user = access::getUserInfo([
        'db' => $database,
        'search' => 'session',
    ]);

    if ($user) {
        return $user;
    }

    // Get employee with username
    $employee = FALSE;
    $employee ? getAD($username) : NULL;

    // Get user info with employeeID
    if (! $employee) {
        return FALSE;
    }

    // Return both if user was found
    return $user ? array_merge($user, $employee) : FALSE;

}

/*
********************************************************************************
*/

function return404()
{
    header('HTTP/1.0 404 Not Found');
    ?>
    <!DOCTYPE html>
    <html><head>
    <title>404 Not Found</title>
    </head><body>
    <h1>Not Found</h1>
    <p>The requested page '<?php echo config::get('site', 'requestPage');
        ?>' was not found on this server.</p>
    </body></html>
    <?php
}

/*
********************************************************************************
*/

function adInfo($adResults)
{
    unset($adResults['count']);

    if (! $adResults) {
        return FALSE;
    }

    $adResult = reset($adResults);
    return $info = [
        'fullName' => $adResult['displayname'][0],
        'username' => $adResult['samaccountname'][0],
    ];
}

/*
********************************************************************************
*/

function createToken($length=FALSE)
{
    $appName = config::get('site', 'appName');
    $uniqueID = uniqid($appName);
    $token = md5($uniqueID);

    return $length ? substr($token, 0, $length) : $token;
}

/*
********************************************************************************
* ENCRYPTION                                                                   *
********************************************************************************
*/

function openSSLencrypt($target, $encode=TRUE)
{
    // Get string from cert
    $certString = file_get_contents(CERTIFICATE);

    // Get public key from string
    $publicKey = openssl_pkey_get_public($certString);

    // Encrypt target with public key
    openssl_public_encrypt($target, $encrypted, $publicKey);

    // Encode the result so its mysql friendly
    $encrypted = $encode ? base64_encode($encrypted) : $encrypted;

    return $encrypted;
}

/*
********************************************************************************
*/

function openSSLdecrypt($target, $decode=TRUE)
{
    // Decode the target if it came from the DB
    $target = $decode ? base64_decode($target) : $target;

    // Get private key string
    $privateKeyString = file_get_contents(PRIVATE_KEY);

    // Get private key resource
    $getPrivate = openssl_pkey_get_private($privateKeyString);

    // Decrypt target with private key resource
    openssl_private_decrypt($target, $unencrypted, $getPrivate);

    return $unencrypted;
}

/*
********************************************************************************
* ACTIVE DIRECTORY                                                             *
********************************************************************************
*/

function getAD($searchTerm='*', $index=FALSE, $searchTarget=FALSE, $sort=FALSE)
{
    // Use the Global Read Credentials to get user accounts
    return adSearch([
        'sort' => $sort,
        'index' => $index,
        'username' => AD_GLOBAL_USER,
        'password' => AD_GLOBAL_PASS,
        'searchTerm' => $searchTerm,
        'searchTarget' => $searchTarget,
    ]);
}

/*
********************************************************************************
*/

function unsetSession()
{
    $_SESSION = [];
}

/*
********************************************************************************
*/

function checkLogout($requestObject, $forced=FALSE)
{
    $loginPage = checkLoginPage();

    $logoutGetVar = isset($requestObject->get['logout']);

    // Don't logout if it is not a forced logout and there is no logout get var
    // or not a login page request
    if (! $forced && ! $logoutGetVar || $loginPage) {
        return;
    }

    unsetSession();

    loginPageRedirect();
}

/*
********************************************************************************
* AUTOMATED TESTING                                                            *
********************************************************************************
*/

function recordPayload($title, $counter, $payload)
{
    $dontWant = array(
        '_SERVER',
        '_COOKIE',
    );

    foreach ($dontWant as $takeOut) {
        unset($payload[$takeOut]);
    }

    $expandArrays = array(
        '_GET',
        '_POST',
        '_FILES',
        '_SESSION',
    );

    foreach ($expandArrays as $array) {
        $payloadArray = $payload[$array];
        foreach ($payloadArray as $title => $value) {
            $payload[$array.'_'.$title] = $value;
        }
        unset($payload[$array]);
    }

    ksort($payload);

    foreach ($payload as $title => $value) {
        varDump($title);
        varDump(getType($value));
        varDump('empty: '.empty($value));
    }


}

/*
********************************************************************************
*/

function userDump($username, $subject)
{
    $storedUsername = access::getUserInfoValue('username');

    if ($storedUsername == $username) {
        varDump($subject);
    }
}

/*
********************************************************************************
*/

function getEmployees($database=FALSE, $whereClause=1, $oneResult=FALSE)
{
    $sql = 'SELECT id,
                   firstName,
                   lastName,
                   username,
                   email,
                   CONCAT(firstName," ",lastName) AS fullName,
                   CONCAT(lastName,", ",firstName) AS lfFullName
            FROM   employees
            WHERE  '.$whereClause;

    $employees = $database->queryResults($sql);

    return getFirstElement($employees, $oneResult);
}

/*
********************************************************************************
*/

function formatStringForArray($arrLocation)
{
    if ($arrLocation) {
        for ( $i = 0; $i < count($arrLocation); $i++) {
            $arrLocation[$i] = '"'. $arrLocation[$i]. '"';
        }
    }
    return $arrLocation;
}

/*
********************************************************************************
*/

