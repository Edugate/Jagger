<?php
$all_converted = array();
$idps = array();
$sps = array();
if (empty($grid) or !isset($grid['known']) or count($grid['known']) == 0)
{

    echo '<div class="notice">No entity found with registered date</div>';
} else
{
    ksort($grid['known']);
    $y = 0;
    foreach ($grid['known'] as $k => $g)
    {
        $dd = DateTime::createFromFormat('Ymd', $k);
        $y = $y + count($g);
        $all_converted[] = array('d' => $dd->format('Y-m-d'), 'v' => $y);
        foreach ($g as $gg)
        {

            if ($gg['t'] == 'IDP')
            {
                $idps[$k] = $g;
            } else
            {
                $sps[$k] = $g;
            }
        }
    }
    $all_converted[] = array('d' => date('Y-m-d'), 'v' => $y);
    $y = 0;
    $idps_converted = array();
    foreach ($idps as $k => $g)
    {
        $dd = DateTime::createFromFormat('Ymd', $k);
        $y = $y + count($g);
        $idps_converted[] = array('d' => $dd->format('Y-m-d'), 'v' => $y);
    }
    $idps_converted[] = array('d' => date('Y-m-d'), 'v' => $y);

    $y = 0;
    $sps_converted = array();
    foreach ($sps as $k => $g)
    {
        $dd = DateTime::createFromFormat('Ymd', $k);
        $y = $y + count($g);
        $sps_converted[] = array('d' => $dd->format('Y-m-d'), 'v' => $y);
    }
    $sps_converted[] = array('d' => date('Y-m-d'), 'v' => $y);
    $line3 = '[';
    foreach ($all_converted as $m)
    {
        $gg = '[\'' . $m['d'] . '\',' . $m['v'] . '],';
        $line3 .= $gg;
    }
    $line3 .='];';

    if (!empty($sps_converted))
    {
        $line1 = '[';
        foreach ($sps_converted as $m)
        {
            $gg = '[\'' . $m['d'] . '\',' . $m['v'] . '],';
            $line1 .= $gg;
        }

        $line1 .= ']';
    } else
    {
        //$today = DateTime::createFromFormat('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $line1 = '[[\'' . $today . '\',0]]';
    }
    $line2 = '[';
    foreach ($idps_converted as $m)
    {
        $gg = '[\'' . $m['d'] . '\',' . $m['v'] . '],';
        $line2 .= $gg;
    }

    $line2 .= ']';

    if (!empty($fedname))
    {
        $graphTitle = 'Progress for federation ' . $fedname;
    } else
    {
        $graphTitle = 'Progress of locally registered entities';
    }
    ?>
    <div class="span-23" style="overflow: visible">
        <div id="chart1" style="overflow: visible ;width: 600;height: 400px; margin: 25px;">
        </div> 
    </div>
    <script>
        $(document).ready(function(){
            var line1 = <?php echo $line1; ?>;
            var line2 = <?php echo $line2; ?>;
            var line3 = <?php echo $line3; ?>;
            var plot1 = $.jqplot('chart1', [line1,line2,line3], {
                title:'<?php echo $graphTitle; ?>',
                axes:{xaxis:{renderer:$.jqplot.DateAxisRenderer}},
                seriesColors: [ "#66c974", "#a4b9fb", "#c61717"],
                legend:{show: true, placement:"insideGrid",labels:['Service Providers','Identity Providers','All Entities']},
            
                series:[{lineWidth:2 },{lineWidth:2 },{lineWidth:4 }],
                seriesDefaults: {
                    lineWidth:4,
                    showMarker:true,
                    pointLabels: { show:true },
                    fontSize: 16
                }
            }
                 );
        });
    </script>
    <?php
}
