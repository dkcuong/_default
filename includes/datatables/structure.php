<?php

namespace datatables;

use \excel\exporter;

class structure 
{
    
    public $params = [];
    
    /*
    ****************************************************************************
    */
    
    function __construct($params=[])
    {
        $defaultParams = [
            'processing' => TRUE,
            'serverSide' => TRUE,
            'bFilter' => TRUE,
            'iDisplayLength' => 10,
            'sScrollX' => '100%',
        ];
        
        $finalParams = array_merge($defaultParams, $params);

        $this->params = $finalParams;
        
        return $this;
    }
    
    /*
    ****************************************************************************
    */
    
    static function tableHTML($id=FALSE)
    {
        $requestClass = $id ? $id : \appConfig::get('site', 'requestClass');
        ob_start(); ?>
        <table id="<?php echo $requestClass; ?>" class="display dynamicDT">
        </table><?php 
        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    static function exportTable($dtInfo)
    {
        ?>
        <table cellspacing="0" width="100%">
            <thead>
                <tr>
                <?php foreach ($dtInfo['columns'] as $field) { ?>
                    <th><?php echo $field['title']; ?></th>
                <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                    foreach ($dtInfo['data'] as $row) { ?>
                        <tr>
                        <?php 
                        exporter::fixLongInts($row);
                        foreach ($row as $cell) { ?>
                                <td><?php echo $cell; ?></td>
                        <?php } ?>
                        </tr><?php 
                    }
                    ?>
            </tbody>
        </table>
        <?php        
    }    

    /*
    ****************************************************************************
    */

    static function search(&$customDT, $app, $target, $targetCol)
    {
        $term = getDefault($app->get[$target]);
        if (! $term) {
            return;
        }
        
        $customDT['oSearch'] = ['sSearch' => $term];
        $customDT['columns'] = [
            $targetCol => ['searchable' => TRUE]
        ];
    }
    
    /*
    ****************************************************************************
    */

    function ajaxPost($passedURL=FALSE)
    {
        $url = $passedURL ? $passedURL : $this->params['ajax'];
    
        $this->params['ajax'] = [
            'url' => $url,
            'type' => 'POST',
        ];
    }
    
    /*
    ****************************************************************************
    */
}
