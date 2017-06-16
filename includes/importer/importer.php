<?php

namespace importer;

class importer
{
    public $errors = [];

    public $badRows = [];

    public $uploadFile;

    public $uploadPath;

    /*
    ************************************************************************
    */

    function __construct($app, $model=FALSE)
    {
        $this->app = $app;
        // If a floating model is used for methods, otherwise use request object
        $this->model = $model ? $model : $app;

        $this->defineImportProperties();
    }

    /*
    ************************************************************************
    */

    function uploadFile($file)
    {
        $this->uploadFile = $this->uploadPath . '/' . $_FILES[$file]['name'];

        return move_uploaded_file(
            $_FILES[$file]['tmp_name'],
            $this->uploadFile
        );
    }

    /*
    ************************************************************************
    */

    static function indexArrayFill($data)
    {
        $model = $data['model'];
        $display = $data['display'];
        $key = $data['key'];

        if (self::checkColumnNames($data)) {
            return;
        }

        $fields = self::caseInsensitiveKeys($model);

        $field = $fields[$display];

        $model->inputNames[] = $display;
        $model->duplicateNamesCheck[] = $display;

        if (getDefault($field['required'])) {
            $model->reqIndexes[$key] = TRUE;
        }

        if (getDefault($field['isBoolean'])) {
            $model->booleanIndexes[$key] = TRUE;
        }

        if (getDefault($field['isPositive'])) {
            $model->positiveIndexes[$key] = TRUE;
        }

        if (getDefault($field['maxValue'])) {
            $model->exceedIndexes[$key] = TRUE;
        }

        if (getDefault($field['validation'])) {
            $model->validationIndexes[$key] = $field['validation'];
        }

        if (getDefault($field['lengthLimit'])) {
            $model->lenLimIndexes[$key] = $field['lengthLimit'];
        }
    }

    /*
    ************************************************************************
    */

    static function checkColumnNames($data)
    {
        $model = $data['model'];
        $display = $data['display'];
        $key = $data['key'];

        if (! $display) {

            $model->errors['emptyCaptions'][$key + 1] = TRUE;

            return TRUE;
        }

        if (in_array($display, $model->duplicateNamesCheck)) {

            $model->errors['duplicateColumns'][$display] = TRUE;

            $model->duplicateNamesCheck[] = $display;

            return TRUE;
        }

        $model->duplicateNamesCheck[] = $display;

        $fields = self::caseInsensitiveKeys($model);

        if (! isset($fields[$display])) {

            $model->errors['invalidColumns'][$display][] = TRUE;

            return TRUE;
        }

        if (isset($fields[$display]['ignore'])
         && ! isset($model->costsColumns[$display])) {

            $model->ignoredIndexes[$key] = TRUE;

            return TRUE;
        }
    }

    /*
    ************************************************************************
    */

    static function checkTableErrors($model)
    {
        $importFields = self::caseInsensitiveKeys($model);

        $fieldsSubmited = \array_flip($model->inputNames);

        // checking whether scan_seldat_order_number column was submitted
        if (isset($fieldsSubmited['scan_seldat_order_number'])) {
            $model->errors['invalidColumns']['scan_seldat_order_number'] = '';
        }

        // checking whether all necessary columns are submitted
        foreach ($importFields as $field => $value) {
            if (isset($value['required'])) {
                $requiredFields[$field] = '';
            }
        }

        $missingColumns = \array_diff_key($requiredFields, $fieldsSubmited);

        if ($missingColumns) {
            $model->errors['missingColumns'] = $missingColumns;
        }

        // Confirm all columns exist
        $invalidColumns = \array_diff_key($fieldsSubmited, $importFields);

        if ($invalidColumns) {
            $model->errors['invalidColumns'] = $invalidColumns;
        }
    }

    /*
    ************************************************************************
    */

    static function checkCellErrors($data)
    {
        $model = $data['model'];
        $rowData = $data['rowData'];
        $rowIndex = $data['rowIndex'];

        $count = 0;

        $importFields = property_exists($model, 'importFields') ?
                $model->importFields : $model->fields;

        $keys = array_keys($rowData);

        foreach ($keys as $key) {
            if (! isset($model->inputNames[$key])) {
                if ($rowData[$key]) {
                    $model->errors['extraColumn'][$rowIndex][] = $key + 1;
                }

                $model->badRows[] = $rowData;

                unset($rowData[$key]);
            }

            if (isset($model->ignoredIndexes[$key])) {

                unset($rowData[$key]);

                continue;
            }

            if (! isset($model->inputNames[$key])) {
                continue;
            }

             //replace the non UTF character
            $input = self::validConvert($rowData[$key]);

            $field = $model->inputNames[$key];
            $display = $importFields[$field]['display'];

            if ($input && \format\nonUTF::check($input)) {

                $model->errors['nonUTFReqs'][$rowIndex][] = $display;

                $model->badRows[] = $rowData;

                $rowData[$key] = NULL;
            }

            if (isset($model->positiveIndexes[$key])
             && (! is_numeric($input) || $input <= 0)) {

                $model->errors['nonPositiveReqs'][$rowIndex][] = $display;

                $model->badRows[] = $rowData;

                $rowData[$key] = NULL;
            }

            if (isset($model->exceedIndexes[$key])
             && (! is_numeric($input) || $input > $model->exceedIndexes[$key])) {

                $model->errors['exceedReq'][$rowIndex][] = $display . ' (length'
                        . ' max value - ' . $model->exceedIndexes[$key]
                        . ' submitted value - ' . $input . ')';

                $model->badRows[] = $rowData;
            }

            if (isset($model->lenLimIndexes[$key])
             && strlen($input) > $model->lenLimIndexes[$key]) {

                $model->errors['lengthLimit'][$rowIndex][] = $display
                        . ' (length limit - ' . $model->lenLimIndexes[$key]
                        . ' submitted length - ' . strlen($input) . ')';

                $model->badRows[] = $rowData;

                $rowData[$key] = substr($input, 0, $model->lenLimIndexes[$key]);
            }

            if (isset($model->validationIndexes[$key])) {

                $params = $data;

                $params['field'] = $field;
                $params['key'] = $key;
                $params['count'] = $count;
                $params['input'] = $input;
                $params['display'] = $display;

                $count = self::validateInput($params);
            }

            if (isset($model->reqIndexes[$key]) && ! $input) {

                $model->errors['missingReqs'][$rowIndex][] = $display;

                $model->badRows[] = $rowData;
            }

            if (isset($model->dateKeys[$key])) {

                $params = $data;

                $params['key'] = $key;
                $params['display'] = $display;

                $rowData = self::checkDate($params);
            }
        }

        foreach ($rowData as $key => $row) {
            $rowData[$key] = self::validConvert($row);
        }

        return [
            'errors' => getDefault($model->errors),
            'rowData' => $rowData,
        ];
    }

    /*
    ****************************************************************************
    */

    static function validateInput($data)
    {
        $model = $data['model'];
        $field = $data['field'];
        $key = $data['key'];
        $count = $data['count'];
        $input = $data['input'];
        $display = $data['display'];
        $rowData = $data['rowData'];
        $rowIndex = $data['rowIndex'];

        if (isset($model->fields[$field]['validationArray'])) {

            $function = [
                $model,
                $model->validationIndexes[$key],
            ];

            $params = $data;
            // do not need to send an object to itself
            unset($params['model']);

            $result = call_user_func($function, $params);

            if ($result) {

                $newLine = $count++ ? '<br>' : NULL;

                $model->errors['invalidReqs'][$rowIndex][] =
                        $newLine . $display . $result;

                $model->badRows[] = $rowData;
            }
        } elseif (! call_user_func($model->validationIndexes[$key], $input)) {

            $model->errors['invalidReqs'][$rowIndex][] = $display;

            $model->badRows[] = $rowData;
        }

        return $count;
    }

    /*
    ****************************************************************************
    */

    static function checkDate($data)
    {
        $key = $data['key'];
        $display = $data['display'];
        $rowData = $data['rowData'];
        $rowIndex = $data['rowIndex'];

        $date = $rowData[$key];

        $year = (int)substr($date, 0, 4);
        $month = (int)substr($date, 5, 2);
        $day = (int)substr($date, 8, 2);
        $hours = (int)substr($date, 11, 2);
        $minutes = (int)substr($date, 14, 2);

        if (checkdate($month, $day , $year) && $hours >= 0 && $hours < 24
         && $minutes >= 0 && $minutes < 60) {

            $orderDate = strtotime($year . '-' . $month . '-' . $day . ' '
                    . $hours . ':' . $minutes . ':00');

            $rowData[$key] = date('Y-m-d H:i:s', $orderDate);
        } else {

            $model->errors['badDate'][$rowIndex][] = $display;

            $model->badRows[] = $rowData;
        }

        return $rowData;
    }

    /*
    ****************************************************************************
    */

    function errorDescription($data)
    {
        $captionDescr = $data['captionSuffix'];

        $delimiter = isset($data['delimiter']) ? $data['delimiter'] : ', ';

        $descriptionCaption = getDefault($data['descriptionCaption'], NULL);
        $descriptionValues = getDefault($data['descriptionValues'], []);

        if (isset($data['rowSuffix'])) {
            $rowDescr = $data['rowSuffix'];
        } elseif (substr($captionDescr, 0, 4) == 'are ') {
            $rowDescr = 'is' . substr($captionDescr, 3);
        } elseif (substr($captionDescr, 0, 5) == 'have ') {
            $rowDescr = 'has' . substr($captionDescr, 4);
        } else {
            $rowDescr = $captionDescr;
        }

        $caption = getDefault($data['caption'], 'row');

        ob_start();

        ?>

        <div class="failedMessage blockDisplay">
            <strong>The following import <?php echo $caption; ?>s <?php echo
                $captionDescr; ?></strong><br> <?php

        $count = 0;

        foreach ($data['errorArray'] as $key => $req) {

            echo ! $count++ || $delimiter == ', ' ? NULL : $delimiter;

            $rowDescr = $rowDescr ? ' ' . $rowDescr : ':';

            echo 'Spreadsheet ' . $caption . ' ' . $key . $rowDescr . ' '
                    . implode($delimiter, $req);

            $description = getDefault($descriptionValues[$key]);

            $text = ! $description ? NULL :
                    '<br>' . $descriptionCaption . implode(',', $description);

            echo $text . '<br>';
        } ?>

        </div>

        <?php

        $this->errors[] = TRUE;

        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function errorFile($descriptions)
    {
        ob_start();

        ?>

        <div class="failedMessage blockDisplay">
            <strong>
            You have submitted a file <?php echo $descriptions['captionSuffix']; ?>
            </strong>
            <br>

        <?php

        if (isset($descriptions['errorArray'])) {
            echo implode('<br>', array_keys($descriptions['errorArray']));
        } ?>

        </div>

        <?php

        $this->errors[] = TRUE;

        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function displayImportErrors()
    {
        $errors = $this->errors;

        $errMsg = NULL;

        if (isset($errors['multipleSheets'])) {
            $errMsg .= $this->errorFile([
                'captionSuffix' => 'with multiple sheets',
            ]);
        } else if (isset($errors['wrongType'])) {
            $errMsg .= $this->errorFile([
                'captionSuffix' => 'that is not a valid Excel file',
            ]);
        } else {
            foreach ($this->errorDescription as $key => $values) {
                if (isset($errors[$key])) {

                    $values['descriptionValues'] =
                            getDefault($errors[$key]['description'], []);

                    unset($errors[$key]['description']);

                    $values['errorArray'] = $errors[$key];

                    $errMsg .= $this->errorDescription($values);
                }
            }

            foreach ($this->errorFile as $key => $values) {
                if (isset($errors[$key]) || $key == 'badRows') {

                    if ($key != 'badRows') {
                        $values['errorArray'] = $errors[$key];
                    }

                    $errMsg .= $this->errorFile($values);
                }
            }
        }

        return $errMsg;
    }

    /*
    ****************************************************************************
    */

    function importError()
    {
        if (! getDefault($this->errors)) {

            $this->model->importSuccess();

            return FALSE;
        }

        $this->errorDescription = $this->model->errorDescription;
        $this->errorFile = $this->model->errorFile;

        $errMsg = $this->displayImportErrors();

        echo $errMsg;

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function defineImportProperties()
    {
        if (! isset($this->model->fields)) {
            return;
        }

        $this->model->errors = [];

        $this->model->inputNames = [];

        $this->model->duplicateNamesCheck = [];

        $this->model->errorDescription['extraColumn'] = [
            'captionSuffix' => 'have empty column caption(s):'
        ];

        $this->model->errorDescription['nonUTFReqs'] = [
            'captionSuffix' => 'have non UTF character(s):'
        ];

        $this->model->errorFile['emptyCaptions'] = [
            'captionSuffix' => 'with empty captions for the following columns:'
        ];

        $this->model->errorFile['missingColumns'] = [
            'captionSuffix' => 'with missing columns:'
        ];

        $this->model->errorFile['duplicateColumns'] = [
            'captionSuffix' => 'with duplicate columns:'
        ];

        $this->model->errorFile['invalidColumns'] = [
            'captionSuffix' => 'with invalid columns:'
        ];

        foreach ($this->model->fields as $values) {
            if (isset($values['ignore'])) {
                $this->model->ignoredIndexes = [];
            }

            if (isset($values['lengthLimit'])) {

                $this->model->lenLimIndexes = [];

                $this->model->errorDescription['lengthLimit'] = [
                    'captionSuffix' => 'have excessive width values:'
                ];
            }

            if (isset($values['validation'])) {

                $this->model->validationIndexes = [];

                $this->model->errorDescription['invalidReqs'] = [
                    'captionSuffix' => 'have invalid values:',
                    'delimiter' => '<br>'
                ];
            }

            if (isset($values['missingReqs'])) {

                $this->model->reqIndexes = [];

                $this->model->errorDescription['missingReqs'] = [
                    'captionSuffix' => 'are missing required values:'
                ];
            }

            if (isset($values['isBoolean'])) {
                $this->model->booleanIndexes = [];
            }

            if (isset($values['nonPositiveReqs'])) {

                $this->model->positiveIndexes = [];

                $this->model->errorDescription['nonPositiveReqs'] = [
                    'captionSuffix' => 'have nonpositive values:',
                    'rowSuffix' => 'value must be positive:'
                ];
            }

            if (isset($values['maxValue'])) {

                $this->model->exceedIndexes = [];

                $this->model->errorDescription['maxValue'] = [
                    'captionSuffix' => 'have excessive values:'
                ];
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function validConvert($value)
    {
        $trimmedInput = $value;

        if ($value) {
           $result = str_replace('‐', '-', $value);
           $nonBreakInput = trim($result, "\xC2\xA0");
           $trimmedInput = trim($nonBreakInput);
        }
        return $trimmedInput;
    }

    /*
    ****************************************************************************
    */

    static function caseInsensitiveKeys($model)
    {
        $importFields = property_exists($model, 'importFields') ?
                $model->importFields : $model->fields;

        $fields = [];

        foreach ($importFields as $key => $values) {

            $caseInsensitiveKey = strtolower($key);

            $valueKey = str_replace([' ', '/'], '_', $caseInsensitiveKey);

            $fields[$valueKey] = $values;
        }

        return $fields;
    }

    /*
    ****************************************************************************
    */
}
