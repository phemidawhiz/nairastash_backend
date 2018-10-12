<?php

// include dependencies
include_once '../dbconn.php';
include_once '../mainclass.php';

// Initiate main class
$mc = new Main();
$mc->sec_session_start(); // Our custom secure way of starting a PHP session.
//Validate json echoed out
header('content-type: application/json');



  if(isset($_GET["mememail"], $_GET["refcode"])) {
    //Fetch form JSON details
    $paymentMemberEmail = mysqli_real_escape_string( $mysqli, $mc->clean($_GET["mememail"]));
    $paymentRefCode = mysqli_real_escape_string( $mysqli, $mc->clean($_GET["refcode"]));

      // fetch pending payment and wallet balance info from database
      $sql1 = "SELECT regcode, firstcode, secondcode, thirdcode, fullname FROM members WHERE mememail = '$paymentMemberEmail' ";
      $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
      $data = mysqli_fetch_assoc($result1);

      if($result1) {
        $firstcode = $data['firstcode'];
        $secondcode = $data['secondcode'];
        $thirdcode = $data['thirdcode'];
        $regisCode = $data['regcode'];
        $fullname = $data['fullname'];

        $fnsql = "SELECT fullname FROM members WHERE regcode = '$firstcode' LIMIT 1";
        $fnresult = mysqli_query($mysqli, $fnsql) or die(mysqli_error($mysqli));
        $fndata = mysqli_fetch_assoc($fnresult);
        $fname = $fndata['fullname'];

        $snsql = "SELECT fullname FROM members WHERE regcode = '$secondcode' LIMIT 1";
        $snresult = mysqli_query($mysqli, $snsql) or die(mysqli_error($mysqli));
        $sndata = mysqli_fetch_assoc($snresult);
        $sname = $sndata['fullname'];

        $firstmessage = "You have a new platinum downline ".$fullname." .";
        $secondmessage = "You have a new diamond downline ".$fullname. " referred by " . $fname;
        $thirdmessage = "You have a new ruby downline ".$fullname. " referred by " . $fname . " who was reffered by " . $sname;
        $mc->UpdateAndNotify($mysqli, $firstmessage, $firstcode, "referral", "firstcode");
        $mc->UpdateAndNotify($mysqli, $secondmessage, $secondcode, "referral", "secondcode");
        $mc->UpdateAndNotify($mysqli, $thirdmessage, $thirdcode, "referral", "thirdcode");
        $mc->activateUser($mysqli, $regisCode);
            // Insert new record into transactions table
            $regFee = 5000;
            $insert_stmt = $mysqli->prepare("INSERT INTO transactions (membercode, memberemail, amount, transcode, transtype) VALUES (?, ?, ?, ?, ?)") ;
            $transtype = 2;
            $insert_stmt->bind_param('ssisi', $regisCode, $paymentMemberEmail, $regFee, $paymentRefCode, $transtype);

            // Execute prepared query
            if($insert_stmt->execute()) {
              $paymentMethod = "paystack";
              $sql = "UPDATE members SET paymentmethod = '$paymentMethod' WHERE regcode='$regisCode'";
              $result = mysqli_query($mysqli, $sql) or die("Unable to update member record: " . mysqli_error($mysqli));

            } else {
              $jsonArray = array("message" => "Error logging payment",  "status" => false);
              $jsonData = json_encode( $jsonArray );
              echo $jsonData;
            }
      }
  } else {
    $jsonArray = array("message" => "Member email or reference code not set",  "status" => false);
    $jsonData = json_encode( $jsonArray );
    echo $jsonData;
  }



?>
