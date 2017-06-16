<?php

namespace models;

use access;

class history 
{
    public $db;
    public $params;
    public $userID;
    public $active = TRUE;
    public $tableName = 'history';
    public $values = [
        'targetID', 'field', 'type', 'fromVal', 
        'toVal', 'userID', 'title', 'hasTitle',        
    ];
    
    //**************************************************************************

    static function init($params=[])
    {
        $self = new static();
        $self->db = getDefault($params['db']);
        $self->tableName = isset($params['tableName']) ? 
            $params['tableName'] : $self->tableName;
        $self->userID = access::getUserID();
        return $self;
    }
    
    //**************************************************************************

    function varDB($holder)
    {
        $this->db = $holder->varGet('db');
        return $this;
    }
    
    //**************************************************************************

    function getDBFrom($holder)
    {
        $this->db = $holder->db();
        return $this;
    }
    
    //**************************************************************************
    
    function setParams($params)
    {
        $this->params = $params;
        return $this;
    }
    
    //**************************************************************************
    
    function trigger($required)
    {
        $this->active = $required;
        return $this;
    }
    
    //**************************************************************************
    
    function addFields($fields)
    {
        $this->values = array_merge($this->values, $fields);
        return $this;
    }
    
    //**************************************************************************
    
    function add($params)
    {
        $params['userID'] = isset($params['userID']) ? 
            $params['userID'] : $this->userID;

        if (! $this->active) {
            return $this;
        }

        $valid = array_flip($this->values);
        
        $field = $params['field'];
        $params['title'] = 
            getDefault($this->params['tableModel']->fields[$field]['display']);
        
        $params['hasTitle'] = $params['title'] ? 'Y' : 'N';

        $ordered = array_merge($valid, $params);
        
        if (count($ordered) != count($this->values) 
        ||  count($ordered) != count($params)) {
            die('Invalid hisotry fields submitted');
        }
        
        if ($params['fromVal'] == $params['toVal']) {
            return;
        }
        
        $fields = array_keys($ordered);
        $qParams = array_values($ordered);
        
        $sql = 'INSERT INTO '.$this->tableName.' ('.implode(',', $fields).') 
                VALUES ('.$this->db->getQMarkString($qParams).')';

        $this->db->runQuery($sql, $qParams);
        
        return $this;
    }
    
    //**************************************************************************
    
    function updates($oldValues, $newValues)
    {
        foreach ($newValues as $key => $value) {
            $this->add([
                'userID' => $this->userID, 
                'type' => $this->params['type'], 
                'targetID' => $this->params['targetID'], 
                'field' => $key, 
                'toVal' => $value, 
                'fromVal' => $oldValues[$key], 
            ]);
        }
        
        return $this;
    }
}
