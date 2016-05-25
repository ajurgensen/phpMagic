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
    var $chartName;
    var $intName;
    var $labels;
    var $dataSets;
    var $dataLegends;
    var $dataTypes;
    var $dataYxaixs;
    var $dataColors;

    /**
     * @param $chartname Name of Chart to be build
     */
    function __construct($chartname)
    {
        $this->chartName = $chartname;
        $this->intName =     str_replace(' ','',$this->chartName) . rand(1000,9999);
        $this->dataTypes = array('bar','line');
        $this->dataYxaixs = array(1,2);
        $this->dataColors = array('#B71A3A','#1ABC9C');
    }

    public function getChartHTML($width = 500, $height = 300)
    {
        $html = '<canvas id="' . $this->intName . '" width="' . $width . '" height="' . $height . '"></canvas>';

        $html .= '<script>
var ChartData =
{
          labels: '. json_encode($this->labels) .',datasets:[';

        $first = true;
        foreach ($this->dataSets as $key => $dataSet)
        {
            if (isset($this->dataTypes[$key]))
            {
                $type = $this->dataTypes[$key];
            }
            else $type = 'line';

            if (isset($this->dataYxaixs[$key]))
            {
                $yAxis = $this->dataYxaixs[$key];
            }
            else
            {
                $yAxis = 1;
            }
            if (isset($this->dataColors[$key]))
            {
                $color = $this->dataColors[$key];
            }
            else
            {
                $color = '#1ABC9C';
            }

            if ($first)
            {
                $first = false;
            }
            else
            {
                $html .=',';
            }
            $html .= '
            {
            type : "' . $type .'",
            label:"'. $this->dataLegends[$key].'",
            data: '. json_encode($dataSet) .',
            fill: false,
            borderColor: "'.$color.'",
            backgroundColor: "'.$color.'",
            pointBorderColor: "'.$color.'",
            pointBackgroundColor: "'.$color.'",
            pointHoverBackgroundColor: "'.$color.'",
            pointHoverBorderColor: "'.$color.'",
            borderColor: "'.$color.'",
            yAxisID: "y-axis-'.$yAxis.'"
            }';
        }
$html .= ']};</script>';




$html .= '<script>
var ctx = document.getElementById("' . $this->intName . '");
var myChart = new Chart(ctx, {
                type: \'bar\',
                data: ChartData,
                options: {
                responsive: true,
                tooltips: {
                  mode: \'label\'
              },
              elements: {
                line: {
                    fill: false
                }
            },
              scales: {
                xAxes: [{
                    display: true,
                    gridLines: {
                        display: false
                    },
                    labels: {
                        show: true,
                    }
                }],
                yAxes: [{
                    type: "linear",
                    display: true,
                    position: "left",
                    id: "y-axis-1",
                    gridLines:{
                        display: false
                    },
                    labels: {
                        show:true,

                    }
                }, {
                    type: "linear",
                    display: true,
                    position: "right",
                    id: "y-axis-2",
                    gridLines:{
                        display: false
                    },
                    labels: {
                        show:true,

                    }
                }]
            }
            }
            });
</script>';

        return $html;
    }

    /**
     * @return string
     */
    public function loadDateData($data)
    {
        $outerCount = 0;
        foreach ($data as $key => $values)
        {
            if (!$outerCount)
            {
                // intercept first line, includes info
                $innerCount = 0;
                foreach($values as $value)
                {
                    $this->dataLegends[$innerCount] = $value;
                    $innerCount++;
                }
            }
            else
            {
                //normal data, all other lines
                $this->labels[] = $key;
                $innerCount = 0;
                foreach($values as $value)
                {
                    $this->dataSets[$innerCount][$outerCount] = $value;
                    $innerCount++;
                }
            }
            $outerCount++;
        }
        foreach ($this->dataSets as &$dataSet)
        {
            $newset = array();
            foreach ($dataSet as $data)
            {
                $newset[] = $data;
            }
            $dataSet = $newset;
        }
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
