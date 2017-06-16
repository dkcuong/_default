<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function pagesTesterController()
    {
        new test\recorder($this);

        $this->testerAccess = access::isTester($this);

        $this->showStartButton = ! $this->testerAccess ? NULL : 'hidden';

        $this->type = getDefault($this->get['type']);
         
        $mode = getDefault($this->get['mode']);
        $this->storeSession('testRunMode', $mode);

        $this->targetID = getDefault($this->get['id']);

        $this->jsVars['runTests'] = $this->targetID && $this->testerAccess;

        if (! $this->jsVars['runTests']) {
            return;
        } 

        $this->testDB = $this->testerAccess ? 
            $this->getDB(['dbAlias' => 'tests']) : NULL;

        // Test record session should not be set when running tests
        $this->unsetSession('recordTest');
        
        $this->modelGetTestInfo();

        if ($this->testID) {
    
            // Clear test results before recording
            $sql = 'UPDATE test_results
                    SET    active = 0
                    WHERE  testID = ?';
            $this->testDB->runQuery($sql, [$this->testID]);
        } 

        $this->storeSession('testID', $this->testID);
        $this->jsVars['requests'] = $this->requests;
        $this->jsVars['postVars'] = $this->postVars;

        $this->jsVars['urls']['tests'] = $this->testURLs;
        $this->jsVars['urls']['switchIgnoreField'] = jsonLink('switchIgnoreField');
    }

    /*
    ****************************************************************************
    */

    function listTesterController()
    {
        $show = getDefault($this->get['show']);

        switch ($show) {
            case 'results':
                $table = new tables\tests\results($this);
                break;
            case 'series':
                $table = new tables\tests\series($this);
                break;
            case 'requests':
                $table = new tables\tests\requests($this);
                $this->addButton = FALSE;
                break;
            case 'requestInputs':

                $table = new tables\tests\requestInputs($this);

                $this->addButton = FALSE;

                break;
            case 'tests':
                $table = new tables\tests\tests($this);
                break;
            case 'testSeries':
                $table = new tables\tests\testSeries($this);
                break;
            case 'ignoreFields':

                $table = new tables\tests\ignoreFields($this);

                $this->addButton = FALSE;

                break;
            default:
                die;
        }

        $order = $show == 'requests' ? 1 : 0;

        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [$order => 'asc'],
        ]);

        new datatables\searcher($table);

        $editable = new datatables\editable($table);

        $editable->canAddRows();
    }

    /*
    ****************************************************************************
    */

}