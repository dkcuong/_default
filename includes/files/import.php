<?php

namespace files;

use \PHPExcel_IOFactory as factory;

class import
{
    
    static $table = [];

    /*
    ****************************************************************************
    */
    
    static function toArray($filePath)
    {
        $pathInfo = pathinfo($filePath, PATHINFO_EXTENSION);
        
        switch ($pathInfo) {
            case 'xls':
            case 'xlsx':
                self::importExcel($filePath);
                break;
            case 'csv':
                self::importCSV($filePath);
                break;
        }

        return self::$table;
    }

    /*
    ****************************************************************************
    */
    
    static function importExcel($filePath)
    {
        // Read your Excel workbook
        try {
            $inputFileType = factory::identify($filePath);
            $objReader = factory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($filePath);
        } catch(Exception $e) {
            die('Error loading file "'
                . pathinfo($filePath, PATHINFO_BASENAME).'": '
                . $e->getMessage());
        }  

        $phpExcel = $objPHPExcel or die('No file loaded..');

        $phpExcel->getSheetCount() < 2 or die('One sheet please');
        
        foreach ($phpExcel->getWorksheetIterator() as $worksheet) {
            self::getRows($worksheet);
        }

    }

    /*
    ************************************************************************
    */

    static function getRows($worksheet)
    {
        foreach ($worksheet->getRowIterator() as $row) {

            $cellIterator = $row->getCellIterator();

            // Loop all cells, even if it is not set
            $cellIterator->setIterateOnlyExistingCells(false); 

            $oneRow = [];
            foreach ($cellIterator as $cell) {
                $oneRow[] = $cell->getFormattedValue();
            }

            self::$table[] = $oneRow;
        }
    }

    /*
    ****************************************************************************
    */

    static function importCSV($filePath)
    {
        $handle = fopen($filePath, 'r');

        if ($handle !== FALSE) {
            $row = fgetcsv($handle, 1000, ",");
            while ($row !== FALSE) {
                self::$table[] = $row;
                $row = fgetcsv($handle, 1000, ",");
            }
            
            fclose($handle);
        }
        
        return self::$table;
    }
    
    /*
    ****************************************************************************
    */

    static function getTable()
    {
        return self::$table;
    }
    
    /*
    ****************************************************************************
    */

    static function emptyTable()
    {
        self::$table = [];
    }
    
    /*
    ****************************************************************************
    */
    
}
