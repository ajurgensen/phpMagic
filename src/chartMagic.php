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

class chartMagic
{
    var $html;
    var $outChartData;
    var $chartData;
    var $charName;
    var $intName;

    /**
     * @param Array $entity Entity to build form from
     * @param Array $options FM_DESCRIPTION FM_OPTIONS  FM_ADDONS FM_NAME FM_DESC FM_EXCLUDE
     * @param Array $names array of 'tablename' => 'Name Shown'
     * @param int $debug Enable debug or not - Defaults to not
     */
    function __construct($chartname)
    {
        $this->chartName = $chartname;
        $this->intName = $this->chartName . rand(1000,9999);
    }

    public function getChartHTML()
    {
        $html = '<div id="'. $this->intName  .'" style="width: 500px;height: 300px;"></div>';


        $html .= '<script>$(document).ready(
        function ()
        {
            new Dygraph
            (
                document.getElementById("'. $this->intName.'"),['. $this->outChartData .'],
                {
                    //customBars: true,
                    title: "'. $this->chartName .'",
                    //legend: "always",
                    //labelsDivStyles: { "textAlign": "right" },
                    //showRangeSelector: true
                }
            );
        });
        </script>';
        return $html;
    }

    /**
     * @return string
     */
    public function loadDateData($data)
    {
        $this->chartData = $data;

        $outChartData = '';
        $loopfirst = 1;
        foreach ($data as $key => $values)
        {
            if ($loopfirst)
            {
                $loopfirst = 0;
            } else
            {
                $outChartData .= ',';
            }


            $first = 1;
            $valueset = '';
            foreach ($values as $value)
            {
                if ($first)
                {
                    $first = 0;
                } else
                {
                    $valueset = ',' . $valueset;
                }


                $valueset .= $value;


            }
            $key = str_replace('-', '/', $key);
            $outChartData .= '[new Date("' . $key . '"),' . $valueset . "]";
        }
        $this->outChartData = $outChartData;
    }

    /**
     * @return string
     */
    public function loadData($data)
    {
        $this->chartData = $data;

        $outChartData = '';
        $loopfirst = 1;
        foreach ($data as $key => $values)
        {
            if ($loopfirst)
            {
                $loopfirst = 0;
            } else
            {
                $outChartData .= ',';
            }


            $first = 1;
            $valueset = '';
            foreach ($values as $value)
            {
                if ($first)
                {
                    $first = 0;
                } else
                {
                    $valueset = ',' . $valueset;
                }


                $valueset .= $value;


            }
            $outChartData .= '[' . $valueset . "]";
        }
        $this->outChartData = $outChartData;
    }

}