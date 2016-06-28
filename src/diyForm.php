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
        $col = new colum($name,'VARCHAR',$size);
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($value);
    }

    public function addBoolean($name,$value='')
    {
        $col = new colum($name,'BOOLEAN');
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($value);
    }

    public function addImage($name,$src)
    {
        $col = new colum($name,'IMAGE');
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($src);
    }

    public function addTextBlock($name,$text)
    {
        $col = new colum($name,'TEXTBLOCK');
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($text);
    }

    public function __construct($name = '',$validationClosure='')
    {
            $this->map = new map($name,$validationClosure);
    }

    public function getValue ($value)
    {

        if (!isset($this->map->{'get' . $value}))
        {
            return ($this->map->staticVars[$value]);
        }
    }
}