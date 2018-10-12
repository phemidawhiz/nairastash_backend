<?php

include_once '../dbconn.php';
include_once '../mainClass.php';

$main = new Main();
$main->sec_session_start(); // Our custom secure way of starting a PHP session.

header('content-type: application/json');

if(isset($_SESSION['regcode'])) {
  if(isset($_GET['regcode'])) {
    $regcode = mysqli_real_escape_string( $mysqli, $main->clean($_GET['regcode']));
    $main->getMemberinfo($mysqli, $regcode);
  } else {
    $jsonArray = array("status" => false, "message" => "registeration code not set");
    echo json_encode($jsonArray);
  }
} else {
  $jsonArray = array("status" => false, "message" => "You're not signed in");
  echo json_encode($jsonArray);
}


?>
