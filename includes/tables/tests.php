<?php

namespace tables;

class tests extends _default
{
    public $displaySingle = 'Test';
    
    public $ajaxModel = 'tests';
    
    public $primaryKey = 't.id';
    
    public $fields = [
        'id' => [
            'display' => 'Test ID',
        ],
        'description' => [
            'display' => 'Description',
        ],
        'outputName' => [
            'display' => 'Output Variable',
            'optional' => TRUE,
        ],
        'outputValue' => [
            'display' => 'Output Value',
            'optional' => TRUE,
        ],
    ];
    
    public $table = 'tests t';
    
    public $insertTable = 'tests';
    
    public $mainField = 'description';
    
    
    /*
    ****************************************************************************
    */
    
}