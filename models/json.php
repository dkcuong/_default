<?php 

class model extends base 
{
    function createModelObject()
    {
        isset($this->get['modelName']) or die('Missing Model Name');
        
        $objectName = 'tables\\' . $this->get['modelName'];
        
        $object = new $objectName($this);

        return $object;
    }

    /*
    ****************************************************************************
    */    

    function datatables()
    {
        $model = $this->createModelObject();

        $ajax = new datatables\ajax($this);
        
        $ajax->setWhereClause();

        $customDT = getDefault($this->post['customDT'], []);

        $output = $ajax->output($model, $customDT);

        $ajax->requestPropsOnly($output->params);

        $this->results = $output->params;
    }

    /*
    ****************************************************************************
    */    
    
}