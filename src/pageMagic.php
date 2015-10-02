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
        if (isset($_COOKIE['pm_session']) && $_COOKIE['pm_session'] == $this->sessionForUser())
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
        return md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['HTTP_HOST'] );
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
        $options = $names = array();
        $data = array(array('LOGIN', "Login", "VARCHAR"), array('PASSWORD', "Password", "VARCHAR"));

        $map = new \ajurgensen\phpMagic\map('Login');
        $map->feedArray($data);
        $map->setPwArray($this->pw_array);
        $fm = new formMagic($map, $options, $names);
        if ($fm->entitySaved)
        {
            //Form was returned and server side validated
            setcookie('pm_session',$this->sessionForUser(),null,'/');
            return true;
        }
        else
        {
            //Form not returned, echo login HTML and return false
            $this->addHtml($fm->html);
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
        $html .= '<div class="panel-body">' . $text . '</div></div>';
        return($html);
    }


    public function addPanel($text,$title='')
    {
        $this->addHtml($this->getPanel($text,$title));
    }

    public function addMenu($menu)
    {
        $this->addHtml('<nav class="navbar navbar-default">
        <div class="container-fluid">
          <div id="navbar" class="navbar-collapse collapse">');

        foreach ($menu as $heading => $submenu)
        {
            $this->addHtml('<ul class="nav navbar-nav">
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">&nbsp; ' . $heading . '<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">');
            foreach ($submenu as $submenuheading => $link)
            {

                $this->addHtml('<li><a href="'. $link .'"><span>&nbsp; '. $submenuheading .'</span></a></li>');
            }

            $this->addHtml('</ul>
              </li>
            </ul>');

        }

        $this->addHtml('</div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
      </nav>');
    }


    public function addPWArray($array)
    {
        $this->pw_array = $array;
        if (!$this->sessionCheck())
        {
            exit;
        }

    }

    function __construct($name='',$defaultInitiate = 1)
    {
        $this->html = '';
        $this->uploadHandled = false;
        $this->uploadedFile = false;
        $this->pageTitle = $name;
        $this->defaultInitiate = $defaultInitiate;
        if ($this->defaultInitiate)
        {
            $this->addBoostrapHeader();
        }
    }

    public function finalize()
    {
        $this->addBoostrapFooter();
        echo $this->getHTML();
    }

    /**
     * @param mixed $html
     */
    public function addHtml($html)
    {
        $this->html .= $html;
    }


    private function addBoostrapFooter()
    {

        $this->addHtml('    </div>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/tableSort.js"></script>
<script src="/js/tablesearch.js"></script>
</body>
</html>
');
    }

    private function addBoostrapHeader()
    {
        $this->addHtml('<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="http://code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
    <script src="/js/fileinput.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/dygraph/1.1.1/dygraph-combined.js"></script>
    <title>' .  $this->pageTitle .'</title>

    <!-- Bootstrap core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/fileinput.min.css" rel="stylesheet">

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
