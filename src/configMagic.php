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
    public $viewname;
    public $demoshown;
    public $namesname;
    public $whatTypeAreWe;
    private $saved;


    public function saved()
    {
        return($this->saved);
    }


    /**
     * @return mixed
     */
    public function getWhatTypeAreWe()
    {
        return $this->whatTypeAreWe;
    }

    /**
     * @param mixed $whatTypeAreWe
     */
    public function setWhatTypeAreWe($whatTypeAreWe)
    {
        $this->whatTypeAreWe = $whatTypeAreWe;
    }

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

    function __construct(&$entity,$viewname='', $namesname='')
    {
    }


    private function init(&$entity,$viewname='', $namesname='')
    {
        $this->demoshown = false;
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
        $this->saved = false;
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
            $this->options['NAME'] = 'Name';
            $this->options['OPTIONS']['hideback'] = false;
            $this->options['DESC'] = $this->getNameFromEntityMap($entity);
            $this->options['AUTOTRANSLATE'] = false;
            $this->options['SLIMFORM'] = false;
            $this->options['LINK'] = array();
            $this->options['EXCLUDE'] = array('created_at', 'updated_at', 'version', 'user_id', 'CREATED_AT', 'UPDATED_AT', 'VERSION', 'USER_ID');
            $this->diskWriteOptions();
        }
        else
        {
            $this->diskReadOptions();
        }

        $qs = $_SERVER['QUERY_STRING'];

        if (substr_count($qs,$this->viewname) && substr_count($qs,$this->namesname))
        {
            $this->demoshown = true;
            $this->showConfig($entity);
        }
    }


    public static function newform(&$entity,$viewname='', $namesname='')
    {
        $form = new configMagic($entity,$viewname,$namesname);
        $form->setWhatTypeAreWe('form');
        $form->init($entity,$viewname,$namesname);
        $form->doForm($entity);
        return($form);
    }

    public static function newlist(&$entity,$viewname='', $namesname='')
    {
        $list = new configMagic($entity,$viewname,$namesname);
        $list->setWhatTypeAreWe('list');
        $list->init($entity,$viewname,$namesname);
        $list->doList($entity);
        return($list);
    }


    public function doForm(&$entity)
    {

        $fm = new formMagic($entity,$this->getFormOptions(),$this->names);
        $this->setHtml($fm->html);

        $this->setHtml($this->getHtml() . '<a href="?'. $this->viewname . $this->namesname.'">hest</a>');

        if ($fm->entitySaved)
        {
            $this->saved = true;
        }
        return;
    }

    public function doList(&$entity)
    {
        $lm = new listMagic($entity,$this->getListOptions(),$this->names);
        $this->setHtml($lm->getHTML());

        $this->setHtml($this->getHtml() . '<a href="?'. $this->viewname . $this->namesname.'">hest</a>');

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


        if ($this->getWhatTypeAreWe() == 'form')
        {
            //General settings
            $map = new \ajurgensen\phpMagic\map('General Settings');
            $map->addColumn(new \ajurgensen\phpMagic\colum('Name', 'Name', 'VARCHAR'));
            $map->addColumn(new \ajurgensen\phpMagic\colum('Description', 'Description', 'VARCHAR'));
            $map->addColumn(new \ajurgensen\phpMagic\colum('AutoTranslate', 'AutoTranslate', 'BOOLEAN'));
            $map->addColumn(new \ajurgensen\phpMagic\colum('HideBack', 'HideBack', 'BOOLEAN'));
            $map->addColumn(new \ajurgensen\phpMagic\colum('SlimForm', 'SlimForm', 'BOOLEAN'));

            $map->setName($this->options['NAME']);
            $map->setDescription($this->options['DESC']);
            $map->setAutoTranslate($this->options['AUTOTRANSLATE']);
            $map->setSlimForm($this->options['SLIMFORM']);

            if (isset($this->options['OPTIONS']['hideback']))
            {
                $map->setHideBack(1);
            }

            $editoptions = array();
            $editoptions['NAME'] = 'General Settings';
            $editoptions['DESC'] = '';

            $fm = new \ajurgensen\phpMagic\formMagic($map, $editoptions, array());

            if ($fm->entitySaved)
            {
                $this->options['NAME'] = $map->getName();
                $this->options['DESC'] = $map->getDescription();
                $this->options['AUTOTRANSLATE'] = $map->getAutoTranslate();
                $this->options['SLIMFORM'] = $map->getSlimForm();

                if ($map->getHideBack())
                {
                    $this->options['OPTIONS']['hideback'] = 1;
                } else
                {
                    unset($this->options['OPTIONS']['hideback']);
                }

                $this->diskWriteOptions($this->options);
                return (true);
            }

            $settingsHtml .= $pm->getPanel($fm->html, 'General');
        }



        if ($this->getWhatTypeAreWe() == 'list')
        {
            if (count($entity))
            {
                foreach ($entity as $first_ob)
                {
                    break;
                }

            }
            else
            {
                die('Error: DDF33332');
            }
            $entitymap = $first_ob::TABLE_MAP;
            $entitymap = $entitymap::getTableMap();

        }
        elseif($this->getWhatTypeAreWe() == 'form')
        {
            $entitymap = $entity::TABLE_MAP;
            $entitymap = $entitymap::getTableMap();
        }




        $loopHtmlBuilder = '';

        foreach ($entitymap->getColumns() as $colum)
        {
            $map = new \ajurgensen\phpMagic\map($colum->getName());
            $map->addColumn(new \ajurgensen\phpMagic\colum('Name', 'Name', 'VARCHAR'));
            $map->addColumn(new \ajurgensen\phpMagic\colum('Excluded', 'Excluded', 'BOOLEAN'));
            $map->addColumn(new \ajurgensen\phpMagic\colum('LinkCol', 'LinkCol', 'VARCHAR'));

            $state = 0;
            if (isset($this->options['EXCLUDE']) && in_array($colum->getName(), $this->options['EXCLUDE']))
            {
                $state = 1;
            }
            $map->setExcluded($state);
            if (isset($this->names[$colum->getName()]))
            {
                $map->setName($this->names[$colum->getName()]);
            }

            if (isset($this->options['LINK'][$colum->getName()]))
            {
                $map->setLinkCol($this->options['LINK'][$colum->getName()]);
            }




            $editoptions['NAME'] = 'Editing ' . $colum->getName();
            $editoptions['DESC'] = '';
            $editoptions['FM_SLIMFORM'] = TRUE;


            $fm = new \ajurgensen\phpMagic\formMagic($map, $editoptions, array());

            if ($fm->entitySaved)
            {
                if (strlen($map->getName()) > 0)
                {
                    $this->names[$colum->getName()] = $map->getName();
                }
                else
                {
                    unset($this->names[$colum->getName()]);
                }

                unset($this->options['LINK'][$colum->getName()]);
                if ($map->staticVars['LinkCol'])
                {
                    $this->options['LINK'] = array_merge($this->options['LINK'], array($colum->getName() =>$map->staticVars['LinkCol']));
                }

                if ($map->staticVars['Excluded'] == 1)
                {
                    array_push($this->options['EXCLUDE'], $colum->getName());
                } else
                {
                    $this->options['EXCLUDE'] = array_diff($this->options['EXCLUDE'], array($colum->getName()));
                }
                $this->diskWriteOptions($this->options);
                $this->diskWriteNames($this->names);
                return (true);
            }
            $loopHtmlBuilder .= $pm->getPanel($fm->html, $colum->getName());
        }
        $loopHtml = $pm->getPanel($loopHtmlBuilder, 'Fields');

        if ($this->getWhatTypeAreWe() == 'form')
        {
            $demoHtml = '';
            //$fm = new formMagic($entity, $this->options, $this->names);
            //$demoHtml = $pm->getPanel($fm->html, 'Form');
        }
        elseif($this->getWhatTypeAreWe() == 'list')
        {
            $lm = new listMagic($entity, $this->getListOptions(), $this->names);
            $demoHtml = $pm->getPanel($lm->getHTML(), 'List');
        }

        $pm->addRow($pm->getCol($demoHtml, 4) . $pm->getCol($settingsHtml, 4) . $pm->getCol($loopHtml, 4));

        $pm->finalize();
        return (false);
    }

    /**
     * @param $entity
     */
    private function getNameFromEntityMap($entity)
    {
        if ($this->whatTypeAreWe == 'form')
        {
            $name = get_class($entity);
        }
        else
        {
            if (count($entity))
            {
                foreach ($entity as $first_ob)
                {
                    break;
                }
                $name = get_class($first_ob);
            }
            else
            {
                die('Error: DDF333555');
            }
        }

        return($name);
    }

    /**
     * @return array
     */
    private function getListOptions()
    {
        $options = array();
        foreach ($this->options as $key => $value)
        {
            $options['LM_' . $key] = $value;
        }
        return $options;
    }

    /**
     * @return array
     */
    private function getFormOptions()
    {
        $options = array();
        foreach ($this->options as $key => $value)
        {
            $options['FM_' . $key] = $value;
        }
        return $options;
    }
}