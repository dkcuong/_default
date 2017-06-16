<?php

namespace tables\users;

class pageParams extends \tables\_default
{
    public $displaySingle = 'Page Parameters';

    public $ajaxModel = 'users\\pageParams';

    public $primaryKey = 'pp.id';

    public $fields = [
        'displayName' => [
            'display' => 'Page Name',
            'searcherDD' => 'users\\pages',
            'ddField' => 'displayName',
            'update' => 'pageID',
        ],
        'active' => [
            'select' => 'IF(pp.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $table = 'page_params pp
            JOIN      pages p ON p.id = pp.pageID';

    public $mainField = 'displayName';

    public $customInsert = 'users\\pageParams';

    public $insertTable = 'page_params';
 
    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        $pageID = $post['displayName'];
        $statusID = $post['active'];

        $sql = 'INSERT INTO page_params (
                    pageID, active
                ) VALUES (
                    ?, ?
                )';

        $ajaxRequest = TRUE;

        $param = [$pageID, $statusID];

        $this->app->runQuery($sql, $param, $ajaxRequest);
    }

    /*
    ****************************************************************************
    */

    function getPageParams($pageID, $returnRow=FALSE)
    {
        $pageIDs = is_array($pageID) ? $pageID : [$pageID];
        
        $qMarks = $this->app->getQMarkString($pageIDs);

        $sql = 'SELECT    id,
                          pageID,
                          name,
                          value
                FROM      page_params
                WHERE     pageID IN (' . $qMarks . ')
                AND       active
                ORDER BY  pageID
                ';

        $results = $this->app->queryResults($sql, $pageIDs);

        $return = [];

        foreach ($results as $id => $row) {

            $row['id'] = $id;
            $name = $row['name'];
            $value = $row['value'];
            $pageID = $row['pageID'];

            $return[$pageID][$name] = $returnRow ? $row : $value;
        }

        return getDefault($return, []);
    }

    /*
    ****************************************************************************
    */    
}