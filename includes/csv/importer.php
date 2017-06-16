<?php

namespace csv;

class importer extends \importer\importer
{

    /*
    ************************************************************************
    */

    function insertFile($file='file')
    {
        $this->uploadFile($file);

        $handle = fopen($this->uploadFile, 'r');

        if ($handle === FALSE) {
            die('No file loaded.');
        }

        $this->model->importData = [];

        $rowIndex = 1;

        while (($rowData = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $this->model->importData[$rowIndex++] = $rowData;
        }

        fclose($handle);

        $lastColumn = 0;

        foreach ($this->model->importData[1] as $key => $value) {
            if ($value) {
                $lastColumn = $key;
            }
        }

        $importData = [];

        // truncating array to amount of columns in its 1-st row
        foreach ($this->model->importData as $key => $value) {

            $row = array_splice($value, 0, $lastColumn + 1);

            $trimmedRow = trim(implode($row));

            if (strlen($trimmedRow)) {
                // skip empty rows
                $importData[$key] = $row;
            }
        }

        $this->model->importData = $importData;

        $result = $this->model->insertFile();

        $this->errors = $this->model->errors;

        return $result;
    }

    /*
    ****************************************************************************
    */
}
