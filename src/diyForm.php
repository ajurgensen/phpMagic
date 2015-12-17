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

    public function addText($name,$desc,$size=128,$value='')
    {
        // EVIL HACK - name <-> desc
        $col = new colum($name,$desc,$size);
        $this->map->addColumn($col);
    }

    public function addBoolean($name,$desc,$value=false)
    {
        // EVIL HACK - name <-> desc
        $col = new colum($name,$desc,'BOOLEAN');
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
            return ($this->map->{'get' . $value});
        }
    }
}