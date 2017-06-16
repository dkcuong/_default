<?php

namespace excel;

class importer extends \importer\importer
{
    public $objPHPExcel = NULL;

    public $badRows = [];

    public $errorOrders = [];

    public $errors = [];

    public $uploadPath = NULL;

    /*
    ************************************************************************
    */

    function loadFile($file='file')
    {
        $this->uploadFile($file);

        $uploadFile = $this->uploadFile;

        $pathInfo = pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);

        if (! in_array($pathInfo, ['xls', 'xlsx'])) {
            return FALSE;
        }

        if (! $this->getCheckFileType($uploadFile)) {
            return FALSE;
        }

        // Read your Excel workbook
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($uploadFile);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $this->objPHPExcel = $objReader->load($uploadFile);
        } catch(Exception $count) {
            die('Error loading file "' . pathinfo($uploadFile, PATHINFO_BASENAME)
                    . '": ' . $count->getMessage());
        }

        return TRUE;
    }

    /*
    ************************************************************************
    */

    function parseToArray($rows)
    {
        $data = [];

        $headerColumns = [];

        foreach ($rows as $key => $row) {
            $getRow = $this->getRow($row);
            $rowData = $this->formatDataFromRow($getRow['rowData'], $key,
                    $headerColumns);
            $rowIndex = $getRow['rowIndex'];

            $data[$rowIndex] = $rowData;
        }
        return $data;
    }

    /*
    ****************************************************************************
    */

    private function formatDataFromRow($dataRow, $index, &$headerColumns)
    {
        $result = [];

        //first row
        if (1 == $index) {
            $headerColumns =
                    \import\inventoryBatch::formatHeaderColumns($dataRow);
            return $dataRow;
        }

        foreach ($dataRow as $key => $row) {
            $rowKey = $headerColumns[$key];
            $result[$rowKey] = \import\inventoryBatch::cleanData($row);
        }

        return $result;
    }

    /*
    ************************************************************************
    */

    function insertFile($file='file')
    {
        if (! $this->loadFile($file)) {
            return $this->errors['wrongType'] = TRUE;
        }

        $this->objPHPExcel or die('No file loaded.');

        if ($this->objPHPExcel->getSheetCount() > 1) {
            $this->errors['multipleSheets'] = TRUE;
            return FALSE;
        }

        $this->model->importData = [];

        foreach ($this->objPHPExcel->getSheet(0)->getRowIterator() as $row) {
            $getRow = $this->getRow($row);

            $rowData = $getRow['rowData'];
            $rowIndex = $getRow['rowIndex'];

            $this->model->importData[$rowIndex] = $rowData;
        }

        $lastColumn = 0;

        foreach ($this->model->importData[1] as $key => $value) {
            if ($value) {
                $lastColumn = $key;
            }
        }

        // truncating array to amount of columns in its 1-st row
        foreach ($this->model->importData as $key => $value) {
            $this->model->importData[$key] = array_splice($value, 0,
                $lastColumn + 1);
        }

        $result = $this->model->insertFile($this->app->post);

        $this->errors = $this->model->errors;

        return $result;
    }

    /*
    ************************************************************************
    */

    function getNextID($tableName)
    {
        $sql = 'SHOW TABLE STATUS LIKE ?';
        $results = $this->app->queryResult($sql, [$tableName]);
        return $results['Auto_increment'];
    }

    /*
    ************************************************************************
    */

    function getRow($row)
    {
        $cellIterator = $row->getCellIterator();

        $cellIterator->setIterateOnlyExistingCells(false);
        $rowIndex = $row->getRowIndex();

        $rowData = [];

        foreach ($cellIterator as $cellID => $cell) {

            $value = isset($this->dateKeys[$cellID])
                ? $cell->getFormattedValue() : $cell->getValue();

            $rowData[] = trim($value);
        }

        $return = [
            'rowData' => $rowData,
            'rowIndex' => $rowIndex,
        ];

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getCheckFileType($file)
    {
        $types = [
            'Excel2007',
            'Excel5'
        ];

        foreach ($types as $type) {
            $reader = \PHPExcel_IOFactory::createReader($type);
            if ($reader->canRead($file)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function getRowsInventoryBatchFile()
    {
        $this->loadFile();
        $rows = $this->objPHPExcel->getSheet(0)->getRowIterator();

        return $rows;
    }

    /*
    ****************************************************************************
    */

    static function validateFileColumns($reader, $rows, $allowColumns)
    {
        foreach ($rows as $row) {
            $getRow = $reader->getRow($row);
            $rowData = $getRow['rowData'];

            $nHeaderColumns = count($allowColumns);

            for ($i = 0; $i < $nHeaderColumns; $i++) {
                $colValue = strtolower($rowData[$i]);

                if (! in_array($colValue, $allowColumns)) {
                    return FALSE;
                }
            }

            return TRUE;
        }
    }

}
