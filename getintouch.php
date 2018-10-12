<?php

// include dependencies
include_once 'dbconn.php';
include_once 'mainclass.php';

// Initiate main class
$mc = new Main();

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
  $fullname = mysqli_real_escape_string( $mysqli, $mc->clean($decoded['fullname']));
  $message = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["message"]));
  $subject = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["subject"]));
  $email = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["email"]));

  // check if phone number and email already exist
  $sql1 = "SELECT email FROM getintouch WHERE email = '$email'";
  $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
  $data = mysqli_fetch_assoc($result1);

  if($result1) {
    if(mysqli_num_rows($result1) > 0) {
      $jsonArray = array("message" => "Email already exist in our database, please use another", "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
      die();
    }
  }

  // Insert new subscriber into the database
  $insert_stmt = $mysqli->prepare("INSERT INTO getintouch (fullname, email, message, subject) VALUES (?, ?, ?, ?)");

  $insert_stmt->bind_param('ssss', $fullname, $email, $message, $subject);

  // Execute prepared query for inserting new subscriber.
  if($insert_stmt->execute()) {
    $jsonArray = array("message" => "Thanks for taking time out to reach us. " . $fullname . ". We'll be in touch.",  "status" => true);
    $jsonData = json_encode( $jsonArray );

    echo $jsonData;
  } else {
    $jsonArray = array("message" => "Error processing data",  "status" => false);
    $jsonData = json_encode( $jsonArray );
    echo $jsonData;
  }
}

?>
