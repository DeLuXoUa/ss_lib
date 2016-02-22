<pre>
<?php
//include(dirname(__FILE__) . '/profiler.php');
include(dirname(__FILE__) . '/SelectSpecs_v_0_8/include.php');

// we can use custom parameters for connection directly in code (NOT Recomended), please use config.php
//$ssAPI = new SSAPI('api.example.com/json-rpc', 8843, 'secret token', 'group id');
try {
    //111c1111111111cc11111111 - test group_id
    //SeCrEtToKeNvAlUe - test token
    $ssapi = new SSAPI(
        "127.0.0.1",
        //"api.warder.tk",
        //"ssapi.selectspecs.com",
        8843,
        "56b32b778ec929c4110cbbfc",
        "@:START:TCP:1454582647656-a1f531a2b3543ae86f92e1982a85461f-4768b62a-3ead-490f-9aa8-d7721d5addde-b519c345-uQBEG3fx:END:@",
        "56a9da969a0bf84c09c316be",
        "local"
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
$item4save = [];

$item4save['rr_price'] = 6;
$item4save['price_old'] = 7;
$item4save['price'] = 8;

//==> __service
$item4save['__service'] = [];
$item4save['__service']['client_id'] = 'helloworld';

$data["prices_domain"]=[
    "56a9da969a0bf84c09c316be"=>[
        "round" => 10,
        "price" => 20,
        "percent" => -1

    ]
];

//==> specifications
$item4save['specifications']['base_curve'] = 'base_curve';
$item4save['specifications']['sph_min'] = 'sph_min';
$item4save['specifications']['sph_max'] = 'sph_max';
$item4save['specifications']['sph_step'] = 'sph_step';
$item4save['specifications']['cyl'] = 'cyl';
$item4save['specifications']['axis'] = 'axis';
$item4save['specifications']['multifocaladd'] = 'multifocaladd';
$item4save['specifications']['warranty_period'] = 'warranty_period';
$item4save['specifications']['pd'] = 'pd';
$item4save['specifications']['height'] = 'height';
$item4save['specifications']['width'] = 'width';
$item4save['specifications']['depth'] = 'depth';
$item4save['specifications']['weight'] = 'weight';
$item4save['specifications']['frame_sizes'] = 'frame_sizes';

//==> stock
$item4save['stock']['is_out_of_stock'] = 'is_out_of_stock';
$item4save['stock']['featured'] = 'featured';
$item4save['stock']['discontinued'] = 'discontinued';


$item4save['colours'] = 'colours';
$item4save['description'] = 'description';
$item4save['status'] = 'option_best_status';
$item4save['model_name'] = 'model_name';
$item4save['supplier_name'] = 'supplier_name';
$item4save['designer_name'] = 'desigenr_name';
$item4save['brand_name'] = 'brand_name';
$item4save['categories'] = 'categories';
$item4save['main_categories']='tab';
$item4save['item_id']='item_id';
$item4save['option_id']='option_id';

//==> migration

$item4save['migration']['item_added'] = 'item_added';
$item4save['migration']['supp_name'] = 'supp_name';
$item4save['migration']['no_large_image'] = 'no_large_image';
$item4save['migration']['no_option_images'] = 'no_option_images';
$item4save['migration']['product_information'] = 'product_information';
$item4save['migration']['is_modified'] = 'is_modified';
$item4save['migration']['item_info'] = 'item_info';
$item4save['migration']['designer_id'] = 'designer_id';

$item4save['migration']['default_option_order'] = 'default_option_order';
$item4save['migration']['tab_id'] = 'tab_id';
$item4save['migration']['experiment_key'] = 'experiment_key';

//==> options
$item4save['options']['option_description'] = 'option_description';
$item4save['options']['option_name'] = 'option_name';
$item4save['options']['option_order'] = 'option_order';

$result = $ssapi->items(NULL, $item4save, SSAPI_RETURN_RESULT);
echo "<h2 style='color: blueviolet;'>ITEMS INSERT:</h2>";
result_print($result);

//Select items by query
$result = $ssapi->items(['item_id' => 4]);
echo "<h2 style='color: blueviolet;'>ITEMS SELECT:</h2>";
result_print($result);

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