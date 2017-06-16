<?php

namespace datatables;

class editable
{
    public $options = [];
    public $dropdowns = [];

    /*
    ****************************************************************************
    */

    function __construct($model)
    {
        $this->app = $model->app;

        if (! $this->activateEdit()) {
            // Set default values for editable add row HTML
            $this->app->searcherAddRowButton = '';
            $this->app->searcherAddRowFormHTML  = '';
            return;
        }

        // Add required Editables JS
        $this->app->includeJS['js/datatables/editables.js'] = TRUE;

        $this->model = $model;

        $this->createOptions();

        $dtName = $this->model->dtName;

        $updateURL = jsonLink('dtEditable', ['modelName' => $model->ajaxModel]);

        $this->app->jsVars['editables'][$dtName] = [
            'sUpdateURL' => $updateURL,
            'aoColumns' => $this->options,
        ];

        foreach (array_values($model->fields) as $index => $info) {
            if (! isset($info['noEdit'])) {
                $this->app->jsVars['dataTables'][$dtName]['columns'][$index]['class'] = 'canEdit';
            }
        }

        return $this;
    }

    /*
    ****************************************************************************
    */

    function activateEdit()
    {
        $app = $this->app;

        $editable = isset($app->get['editable']);

        $admin = \access::required([
            'app' => $app,
            'terminal' => FALSE,
            'accessLevels' => 'admin'
        ]);

        return $admin && $editable ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    function createOptions()
    {
        $index = 0;
        foreach ($this->model->fields as $field => $info) {
            $option = [];
            if (isset($info['noEdit'])) {
                $option = NULL;
            } else if (isset($info['searcherDD'])) {
                $dropdown = $this->getDropdown($field, $info);
                $option = [
                    'type' => 'select',
                    'onblur' => 'submit',
                    'data' => $dropdown
                ];
            } else if (isset($info['searcherDate'])) {
                $option = [
                    'type' => 'datepicker',
                ];
            } else if (isset($info['autocomplete'])) {
                $this->app->jsVars['urls']['autocomplete'][$index]
                    = $this->autocompleteLink($info);

                $option = [
                    'type' => 'autocompleteData',
                ];
            }else if (isset($info['display'])) {
                $option = [];
            }

            $this->options[] = $option;

            $index++;
        }
    }

    /*
    ****************************************************************************
    */

    function getDropdown($field, $info)
    {
        // If searcherDD is set to TRUE use searcherDD values
        if ($info['searcherDD'] === TRUE) {
            return $this->dropdowns[$field] = $this->app->jsVars['searcherDDs'][$field];
        }

        $modelName = 'tables\\'.$info['searcherDD'];
        $ddField = getDefault($info['ddField'], 'displayName');

        if (isset($this->dropdowns[$field])) {
            return $this->dropdowns[$field];
        }

        $model = new $modelName($this->app);

        $this->dropdowns[$field] = $model->getDropdown($ddField);

        if (getDefault($info['canEmptyFieldValue'])) {
            $this->dropdowns[$field][0] = 'Empty Value';
        }

        return $this->dropdowns[$field];
    }

    /*
    ****************************************************************************
    */

    function canAddRows()
    {
        $this->app->includeCSS['css/datatables/editable.css'] = TRUE;

        if (! $this->activateEdit()) {
            return;
        }

        $addURL = jsonLink('dtEditableAdd', [
            'modelName' => $this->model->ajaxModel
        ]);

        $dtName = $this->model->dtName;

        $this->app->jsVars['editables'][$dtName]['sAddURL'] = $addURL;

        $single = $this->model->displaySingle;

        ob_start(); ?>

        <form id="formAddNewRow" action="#"
              title="Add New <?php echo $single; ?>">
        <div id="addRowNotice"></div>
        <table>

        <?php

        $rel = 0;

        foreach ($this->model->fields as $field => $info) {
            if (isset($info['insertDefaultValue'])) { ?>

                <input type="hidden" rel="<?php echo $rel++; ?>">

                <?php
                continue;
            }

            if (isset($info['noEdit'])) { ?>

                <input type="hidden" rel="<?php echo $rel++; ?>"
                       name="<?php echo $field; ?>" value="0">

                <?php
                continue;
            }

            $rel = $this->addRowInput($info, $field, $rel);

            if (property_exists($this->model, 'hiddenFields')) {
                $rel = $this->addHiddenFields($field, $rel);
            }
        } ?>

        </table>
        </form>

        <?php
        $this->app->searcherAddRowFormHTML = ob_get_clean();

        ob_start(); ?>

        <button id="btnAddNewRow" class="add_row">
            Add <?php echo $single; ?>
        </button>

        <?php $this->app->searcherAddRowButton = ob_get_clean();
    }

    /*
    ****************************************************************************
    */
    function addRowInput($info, $field, $rel)
    { ?>

        <tr>
            <td class="noWrap"><?php echo $info['display']; ?></td>

            <?php if (isset($info['searcherDD'])) {
                $dropdown = $this->getDropdown($field, $info); ?>

            <td>
                <select rel="<?php echo $rel++; ?>"
                        name="<?php echo $field; ?>">

                <?php foreach ($dropdown as $value => $display) { ?>

                    <option value="<?php echo $value; ?>">
                        <?php echo $display; ?>
                    </option>

                <?php } ?>

                </select>
            </td>

            <?php } elseif (getDefault($info['searcherDate'])) {

                $class = 'datepicker hasDatepicker';

                $class .= isset($info['optional']) ? NULL :
                        ' required'; ?>

            <td>
                <input type="text" class="<?php echo $class; ?>"
                       id="<?php echo $field; ?>" name="<?php echo $field; ?>"
                       maxlength="10" autocomplete="off" style="width: 100px;"
                       rel="<?php echo $rel++; ?>">
            </td>

            <?php } else {

                $required = isset($info['optional']) ? NULL :
                        'class="required"';

                $autocomplete = isset($info['autocomplete']) ?
                        'placeholder="(autocomplete)"' : NULL; ?>

            <td>
                <input <?php echo $required; ?> type="text"
                       id="<?php echo $field; ?>"  name="<?php echo $field; ?>"
                       rel="<?php echo $rel++; ?>" <?php echo $autocomplete; ?>>
            </td>

            <?php } ?>

        </tr>

        <?php

        return $rel;
    }

    /*
    ****************************************************************************
    */

    function addHiddenFields($field, $rel)
    {
        foreach ($this->model->hiddenFields as $key => $values) {
            if ($field == $values['after']) {
                return $this->addRowInput($values, $key, $rel);
            }
        }

        return $rel;
    }

    /*
    ****************************************************************************
    */

    function autocompleteLink($info)
    {
        $modelName = $info['autocomplete'];
        $autocompleteSelect = getDefault($info['autocompleteSelect']);

        return jsonLink('autocomplete',
            ['modelName' => $modelName, 'field' => $autocompleteSelect]
        );
    }
    /*
    ****************************************************************************
    */

}
