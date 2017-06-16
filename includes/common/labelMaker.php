<?php

namespace common;

class labelMaker 
{ 
    // Used when the plates have to be created before transaction is started
    static $reservedPlates = [];
    public $searchLabelLinks = [];
    
    const PLAGES_CONTROLLER = 'plates';
    const ORDER_LABELS_CONTROLLER = 'orderLabels';
    const BILL_OF_LADING_LABELS_CONTROLLER = 'billOfLadings';
     
    const TYPE_ORDER = 'order';
    const TYPE_WORK = 'work';
    const TYPE_BILL = 'bill';
    
    const SEARCH_PLATES = 'plates';
    const SEARCH_ORDER_LABELS = 'orderLabels';
    const SEARCH_WORK_ORDER_LABELS = 'workOrderLabels';
    const SEARCH_BILL_OF_LADING_LABELS = 'billOfLadingLabels';
    
    const WORK = 'work';
    const ORDER = 'order';
    const PLATE = 'plate';
    const BILL = 'bill';
    
    const LABEL_TABLE_WORK_ORDER = 'workOrderLabel';
    const LABEL_TABLE_NEW_ORDER = 'newOrderLabel';
    const LABEL_TABLE_LICENSE_PLATE = 'licensePlate';
    const LABEL_TABLE_BILL_OF_LADING = 'billOfLadings';
    
    const LABEL_BATCHES = 'label_batches';
    const PLATE_BATCHES = 'plate_batches';
    const BILL_BATCHES = 'bill_batches';

    /*
    ****************************************************************************
    */
    
    function __construct($app, $model, $users)
    {   
        $this->app = $app;
        $app->includeJS['custom/js/common/labelMaker.js'] = TRUE;
        $this->model = $model;
        $this->users = $users;
        // Save the class name for label maker js
        $app->jsVars['labelModel'] = $this->className = getClass($model);
        $this->addJSLinks();
        $this->html();
    }

    /*
    ****************************************************************************
    */

    function addJSLinks()
    {
        $className = $this->className;
        $this->app->jsVars['urls']['addLabels'] = makeLink('appJSON', 'addModel');

        $this->searchLabelLinks = [
            'displayLabels' => $className,       
            'labelsByBatch' => 'batch',
            'labelsByDate' => 'dateEntered',
        ];

        $controller = $type = NULL;

        switch ($className) {
            // Moving plate labels to the MVC
            case self::SEARCH_PLATES: 
                $controller = self::PLAGES_CONTROLLER;
                $type = NULL;
                break;
            
            case self::SEARCH_ORDER_LABELS:
                $controller = self::ORDER_LABELS_CONTROLLER;
                $type = self::TYPE_ORDER;
                break;        
            
            case self::SEARCH_WORK_ORDER_LABELS:
                $controller = self::ORDER_LABELS_CONTROLLER;
                $type = self::TYPE_WORK;
                break;

            case self::SEARCH_BILL_OF_LADING_LABELS:
                $controller = self::BILL_OF_LADING_LABELS_CONTROLLER;
                $type = self::TYPE_BILL;
                break;
            
            default:
                die('Invalid Search');
       }

        foreach ($this->searchLabelLinks as $link => $search) {
            $this->app->jsVars['urls'][$link] = 
                customJSONLink($controller, 'display', [
                'type' => $type,
                'search' => $search,
                'term' => '',
            ]);
        }
    }
    
    /*
    ****************************************************************************
    */

    function html()
    {
        $this->htmlSelect = $this->users->htmlSelect();
        
        $model = $this->model;

        ob_start(); ?>
        <table id="addTable"><tr>
        <td id="createBox" class="message">Create New 
                <?php echo $model::$labelsTitle; ?>: Enter Quantity 
            <input id="quantity" type="text">
            <?php echo $this->htmlSelect; ?>
            <button>Submit</button>
        </td>
        <td class="successMessage"><?php echo $model::$labelTitle; 
            ?><span id="plural">s have</span>
            <span id="singular">has</span> been added successfully</td>
            </tr></table><?php        
        $this->app->labelMakerHTML = ob_get_clean();
    }
    
    /*
    ****************************************************************************
    */
    
    static function inserts($params)
    {
        $model = $params['model'];
        $userID = $params['userID'] or die('Missing User ID');
        $quantity = $params['quantity'] or die('Missing Quantity Label');
        $dbName = isset($params['dbName']) ? $params['dbName'].'.' : NULL;
        $makeTransaction = getDefault($params['makeTransaction'], TRUE);

        
        $labelTable = $batchTable = NULL;
        switch ($params['labelType']) {
            case self::WORK : 
                $labelTable = self::LABEL_TABLE_WORK_ORDER;
                $params['table'] = self::LABEL_BATCHES;
                break;
            
            case self::ORDER:
                $labelTable = self::LABEL_TABLE_NEW_ORDER;
                $params['table'] = self::LABEL_BATCHES;
                break;
            
            case self::PLATE: 
                $labelTable = self::LABEL_TABLE_LICENSE_PLATE;
                $params['table'] = self::PLATE_BATCHES;
                break;

            case self::BILL:
                $labelTable = self::LABEL_TABLE_BILL_OF_LADING;
                $params['table'] = self::BILL_BATCHES;
                break;
        }

        self::reserveBatchIDs($params);
        
        $params['returnID'] = TRUE;
        
        $batch = self::$reservedPlates ? 
            array_shift(self::$reservedPlates) : self::getNextBatchID($params);
        
        $sql = 'INSERT INTO ' . $dbName . $labelTable .' (
                    userID,
                    batch
                ) VALUES (?, ?)';
                  
        $makeTransaction ? $model->app->beginTransaction() : NULL;
        
        $results = [];
        for ($i = 0; $i < $quantity; $i++) {
           $results[] =(bool)$model->app->runQuery($sql, [$userID, $batch]);
        }            
         
        if ($makeTransaction && in_array(FALSE, $results)) {
            $pdo = $this->app->getHoldersPDO();
            $pdo->rollBack();
            
            $msg = 'INSERT INTO ' . $dbName . $labelTable .' Failed';
            
            die($msg);
        }
            
        $makeTransaction ? $model->app->commit() : NULL;            
        
        return $quantity;
    }
    
    /*
    ****************************************************************************
    */

    static function reserveBatchIDs($params)
    {
        $firstBatchID = getDefault($params['firstBatchID']);
        
        if (! $firstBatchID) {
            return;
        }
        
        $quantity = $params['quantity'];
        
        $params['returnID'] = FALSE;

        for ($i = 0; $i < $quantity; $i++) {
            self::getNextBatchID($params);
        }
        
        self::$reservedPlates = range($firstBatchID, 
            $firstBatchID + $quantity - 1);
    }
    
    /*
    ****************************************************************************
    */

    static function getNextBatchID($params)
    {
        $table = $params['table'];
        $model = $params['model'];
        $returnID = getDefault($params['returnID']);
        
        return $model->insertBlank($table, $returnID);
    }
    
    /*
    ****************************************************************************
    */

}
