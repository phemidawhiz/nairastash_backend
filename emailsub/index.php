<?php

include_once '../dbconn.php';
include_once '../mainClass.php';

$mc = new Main();
$mc->sec_session_start(); // our custom way of starting a session

header('content-type: application/json');

if (isset($_GET['subemail'])) {
  $subemail = mysqli_real_escape_string( $mysqli, $mc->clean($_GET['subemail']));
  // check if email already exist in token table
  $sql1 = "SELECT email FROM emailsub WHERE email = '$subemail' ";
  $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
  $data = mysqli_fetch_assoc($result1);

  if($result1) {
    if(mysqli_num_rows($result1) == 0) {
      // Insert new token and corresponding email into the database
      $insert_stmt = $mysqli->prepare("INSERT INTO emailsub (email) VALUES (?)") ;
      $insert_stmt->bind_param('s', $subemail);

      // Execute prepared query for inserting new tokenr.
      if($insert_stmt->execute()) {
        $jsonArray = array("message" => "You'll start receiving email from us soon",  "status" => true);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
      } else {
        $jsonArray = array("message" => "Unable to finish subscription",  "status" => false);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
      }
    } else {
      $jsonArray = array("message" => "This email is already in our database",  "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
    }
  } else {
    $jsonArray = array("message" => "Unable to complete request",  "status" => false);
    $jsonData = json_encode( $jsonArray );
    echo $jsonData;
  }


} else {
  // Not logged in
  $jsonArray = array("message" => "email not set",  "status" => false);
  $jsonData = json_encode( $jsonArray );

  echo $jsonData;
}

?>
