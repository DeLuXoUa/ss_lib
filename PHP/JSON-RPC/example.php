<pre>
<?php
//include(dirname(__FILE__) . '/profiler.php');
include(dirname(__FILE__) . '/SelectSpecs_v_0_8/include.php');

// we can use custom parameters for connection directly in code (NOT Recomended), please use config.php
//$ssAPI = new SSAPI('api.example.com/json-rpc', 8843, 'secret token', 'group id');
try {
    //111c1111111111cc11111111 - test group_id
    //SeCrEtToKeNvAlUe - test token
    $ssapi = new SSAPI("ssapi.selectspecs.com", 8843, "SeCrEtToKeNvAlUe", "111c1111111111cc11111111");
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
$result = $ssapi->orders(['order_id' => 4]);
echo "<h2 style='color: blueviolet;'>SELECT:</h2>";
result_print($result);

//Remove orders by query
$result = $ssapi->orders(['order_id' => 4], NULL, SSAPI_DELETE_DOCUMENT);
echo "<h2 style='color: blueviolet;'>REMOVE:</h2>";
result_print($result);


//Replace orders by query
$result = $ssapi->orders(['order_id' => 3], ['name' => 'test order 3', '_group_id' => '333d3333333333dd33333333'], SSAPI_CREATE_IF_NOT_EXIST | SSAPI_RETURN_RESULT);
echo "<h2 style='color: blueviolet;'>REPLACE (with return result flag):</h2>";
result_print($result);

//Insert order
$result = $ssapi->orders(NULL, ['order_id' => 4, 'name' => 'test order 4', '_group_id' => '333d3333333333dd33333333'], SSAPI_RETURN_RESULT);
echo "<h2 style='color: blueviolet;'>INSERT (with return result flag):</h2>";
result_print($result);

//Update orders by query
$result = $ssapi->orders(['order_id' => 4, '_group_id' => '333d3333333333dd33333333'], ['name' => 'UPDATED order 4 to 2', '_group_id' => '444d4444444444dd44444444'], SSAPI_RETURN_RESULT);
echo "<h2 style='color: blueviolet;'>UPDATE (with return result flag):</h2>";
result_print($result);

//Insert order and don't wait answer from server
$ssapi->orders(NULL, ['order_id' => 5, 'name' => 'test order 5', '_group_id' => '333d3333333333dd33333333'], SSAPI_NO_WAIT_RESPONSE);
echo "<h2 style='color: blueviolet;'>INSERT (w/o answer):</h2>";
result_print($result);

//Replace order and don't wait answer from server
$ssapi->orders(['order_id' => 6], ['name' => 'test order 6', '_group_id' => '333d3333333333dd33333333'], SSAPI_CREATE_IF_NOT_EXIST | SSAPI_NO_WAIT_RESPONSE);
echo "<h2 style='color: blueviolet;'>REPLACE (w/o answer):</h2>";
result_print($result);


if($ssapi->getAllErrors()){
    echo '<hr><h3>LIST OF ERRORS:</h3> <p style="color:red">';
    var_dump($ssapi->getAllErrors());
    echo '</p>';
} else {
    echo '<hr><hr><b>NO ERRORS FOUND!</b>';
}
if($ssapi->getAllTimes()){
    echo '<h3>LIST OF QUERIES EXECUTION TIME:</h3> <p style="color:blue;">';
    var_dump($ssapi->getAllTimes());
    echo '</p>';
}
if($ssapi->getTotalTime()){
    echo '<hr>Total spended time for all queries: <h2 style="color:olivedrab;">';
    var_dump($ssapi->getTotalTime());
    echo '</h2>';
}

/*
  ERROR CODES:

    -32700 ---> parse error. not well formed
    -32701 ---> parse error. unsupported encoding
    -32702 ---> parse error. invalid character for encoding
    -32600 ---> server error. invalid json-rpc. not conforming to spec.
    -32601 ---> server error. requested method not found
    -32602 ---> server error. invalid method parameters
    -32603 ---> server error. internal json-rpc error
    -32500 ---> application error
    -32400 ---> system error
    -32300 ---> transport error
    -32000 to
    -32099 ---> Server error (Reserved for implementation-defined server-errors.)
*/
?>
</pre>