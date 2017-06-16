<?php

namespace dbCommands;

class dataTypes
{
    static $models = [        
        'page' => [
            'display' => 'Page',
            'targets' => ['displayName', 'hiddenName'],
        ],
        'test' => [
            'display' => 'Test',
            'target' => 'displayName',
        ],
        'group' => [
            'display' => 'Group',
            'target' => 'description', 
        ],
        'status' => [
            'display' => 'Status',
            'target' => 'displayName', 
        ],
        'submenu' => [
            'display' => 'Submenu',
            'target' => 'displayName', 
        ],
        'dealSite' => [
            'display' => 'Deal Site',
            'target' => 'displayName', 
        ],
        'cronTask' => [
            'display' => 'Cron Task',
            'target' => 'displayName', 
        ],
    ];

    /*
    ****************************************************************************
    */

    static function get($index=FALSE)
    {
        $models = [];
        foreach (self::$models as $key => $model) {
            $singelTarget = getDefault($model['target']);
            $model['targets'] = $singelTarget ? 
                [$singelTarget] : $model['targets'];
            $models[$key] = $model;
        }
        
        return $index ? $models[$index] : $models;
    }
    
}
