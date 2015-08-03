<?php
/*
The MIT License (MIT)

Copyright (c) [2015] [Andreas K. Jurgensen]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

namespace ajurgensen\phpMagic;

class map
{
    var $columns;
    var $name;
    var $staticVars;

    /**
     * @param mixed $name
     */
    function validates($name,$value)
    {
        if (1==1)
        {
            return true;
        }
        elseif ($name == 'name' && $value == 'value')
        {
            return true;
        }
        else
        {
            return "extend class with some logic";
        }
    }

    function save(){}

    function __call($func, $params)
    {
        if (substr($func,0,3) == 'set')
        {
            $name = substr($func,3);
            $this->staticVars[$name] = $params[0];
        }
        $out = $this->validates($name,$params[0]);
        if ($out === true)
        {
            return true;
        }
        return $out;
    }

    function feedArray($array)
    {
        foreach($array as $line)
        {
            $col = new \ajurgensen\phpMagic\colum($line[0],$line[1],$line[2]);
            $this->addColumn($col);
        }
    }
    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    function toArray()
    {
        return false;
    }
    function __construct($name)
    {
        $this->name = $name;
        $this->columns = array();
    }

    /**
     * @return mixed
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param mixed $columns
     */
    public function addColumn($column)
    {
        array_push($this->columns,$column);
    }
}
