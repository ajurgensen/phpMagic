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
use SimpleExcel\SimpleExcel;

/**
 * Class listMagic
 *
 * @package ajurgensen\phpMagic
 */
class listMagic
{
    private $html;
    public $HTMLready;
    private $options;
    private $fromPropel;
    private $dataArray;
    private $headerArray;
    private $empty;

    /**
     * @return mixed
     */
    public function getFromPropel()
    {
        return $this->fromPropel;
    }

    /**
     * @param mixed $fromPropel
     */
    public function setFromPropel($fromPropel)
    {
        $this->fromPropel = $fromPropel;
    }

    private function addHTML($html)
    {
        $this->html .= $html . "\r\n";
    }

    public function getHTML()
    {
        foreach ($_GET as $param => $hest)
        {
            if ($param == "report_" . $this->stripName($this->options['LM_DOWNLOAD']))
            {
                $this->getCSV();
            }
        }
        return $this->html;
    }

    public function getCSV()
    {
        $filename = $this->stripName($this->options['LM_DOWNLOAD']);
        $pe = new \PHPExcel();

        $array = array_merge([$this->headerArray],$this->dataArray);

        if ($comments = $this->options['LM_DOWNLOAD_TEXTBLOCK'])
        {
            $array = array_merge($comments,$array);
        }


        $pe->setActiveSheetIndex(0)->fromArray($array);

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");;
        header("Content-Disposition: attachment;filename=$filename.xls");
        header("Content-Transfer-Encoding: binary ");
        $objWriter = new \PHPExcel_Writer_Excel5($pe);
        $objWriter->save('php://output');
    }

    private function propelFormatColName($colname)
    {
        $newname = '';
        $parts = explode('_', $colname);
        foreach ($parts as $part)
        {
            $newname .= strtolower(ucfirst($part));
        }

        return ($newname);

    }

    /**
     * @param Array $entites Collection of entities to be listed. Could be a list of Propel Objects
     * @param Array $options LM_LINK LM_EXCLUDE LM_DESCRIPTION LM_NAME LM_ADDNEW LM_DONTSORT
     */
    function __construct($entites = array(), $options = array(), $names = array())
    {
        $this->empty = false;
        $this->options = $options;
        $this->HTMLready = true;
        $this->setFromPropel(0);
        $this->html = '';
        $cols = array();
        if (count($entites))
        {
            foreach ($entites as $first_ob)
            {
                $entity = $first_ob;
                break;
            }
        }
        else
        {
            $entites = [];
            $this->empty = true;
        }

        $nolinking = 0;
        //if (method_exists($entity,'toArray'))
        if (isset($entity) && method_exists($entity, 'configMagic'))
        {
            //configMagicDIY form
            $this->setFromPropel(0);
            $map = $entity;
        }
        elseif (isset($entity))
        {
            $map = $entity::TABLE_MAP;
            $map = $map::getTableMap();
            if ($entity->toArray())
            {
                $this->setFromPropel(1);
            }
        }
        $linkarray = array();
        if (isset($this->options['LM_LINK']) && is_array($this->options['LM_LINK']))
        {
            foreach ($this->options['LM_LINK'] as $key => $value)
            {
                $linkarray[$key] = $value;
            }
        }
        else
        {
            $nolinking = 1;
        }

        //Image col
        if (isset($this->options['LM_IMAGE']))
        {
            $imageColName = $this->options['LM_IMAGE'];
            $cols[$imageColName]['headername'] = $imageColName;
            $cols[$imageColName]['getdatastring'] = $this->propelFormatColName($imageColName);
            if (array_key_exists($imageColName, $linkarray))
            {
                $cols[$imageColName]['type'] = 'IMAGELINK';
                $cols[$imageColName]['link'] = $linkarray[$imageColName];
            }
            else
            {
                $cols[$imageColName]['type'] = 'IMAGE';
            }
        }

        //First loop, build structure
        if (isset($map))
        {
            foreach ($map->getColumns() as $colum)
            {
                if (isset($this->options['LM_EXCLUDE']) && in_array($colum->getName(), $this->options['LM_EXCLUDE']))
                {
                    //excluded
                }
                elseif (!$nolinking && array_key_exists($colum->getName(), $linkarray))
                {
                    //Name field - add id link!
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = $this->propelFormatColName('get' . $this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'LINK';
                    $cols[$colum->getName()]['link'] = $linkarray[$colum->getName()];
                }
                elseif ($colum->getType() == 'VARCHAR')
                {
                    //Normal VARCHAR field
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'VARCHAR';
                }
                elseif ($this->getFromPropel() && $colum->isForeignKey())
                {
                    //Link to other table
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['remoteTableName'] = ucfirst(strtolower($colum->getRelatedTableName()));
                    $cols[$colum->getName()]['type'] = 'REMOTENAME';
                }
                elseif ($colum->getType() == 'INTEGER' && $colum->getName() !== 'id')
                {
                    //Integer field
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'INTEGER';
                }
                elseif ($colum->getType() == 'FLOAT')
                {
                    //Integer field
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'FLOAT';
                }
                elseif ($colum->getType() == 'TIMESTAMP')
                {
                    //Timestamp field
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'TIMESTAMP';
                }
                elseif ($colum->getType() == 'ENUM')
                {
                    //Timestamp field
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'VARCHAR';
                }
                elseif ($colum->getType() == 'BOOLEAN')
                {
                    //Timestamp field
                    $cols[$colum->getName()]['headername'] = $colum->getName();
                    $cols[$colum->getName()]['getdatastring'] = 'get' . $this->propelFormatColName($this->propelFormatColName($colum->getName()));
                    $cols[$colum->getName()]['type'] = 'BOOLEAN';
                }
            }
        }

        $headers = array();
        $dataarray = array();
        //Second loop, get data and build objects
        foreach ($entites as $entity)
        {
            $fieldarray = array();
            $data = '';
            foreach ($cols as $col)
            {
                if (!isset($headers[$col['headername']]))
                {
                    if (isset($names[$col['headername']]))
                    {
                        $headers[$col['headername']] = $names[$col['headername']];
                    }
                    elseif (isset($options['LM_DESCRIPTION'][$col['headername']]))
                    {
                        $headers[$col['headername']] = $options['LM_DESCRIPTION'][$col['headername']];
                    }
                    else
                    {
                        $headers[$col['headername']] = $col['headername'];
                    }
                }
                if ($col['type'] == 'REMOTENAME')
                {

                    $remote_id = $entity->{$col['getdatastring']}();
                    $remoteQueryName = ucfirst(strtolower($col['remoteTableName'])) . "Query::create";
                    $remoteQuery = call_user_func($remoteQueryName);
                    if ($remoteEntity = $remoteQuery->findOneById($remote_id))
                    {
                        $data = $remoteEntity->getName();
                    }
                }
                elseif ($col['type'] == 'IMAGE')
                {
                    $data = '<a href="#" class="thumbnail"><img width="100" src=' . $entity->{$col['getdatastring']} . '></a>';

                }
                elseif ($col['type'] == 'BOOLEAN')
                {
                    $data = 'No';
                    if ($entity->{$col['getdatastring']}()) $data = 'Yes';
                }
                elseif ($col['type'] == 'IMAGELINK')
                {
                    $data = '<a href="' . $entity->{$col['link']} . '" class="thumbnail"><img width="100" src=' . $entity->{$col['getdatastring']} . '></a>';
                }
                else
                {
                    $data = $entity->{$col['getdatastring']}();
                }
                if ($col['type'] == 'TIMESTAMP' && ($data instanceof \DateTime))
                {
                    $data = $data->format('Y-m-d H:i:s');
                }
                if ($col['type'] == 'LINK' && isset($entity->{$col['link']}))
                {
                    if (!$data)
                    {
                        $data = '_';
                    }
                    $data = '<a href="' . $entity->{$col['link']} . '">' . $data . '</a>';
                }
                if (!is_null($data))
                {
                    $fieldarray[] = $data;
                }
                else
                {
                    $fieldarray[] = '';
                }
            }

            if ($this->fromPropel)
            {
                foreach ($entity->getVirtualColumns() as $key => $value)
                {
                    if (isset($this->options['LM_EXCLUDE']) && in_array($key, $this->options['LM_EXCLUDE']))
                    {
                        //excluded
                    }
                    else
                    {
                        if (!isset($headers[$key]))
                        {
                            if (isset($names[$key]))
                            {
                                $headers[$key] = $names[$key];
                            }
                            elseif (isset($options['LM_DESCRIPTION'][$key]))
                            {
                                $headers[$key] = $options['LM_DESCRIPTION'][$key];
                            }
                            else
                            {
                                $headers[$key] = $key;
                            }
                        }
                        if (array_key_exists($key, $linkarray))
                        {
                            $fieldarray[] = '<a href="' . $entity->{$linkarray[$key]} . '">' . $value . '</a>';

                        }
                        else
                        {
                            $fieldarray[] = $value;
                        }
                    }

                }

            }
            $dataarray[] = $fieldarray;

        }

        $this->dataArray = $dataarray;
        $this->headerArray = $headers;

        if (isset($options['LM_NAME']))
        {
            $name = $options['LM_NAME'];
        }
        elseif (isset($map))
        {
            $name = $map->getName();
        }
        else
        {
            $name = '';
        }

        //Third loop, build HTML from objects
        $this->initHTML($name);

        $this->addHeaders($headers);

        $this->addData($dataarray);

        $afterTableComment = '';
        if (isset($this->options['LM_ADDNEW']))
        {
            $afterTableComment = '<a href="' . $this->options['LM_ADDNEW'] . '">Add New</a></p>';
        }
        $this->endHTML($afterTableComment);

        $this->HTMLready = true;
    }

    public function initHTML($title = '')
    {
        if (isset($this->options['LM_DONTSORT']))
        {
            $sortable = '';
        }
        else
        {
            $sortable = 'sortable';
        }
        if (isset($this->options['LM_SEARCH']))
        {
            $search2 = $this->options['LM_SEARCH'] . ' ';
            $search1 = '<input type="search" class="light-table-filter" data-table="' . $search2 . '" placeholder="Quick Filter">';
        }
        else
        {
            $search1 = '';
            $search2 = '';
        }
        $this->addHTML('<div class="panel panel-default">');
        if ($title)
        {
            $this->addHTML('<div class="panel-heading"><h3 class="panel-title">' . $title    .'</h3></div>');
        }
        $this->addHTML('<div class="panel-body">' . $search1 . '<table class="' . $search2 . 'col-md-12 table-bordered table-striped table-condensed cf ' . $sortable . '">');
    }

    public function endHTML($afterTableComment = '')
    {
        $downloadLink = '';
        $this->addHTML('</tbody>');
        if (isset($this->options['LM_DOWNLOAD']) && !$this->empty)
        {
            $downloadName = $this->stripName($this->options['LM_DOWNLOAD']);
            $downloadLink = "<a href='?report_".$downloadName."'>Download " . $this->options['LM_DOWNLOAD']."</a> ";
        }
        if ($this->empty)
        {
            $this->addHTML($afterTableComment);

        }
        else
        {
            $this->addHTML('</table>'.   $downloadLink . $afterTableComment . '</div></div>');
        }

    }

    public function stripName($name)
    {
        return preg_replace('/\s+/', '', $name);
    }

    /**
     * @param $headers
     */
    public function addHeaders($headers)
    {
        if (count($headers))
        {
            $this->addHTML('<thead class="cf"><tr>');

            $first = true;
            foreach ($headers as $header)
            {
                $this->addHTML('<th>' . $header . '</th>');
            }

            $this->addHTML('</tr></thead>');
            $this->addHTML('<tbody>');
        }
        $this->headerArray = $headers;
    }

    /**
     * @param $dataarray
     */
    public function addData($dataarray)
    {

        foreach ($dataarray as $fieldarray)
        {
            $this->empty = false;
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
        $this->dataArray = $dataarray;
    }
}
