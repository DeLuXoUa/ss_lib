<?php
$total = 0;
$start = microtime(true);

function profiling($showtotal = false){
    global $total, $start;

    if($showtotal) {
        echo "\n=======================================================\ntime spended TOTAL: ", round($total, 6), " seconds\n=======================================================\n\n";
    } else {
        $end = microtime(true);
        $spended = ($end - $start);
        $total += $spended;
        echo "\n-------------------------------------------------------\ntime spended on connection & request: ", round($spended, 6), " seconds\n-------------------------------------------------------\n\n";
        $start = microtime(true);
    }
}
?>