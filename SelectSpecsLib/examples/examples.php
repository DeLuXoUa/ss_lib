<pre>
<?php

//include(dirname(__FILE__) . '/profiler.php');
include(dirname(__FILE__) . '/../include.php');

// we can use custom parameters for connection directly in code (NOT Recomended), please use config.php
//$ssAPI = new SSAPI('api.example.com/json-rpc', 8843, 'secret token', 'group id');
try {
    $ssapi = new SSAPI(
        //"127.0.0.1", 1232,
        //"api.aa.com", 1111,
        "ssapi.aa.com", 1234,

        "",
        "@:START:TCP:-a1f531a2b3543ae86f92e1982a85461f-4768b62a-3ead-490f-9aa8-d7721d5addde-b519c345-uQBEG3fx:END:@",
        "",
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



//Select gettext

/*
 *
 * "datatype" : "gettext_translation" - ordinary gettext
 * "datatype" : "categories_translation" - categories gettext
 * ...
 * */

$result = $ssapi->gettext_last_updated("2010-01-01 00:00:00", NULL, NULL, ["limit"=>100]);
echo "<h2 style='color: blueviolet;'>GETTEXT:</h2>";
result_print($result);

//[0]=>
//  array(10) {
//    ["_id"]=> string(24) "57d190b61ca1541200893bfe"
//    ["_group_id"]=> string(24) "56a9da979a0bf84c09c316ca"
//    ["datatype"]=> string(19) "gettext_translation"
//    ["label"]=> string(11) "1000SemiRim"
//    ["value"]=> array(2) {
//        ["value"]=> string(80) "Over 1000 Semi-Rimless styles available from only ?14.50 inc. lenses & coatings."
//        ["id"]=> string(3) "100"
//    }
//    ["lang"]=> string(2) "en"
//    ["versions"]=> array(0) { }
//    ["is_translated"]=> bool(false)
//    ["urls"]=> array(0) { }
//    ["__service"]=> array(6) {
//        ["_group_id"]=> string(24) "56a9da969a0bf84c09c316be"
//        ["_key_id"]=> string(24) "56b32b778ec929c4110cbbfc"
//        ["client_id"]=> string(31) "ubuntu_cron_items_import_script"
//        ["ip"]=> string(19) "::ffff:80.78.51.108"
//        ["created"]=> string(24) "2016-09-08T16:24:22.645Z"
//        ["updated"]=> string(24) "2016-09-08T16:24:22.645Z"
//    }
//  }



//insert order
//$data = json_decode(
//    '{"id":"51523286","number":null,"code":"e35066f7f5452144a7fe89bdab6cd2db","parent_id":null,"payment_system_id":null,"payment_record_id":null,"profile_id":null,"order_ip":"z36.23.229","delivery_address_id":null,"beauty_card":null,"billing_address_id":null,"currency_id":"1","rate":"1.00","vat_perc":"1.00000","shipping_cost":"5.95","exported":false,"delivery_method_id":null,"comments":null,"errors":"","checkout_at":null,"pcode_value":null,"promo_code_id":null,"promo_discount":"0.00","order_amount":"122.24","status":"","final_total":"128.19","addresschanged":false,"reference":null,"user_id":null,"price_extra":null,"extra_comment":null,"domain_id":null,"checkout_page_visited":"0","checkout_page_email_sent":"0","ipn_email_sent":"0","ipn_user_data":null,"total_save_two_for_one":"0.00","staff_id":null,"store_id":null,"created_at":"2016-05-24 10:12:24","updated_at":"2016-05-24 10:12:24","domain":"selectspecs.com","items":[{"id":"1131102","type":"ordinary","order_id":"51523286","tab_id":"1","designer_id":"246","status":"IN_STOCK","weight":"6","height":"45","prescription_id":"1287293","quantity":"1","item_id":"51950","option_order":"4","arm":"145","bridge":"21","lens_width":"49","item_description":"BOSS 0681","option_name":"OHQ","option_description":"BLCK HAVN","supplier_name":"SAFI","supplier_description":null,"price":"122.24","rel_id":null,"frame_size":"1","count_2_for_1":"0","created_at":"2016-05-24 10:12:24","prescription":{"id":"1287293","profile_id":null,"name":null,"R_Sphere":null,"R_Cylinder":null,"R_Axis":null,"L_Sphere":null,"L_Cylinder":null,"L_Axis":null,"R_Sphere2":null,"R_Cylinder2":null,"R_Axis2":null,"L_Sphere2":null,"L_Cylinder2":null,"L_Axis2":null,"R_Sphere3":null,"R_Cylinder3":null,"R_Axis3":null,"L_Sphere3":null,"L_Cylinder3":null,"L_Axis3":null,"R_Addition":null,"L_Addition":null,"PD":null,"pdcheckType":null,"prism_re_dir":null,"prism_re_pow":null,"prism_le_dir":null,"prism_le_pow":null,"faxed":null,"posted":null,"emailed":null,"add_info":null,"use_id":"1","lens_id":null,"tint_id":"99","tint_level_id":null,"rx":false,"attached_filename":null,"amazon_etag":null,"reglaze_info":null,"lens_diameter":null,"vertical_height_b":null,"vertical_height_p":null,"presc_use_sub":null,"presc_sun":false,"multifocaladd":null,"colours":null,"created_at":"2016-05-24 10:12:24","updated_at":"2016-05-24 10:12:24"}},{"id":"1131140","type":"ordinary","order_id":"51523286","tab_id":"1","designer_id":"246","status":"IN_STOCK","weight":"6","height":"45","prescription_id":"1287365","quantity":"1","item_id":"51950","option_order":"4","arm":"145","bridge":"21","lens_width":"49","item_description":"BOSS 0681","option_name":"OHQ","option_description":"BLCK HAVN","supplier_name":"SAFI","supplier_description":null,"price":"122.24","rel_id":null,"frame_size":"1","count_2_for_1":"0","created_at":"2016-05-24 11:00:28","prescription":{"id":"1287365","profile_id":null,"name":null,"R_Sphere":null,"R_Cylinder":null,"R_Axis":null,"L_Sphere":null,"L_Cylinder":null,"L_Axis":null,"R_Sphere2":null,"R_Cylinder2":null,"R_Axis2":null,"L_Sphere2":null,"L_Cylinder2":null,"L_Axis2":null,"R_Sphere3":null,"R_Cylinder3":null,"R_Axis3":null,"L_Sphere3":null,"L_Cylinder3":null,"L_Axis3":null,"R_Addition":null,"L_Addition":null,"PD":null,"pdcheckType":null,"prism_re_dir":null,"prism_re_pow":null,"prism_le_dir":null,"prism_le_pow":null,"faxed":null,"posted":null,"emailed":null,"add_info":null,"use_id":"1","lens_id":null,"tint_id":"99","tint_level_id":null,"rx":false,"attached_filename":null,"amazon_etag":null,"reglaze_info":null,"lens_diameter":null,"vertical_height_b":null,"vertical_height_p":null,"presc_use_sub":null,"presc_sun":false,"multifocaladd":null,"colours":null,"created_at":"2016-05-24 11:00:28","updated_at":"2016-05-24 11:00:28"}}]}',
//    true);
//
//$result = $ssapi->orders( NULL, $data, SSAPI_CONVERTER_WEB );
//echo "<h2 style='color: blueviolet;'>ORDER INSERT RESULT:</h2>";
//print_r($result);

//profiles
//$result = $ssapi->profiles(["profile_number" => 342690], NULL );
//$result = $ssapi->profiles(["__service.updated" => ['$gt'=>'2015-05-27']], NULL );
//echo "<h2 style='color: blueviolet;'>Profiles:</h2>";
//var_dump($result);

//Insert user
//$result = $ssapi->users(NULL, ["hashedPassword"=>"asd@asd.com", "login"=>['email'=>'asdasd'], 'user_number'=>12321] );
//echo "<h2 style='color: blueviolet;'>ORDERS:</h2>";
//print_r($result);

//Select orders
//$result = $ssapi->orders(["order_number"=>51523286], NULL, SSAPI_CONVERTER_WEB );
//echo "<h2 style='color: blueviolet;'>ORDERS:</h2>";
//print_r($result);

//Select profiles by query
//$result = $ssapi->profiles(["user_number"=>105710]);
//echo "<h2 style='color: blueviolet;'>PROFILES:</h2>";
//result_print($result);

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
//$result = $ssapi->items(['item_number' => 7734], NULL, SSAPI_CONVERTER_WEB);
//echo "<h2 style='color: blueviolet;'>ITEMS SELECT:</h2>";
//echo "\n\n".count($result[0]['options'])."\n\n";
//var_dump($result);

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
//while($result = $ssapi->items_last_updated("2010-04-18 15:59:00.000Z", NULL, SSAPI_CONVERTER_WEB, ["order"=>["__service.updated"=>1], "limit"=>$limit, "skip"=>$skip])){
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
//
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