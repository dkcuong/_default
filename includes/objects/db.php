<?php

namespace objects;

class db extends myType
{
    public $sth;
    public $sql = NULL;
    public $glue = NULL;
    public $clause = NULL;
    public $params = [];
    public $results = [];
    public $flatParams = [];
    
    //**************************************************************************

    function __debugInfo()
    {
        return (array) $this;
    }

    //**************************************************************************

    function sql($sql)
    {
        $this->sql = $sql;
        // Don't want params from previous query
        $this->flatParams = $this->params = $this->results = [];
        return $this;
    }
    
    //**************************************************************************

    function param($param)
    {
        $this->flatParams = $this->params[] = [$param];
        return $this;
    }
    
    //**************************************************************************

    function params($params)
    {
        $this->flatParams = $this->params[] = $params;
        return $this;
    }
    
    //**************************************************************************

    function paramsObject($passedParams, $index=FALSE)
    {
        $params = $index ? $this->model->get($passedParams) : $passedParams;
        $this->flatParams = $this->params[] = $params->getValue();
        return $this;
    }
    
    //**************************************************************************

    function prep($sql)
    {
        $this->sql = $sql;
        $this->sth = $this->model->pdo()->prep($sql);
        return $this;
    }
    
    //**************************************************************************

    function clause($glue, $clause)
    {
        $this->glue = $glue;
        $this->clause = $clause;
        return $this;
    }
    
    //**************************************************************************

    function bindParamsObj($index, $fields=[])
    {
        $params = $this->model->get($index)->getValue();
        return $this->bindParams($params, $fields);
    }
    
    //**************************************************************************

    function bindParams($params, $fields=[])
    {
        if ($fields) {
            $this->pickFields = array_flip($fields);
            array_walk($params, 'self::walkFields');
        }
        
        if ($this->clause) {
            $this->sql .= implode(' '.$this->glue.' ', $this->clause);
            $this->clause = NULL;
        }
        
        
        $this->params = $params;
        foreach ($params as $key => $value) {
            $this->sth->bindParam(':'.$key, $value);
        }
        return $this;
    }
    
    //**************************************************************************

    function walkFields($row)
    {
        $row = array_intersect_key($row, $this->pickFields);
    }
    
    //**************************************************************************

    function run($index=FALSE)
    {
        $this->results = $this->sth->execute($this->params);

        $index ? $this->model->set($index, $this->results) : NULL;
        return $this;
    }
    
    //**************************************************************************

    function paramString($index)
    {
        $indexes = explode(', ', $index);
        return self::varParams($indexes);
    }
    
    //**************************************************************************

    function varParam($index)
    {
        $this->params[] = $adding = $this->model->get($index)->getValue();
        $this->flatParams = $this->flatParams ? 
            array_merge($this->flatParams, $adding) : $adding;
        return $this;
    }
    
    //**************************************************************************

    function varParams($indexes)
    {
        $this->params[] = $adding = $this->model->gets($indexes);
        $this->flatParams = $this->flatParams ? 
            array_merge($this->flatParams, $adding) : $adding;
        return $this;
    }
    
    //**************************************************************************

    function exec($index=FALSE)
    {
        $this->results = $this->model->pdo()->runQuery($this->sql, $this->params);
        $index ? $this->model->set($index, $this->results) : NULL;
        return $this;
    }
    
    //**************************************************************************

    function fetch()
    {
        $this->results = $this->sth->fetch(\pdo::FETCH_ASSOC|\pdo::FETCH_UNIQUE);
        return $this->model->make('array', $this->results);
    }
    
    //**************************************************************************

    function select($index=FALSE)
    {
        $valuesStrings = [];
        
        foreach ($this->params as $params) {
            $valuesStrings[] = '('.$this->model->qMarkString($params).')';
            
        }
        
        $this->sql = str_replace(['CLAUSE_VALUES'], $valuesStrings, $this->sql);
        return $this->execFetch($index);
    }
    
    //**************************************************************************

    function clauseValues($index=FALSE)
    {
        $valuesStrings = [];
        
        foreach ($this->params as $params) {
            $valuesStrings[] = '('.$this->model->qMarkString($params).')';
            
        }
        
        $this->sql = str_replace(['CLAUSE_VALUES'], $valuesStrings, $this->sql);
        return $this->execFetch($index);
    }
    
    //**************************************************************************

    function insert($index=FALSE)
    {
        $valuesString = 'VALUES ('.$this->model->qMarkString($this->params).')';
        $this->sql = str_replace('INSERT_VALUES', $valuesString, $this->sql);
        return $this->exec($index);
    }
    
    //**************************************************************************

    function resultsObj($index=FALSE)
    {
        $results = $this->results($index);
        return myArray::makeVar($results);
    }
    
    //**************************************************************************

    function results($index=FALSE)
    {
        $this->execFetch($index);
        return $this->results;
    }
    
    //**************************************************************************

    function execFetch($index=FALSE)
    {
        $results = 
            $this->model->pdo()->queryResults($this->sql, $this->flatParams);
        $this->results = $this->model->make('array', $results);
        $index ? $this->results->store($index) : NULL;
        return $this;
    }    
    
    //**************************************************************************

    function show()
    {
        $this->results->dump();
        return $this;
    }    

    //**************************************************************************

    function callback($callback, $index=FALSE)
    {
        $this->results($index);
        $callback($this->results);
        return $this;
    }
    
    //**************************************************************************

    function resultsCall($callback)
    {
        $this->results();
        ! $this->results->isEmpty() ? $callback($this->results) : NULL;

        return $this;
    }    

    //**************************************************************************

    function emptyCall($callback)
    {
        $this->results();
        $this->results->isEmpty() ? $callback() : NULL;

        return $this;
    }    

}
