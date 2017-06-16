<?php

class controller extends template
{
    /*
    ****************************************************************************
    */

    function userLoginController()
    {
        models\directories::checkAll();

        if (isset($this->get['code'])) {
            $this->codeSent = isset($this->get['code']);
        } else {

            $code = getDefault($_SESSION['loginCode']);

            if ($code) {

                $link = makeLink('login', 'user', [
                    'password' => 'recovery',
                ]);

                unset($_SESSION['loginCode']);

                redirect($link);
            }
        }

        $isPassRec = isset($this->get['password']);

        // Use method for password recovery or login
        $this->results = $isPassRec ?
            $this->modelPasswordRecovery() :
            login(['database' => $this]);

        $this->passTitle = $isPassRec ? 'EMAIL OR CODE' : 'PASSWORD';

        $this->recoverTitle
                = $isPassRec ? 'Back to Login' : 'Password Recovery';

        $this->inputType = $isPassRec ? 'text' : 'password';

        $passOption = $isPassRec ? [] : ['password' => 'recovery'];

        $this->passwordLink = makeLink('login', 'user', $passOption);

        $this->tooManyFails = getDefault($this->results['tooManyFails']);
    }

    /*
    ****************************************************************************
    */

    function changePasswordLoginController()
    {
        $newPass = getDefault($this->post['newPass'], NULL);

        if ($newPass) {
            if (strlen($newPass) < 8 || ! ctype_alnum($newPass)) {
                return $this->error = 'The new password must be at least eight
                    characters long and alpha-numeric';
            }

            if ($newPass != $this->post['confirmPass']) {
                return $this->error = 'The new password did not match the
                    confirmation password entered';
            }

            $userDB = $this->getDBName('users');

            $sql = 'SELECT id,
                           password
                    FROM   ' . $userDB . '.info
                    WHERE  username = ?';

            $username = access::getUserInfoValue('username');
            $info = $this->queryResult($sql, [$username]);

            $sql = 'UPDATE ' . $userDB . '.info
                    SET    password = ?
                    WHERE  id = ?';

            $this->runQuery($sql, [
                md5($newPass),
                $info['id'],
            ]);

            $this->success = 'Password has been succussfully updated';

            unset($_SESSION['loginCode']);
        }
    }

    /*
    ****************************************************************************
    */
}