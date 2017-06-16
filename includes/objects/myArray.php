<?php

namespace objects;

class myArray extends myType
{
    
    public $offset = 0;
    
    public $length;
    
    //**************************************************************************

    static function makeEmpty($model)
    {
        return self::makeVar($model, []);
    }
    
    //**************************************************************************

    function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }
    
    //**************************************************************************

    function setKey($index)
    {
        $key = $this->model->get($index);
        $this->setKey = is_string($key) ? $key : $key->get();
        return $this;
    }
    
    //**************************************************************************

    function isEmpty()
    {
        return ! $this->value;
    }
    
    //**************************************************************************

    function getDef($key=FALSE)
    {
        $result = NULL;
        
        if ($this->setKey) {
            $result = $key ? getDefault($this->value[$this->setKey][$key]) : 
                getDefault($this->value[$this->setKey]);
        } else {
            $result = $key ? getDefault($this->value[$key]) : 
                getDefault($this->value);
        }
        
        return $result;
    }
    
    //**************************************************************************

    function getDefArray($key=FALSE)
    {
        $value = self::getDef($key);
        return $this->model->make('array', $value);
    }
    
    //**************************************************************************

    function getDefStr($key=FALSE)
    {
        $value = self::getDef($key);
        return $this->model->make('string', $value);
    }
    
    //**************************************************************************

    function getStr($key=FALSE)
    {
        $value = self::get($key);
        return $this->model->make('string', $value);
    }
    
    //**************************************************************************

    function keyEqualsAssert($key, $value, $callback)
    {
        $this->value[$key] ==  $value? 
            $this->call($callback) : NULL;
        
        return $this;
    }
    
    //**************************************************************************

//    function get($key=FALSE)
//    {
//        $result = NULL;
//        
//        if ($this->setKey) {
//            $result = $key ? $this->value[$this->setKey][$key] : 
//                $this->value[$this->setKey];
//        } else {
//            $result = $key ? $this->value[$key] : $this->value;
//            backtrace();die;
//        }
//        
//        return is_string($result) ? new myString($result) : $result;
//    }

    //**************************************************************************

    function in($field)
    {
        $string = $field.' IN ('.implode(', ', $this->value).') ';
        return $this->model->make('string', $string);
    }

    //**************************************************************************

    function implode($glue)
    {
        $string = implode($glue, $this->value);
        return $this->model->make('string', $string);
    }

    //**************************************************************************

    function slice($length)
    {
        $slice = array_slice($this->value, $this->offset, $length);
        return $this->model->make('array', $slice);
    }

    //**************************************************************************

    function unique()
    {
        $unique = array_unique($this->value);
        return $this->model->make('array', $unique);
    }

    //**************************************************************************

    function combine($index, $isObject=FALSE)
    {
        $array = $isObject ? $index->getValue() : $index;
        $combined = array_combine($this->value, $array);
        return $this->model->make('array', $combined);
    }

    //**************************************************************************

    function fieldToKey($columnName)
    {
        return $this->column($columnName)->combine($this->value);
    }

    //**************************************************************************

    function map($callback)
    {
        $mapped = array_map($callback, $this->value);
        return $this->model->make('array', $mapped);
    }

    //**************************************************************************

    function quotes()
    {
        $quoted = clone $this;
        quoteArray($quoted->value);
        return $quoted;
    }
    
    //**************************************************************************

    function pushTo($index)
    {
        $this->model->get($index)->push($this->value);
        return $this;
    }
    
    //**************************************************************************

    function rowPushes($params, $index, $isObject=FALSE)
    {
        $row = $isObject ? $this->model->get($index) : $index;

        foreach ($params as $key => $rowKey) {
            $this->push($row[$rowKey], $key);
        }
        return $this;
    }
    
    //**************************************************************************

    function pushes($params)
    {
        foreach ($params as $key => $value) {
            $this->push($value, $key);
        }
        return $this;
    }
    
    //**************************************************************************

    function push($value, $key=FALSE)
    {
        if ($key) {
            $this->value[$key] = $value;
        } else {
            $this->value[] = $value;
        }
        return $this;
    }
    
    //**************************************************************************

    function pushByIndex($index, $key=FALSE)
    {
        $value = $this->model->get($index);
        return $this->push($value, $key);
    }

    //**************************************************************************

    function pushObjVal($index, $key=FALSE)
    {
        $value = $this->model->get($index)->getValue();
        return $this->push($value, $key);
    }
    
    //**************************************************************************

    function emp()
    {
        $this->value = [];
        return $this;
    }
    
    //**************************************************************************

    function merge($index, $isObject=FALSE)
    {
        $other = $isObject ? $this->model->getVal($index) : $index;
        $all = array_merge($this->value, $other);
        return $this->model->make('array', $all);
    }
    
    //**************************************************************************

    function mergeTo($index)
    {
        $other = $this->model->get($index);
        $other->value = array_merge($other->value, $this->value);
        return $this;
    }
    
    //**************************************************************************

    function keyMergeRows($key, $name, $isObject=FALSE)
    {
        $other = $isObject ? $this->model->getVal($name) : $name;

        $all = [];
        foreach ($this->value as $index => $row) {
            $keyValue = $row[$key];
            $found = getDefault($other[$keyValue]);
            $all[$index] = $found ? array_merge($row, $found) : $row;
        }
        
        return $this->model->make('array', $all);
    }
    
    //**************************************************************************

    function intersectKey($index, $isObject=FALSE)
    {
        $other = $isObject ? $this->model->getVal($index) : $index;
        $commons = array_intersect_key($this->value, $other);
        return $this->model->make('array', $commons);
    }
    
    //**************************************************************************

    function filterFields($fields)
    {
        $this->pickFields = array_flip($fields);
        $filtered = array_map('self::walkFields', $this->value);
        return $this->model->make('array', $filtered);
        
    }
    
    //**************************************************************************

    function walkFields($row)
    {
        return array_intersect_key($row, $this->pickFields);
    }

    //**************************************************************************

    function filterRowKey($key)
    {
        $this->value = isset($this->value[$key]) ? $this->value : FALSE;
        return $this;
    }
    
    //**************************************************************************

    function filterOutKeyIsset($otherArray, $key)
    {
        $filtered = 
            array_filter($this->value, function ($row) use ($otherArray, $key) {
                return ! $this->model->get($otherArray)->getDef($row[$key]);
            });
        return $this->model->make('array', $filtered);
    }
    
    //**************************************************************************

    function filterByKey($key)
    {
        $filtered = array_filter($this->value, function ($row) use ($key) {
            return isset($row[$key]);
        });
        return $this->model->make('array', $filtered);
    }
    
    //**************************************************************************

    function filter($callback=FALSE)
    {
        $filtered = $callback ? array_filter($this->value, $callback) : 
            array_filter($this->value);
        return $this->model->make('array', $filtered);
    }
    
    //**************************************************************************

    function diff($passedSubtrahend, $index=FALSE)
    {
        $subtrahend = $index ? 
            $this->model->getVal($passedSubtrahend) : $passedSubtrahend;
        $diff = array_diff($this->value, $subtrahend);
        return $this->model->make('array', $diff);
    }
    
    //**************************************************************************

    function diffFrom($from)
    {
        $subtrahend = $this->getValue();
        return $this->model->get($from)->diff($subtrahend);
    }
    
    //**************************************************************************

    function column($column)
    {
        $colunns = clone $this;
        $colunns->value = array_column($colunns->value, $column);
        return $colunns;
    }
    
    //**************************************************************************

    function deriveColumn($name, $callback)
    {
        $new = [];
        foreach ($this->value as $key => &$row) {
            $newRow = $row;
            $newRow[$name] = $callback($row, $key);
            $new[] = $newRow;
        }
        
        return $this->model->make('array', $new);
    }
    
    //**************************************************************************

    function run($callback, $params=[])
    {
        $callback($params);
        return $this;
    }
    
    //**************************************************************************

    function keys()
    {
        $keys = array_keys($this->value);
        return $this->model->make('array', $keys);
    }
    
    //**************************************************************************

    function count()
    {
        $count = count($this->value);
        return $this->model->make('number', $count);
    }
    
    //**************************************************************************

    function othersEach($otherIndex, $callback, $store=FALSE)
    {
        $other = $this->model->get($otherIndex);
        foreach ($other->value as $key => $value) {
            $store ? $this->model->set($store, $value) : NULL;
            $this->call($callback, $value, $key);
        }
        return $this;
    }
    
    //**************************************************************************

    function each($callback, $store=FALSE)
    {
        foreach ($this->value as $key => $value) {
            $store ? $this->model->set($store, $value) : NULL;
            $this->call($callback, $value, $key);
        }
        return $this;
    }
    
    //**************************************************************************

    function addColumn($name, $column)
    {
        $columnElements = is_array($column) ? $column : $column->get();

        foreach (array_keys($this->value) as $index) {
            $this->value[$index][$name] = $columnElements[$index];
        }
        return $this;
    }
    
    //**************************************************************************

    function first()
    {
        $reset = reset($this->value);
        return $this->model->make('array', $reset);
    }
    
    //**************************************************************************

    function end()
    {
        $end = end($this->value);
        return $this->model->make('array', $end);
    }
    
    //**************************************************************************

    function byKey($key)
    {
        $byKey = clone $this;
        $results = [];
        foreach ($this->value as $row) {
            $newKey = $row[$key];
            $results[$newKey] = $row;
        }
        $byKey->value = $results;
        
        return $byKey;
    }
    
    //**************************************************************************

    function groupByKey($key, $params=[])
    {
        $valueKey = getDefault($params['valueKey']);
        $storeKey = getDefault($params['storeKey']);
        $grouped = [];
        foreach ($this->value as $row) {
            $newKey = $row[$key];
            $adding = $valueKey ? $row[$valueKey] : $row;
            if ($storeKey) {
                $grouped[$newKey][$storeKey] = $adding;
            } else {
                $grouped[$newKey][] = $adding;
            }
        }
        return $this->model->make('array', $grouped);
    }
    
    //**************************************************************************

    function firstRowKeys() 
    {
        $newTable = [];
        $firstRow = array_shift($this->value);
        foreach ($this->value as $key => $row) {
            $newTable[$key] = array_combine($firstRow, $row);
        }

        return $this->model->make('array', $newTable);
    }
    
    //**************************************************************************

    function chunk($size) 
    {
        $chunks = array_chunk($this->value, $size);
        return $this->model->make('array', $chunks);
    }
    
    //**************************************************************************

    function writeCSV($dest, $columns) 
    {
        $fp = fopen($dest, 'w');

        $columns ? array_unshift($this->value, $columns) : NULL; 

        foreach ($this->value as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        
        return $this;
    }
    
    //**************************************************************************

    function htmlTable($columns=FALSE) 
    {
        $columns ? array_unshift($this->value, $columns) : NULL; ?>

        <table><?php
        foreach ($this->value as $row) {
            $row = is_array($row) ? $row : [$row];
            ?><tr><?php
                foreach ($row as $cell) {
                    ?><td><?php
                        echo $cell;
                    ?></td><?php
                }
            ?></tr><?php
        }
        ?></table><?php
    }
}