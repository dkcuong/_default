<?php

namespace objects;

class holder
{
    const SHOW_INDEX_CHANGES = FALSE;
    public $values = [];
    public $pdo;
    
    //**************************************************************************

    function showAll()
    {
        varDump($this->values);
    }

    //**************************************************************************

    function get($index)
    {
        return $this->values[$index];
    }

    //**************************************************************************

    function getDef($index)
    {
        return getDefault($this->values[$index]);
    }

    //**************************************************************************

    function set($index, $value)
    {
        if (self::SHOW_INDEX_CHANGES == $index) {
            echo 'Changing index: '.$index.' value';
            backtrace();
        }
        return $this->values[$index] = $value;
    }

    //**************************************************************************

    function concat($index, $value)
    {
        return $this->values[$index] .= $value;
    }
    
    //**************************************************************************

    static function qMarkString($row)
    {
        return $qMarks = implode(',', $this->getQMarks($row));
    }
    
    //**************************************************************************

    static function getQMarks($row)
    {
        return $qMarks = array_fill(0, count($row), '?');    
    }
}