<?php

include_once 'dbconn.php';
include_once 'mainClass.php';

$mc = new Main();

if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'])) {
  $ownercode = $_GET["oc"];
  $ownercode = mysqli_real_escape_string( $mysqli, $mc->clean($ownercode) );

    $getData = "SELECT * FROM notifications WHERE ownercode='$ownercode'";
    $qur = $mysqli->query($getData);

    while($r = mysqli_fetch_assoc($qur)){

    $msg[] = array("notid" => $r['id'], "notmessage" => $r['message'], "notiftype" => $r['notiftype'], "dateadded" => $r['dateadded']);
    }

    $jsonArray = array("status" => true, "data" => $msg);

    header('content-type: application/json');
    echo json_encode($jsonArray);

    @mysqli_close($mysqli);


} else {
  // Not logged in
  $jsonArray = array("message" => "User is not signed in",  "status" => false);
  $jsonData = json_encode( $jsonArray );

  echo $jsonData;
}

?>
