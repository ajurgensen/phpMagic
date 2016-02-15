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
    var $options;
    var $name;
    var $names;
    var $formNiceName;
    var $formName;
    var $formDesc;


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

    function cleanString($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
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



        //if (method_exists($entity,'toArray'))
        if (method_exists($entity,'configMagic'))
        {
            //configMagicDIY form
            $this->setFromPropel(0);
            $map = $entity;
        }
        else
        {
            //we are coming from a Propel Object - Or so we assume
            $map = $entity::TABLE_MAP;
            $map = $map::getTableMap();
            $this->setFromPropel(1);
        }

        //Get name, nicename and Desc for the form
        if (isset($options['FM_NAME']) && $options['FM_NAME'])
        {
            $this->formNiceName =$options['FM_NAME'];
        }
        elseif (isset($this->options['FM_OPTIONS']['autotranslate']))
        {
            $this->formNiceName = 'FM_' . get_class($entity);
        }
        elseif ((substr_count(get_class($entity),'map')) && $entity->getObjectName())
        {
            $this->formNiceName = $entity->getObjectName();
        }
        else
        {
            $this->formNiceName = get_class($entity);
        }

        $this->formName = $this->cleanString($this->formNiceName);

        if (isset($options['FM_DESC']))
        {
            $this->formDesc = $options['FM_DESC'];
        }
        else
        {
            $this->formDesc = $this->formNiceName;
        }

        //Check if we are being POSTed to and proccess
        if (isset($_POST[$this->formName . '_posted']) && $_POST[$this->formName . '_posted'] == 'true')
        {
            if (1==1 || $this->getFromPropel())
            {
                $errors = $this->handlePostEntity($entity, $map);

                if (!count($errors) && method_exists($entity,'validate'))
                {
                    $validationResult = $entity->validate();
                    if ($validationResult === true)
                    {
                        //We validate
                    }
                    else
                    {
                        $errors['Password'] = $validationResult;
                    }
                }

                if (!count($errors))
                {
                    $entity->save();
                    $this->handlePostEntityAddons($entity, $map, $options);
                    $this->entitySaved = true;
                    $this->entity = $entity;
                    return;
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

        //Do all the magic stuff
        $html = $this->initForm();

        if (1==1 || $this->getFromPropel())
        {
            $html .= $this->buildFormEntity($entity, $options, $names, $debug, $map);
            $html .= $this->buildFormVirtualCols($entity, $options, $names, $debug, $map);
            $html .= $this->buildFormEntityAddons($entity, $options, $map);
            $html .= $this->finalizeForm($errorsText);
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
        if (isset($this->options['FM_AUTOTRANSLATE']) && $this->options['FM_AUTOTRANSLATE'])
        {
            if (!function_exists('translate'))
            {
                throw new \Exception('You must Implement the "translate" function as global to use Autotranslate');
            }
            $nicename = translate('FM_' . $name);
        }
        else
        {
            $nicename = $name;
        }
        $html = '';

        if (1==1 || !isset($this->options['FM_SLIMFORM']) || $this->options['FM_SLIMFORM'] == 0)
        {
            $html = '<li class="list-group-item"><div class="row">';
            $html .= '<div class="col-xs-5">';
            $html .= '<label for="' . $name . '">' . $nicename . ":</label>";
            $html .= '</div>';
            $html .= '<div class="col-xs-7">';
        }

        $html .= $formElement;
        if (1==1 || !isset($this->options['FM_SLIMFORM']) || $this->options['FM_SLIMFORM'] == 0)
        {
            $html .= '</div>';
            $html .= '</div></li>';
        }
        return $html;
    }

    /**
     * @param $map
     * @return string
     */
    private function initForm()
    {
        $html = '';
        if (!isset($this->options['FM_SLIMFORM']) || $this->options['FM_SLIMFORM'] == 0)
        {
            $html = '<div class="panel panel-default">';
            $html .= '<div class="panel-heading"><strong>' . $this->formNiceName . '</strong></div>';
            $html .= '<div class="panel-body"><p>' . $this->formDesc . '</p></div>';
        }
        else
        {
            $html .= '<div>';
        }

        $html .= '<ul class="list-group">';
        $html .= '<form method="post">';
        $html .= '<div class="form-group">';
        return $html;
    }

    /**
     * @param $map
     * @param $html
     */
    private function finalizeForm($error='')
    {
        if (session_status() == PHP_SESSION_NONE)
        {
            session_start();
        }
        $rnd = md5($this->formName . rand(0,1000000000000000000000));
        $_SESSION[$this->formName] = $rnd;

        $back = 'Back';
        if (isset($this->options['FM_SENDTEXT']))
        {
            $send = $this->options['FM_SENDTEXT'];
        }
        else
        {
            $send = 'Send';
        }

        if (isset($this->options['FM_OPTIONS']['autotranslate']))
        {
            $back = translate('FM_' . $this->formName . '_back');
            $send = translate('FM_' . $this->formName . '_send');
        }

        $html = "<input type='hidden' value='true' name='" . $this->formName . '_posted' . "'>";
        $html .= "<input type='hidden' value='". $rnd ."' name='" . $this->formName . '_key' . "'>";
        $html .= '<li class="list-group-item">';
        $html .= "&nbsp";
        $html .= '<div class="btn-group pull-right" role="group" aria-label="...">';
        $html .= "<input type='submit' value='" . $send . "' class='btn btn-success '> &nbsp;";
        $html .= '</div>';

        if (!isset($this->options['FM_OPTIONS']['hideback']))
        {
            $html .= '<div class="btn-group pull-right" role="group" aria-label="...">';
            $html .= '<input type="button" value="' . $back . '" class="btn btn-default" onclick="window.history.back()" />  &nbsp;</div>';
        }

        $html .= '</div>';
        $html .= '</li>';
        if ($error)
        {
            $html .= '<li class="list-group-item">';
            $html .= '<div class="alert alert-danger" >' . $error . '</div >';
            $html .= '</li>';
        }
        $html .= "</form>";
        $html .= '</ul>';
        if (!isset($this->options['FM_SLIMFORM']) || $this->options['FM_SLIMFORM'] == 0)
        {
            $html .= '</div>';
        }

        return($html);
    }

    /**
     * @param $colum
     * @param $value
     * @param string $JSValidaion
     * @return string
     */
    private function addFormInputText($colum, $value, $JSValidaion = '')
    {
        if (isset($this->options['FM_DESCRIPTION'][$colum->getName()]))
        {
            $desc = $this->options['FM_DESCRIPTION'][$colum->getName()];
        }
        elseif (isset($this->options['FM_OPTIONS']['autotranslate']))
        {
            $desc = translate('FM_DESC_' .$colum->getName());
        }
        else
        {
            $desc = "";
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

        $html = $this->addFormElement($colum->getName(), $html);
        return $html;
    }

    private function addDateTimePicker($nicename,$name, \DateTime $value)
    {
        $html = "            <input type='text' name='".$name."' class='form-control' value='".$value->format('m-d-y h:m:s')."' id='datetimepicker". $nicename ."' />

                        <script type='text/javascript'>
            $(function () {
                $('#datetimepicker". $nicename ."').datetimepicker();
            });
        </script>";
        $html = $this->addFormElement($nicename,$html);
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
    private function addFormMultiSelect($nicename,$name, array $optionarray, array $selected, $required = true)
    {
        if (!$required)
        {
            $required = '';
        }
        else
        {
            $required = 'required';
        }
        $count = count($optionarray);
        $html = "<select data-parsley-trigger='change' size='" .$count . "' ". $required ." multiple class='form-control' name='" . $name . "[]'>";
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
        if ((isset($_POST[$map->getName() . '_key']))&&($_POST[$map->getName() . '_key'] !== $_SESSION[$map->getName()]))
        {
            return (array('General' => "Don't Cheat"));
        }

        $errors = array();
        foreach ($map->getColumns() as $colum)
        {
            if (!$colum->isPrimaryKey() && !$colum->isPrimaryString())
            {
                //General validation
                if (isset($_POST[$colum->getName()]))
                {
                    $value = $_POST[$colum->getName()];

                    //General validation
                    if (isset($this->options['FM_VALIDATION_BLOCK'][$colum->getName()]) && $function = $this->options['FM_VALIDATION_BLOCK'][$colum->getName()])
                    {
                        if ($error = call_user_func($function,$value,$entity->getId()))
                        {
                            $errors[$colum->getName()] =  $error  . '<br>';
                        }
                    }

                    //Start the processing and test for types
                    if (substr_count($colum->getName(), 'EMAIL') && $colum->getType() == 'VARCHAR' && strlen($value) <= $colum->getSize() && is_string($value))
                    {
                        if (filter_var($value, FILTER_VALIDATE_EMAIL))
                        {
                            $name = 'set' . $colum->getName();
                            $entity->{$name}($value);
                        } else
                        {
                            $message = ": Not a valid email address";
                            if (isset($this->options['FM_OPTIONS']['autotranslate']))
                            {
                                $message = translate('FM_' . $this->formName . '_eml_er');
                            }

                            $errors[$colum->getName()] = $message . '<br>';
                        }
                    } elseif ($colum->getType() == 'VARCHAR' && strlen($value) <= $colum->getSize() && is_string($value))
                    {
                        $name = 'set' . $colum->getName();
                        try
                        {
                            $entity->{$name}($value);
                        } catch (\Exception $e)
                        {
                            $errors[$colum->getName()] = "Unable to update " . $colum->getName();
                         }
                    }
                    elseif ($colum->getType() == 'FLOAT' && (is_numeric($value) || is_float($value)))
                    {
                        $name = 'set' . $colum->getName();
                        $entity->{$name}($value);
                    }
                    elseif ($colum->getType() == 'INTEGER' && is_numeric($value))
                    {
                        //TODO
                        $name = 'set' . $colum->getPhpName();
                        $entity->{$name}($value);
                    }
                    elseif ($colum->getType() == 'TINYINT' && is_numeric($value) && $value < 256)
                    {
                        $name = 'set' . $colum->getName();
                        $entity->{$name}($value);
                    } elseif ($colum->getType() == 'BOOLEAN' && ($value == 1 || $value == 0))
                    {
                        $name = 'set' . $colum->getName();
                        $entity->{$name}($value);
                    }
                    elseif ($colum->getType() == 'TIMESTAMP')
                    {
                        $name = 'set' . $colum->getName();
                        $entity->{$name}($value);
                    }
                    elseif ($colum->getType() == 'ENUM')
                    {
                        $valueset = $colum->getValueSet();
                        $name = 'set' . $colum->getName();
                        $entity->{$name}($valueset[$value]);
                    }
                    else
                    {
                        $errors[$colum->getName()] = "Error: " . $colum->getName();
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
                if (is_array($addon))
                {
                    $addon = $addon[0];
                }

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
                $required = true;
                if (is_array($addon))
                {
                    if (isset($addon['notrequired']))
                    {
                        $required = false;
                    }
                    $addon = $addon[0];
                }

                //Image-serviceHasImage-User-125');
                if (substr_count($addon, '-') == 3)
                {
                    list($addon_table, $addon_link, $addon_owner, $addon_owner_id) = explode('-', $addon);
                    $name = ucfirst(strtolower($addon_table)) . "Query::create";
                    $remoteEntities = call_user_func($name);
                    $remoteEntities = $remoteEntities->{"filterBy" . $addon_owner . "Id"}($addon_owner_id)->find();

                }
                elseif (substr_count($addon, '-') == 1)
                {
                    list($addon_table, $addon_link) = explode('-', $addon);
                    $name = ucfirst(strtolower($addon_table)) . "Query::create";
                    $remoteEntities = call_user_func($name);
                    $remoteEntities = $remoteEntities->find();
                }
                else
                {
                    throw new \Exception('Misconfigured Addon: ' . $addon);
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


                    $html .= $this->addFormMultiSelect($name, $addon_link, $optionarray, $values,$required);
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
            }
            elseif (!$this->getFromPropel())
            {
                $name = 'get' . $colum->getName();
                if (!$value = $entity->{$name}())
                {
                    $value = '';
                }
            }
            else
            {
                //TODO - phpname only needed for a few cases
                $name = 'get' . $colum->getPhpName();
                if (!$value = $entity->{$name}())
                {
                    $value = '';
                }
            }
            //If we have a Name override use it
            if (isset($names[$colum->getName()]))
            {
                //We got it!
                $newname = $names[$colum->getName()];
            }
            else
            {
                $newname = $colum->getName();
            }

            //Ok, start the colunm processing
            if (isset($options['FM_EXCLUDE']) && in_array($colum->getName(), $options['FM_EXCLUDE']))
            {
                //We were blacklisted
            }
            elseif (in_array($colum->getName(), array('id','ID')))
            {
                //Trying to detect ID col
            }
            //name hack
            elseif (isset($options[$colum->getName()]) && $colum->getName() !== 'NAME')
            {
                //We are have custom Optionset for this one
                $html .= $this->addFormSelect($newname, $colum->getName(), $options[$colum->getName()], $value);
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
                        try
                        {
                            $optionarray[$out->getId()] = $out->getName();
                        }
                        catch (\Exception $e)
                        {

                        }
                    }
                    if (!isset($newname))
                    {
                        $newname = ($colum->getRelatedTableName());
                    }
                }
                $html .= $this->addFormSelect($newname, $colum->getName(), $optionarray, $value);
            }
            elseif (substr_count($colum->getName(), 'EMAIL') && $colum->getType() == 'VARCHAR')
            {
                //We have an email field
                $html .= $this->addFormInputText($colum, $value, ' type="email" data-parsley-trigger="change" required ');
            }
            elseif (substr_count($colum->getName(), 'PASSWORD') && $colum->getType() == 'VARCHAR')
            {
                //We have an PASSWORD field
                $html .= $this->addFormInputText($colum, $value, ' type="password" data-parsley-trigger="change" required ');
            }
            elseif ($colum->getType() == 'VARCHAR')
            {
                //normal Varchar
                $require = '';
                if ($this->fromPropel && $colum->isNotNull())
                {$require = 'required';}

                $html .= $this->addFormInputText($colum, $value, ' data-parsley-trigger="change" ' . $require . ' ');
            }
            elseif ($colum->getType() == 'INTEGER')
            {
                //Int
                $require = '';
                if ($this->fromPropel && $colum->isNotNull())
                {$require = 'required';}

                $html .= $this->addFormInputText($colum, $value, ' data-parsley-trigger="change" ' . $require . ' ');
            }
            elseif ($colum->getType() == 'TINYINT')
            {
                //Tinyfucker
                $require = '';
                if ($this->fromPropel && $colum->isNotNull())
                {$require = 'required';}

                $html .= $this->addFormInputText($colum, $value, ' data-parsley-trigger="change" ' . $require . ' ');
            }
            elseif ($colum->getType() == 'FLOAT')
            {
                //normal float
                $require = '';
                if ($this->fromPropel && $colum->isNotNull())
                {$require = 'required';}

                $html .= $this->addFormInputText($colum, $value, ' type="number" step="0.01" data-parsley-trigger="change" ' . $require . ' ');
            }
            elseif ($colum->getType() == 'BOOLEAN')
            {
                //Boolean
                $yes = 'yes';
                $no = 'no';

                if (isset($this->options['FM_OPTIONS']['autotranslate']))
                {
                    $yes = translate('FM_' . $this->formName . '_true');
                    $no = translate('FM_' . $this->formName . '_false');
                }
                $html .= $this->addFormSelect($newname, $colum->getName(), array(0 => $no, 1 => $yes), $value);
            }
            elseif ($colum->getType() == 'TIMESTAMP')
            {
                if (!is_a($value, 'DateTime'))
                {
                    $value = new \DateTime();
                }
                $html .= $this->addDateTimePicker($newname,$colum->getName(),$value);

            }
            elseif ($colum->getType() == 'ENUM')
            {
                $selectedValueCode = array_search($value,$colum->getValueSet());

                $html .= $this->addFormSelect($newname,$colum->getName(),$colum->GetValueSet(),$selectedValueCode);
            }
            elseif ($debug)
            {
                //Give hits in debug mode
                $html .= $this->addFormElement($colum->getName(), 'Debug - Add ' . $colum->getName());
            }
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
    private function buildFormVirtualCols($entity, $options, $names, $debug, $map)
    {
        $cols = $entity->getVirtualColumns();
        $html = '';
        if (!is_array($cols))
        {
            return('');
        }
        $html = '';
        foreach ($cols as $col => $value)
        {
            //If we have POST data and are here, it's an error situation, use that data and not DB data
            if (isset($_POST[$col]))
            {
                $value = $_POST[$col];
            }

            $name = $this->cleanString($col);
            $nicename = $col;

            $type = 'text';
            if (substr_count(strtolower($col),'password'))
            {
                $type = 'password';
            }

            $addhtml = "<input size=32 data-parsley-trigger='change' required placeholder='" . $nicename . "' class='form-control' value='" . $value . "' type='". $type ."' name='" . $name . "'>";
            $html .= $this->addFormElement($nicename, $addhtml);


        }
        return $html;
    }

}
