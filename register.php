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
  $phonenumber = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["phonenumber"]));
  $acctnum = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["acctnum"]));
  $password = md5(mysqli_real_escape_string( $mysqli, $mc->clean($decoded["password"])));
  $mememail = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["mememail"]));
  $address = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["address"]));
  $firstcode = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["firstcode"]));
  $occupation = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["occupation"]));
  $bankname = mysqli_real_escape_string( $mysqli, $mc->clean($decoded["bankname"]));

  //generate registeration code
  $regcode = strtolower(substr($fullname,3,1)) . rand(10,99) . strtolower(substr($fullname,-1,1)) . rand(10,99) . strtolower(substr($fullname,-3,1));

  // check if referral code exist
  $prep_stmt = "SELECT id FROM members WHERE regcode = ? LIMIT 1";
  $stmt = $mysqli->prepare($prep_stmt);

   if ($stmt) {
    $stmt->bind_param('s', $firstcode);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
            // Invalid referral code
        $jsonArray = array("message" => "Unable to complete registeration. Please check your referral code", "status" => false);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
        die();
      }
    }

    //get referral data for upper levels
  $secondcodesql = "SELECT firstcode, fullname, phonenumber, mememail, acctnum FROM members WHERE regcode = '$firstcode' LIMIT 1";
  $secondcoderesult = mysqli_query($mysqli, $secondcodesql) or die(mysqli_error($mysqli));
  $scdata = mysqli_fetch_assoc($secondcoderesult);
  $scphone = $scdata['phonenumber']; $scemail = $scdata['mememail']; $scacctnum = $scdata['acctnum']; $scfullname = $scdata['fullname'];
  $secondcode = $scdata['firstcode'];

  $thirdcodesql = "SELECT firstcode, fullname, phonenumber, mememail, acctnum FROM members WHERE regcode = '$secondcode' LIMIT 1";
  $thirdcoderesult = mysqli_query($mysqli, $thirdcodesql) or die(mysqli_error($mysqli));
  $tcdata = mysqli_fetch_assoc($thirdcoderesult);
  $tcphone = $tcdata['phonenumber']; $tcemail = $tcdata['mememail']; $tcacctnum = $tcdata['acctnum']; $tcfullname = $tcdata['fullname'];
  $thirdcode = $tcdata['firstcode'];

  $fourthcodesql = "SELECT firstcode, fullname, phonenumber, mememail, acctnum FROM members WHERE regcode = '$thirdcode' LIMIT 1";
  $fourthcoderesult = mysqli_query($mysqli, $fourthcodesql) or die(mysqli_error($mysqli));
  $fcdata = mysqli_fetch_assoc($fourthcoderesult);
  $fcphone = $fcdata['phonenumber']; $fcemail = $fcdata['mememail']; $fcacctnum = $fcdata['acctnum']; $fcfullname = $fcdata['fullname'];
  $fourthcode = $fcdata['firstcode'];

  // check if phone number, email or account number already exist
  $sql1 = "SELECT phonenumber, mememail, acctnum FROM members WHERE phonenumber = '$phonenumber' OR mememail = '$mememail' LIMIT 1";
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

    // Insert new member into the database
    $insert_stmt = $mysqli->prepare("INSERT INTO members (fullname, regcode, phonenumber, acctnum, password, mememail, address, firstcode, secondcode, thirdcode, occupation, bankname, activated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)") ;
    $activated = "0";
    $insert_stmt->bind_param('sssssssssssss', $fullname, $regcode, $phonenumber, $acctnum, $password, $mememail, $address, $firstcode, $secondcode, $thirdcode, $occupation, $bankname, $activated);

    // Execute prepared query for inserting new member.
    if($insert_stmt->execute()) {
      // get direct referrer data
      $firstrefsql = "SELECT fullname, phonenumber, mememail, acctnum FROM members WHERE regcode = '$firstcode' LIMIT 1";
      $firstrefresult = mysqli_query($mysqli, $firstrefsql) or die(mysqli_error($mysqli));
      $firstrefdata = mysqli_fetch_assoc($firstrefresult);
      $firstrefphone = $firstrefdata['phonenumber']; $firstrefemail = $firstrefdata['mememail']; $firstrefacctnum = $firstrefdata['acctnum']; $firstreffullname = $firstrefdata['fullname'];

      // Insert new referral information (for all levels) into the database
      $insert_ref = $mysqli->prepare("INSERT INTO referrals (referrercode, referredcode, referrerfullname, refferedfullname, refferedemail, secondcode, thirdcode, secondcodefullname, secondcodeemail, thirdcodefullname, thirdcodeemail, referreremail) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)") ;
      $insert_ref->bind_param('ssssssssssss', $firstcode, $regcode, $firstreffullname, $fullname, $mememail, $secondcode, $thirdcode, $tcfullname, $tcemail, $fcfullname, $fcemail, $firstrefemail);

      // Execute prepared query for inserting referral.
      if($insert_ref->execute()) {

        $jsonArray = array("message" => "Record saved! Choose a payment method to activate your account",  "status" => true);
        $jsonData = json_encode( $jsonArray );

        echo $jsonData;
      } else {
        $jsonArray = array("message" => "Unable to add refferal info",  "status" => false);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
      }

    } else {
      $jsonArray = array("message" => "Error processing request",  "status" => false);
      $jsonData = json_encode( $jsonArray );

      echo $jsonData;

    }
    /* close connection */
    mysqli_close($mysqli);
}
?>
