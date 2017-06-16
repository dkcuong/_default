<?php

namespace datatables;

use models\vars;
use csv\export as csvExporter;
use excel\exporter as excelExporter;

class ajax
{
    static public $ajaxProperties = [
        'data' => TRUE,
        'draw' => TRUE,
        'custom' => TRUE,
        'recordsTotal' => TRUE,
        'recordsFiltered' => TRUE,
    ];

    // Debug config properties
    public $outputSQL = FALSE;
    public $timeOutput = FALSE;

    /*
    ****************************************************************************
    */

    public $vars;
    public $fields = [];
    public $params = [];
    public $clause = NULL;
    public $isOrQuery = [];
    public $fieldKeys = [];
    public $fieldNames = [];
    public $modelFields = [];
    public $queryParams = [
        'wheres' => [],
        'havings' => [],
    ];
    public $selectFields = [];
    public $displayParams = [];
    public $searchClauses = [];
    public $concatWhere = FALSE;
    public $overrideWhere = FALSE;
    public $requiresHavingOnly = [];
    public $selectFieldsByFields = [];
    public $presetDisplayFilters = [];
    public $columnHeadersCreated = FALSE;
    public $searchedGroupedFields = [];
    public $searchingGroupedFields;

    //search from triggering until there are at least 3 string length
    public $quickSearchLength = 3;

    /*
    ****************************************************************************
    */

    function __construct($app, $params=[])
    {
        // Add required DT JS
        $app->includeJS['js/datatables/ajax.js'] = TRUE;
        $app->includeCSS['css/datatables/ajax.css'] = TRUE;

        $this->app = $app;

        $this->vars = isset($params['vars']) ? $params['vars'] : vars::init();

        $this->setJsVarsDefault();

        return $this;
    }

    /*
    ****************************************************************************
    */

    function setJsVarsDefault()
    {
        $this->app->jsVars['quickSearchLength'] = $this->quickSearchLength;
    }

    /*
    ****************************************************************************
    */

    function isOrQuery($index)
    {
        if (! isset($this->isOrQuery[$index])) {
            $andOrs = $this->vars->get([
                'searchParams', $index, 'andOrs'
            ], 'getDef', []);

            $this->isOrQuery[$index] = in_array('or', $andOrs);
        }

        return $this->isOrQuery[$index];
    }

    /*
    ****************************************************************************
    */

    function searchingGroupedFields($index)
    {
        $searchParams = $this->vars->get(['searchParams', $index]);

        if (! isset($searchParams['searchTypes'])) {
            return;
        }

        if (isset($this->searchingGroupedFields[$index])) {
            return $this->searchingGroupedFields[$index];
        }

        $this->searchingGroupedFields[$index] = FALSE;

        foreach ($searchParams['searchTypes'] as $field) {
            if (isset($this->modelFields[$field]['groupedFields'])) {
                // Make note that this search filter has a grouped field
                $this->searchingGroupedFields[$index] =
                    // Make note that this field is a grouped field
                    $this->searchedGroupedFields[$field] = TRUE;
            }
        }

        return $this->searchingGroupedFields[$index];
    }

    /*
    ****************************************************************************
    */

    function setDisplayFilters($filters)
    {
        $this->presetDisplayFilters = $filters;
    }

    /*
    ****************************************************************************
    */

    function useDisplayFilters($model, &$whereClause)
    {
        if (! isset($this->presetDisplayFilters)) {
            return;
        }

        $storedWhereClauses = $presetSearches = [];

        foreach ($this->presetDisplayFilters as $row) {

            $storedWhereClauses[] = $row['clause'];

            $presetSearches[] = [
                'field' => $row['selectField'],
                'value' => $row['selectValue'],
            ];
        }

        $storedWhereClauses[] = $whereClause;

        $finalClause = implode(' AND ', $storedWhereClauses);

        $model->app->jsVars['presetSearches'] = $presetSearches;

        $whereClause = $finalClause ? $finalClause : ' 1 ';
    }

    /*
    ****************************************************************************
    */

    function addClause(&$having, &$where, $info)
    {
        $index = $info['index'];

        if ($this->requiresHavingOnly[$index] || $info['isGroupedField']) {
            $having[] = $info['clause'];
        } else {
            $where[] = $info['clause'];
        }
    }

    /*
    ****************************************************************************
    */

    function convertQuickSearchToFilter()
    {
        $quickSearchValue = getDefault($this->params['search']['value']);

        if (! $quickSearchValue) {
            return;
        }

        $params = [
            'andOrs' => [],
            'searchTypes' => [],
            'searchValues' => [],
        ];

        $first = TRUE;
        foreach ($this->params['columns'] as $index => $colInfo) {

            $searchable = ! isset($colInfo['searchable'])
                        ||  $colInfo['searchable'] == TRUE;

            if (! $searchable) {
                continue;
            }

            $fieldName = $this->fieldKeys[$index];

            $params['andOrs'][] = $first ? 'and' : 'or';
            $first = FALSE;
            $params['searchTypes'][] = $fieldName;
            $params['searchValues'][] = $quickSearchValue;
        }

        $params['quickSearch'] = TRUE;

        $this->vars->push('searchParams', $params);
    }

    /*
    ****************************************************************************
    */

    function mergeParams()
    {
        if (! $this->queryParams['wheres']) {
            return $this->queryParams['havings'];
        }

        if (! $this->queryParams['havings']) {
            return $this->queryParams['wheres'];
        }

        return array_merge($this->queryParams['wheres'], $this->queryParams['havings']);
    }

    /*
    ****************************************************************************
    */

    function output($model, $customStucture=[], $multiSelect=FALSE)
    {
        // AJAX method to change where clause
        $this->overrideWhere ?
            $model->updateWhere($this->overrideWhere, $this->concatWhere) : NULL;

        $startTime = $this->timeOutput ? timeThis() : NULL;

        // Tell Ajax to return query
        $queryString = isset($customStucture['queryString']) ? TRUE : FALSE;

        unset($customStucture['queryString']);

        if (isset($customStucture['compareOperator'])) {
            $compareOperator = $customStucture['compareOperator'];
            $this->vars->set('compareOperator', $compareOperator);
            unset($customStucture['compareOperator']);
        } else {
            $compareOperator = isset($this->app->get['compareOperator'])
                ? $this->app->get['compareOperator'] : NULL;
            $this->vars->set('compareOperator', $compareOperator);
        }

        unset($customStucture['compareOperator']);

        $post = getDefault($this->app->post, []);

        $this->params = array_merge($post, $this->app->get);

        if (isset($this->app->get['searchTypes']) && isset($post['searchTypes'])) {
            // post and get arrays have identical 'andOrs', 'searchTypes' and
            // 'searchValues' keys so after array_merge($post, $this->app->get)
            // fucntion is executed $this->params property misses $post values

            $this->params['andOrs'] =
                    array_merge($this->params['andOrs'], $post['andOrs']);
            $this->params['searchTypes'] =
                    array_merge($this->params['searchTypes'], $post['searchTypes']);
            $this->params['searchValues'] =
                    array_merge($this->params['searchValues'], $post['searchValues']);
        }

        $dtName = $model->dtName = getClass($model);

        $this->getJavacrtiptVariable('searchParams');
        $this->getJavacrtiptVariable('displayParams');

        // Default DT Structure
        $structure = new structure($customStucture);

        $start = getDefault($this->params['start'], 0);
        $defaultLength = $structure->params['iDisplayLength'];
        $length = getDefault($this->params['length'], $defaultLength);

        $passedLimit = 'LIMIT '.intVal($start).', '.intVal($length);

        // Get dynamic fields if needed
        $model->fields ? NULL : $model->fields();

        isset($this->app->post['exportSearcher']) ?
            $model->excludeExportFields() : NULL;

        $this->modelFields = $model->fields;

        $this->fieldKeys = $fieldKeys = array_keys($this->modelFields);

        $selectFields = $this->selectFieldsByFields =
            $model->getSelectFields([], TRUE, TRUE);

        $this->selectFields = array_values($this->selectFieldsByFields);

        $this->selectFieldsByFields = array_combine($fieldKeys, $selectFields);

        $orderDirs = $orderCols = $dtOrder = [];

        // Check if order is DT Format or 1D array
        $defaultOrder = getDefault($structure->params['order'], []);
        $structure->params['order'] =
            getDefault($this->params['order'], $defaultOrder);

        $firstOrder = reset($structure->params['order']);

        if (is_array($firstOrder)) {
            foreach ($structure->params['order'] as $orderInfo) {
                $orderCols[] = $orderInfo['column'];
                $orderDirs[] = $orderInfo['dir'];
            }
        } else {
            foreach ($structure->params['order'] as $column => $key) {
                // If order field is not a column ID, look it up
                $orderCols[] = $column = is_numeric($column)
                    ? $column : array_search($column, $fieldKeys);

                $orderDirs[] = $key;

                // Make Datatables' format
                $dtOrder[] = [$column, $key];
            }
            $structure->params['order'] = $dtOrder;
        }

        $model->fieldsValues = array_values($this->modelFields);

        $this->fields = $fields = $model->getFieldValues(
            [],
            $returnArray = TRUE
        );

        // Get primary key of table
        $primaryKey = getDefault($model->primaryKey, reset($fields));

        // Ordering
        $orderByArray = [];

        foreach ($orderDirs as $key => $orderDir) {
            $orderCol = $orderCols[$key];
            $orderByColumn = getDefault($fields[$orderCol]);
            $orderBy = isset($model->fieldsValues[$orderByColumn]['orderBy'])
                ? $model->fieldsValues[$orderByColumn]['orderBy']
                : $orderByColumn;
            $orderByArray[] = $orderBy.' '.$orderDir;
        }

        $sOrder = $orderByArray
            ? ' ORDER BY '.implode(', ', $orderByArray) : NULL;

        $modelWhere = getDefault($model->where, 1);
        $modelHaving = getDefault($model->having, 1);

        // Field Keys by Name
        $this->fieldKeyNames = array_flip($fieldKeys);

        // This array will be used to store all filter queries

        isset($this->app->get['searchTypes']) && isset($post['searchTypes']) ?
            // use 'andOrs', 'searchTypes' and 'searchValues' clauses passed in
            // both post and get arrays as two separate parenthesized blocks
            // of clauses joined by "AND" conjunction (use different keys in
            // $this->vars->get('searchParams') property)
            $this->addSearchParams($this->app->get)->addSearchParams($post) :

            $this->addSearchParams($this->params);

        $model->app->jsVars['searchParams'] = $this->vars->get('searchParams');
        $model->app->jsVars['displayParams'] = $this->displayParams;

        $this->noSearchOrFilter = getDefault($this->params['andOrs'])
        && getDefault($this->params['searchValues'][0])
            ? TRUE : FALSE;

        // For quick search
        $this->convertQuickSearchToFilter();

        // Add query optimization fields if applicable
        $this->addCustomClauses();

        // Standard DT Search
        $clauses = $this->createSearchClauses();


        // Recieve search from controller
        $this->params['search']['value'] = getDefault(
            $structure->params['oSearch']['sSearch'],
            getDefault($this->params['search']['value'])
        );

        // Recieve search columns from controller
        $this->params['columns'] = getDefault(
            $structure->params['columns'],
            getDefault($this->params['columns'])
        );

        $pagination = getDefault($this->displayParams['pagination'], TRUE);

        // Exports don't have limited search results nor primary keys at the end
        // of each row
        $searchLimit = isset($this->app->post['exportSearcher']) || ! $pagination ?
                NULL : $passedLimit;

        $scope = getDefault($this->app->get['scope']);

        $cartonLabelsSet = isset($this->app->get['cartonLabels']);

        $cartonLabels = getDefault($this->app->get['cartonLabels'], 0);

        $sLimit = $cartonLabelsSet ?
            'LIMIT '.intVal($cartonLabels).', '.$scope : $searchLimit;

        // Carton Label printing can't have a limit
        $sLimit = $cartonLabelsSet && ! $scope ? NULL : $sLimit;

        $primaryKeyForEditables = isset($this->app->post['exportSearcher'])
            ? NULL : ', '.$primaryKey;

        $groupBy = isset($model->groupBy) ? 'GROUP BY '.$model->groupBy : NULL;

        $whereClause = $clauses['wheres']
            ? implode(' AND ', $clauses['wheres'])
            : 1;

        $havingClause = $clauses['havings']
            ? implode(' AND ', $clauses['havings'])
            : 1;

        $this->useDisplayFilters($model, $modelWhere);

        $finalWhereClause = 'WHERE (' . $modelWhere . ') AND ' . $whereClause;
        $finalHavingClause = 'HAVING (' . $modelHaving . ') AND ' . $havingClause;
        $baseWhereClause = ' (' . $modelWhere . ') AND ' . $whereClause;

        // Get data to display
        $selectedFields = $model->getSelectFields();
        $sqlSelect = 'SELECT SQL_CALC_FOUND_ROWS
                     '.$selectedFields.'
                     '.$primaryKeyForEditables;

        $returnClause = $finalWhereClause.'
                      '.$groupBy.'
                      '.$finalHavingClause;

        $sql = $sqlSelect.'
               FROM  '.$model->table.'
                     '.$returnClause.'
                     '.$sOrder.'
                     '.$sLimit;

        $this->outputSQL ? vardump($sql) : NULL;

        // CSV Export here
        $exportType = getDefault($this->app->post['exportType']) == 'csv';
        $outputExcel = getDefault($this->app->post['exportType']) == 'excel';

        // Merge the having and where clauses
        $finalParams = $this->mergeParams();

        $this->outputSQL ? vardump($finalParams) : NULL;

        if ($exportType || $outputExcel) {

            foreach ($model->fields as $field => $info) {

                if (isset($info['ignoreExport']) && $info['ignoreExport']) {

                    // unset fields if ignore search
                    unset($model->fields[$field]);
                    continue;

                }

                $infoSelect = getDefault($info['select']);

                $fieldSelects[] = isset($info['select'])
                    ? $infoSelect . ' AS ' . $field : $field;
            }

            $selectedFields = implode(',', $fieldSelects);

            $exportSql = 'SELECT SQL_CALC_FOUND_ROWS
                                 ' . implode(',', $fieldSelects) . '
                           FROM  ' . $model->table . '
                                 ' . $returnClause . '
                                 ' . $sOrder . '
                                 ' . $sLimit;

            $fileName = getClass($model);

            $this->getColumnHeaders($structure, TRUE);

            $exportParams = [
                'db' => $this->app,
                'sql' => $exportSql,
                'fileName' => $fileName,
                'fieldKeys' => $structure->params['columns'],
                'queryParams' => $finalParams,
                'model' => $model
            ];

            $outputExcel ? excelExporter::queryToExcel($exportParams) :
                csvExporter::queryToCSV($exportParams);
        }

        if ($queryString) {
            return [
                'selectFileds' => $selectedFields,
                'clause' => $returnClause,
                'params' => $finalParams,
                'limit' => $sLimit,
            ];
        }

        // Don't run queries if this is multi selecter. They will be run later
        $structure->params['data'] = $data = $multiSelect ? [] :
            $this->app->ajaxQueryResults($sql, $finalParams);


        if ($outputExcel) {
            $fileName = getClass($model);

            $this->getColumnHeaders($structure);

            if ($outputExcel) {
                excelExporter::ArrayToExcel([
                    'data' => $data,
                    'fileName' => $fileName,
                    'fieldKeys' => $structure->params['columns'],
                ]);
            }
        }


        // Data set length after filtering
        $lengthQuery = 'SELECT FOUND_ROWS() AS iTotalDisplayRecords';

        $emptyFilteredRecords = ['iTotalDisplayRecords' => 0];

        $result = $multiSelect ? $emptyFilteredRecords :
            $this->app->queryResult($lengthQuery);

        $structure->params['recordsFiltered'] = $result['iTotalDisplayRecords'];

        // Use the model's custom info method if it exists
        if (method_exists($model, 'customDTInfo')) {
            $structure->params['custom']
                = $model->customDTInfo($structure->params['data']);
        }

        // Total data set length
        // Get distinct primary ID incase the query has joins

        $countQuery = 'SELECT COUNT(groupedRows) AS iTotalRecords
                    FROM (
                        SELECT COUNT(' . $primaryKey . ' ) AS groupedRows, ' . $selectedFields . '
                        FROM ' . $model->table . '
                             ' . $finalWhereClause . '
                             ' . $groupBy . '
                             ' . $finalHavingClause . '
                    ) AS groupedTable';

        if((isset($model->baseTable) && ! $this->noSearchOrFilter && ! $groupBy)
        || ! $groupBy){
            $table = (isset($model->baseTable) && ! $this->noSearchOrFilter)
                ? $model->baseTable : $model->table;
            $countQuery = $this->getTotalRecords($table, $primaryKey, $baseWhereClause);
        }
        $emptyTotalRecords= ['iTotalRecords' => 0];

        $totalResult = $multiSelect ? $emptyTotalRecords :
            $this->app->queryResult($countQuery, $finalParams);

        $structure->params['recordsTotal'] = $totalResult['iTotalRecords'];
        $structure->params['deferLoading'] = $totalResult['iTotalRecords'];

        $structure->params['draw'] = getDefault($this->params['draw'], 0);

        // re-use param columns for output
        $this->getColumnHeaders($structure);

        // If ajax property isn't set use the method
        $structure->params['ajax'] = isset($model->ajaxSource)
            ? $model->ajaxSource : $model->ajaxSource();

        // Use post ajax if implied
        $structure->ajaxPost();
        unset($structure->params['ajaxPost']);

        // Create the JS for Datatable AJAX
        $this->app->jsVars['dataTables'][$dtName] = $structure->params;

        $this->addJavacrtiptVariable($dtName, 'searchParams');
        $this->addJavacrtiptVariable($dtName, 'displayParams');

        // Create the HTML for the DT
        $this->app->datatablesStructureHTML
            = $this->app->datatablesStructuresHTML[$dtName]
            = $structure::tableHTML($dtName);

        if ($this->timeOutput) {
            $outputTime = timeThis($startTime);
            $message = 'Datatables AJAX output time: '.$outputTime;
            vardump($message);
            vardump($model->getTable());
        }

        return $structure;
    }

    /*
    ****************************************************************************
    */

    function getClauseInfo($params)
    {
        // Quick searches have wildcards on each end
        $fieldName = $params['fieldName'];

        $searchIndex = $params['searchIndex'];

        $exactSearch = isset($this->modelFields[$fieldName]['exactSearch'])
            || $params['compareOperator'] == 'exact'
            || $this->vars->get([
                'searchParams', $searchIndex, 'compareOperator'
            ], 'getDef') == 'exact';

        $wildcard = $exactSearch ? NULL : '%';
        $operator = $exactSearch ? '=' : 'LIKE';

        $quickSearch = getDefault($params['quickSearch']);

        $andOr = getDefault($params['andOr']);

        if (is_null($params['value'])) {
            // "field IS NULL" will be used for instead if "field = ?" notation
            $param = NULL;
            $clause = $params['fieldName'] . ' IS NULL';
        } else {
            // If the serach is for an integer, cast the fields as chars
            $clause = intVal($params['escaped']) ?
                'CONVERT (' . $params['fieldName'] . ', CHAR) ' . $operator . ' ?' :
                $params['fieldName'] . ' ' . $operator . ' ?';

            $param = $quickSearch ? $wildcard . $params['escaped'] . $wildcard :
                $params['escaped'] . $wildcard;
        }

        $clause = $andOr . ' ' . $clause;

        return [
            'param' => $param,
            'clause' => $clause,
            'wildcard' => $wildcard,
        ];
    }

    /*
    ****************************************************************************
    */

    function addParam($params)
    {
        $searchIndex = $params['searchIndex'];

        $clauseName = $this->requiresHavingOnly[$searchIndex] ||
            $params['isGroupedField'] ? 'havings' : 'wheres';

        $this->queryParams[$clauseName][] = $params['value'];
    }

    /*
    ****************************************************************************
    */

    function inHavingClause($params)
    {
        // Figure if a clause or param will be having or where

        $optionName = $params['optionName'];
        $searchIndex = $params['searchIndex'];

        $isOrQuery = $this->isOrQuery($searchIndex);
        $groupedField = isset($this->searchedGroupedFields[$optionName]);
        $queryFieldGrouped = $this->searchingGroupedFields[$searchIndex];

        // Use field select or key name depending on if it is going to be
        // where or having clause
        return ($isOrQuery || $groupedField) && $queryFieldGrouped
            ? TRUE : FALSE;

    }

    /*
    ****************************************************************************
    */

    function fieldSearch($params)
    {
        // Used for all searches now

        $optionName = $params['optionName'];
        $fieldIndex = $params['fieldIndex'];
        $searchIndex = $params['searchIndex'];

        $trimmed = trim($params['value']);
        // escape percent sign (if submitted search value contains "%")
        $params['escaped'] = str_replace('%', '\%', $trimmed);

        $inHavingClause = $this->inHavingClause($params);

        $selectField = isset($this->modelFields[$optionName]['select']);

        // Use field select or key name depending on if it is going to be
        // where or having clause
        $params['fieldName'] = $inHavingClause || ! $selectField ?
            $optionName : $this->modelFields[$optionName]['select'];

        // Set the andOr value
        $params['andOr'] = $this->vars->get([
            'searchParams', $searchIndex, 'andOrs', $fieldIndex
        ]);

        // Get clause, paremeter and possible wildcard symbol
        $results = $this->getClauseInfo($params);

        // Replace the value with the get clause value result
        $params['value'] = $results['param'];

        if (! is_null($params['value'])) {
            // do not NULL to a list of paraters: IS NULL will be used instead
            $this->addParam($params);
        }

        return $results['clause'];
    }

    /*
    ****************************************************************************
    */

    function dateLookUp($params)
    {
        $fieldIndex = $params['fieldIndex'];
        $optionName = $this->getLookUpName($params);

        $field = $params['optionName'];
        $operator = $params['dateCompare'];

        $andOr = getDefault($this->params['andOrs'][$fieldIndex], 'AND');

        // If its a date lookup the format in the model fields
        $format = $this->modelFields[$optionName]['searcherDate'];

        $clause = $format === TRUE ?
            $andOr . ' DATE(' . $field . ') ' . $operator . ' ?' :
            $andOr . ' STR_TO_DATE(' . $field . ', "' . $format . '") '
                . $operator . ' ?';

        $this->addParam($params);

        return $clause;
    }

    /*
    ****************************************************************************
    */

    function addCustomClauses()
    {
        $addedSearches = [];

        $searchParams = $this->vars->get('searchParams');

        foreach ($searchParams as $groupID => $clauseGroups) {
            foreach ($clauseGroups['searchTypes'] as $index => $searchType) {
                $isCustom =
                    getDefault($this->modelFields[$searchType]['customClause']);

                if ($isCustom) {

                    $addedSearches[] = [
                        'groupID' => $groupID,
                        'index' => $index,
                        'values' => customClauses::callback([
                            'index' => $index,
                            'searchType' => $searchType,
                            'clauseGroups' => $clauseGroups,
                        ]),
                    ];
                }
            }
        }

        $addedOffset = 0;

        foreach ($addedSearches as $row) {

            $groupID = $row['groupID'];

            foreach ($row['values'] as $type => $value) {
                $this->vars->splice(['searchParams', $groupID, $type], [
                    'offset' => $row['index'] + $addedOffset + 1,
                    'replacement' => [$value],
                ]);
            }

            // Move up the offset becuase a row has been added
            $addedOffset++;
        }
    }

    /*
    ****************************************************************************
    */

    function createSearchClauses()
    {
        $allWheres = $allHavings = [];

        $searches = json_decode($this->params['searchParams'], TRUE);

        if ($searches) {
            foreach ($searches as $search) {
                $this->vars->push('searchParams', $search);
            }
        }

        $searchParams = $this->vars->get('searchParams');

        foreach ($searchParams as $searchIndex => $filterSet) {

            $this->searchingGroupedFields($searchIndex);

            // Searcher: Custom Search Filters

            $this->isOrQuery($searchIndex);

            $this->requiresHavingOnly[$searchIndex] =
                $this->isOrQuery[$searchIndex] &&
                $this->searchingGroupedFields[$searchIndex];

            $wheres = $havings = [1];

            $quickSearch = isset($filterSet['quickSearch']);

            $searchTypes = getDefault($filterSet['searchTypes'], []);
            $types = array_filter($searchTypes);

            foreach ($types as $fieldIndex => $type) {
                if (! array_key_exists('searchValues', $filterSet)
                 || ! array_key_exists($fieldIndex, $filterSet['searchValues'])) {
                    // isset() does not handle NULL values in arrays
                    continue;
                }

                $value = $filterSet['searchValues'][$fieldIndex];

                $option = parseDateOption($type);

                $dateCompare = $option['dateCompare'] == 'starting'
                    ? '>=' : '<=';

                $optionName = $this->getFieldName(
                    $option['name'],
                    $searchIndex
                );

                $isGroupedField = isset($this->searchedGroupedFields[$optionName]);

                $params = [
                    'value' => $value,
                    'fieldIndex' => $fieldIndex,
                    'optionName' => $optionName,
                    'dateCompare' => $dateCompare,
                    'quickSearch' => $quickSearch,
                    'searchIndex' => $searchIndex,
                    'isGroupedField' => $isGroupedField,
                    'compareOperator' =>
                        $this->vars->get('compareOperator', 'getDef'),
                ];

                // If the input is a date array value, use date look up
                $clause = $option['dateCompare'] ?
                    $this->dateLookUp($params) : $this->fieldSearch($params);

                $this->addClause($havings, $wheres, [
                    'index' => $searchIndex,
                    'clause' => $clause,
                    'isGroupedField' => $isGroupedField,
                ]);
            }

            if (isset($this->app->get['sKey'])) {
                $sOperator = $this->app->get['sOperator'];

                $allWheres[] = isset($this->app->get['sValues'])
                    ? $this->app->get['sKey'] . ' ' . $sOperator
                    . '(' . implode(',', $this->app->get['sValues']) . ')' : 0;
            }

            $allWheres[] = '('.implode(' ', $wheres).')';
            $allHavings[] = '('.implode(' ', $havings).')';
        }

        return [
            'wheres' => $allWheres,
            'havings' => $allHavings,
        ];
    }

    /*
    ****************************************************************************
    */

    function addControllerSearchParams($data)
    {
        $field = $data['field'];
        $values = $data['values'];
        $andOrs = getDefault($data['andOrs']);
        $exact = getDefault($data['exact'], 'exact');

        $count = count($values);

        if (! $andOrs) {
            $andOrs = $count > 1 ? array_fill(0, $count - 1, 'OR') : [];
        }

        $searchTypes = is_array($field) ? $field : array_fill(0, $count, $field);

        array_unshift($andOrs, 'AND');

        $this->vars->push('searchParams', [
            'andOrs' => $andOrs,
            'searchTypes' => $searchTypes,
            'searchValues' => $values,
            'compareOperator' => $exact,
        ]);
    }

    /*
    ****************************************************************************
    */

    function getFieldName($field, $index)
    {
        // Store fieldNames for later reference
        if (isset($this->fieldNames[$field])) {
            return $this->fieldNames[$field];
        }

        $numericIndex = $field === 0 || intVal($field);

        if ($numericIndex) {
            $this->fieldNames[$field] = $this->searchingGroupedFields[$index] ?
                $this->fieldKeys[$field] : $this->selectFields[$field];

            return $this->fieldNames[$field];
        }

        $selectFieldByName = getDefault($this->selectFieldsByFields[$field]);

        $this->fieldNames[$field] = $selectFieldByName
            && ! $this->searchingGroupedFields[$index]
            ? $selectFieldByName
            : $field;

        return $this->fieldNames[$field];
    }

    /*
    ****************************************************************************
    */

    // Making this easier to use on multiple apps
    static function jsonRequest($app)
    {
        isset($app->get['modelName']) or die('Missing Model Name');

        $objectName = 'tables\\'.$app->get['modelName'];

        $object = new $objectName($app);

        $ajax = new ajax($app);

        $app->results = $ajax->output($object);
    }

    /*
    ****************************************************************************
    */

    function getColumnHeaders(&$structure, $isExport=FALSE)
    {
        // Only do this once per datatable
        if ($this->columnHeadersCreated) {
            return;
        }

        $structure->params['columns'] = [];
        foreach ($this->modelFields as $field) {
            $displayTitle = getDefault($field['display']);
            if ($displayTitle) {
                if (! ($isExport && isset($field['ignoreExport'])))
                    $structure->params['columns'][] = ['title' => $displayTitle];
            }

        }

        $this->columnHeadersCreated = TRUE;
    }

    /*
    ****************************************************************************
    */

    static function requestPropsOnly(&$params)
    {
        $params = array_intersect_key($params, self::$ajaxProperties);
    }

    /*
    ****************************************************************************
    */

    public function getTotalRecords($table, $primaryKey, $where=1)
    {
        $countQuery = 'SELECT COUNT(DISTINCT '. $primaryKey .') AS iTotalRecords
                       FROM   '  . $table . '
                       WHERE ' .  $where;

        return $countQuery;
    }

    /*
    ****************************************************************************
    */

    public function addSearchParams($paramArray)
    {
        $this->vars->push('searchParams', [
            'andOrs' => getDefault($paramArray['andOrs'], []),
            'searchTypes' => getDefault($paramArray['searchTypes'], []),
            'searchValues' => getDefault($paramArray['searchValues'], []),
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    public function addParams($paramArray)
    {
        foreach ($paramArray as $key => $value) {
            $this->displayParams[$key] = $value;
        }
    }

    /*
    ****************************************************************************
    */

    function multiSelectTableController($data)
    {
        $app = $data['app'];
        $model = $data['model'];
        $dtOptions = getDefault($data['dtOptions'], []);
        $setMysqlFilter = getDefault($data['setMysqlFilter'], []);
        $searchField = getDefault($data['searchField'], 'v.id');

        if ($setMysqlFilter) {

            $setMysqlFilter['ajax'] = $this;

            $model->setMysqlFilters($setMysqlFilter);
        }

        $this->output($model, $dtOptions, TRUE);

        $searcher = new searcher($model);

        \datatables\vendorMultiselect::vendorMultiselect([
            'object' => $app,
            'searcher' => $searcher,
            'searchField' => $searchField,
        ]);

        new \datatables\editable($model);
    }

    /*
    ****************************************************************************
    */

    function multiSelectTableView($app, $field=NULL, $buttons=NULL)
    {
        echo $app->searcherHTML;

        echo $field ? $app->multiSelectTableStarts[$field] :
            $app->multiSelectTableStart;

        echo $app->datatablesStructureHTML;
        echo $app->searcherExportButton;
        echo $buttons;
        echo $app->multiSelectTableEnd;
    }

    /*
    ****************************************************************************
    */

    function warehouseVendorMultiSelectTableController($data)
    {

        $app = $data['app'];
        $model = $data['model'];
        $dtOptions = getDefault($data['dtOptions'], []);
        $setMysqlFilter = getDefault($data['setMysqlFilter'], []);
        $warehouseField = getDefault($data['warehouseField'], 'displayName');
        $whsType = getDefault($data['whsType'], []);
        $display = getDefault($data['display'], []);

        $modelName = getClass($model);

        if ($setMysqlFilter) {

            $setMysqlFilter['ajax'] = $this;

            $model->setMysqlFilters($setMysqlFilter);
        }

        $this->output($model, $dtOptions, TRUE);

        \datatables\vendorMultiselect::warehouseVendorGroup($model,
            $warehouseField, $whsType, $display);

        $app->jsVars[$modelName] = TRUE;
        $app->jsVars['clientView'] = [
            'warehouseID',
            'vendorID',
        ];

        $app->includeJS['custom/js/common/multiSelectFilter.js'] = TRUE;

        $app->includeCSS['custom/css/common/multiSelectFilter.css'] = TRUE;
    }


    /*
    ****************************************************************************
    */

    function warehouseVendorMultiSelectTableView($app, $id)
    { ?>

        <div id="<?php echo $id; ?>">

        <?php echo $app->searcherHTML; ?>

        <div id="filterContainer">

        <?php foreach ($app->datableFilters as $value) { ?>

            <div class="multiSelectFilter">

                <?php
                echo $app->multiSelectTableStarts[$value];
                echo $app->multiSelectTableEnd; ?>

            </div>

        <?php }

        echo method_exists($app, 'multiSelectCustomDiv') ?
            $app->multiSelectCustomDiv() : NULL;

        echo $app->datatablesStructureHTML;
        echo $app->searcherExportButton; ?>

        <button id="dashView">Full Screen View</button>
        </div>

    <?php }


    /*
    ****************************************************************************
    */

    function getLookUpName($params)
    {
        $optionName = $params['optionName'];

        if (isset($this->modelFields[$optionName])) {
            return $optionName;
        } else {
            foreach ($this->modelFields as $key => $value) {
                if (getDefault($value['select']) == $optionName) {
                    return $key;
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    function addJavacrtiptVariable($dtName, $property)
    {
        if (getDefault($this->vars->values[$property])) {

            $values = $this->vars->get($property);

            // pass displayParams to Javascript
            $this->app->jsVars['dataTables'][$dtName][$property] =
                    json_encode($values);
        }
    }

    /*
    ****************************************************************************
    */

    function setWhereClause()
    {
        $field = $this->app->getVar('field', 'getDef');
        $value = $this->app->getVar('value', 'getDef');

        $this->concatWhere = $this->app->getVar('concat', 'getDef');

        $this->overrideWhere = $field && $value ?
            ' '.$field.' = '.$this->app->quote($value).' ' : NULL;
    }

    /*
    ****************************************************************************
    */

    function getJavacrtiptVariable($property)
    {
        if (! getDefault($this->{$property}) &&
                getDefault($this->params[$property])) {
            // get displayParams from Javascript
            $this->{$property} = is_string($this->params[$property]) ?
                    json_decode($this->params[$property], TRUE) :
                    $this->params[$property];
        }
    }

    /*
    ****************************************************************************
    */

    function getFieldKeyNames()
    {
        return $this->fieldKeyNames;
    }

    /*
    ****************************************************************************
    */

}
