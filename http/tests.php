<?php 
$data = json_decode('{"some": ["data random"], "and": ["lol"]}');

function http_build_query_custom($datas) {

    $finalstr = '';
    foreach($datas as $data_key => $data_values) {
        // var_dump($data_key);
        // var_dump($data_value);
        foreach($data_values as $data_value) {
            $finalstr = $finalstr . urlencode($data_key) . '=' . urlencode($data_value) . '&';
        }
    }

    return rtrim($finalstr, '&');
}

print(http_build_query_custom($data));