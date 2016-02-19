<pre>
<?php
//include(dirname(__FILE__) . '/profiler.php');
include(dirname(__FILE__) . '/SelectSpecs_v_0_8/include.php');

// we can use custom parameters for connection directly in code (NOT Recomended), please use config.php
//$ssAPI = new SSAPI('api.example.com/json-rpc', 8843, 'secret token', 'group id');
try {
    //111c1111111111cc11111111 - test group_id
    //SeCrEtToKeNvAlUe - test token
    $ssapi = new SSAPI("127.0.0.1", 8843, "SeCrEtToKeNvAlUe", "111c1111111111cc11111111");
//    $ssapi = new SSAPI("api.warder.tk", 8843, "SeCrEtToKeNvAlUe", "111c1111111111cc11111111");
} catch (Exception $e) {
    echo $e;
    die('<br><br><hr><b>CANT CONNECT TO SERVER');
}


function result_print($result){
    global $ssapi;

    if($result){
        var_dump($result);
    } elseif($ssapi->getLastError()){
        echo '<br>ERROR HAPPENNED: <p style="color:red">';
        var_dump($ssapi->getLastError());
        echo '</p>';
    }
    echo '<hr><p style="color: darkgoldenrod;">execution time is ', $ssapi->getLastTime(),' seconds</p><hr>';
}


//Select orders by query
$result = $ssapi->items_last_updated(NULL, NULL);

print_r($result);


?>
</pre>