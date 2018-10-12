<?php

// include dependencies
include_once 'dbconn.php';
include_once 'mainclass.php';

// Initiate main class
$mc = new Main();
$mc->sec_session_start(); // Our custom secure way of starting a PHP session.

header($ACAO, false);

//Validate json echoed out
header('content-type: application/json');

//Make sure that it is a POST request.
if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0) {
  throw new Exception('Request method must be POST!');
}

//Make sure that the content type of the POST request has been set to application/json
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if(strcasecmp($contentType, 'application/json') != 0) {
  throw new Exception('Content type must be: application/json');
}

//Receive the RAW post data.
$content = trim(file_get_contents("php://input"));

//Attempt to decode the incoming RAW post data from JSON.
$decoded = json_decode($content, true);

//If json_decode failed, the JSON is invalid.
if(!is_array($decoded)) {
  throw new Exception('Received content contained invalid JSON!');
} else {
  if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {

    //Fetch form JSON details
    $reqamount = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["reqamount"]));

    // get reg code from session
    $reqmemcode = $_SESSION['regcode'];
    $reqmememail = $_SESSION['email'];
    $transcode = rand(10,2999) . strtolower(substr($reqmememail,3,1)) . rand(3000,6999) . strtolower(substr($reqmememail,-1,1)) . rand(7000,9999) ;

      // fetch pending payment and wallet balance info from database
      $sql1 = "SELECT pendingcomm, affiliatecomm, acrecieved FROM members WHERE regcode = '$reqmemcode' ";
      $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
      $data = mysqli_fetch_assoc($result1);

      if($result1) {
        if($data['pendingcomm'] == 0 ) {
          $commBalance = $data['affiliatecomm'] - $data['acrecieved'];
          if($commBalance >= $reqamount && $reqamount >= 1000 ) {
            // Insert new record into transactions table
            $insert_stmt = $mysqli->prepare("INSERT INTO transactions (membercode, memberemail, amount, transcode) VALUES (?, ?, ?, ?)") ;

            $insert_stmt->bind_param('ssis', $reqmemcode, $reqmememail, $reqamount, $transcode);

            // Execute prepared query
            if($insert_stmt->execute()) {
              //Update pendingpayment field of member table
              $sql = "UPDATE members SET pendingcomm = '$reqamount' WHERE regcode='$reqmemcode'";
              $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli));

              if($result) {
                $message1 = "You have successfully request commission payment. Your request will be processed within 12 hours and payment will be made within 24 hours.";
                $notiftype1 = "request";
                $message2 = "New payment request";
                $notiftype2 = "requestnot";
                $mc->paymentNotification($mysqli, $message1, $_SESSION['regcode'], $notiftype1);
                $mc->paymentNotification($mysqli, $message2, "j34t53g", $notiftype2);
                $jsonArray = array("message" => "Request successful. Payment will be made within 24 hours",  "status" => true);
                $jsonData = json_encode( $jsonArray );
                echo $jsonData;
              } else {
                $jsonArray = array("message" => "Error processing data",  "status" => false);
                $jsonData = json_encode( $jsonArray );
                echo $jsonData;
              }

            } else {
              $jsonArray = array("message" => "Error processing data",  "status" => false);
              $jsonData = json_encode( $jsonArray );
              echo $jsonData;
            }

          } else {
            if($commBalance < $reqamount) {
              $jsonArray = array("message" => "Insufficient commission balance!",  "status" => false);
              $jsonData = json_encode($jsonArray);
              echo $jsonData;
            } elseif ($reqamount < 1000) {
              $jsonArray = array("message" => "Invalid request amount: minimum commission payout is ₦1000!",  "status" => false);
              $jsonData = json_encode($jsonArray);
              echo $jsonData;
            }
          }
        } else {
          $jsonArray = array("message" => "You have a pending commission payment request. Only one request can be made at a time. Please try again later", "status" => false);
          $jsonData = json_encode( $jsonArray);
          echo $jsonData;
          die();
        }
      }
  } else {
      $jsonArray = array("message" => "Unable to process request. Please login", "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
      die();
    }
}

?>
