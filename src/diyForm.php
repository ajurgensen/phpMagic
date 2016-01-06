<?php

/**
 * Created by PhpStorm.
 * User: andreas
 * Date: 04/12/2015
 * Time: 11:06
 */

namespace ajurgensen\phpMagic;

class diyForm
{
    private $map;

    /**
     * @return map
     */
    public function getMap()
    {
        return $this->map;
    }

    public function addText($name,$size=128,$value='')
    {
        $col = new colum($name,$size);
        $this->map->addColumn($col);
    }

    public function addBoolean($name,$value=false)
    {
        $col = new colum($name,'BOOLEAN');
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($value);
    }

    public function __construct($name = '')
    {
        $this->map = new map($name);
    }

    public function getValue ($value)
    {

        if (!isset($this->map->{'get' . $value}))
        {
            return ($this->map->staticVars[$value]);
        }
    }
}