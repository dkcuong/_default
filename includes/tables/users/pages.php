<?php

namespace tables\users;

class pages extends \tables\_default
{
    public $displaySingle = 'Pages';

    public $ajaxModel = 'users\\pages';

    public $primaryKey = 'id';

    public $fields = [
        'displayName' => [
            'display' => 'Page Name',
        ],
        'red' => [
            'select' => 'IF(red, "Yes", "No")',
            'display' => 'Red',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'red',
            'updateOverwrite' => TRUE,
        ],
        'clientAccess' => [
            'select' => 'IF(clientAccess, "Yes", "No")',
            'display' => 'Client Access',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'clientAccess',
            'updateOverwrite' => TRUE,
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

    public $table = 'pages';

    public $mainField = 'displayName';

    public $customInsert = 'users\\pages';

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
        $displayName = $post['displayName'];
        $red = $post['red'];
        $active = $post['active'];

        $sql = 'INSERT INTO pages (
                    displayName, red, active
                ) VALUES (
                    ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    red = ?,
                    active = ?';

        $ajaxRequest = TRUE;

        $param = [$displayName, $red, $active, $red, $active];

        $this->app->runQuery($sql, $param, $ajaxRequest);
    }


    /*
    ****************************************************************************
    */

    function getPages()
    {
        $sql = 'SELECT    '.$this->primaryKey.',
                          '.$this->mainField.'
                FROM      '.$this->table.'
                WHERE     '.$this->where.'
                ORDER BY  '.$this->mainField.' ASC';

        return $this->app->queryResults($sql);
    }

    /*
    ****************************************************************************
    */

    function getUserPages($groups, $pageParams, $isClient=FALSE)
    {
        $clause = $clientClause = $join = NULL;
        $params = [];

        $qMarks = $this->app->getQMarkString($groups);

        if ($groups !== FALSE) {

            $join   = 'JOIN group_pages gp ON gp.pageID = p.id';

            $clause = 'AND groupID IN (' . $qMarks . ')
                AND    gp.active';

            $params = $groups;
        }

        $submenuField = $isClient ? 
                'IF(sm.hiddenName = "signOut", sm.displayName, "WMS")' : 
                'sm.displayName';
        $clientClause = $isClient ? 'AND       clientAccess' : NULL;

        $sql = 'SELECT    p.id,
                          ' . $submenuField . ' AS subMenu,
                          p.displayName AS page,
                          class,
                          method,
                          red, 
                          app
                FROM      pages p
                ' . $join . '
                JOIN      submenu_pages sp ON sp.pageID = p.id
                JOIN      submenus sm ON sm.id = sp.subMenuID
                WHERE     sp.active
                AND       sm.active
                ' . $clause . '
                ' . $clientClause . '
                ORDER BY sm.displayOrder, p.displayOrder';

        $pageResults = $this->app->queryResults($sql, $params);

        if (! $pageResults) {
            return [];
        }

        $pageIDs = array_keys($pageResults);

        $paramResults = $pageParams->getPageParams($pageIDs);

        foreach ($paramResults as $pageID => $pageParams) {
            $pageResults[$pageID]['pageParams'] = $pageParams;
        }

        return $pageResults;
    }

    /*
    ****************************************************************************
    */

    function getIDByHiddenNameQuery($name, $value, $hiddenName)
    {
        $sql = 'SELECT    id AS `pageID`,
                          "' . $name . '" AS `name`,
                          "' . $value . '" AS `value`,
                          1 AS active
                FROM      pages
                WHERE     hiddenName = "' . $hiddenName . '"';

        return $sql;
    }

    /*
    ****************************************************************************
    */

    function getPagesOrderQuery($data)
    {
        $type = $data['type'];
        $submenu = $data['submenu'];
        $page = getDefault($data['page'], NULL);
        $subMenuID = $data['subMenuID'];
        $displayName = $data['displayName'];
        $hiddenName = $data['hiddenName'];
        $class = $data['class'];
        $method = $data['method'];
        $red = $data['red'];
        $active = $data['active'];

        $before = strtolower($type) == 'before';

        $compare = $before ? '<' : '>';
        $sortOrder = $before ? 'DESC' : 'ASC';

        if ($page) {

            $orderNumber = $before ? 'p.displayOrder / 2' : 'p.displayOrder + 1';

            $displayOrder = '
                IF(
                      p.displayOrder = AVG(a.displayOrder),
                      ' . $orderNumber . ',
                      AVG(a.displayOrder)
                )';

            $clause = 'sm.hiddenName = "' . $submenu . '"
                AND       sm.active
                AND       sp.active
                AND       p.hiddenName = "' . $page .'"';

            $subQuery = 'SELECT    p.displayOrder
                         FROM      pages p
                         JOIN      submenu_pages sp ON sp.pageID = p.id
                         JOIN      submenus sm ON sm.id = sp.subMenuID
                         WHERE     ' . $clause;

            $table = 'pages p
                JOIN      submenus sm ON sm.id = p.subMenuID
                JOIN      (
                    (
                        ' . $subQuery .'
                    ) UNION (
                        SELECT    p.displayOrder
                        FROM      pages p
                        JOIN      submenu_pages sp ON sp.pageID = p.id
                        JOIN      submenus sm ON sm.id = sp.subMenuID
                        JOIN      (
                            ' . $subQuery .'
                        ) m
                        WHERE     sm.hiddenName = "' . $submenu . '"
                        AND       sp.active
                        AND       sm.active
                        AND       p.displayOrder ' . $compare . ' m.displayOrder
                        ORDER BY  p.displayOrder ' . $sortOrder . '
                        LIMIT 1
                    )
                ) a';
        } else {
            // add the page to the end of the list
            $displayOrder = 'a.displayOrder + 1';

            $table = '(
                SELECT    p.displayOrder
                FROM      pages p
                JOIN      submenu_pages sp ON sp.pageID = p.id
                JOIN      submenus sm ON sm.id = sp.subMenuID
                WHERE     sm.hiddenName = "' . $submenu . '"
                ORDER BY  p.displayOrder DESC
                LIMIT 1
            ) a';

            $clause = 1;
        }

        $sql = 'SELECT    ' . $subMenuID . ' AS subMenuID,
                          "' . $displayName . '" AS displayName,
                          ' . $displayOrder . ' AS displayOrder,
                          "' . $hiddenName . '" AS hiddenName,
                          "' . $class . '" AS class,
                          "' . $method . '" AS method,
                          ' . $red . ' AS red,
                          ' . $active . ' AS active
                FROM      ' . $table . '
                WHERE     ' . $clause;

        return $sql;
    }

    /*
    ****************************************************************************
    */

}