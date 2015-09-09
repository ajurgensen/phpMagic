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

class formMagic
{
    var $entitySaved;
    var $html;
    var $entity;
    var $fromPropel;
    var $passAlongObjects;
    var $name;
    var $names;


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

    /**
     * @param Array $entity Entity to build form from
     * @param Array $options FM_DESCRIPTION FM_OPTIONS  FM_ADDONS FM_NAME FM_DESC FM_EXCLUDE
     * @param Array $names array of 'tablename' => 'Name Shown'
     * @param int $debug Enable debug or not - Defaults to not
     */
    function __construct($entity, $options,$names,$debug = 0)
    {
        //Only set this to true once we are validated
        $this->entitySaved = false;
        $errorsText = '';
        $this->names = $names;
        $this->options = $options;

        if ($entity->toArray())
        {
            //we are coming from a Propel Object
            $map = $entity::TABLE_MAP;
            $map = $map::getTableMap();
            $this->setFromPropel(1);
        }
        else
        {
            //We are being build manually
            $this->setFromPropel(0);
            $map = $entity;
        }
        $this->name = $map->getName();

        //Check if we are being POSTed to and proccess
        if (isset($_POST[$this->name . '_posted']) && $_POST[$this->name . '_posted'] == 'true')
        {
            if (1==1 || $this->getFromPropel())
            {
                $errors = $this->handlePostEntity($entity, $map);
                if (!count($errors))
                {
                    $entity->save();
                    $this->handlePostEntityAddons($entity, $map, $options);
                    $this->entitySaved = true;
                    $this->entity = $entity;
                    return true;
                }
                else
                {
                    foreach ($errors as $key => $value)
                    {
                        $errorsText .= " " . $value . " ";
                    }
                }
            }
        }


        //Get name and Desc for the form
        if (isset($options['FM_NAME']))
        {
            $name = $options['FM_NAME'];
        }
        else
        {
            $name = ucfirst($this->name);
        }

        if (isset($options['FM_DESC']))
        {
            $desc = $options['FM_DESC'];
        }
        else
        {
            $desc = 'Edit ' . $this->name;
        }

        //Do all the magic stuff
        $html = $this->initForm($name,$desc);

        if (1==1 || $this->getFromPropel())
        {

            $html .= $this->buildFormEntity($entity, $options, $names, $debug, $map);
            $html .= $this->buildFormEntityAddons($entity, $options, $map);
            $html .= $this->finalizeForm($this->name, $errorsText);
        }
        $this->html = $html;

        return true;
    }

    /**
     * @param $colum
     * @param $value
     * @return string
     */
    private function addFormElement($name,$formElement)
    {
        if (isset($this->options['FM_OPTIONS']['autotranslate']))
        {
            $nicename = translate('FM_' . $name);
        }
        else
        {
            $nicename = $name;
        }
        $html = '<li class="list-group-item"><div class="row">';
        $html .= '<div class="col-xs-5">';
        $html .= '<label for="' . $name . '">' . $nicename . ":</label>";
        $html .= '</div>';
        $html .= '<div class="col-xs-7">';
        $html .= $formElement;
        $html .= '</div>';
        $html .= '</div></li>';
        return $html;
    }

    /**
     * @param $map
     * @return string
     */
    private function initForm($name,$desc)
    {
        $html = '<div class="panel panel-default">';
        $html .= '<div class="panel-heading"><strong>' . $name . '</strong></div>';
        $html .= '<div class="panel-body"><p>' . $desc . '</p></div>';
        $html .= '<ul class="list-group">';
        $html .= '<form method="post">';
        $html .= '<div class="form-group">';
        return $html;
    }

    /**
     * @param $map
     * @param $html
     */
    private function finalizeForm($name,$error='')
    {
        if (session_status() == PHP_SESSION_NONE)
        {
            session_start();
        }
        $rnd = md5($name . rand(0,1000000000000000000000));
        $_SESSION[$name] = $rnd;

        $html = "<input type='hidden' value='true' name='" . $name . '_posted' . "'>";
        $html .= "<input type='hidden' value='". $rnd ."' name='" . $name . '_key' . "'>";
        $html .= '<li class="list-group-item">';
        $html .= "&nbsp";
        $html .= '<div class="btn-group pull-right" role="group" aria-label="...">';
        $html .= "<input type='submit' value='Send' class='btn btn-success '> &nbsp;";
        $html .= '</div>';
        $html .= '<div class="btn-group pull-right" role="group" aria-label="...">';
        $html .= '<input type="button" value="Back" class="btn btn-default" onclick="window.history.back()" />  &nbsp;';
        $html .= '</div>';
        $html .= '</li>';
        if ($error)
        {
            $html .= '<li class="list-group-item">';
            $html .= '<div class="alert alert-danger" >' . $error . '</div >';
            $html .= '</li>';
        }
        $html .= '</div>';
        $html .= "</form>";
        $html .= '</ul>';
        $html .= '</div>';
        return($html);
    }

    /**
     * @param $colum
     * @param $value
     * @return string
     */
    private function addFormInputText($colum, $value,$options, $JSValidaion = '')
    {
        if (isset($options['FM_DESCRIPTION'][$colum->getName()]))
        {
            $desc = $options['FM_DESCRIPTION'][$colum->getName()];
        }
        elseif (isset($options['FM_OPTIONS']['autotranslate']))
        {
            $desc = translate('FM_' . $colum->getTableName() . '_' . $colum->getName());

        }
        else
        {
            $desc = $colum->getPhpName();
        }


        $max = $colum->getSize();
        if ($max < 257)
        {
            $html = "<input size=32 " . $JSValidaion . " placeholder='" . $desc . "' class='form-control' value='" . $value . "' type='text' name='" . $colum->getName() . "'>";
        }
        else
        {
            $html = '<textarea ' . $JSValidaion . ' placeholder="' . $desc . '" class="form-control" name="' . $colum->getName() . '" rows="4" cols="50">' . $value .  '</textarea>';
        }

        $html = $this->addFormElement($colum->getPhpName(), $html);
        return $html;
    }

    /**
     * @param $name
     * @param array $optionarray
     * @param $value
     * @return string
     */
    private function addFormSelect($nicename,$name, array $optionarray, $selected)
    {
        $html = "<select class='form-control' name='" . $name . "'>";
        foreach ($optionarray as $key => $value)
        {
            $sel = '';
            if ($selected == $key)
            {
                $sel = 'selected';
            }
            $html .= '<option ' . $sel . ' value="' . $key .'">' . $value . '</option>';
        }
        $html .="</select>";
        $html = $this->addFormElement($nicename,$html);
        return $html;
    }
    /**
     * @param $name
     * @param array $optionarray
     * @param $value
     * @return string
     */
    private function addFormMultiSelect($nicename,$name, array $optionarray, array $selected)
    {
        $count = count($optionarray);
        $html = "<select data-parsley-trigger='change' size='" .$count . "' required multiple class='form-control' name='" . $name . "[]'>";
        foreach ($optionarray as $key => $value)
        {
            $sel = '';
            if (in_array($key,$selected))
            {
                $sel = 'selected';
            }
            $html .= '<option ' . $sel . ' value="' . $key .'">' . $value . '</option>';
        }
        $html .="</select>";
        $html = $this->addFormElement($nicename,$html);
        return $html;
    }

    /**
     * @param $entity
     * @param $map
     */
    private function handlePostEntity(&$entity, $map)
    {
        if (session_status() == PHP_SESSION_NONE)
        {
            session_start();
        }
        if ($_POST[$map->getName() . '_key'] !== $_SESSION[$map->getName()])
        {
            return (array('General' => "Don't Cheat"));
        }

        $errors = array();
        foreach ($map->getColumns() as $colum)
        {
            if (!$colum->isPrimaryKey() && !$colum->isPrimaryString())
            {
                if (isset($_POST[$colum->getName()]))
                {
                    $value = $_POST[$colum->getName()];
                    if (substr_count($colum->getName(), 'EMAIL') && $colum->getType() == 'VARCHAR' && strlen($value) <= $colum->getSize() && is_string($value))
                    {
                        if (filter_var($value, FILTER_VALIDATE_EMAIL))
                        {
                            $name = 'set' . $colum->getPhpName();
                            $entity->{$name}($value);
                        } else
                        {
                            $errors[$colum->getPhpName()] = ': Not a valid email address <br>';
                        }
                    } elseif ($colum->getType() == 'VARCHAR' && strlen($value) <= $colum->getSize() && is_string($value))
                    {
                        $name = 'set' . $colum->getPhpName();
                        try
                        {
                            $entity->{$name}($value);
                        } catch (\Exception $e)
                        {
                            $errors[$colum->getPhpName()] = "Unable to update " . $colum->getPhpName();
                         }
                    } elseif ($colum->getType() == 'INTEGER' && is_numeric($value))
                    {
                        $name = 'set' . $colum->getPhpName();
                        $entity->{$name}($value);
                    } elseif ($colum->getType() == 'TINYINT' && is_numeric($value) && $value < 256)
                    {
                        $name = 'set' . $colum->getPhpName();
                        $entity->{$name}($value);
                    } elseif ($colum->getType() == 'BOOLEAN' && ($value == 1 || $value == 0))
                    {
                        $name = 'set' . $colum->getPhpName();
                        $entity->{$name}($value);
                    }else
                    {
                        $errors[$colum->getPhpName()] = "Error: " . $colum->getPhpName();
                    }
                }
            }
        }
        return($errors);
    }
    private function handlePostEntityAddons(&$entity, $map,$options)
    {
        if (isset($options['FM_ADDONS']))
        {
            foreach ($options['FM_ADDONS'] as $addon)
            {
                //Image-serviceHasImage-User-125');
                list($addon_table, $addon_link) = explode('-', $addon);

                //Delete all ServiceHasImage for this service
                $table_link = call_user_func(ucfirst(strtolower($addon_link)) . 'Query::create');
                $table_link->{'filterBy' . $map->getName()}($entity)->delete();

                //For each new link
                if (isset($_POST[$addon_link]))
                {
                    foreach ($_POST[$addon_link] as $id)
                    {
                        //Create new serviceHasImage
                        $tablename = ucfirst(strtolower($addon_link));
                        $new_link = new $tablename;

                        $new_link->{'set' . $addon_table . 'Id'}($id);
                        $new_link->{'set' . $map->getName() . 'Id'}($entity->getId());
                        $new_link->save();
                    }
                }
            }
        }
    }

    /**
     * @param $entity
     * @param $options
     * @param $map
     * @param $html
     * @return string
     */
    private function buildFormEntityAddons($entity, $options, $map)
    {
        $html = '';
        if (isset($options['FM_ADDONS']))
        {
            foreach ($options['FM_ADDONS'] as $addon)
            {
                //Image-serviceHasImage-User-125');
                if (substr_count($addon, '-') == 3)
                {
                    list($addon_table, $addon_link, $addon_owner, $addon_owner_id) = explode('-', $addon);
                    $name = ucfirst(strtolower($addon_table)) . "Query::create";
                    $remoteEntities = call_user_func($name);
                    $remoteEntities = $remoteEntities->{"filterBy" . $addon_owner . "Id"}($addon_owner_id)->find();

                } elseif (substr_count($addon, '-') == 1)
                {
                    list($addon_table, $addon_link) = explode('-', $addon);
                    $name = ucfirst(strtolower($addon_table)) . "Query::create";
                    $remoteEntities = call_user_func($name);
                    $remoteEntities = $remoteEntities->find();
                }

                $name = ucfirst(strtolower($addon_table)) . "Query::create";
                $selectedEntities = call_user_func($name);
                $selectedEntities = $selectedEntities->{'use' . ucfirst(strtolower($addon_link)) . 'Query'}()->{'filterBy' . $map->getName() . 'Id'}($entity->getId())->endUse()->find();

                $values = array();
                if ($selectedEntities)
                {
                    foreach ($selectedEntities as $out)
                    {
                        $values[] = $out->getId();
                    }
                }


                $optionarray = array();
                if ($remoteEntities)
                {
                    foreach ($remoteEntities as $out)
                    {
                        $optionarray[$out->getId()] = $out->getName();
                    }

                    if (isset($this->names[$addon_table]))
                    {
                        $name = $this->names[$addon_table];
                    } else
                    {
                        $name = $addon_table;
                    }


                    $html .= $this->addFormMultiSelect($name, $addon_link, $optionarray, $values);
                }
            }
            return $html;
        }
        return $html;
    }

    /**
     * @param $entity
     * @param $options
     * @param $names
     * @param $debug
     * @param $map
     * @param $html
     * @return string HTML
     */
    private function buildFormEntity($entity, $options, $names, $debug, $map)
    {
        $html = '';
        foreach ($map->getColumns() as $colum)
        {
            //If we have POST data and are here, it's an error situation, use that data and not DB data
            if (isset($_POST[$colum->getName()]))
            {
                $value = $_POST[$colum->getName()];
            } else
            {
                $name = 'get' . $colum->getPhpName();
                if ($this->getFromPropel())
                {
                    $value = $entity->{$name}();
                } else
                {
                    $value = '';
                }
            }
            //If we have a Name override use it
            if (isset($names[$colum->getName()]) && $newname = $names[$colum->getName()])
            {
                $colum->setPhpName($newname);
            }

            //Ok, start the colunm processing
            if (isset($options['FM_EXCLUDE']) && in_array($colum->getName(), $options['FM_EXCLUDE']))
            {
                //We were blacklisted
            }
            elseif (isset($options[$colum->getName()]))
            {
                //We are have custom Optionset for this one
                $html .= $this->addFormSelect($colum->getPhpName(), $colum->getName(), $options[$colum->getName()], $value);
            }
            elseif ($this->getFromPropel() && $colum->isForeignKey())
            {
                //It's a foreign key - try to do magic!
                $optionarray = array();
                $name = 'get' . $colum->getRelatedTableName() . 'sRelatedBy' . $colum->getTableName() . 'Id';
                try
                {
                    $remoteEntities = $entity->{$name}();
                }
                catch (\Exception $e)
                {
                    $name = ucfirst(strtolower($colum->getRelatedTableName())) . "Query::create";
                    $remoteEntities = call_user_func($name, 'find');
                }
                if ($remoteEntities)
                {
                    foreach ($remoteEntities as $out)
                    {
                        $optionarray[$out->getId()] = $out->getName();
                    }
                    if (!isset($newname))
                    {
                        $colum->setPhpName($colum->getRelatedTableName());
                    }
                }
                $html .= $this->addFormSelect($colum->getPhpName(), $colum->getName(), $optionarray, $value);
            }
            elseif (substr_count($colum->getName(), 'EMAIL') && $colum->getType() == 'VARCHAR')
            {
                //We have an email field
                $html .= $this->addFormInputText($colum, $value, $options, ' type="email" data-parsley-trigger="change" required ');
            }
            elseif (substr_count($colum->getName(), 'PASSWORD') && $colum->getType() == 'VARCHAR')
            {
                //We have an PASSWORD field
                $html .= $this->addFormInputText($colum, $value, $options, ' type="password" data-parsley-trigger="change" required ');
            }
            elseif ($colum->getType() == 'VARCHAR')
            {
                //normal Varchar
                $require = '';
                if ($this->fromPropel && $colum->isNotNull())
                {$require = 'required';}

                $html .= $this->addFormInputText($colum, $value, $options, ' data-parsley-trigger="change" '. $require .' ');
            }
            elseif ($colum->getType() == 'BOOLEAN')
            {
                //Boolean
                $html .= $this->addFormSelect($colum->getPhpName(), $colum->getName(), array(0 => 'No', 1 => 'Yes'), $value);
            }
            elseif ($debug)
            {
                //Give hits in debug mode
                $html .= $this->addFormElement($colum->getName(), 'Debug - Add ' . $colum->getName());
            }
        }
        return $html;
    }
}
