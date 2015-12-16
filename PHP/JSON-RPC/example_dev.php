<pre>
<?php
include(dirname(__FILE__) . '/profiler.php');
include(dirname(__FILE__) . '/SelectSpecs_v_0_7/include.php');

// we can use custom parameters for connection directly in code (NOT Recomended), please use config.php
//$ssAPI = new SSAPI('api.example.com/json-rpc', 8843, 'secret token', 'group id');
try {
    //111c1111111111cc11111111 - test group_id
    //SeCrEtToKeNvAlUe - test token
    $ssapi = new SSAPI("api.warder.tk", 8843, "SeCrEtToKeNvAlUe", "111c1111111111cc11111111");
} catch (Exception $e) {
    echo $e;
    die('<br><br><hr><b>CANT CONNECT TO SERVER');
}

//Select orders by query
echo "<b>SELECT:</b>\n";
$result = $ssapi->orders(['order_id' => 4]);
var_dump($result);

profiling();

//Remove orders by query
echo "<b>Remove:</b>\n";
$result = $ssapi->orders(['order_id' => 4], NULL, SSAPI_DELETE_DOCUMENT);
var_dump($result);

profiling();


//Replace orders by query
echo "<b>REPLACE (with return result flag):</b>\n";
$result = $ssapi->orders(['order_id' => 3], ['name' => 'test order 3', '_group_id' => '333d3333333333dd33333333'], SSAPI_CREATE_IF_NOT_EXIST | SSAPI_RETURN_RESULT);
var_dump($result);

profiling();

//Insert order
echo "<b>INSERT (with return result flag):</b>\n";
$result = $ssapi->orders(NULL, ['order_id' => 4, 'name' => 'test order 4', '_group_id' => '111c1111111111cc11111111'], SSAPI_RETURN_RESULT);
var_dump($result);

profiling();

//Update orders by query
echo "<b>UPDATE (with return result flag):</b>\n";
$result = $ssapi->orders(['order_id' => 4, '_group_id' => '333d3333333333dd33333333'], ['name' => 'UPDATED order 4 to 2', '_group_id' => '444d4444444444dd44444444'], SSAPI_RETURN_RESULT);
var_dump($result);

profiling();

//Insert order and don't wait answer from server
$ssapi->orders(NULL, ['order_id' => 5, 'name' => 'test order 5', '_group_id' => '333d3333333333dd33333333'], SSAPI_NO_WAIT_RESPONSE);
echo "INSERT (w/o answer)\n";

profiling();

//Replace order and don't wait answer from server
$ssapi->orders(['order_id' => 6], ['name' => 'test order 6', '_group_id' => '333d3333333333dd33333333'], SSAPI_CREATE_IF_NOT_EXIST | SSAPI_NO_WAIT_RESPONSE);
echo "REPLACE (w/o answer)\n";

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