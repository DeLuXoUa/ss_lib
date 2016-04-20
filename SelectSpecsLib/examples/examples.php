<pre>
<?php
//include(dirname(__FILE__) . '/profiler.php');
include(dirname(__FILE__) . '/../include.php');

// we can use custom parameters for connection directly in code (NOT Recomended), please use config.php
//$ssAPI = new SSAPI('api.example.com/json-rpc', 8843, 'secret token', 'group id');
try {
    $ssapi = new SSAPI(
        //"127.0.0.1",
        //"api.warder.tk", //port now is 8844
        "ssapi.selectspecs.com",
        8843,
        //8844,
        "56b32b778ec929c4110cbbfc",
        "@:START:TCP:1454582647656-a1f531a2b3543ae86f92e1982a85461f-4768b62a-3ead-490f-9aa8-d7721d5addde-b519c345-uQBEG3fx:END:@",
        "56a9da969a0bf84c09c316be",
        "example_client"
    );
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

//Insert items
//include(dirname(__FILE__).'/items/item4.php');
//$result = $ssapi->items(NULL, $item4save, SSAPI_RETURN_RESULT);
//echo "<h2 style='color: blueviolet;'>ITEMS INSERT:</h2>";
//result_print($result);

//Insert items (eugene)
//include(dirname(__FILE__).'/items/item_eugene.php');
//$result = $ssapi->items(NULL, $item_eugene, SSAPI_RETURN_RESULT | SSAPI_CONVERTER_WEB | SSAPI_FORCEADD);
//echo "<h2 style='color: blueviolet;'>ITEMS INSERT (eugene):</h2>";
//result_print($result);

//Insert items (omnis-multy)
//include(dirname(__FILE__).'/items/item_omnis_multy.php');
//$result = $ssapi->items(NULL, $item_omnis_multy, SSAPI_RETURN_RESULT | SSAPI_CONVERTER_OMNIS | SSAPI_MULTI_QUERY);
//echo "<h2 style='color: blueviolet;'>ITEMS INSERT (omnis):</h2>";
//result_print($result);

//Insert items (omnis)
//include(dirname(__FILE__).'/items/item_omnis.php');
//$result = $ssapi->items(NULL, $item_omnis, SSAPI_RETURN_RESULT | SSAPI_CONVERTER_OMNIS);
//echo "<h2 style='color: blueviolet;'>ITEMS INSERT (omnis):</h2>";
//result_print($result);

//Select items by query
$result = $ssapi->items(['item_number' => 8115]);
echo "<h2 style='color: blueviolet;'>ITEMS SELECT:</h2>";
print_r($result[0]['options']);

//Select LAST items by query with limit and skip
//$result = $ssapi->items_last_updated("2016-04-14T00:00:00.000Z", NULL, SSAPI_CONVERTER_WEB, ["order"=>["__service.updated"=>1], "limit"=>10, "skip"=>1]);
//echo "<h2 style='color: blueviolet;'>SELECT LAST:</h2>\n";
//result_print($result);

//Download all items and count it
//$skip = 0;
//$limit = 1000;
//$options_count = 0;
//$prices_count = 0;
//$prices_count_not_zero = 0;
//$domain_price_count = [];
//while($result = $ssapi->items_last_updated("2016-04-18 15:59:00.000Z", NULL, SSAPI_CONVERTER_WEB, ["order"=>["__service.updated"=>1], "limit"=>$limit, "skip"=>$skip])){
//    $skip+=$limit;

//    foreach($result as $r){
//        if(!$r['discontinued']) {
//            $options_count ++;

//            $prices_count += count($r['prices_domain']);
//            foreach ($r['prices_domain'] as $k => $pd) {
//                if ($pd['price'] && $pd['price'] != '0') {
//                    $prices_count_not_zero++;
//                    if(!isset($domain_price_count[$k])) $domain_price_count[$k]=0;
//                    $domain_price_count[$k]++;
//                }
//            }
//        }
//    }

//    echo "chunk $skip: total options - [ $options_count ], total prices - $prices_count (nz: $prices_count_not_zero)\n";
//}
//echo "\n----------------------------------\nTOTAL: $prices_count (nz: $prices_count_not_zero)\n";
//print_r($domain_price_count);

//$result = $ssapi->items(["item_number"=>6011], NULL, SSAPI_CONVERTER_WEB);
//echo "<h2 style='color: blueviolet;'>SELECT 6011:</h2>\n";
//result_print($result);

//Select MIN&MAX LAST items by query
//$result = $ssapi->items_last_updated_minmax("2012-01-01T00:00:00.000Z", NULL);
//echo "<h2 style='color: blueviolet;'>SELECT MIN&MAX LAST:</h2>";
//result_print($result);

/*
//Remove orders by query
$result = $ssapi->orders(['order_id' => 4], NULL, SSAPI_DELETE_DOCUMENT);
echo "<h2 style='color: blueviolet;'>ORDERS REMOVE:</h2>";
result_print($result);


//Replace orders by query
$result = $ssapi->orders(['order_id' => 3], ['name' => 'test order 3', '_group_id' => '333d3333333333dd33333333'], SSAPI_CREATE_IF_NOT_EXIST | SSAPI_RETURN_RESULT);
echo "<h2 style='color: blueviolet;'>ORDERS REPLACE (with return result flag):</h2>";
result_print($result);

//Insert order
$result = $ssapi->orders(NULL, ['order_id' => 4, 'name' => 'test order 4', '_group_id' => '333d3333333333dd33333333'], SSAPI_RETURN_RESULT);
echo "<h2 style='color: blueviolet;'>ORDERS INSERT (with return result flag):</h2>";
result_print($result);

//Update orders by query
$result = $ssapi->orders(['order_id' => 4, '_group_id' => '333d3333333333dd33333333'], ['name' => 'UPDATED order 4 to 2', '_group_id' => '444d4444444444dd44444444'], SSAPI_RETURN_RESULT);
echo "<h2 style='color: blueviolet;'>ORDERS UPDATE (with return result flag):</h2>";
result_print($result);

//Insert order and don't wait answer from server
$ssapi->orders(NULL, ['order_id' => 5, 'name' => 'test order 5', '_group_id' => '333d3333333333dd33333333'], SSAPI_NO_WAIT_RESPONSE);
echo "<h2 style='color: blueviolet;'>ORDERS INSERT (w/o answer):</h2>";
result_print($result);

//Replace order and don't wait answer from server
$ssapi->orders(['order_id' => 6], ['name' => 'test order 6', '_group_id' => '333d3333333333dd33333333'], SSAPI_CREATE_IF_NOT_EXIST | SSAPI_NO_WAIT_RESPONSE);
echo "<h2 style='color: blueviolet;'>ORDERS REPLACE (w/o answer):</h2>";
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