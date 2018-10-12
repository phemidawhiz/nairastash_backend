<?php

  include_once '../dbconn.php';
  include_once '../mainClass.php';

  $main = new Main();
  $main->sec_session_start(); // Our custom secure way of starting a PHP session.

  header('content-type: application/json');

if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {
  if($_SESSION['urole'] >= 4) {


  if(isset($_GET["regcode"])) {
    $regcode = mysqli_real_escape_string( $mysqli, $main->clean($_GET['regcode']));

    // fetch referral data
    $firstrefsql = "SELECT fullname, mememail, firstcode, secondcode, thirdcode, togglecount FROM members WHERE regcode = '$regcode' LIMIT 1";
    $firstrefresult = mysqli_query($mysqli, $firstrefsql) or die(mysqli_error($mysqli));
    $firstrefdata = mysqli_fetch_assoc($firstrefresult);

    if($firstrefresult) {
      $firstcode = $firstrefdata['firstcode'];
      $secondcode = $firstrefdata['secondcode'];
      $thirdcode = $firstrefdata['thirdcode'];
      $fullname = $firstrefdata['fullname'];
      $togglecount = $firstrefdata['togglecount'];
      $mememail = $firstrefdata['mememail'];

      if($togglecount == 0) {
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
        $main->UpdateAndNotify($mysqli, $firstmessage, $firstcode, "referral", "firstcode");
        $main->UpdateAndNotify($mysqli, $secondmessage, $secondcode, "referral", "secondcode");
        $main->UpdateAndNotify($mysqli, $thirdmessage, $thirdcode, "referral", "thirdcode");
        $main->activateUser($mysqli, $regcode);
        mail($mememail, 'Account Activation', "Your account has been activated. You can now login to access your profile", 'From: info@prudentialearners.com');
      } else {
        $main->activateUser($mysqli, $regcode);
        mail($mememail, 'Account Re-activation', "Your account has been re-activated. You can now login to access your profile", 'From: info@prudentialearners.com');
      }
    }

  } else {
    $jsonArray = array("status" => false, "message" => "invalid request");
    echo json_encode($jsonArray);
  }

} else {
  $jsonArray = array("message" => "Only Admin Members Activate Others", "status" => false);
  $jsonData = json_encode( $jsonArray );
  echo $jsonData;
  die();
}

} else {
  $jsonArray = array("message" => "Unable to process request.", "status" => false);
  $jsonData = json_encode( $jsonArray );
  echo $jsonData;
  die();
}



?>
