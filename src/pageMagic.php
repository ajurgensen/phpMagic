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

class pageMagic
{
    private $html;
    private $pageTitle;
    public $loginStatus;
    private $pw_array;
    private $uploadedFile;
    private $defaultInitiate;
    private $uploadHandled;
    public $AsseticJS;
    public $AsseticCSS;
    public $JSfile;
    public $CSSfile;
    private $needFileReWrite;
    private $validationClosure;
    public $authenticatedId;

    public static function staticTest()
    {
        return 'Hello World, Composer!';
    }

    public function getHTML()
    {
        return($this->html);
    }

    private function sessionCheck()
    {
        session_start();

        if
        (
            isset($_SESSION['fingerprint']) && isset($_SESSION['intiated']) && isset($_SESSION['authenticatedId']) &&
            $_SESSION['fingerprint'] == $this->sessionForUser() &&
            is_int($_SESSION['intiated']) &&
            time() - $_SESSION['intiated'] < 60 * 60 * 24 * 7 &&
            $this->authenticatedId = $_SESSION['authenticatedId']
        )
        {
            return true;
        }

        if ($this->doLogin())
        {
            return true;
        }
        return false;
    }

    private function sessionForUser()
    {
        return md5($_SERVER['HTTP_USER_AGENT'] . ' SOME SALT VALUE 123 '.$_SERVER['HTTP_HOST'] );
    }

    public function addFileUploader($title='',$autotranslate=0)
    {

        if ($autotranslate)
        {
            $translate  =     ',browseLabel: "'. translate('file_upload_browse') .'"';
            $translate .=     ',uploadLabel: "'. translate('file_upload_upload') .'"';
            $translate .=     ',removeLabel: "'. translate('file_upload_remove') .'"';
        }
        else
        {
            $translate = '';
        }

        $translate .=     ',uploadClass: "btn btn-success"';


        $html =
'<form method="post" enctype="multipart/form-data" name="fileUploadForm">
<input id="input-4" type="file" multiple=true class="file-loading" name="uploadFile">
<script>
$(document).on(\'ready\', function() {
    $("#input-4").fileinput({showCaption: false' . $translate . '});
});
</script>
</form>';
        $this->addPanel($html,$title);
    }

    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }

    public function includeJSfile($jsFile)
    {
        $string = '<script language="javascript" type="text/javascript" src="'.$jsFile.'">';
        $this->addHtml($string);
        return;
    }

    private function handleFileUpload()
    {
        if (isset($_FILES['uploadFile']))
        {
            $this->uploadedFile = $_FILES['uploadFile'];
        }
        $this->uploadHandled = true;
    }

    public function fileUploaded()
    {
        if (!$this->uploadHandled)
        {
            $this->handleFileUpload();
        }
        if ($this->uploadedFile)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function addImage($image)
    {
        $html = '<img src="'.$image.'">';
        $this->addPanel($html);
    }

    private function doLogin()
    {

        $diyform = configMagic::newdiyform('Login',$this->validationClosure);

        $diyform->addText('Login');
        $diyform->addText('PASSWORD');

        $form = configMagic::convertDiyForm($diyform,false,'pm_login_FormView','pm_login_FormName');

        if ($form->saved())
        {
            //Form was returned and server side validated
            $_SESSION['fingerprint'] = $this->sessionForUser();
            $_SESSION['intiated'] = time();
            $_SESSION['authenticatedId'] = $this->authenticatedId;
            return true;
        }
        else
        {
            //Form not returned, echo login HTML and return false
            $this->addHtml($form->getHtml());
            $this->finalize();
            return false;
        }
    }

    public function addRow($content)
    {
        $this->addHtml($this->getRow($content));
    }

    public function getRow($content)
    {
        $html ='<div class="row">'.$content.'</div>';
        return $html;
    }

    public function addCol($content,$width)
    {
        $this->addHtml($this->getCol($content,$width));
    }

    public function getCol($content,$width)
    {
        $html ='<div class="col-md-'.$width.'">'.$content.'</div>';
        return $html;
    }

    public function getPanel($text,$title='')
    {
        $html = '<div class="panel panel-default">';
        if ($title)
        {
            $html .= '<div class="panel-heading">'. $title .'</div>';
        }
        $html .= '<div class="panel-body">' . $text . '</div>';
        $html .= '</div>';
        return($html);
    }


    public function getButton($text,$link='')
    {
        $html ='';
        if ($link)
        {
            $html .= '<a href="'.$link.'">';
        }
        $html .= '<button type="button" class="btn btn-default">'.$text.'</button>';
        if ($link)
        {
            $html .= '</a>';
        }
        return($html);
    }

    public function getButtonGroup(array $buttonGroup)
    {

        $html = '<div class="btn-group" role="group" aria-label="...">';
        foreach ($buttonGroup as $button)
        {
            $html .= $button;
        }
        $html .= '</div>';
        return($html);
    }


    public function addPanel($text,$title='')
    {
        $this->addHtml($this->getPanel($text,$title));
    }

    public function addMenu($menu,$brand='',$rightmenu='')
    {
        $this->addHtml('<nav class="navbar navbar-default">
        <div class="container-fluid">

        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="navbar" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">'.$brand.'</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
              <ul class="nav navbar-nav">');

        foreach ($menu as $heading => $submenu)
        {
            if ($heading == 'PM_FORM')
            {

                $this->addHtml($submenu);
            }
            elseif (is_array($submenu))
            {
                $this->addHtml('<ul class="nav navbar-nav navbar-left">
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">&nbsp; ' . $heading . '<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">');
                foreach ($submenu as $submenuheading => $link)
                {

                    $this->addHtml('<li><a href="' . $link . '"><span>&nbsp; ' . $submenuheading . '</span></a></li>');
                }
            }
            else
            {
                $this->addHtml('<li><a href="'.$submenu.'">'.$heading.'</a></li>');
            }

            $this->addHtml('</ul>
              </li>');

        }

        if ($rightmenu)
        {

            foreach ($rightmenu as $heading => $submenu)
            {
                if ($heading == 'PM_FORM')
                {

                    $this->addHtml($submenu);
                }
                elseif (is_array($submenu))
                {
                    $this->addHtml('<ul class="nav navbar-nav navbar-right">
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">&nbsp; ' . $heading . '<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">');
                    foreach ($submenu as $submenuheading => $link)
                    {

                        $this->addHtml('<li><a href="' . $link . '"><span>&nbsp; ' . $submenuheading . '</span></a></li>');
                    }
                }
                else
                {
                    $this->addHtml('<li><a href="'.$submenu.'">'.$heading.'</a></li>');
                }

                $this->addHtml('</ul>
              </li>');

            }
        }


        $this->addHtml('</ul></div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
      </nav>');
    }


    public function validate($validationClosure)
    {
        try
        {
            $reflection = new \ReflectionFunction($validationClosure);
        }
        catch (\Exception $e)
        {
            throw new \Exception('Validate not called with Closure');

        }

        if (!$reflection->isClosure())
        {
            throw new \Exception('Validate not called with Closure');
        }

        $this->validationClosure = $validationClosure;

        if (!$this->sessionCheck())
        {
            throw new \Exception('NOT_LOGGED_IN');
        }

    }

    function __construct($name='',$defaultInitiate = 1)
    {
        $this->html = '';
        $this->uploadHandled = false;
        $this->uploadedFile = false;
        $this->pageTitle = $name;
        $this->defaultInitiate = $defaultInitiate;

        $this->needFileReWrite = false;
        $this->JSfile = '/scrpt.js';
        $this->CSSfile = '/csses.css';

        $this->AsseticCSS = new \Assetic\Asset\AssetCollection();
        $this->AsseticJS = new \Assetic\Asset\AssetCollection();


        $public = '../vendor/ajurgensen/php-magic/public/';

        $this->addJS($public . 'js/jquery.min.js');

        $this->addCSS($public . 'css/bootstrap.min.css');
        $this->addJS($public . 'js/bootstrap.min.js');

        $this->addJS($public . 'js/moment-with-locales.min.js');

        $this->addJS(  $public . 'js/fileinput.min.js');
        $this->addCSS( $public . 'css/fileinput.min.css');

        $this->addCSS( $public . 'css/bootstrap-datetimepicker.min.css');
        $this->addJS(  $public . 'js/bootstrap-datetimepicker.min.js');



        $this->addCSS( $public . 'css/daterangepicker.css');
        $this->addJS(  $public . 'js/daterangepicker.js');

        $this->addJS($public . 'js/Chart.bundle.min.js');


        if ($this->defaultInitiate)
        {
            $this->addBoostrapHeader();
        }
    }

    public function addCSS($css)
    {
        $localfile = $_SERVER['DOCUMENT_ROOT'] . $this->CSSfile;
        if (!file_exists($localfile))
        {
                touch($localfile, 1);
                $this->needFileReWrite = true;
        }
        if (file_exists($css))
        {
            if (filemtime($css) > filemtime($localfile))
            {
                $this->needFileReWrite = true;
            }
                $this->AsseticCSS->add(new \Assetic\Asset\FileAsset($css));
        }
        elseif (parse_url($css))
        {
            $this->AsseticCSS->add(new \Assetic\Asset\HttpAsset($css));
        }
        else
        {
            throw new \Exception('PM: CSS for inclusion should be either file or URL');

        }
    }


    public function addJS($js)
    {
        $localfile = $_SERVER['DOCUMENT_ROOT'] . $this->JSfile;
        if (!file_exists($localfile))
        {
            touch($localfile, 1);
            $this->needFileReWrite = true;
        }
        if (file_exists($js))
        {
            if (filemtime($js) > filemtime($localfile))
            {
                $this->needFileReWrite = true;
            }
            $this->AsseticJS->add(new \Assetic\Asset\FileAsset($js));
        }
        elseif (parse_url($js))
        {
            $this->AsseticJS->add(new \Assetic\Asset\HttpAsset($js));
        }
        else
        {
            throw new \Exception('PM: JS for inclusion should be either file or URL');

        }
    }


    public function finalize()
    {
        $this->addBodyHTMLEnd();
        $JSfile = $_SERVER["DOCUMENT_ROOT"] . $this->JSfile;
        $CSSfile = $_SERVER["DOCUMENT_ROOT"] . $this->CSSfile;
        if ($this->needFileReWrite)
        {
            file_put_contents($JSfile, $this->AsseticJS->dump());
            file_put_contents($CSSfile, $this->AsseticCSS->dump());
        }
        echo $this->getHTML();
    }

    /**
     * @param mixed $html
     */
    public function addHtml($html)
    {
        $this->html .= $html;
    }

    private function addBodyHTMLEnd()
    {
        $this->addHtml('</div></body></html>');
    }

    private function addBoostrapHeader()
    {
        $this->addHtml('<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="'. $this->JSfile  .'"></script>
    <title>' .  $this->pageTitle .'</title>

    <link href="'. $this->CSSfile .'" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>
<body>
<div class="container">');
    }

}
