<?php

namespace charts;

use PHPExcel_Worksheet_MemoryDrawing as worksheet;
use PHPExcel_IOFactory as factory;
use excel\exporter;
use models\directories;

class model
{
    const DEBUG = TRUE;

    public $db;

    public $objPHPExcel;

    public $imageDir;

    //**************************************************************************

    function __construct($db)
    {
        $this->db = $db;
    }

    //**************************************************************************

    static function init($db)
    {
        return new static($db);
    }

    //**************************************************************************

    function getImageDir()
    {
        return $this->imageDir;
    }

    //**************************************************************************

    function calcPlots($params)
    {
        $yField = getDefault($params['yField']);
        $xField = getDefault($params['xField']);
        $groupField = $params['groupField'];

        $allDates = $xField ? array_column($this->data, $xField) : [];
        $xData = array_unique($allDates);
        sort($xData);

        $allWHs = array_column($this->data, $groupField);
        $uniqueWHs = array_unique($allWHs);
        $groups = array_values($uniqueWHs);

        $plots = [];

        $dateCount = count($xData);

        foreach ($groups as $wh) {
            $plots[$wh] = array_fill(0, $dateCount, 0);
        }

        foreach ($this->data as $row) {
            $qty = $row[$yField];
            $date = $row[$xField];
            $wh = $row[$groupField];

            $dateID = array_search($date, $xData);
            $plots[$wh][$dateID] = $qty;
        }

        return [
            'plots' => $plots,
            'xData' => $xData,
            'groups' => $groups,
        ];
    }

    //**************************************************************************

    function excel($params, $title)
    {

        $this->chartToExcel($params, $title);

        $objWriter = factory::createWriter($this->objPHPExcel, 'Excel2007');
        $objWriter->save(str_replace('.php', '.xlsx', __FILE__));

        $objWriter->save('php://output');
    }

    //**************************************************************************

    function chartToExcel($params, $title) {

        $this->objPHPExcel->getActiveSheet()->setTitle($title);

        $this->objPHPExcel->setActiveSheetIndex(0);

        ! self::DEBUG ? exporter::header($params['excelFile'], 'xlsx') : NULL;

        $this->imageDir = directories::getDir('uploads', $params['imageDir']);


        foreach ($params['chartImages'] as $row) {

            $imagePath = $this->imageDir.'/'.$row['filename'];
            call_user_func($row['makeImage']['method'], $row);

            $gdImage = imagecreatefrompng($imagePath.'.png');
            $objDrawing = new worksheet();
            $objDrawing->setCoordinates($row['xPos'].$row['yPos']);
            isset($row['name']) ?
                $objDrawing->setName($row['name']) : NULL;
            isset($row['desc']) ?
                $objDrawing->setDescription($row['desc']) : NULL;
            $objDrawing->setImageResource($gdImage);
            $objDrawing->setRenderingFunction(worksheet::RENDERING_PNG);
            $objDrawing->setMimeType(worksheet::MIMETYPE_DEFAULT);
            $objDrawing->setWidth($row['excelWidth']);
            $objDrawing->setHeight($row['excelHeight']);
            $objDrawing->setWorksheet($this->objPHPExcel->getActiveSheet());
        }
    }

    //**************************************************************************
}
