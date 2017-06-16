<?php

namespace files;

use \models\directories;

class move 
{
    function __construct($params)
    {
        $app = $params['app'];
        $files = $app->getArray('files');

        $first = reset($files);

        $dir = isset($params['targetDir']) ? 
            directories::getDir('uploads', $params['targetDir']).'/' : NULL;
        
        $tmpName = getDefault($first['tmp_name']);
        $tmpNames = is_array($tmpName) ? $tmpName : [$tmpName];

        $filtered = array_filter($tmpNames);

        $count = count($filtered);
        $filenames = $params['model']->testFileNames(['quantity' => $count]);

        foreach ($filtered as $index => $tmpName) {
            $filePath = $dir.$filenames[$index];
            copy($tmpName, $filePath);

        }
    }

    /*
    ****************************************************************************
    */
}
