<?php

include_once '../dbconn.php';
include_once '../mainClass.php';

$mc = new Main();
$mc->sec_session_start(); // our custom way of starting a session

header('content-type: application/json');

if (isset( $_GET['actiontype']) && $_GET['actiontype'] != "") {
  $actionType = mysqli_real_escape_string( $mysqli, $mc->clean($_GET['actiontype']));
  if($actionType == "sendlink") {



    if(isset( $_GET['prlemail']) && $_GET['prlemail'] != "") {
      $prlemail = mysqli_real_escape_string( $mysqli, $mc->clean($_GET['prlemail']));
      $prtoken = rand(10,99) . rand(100,564) . rand(10,99) . strtolower(substr($prlemail,-1,1)) . rand(10,99) . strtolower(substr($prlemail,-3,1));

      // check if there is any member registered with the email
      $sqlb = "SELECT mememail FROM members WHERE mememail = '$prlemail' ";
      $resultb = mysqli_query($mysqli,$sqlb) or die(mysqli_error($mysqli));
      $datab = mysqli_fetch_assoc($resultb);

      if($resultb) {
        if(mysqli_num_rows($resultb) == 0) {
            $jsonArray = array("message" => "Email provided does not exist in our database",  "status" => false);
            $jsonData = json_encode( $jsonArray );
            echo $jsonData;
            die();
        }
      }

      // check if there is any member with email provided
      $sql1 = "SELECT premail FROM prtokens WHERE premail = '$prlemail' ";
      $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
      $data = mysqli_fetch_assoc($result1);

      if($result1) {
        if(mysqli_num_rows($result1) > 0) {
            $sql = "UPDATE prtokens SET prtoken='$prtoken' WHERE premail='$prlemail' AND used = 0";
            $result = mysqli_query($mysqli, $sql) or die("Unable to update token table: " . mysqli_error($mysqli));
            $jsonArray = array("message" => "Please check your email for password reset link",  "status" => true);
            $jsonData = json_encode( $jsonArray );
            echo $jsonData;
            die();
        }
      }

      // check if email already exist in token table
      $sql1 = "SELECT premail FROM prtokens WHERE premail = '$prlemail' ";
      $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
      $data = mysqli_fetch_assoc($result1);

      if($result1) {
        if(mysqli_num_rows($result1) > 0) {
            $sql = "UPDATE prtokens SET prtoken='$prtoken' WHERE premail='$prlemail' AND used = 0";
            $result = mysqli_query($mysqli, $sql) or die("Unable to update token table: " . mysqli_error($mysqli));
            $mailContent = "Please click the link below to reset your password <br><br> " . "http://localhost/refmoney/api/passwordreset/?actiontype=resetpwd". "&prltoken=" . $prtoken;
            mail($prlemail, 'Password Reset', $mailContent, 'From: info@prudentialearners.com');
            $jsonArray = array("message" => "Please check your email for password reset link",  "status" => true);
            $jsonData = json_encode( $jsonArray );
            echo $jsonData;
            die();
        }
      }


      // Insert new token and corresponding email into the database
      $insert_stmt = $mysqli->prepare("INSERT INTO prtokens (premail, prtoken) VALUES (?, ?)") ;
      $insert_stmt->bind_param('ss', $prlemail, $prtoken);

      // Execute prepared query for inserting new tokenr.
      if($insert_stmt->execute()) {
        $jsonArray = array("message" => "Please check your email for password reset link",  "status" => true);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
      } else {
        $jsonArray = array("message" => "Unable to send password reset link due to system error",  "status" => false);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
      }
    } else {
      $jsonArray = array("status" => false, "message" => "Email not set!");
      echo json_encode($jsonArray);
    }
  } elseif ($actionType == "resetpwd") {
      if(isset( $_GET['prltoken'], $_GET['newpassword']) && $_GET['prltoken'] != "" && $_GET['newpassword'] != "") {
        $prlToken = mysqli_real_escape_string( $mysqli, $mc->clean($_GET['prltoken']));
        $newpassword = md5(mysqli_real_escape_string( $mysqli, $mc->clean($_GET['newpassword'])));

        // check if token is in token table
        $sql1 = "SELECT prtoken, premail FROM prtokens WHERE prtoken = '$prlToken' ";
        $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
        $data = mysqli_fetch_assoc($result1);

        if($result1) {
          if(mysqli_num_rows($result1) > 0) {
              $premail = $data["premail"];
              $sql = "UPDATE members SET password='$newpassword' WHERE mememail='$premail' ";
              $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli));
              $jsonArray = array("message" => "Password Updated Successfully",  "status" => true);
              $jsonData = json_encode( $jsonArray );
              echo $jsonData;
              die();
          } else {
            $jsonArray = array("message" => "Invalid or expired token! Click 'Send New Link' to get new password reset link ",  "status" => false);
            $jsonData = json_encode( $jsonArray );
            echo $jsonData;
            die();
          }
        }
      } else {
        $jsonArray = array("status" => false, "message" => "token or password not set!");
        echo json_encode($jsonArray);
      }

  } else {
      $jsonArray = array("status" => false, "message" => "Reset action not defined!");
      echo json_encode($jsonArray);
  }


} else {
  // Not logged in
  $jsonArray = array("message" => "Invalid Resquest",  "status" => false);
  $jsonData = json_encode( $jsonArray );

  echo $jsonData;
}

?>
