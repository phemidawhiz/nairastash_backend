<?php

  include_once '../dbconn.php';
  include_once '../mainClass.php';

  $main = new Main();

  header('content-type: application/json');

  $ip = $main->getUserLocation();
  // Activiity score increased successfully.
  $data = file_get_contents('http://www.geoplugin.net/php.gp?ip=31.13.158.236');
  $jsonArray = array("ipaddress" => $ip, "status" => true, "ipdata" => $data);
  $jsonData = json_encode( $jsonArray );
  echo $jsonData;

?>
