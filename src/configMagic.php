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

class configMagic
{

    private $options;
    private $names;
    private $viewFile;
    private $nameFile;
    private $html;
    public $entitySaved;
    public $viewname;
    public $namesname;

    /**
     * @return mixed
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param mixed $html
     */
    private function setHtml($html)
    {
        $this->html = $html;
    }

    /**
     * @return mixed
     */
    private function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    private function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return mixed
     */
    private function getNames()
    {
        return $this->names;
    }

    /**
     * @param mixed $names
     */
    private function setNames($names)
    {
        $this->names = $names;
    }


    /**
     * @param $viewsCatalog
     * @param $viewname
     * @param $namesname
     * @param $entity
     * @param $editoptions
     * @param $app
     */
    function __construct(&$entity,$viewname='', $namesname='')
    {
        if (!$viewname)
        {
            $viewname = $this->getNameFromEntityMap($entity) . 'view';
        }

        if (!$namesname)
        {
            $namesname = $this->getNameFromEntityMap($entity) . 'name';
        }


        $viewsCatalog = '../views';
        $this->viewFile = $viewsCatalog . '/' . $viewname;
        $this->nameFile = $viewsCatalog . '/' . $namesname;
        $this->entitySaved = false;
        $this->viewname = $viewname;
        $this->namesname = $namesname;

        if (!file_exists($this->nameFile))
        {
            $this->diskWriteNames(array());
        }
        else
        {
            $this->diskReadNames();
        }

        if (!file_exists($this->viewFile))
        {
            $this->options = array();
            $this->options['FM_NAME'] = 'Name';
            $this->options['FM_OPTIONS']['hideback'] = false;
            $this->options['FM_DESC'] = $entity->getName();
            $this->options['FM_AUTOTRANSLATE'] = false;
            $this->options['FM_SLIMFORM'] = false;
            $this->options['FM_EXCLUDE'] = array('created_at', 'updated_at', 'version', 'user_id', 'CREATED_AT', 'UPDATED_AT', 'VERSION', 'USER_ID');
            $this->diskWriteOptions();
        }
        else
        {
            $this->diskReadOptions();
        }

        $qs = $_SERVER['QUERY_STRING'];
        if (substr_count($qs,$this->viewname) && substr_count($qs,$this->namesname))
        {
            if ($this->showConfig($entity))
            {
                $this->form($entity);
            }
        }
        else
        {
            $this->form($entity);
        }
    }

    public function form(&$entity)
    {
        $fm = new formMagic($entity,$this->options,$this->names);
        $this->setHtml($fm->html);

        $this->setHtml($this->getHtml() . '<a href="?'. $this->viewname . $this->namesname.'">hest</a>');

        if ($fm->entitySaved)
        {
            $this->entitySaved = true;
        }
        return;
    }

    /**
     * @param $options
     */
    private function diskWriteOptions()
    {
        file_put_contents($this->viewFile, serialize($this->options));
    }

    /**
     * @return mixed
     */
    private function diskReadOptions()
    {
        $this->options = unserialize(file_get_contents($this->viewFile));
    }

    /**
     * @param $names
     */
    private function diskWriteNames($names)
    {
        file_put_contents($this->nameFile, serialize($names));
    }

    /**
     * @return mixed
     */
    private function diskReadNames()
    {
        $this->names = unserialize(file_get_contents($this->nameFile));
    }

    /**
     * @param $entity
     * @param $options
     * @param $names
     * @return bool
     */
    private function showConfig($entity)
    {
        $pm = new pageMagic('');

        $settingsHtml = '';

        //General settings
        $map = new \ajurgensen\phpMagic\map('');
        $map->addColumn(new \ajurgensen\phpMagic\colum('Name', 'Name', 'VARCHAR'));
        $map->addColumn(new \ajurgensen\phpMagic\colum('Description', 'Description', 'VARCHAR'));
        $map->addColumn(new \ajurgensen\phpMagic\colum('AutoTranslate', 'AutoTranslate', 'BOOLEAN'));
        $map->addColumn(new \ajurgensen\phpMagic\colum('HideBack', 'HideBack', 'BOOLEAN'));
        $map->addColumn(new \ajurgensen\phpMagic\colum('SlimForm', 'SlimForm', 'BOOLEAN'));

        $map->setName($this->options['FM_NAME']);
        $map->setDescription($this->options['FM_DESC']);
        $map->setAutoTranslate($this->options['FM_AUTOTRANSLATE']);
        $map->setSlimForm($this->options['FM_SLIMFORM']);

        if (isset($this->options['FM_OPTIONS']['hideback']))
        {
            $map->setHideBack(1);
        }

        $editoptions = array();
        $editoptions['FM_NAME'] = 'General Settings';
        $editoptions['FM_DESC'] = '';

        $fm = new \ajurgensen\phpMagic\formMagic($map, $editoptions, array());

        if ($fm->entitySaved)
        {
            $this->options['FM_NAME'] = $map->getName();
            $this->options['FM_DESC'] = $map->getDescription();
            $this->options['FM_AUTOTRANSLATE'] = $map->getAutoTranslate();
            $this->options['FM_SLIMFORM'] = $map->getSlimForm();

            if ($map->getHideBack())
            {
                $this->options['FM_OPTIONS']['hideback'] = 1;
            } else
            {
                unset($this->options['FM_OPTIONS']['hideback']);
            }

            $this->diskWriteOptions($this->options);
            return (true);
        }

        $settingsHtml .= $pm->getPanel($fm->html, 'General');

        $this->getNameFromEntityMap($entity);

        $loopHtmlBuilder = '';

        foreach ($map->getColumns() as $colum)
        {
            $map = new \ajurgensen\phpMagic\map('');
            $map->addColumn(new \ajurgensen\phpMagic\colum('Name', 'Name', 'VARCHAR'));
            $map->addColumn(new \ajurgensen\phpMagic\colum('Excluded', 'Excluded', 'BOOLEAN'));
            $state = 0;
            if (isset($this->options['FM_EXCLUDE']) && in_array($colum->getName(), $this->options['FM_EXCLUDE']))
            {
                $state = 1;
            }
            $map->setExcluded($state);
            if (isset($this->names[$colum->getName()]))
            {
                $map->setName($this->names[$colum->getName()]);
            }
            $editoptions['FM_NAME'] = 'Editing ' . $colum->getName();
            $editoptions['FM_DESC'] = '';

            $fm = new \ajurgensen\phpMagic\formMagic($map, $editoptions, array());

            if ($fm->entitySaved)
            {
                $this->names[$colum->getName()] = $map->getName();

                if ($map->staticVars['Excluded'] == 1)
                {
                    array_push($this->options['FM_EXCLUDE'], $colum->getName());
                } else
                {
                    $this->options['FM_EXCLUDE'] = array_diff($this->options['FM_EXCLUDE'], array($colum->getName()));
                }
                $this->diskWriteOptions($this->options);
                $this->diskWriteNames($this->names);
                return (true);
            }
            $loopHtmlBuilder .= $pm->getPanel($fm->html, $colum->getName());
        }
        $loopHtml = $pm->getPanel($loopHtmlBuilder, 'Fields');

        $fm = new formMagic($entity, $this->options, $this->names);
        $demoHtml = $pm->getPanel($fm->html, 'Form');

        $pm->addRow($pm->getCol($demoHtml, 4) . $pm->getCol($settingsHtml, 4) . $pm->getCol($loopHtml, 4));

        $pm->finalize();
        return (false);
    }

    /**
     * @param $entity
     */
    private function getNameFromEntityMap($entity)
    {
        $map = $entity::TABLE_MAP;
        return($map::getTableMap()->getName());
    }
}
