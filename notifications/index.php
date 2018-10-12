<?php

include_once '../dbconn.php';
include_once '../mainClass.php';

$mc = new Main();
$mc->sec_session_start(); // our custom way of starting a session

header('content-type: application/json');

if (isset($_SESSION['user_id'], $_GET['notiftype'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {
  $ownercode = $_SESSION["regcode"];
  $notType = mysqli_real_escape_string( $mysqli, $mc->clean($_GET['notiftype']));
  if($notType !== "other") {

      $getData = "SELECT * FROM notifications WHERE ownercode='$ownercode' AND notiftype='$notType' ORDER BY id DESC ";
      $qur = $mysqli->query($getData);
      $msg = [];
      $isreadcount = 0;

      if(mysqli_num_rows($qur) != 0) {
        while($r = mysqli_fetch_assoc($qur)){
          if($r['readmsg'] == 0) {
            $isreadcount++;
          }
            $msg[] = array("notID" => $r['id'],"message" => $r['message'], "notiftype" => $r['notiftype'], "isread" => $r['readmsg'], "dateadded" => $r['dateadded']);
        }
      }

      $jsonArray = array("status" => true, "data" => $msg, "readcount" => $isreadcount);

      echo json_encode($jsonArray);

      @mysqli_close($mysqli);
  } else {

      $getData = "SELECT * FROM notifications WHERE ownercode='$ownercode' AND notiftype='$notType' ORDER BY id DESC ";
      $qur = $mysqli->query($getData);
      $msg = [];
      $isreadcount = 0;

      if(mysqli_num_rows($qur) != 0) {
        while($r = mysqli_fetch_assoc($qur)){
          if($r['isread'] == 0) {
            $isreadcount++;
          }
            $msg[] = array("notID" => $r['id'],"message" => $r['message'], "notiftype" => $r['notiftype'], "isread" => $r['readmsg'], "dateadded" => $r['dateadded']);
        }
      }

      $jsonArray = array("status" => true, "data" => $msg, "readcount" => $isreadcount);

      echo json_encode($jsonArray);

      @mysqli_close($mysqli);
  }



} else {
  // Not logged in
  $jsonArray = array("message" => "Invalid Resquest",  "status" => false);
  $jsonData = json_encode( $jsonArray );

  echo $jsonData;
}

?>
