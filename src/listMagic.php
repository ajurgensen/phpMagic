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

class listMagic
{
    private $html;
    public $HTMLready;

    private function addHTML($html)
    {
        $this->html .= $html;
    }
    public function getHTML()
    {
        return $this->html;
    }

    private function propelFormatColName($colname)
    {
        $newname = '';
        $parts = explode('_',$colname);
        foreach($parts as $part)
        {
            $newname .= strtolower(ucfirst($part));
        }
        return($newname);

    }

    function __construct($entites,$options='')
    {
        $this->HTMLready = true;
        $this->html = '';
        $cols = array();
        if (!count($entites))
        {

        } else
        {
            $entity = $entites[0];
        }

        $nolinking = 0;
        if (isset($entity))
        {
            $map = $entity::TABLE_MAP;
            $map = $map::getTableMap();
        }
        if (isset($options['LM_LINK']))
        {
            $linkname = $options['LM_LINK'];
        } else
        {
            $nolinking = 1;
        }

        //First loop, build structure
        if (isset($map))
        {
            foreach ($map->getColumns() as $colum)
            {
                if (isset($options['LM_EXCLUDE']) && in_array($colum->getName(), $options['LM_EXCLUDE']))
                {
                    //excluded
                } elseif (!$nolinking && in_array($colum->getName(), $linkname))
                {
                    //Name field - add id link!
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = $this->propelFormatColName('get' . $this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'LINK';
                } elseif ($colum->getType() == 'VARCHAR')
                {
                    //Normal VARCHAR field
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'VARCHAR';
                } elseif ($colum->getType() == 'INTEGER' && $colum->getName() !== 'id')
                {
                    //Integer field
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'INTEGER';
                } elseif ($colum->getType() == 'TIMESTAMP')
                {
                    //Integer field
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'TIMESTAMP';
                }
            }
        }

        $headers = array();
        $dataarray = array();
        //Second loop, get data and build objects
        foreach ($entites as $entity)
        {
            $fieldarray = array();
            foreach ($cols as $col)
            {
                if (!isset($headers[$col['headername']]))
                {
                    $headers[$col['headername']] = $col['headername'];
                }
                $data = $entity->{$col['getdatastring']}();
                if ($col['type'] == 'TIMESTAMP' && ($data instanceof DateTime))
                {
                    $data = $data->format('Y-m-d H:i:s');
                }
                if ($col['type'] == 'LINK')
                {
                    if (!$data)
                    {
                        $data = '_';
                    }
                    $data = '<a href="' . $entity->link . '">' . $data . '</a>';
                }
                $fieldarray[] = $data;


            }
            $dataarray[] = $fieldarray;
        }

        if (isset($map))
        {
            $name = $map->getName();
        }
        else
        {
            $name = '';
        }
            //Third loop, build HTML from objects
            $this->initHTML($name);

        $this->addHTML('<thead class="cf"><tr>');

        foreach ($headers as $header)
        {
            $this->addHTML('<th>' . $header . '</th>');
        }

        $this->addHTML('</thead></tr>');


        foreach ($dataarray as $fieldarray)
        {
            $this->addHTML('<tr>');
            foreach ($fieldarray as $col)
            {
                if (is_a($col, "DateTime"))
                {
                    $col = $col->format('D, d M y H:i:s');
                }
                $this->addHTML('<td>');
                $this->addHTML((string)$col);
                $this->addHTML('</td>');
            }

            $this->addHTML('</tr>');
        }

        $afterTableComment = '';
        if (isset($options['LM_ADDNEW']))
        {
            $afterTableComment = '<p><a href="'. $options['LM_ADDNEW'] .'">Add New ' . $name .'</a></p>';
        }
        $this->endHTML($afterTableComment);

        $this->HTMLready = true;
    }


    private function initHTML($title)
    {
        $this->addHTML('<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">' . $title. '</h3></div>
<div class="panel-body">
<table class="col-md-12 table-bordered table-striped table-condensed cf sortable">'
        );
    }

    private function endHTML($afterTableComment='')
    {
        $this->addHTML('</table>'. $afterTableComment . '</div></div>');
    }
}
