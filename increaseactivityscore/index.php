<?php
include_once '../dbconn.php';
include_once '../mainclass.php';

$main = new Main();
$main->sec_session_start(); // Our custom secure way of starting a PHP session.

//Validate json echoed out
header('content-type: application/json');

if(isset($_SESSION['email']) && isset($_GET['score'])) {
  $memcode = mysqli_real_escape_string( $mysqli, $main->clean( $_SESSION['regcode']));
  $scorepoints = mysqli_real_escape_string( $mysqli, $main->clean( $_GET['score']));
  $main->increaseActivityScore($mysqli, $memcode, $scorepoints);
} else {
  // user not logged in or score increment value not set.
  if(isset($_GET['refcode'])) {
    $memcode = mysqli_real_escape_string( $mysqli, $main->clean($_GET['refcode']));
    $scorepoints = mysqli_real_escape_string( $mysqli, $main->clean( $_GET['score']));
    $main->increaseActivityScore($mysqli, $memcode, $scorepoints);
  } else {
    $jsonArray = array("message" => "unable to perform operation",  "status" => false);
    $jsonData = json_encode( $jsonArray );
    echo $jsonData;
  }

}

?>
