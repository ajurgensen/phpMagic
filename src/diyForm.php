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

    public function addText($name,$size=128,$value='',$diyDesc = '')
    {
        $col = new colum($name,'VARCHAR',$size);
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($value);
        if ($diyDesc) $col->diyDesc = $diyDesc;
    }

    public function addInt($name,$value='',$diyDesc = '')
    {
        $col = new colum($name,'INTEGER');
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($value);
        if ($diyDesc) $col->diyDesc = $diyDesc;
    }

    public function addBoolean($name,$value='',$diyDesc = '')
    {
        $col = new colum($name,'BOOLEAN');
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($value);
        if ($diyDesc) $col->diyDesc = $diyDesc;
    }

    public function addImage($name,$src,$diyDesc = '')
    {
        $col = new colum($name,'IMAGE');
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($src);
        if ($diyDesc) $col->diyDesc = $diyDesc;
    }

    public function addTextBlock($name,$text,$diyDesc = '')
    {
        $col = new colum($name,'TEXTBLOCK');
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($text);
        if ($diyDesc) $col->diyDesc = $diyDesc;
    }
    public function addTimeStamp($name,$text,$diyDesc = '')
    {
        $col = new colum($name,'TIMESTAMP');
        $this->map->addColumn($col);
        $this->map->{'set'.$name}($text);
        if ($diyDesc) $col->diyDesc = $diyDesc;
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