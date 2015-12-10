<pre>
<?php
include(dirname(__FILE__) . '/SelectSpecs_v_0_4/include.php');

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

$ssapi = new SSAPI("127.0.0.1", 8843, "SeCrEtToKeNvAlUe", "GrOuPiD"); //if we dont send params all params will loaded from config.php file
// we can use custom parameters for connection directly in code (NOT Recomended), please use config.php
//$ssAPI = new SSAPI('http', 'api.example.com/json-rpc', 80, 'secret token', 'group id');

//Select orders by query
    $result = $ssapi->orders(['id' => 1]);
    var_dump($result);

    profiling();


//Update orders by query
    $result = $ssapi->orders(['id' => 2], ['name' => 'test order 2', '_group_id' => 'A1498Hjhjh99hjjh'], SSAPI_RETURN_RESULT);
    var_dump($result);

    profiling();

//Replace orders by query
    $result = $ssapi->orders(['id' => 3], ['name' => 'test order 3', '_group_id' => 'A1498Hjhjh99hjjh'], SSAPI_CREATE_IF_NOT_EXIST | SSAPI_RETURN_RESULT);
    var_dump($result);

    profiling();

//Insert order
    $result = $ssapi->orders(NULL, ['name' => 'test order 4', '_group_id' => 'A1498Hjhjh99hjjh'], SSAPI_CREATE_IF_NOT_EXIST | SSAPI_RETURN_RESULT);
    var_dump($result);

    profiling();

//Insert order and don't wait answer from server
    $ssapi->orders(NULL, ['name' => 'test order 5', '_group_id' => 'A1498Hjhjh99hjjh'], SSAPI_NO_WAIT_RESPONSE);
    var_dump('NOTIFICATION');
    profiling();

//Replace order and don't wait answer from server
    $ssapi->orders(['id' => 6], ['name' => 'test order 6', '_group_id' => 'A1498Hjhjh99hjjh'], SSAPI_CREATE_IF_NOT_EXIST | SSAPI_NO_WAIT_RESPONSE);
    var_dump('NOTIFICATION');

    profiling();
    profiling(true);



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