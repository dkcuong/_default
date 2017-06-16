<?php

namespace datatables;

use \excel\exporter;

class searcher
{

    static public $andOr = [
        'and' => 'And',
        'or' => 'Or',
    ];

    public $dropdownsIndexes = [
        'firstDropdown',
        'secondDropdown',
        'thirdDropdown',
        'fourthDropdown',
    ];

    public $dropdownValues = [];

    /*
    ****************************************************************************
    */

    function __construct($model)
    {
        $model->app
        or die('This table model does not have an application DB instance.');
        $this->app = $model->app;
        $this->model = $model;

        $this->app->jsVars['searcher']['modelName'] = $className
            = $this->className = getClass($this->model);

        // Export search if posted was requested
        $this->export();

        $this->setDropdown();

        // Add required searcher JS
        $this->app->includeJS['js/datatables/searcher.js'] = TRUE;
        $this->app->includeCSS['css/datatables/searcher.css'] = TRUE;

        // Add required URLs to jsVars
        $this->app->jsVars['urls']['filter']
            = jsonLink('filterSearcher', ['modelName' => $model->ajaxModel]);
        $this->app->jsVars['urls']['searcher']
            = jsonLink('datatables', ['modelName' => $model->ajaxModel]);

        // Add the models fields to JS Vars
        $this->app->jsVars['searcherFields'] = $model->fields;

        // Create searcher HTML
        $this->app->searcherHTML = $this->html();

        $this->prePopDropDowns();

        $this->app->searcherExportButton = self::getExportButton();

        $this->app->multiSelectTableStarts =
        $model->app->jsVars['searcher']['multiID'] =
        $model->app->jsVars['searcher']['preSelects'] = [];

        $this->app->multiSelectTableStart =
        $this->app->multiSelectTableEnd = NULL;
    }

    /*
    ****************************************************************************
    */

    function setDropdown()
    {
        // Set searcher dropdown values with get params
        if (isset($this->app->get['firstDropdown'])) {
            foreach ($this->dropdownsIndexes as $index) {
                $field = getDefault($this->app->get[$index], FALSE);
                $this->app->jsVars['searcher']['dropdowns'][$index] = $field;
            }
        }
    }

    /*
    ****************************************************************************
    */

    function export()
    {
        if (isset($this->post['cartonLabels'])
        || isset($this->app->get['cartonLabels'])
        ) {
            return;
        }

        $exportType = getDefault($this->app->post['exportType']);

        $className = $this->className;
        $dtInfo = $this->app->jsVars['dataTables'][$className];

        switch ($exportType) {
            case 'csv':
                \csv\export::downloadHeader($this->className.'_csv_export');
                foreach ($dtInfo['data'] as $row) {
                    foreach ($row as $key => $value) {
                        if (intval($value) > 0 &&
                            (strlen($value) > 11 || ! substr(trim($value), 0, 1))
                        ) {
                            $row[$key] = $value.' ';
                        }
                    }
                    echo implode(',', $row)."\n";
                }
                die;
                break;

            case 'excel':
                exporter::header($this->className.'_excel_export');
                structure::exportTable($dtInfo);
                die;
                break;
        }
    }

    /*
    ****************************************************************************
    */

    function html()
    {
        $model = $this->model;

        // Get post vars
        $post = $model->app->post;

        // If there is no andOr param set and as default
        $post['andOrs'] = isset($model->app->post['andOrs'])
            ? $model->app->post['andOrs'] : ['and'];

        ob_start();

        ?>
        <div id="searcher">
        <form id="searchForm" name="searcher" method="post">
        <table><tr>
            <?php foreach ($post['andOrs'] as $index => $andOrs) {
                // Only hide the remove button and and/or if first clause
                $hide = $index ? NULL : ' style="display: none;"';
                ?>
            <td class="clauses">
            <select name="andOrs[]" class="andOrs"<?php echo $hide; ?>>
            <?php foreach (self::$andOr as $value => $name) {
                $selected = $andOrs == $value ? ' selected' : NULL; ?>
                <option value="<?php echo $value; ?>"<?php echo $selected; ?>>
                    <?php echo $name; ?></option>
            <?php } ?>
            </select>
            <select name="searchTypes[]" class="searchTypes">
                <option value="0">Select Field</option>
                <?php
                foreach ($model->fields as $value => $info) {
                    if (isset($info['searcherDate'])) {
                        $this->displayOption($post,
                            $info,
                            $index,
                            $value,
                            'starting'
                        );
                        $this->displayOption($post,
                            $info,
                            $index,
                            $value,
                            'ending'
                        );
                    } else {
                        $this->displayOption($post, $info, $index, $value);
                    }
                } ?>
            </select>
            <input name="searchValues[]" class="searchValues" value="<?php echo getDefault($post['searchValues'][$index]); ?>"><br>
            <input class="removeButtons" type="button" value="Remove"<?php echo $hide; ?>>
            </td>
            <?php }
            $model->app->jsVars['dropdownValues'] = $this->dropdownValues;
            ?>
            <td><input id="addClause" type="button" value="Add Clause"><br>
                <input id="submitSearch" type="button" value="Search"></td>
        </tr></table>
        </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function displayOption($post, $info, $index, $value, $dateDisplay=NULL)
    {
        // Don't skip if its a custom searcher field
        if (! isset($info['ignoreSearch']) || isset($info['searcherDD'])
        ||  isset($info['searcherDate'])
        ) {
            $selected = getDefault($post['searchTypes'][$index]) == $value
                ? ' selected' : NULL;
            $dateArray = $dateDisplay ? '[' . $dateDisplay . ']' : NULL;
            $dateData = $dateDisplay ? 'data-date="isDate"'
                : NULL;
            $finalDisplay = $info['display'] . ' ' . ucFirst($dateDisplay); ?>

            <option value="<?php echo $value.$dateArray; ?>"
            <?php echo $dateData.$selected; ?>>
                <?php echo $finalDisplay; ?>
            </option><?php

             // Create an array of display names to reference js actual field names
            $this->dropdownValues[$finalDisplay] = $value.$dateArray;
        }
    }

    /*
    ****************************************************************************
    */

    function prePopDropDowns()
    {
        foreach ($this->model->fields as $field => $info) {
            if (isset($info['searcherDD'])) {

                $className = 'tables\\' . $info['searcherDD'];

                // Dynamic instantiation.. Have to figure out a better way later
                $fieldClass = new $className($this->app);

                $classTable = $fieldClass->table;

                $select = isset($info['select']) ? $info['select'] : $field;

                $select = isset($info['ddField']) ? $info['ddField'] : $select;

                $hintField = isset($info['hintField']) ? $info['hintField'] : '';

                $params = [
                    'classTable' => $classTable,
                    'select' => $select,
                    'hintField' => $hintField,
                    'fieldClass' => $fieldClass
                ];

                $this->app->jsVars['searcherDDs'][$field] =
                    $this->getOptions($params);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function getOptions($params)
    {
        $classTable = $params['classTable'];
        $select = $params['select'];
        $hintField = $params['hintField'];
        $fieldClass = $params['fieldClass'];

        $clause = getDefault($fieldClass->where, 1);
        $dropdownWhere = getDefault($fieldClass->dropdownWhere);

        if ($dropdownWhere) {
            $clause = $dropdownWhere;
        }

        $group = getDefault($fieldClass->groupby, 1);
        $groupby = $group ? $group : $select;

        if(! $hintField) {

            $sql = 'SELECT   ' . $select . ' AS optionName';
        }
        // $hintField is display as attribute title in <option>
        // example <option title="hintField">some text</option>
        else {
            $sql = 'SELECT   ' . $select . ' AS optionName, ' .
                    $hintField . ' AS titleName';
        }
        $sql .= '   FROM     ' . $classTable . '
                    WHERE    ' . $clause . '
                    GROUP BY ' . $groupby;

        return $this->app->queryResults($sql);
    }

    /*
    ****************************************************************************
    */

    static function getExportButton()
    {
        ob_start(); ?>
        <button class="exportSearcher" id="csv">Export Results to CSV</button>
        <button class="exportSearcher" id="excel">Export Results to Excel</button>
        <?php return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function createMultiSelectTable($params)
    {
        $triggerSent = isset($params['trigger']);

        $app = $this->app;
        $title = $params['title'];
        $idName = $params['idName'];
        $subject = $params['subject'];
        $searchField = $params['searchField'];
        $selectedValues = $params['selected'];
        $size = getDefault($params['size'], 26);
        $trigger = getDefault($params['trigger']);
        $isClient = getDefault($params['isClient']);
        $fieldName = getDefault($params['fieldName'], 'displayName');
        $radio = getDefault($params['radio']);
        $selectRadio = getDefault($params['selectRadio']);
        $radioValue = getDefault($params['radioValue']);

        if ($triggerSent && ! $trigger) {
            return;
        }

        $app->jsVars['searcher']['multiID'][] = $idName;

        $app->jsVars['searcher']['preSelects'][$idName] =
            array_keys($selectedValues);

        // Clients can only see their info

        ob_start(); ?>

        <table id="searcherTable">
            <tr><td id="searcherTableTitle"><?php

        $disabled = NULL;

        if ($radio) {

            $checked = $selectRadio ? 'checked' : NULL;
            $value = $radioValue ? 'value="' . $radioValue . '"' : NULL;
            $disabled = $selectRadio ? NULL : 'disabled'; ?>

                <input type="radio" class="multiSelectRadio"
                       name="<?php echo $radio; ?>" <?php echo $value . ' ' . $checked; ?>>

        <?php }

                    echo $title; ?>:<br>
                <span id="multiSearcherDisplay">
                <select size="<?php echo $size; ?>" name="<?php echo $idName; ?>[]"
                        id="<?php echo $idName; ?>" multiple
                        data-search-field="<?php echo $searchField; ?>"
                        <?php echo $disabled; ?>>

            <?php foreach ($subject as $id => $row) {

                $clientsInfo = isset($selectedValues[$id]);

                if ($isClient && ! $clientsInfo) {
                    continue;
                }

                $selected = $clientsInfo ? ' selected' : NULL; ?>

                    <option data-subject-id="<?php echo $id; ?>"
                            <?php echo $selected; ?>>
                            <?php echo $row[$fieldName]; ?></option>

            <?php } ?>

                </select>
                </span><br>
                <td>

        <?php

        $app->multiSelectTableStarts[$idName] = $app->multiSelectTableStart =
            ob_get_clean();

        ob_start(); ?>

                </td>
            </tr>
        </table>

        <?php
        $app->multiSelectTableEnd = ob_get_clean();
    }

    /*
    ****************************************************************************
    */

}
