<?php

namespace tables;

use \get\string;
use models\config;

class _default
{

    public $ajaxSource = NULL;

    /*
    ****************************************************************************
    */

    public $fields = [
        'id' => [
            // This will be used to calculate the value in select queries
            'select' => '',
            // Only these will be selected for DTs
            'display' => '',
            // These won't be inserted
            'ignore' => '',
        ],
    ];

    private $errorMsg = '';


    const INTERVAL_ONE_MONTH = 'INTERVAL 1 MONTH';
    const INTERVAL_TWO_WEEK = 'INTERVAL 2 WEEK';
    const SELECT_INTERVAL_MONTH = 'MONTH';
    const SELECT_INTERVAL_WEEK = 'WEEKS';
    const NUMBER_INTERVAL_MONTH = 1;
    const NUMBER_INTERVAL_WEEK = 2;

    /*
    ****************************************************************************
    */

    function __construct($app=FALSE)
    {
        $this->app = $app ? $app : NULL;

        $customSearchController =
            getDefault($this->customSearchController, 'datatables');

        $this->ajaxSource = isset($this->ajaxModel) ?
            jsonLink($customSearchController, [
                'modelName' => $this->ajaxModel
            ]) : NULL;

        $this->getTable();

        $this->fields = method_exists($this, 'fields') ?
            $this->fields() : getDefault($this->fields);

        $this->where = method_exists($this, 'where') ?
            $this->where() : getDefault($this->where, 1);

        $this->insertTable = method_exists($this, 'insertTable') ?
            $this->insertTable() : getDefault($this->insertTable);

        $this->addBarcodeAppName();

        return $this;
    }

    /*
    ****************************************************************************
    */

    function getAjaxSource()
    {
        return $this->ajaxSource;
    }

    /*
    ****************************************************************************
    */

    function getTable()
    {
        if (! isset($this->table)) {
            $this->table = method_exists($this, 'table') ?
                $this->table() : getDefault($this->table);
        }

        return $this->table;
    }

    /*
    ****************************************************************************
    */

    function getQueryPiece($target)
    {
        $name = $mysql = NULL;

        switch ($target) {
            case 'orderBy':
                $name = $target;
                $mysql = 'ORDER BY';
                break;
            case 'groupBy':
                $name = $target;
                $mysql = 'GROUP BY';
        }

        $property = getDefault($this->$name, NULL);

        return $property ? ' ' . $mysql . ' ' . $property : NULL;
    }

    /*
    ****************************************************************************
    */

    function unsetAppProp()
    {
        unset($this->app);
    }

    /*
    ****************************************************************************
    */

    function getDropdown($selectField='displayName')
    {
        $where = getDefault($this->where, 1);

        $dropdownWhere = getDefault($this->dropdownWhere);

        if ($dropdownWhere) {
            $where = $dropdownWhere;
        }

        $orderBy = isset($this->orderBy) ? $this->orderBy : $selectField;

        $sql = 'SELECT   ' . $this->primaryKey . ',
                         ' . $selectField . '
                FROM     ' . $this->table . '
                WHERE    ' . $where . '
                GROUP BY ' . $selectField . '
                ORDER BY ' . $orderBy;

        $results = $this->app->queryResults($sql);

        return array_map('reset', $results);
    }

    /*
    ****************************************************************************
    */

    function get($primaryKey=FALSE)
    {
        $clause = $primaryKey ? $this->primaryKey . ' = ?' : 1;
        $param = $primaryKey ? [$primaryKey] : [];
        $orderBy = isset($this->orderBy) ? 'ORDER BY ' . $this->orderBy : NULL;

        $selectFields = $this->getSelectFields();

        $sql = 'SELECT  ' . $selectFields . '
                FROM    ' . $this->table . '
                WHERE   ' . $clause . '
                        ' . $orderBy;

        $results = $this->app->queryResults($sql, $param);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function fieldQMark($field)
    {
        return $field.' = ? ';
    }

    /*
    ****************************************************************************
    */

    function search($params)
    {
        $glue = getDefault($params['glue'], 'AND');

        $searchParams = getDefault($params['search']);

        $selectField = getDefault($params['selectField']);
        $calcFoundRows = getDefault($params['calcFoundRows']);

        $term = getDefault($params['term']);
        $termParams = is_array($term) ? $term : [$term];
        $termClause = getDefault($params['clause']);
        $clauses = $termClauses = is_array($termClause) ?
        $termClause : [$termClause];

        $searchTerms = getDefault($params['searchTerms']);

        $searchs = $searchTerms ? array_keys($searchTerms) : $searchParams;
        $terms = $searchTerms ? array_values($searchTerms) : $termParams;

        $orderBy = isset($params['orderBy']) ?
                    'ORDER BY ' . $params['orderBy'] :  NULL;

        $wherClause = isset($this->where) ? ' AND ' . $this->where : NULL;

        $searchArray = is_array($searchs);
        $clause = $searchArray ? NULL : self::fieldQMark($searchs);

        if (! array_filter($termClauses)) {
            $clauses = $searchArray ?
                array_map([$this, 'fieldQMark'] , $searchs) :
                array_fill(0, count($terms), $clause);
        }

        $firstGlue = $clauseString = NULL;
        foreach ($clauses as $clause) {
            $clauseString.= ' 
            '.$firstGlue.$clause;
            $firstGlue = $glue.' ';
        }

        $wherClause .= trim($clauseString) ? ' AND ('. $clauseString . ' 
        ) ' : NULL;

        $groupBy = isset($this->groupBy) ? 'GROUP BY ' . $this->groupBy : NULL;

        $havingClause = isset($this->having) ? 'HAVING ' . $this->having : NULL;

        // Add field to the query that aren't included in the table
        $addFields = getDefault($params['addFields']);

        $addFieldsArray = is_array($addFields) ? $addFields : [$addFields];

        $addFieldString = array_filter($addFieldsArray) ?
        ',' . implode(',', $addFieldsArray) : NULL;

        $selectFields = $selectField ? $selectField : $this->getSelectFields();

        $limit = isset($params['limit']) ? 'LIMIT '.$params['limit'] : NULL;

        $selectFoundRows = $calcFoundRows ? 'SQL_CALC_FOUND_ROWS' : NULL;

        $sql = 'SELECT ' . $selectFoundRows . ' 
                ' . $this->primaryKey . ', 
                ' . $selectFields . ' 
                ' . $addFieldString . ' 
                FROM ' . $this->table . ' 
                WHERE 1 
                ' . $wherClause . ' 
                ' . $groupBy . ' 
                ' . $havingClause . ' 
                ' . $orderBy . ' 
                ' . $limit;

        if (isset($params['returnQuery'])) {
            $quoted = array_map([$this->app, 'quote'], $terms);
            $escapePercents = str_replace('%', '%%', $sql);
            $sfString = str_replace('?', '%s', $escapePercents);
            $fullQuery = $quoted ? vsprintf($sfString, $quoted) : $sfString;
            return str_replace('%%', '%', $fullQuery);
        }

        $result = isset($params['oneResult']) ?
        $this->app->queryResult($sql, $terms) :
        $this->app->queryResults($sql, $terms);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getQuery($primaryKey=NULL)
    {
        $defaultWhere = isset($this->where) ? '1 AND ' . $this->where : 1;

        $clause = $primaryKey ? $this->primaryKey . ' = ?' : $defaultWhere;

        // If multiple values, search in
        if (is_array($primaryKey) && count($primaryKey)) {
            $qMarkString = $this->app->getQMarkString($primaryKey);
            $clause = $this->primaryKey . ' IN (' . $qMarkString . ')';
        }

        $groupBy = isset($this->groupBy) ? 'GROUP BY ' . $this->groupBy : NULL;

        $having = isset($this->having) ? $this->having : NULL;

        $selectFields = $this->getSelectFields();

        $sql = 'SELECT  ' . $this->primaryKey . ',
                        ' . $selectFields . '
                FROM    ' . $this->table . '
                WHERE   ' . $clause . '
                        ' . $groupBy . '
                HAVING  1
                        ' . $having;

        return $sql;
    }

    /*
    ****************************************************************************
    */

    function getByID($primaryKey=NULL, $orderBy=FALSE)
    {
        $keyArray = is_string($primaryKey) ? [$primaryKey] : $primaryKey;
        $param = is_array($keyArray) ? $keyArray : [];

        $order = $orderBy ? 'ORDER BY ' . $orderBy : NULL;

        $queryString = $this->getQuery($primaryKey);

        $sql = $queryString . ' ' . $order;

        $results = $this->app->queryResults($sql, $param);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getSelectFields($fields=[], $returnArray=FALSE, $noAs=FALSE)
    {
        // Default to tables fields
        $fields = $fields ? $fields : $this->fields;

        // Uses key, default to select value
        $fieldSelects = [];
        foreach ($fields as $field => $info) {

            $infoSelect = getDefault($info['select']);

            $select = $noAs ? $infoSelect : $infoSelect . ' AS ' . $field;

            $fieldSelects[] = isset($info['select']) ? $select : $field;
        }

        return $returnArray ? $fieldSelects : implode(',', $fieldSelects);
    }

    /*
    ****************************************************************************
    */

    function getFieldValues($fields=[], $returnArray=FALSE)
    {
        // Default to tables fields
        $fields = $fields ? $fields : $this->fields;

        // Uses key, default to select value
        $fieldSelects = [];
        foreach ($fields as $field => $info) {
            $fieldSelects[] = isset($info['select']) ? $info['select'] : $field;
        }

        return $returnArray ? $fieldSelects : implode(',', $fieldSelects);
    }

    /*
    ****************************************************************************
    */

    function getNonIgnoredFields($fields=[], $returnArray=FALSE)
    {
        // Default to tables fields
        $fields = $fields ? $fields : $this->fields;

        $nonIgnored = [];
        foreach ($fields as $name => $field) {
            if (! isset($field['ignore'])) {
                $nonIgnored[] = $name;
            }
        }

        return $returnArray ? $nonIgnored : implode(',', $nonIgnored);
    }

    /*
    ****************************************************************************
    */

    function getMultiRowOrders($refFields='reference_id')
    {
        $sql = 'SELECT   ' . $refFields . ',
                         COUNT(' . $this->primaryKey . ') AS rowCount
                FROM     ' . $this->table . '
                GROUP BY ' . $refFields . '
                HAVING   COUNT(' . $this->primaryKey . ') > 1';

        $results = $this->app->queryResults($sql);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function searchByField($field, $term, $secondField=FALSE)
    {
        $fields = $this->fields;

        // Make sure not malicious content in field name
        if (! isset($fields[$field]) && ! in_array($field, $fields)) {
            return NULL;
        }

        $select = getDefault($fields[$field]['select'], $field);
        // Grouped fields need a different query
        $isGroupField = getDefault($fields[$field]['groupedFields'], NULL);

        $where = $isGroupField ? NULL : 'WHERE ' . $select . ' LIKE ?';

        $finalWhere = $secondField ?
            $where . ' OR ' . $secondField . ' LIKE ?' : $where;

        $grouping = $isGroupField ? $this->groupBy : $select;
        $having = $isGroupField ? 'HAVING groupField LIKE ?' : NULL;
        $selectField = $isGroupField ? '(' . $select . ') AS groupField' :
            $select;

        $table = getDefault($fields[$field]['acTable'], $this->table);

        $sql = 'SELECT   DISTINCT ' . $selectField . '
                FROM     ' . $table . '
                ' . $finalWhere . '
                GROUP BY ' . $grouping . '
                ' . $having . '
                ORDER BY ' . $select . '
                LIMIT    10';

        $params = $secondField ? [$term . '%', $term . '%'] : [$term . '%'];

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getNextID($table=FALSE)
    {
        $table = $table ? $table : $this->table;

        $sql = 'SHOW TABLE STATUS LIKE ?';

        $results = $this->app->queryResult($sql, [$table]);

        return $results ? $results['Auto_increment'] : NULL;
    }

    /*
    ****************************************************************************
    */

    function insertBlank($table=FALSE, $returnID=FALSE)
    {
        $table = $table ? $table : $this->table;

        $sql = 'INSERT INTO ' . $table . ' () VALUES ()';

        $result = $this->app->runQuery($sql);

        if ($result && $returnID) {
            return $this->app->lastInsertID();
        }

        return NULL;
    }

    /*
    ****************************************************************************
    */

    function idExists($container)
    {
        $sql = 'SELECT id
                FROM   ' . $this->table . '
                WHERE  id = ?';

        $result = $this->app->queryResult($sql, [$container]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function valueExists($field, $value)
    {
        $sql = 'SELECT ' . $field . '
                FROM   ' . $this->table . '
                WHERE  ' . $field . ' = ?';

        $result = $this->app->queryResult($sql, [$value]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getTypeFields($type, $fields=[], $returnArray=FALSE, $flip=FALSE)
    {
        $fields = $this->fields;

        // Uses key, default to select value
        $typeFields = [];
        foreach ($fields as $name => $info) {
            // Use flip param to get fields that arent the type
            if ($flip xor isset($info[$type])) {
                $typeFields[$name] = $info;
            }
        }

        return $returnArray ? $typeFields : implode(',', $typeFields);
    }

    /*
    ****************************************************************************
    */

    function orderInsretRow($values, $order=FALSE)
    {
        $order = $order ? $order : $this->fields;

        // Get order the array by keys other array
        $ordered = [];
        foreach ($order as $key) {
            $ordered[$key] = $values[$key];
        }
        return $ordered;
    }

    /*
    ****************************************************************************
    */

    function valid($targets, $check='id', $select=FALSE, $clientID=FALSE)
    {
        $selectAssoc = $select;

        $this->processDataInputOfValid([
            'targets' => &$targets,
            'check' => $check,
            'select' => &$select,
            'selectAssoc' => &$selectAssoc,
        ]);

        $dataCheck = $this->getDataCheckOfValid([
            'targets' => $targets,
            'check' => $check,
            'select' => $select,
            'selectAssoc' => $selectAssoc,
            'clientID' => $clientID
        ]);


        $result = $this->getValidAndPerrows([
            'targets' => $targets,
            'dataCheck' => $dataCheck,
            'selectAssoc' => $selectAssoc,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function updateOldStatus($status, $targets, $field, $statusField='status')
    {
        $targets = is_array($targets) ? $targets : [$targets];

        $sql = 'UPDATE ' . $this->table . '
                SET    ' . $statusField . ' = ?
                WHERE  ' . $field . ' = ?';

        $this->app->beginTransaction();

        foreach ($targets as $target) {
            $this->app->runQuery($sql, [$status, $target]);
        }

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function updateStatus($param)
    {
        $target = $param['target'];
        $field = getDefault($param['field'], 'id');
        $status = getDefault($param['status'], 'IN');
        $statusField = getDefault($param['statusField'], 'status');
        $transaction = getDefault($param['transaction'], TRUE);
        $table = getDefault($param['table'], $this->table);

        $targets = is_array($target) ? $target : [$target];

        $sql = 'UPDATE ' . $table . '
                SET    ' . $statusField . ' = ?
                WHERE  ' . $field . ' = ?';

        $transaction ? $this->app->beginTransaction() : FALSE;

        foreach ($targets as $target) {
            $target = isset($target['target']) ? $target['target'] : $target;
            $this->app->runQuery($sql, [$status, $target]);
        }

        $transaction ? $this->app->commit() : FALSE;
    }

    /*
    ****************************************************************************
    */

    function update($columnID, &$value, $rowID, $ajaxRequest=FALSE)
    {
        $fieldIDs = array_keys($this->fields);
        $field = getDefault($fieldIDs[$columnID]);
        $fieldInfo = $this->fields[$field];

        // Get custom ajax error for field if available
        $fieldUpdateError = getDefault($this->fields[$field]['updateError']);
        $updateError = $fieldUpdateError ? $fieldUpdateError : $ajaxRequest;

        $isValidate = $this->validateDataInputUpdate([
            'fieldInfo' => $fieldInfo,
            'value' => $value,
        ]);

        if (! $isValidate) {
            echo $this->errorMsg;
            return FALSE;
        }

        if (getDefault($fieldInfo['isDecimal'])) {
            $newValue = ceil($value * 4) / 4;
        }

        $updateOverwrite = getDefault($fieldInfo['updateOverwrite']);

        $updateField = $overwriteField = getDefault(
            $fieldInfo['update'],
            $field
        );

        $updateFieldSelect = isset($this->fields[$updateField]['select']) ?
                $this->fields[$updateField]['select'] : NULL;

        $updateField = isset($updateFieldSelect) ?
                $updateFieldSelect : $updateField;

        if (! $updateField) {
            return 'Field not found';
        }

        $previous = NULL;

        $queryValue = isset($newValue) ? $newValue : $value;

        if (isset($this->groupBy)) {
            // Hacky way to deal with grouped by table updates until we have a
            // better database structure
            $whereResult = $this->getWhereResultWithGroupBy([
                'rowID' => $rowID,
                'ajaxRequest' => $updateError
            ]);

            $params = $whereResult = array_values($whereResult);

            $whereClause = str_replace(',', ' = ? AND ', $this->groupBy)
                . ' = ?';

            $previous = $this->getPreviousValueUpdateHaveGroupBy([
                'updateField' => $updateField,
                'whereClause' => $whereClause,
                'sqlParams' => $params,
            ]);

            if ($previous != $value) {
                $this->updateNewValueHaveGroupBy([
                    'updateField' => &$updateField,
                    'updateOverwrite' => $updateOverwrite,
                    'overwriteField' => $overwriteField,
                    'whereClause' => $whereClause,
                    'sqlParams' => $params,
                    'value' => $value,
                    'fieldInfo' => $fieldInfo,
                    'ajaxRequest' => $updateError,
                ]);
            }

        } else {
            // If the value is from a foreign table, look up the ID
            if (isset($fieldInfo['updateTable'])) {

                $queryValue = $this->getQueryValueFromForeignTable([
                    'fieldInfo' => $fieldInfo,
                    'value' => $value,
                ]);

                if ($queryValue === FALSE) {
                    echo $this->errorMsg;
                    return FALSE;
                }
            }

            $previous = $this->getPreviousValueUpdateNoneGroupBy([
                'updateField' => $updateField,
                'rowID' => $rowID,
            ]);

            if ($previous != $queryValue) {
                $this->updateNewValueNoneGroupBy([
                    'updateField' => &$updateField,
                    'updateOverwrite' => $updateOverwrite,
                    'overwriteField' => $overwriteField,
                    'queryValue' => $queryValue,
                    'rowID' => $rowID,
                    'fieldInfo' => $fieldInfo,
                    'ajaxRequest' => $updateError,
                ]);
            }
        }

        if ($previous != $queryValue) {
            history::addUpdate([
                'model' => $this,
                'field' => $field,
                'rowID' => $rowID,
                'toValue' => $value,
                'fromValue' => $previous,
            ]);
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function addBarcodeAppName()
    {
        if (isset($this->barcodePage)) {
            $appName = config::get('site', 'appName');
            $this->barcodePage = '/' . $appName . '/' . $this->barcodePage;
        }
    }

    /*
    ****************************************************************************
    */

    function addRowQuery($post, $unbound=FALSE)
    {
        $valuesClause = getDefault($post['valuesClause']);

        unset($post['valuesClause']);

        $values = [];

        // Allow insert of fields that are not displayed
        $hiddenInserts = getDefault($this->hiddenInsertFields, []);

        if ($unbound) {
            $insertFields = $this->getNonIgnoredFields([], TRUE);
            $allowedKeys = array_merge($insertFields, $hiddenInserts);
            $insertFieldKeys = array_flip($allowedKeys);
            $values = array_intersect_key($post, $insertFieldKeys);
        } else {
            $values = $post;
        }

        $valueString = implode(', ', array_keys($values));

        if ( $valuesClause) {
            $queryParams = [];
        } else {

            $quoted = array_map([$this->app, 'quote'], $values);

            $params = $unbound ?
                $this->app->getQMarkString($values) : implode(', ', $quoted);

            $valuesClause = 'VALUES ('.$params.')';

            $queryParams = array_values($values);
        }

        $sql = 'INSERT INTO '.$this->insertTable.' ('.$valueString.')
                '.$valuesClause.';';

        return $unbound ? [
            'sql' => $sql,
            'params' => $queryParams,
        ] : $sql;
    }

    /*
    ****************************************************************************
    */

    function editableAddRow()
    {
        $post = $this->app->post;
        $badInput = \format\nonUTF::check($post);

        if ($badInput) {
            die('Input contains non-UTF encoding charactor(s)!');
        }

        $customInsert = getDefault($this->customInsert);

        if ($customInsert == $this->app->get['modelName']) {

            $this->customInsert($post);

            return;
        }

        // Stop if a field has been submitted that does not exist

        $queryInfo = $this->addRowQuery($post, 'unbound');

        $ajaxRequest = TRUE;

        $result = $this->app->runQuery($queryInfo['sql'], $queryInfo['params'],
            $ajaxRequest);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function decimalCheck($num, $decimal)
    {
        $decimals = ( (int) $num != $num ) ?
            (strlen($num) - strpos($num, '.')) - 1 : 0;

        return $decimals <= $decimal;
    }

    /*
    ****************************************************************************
    */

    function getUpdateValue($value, $fieldInfo)
    {
        $maxLength = getDefault($fieldInfo['maxLength']);

        $maxLength = intVal($maxLength);

        $maxLength = abs($maxLength);

        return $maxLength && strlen($value) > $maxLength ?
                substr($value, 0, $maxLength) : $value;
    }

    /*
    ****************************************************************************
    */

    function setMysqlFilters($payload)
    {
        // Always run if a trigger was not sent
        if (isset($payload['trigger']) && ! $payload['trigger']) {
            return;
        }

        $ajax = $payload['ajax'];

        $ajax->setDisplayFilters($payload['searches']);
    }

    /*
    ****************************************************************************
    */

    function commonMysqlFilter($filter, $app, $ajax)
    {
        $field = $selectValue = $mysql = NULL;

        $this->processDataFilter([
            'filter' => $filter,
            'field' => &$field,
            'mysql' => &$mysql,
            'selectValue' => &$selectValue,
        ]);

        $notPrintingLabels = ! isset($app->get['cartonLabels']);

        $result = $this->setMysqlFilters([
            'ajax' => $ajax,
            'trigger' => $notPrintingLabels,
            'searches' => [
                [
                    'selectField' => 'Set Date Starting',
                    'selectValue' => $selectValue,
                    'clause' => $field . ' > NOW() - ' . $mysql,
                ],
            ],
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getInsertDropDown($param)
    {
        $caption = $param['caption'];
        $rel = $param['rel'];
        $source = $param['source'];
        $name = $param['name'];
        $field = getDefault($param['field'], $name); ?>

        <tr>
            <td><?php echo $caption; ?></td>
            <td>
                <select name="<?php echo $name; ?>" rel="<?php echo $rel++; ?>">
                <?php
                foreach ($source as $key => $value) { ?>
                    <option value="<?php echo $key; ?>">
                        <?php echo $value[$field]; ?></option><?php
                } ?>
                </select>
            </td>
        </tr><?php

        return $rel;
    }

    /*
    ****************************************************************************
    */

    function getActiveValues($active='Yes', $column=NULL)
    {
        $query = $this->getQuery();

        $results = $this->app->queryResults($query);

        $activeValues = [];

        foreach ($results as $key => $value) {
            if ($value['active'] == $active) {

                unset($value['active']);

                $activeValues[$key] = $value;
            }
        }

        return $column ? array_column($activeValues, $column) : $activeValues;
    }

    /*
    ****************************************************************************
    */

    function processDataInputOfValid($params)
    {
        $targets = &$params['targets'];
        $check = $params['check'];
        $select = &$params['select'];
        $selectAssoc = &$params['selectAssoc'];


        // If an associative key
        if (is_array($select)) {
            $selectAssoc = $select['assoc'];
            $select = $select['field'];
        }

        // If check is not set it will default to id
        // If select is not set it will default to check
        $select = $select ? $select : $check;

        $targets = is_array($targets) ? $targets : [$targets];
    }

    /*
    ****************************************************************************
    */

    function getDataCheckOfValid($params)
    {
        $targets = $params['targets'];
        $check = $params['check'];
        $select = $params['select'];
        $selectAssoc = $params['selectAssoc'];
        $clientID = $params['clientID'];
        $whereClause = '';
        $params = $targets;

        if ($check == 'clientordernumber' && $clientID) {
            $whereClause = ' AND v.id = ?';
            $params = array_merge($params, [$clientID]);
        }

        $qMarkString = $this->app->getQMarkString($targets);

        $sql = 'SELECT ' . $check . ',
                       ' . $select . ' AS ' . $selectAssoc . '
                FROM   ' . $this->table . '
                WHERE  ' . $check . ' IN (' . $qMarkString . ')'
            . $whereClause;

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getValidAndPerrows($params)
    {
        $targets = $params['targets'];
        $dataCheck = $params['dataCheck'];
        $selectAssoc = $params['selectAssoc'];

        $allValid = $perRows = [];
        foreach ($targets as $target) {

            if (! isset($dataCheck[$target])) {
                continue;
            }

            $result = getDefault($dataCheck[$target][$selectAssoc]);

            $allValid[] = $result;
            $perRows[] = [
                'target' => $target,
                'id' => $result,
            ];
        }

        $filterAllValid = array_filter($allValid);
        $withoutEmpties = count($filterAllValid);

        $valid = $withoutEmpties == count($allValid) && $withoutEmpties > 0;
         // If any of the results were empty then return false
        $results = [
            'valid' => $valid,
            'perRow' => $perRows,
        ];

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkFieldIsNumber($fieldInfo, $value)
    {
        if (! getDefault($fieldInfo['isNum'])) {
              return TRUE;
        }

        if (! is_numeric($value)) {
            $this->errorMsg = 'Must Enter a Number';
            return FALSE;
        }

        if (getDefault($fieldInfo['isDecimal'])) {
            if (! $this->decimalCheck($value, $fieldInfo['isDecimal'])) {
                $this->errorMsg = 'Only one decimal number allowed';
                return FALSE;
            }
        }

        if (getDefault($fieldInfo['limitmax'])) {
            if ($value > $fieldInfo['limitmax']) {
                $this->errorMsg = 'Number can not be more than '
                    . $fieldInfo['limitmax'];
                return FALSE;
            }
        }

        if (getDefault($fieldInfo['limitmin'])) {
            if ($value < $fieldInfo['limitmin']) {
                $this->errorMsg = 'Number can not be less than '
                    .  $fieldInfo['limitmin'];
                return FALSE;
            }
        }

        if ($fieldInfo['isNum'] != 'unl'
            && strlen($value) > $fieldInfo['isNum']
        ) {
            $this->errorMsg = 'Number maximum input length is '
                . $fieldInfo['isNum'];
            return FALSE;
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function validateDataInputUpdate($params)
    {
        $value = $params['value'];
        $fieldInfo = $params['fieldInfo'];

        if (! getDefault($fieldInfo['allowNull']) || $value) {
            $isValidate = $this->checkFieldIsNumber($fieldInfo, $value);
            if (! $isValidate) {
                return FALSE;
            }
        }

        if ($value && getDefault($fieldInfo['searcherDate'])) {
            $format = 'Y-m-d';
            $date = \DateTime::createFromFormat($format, $value);
            if (! $date || $date->format($format) != $value) {
                $this->errorMsg = 'Date is not valid';
                return FALSE;
            }
        }

        $trimmedValue = trim($value);

        if (! $trimmedValue && getDefault($fieldInfo['noEmptyInput'])) {
            $this->errorMsg = 'No empty input is allowed!';
            return FALSE;
        }

        $nonUTFInput = \format\nonUTF::check($value);

        if ($nonUTFInput) {
            $this->errorMsg = 'Input contains non-UTF encoding charactor(s)!';
            return FALSE;
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function runUpdate($params)
    {
        $idField = getDefault($params['idField'], 'id');

        $fieldUpdates = $values = [];
        foreach ($params['fieldUpdates'] as $field => $value) {
            $values[] = $value;
            $fieldUpdates[] = $field.' = ?';
        }

        array_push($values, $params['idSearch']);

        $sql = 'UPDATE '.$this->table.'
                SET    '.implode(', ', $fieldUpdates).'
                WHERE  '.$idField.' = ?';


        $this->app->runQuery($sql, $values);
    }

    /*
    ****************************************************************************
    */

    function getWhereResultWithGroupBy($params)
    {
        $rowID = $params['rowID'];

        $sql = 'SELECT  ' . $this->groupBy . '
                FROM    ' . $this->table . '
                WHERE   ' . $this->primaryKey . ' = ?';

        $result = $this->app->queryResult($sql, [$rowID]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getPreviousValueUpdateHaveGroupBy($params)
    {
        $updateField = $params['updateField'];
        $whereClause = $params['whereClause'];
        $sqlParams = $params['sqlParams'];

        $sql = 'SELECT  ' . $updateField . ' AS previousValue
                FROM    ' . $this->table . '
                WHERE   ' . $whereClause;

        $result = $this->app->queryResult($sql, $sqlParams);

        return $result ? $result['previousValue'] : NULL;
    }

    /*
    ****************************************************************************
    */

    function updateNewValueHaveGroupBy($params)
    {
        $updateField = &$params['updateField'];
        $updateOverwrite = $params['updateOverwrite'];
        $overwriteField = $params['overwriteField'];
        $whereClause = $params['whereClause'];
        $value = $params['value'];
        $fieldInfo = $params['fieldInfo'];
        $ajaxRequest = $params['ajaxRequest'];
        $sqlParams = $params['sqlParams'];

        $updateField = $updateOverwrite ? $overwriteField : $updateField;

        $sql = 'UPDATE  ' . $this->table . '
                SET     ' . $updateField . ' = ?
                WHERE   ' . $whereClause;

        $updateValue = $this->getUpdateValue($value, $fieldInfo);

        array_unshift($sqlParams, $updateValue);

        $reuslt = $this->app->runQuery($sql, $sqlParams, $ajaxRequest);

        return $reuslt;
    }

    /*
    ****************************************************************************
    */

    function getQueryValueFromForeignTable($params)
    {
        $fieldInfo = $params['fieldInfo'];
        $value = $params['value'];

        $sql = 'SELECT id
                FROM   ' . $fieldInfo['updateTable'] . '
                WHERE  ' . $fieldInfo['updateField'] . ' = ?';

        $result = $this->app->queryResult($sql, [$value]);

        if (! $result) {
            $this->errorMsg = 'Value Not Found';
            return FALSE;
        }

        return $result['id'];
    }

    /*
    ****************************************************************************
    */

    function getPreviousValueUpdateNoneGroupBy($params)
    {
        $updateField = $params['updateField'];
        $rowID = $params['rowID'];

        $sql = 'SELECT  ' . $updateField . ' AS previousValue
                FROM    ' . $this->table . '
                WHERE   ' . $this->primaryKey . ' = ?';

        $result = $this->app->queryResult($sql, [$rowID]);

        return $result ? $result['previousValue'] : NULL;
    }

    /*
    ****************************************************************************
    */

    function updateNewValueNoneGroupBy($params)
    {
        $updateField = &$params['updateField'];
        $updateOverwrite = $params['updateOverwrite'];
        $overwriteField = $params['overwriteField'];
        $queryValue = $params['queryValue'];
        $rowID = $params['rowID'];
        $fieldInfo = $params['fieldInfo'];
        $ajaxRequest = $params['ajaxRequest'];

        $updateField = $updateOverwrite ? $overwriteField : $updateField;

        $customUpdate = getDefault($fieldInfo['customUpdate']);

        if ($customUpdate) {

            $params['customUpdate'] = $customUpdate;

            $this->customUpdate($params);

            return;
        }

        $sql = 'UPDATE  ' . $this->table . '
                SET     ' . $updateField . ' = ?
                WHERE   ' . $this->primaryKey . ' = ?';

        $updateValue = $this->getUpdateValue($queryValue, $fieldInfo);

        $sqlParams = [$updateValue, $rowID];

        $this->app->runQuery($sql, $sqlParams, $ajaxRequest);
    }

    /*
    ****************************************************************************
    */

    function processDataFilter($params)
    {
        $filter = $params['filter'];
        $field = &$params['field'];
        $mysql = &$params['mysql'];
        $selectValue = &$params['selectValue'];

        //oneMonth
        if ($filter == 'oneMonth') {
            $field = 'co.setDate';
            $mysql = self::INTERVAL_ONE_MONTH;
            $selectValue = string::date(-self::NUMBER_INTERVAL_MONTH,
                        self::SELECT_INTERVAL_MONTH);
        } else {
            //twoWeeks
            $field = 'setDate';
            $mysql = self::INTERVAL_TWO_WEEK;
            $selectValue = string::date(-self::NUMBER_INTERVAL_WEEK,
                    self::SELECT_INTERVAL_WEEK);
        }
    }

    /*
    ****************************************************************************
    */

    function handleColumnTitles(&$rowData)
    {
        foreach ($rowData as $key => &$display)
        {
            $display = $this->importField($display);

            \importer\importer::indexArrayFill([
                'model' => $this,
                'display' => $display,
                'key' => $key,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function importField($display)
    {
        $trimmed = trim($display);

        $lower = strToLower($trimmed);
        // replace spaces with underscorse for fields
        return str_replace([' ', '/'], '_', $lower);
    }

    /*
    ****************************************************************************
    */

    function checkUPCs($data)
    {
        $quantities = $data['quantities'];
        $vendorID = $data['vendorID'];
        // $isMezzanine paramter has NULL defaule value: i.e. both Mezzanine and
        // not Mezzanine inventory will be selected
        $isMezzanine = getDefault($data['isMezzanine'], NULL);
        $inventoryCheck = getDefault($data['inventoryCheck']);

        $cartons = new inventory\cartons($this->app);
        $upcs = new upcs($this->app);

        $upcKeys = array_keys($quantities);

        $results = $upcs->getUpcs($upcKeys);

        $existingUPCs = array_keys($results);

        $invalidUPCs = array_diff($upcKeys, $existingUPCs);

        if ($invalidUPCs) {
            $this->errors['invalidUPCs'] = array_flip($invalidUPCs);
        }

        $wrongUPCs = $upcs->getVendorUPCMismatch($upcKeys, $vendorID);

        if ($wrongUPCs) {
            $this->errors['wrongUPCs'] = $wrongUPCs;
        }

        if (! $inventoryCheck) {
            return;
        }

        $inventory = $cartons->getUPCQuantity([$vendorID => $upcKeys], $isMezzanine);

        foreach ($quantities as $upc => $values) {
            $inventory[$upc] = getDefault($inventory[$vendorID][$upc], 0) -
                	$values['quantity'];
        }

        $errorUpcKeys = [];

        foreach ($inventory as $upc => $quantity) {
            if ($quantity < 0) {
                $this->errors['noInventory'][$upc][] = abs($quantity);
                $errorUpcKeys[$upc] = TRUE;
            }
        }

        $errorOrders = [];

        $errorUpcs = array_keys($errorUpcKeys);

        foreach ($errorUpcs as $upc) {
            $errorOrders += getDefault($quantities[$upc]['orders'], []);
        }

        return $errorOrders;
    }

    /*
    ****************************************************************************
    */

    function updateWhere($whereClause, $concat=FALSE)
    {
        $this->where = $concat ?
            $this->where.' '.$concat.' '.$whereClause : $whereClause;
        return $this;
    }

    /*
    ****************************************************************************
    */

    function excludeExportFields()
    {
        $fieldKeys = isset($this->excludeExportFields) ?
            array_flip($this->excludeExportFields) : [];

        $this->fields = $fieldKeys ?
            array_diff_key($this->fields, $fieldKeys) : $this->fields;
    }

    /*
    ****************************************************************************
    */

}