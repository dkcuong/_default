<?php

class model extends base
{
    public $error = FALSE;

    public $success = FALSE;

    public $codeSent = FALSE;

    /*
    ****************************************************************************
    */

    function modelPasswordRecovery()
    {
        $username = $this->postVar('username', 'getDefault');
        $emailOrCode = $this->postVar('password', 'getDefault');

        if (! $username) {
            return;
        }

        //  Check if there are too many fails for an email lookup
        $tooMany = access::getFailedAttempts([
            'db' => $this,
            'username' => $username,
            'passRecovery' => TRUE,
            'checkTooMany' => TRUE
        ]);

        if ($tooMany) {
            return $tooMany;
        }

        $userDB = $this->getDBName('users');

        $sql = 'SELECT    u.id,
                          CONCAT(firstName, " ", lastName) AS fullName,
                          IF (c.code = ?, TRUE, FALSE) AS isCode
                FROM      ' . $userDB . '.info u
                LEFT JOIN ' . $userDB . '.reset_codes c ON c.userID = u.id
                WHERE     username = ?
                AND     ( email = ? OR code = ? AND c.active )';

        $result = $this->queryResult($sql, [
            $emailOrCode,
            $username,
            $emailOrCode,
            $emailOrCode
        ]);

        $userID = getDefault($result['id']);

        if (! $userID) {
            // Log the failure
            access::logFailedAttempt([
                'db' => $this,
                'username' => $username,
                'passRecovery' => TRUE
            ]);

            return $tooMany;
        }

        $fullName = $result['fullName'];
        $isCode  = $result['isCode'];

        if ($isCode) {
            $sql = 'UPDATE ' . $userDB . '.reset_codes
                    SET    active = 0
                    WHERE  userID = ?';

            $this->runQuery($sql, [$userID]);

            access::createSession([
                'database' => $this,
                'username' => $username,
            ]);

            $link = makeLink('main', 'menu', [
                'class' => 'login',
                'method' => 'changePassword',
            ]);

            return redirect($link);
        }

        // Creat a recovery code and email it to the user
        $code = $_SESSION['loginCode'] = createToken(12);

        $sql = 'INSERT INTO ' . $userDB . '.reset_codes (
                    userID,
                    code
                ) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE
                    code = VALUES(code),
                    active = 1';

        $this->runQuery($sql, [$userID, $code]);

        $message = 'Hello ' . $fullName . ',<br>Here is the code you will '
                 . 'need to reset your password: ' . $code . '<br>Make sure '
                 . 'to reset your password once you have successfully '
                 . 'logged in.<br><br>If you have not try to reset your '
                 . 'password please disregard this message.';

        PHPMailer\send::mail([
            'recipient' => $emailOrCode,
            'subject' => 'Seldat WMS Password Reset',
            'body' => $message
        ]);

        $link = makeLink('login', 'user', [
            'password' => 'recovery',
            'code' => 'sent'
        ]);

        redirect($link);
    }

    /*
    ****************************************************************************
    */

}

/*
********************************************************************************
* LOGIN RELATED FUNCTIONS                                                      *
********************************************************************************
*/

function hashString($string) {
    return md5(APPLICATION_NAME.$string);
}

/*
****************************************************************************
*/

function getAppUser($username, $password, $passwordOptional=FALSE)
{
    $hashPass = hashString($password);

    // Password-Optional version for when looking up username
    $passwordClause = $passwordOptional ?
                   'AND     password = ' . $this->quote($hashPass) : NULL;

    $sql = 'SELECT  id,
                    username,
                    firstName,
                    lastName,
                    email,
                    CONCAT(firstName," ",lastName) AS fullName
            FROM    users
            WHERE   username = ' . $this->quote($username) . '
            ' . $passwordClause . '
            LIMIT   1';

    return runQuery($sql);
}
