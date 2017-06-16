<?php

use models\config;

class access
{
    // Store user info by username then IP then passRecovery
    static $user = [];

    static $username = NULL;

    static $sitePages = [];

    static $ipAddress = NULL;

    static $apiRequest = FALSE;

    static $sessionUpdated = FALSE;
    
    static $loggedUserRequest = FALSE;

    /*
    ****************************************************************************
    */

    static function getSitePages()
    {
        self::$sitePages = sitePages::get();

        return self::$sitePages;
    }

    /*
    ****************************************************************************
    */

    static function getIPAddress()
    {
        if (! self::$ipAddress) {
            self::$ipAddress = $_SERVER['REMOTE_ADDR'];
        }

        return self::$ipAddress;

    }

    /*
    ****************************************************************************
    */

    static function setAPIRequest()
    {
        self::$apiRequest = TRUE;
    }

    /*
    ****************************************************************************
    */

    static function unsetAPISession()
    {
        if (self::$apiRequest) {
            return unsetSession();
        }
    }

    /*
    ****************************************************************************
    */

    static function isSessionCheck($app)
    {
        $requestPage = config::get('site', 'requestPage');
        $dialogRequest = $requestPage == 'dialogLoginJson';
        $sessionCheck = $app->getVar('sessionCheck', 'detDef');
        return $dialogRequest && $sessionCheck;
    }

    /*
    ****************************************************************************
    */

    static function updateSession($params)
    {
        $app = $params['db'];

        // Don't update the session if this is a login check
        $isSessionCheck = self::isSessionCheck($app);
        if (self::$sessionUpdated || $isSessionCheck) {
            return;
        }

        $session = $params['session'];
        $username = $params['username'];
        $passwordHash = getDefault($params['passwordHash']);

        $storedSession = self::getUserInfoValue('sessionToken', $username);

        $userDB = $app->getDBName('users');

        $sessionQuery = $storedSession ?

               'UPDATE '.$userDB.'.sessions
                SET    lastUpdate = NOW(),
                       sessionToken = ?,
                       ip = ?
                WHERE  username = ?' :

               'INSERT INTO '.$userDB.'.sessions (
                    sessionToken,
                    ip,
                    username
                ) VALUES (?,?,?)';

        $newPasswordQuery = 'UPDATE '.$userDB.'.info
                             SET    newPassword = ?
                             WHERE  username = ?;';

        $sql = 'UPDATE '.$userDB.'.log_attempts
                SET    quantity = 0
                WHERE  ip = ?
                AND    username = ?;';

        $app->beginTransaction();

        $app->runQuery($sql, [self::$ipAddress, $username]);
        if ($passwordHash) {
            $app->runQuery($newPasswordQuery, [$passwordHash, $username]);
        }

        $app->runQuery($sessionQuery, [$session, self::$ipAddress, $username]);

        $app->commit();

        self::$sessionUpdated = TRUE;
    }

    /*
    ****************************************************************************
    */

    static function isClient($db)
    {
        self::getUserInfo([
            'db' => $db,
            'search' => 'session',
        ]);

        isset(self::$user['employer'])
            or die('Checking for Client Status but it is not set yet.');

        return self::$user['employer'] == 'Seldat' ? FALSE : TRUE;
    }

    /*
    ****************************************************************************
    */

    static function getSessionToken($mvc=NULL)
    {
        $sessionToken = getDefault($_SESSION['token']);

        if ($sessionToken || ! $mvc) {
            return $sessionToken;
        }

        $token = isset($mvc->post['token']) ? $mvc->post['token'] : NULL;

        return $token;
    }

    /*
    ****************************************************************************
    */

    static function checkClientPage($db)
    {
        $requestPage = config::get('site', 'requestPage');

        $requiredAccess = self::$sitePages['access'][$requestPage];

        $noLevel = config::getSetting('accessLevels', 'none');
        if ($requiredAccess == $noLevel) {
            return;
        }

        // Clients can only see client pages
        $isClient = self::isClient($db);

        ! $isClient || isset(self::$sitePages['clientAccess'][$requestPage])
            or die();

        return $isClient;
    }

    /*
    ****************************************************************************
    */

    static function getLoggedUsername($db)
    {

        self::getUserInfo([
            'db' => $db,
            'search' => 'session',
        ]);

        $username = self::getUserInfoValue('username');

        if ($username) {
            return $username;
        }

        $postUsername = getDefault($db->post['username']);

        return self::getUserInfo([
            'db' => $db,
            'term' => $postUsername,
            'search' => 'username',
        ]);
    }

    /*
    ****************************************************************************
    */

    static function getUserInfo($params)
    {
        $db = $params['db'];

        // Get the session token if necessary
        $term = ! isset($params['term']) && $params['search'] == 'session' ?
            self::getSessionToken($db) : $params['term'];

        $ipAddress = self::getIPAddress();

        $passRecoverySearch = getDefault($params['passRecovery'], 0);

        if ($term && ! isset(self::$user[$term])) {
            $search = $params['search'];

            $userDB = $db->getDBName('users');

            switch ($search) {
                case 'session':
                    $clause = 'sessionToken = ?';
                    break;
                case 'username':
                    $clause = 'u.username = ?';
                    break;
                default:
                    die;
            }

            $users = new tables\users($db);

            $sql = 'SELECT    la.id,
                              '.$users->primaryKey.',
                              '.$users->getSelectFields().',
                              a.levelID AS level,
                              u.password,
                              IF (se.ip IS NULL, ?, se.ip) AS ip,
                              IF (la.ip IS NULL, ?, la.ip) AS failedIP,
                              quantity,
                              la.lastUpdate,
                              -- This value can be null due to first time
                              -- sessions. At this point assume non a password
                              -- recovery
                              IF (
                                  la.passRecovery IS NULL, 0, la.passRecovery
                              ) AS passRecovery,
                              se.lastUpdate AS sessionLastUpdate,
                              es.displayName AS employer,
                              sessionToken
                    FROM      '.$users->table.'
                    LEFT JOIN '.$userDB.'.log_attempts la ON la.username = u.username
                    LEFT JOIN '.$userDB.'.sessions se ON se.username = u.username
                    JOIN      statuses es ON es.id = u.employer
                    WHERE     '.$clause;

            $userInfo = $db->queryResults($sql, [$ipAddress, $ipAddress, $term]);

            foreach ($userInfo as $row) {
                $ip = $row['ip'];
                $username = $row['username'];
                $passRecovery = $row['passRecovery'];

                self::$user['level'] = $row['level'];
                self::$user['username'] = $row['username'];
                self::$user['password'] = $row['password'];


                self::$user['sessionToken'] = $row['sessionToken'];

                self::$user['employer'] = $row['employer'];

                $failedIP = $row['failedIP'];

                // Store user info by username/token, IP and passRec for later
                self::$user['failedIPs'][$failedIP] =
                self::$user[$term][$ip][$passRecovery] =
                    self::$user[$username][$ip][$passRecovery] = $row;
            }
        }

        return isset(self::$user[$term][$ipAddress][$passRecoverySearch]) ?
            self::$user[$term][$ipAddress][$passRecoverySearch] : [];
    }

    /*
    ****************************************************************************
    */

    static function getUserID()
    {
        return self::getUserInfoValue('id');
    }

    /*
    ****************************************************************************
    */

    static function getUserInfoValue($name, $passedUsername=FALSE)
    {
        $userValue = getDefault(self::$user[$name]);

        if ($userValue) {
            return $userValue;
        }

        // Don't get the password recovery value
        $passRecovery = 0;

        $ipAddress = self::$ipAddress;

        $username = $passedUsername ? $passedUsername : self::$username;

        $isset = isset(self::$user[$username][$ipAddress][$passRecovery][$name]);

        // Custom username calls may not be set
        return ! $isset ? NULL :
            self::$user[$username][$ipAddress][$passRecovery][$name];
    }

    /*
    ****************************************************************************
    */

    static function passwordCheck($app, $username, $searchPassword)
    {
        self::getUserInfo([
            'db' => $app,
            'term' => $username,
            'search' => 'username',
        ]);

        $password = self::getUserInfoValue('password');

        return $password && $password == $searchPassword;
    }

    /*
    ****************************************************************************
    */

    static function logFailedAttempt($params)
    {
        $db = $params['db'];
        $username = $params['username'];
        $passRecovery = getDefault($params['passRecovery'], 0);

        // Store sql query for insert/update
        $failedAttempts = self::getFailedAttempts($params);

        $failQuantity = getDefault($failedAttempts['quantity']);

        // If cap quantity at 9 because db only is limited to 1 digit value
        $newQuantity = $failQuantity >= 9 ? $failQuantity : $failQuantity + 1;

        $userDB = $db->getDBName('users');

        // No attempts, create first entry
        $sql = $failedAttempts['quantity'] === NULL ?

               'INSERT IGNORE INTO '.$userDB.'.log_attempts (
                    quantity,
                    ip,
                    username,
                    passRecovery
                ) VALUES (?, ?, ?, ?)' :

               'UPDATE '.$userDB.'.log_attempts
                SET    quantity = ?,
                       lastUpdate = NOW()
                WHERE  ip = ?
                AND    username = ?
                AND    passRecovery = ?';

        $db->runQuery($sql, [$newQuantity, self::$ipAddress, $username,
            $passRecovery
        ]);

        return $newQuantity;
    }

    /*
    ****************************************************************************
    */

    static function getFailedAttempts($params)
    {

        // Select where username and last attmpt was less than 10 min ago
        self::getUserInfo([
            'db' => $params['db'],
            'term' => $params['username'],
            'search' => 'username',
            'passRecovery' => getDefault($params['passRecovery']),
        ]);

        if (! isset(self::$user['failedIPs'][self::$ipAddress])) {
            return FALSE;
        }

        $failedAttempts =
            getDefault(self::$user['failedIPs'][self::$ipAddress]);

        // If the last update was over ten minuts ago return zero fails
        $lastUpdate = getDefault($failedAttempts['lastUpdate']);

        $lockDuration = config::getSetting('durations', 'lock');
        $overTen = self::timedOut($lastUpdate, $lockDuration);

        if ($overTen) {
            $failedAttempts['quantity'] = 0;
        }

        // Check for too many fails
        $tooManyFails = $failedAttempts['quantity'] > 3;

        return isset($params['checkTooMany']) ? $tooManyFails : $failedAttempts;
    }

    /*
    ****************************************************************************
    */

    static function getSessionSeconds($timestamp)
    {
        return [
            'currentTime' => config::getDateTime('currentTime'),
            // Don't false green on missing timestamp or CURRENT_TIME var
            'lastUpdateTime' => strtotime($timestamp),
        ];
    }

    /*
    ****************************************************************************
    */

    static function timedOut($timestamp, $maxTime)
    {
        $results = self::getSessionSeconds($timestamp);

        if (! $results['lastUpdateTime'] || ! $results['currentTime']) {
            return TRUE;
        }

        $seconds = $results['currentTime'] - $results['lastUpdateTime'];

        return $seconds > $maxTime;
    }

    /*
    ****************************************************************************
    */

    static function createSession($params)
    {
        $database = $params['database'];
        $username = $params['username'];
        $passwordHash = getDefault($params['passwordHash']);
        $prefix= getDefault($params['prefix']) ? getDefault($params['prefix']) : FALSE;
        $escapedPrefix = $prefix ? $database->quote($prefix, 'escape') : FALSE;
        $underScorePrefix = $prefix ? $escapedPrefix.'_' : FALSE;

        // Create applicant session using app name as salt
        $_SESSION[$underScorePrefix.'token'] = createToken();

        // Cant make sessions for employeeID = 0
        // must have IP address to be secure user
        if (! self::$ipAddress) {
            return FALSE;
        }

        // Set applicant session in DB
        self::updateSession([
            'db' => $database,
            'username' => $username,
            'passwordHash' => $passwordHash,
            'session' => $_SESSION[$underScorePrefix.'token'],
        ]);

        return $_SESSION[$underScorePrefix.'token'];
    }

    /*
    ****************************************************************************
    */

    static function getDuration()
    {
        $sessionType = isset($_SESSION['onScanner']) ? 'gunSession' : 'session';

        return config::getSetting('durations', $sessionType);
    }

    /*
    ****************************************************************************
    */

    static function checkTimeOut($database, $getDiff=FALSE)
    {
        $sessionToken = self::getSessionToken($database);

        if (! $sessionToken) {
            return FALSE;
        }

        $userInfo = self::getUserInfo([
            'db' => $database,
            'term' => $sessionToken,
            'search' => 'session',
        ]);

        self::$username = getDefault($userInfo['username']);

        $sessionDuration = self::getDuration();

        $lastUpdate = getDefault($userInfo['sessionLastUpdate']);

        if ($getDiff) {
            $seconds = self::getSessionSeconds($lastUpdate);
            $seconds['duration'] = $sessionDuration;
            return $seconds;
        }

        $overHalfHour = self::timedOut($lastUpdate, $sessionDuration);

        return [
            'session' => $sessionToken,
            'expired' => $overHalfHour,
            'userInfo' => $userInfo,
            'username' => $userInfo['username'],
        ];
    }

    /*
    ****************************************************************************
    */

    static function getUserFromSession($database, $getAll=FALSE)
    {
        $results = self::checkTimeOut($database);

        if (! $results) {
            return FALSE;
        }

        
        // If the user is found and it hasn't been a half hour, update the time
        if (self::$username && ! $results['expired']) {

            self::updateSession([
                'db' => $database,
                'session' => $results['session'],
                'username' => $results['userInfo']['username'],
            ]);

            return $getAll ? $results['userInfo'] :
                $results['userInfo']['username'];
        }

        // User must have lost their session if they are being prompted to login
        // but are not making a login ajax request
        self::checkExpiredSession();

        $requiredLevel = self::getLevel();

        $requiredLevel ? loginPageRedirect() : NULL;
    }

    /*
    ****************************************************************************
    */

    static function getUserPassword($database, $undecrypted = FALSE)
    {
        if (! $sessionToken = getDefault($_SESSION['token'])) {
            return FALSE;
        }

        $userDB = $database->getDBName('users');

        $sql = 'SELECT    u.password
                FROM      '.$userDB.'.sessions s
                LEFT JOIN users_access u ON s.employeeID = u.employeeID
                WHERE     s.sessionToken = ?
                AND       ip = ?
                LIMIT     1';

        $params = [$sessionToken, self::$ipAddress];

        $result = $database->queryResult($sql, $params);

        return  $undecrypted ? $result['password'] :
            openSSLdecrypt($result['password'], FALSE);
    }

    /*
    ****************************************************************************
    */

    static function checkPage()
    {
        $sitePages = self::getSitePages();

        $requestPage = config::get('site', 'requestPage');

        if (isset($sitePages['access'][$requestPage])) {
            // Otherwise, an acceptable page
            return TRUE;
        } else {
            // If the page is not a known site page, stop
            echo return404();
            if (isDev()) {
                backTrace();
            }
            return die();
        }
    }

    /*
    ****************************************************************************
    */

    static function getLevel($passedLevel=FALSE)
    {
        $requiredLevel = $passedLevel ?
            config::getSetting('accessLevels', $passedLevel) : FALSE;

        if (! $requiredLevel) {
            $requestPage = config::get('site', 'requestPage');
            $requiredLevel = self::$sitePages['access'][$requestPage];
        }

        return $requiredLevel;
    }

    /*
    ****************************************************************************
    */

    static function checkExpiredSession()
    {
        $requestPage = config::get('site', 'requestPage');
        $jsonRequest = config::get('site', 'jsonRequest');

        if ($jsonRequest && $requestPage != 'dialogLoginJson') {

            $message = 'Your session has expired. Please log out and log back '
                . 'in to continue.';

            $errorMessage = json_encode(['error' => $message]);

            die($errorMessage);
        }
    }

    /*
    ****************************************************************************
    */

    static function required($params)
    {
        $app = $params['app'];
        $level = getDefault($params['requiredLevel']);
        $terminal = getDefault($params['terminal']);
      
        $requiredLevel = self::getLevel($level);
        $userIsLogged = self::getUserInfoValue('sessionToken');

        // Go to login page if level is require but user isn't logged in
        $requiredLevel && ! $userIsLogged ? loginPageRedirect() : NULL;
        
        $isClient = self::checkClientPage($app);    

        $isClient ? self::logUserRequests($app) : NULL;
        
        // If the user doesn't have required access level, send to login page
        $accessGranted = accessCheck($app, $requiredLevel);

        if (! $accessGranted && $terminal) {
            die('You are not authorized to view this page.');
        }

        if ($accessGranted) {
            return TRUE;
        }

        getAuthUser($app);
    }

    /*
    ****************************************************************************
    */

    static function passwordHash($data, $binarySize=4)
    {
        $iv = mcrypt_create_iv($binarySize, MCRYPT_RAND);
        $hashResults = unpack('H*', $iv);
        $salt = reset($hashResults);
        return $salt . hash('sha1', $salt . $data);
    }

    /*
    ****************************************************************************
    */

    static function isTester($app)
    {
        // Only "Developer" users with tester session can access run tests
        if (! getDefault($_SESSION['tester'])) {
            return FALSE;
        }

        return accessCheck($app, 'developer');
    }

    /*
    ****************************************************************************
    */

    static function logUserRequests($app)
    {
        // Don't log JSON Reuqest and only log once per request
        
        $jsonRequest = config::get('site', 'jsonRequest');

        if ($jsonRequest || self::$loggedUserRequest) {
            return;
        }

        $userClients = \users\groups::commonClientLookUp($app);
        
        $json = json_encode([
            'userID' => self::getUserID(),
            'userCustomerIDs' => array_keys($userClients),
            'requestURI' => config::get('site', 'requestURI'),
            'dt' => config::getDateTime('dateTime'),
        ], JSON_PRETTY_PRINT).',';
        
        $logger = new \logger\object([
            'logDir' => 'customerPRs',
            'filename' => 'requests',
        ]);
        
        $logger->log($json);
        
        self::$loggedUserRequest = TRUE;
    }
}
