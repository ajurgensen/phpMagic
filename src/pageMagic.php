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

    public static function staticTest()
    {
        return 'Hello World, Composer!';
    }

    private function getHTML()
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

    public function addPanel($text,$title='')
    {
        $html = '<div class="panel panel-default">';
        if ($title)
        {
            $html .= '<div class="panel-heading">'. $title .'</div>';
        }
        $html .= '<div class="panel-body">' . $text . '</div></div>';
        $this->addHtml($html);
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

    function __construct($name='')
    {
        $this->html = '';
        $this->pageTitle = $name;
        $this->addBoostrapHeader();
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
    <title>' .  $this->pageTitle .'</title>

    <!-- Bootstrap core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">

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
