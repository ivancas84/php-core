<?php


function qr($url, $size = "300x300"){
  // CHart Type
  $cht = "qr";

  // CHart Size
  $chs = $size;

  // CHart Link
  // the url-encoded string you want to change into a QR code
  $chl = urlencode($url);

  // CHart Output Encoding (optional)
  // default: UTF-8
  $choe = "UTF-8";

  return 'https://chart.googleapis.com/chart?cht=' . $cht . '&chs=' . $chs . '&chl=' . $chl . '&choe=' . $choe;
  // echo $_GET["url"]."<br>".urldecode($_GET["url"])."<br>".urlencode($_GET["url"]);
  // die();

}