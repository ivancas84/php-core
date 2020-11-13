<?php

function rest($url, $entityName, $api, array $display = null){
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($display));
    curl_setopt($curl, CURLOPT_URL, $url . "/" . $entityName . "/" . $api);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);

    return json_decode($result, true);
  }