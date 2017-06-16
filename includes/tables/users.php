<?php

namespace tables;

class users extends _default
{
    public $displaySingle = 'User';

    public $ajaxModel = 'users';

    public $primaryKey = 'u.id';

    public $fields = [
        'employer' => [
            'update' => 'u.employer',
            'select' => 's.displayName',
            'display' => 'Employer',
            'searcherDD' => 'statuses\employer',
            'ddField' => 'displayName',
        ],
        'firstName' => [
            'select' => 'u.firstName',
            'display' => 'First Name',
        ],
        'lastName' => [
            'select' => 'u.lastName',
            'display' => 'Last Name',
        ],
        'username' => [
            'select' => 'u.username',
            'display' => 'Username',
        ],
        'email' => [
            'select' => 'u.email',
            'display' => 'Email',
        ],
        'active' => [
            'select' => 'IF(u.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'u.active',
            'updateOverwrite' => TRUE,
        ],
        'dropDown' => [
            'select' => 'IF(u.dropDown, "Yes", "No")',
            'display' => 'Drop Down',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'u.dropDown',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $mainField = 'username';

    public $customInsert = 'users';

    /*
    ****************************************************************************
    */

    function insertTable()
    {
        $userDB = $this->app->getDBName('users');

        return $userDB.'.info';
    }

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return $userDB.'.info u
               LEFT JOIN statuses s ON s.id = u.employer
               LEFT JOIN users_access a ON u.id = a.userID
               LEFT JOIN user_levels l ON l.id = a.levelID
               ';
    }

    /*
    ****************************************************************************
    */

    function get($userID=FALSE)
    {
        $where = $userID ? 'AND       u.id <> ' . $userID : '';
        $sql = 'SELECT    '.$this->primaryKey.',
                          '.$this->getSelectFields().',
                          CONCAT(lastName, ", ", firstName) AS lastFirst
                FROM      '.$this->table.'
                WHERE     u.active = 1
                AND       dropDown = 1
                ' . $where . '
                ORDER BY  lastFirst ASC';

        return $this->app->queryResults($sql);
    }

    /*
    ****************************************************************************
    */

    function lookUp($username)
    {
        $sql = 'SELECT '.$this->primaryKey.',
                       '.$this->getSelectFields().',
                       l.level
                FROM   '.$this->table.'
                WHERE  u.active = 1
                AND    username = ?';

        return $this->app->queryResult($sql, [$username]);
    }

    /*
    ****************************************************************************
    */

    function htmlSelect()
    {
        $users = $this->get();
        ob_start();
        ?>
        <select id="userID" name="userID">
           <option value="0">Select Username</option>
           <?php foreach ($users as $userID => $info) { ?>
           <option value="<?php echo $userID; ?>">
               <?php echo $info['lastFirst']; ?>
           </option>
           <?php } ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        $fields = array_keys($post);
        $fields[] = 'password';

        $params = array_values($post);
        $params[] = md5($post['username']);

        $userDB = $this->app->getDBName('users');

        $sql = 'INSERT INTO '.$userDB.'.info (
                    '.implode(',', $fields).'
                ) VALUES (
                    '.$this->app->getQMarkString($fields).'
                )';

        $ajaxRequest = TRUE;

        $this->app->runQuery($sql, $params, $ajaxRequest);
    }

    /*
    ****************************************************************************
    */

    function getUser($userID)
    {
        $sql = 'SELECT    u.id,
                          CONCAT(firstName, " ", lastName) AS fullName,
                          u.email
                FROM      '.$this->table.'
                WHERE     u.id = ?
                ORDER BY  fullName ASC';

        return $this->app->queryResult($sql, [$userID]);
    }

    /*
    ****************************************************************************
    */

}
