<?php

namespace tables\users;

class groups extends \tables\_default
{
    public $displaySingle = 'Groups';

    public $ajaxModel = 'users\\groups';

    public $primaryKey = 'id';

    public $fields = [
        'groupName' => [
            'display' => 'User Group',
        ],
        'description' => [
            'display' => 'Description',
        ],
        'active' => [
            'select' => 'IF(active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $table = 'groups';

    public $mainField = 'id';

    public $hiddenFields = [
        'hiddenName' => [
            'after' => 'groupName',
            'display' => 'Hidden Name',
        ]
    ];

    public $orderBy = 'groupName ASC';

    public $customInsert = 'users\\groups';

    /*
    ****************************************************************************
    */

    function insertTable()
    {
        return $this->table;
    }

    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        $groupName = $post['groupName'];
	    $hiddenName = $post['hiddenName'];
        $description = $post['description'];
        $active = $post['active'];

        $sql = 'INSERT INTO groups (
                    groupName, hiddenName, description, active
                ) VALUES (
                    ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    description = ?,
                    active = ?';

        $ajaxRequest = TRUE;

        $param = [$groupName, $hiddenName, $description, $active, $description,
            $active];

        $this->app->runQuery($sql, $param, $ajaxRequest);
    }


    /*
    ****************************************************************************
    */

}