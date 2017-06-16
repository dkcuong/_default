<?php

namespace objects;

use files\import as filesImport;

class model
{
    public $db;
    public $pdo;
    public $holder;
    
    //**************************************************************************

    function __construct($pdo)
    {
        $this->db = $this->make('db', $pdo);
        $this->pdo = $pdo;
        $this->holder = new holder();
        return $this;
    }

    //**************************************************************************

    static function init($pdo)
    {
        return new static($pdo);
    }

    //**************************************************************************

    function make($type, $value=NULL)
    {
        switch ($type) {
            case 'db':
                return db::makeVar($this, $value)->setType('db');
            case 'emptyArray':
                return myArray::makeEmpty($this)->setType('array');
            case 'array':
                return myArray::makeVar($this, $value)->setType('array');
            case 'string':
                return myString::makeVar($this, $value)->setType('string');
            case 'number':
                return myNumber::makeVar($this, $value)->setType('number');
        }                
    }

    //**************************************************************************

    function db()
    {
        return $this->db;
    }

    //**************************************************************************

    function pdo()
    {
        return $this->pdo;
    }

    //**************************************************************************

    function get($index)
    {
        return $this->holder->get($index);
    }

    //**************************************************************************

    function getStr($index)
    {
        $value = $this->get($index);
        return myString::makeVar($value);
    }

    //**************************************************************************

    function getArray($index)
    {
        $value = $this->get($index);
        return myArray::makeVar($value);
    }

    //**************************************************************************

    function gets($indexes)
    {
        $values = [];
        foreach ($indexes as $index) {
            $values[] = $this->get($index);
        }
        return $values;
    }
    
    //**************************************************************************

    function getVal($index)
    {
        return $this->get($index)->getValue();
    }

    //**************************************************************************

    function getVals($indexes)
    {
        $values = [];
        foreach ($indexes as $index) {
            $values[] = $this->getVal($index);
        }
        return $values;
    }

    //**************************************************************************

    function dumpVals($indexes, $depth=FALSE)
    {
        foreach ($indexes as $index) {
            $value = $this->getVal($index);
            $this->dump($value, $depth);
        }
        return $this;
    }

    //**************************************************************************

    function dump($index, $depth=FALSE)
    {
        return $this->get($index)->dump($depth);
    }

    //**************************************************************************

    function dumps($indexes, $depth=FALSE)
    {
        foreach ($indexes as $index) {
            $this->dump($index, $depth);
        }
        return $this;
    }

    //**************************************************************************

    function dumpNameString($index, $depth=FALSE)
    {
        $indexes = explode(' ', $index);
        return $this->dumps($indexes, $depth);
    }

    //**************************************************************************

    function stop($value)
    {
        $this->dump($value, 2); 
        die;
    }

    //**************************************************************************

    function set($index, $value)
    {
        return $this->holder->set($index, $value);
    }

    //**************************************************************************

    function setStr($index, $value)
    {
        return $this->make('string', $value)->store($index);
    }

    //**************************************************************************

    function setArray($index, $value)
    {
        return $this->make('array', $value)->store($index);
    }

    //**************************************************************************

    function sets($params)
    {
        foreach ($params as $index => $value) {
            $this->set($index, $value);
        }
        return $this;
    }

    //**************************************************************************

    function showAll()
    {
        $this->holder->getValues();
        return $this;
    }

    //**************************************************************************

    function concat($index, $value)
    {
        return $this->model->concat($index, $value);
    }
    
    //**************************************************************************

    function mustHaves($indexes)
    {
        foreach ($indexes as $index) {
            ! $this->getDef($index) ? diedump('Didn\'t have: '.$index) : NULL;
        }
    }

    //**************************************************************************

    function qMarkString($row)
    {
        $qMarks = $this->getQMarks($row);
        return implode(',', $qMarks);
    }
    
    //**************************************************************************

    function getQMarks($row)
    {
        $count = count($row);
        return array_fill(0, $count, '?');    
    }

    //**************************************************************************

    function getCSV($path, $name, $firstRowKeys=FALSE) 
    {
        $table = filesImport::importCSV($path);
        $myArray = $this->make('array', $table)->store($name);
        $firstRowKeys ? $myArray->firstRowKeys()->store($name) : NULL;
        filesImport::emptyTable();
        return $myArray;
    }
    
}