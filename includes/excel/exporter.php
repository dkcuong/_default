<?php

namespace excel;

class exporter
{
    function __construct($app)
    {
        $this->app = $app;
        $this->phpExcel = new \PHPExcel();
    }

    /*
    ****************************************************************************
    */

    function exportArray($array)
    {
        $this->phpExcel->getActiveSheet()->fromArray($array, NULL, 'A1');

        $this->phpExcel->getActiveSheet()->setTitle('Members');

        // Set AutoSize for name and email fields
        $this->phpExcel->getActiveSheet()->getColumnDimension('A')
            ->setAutoSize(true);
        $this->phpExcel->getActiveSheet()->getColumnDimension('B')
            ->setAutoSize(true);

        $objWriter = \PHPExcel_IOFactory::createWriter(
            $this->phpExcel,
            'Excel2007'
        );
        $objWriter->save('test.xls');
    }

    /*
    ****************************************************************************
    */

    static function header($fileName, $extension='xls')
    {
        header('Content-Type:   application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$fileName.'.'.$extension);
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private',false);
    }

    /*
    ****************************************************************************
    */

    static function arrayToTable($array)
    { ?>
        <table>
        <?php foreach ($array as $row) { ?>
            <tr>
            <?php
            // Make each long int into a string
            exporter::fixLongInts($row);
            foreach ($row as $cell) { ?>
                <td><?php echo $cell; ?></td><?php
            } ?>
            </tr>
        <?php } ?>
        </table><?php
    }

    /*
    ****************************************************************************
    */

    static function fixLongInts(&$value)
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $isLongNumber = is_numeric($item) && strlen($item) > 9;
                $item = $isLongNumber ? '="'.$item.'"' : $item;
            }
            return;
        }

        $isLongNumber = is_numeric($value) && strlen($value) > 9;
        $value = $isLongNumber ? '="'.$value.'"' : $value;
    }

    /*
    ****************************************************************************
    */

    static function queryToExcel($params)
    {
        $db = $params['db'];
        $sql = $params['sql'];
        $fileName = $params['fileName'];
        $fieldKeys = $params['fieldKeys'];
        $queryParams = $params['queryParams'];
        $model = $params['model'];

        $sth = $db->runQuery($sql, $queryParams);

        if (getDefault($model->xlsExportFileHandle)) {

            $params['sth'] = $sth;

            $function = [
                $model,
                $model->xlsExportFileHandle
            ];

            call_user_func($function, $params);

        } else {

            $columnTitles = array_column($fieldKeys, 'title');

            self::header($fileName); ?>

            <table>
            <tr>
            <?php
            // Make each long int into a string
            exporter::fixLongInts($columnTitles);
            foreach ($columnTitles as $cell) { ?>
                <td><?php echo $cell; ?></td><?php
            } ?>
            </tr><?php

            $rowCount = 1;

            while ($row = $sth->fetch(\pdo::FETCH_NUM)) { ?>
                <tr><?php
                // Make each long int into a string
                exporter::fixLongInts($row);
                foreach ($row as $cell) { ?>
                    <td><?php echo $cell; ?></td><?php
                } ?>
                </tr><?php

                $rowCount++;

            } ?>
            </table><?php
        }

        die;
    }

    /*
    ****************************************************************************
    */

    static function ArrayToExcel($params)
    {
        $data = $params['data'];
        $fileName = $params['fileName'];
        $fieldKeys = getDefault($params['fieldKeys'], []);

        $columnTitles = array_column($fieldKeys, 'title');

        array_unshift($data, $columnTitles);

        $phpExcel = new \PHPExcel();
        $phpExcel->getDefaultStyle()
            ->getNumberFormat()
            ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        $phpExcel->getActiveSheet()->fromArray($data, NULL, 'A1');

        self::header($fileName);

        $objWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5');
        $objWriter->save('php://output');

        exit;
    }

    /*
    ****************************************************************************
    */

    static function getLetter($index)
    {
        $column = $index - 1;

        $frack = $column % 26;
        $int = intval($column / 26);

        $letter = chr(65 + $frack);

        return $int ? self::getLetter($int) . $letter : $letter;
    }

    /*
    ****************************************************************************
    */

    static function coloring($model, $data)
    {
        $fileName = $data['fileName'];

        $phpExcel = new \PHPExcel();
        $writer = new \PHPExcel_Writer_Excel2007($phpExcel);
        $conditionalStyle = new \PHPExcel_Style_Conditional();

        $phpExcel->setActiveSheetIndex(0);

        $sheet = $phpExcel->getActiveSheet();

        $columnTitles = array_column($model->fields, 'display');
        $columnColors = array_column($model->fields, 'backgroundColor');

        $colors = array_combine($columnTitles, $columnColors);

        $column = $row = 1;

        $columnLetters = [];

        foreach ($columnTitles as $columnTitle) {

            $columnLetters[] = $letter = \excel\exporter::getLetter($column++);

            $sheet->SetCellValue($letter . $row, $columnTitle);

            $letterColors[$letter] = $colors[$columnTitle];
        }

        $sth = $data['sth'];

        $row++;

        while ($rowValues = $sth->fetch(\pdo::FETCH_NUM)) {

            $columnCount = 0;

            foreach ($rowValues as $rowValue) {

                $letter = $columnLetters[$columnCount++];

                $sheet->SetCellValue($letter . $row, $rowValue);
            }

            $row++;
        }

        $row--;

        $currentColor = reset($letterColors);
        $firstLetter = $currentLetter = key($letterColors);

        foreach ($letterColors as $letter => $color) {

            if ($currentColor != $color) {

                self::setExcelColor([
                    'model' => $model,
                    'currentColor' => $currentColor,
                    'range' => $firstLetter . '1:' . $currentLetter . $row,
                    'sheet' => $sheet,
                ]);

                $firstLetter = $letter;
            }

            $currentColor = $color;
            $currentLetter = $letter;
        }

        $rangeEnd = $currentLetter . $row;

        self::setExcelColor([
            'model' => $model,
            'currentColor' => $currentColor,
            'range' => $firstLetter . '1:' . $rangeEnd,
            'sheet' => $sheet,
        ]);

        self::setConditionFormatting([
            'model' => $model,
            'conditionalStyle' => $conditionalStyle,
            'sheet' => $sheet,
            'rangeEnd' => $rangeEnd,
        ]);

        foreach($columnLetters as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(TRUE);
        }

        \excel\exporter::header($fileName, 'xlsx');

        $writer->save('php://output');
    }

    /*
    ****************************************************************************
    */

    static function setExcelColor($data)
    {
        $model = $data['model'];
        $currentColor = $data['currentColor'];
        $sheet = $data['sheet'];
        $range = $data['range'];

        $sheet->getStyle($range)->getFill()
                ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()
                ->setARGB('FF' . $model->backgroundColors[$currentColor]);
    }

    /*
    ****************************************************************************
    */

    static function setConditionFormatting($data)
    {
        $model = $data['model'];
        $conditionalStyle = $data['conditionalStyle'];
        $sheet = $data['sheet'];
        $rangeEnd = $data['rangeEnd'];

        $conditionalStyle
                ->setConditionType(\PHPExcel_Style_Conditional::CONDITION_CELLIS)
                ->setOperatorType(\PHPExcel_Style_Conditional::OPERATOR_EQUAL)
                ->addCondition(0);

        $conditionalStyle->getStyle()->getFill()
                ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getEndColor()
                ->setARGB('FF' . $model->backgroundColors['red']);

        $conditionalStyles = $sheet->getStyle('A1:' . $rangeEnd)
                ->getConditionalStyles();

        array_push($conditionalStyles, $conditionalStyle);

        $sheet->getStyle('A1:' . $rangeEnd)
                ->setConditionalStyles($conditionalStyles);
    }

    /*
    ****************************************************************************
    */

}

