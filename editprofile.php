<?php

// include dependencies
include_once 'dbconn.php';
include_once 'mainclass.php';

// Initiate main class
$mc = new Main();
$mc->sec_session_start();

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
  //Fetch form JSON details
  if(isset($_SESSION['regcode'])) {
    $regcode = $_SESSION['regcode'];
    $fullname = mysqli_real_escape_string( $mysqli, $mc->clean($decoded['fullname']));
    $phonenumber = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["phonenumber"]));
    $acctnum = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["acctnum"]));
    $mememail = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["mememail"]));
    $address = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["address"]));
    $occupation = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["occupation"]));
    $bankname = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["bankname"]));

    // check if phone number, email or account number already exist
    $sql1 = "SELECT phonenumber, mememail, acctnum FROM members WHERE regcode <> '$regcode' AND (phonenumber = '$phonenumber' OR mememail = '$mememail')  LIMIT 1";
    $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
    $data = mysqli_fetch_assoc($result1);

    if($result1) {
      if(mysqli_num_rows($result1) == 1) {
        if($data['phonenumber'] == $phonenumber ) {
          $jsonArray = array("message" => "Phone number already exist in our database, please use another", "status" => false);
          $jsonData = json_encode( $jsonArray );
          echo $jsonData;
          die();
        } elseif ($data['mememail'] == $mememail ) {
          $jsonArray = array("message" => "Email already exist in our database, please use another", "status" => false);
          $jsonData = json_encode( $jsonArray );
          echo $jsonData;
          die();
        } elseif ($data['acctnum'] == $acctnum ) {
          $jsonArray = array("message" => "Account number already exist in our database, please use another", "status" => false);
          $jsonData = json_encode( $jsonArray );
          echo $jsonData;
          die();
        }
      }
    }

    /* $sql = "UPDATE members SET fullname = '$fullname', phonenumber = '$phonenumber', acctnum = '$acctnum', mememail = '$mememail', address = '$address', occupation = '$occupation', bankname = '$bankname' WHERE regcode='$regcode'";
    $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli)); */

    $sql = "UPDATE members SET fullname = ?, phonenumber = ?, acctnum = ?, mememail = ?, address = ?, occupation = ?, bankname = ? WHERE regcode  = ?" ;
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssssssss', $fullname, $phonenumber, $acctnum, $mememail, $address, $occupation, $bankname, $regcode);

    if($stmt->execute()) {
      // Profile updated successfully..
      $jsonArray = array("message" => "Your profile was successfully updated!",  "status" => true);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
    } else {
      // Profile could not be updated.
      $jsonArray = array("message" => "Unable to process request",  "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
    }

  } else {
    $jsonArray = array("message" => "Your're not signed in", "status" => false);
    $jsonData = json_encode( $jsonArray );
    echo $jsonData;
    die();
  }

}

?>
