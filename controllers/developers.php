<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use dbCommands\model as commands;

class controller extends template
{

    function dbCheckerDevelopersController()
    {
        $this->jsVars['urls']['runDBCheckJSON'] = jsonLink('runDBCheck');
        $this->jsVars['urls']['runDBUpdateJSON'] = jsonLink('runDBUpdate');
        $this->jsVars['urls']['addTestJSON'] = jsonLink('addTest');

        $this->queries = dbCommands\queries::get();
        ksort($this->queries);

        $this->dataTypes = dbCommands\dataTypes::get();
        ksort($this->dataTypes);

        $this->queryForms = commands::getForms();

        $this->dbKeys = database\model::listDBKeys();
        sort($this->dbKeys);

        $this->iframeURL = $this->jsVars['urls']['iframe'] = 
            makeLink('developers', 'dbCommandsIframe');
        
        $this->jsVars['urls']['testNameAutocomplete'] = jsonLink('autocomplete', [
            'modelName' => 'tests\\tests',
            'field' => 'displayName',
            'secondField' => 'id', 
        ]);

    }

    /*
    ****************************************************************************
    */

    function dbCommandsIframeDevelopersController()
    {
        $this->jsVars['urls']['runDBCheckJSON'] = jsonLink('runDBCheck');
        $this->jsVars['urls']['runDBUpdateJSON'] = jsonLink('runDBUpdate');

        $commands = commands::get($this, 'displayMode'); 
        $this->dbCommands = $commands['results'];

        $this->includeJS['js/jQuery/blocker.js'] = TRUE;
    }
    
}