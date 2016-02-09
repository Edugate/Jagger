<?php
$all_converted = array();
$idps = array();
$sps = array();
$base_url = base_url();
if (empty($grid) || !isset($grid['known']) || count($grid['known']) == 0) {

    echo '<div class="notice">' . lang('rr_noentitywithregdate') . '</div>';
} else {
    ksort($grid['known']);
    $y = 0;
    foreach ($grid['known'] as $k => $g) {
        $dd = DateTime::createFromFormat('Ymd', $k);
        $y = $y + count($g);
        $all_converted[] = array('d' => $dd->format('Y-m-d'), 'v' => $y);
        foreach ($g as $gg) {

            if ($gg['t'] == 'IDP') {
                $idps[$k][] = $gg;
            } else {
                $sps[$k][] = $gg;
            }
        }
    }
    $all_converted[] = array('d' => date('Y-m-d'), 'v' => $y);
    $y = 0;
    $idps_converted = array();
    foreach ($idps as $k => $g) {
        $dd = DateTime::createFromFormat('Ymd', $k);
        $y = $y + count($g);
        $idps_converted[] = array('d' => $dd->format('Y-m-d'), 'v' => $y);
    }
    $idps_converted[] = array('d' => date('Y-m-d'), 'v' => $y);

    $y = 0;
    $sps_converted = array();
    foreach ($sps as $k => $g) {
        $dd = DateTime::createFromFormat('Ymd', $k);
        $y = $y + count($g);
        $sps_converted[] = array('d' => $dd->format('Y-m-d'), 'v' => $y);
    }
    $sps_converted[] = array('d' => date('Y-m-d'), 'v' => $y);
    $line3 = '[';
    foreach ($all_converted as $m) {
        $gg = '[\'' . $m['d'] . '\',' . $m['v'] . '],';
        $line3 .= $gg;
    }
    $line3 .= '];';

    if (!empty($sps_converted)) {
        $line1 = '[';
        foreach ($sps_converted as $m) {
            $gg = '[\'' . $m['d'] . '\',' . $m['v'] . '],';
            $line1 .= $gg;
        }

        $line1 .= ']';
    } else {
        $today = date('Y-m-d');
        $line1 = '[[\'' . $today . '\',0]]';
    }
    $line2 = '[';
    foreach ($idps_converted as $m) {
        $gg = '[\'' . $m['d'] . '\',' . $m['v'] . '],';
        $line2 .= $gg;
    }

    $line2 .= ']';

    ?>

    <div style="overflow: visible">
        <div id="chart1" style="overflow: visible ;width: 600;height: 400px; margin: 25px;">
        </div>
    </div>
    <script>
        var genJaggDiag1 = function () {
            var line1 = <?php echo $line1; ?>;
            var line2 = <?php echo $line2; ?>;
            var line3 = <?php echo $line3; ?>;
            var spname = "<?php echo lang('serviceproviders'); ?>";
            var idpname = "<?php echo lang('identityproviders'); ?>";
            var allentname = "<?php echo lang('allentities'); ?>";
            var progressname = "<?php echo lang('rr_progress'); ?>";
            var plot1 = jQuery.jqplot('chart1', [line1, line2, line3], {
                    title: '' + progressname + '',
                    axes: {
                        xaxis: {renderer: jQuery.jqplot.DateAxisRenderer},
                        yaxis: {
                            tickOptions: {
                                formatString: '%.0f'
                            }
                        }
                    },
                    highlighter: {
                        show: true,
                        sizeAdjust: 7.5
                    },
                    seriesColors: ["#66c974", "#a4b9fb", "#c61717"],
                    legend: {
                        show: true,
                        placement: "outsideGrid",
                        location: "s",
                        showSwatch: true,
                        marginLeft: "210px",
                        labels: ['' + spname + '', '' + idpname + '', '' + allentname + '']
                    },

                    series: [{lineWidth: 2}, {lineWidth: 2}, {lineWidth: 4}],
                    seriesDefaults: {
                        lineWidth: 4,
                        showMarker: true,
                        pointLabels: {show: true},
                        fontSize: 16
                    }
                }
            );
        };
        function defer(method) {

            if (window.jQuery) {
                method();
            }
            else {
                setTimeout(function () {
                    defer(method)
                }, 50);
            }
        }
        defer(genJaggDiag1);
    </script>
    <?php
}
