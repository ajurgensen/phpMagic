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
    public $FromPropel;
    public $whatTypeAreWe;
    private $saved;
    private $adminMode;

    /**
     * @return mixed
     */
    public function getAdminMode()
    {
        return $this->adminMode;
    }

    /**
     * @param mixed $adminMode
     */
    public function setAdminMode($adminMode)
    {
        $this->adminMode = $adminMode;
    }


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

    function __construct()
    {
        $this->setAdminMode(false);
    }


    public function init(&$entity,$viewname='', $namesname='',$added_options=array())
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


        $this->options = array();
        $this->options['FM_NAME'] = 'Name';
        $this->options['FM_OPTIONS']['hideback'] = false;
        $this->options['FM_DESC'] = $this->getNameFromEntityMap($entity);
        $this->options['FM_AUTOTRANSLATE'] = false;
        $this->options['FM_SLIMFORM'] = false;
        $this->options['FM_SENDTEXT'] = 'Send';
        $this->options['FM_LINK'] = array();
        $this->options['FM_EXCLUDE'] = array('created_at', 'updated_at', 'version', 'user_id', 'CREATED_AT', 'UPDATED_AT', 'VERSION', 'USER_ID');

        if (file_exists($this->viewFile))
        {
            $this->diskReadOptions();
        }

        $this->options = array_merge($this->options, $added_options);

        $qs = $_SERVER['QUERY_STRING'];

        if (substr_count($qs,$this->viewname) && substr_count($qs,$this->namesname))
        {
            $this->demoshown = true;
            if ($this->showConfig($entity))
            {
                if ($this->getWhatTypeAreWe() == 'form')
                {
                    $this->doForm($entity);
                }
            }

        }
    }



    public static function convertDiyForm(diyForm $diyForm,$adminEdit=false,$viewname='', $namesname='')
    {
        $map = $diyForm->getMap();

        $form = new configMagic($map,$viewname,$namesname);
        $form->setWhatTypeAreWe('form');
        $form->setAdminMode($adminEdit);
        $form->init($map,$viewname,$namesname);
        if (!$form->demoshown)
        {
            $form->doForm($map);
        }
        return($form);
    }


    public static function newdiyform($name = '',$validationClosure='')
    {
        $diyform = new diyForm($name,$validationClosure);
        return $diyform;
    }

    public static function newform(&$entity,$adminEdit=false,$viewname='', $namesname='',$options=array())
    {
        $form = new configMagic();
        $form->setWhatTypeAreWe('form');
        $form->setAdminMode($adminEdit);
        $form->init($entity,$viewname,$namesname,$options);
        if (!$form->demoshown)
        {
            $form->doForm($entity);
        }
        return($form);
    }

    public static function newlist(&$entity,$adminEdit=false,$viewname='', $namesname='')
    {
        $list = new configMagic($entity,$viewname,$namesname);
        $list->setWhatTypeAreWe('list');
        $list->setAdminMode($adminEdit);
        $list->init($entity,$viewname,$namesname);
        $list->doList($entity);
        return($list);
    }


    private function proccessColum($columName,pageMagic $pm)
    {
        $map = new \ajurgensen\phpMagic\map($columName);
        $map->addColumn(new \ajurgensen\phpMagic\colum('Name', 'VARCHAR'));
        $map->addColumn(new \ajurgensen\phpMagic\colum('Excluded', 'BOOLEAN'));
        $map->addColumn(new \ajurgensen\phpMagic\colum('LinkCol', 'VARCHAR'));

        $state = 0;
        if (isset($this->options['FM_EXCLUDE']) && in_array($columName, $this->options['FM_EXCLUDE']))
        {
            $state = 1;
        }
        $map->setExcluded($state);
        if (isset($this->names[$columName]))
        {
            $map->setName($this->names[$columName]);
        }

        if (isset($this->options['FM_LINK'][$columName]))
        {
            $map->setLinkCol($this->options['FM_LINK'][$columName]);
        }

        $editoptions['FM_NAME'] = 'Editing ' . $columName;
        $editoptions['FM_DESC'] = '';


        $fm = new \ajurgensen\phpMagic\formMagic($map, $editoptions, array());

        if ($fm->entitySaved)
        {
            if (strlen($map->getName()) > 0)
            {
                $this->names[$columName] = $map->getName();
            }
            else
            {
                unset($this->names[$columName]);
            }

            unset($this->options['FM_LINK'][$columName]);
            if ($map->staticVars['LinkCol'])
            {
                $this->options['FM_LINK'] = array_merge($this->options['FM_LINK'], array($columName =>$map->staticVars['LinkCol']));
            }

            if ($map->staticVars['Excluded'] == 1)
            {
                array_push($this->options['FM_EXCLUDE'], $columName);
            } else
            {
                $this->options['FM_EXCLUDE'] = array_diff($this->options['FM_EXCLUDE'], array($columName));
            }


            $this->diskWriteOptions($this->options);
            $this->diskWriteNames($this->names);
            return false;
        }
        return ($pm->getPanel($fm->html, $columName));
    }

    public function doForm(&$entity)
    {

        $fm = new formMagic($entity,$this->getFormOptions(),$this->names);
        $this->setHtml($fm->html);

        if ($this->getAdminMode())
        {
            $this->setHtml($this->getHtml() . '<a href="?'. $this->viewname . $this->namesname.'">setup form</a>');
        }

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

        if ($this->getAdminMode())
        {
            $this->setHtml($this->getHtml() . '<a href="?' . $this->viewname . $this->namesname . '">setup list</a>');
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


        if ($this->getWhatTypeAreWe() == 'form')
        {

            //General settings
            $diyform = configMagic::newdiyform('General Settings');
            $diyform->addText('FormName',128,$this->options['FM_NAME']);
            $diyform->addText('Description',512,$this->options['FM_DESC']);
            $diyform->addBoolean('AutoTranslate',$this->options['FM_AUTOTRANSLATE']);
            $diyform->addBoolean('HideBack',$this->options['FM_OPTIONS']['hideback']);
            $diyform->addBoolean('SlimForm',$this->options['FM_SLIMFORM']);
            $diyform->addText('SendText',128,$this->options['FM_SENDTEXT']);


            $form = configMagic::convertDiyForm($diyform,false,'diyFormSettingsView','diyFormSettingsName');

            if ($form->saved())
            {
                $this->options['FM_NAME'] = $diyform->getValue('FormName');
                $this->options['FM_DESC'] = $diyform->getValue('Description');
                $this->options['FM_AUTOTRANSLATE'] = $diyform->getValue('AutoTranslate');
                $this->options['FM_SLIMFORM'] = $diyform->getValue('SlimForm');
                $this->options['FM_SENDTEXT'] = $diyform->getValue('SendText');
                $this->options['FM_OPTIONS']['hideback'] = $diyform->getValue('HideBack');

                $this->diskWriteOptions($this->options);
                return (true);
            }

            $settingsHtml .= $pm->getPanel($form->getHtml(), 'General');
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
                throw new \Exception("Error: DDF33332");
            }
            $entitymap = $first_ob::TABLE_MAP;
            $entitymap = $entitymap::getTableMap();
            $entityVirtualCols = $first_ob->getVirtualColumns();
        }
        elseif($this->getWhatTypeAreWe() == 'form')
        {

            $entityVirtualCols = array();
            if (method_exists($entity,'toArray'))
            {
                //we are coming from a Propel Object
                $map = $entity::TABLE_MAP;
                $entitymap = $map::getTableMap();
                $entityVirtualCols = $entity->getVirtualColumns();

                $this->FromPropel = true;
            }
            else
            {
                //We are being build manually
                $this->FromPropel = false;
                $entitymap = $entity;

            }
        }

        $loopHtmlBuilder = '';

        foreach ($entitymap->getColumns() as $colum)
        {
            if ($hest = $this->proccessColum($colum->getName(),$pm))
            {
                $loopHtmlBuilder .= $hest;
            }
            else
            {
                return true;
            }
        }

        foreach ($entityVirtualCols as $key => $value)
        {
            if ($hest = $this->proccessColum($key,$pm))
            {
                $loopHtmlBuilder .= $hest;
            }
            else
            {
                return true;
            }
        }


        $loopHtml = $pm->getPanel($loopHtmlBuilder, 'Fields');

        if ($this->getWhatTypeAreWe() == 'form')
        {
            $fm = new formMagic($entity, $this->getFormOptions(), $this->names);
            $demoHtml = $pm->getPanel($fm->html, 'Form');
        }
        elseif($this->getWhatTypeAreWe() == 'list')
        {
            $lm = new listMagic($entity, $this->getListOptions(), $this->names);
            $demoHtml = $pm->getPanel($lm->getHTML(), 'List');
        }

        $pm->addRow($pm->getCol($demoHtml,6) . $pm->getCol($settingsHtml, 3) . $pm->getCol($loopHtml, 3));

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
                throw new \Exception('Error: DDF333555');
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
            $options[$key] = $value;
        }
        return $options;
    }
}
