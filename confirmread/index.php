<?php

  include_once '../dbconn.php';
  include_once '../mainClass.php';

  $main = new Main();
  $main->sec_session_start(); // Our custom secure way of starting a PHP session.

  header('content-type: application/json');

  if(isset($_SESSION["regcode"], $_GET["notiftype"])) {
    $notiftype = mysqli_real_escape_string( $mysqli, $main->clean($_GET['notiftype']));
    $regcode = $_SESSION["regcode"];
    $main->confirmRead($mysqli, $regcode, $notiftype);
  } else {
    $jsonArray = array("status" => false, "message" => "invalid request");
    echo json_encode($jsonArray);
  }


?>
