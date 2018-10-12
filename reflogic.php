<?php
include_once('dbconn.php');

Class RefLogic {

  public function getDownlinesByType($mysqli, $dltype) {

    if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {
      $referrercode = $_SESSION["regcode"];
      if($dltype == "platinum") {
        $getData = "SELECT * FROM referrals WHERE referrercode='$referrercode' ORDER BY id DESC";
        $qur = $mysqli->query($getData) or mysqli_error($mysqli);
        $msg = [];

        if(mysqli_num_rows($qur) != 0) {
          while($r = mysqli_fetch_assoc($qur)){
              $msg[] = array("referredcode" => $r['referredcode'], "refferedfullname" => $r['refferedfullname'], "referrerfullname" => $r['referrerfullname'], "referrercode" => $r['referrercode'], "secondcodefullname" => $r['secondcodefullname'], "secondcode" => $r['secondcode'], "thirdcodefullname" => $r['thirdcodefullname'], "thirdcode" => $r['thirdcode'], "dateadded" => $r['dateadded']);
          }
        }

        $jsonArray = array("status" => true, "data" => $msg);

        echo json_encode($jsonArray);

        @mysqli_close($mysqli);
      }

      if($dltype == "diamond") {
        $getData = "SELECT * FROM referrals WHERE secondcode='$referrercode'";
        $qur = $mysqli->query($getData) or mysqli_error($mysqli);
        $msg = [];

        if(mysqli_num_rows($qur) != 0) {
          while($r = mysqli_fetch_assoc($qur)){
              $msg[] = array("referredcode" => $r['referredcode'], "refferedfullname" => $r['refferedfullname'], "referrerfullname" => $r['referrerfullname'], "referrercode" => $r['referrercode'], "secondcodefullname" => $r['secondcodefullname'], "secondcode" => $r['secondcode'], "thirdcodefullname" => $r['thirdcodefullname'], "thirdcode" => $r['thirdcode'], "dateadded" => $r['dateadded']);
          }
        }

        $jsonArray = array("status" => true, "data" => $msg);

        echo json_encode($jsonArray);

        @mysqli_close($mysqli);
      }

      if($dltype == "ruby") {
        $getData = "SELECT * FROM referrals WHERE thirdcode='$referrercode'";
        $qur = $mysqli->query($getData) or mysqli_error($mysqli);
        $msg = [];

        if(mysqli_num_rows($qur) != 0) {
          while($r = mysqli_fetch_assoc($qur)) {
              $msg[] = array("referredcode" => $r['referredcode'], "refferedfullname" => $r['refferedfullname'], "referrerfullname" => $r['referrerfullname'], "referrercode" => $r['referrercode'], "secondcodefullname" => $r['secondcodefullname'], "secondcode" => $r['secondcode'], "thirdcodefullname" => $r['thirdcodefullname'], "thirdcode" => $r['thirdcode'], "dateadded" => $r['dateadded']);
          }
        }

        $jsonArray = array("status" => true, "data" => $msg);

        echo json_encode($jsonArray);

        @mysqli_close($mysqli);
      }

      if($dltype == "all") {
        $getData = "SELECT refferedfullname, dateadded, referredcode, refferedemail FROM referrals WHERE thirdcode='$referrercode'";
        $qur = $mysqli->query($getData) or mysqli_error($mysqli);
        $msg = [];

        if(mysqli_num_rows($qur) != 0) {
          while($r = mysqli_fetch_assoc($qur)){
              $msg[] = array("referredcode" => $r['referredcode'], "refferedfullname" => $r['refferedfullname'], "refferedEmail" => $r['refferedemail'], "dateadded" => $r['dateadded']);
          }
        }

        $jsonArray = array("status" => true, "data" => $msg);

        echo json_encode($jsonArray);

        @mysqli_close($mysqli);
      }

    } else {
      // Not logged in
      $jsonArray = array("message" => "User is not signed in",  "status" => false);
      $jsonData = json_encode( $jsonArray );

      echo $jsonData;
    }
  }

  public function getPendingPaymentRequests($mysqli) {
    if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {
      if($_SESSION['urole'] >= 4) {
        $getData = "SELECT pendingpayment, regcode, fullname FROM members WHERE pendingpayment <> 0";
        $qur = $mysqli->query($getData) or mysqli_error($mysqli);
        $msg = [];

        if(mysqli_num_rows($qur) != 0) {
          while($r = mysqli_fetch_assoc($qur)) {
              $msg[] = array("paymentAmount" => $r['pendingpayment'], "regCode" => $r['regcode'], "fullName" => $r['fullname']);
          }
        }

        $jsonArray = array("status" => true, "data" => $msg);

        echo json_encode($jsonArray);

        @mysqli_close($mysqli);
      } else {
        // low priviledge
        $jsonArray = array("message" => "You're not authorised to access this information",  "status" => false);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
      }
    } else {
      // Not logged in
      $jsonArray = array("message" => "User is not signed in",  "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
    }
  }

  public function getTransactions($mysqli) {
    if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {
      if($_SESSION['urole'] >= 4) {
        $getData = "SELECT * FROM transactions ORDER BY id DESC";
        $qur = $mysqli->query($getData) or mysqli_error($mysqli);
        $msg = [];
        $isreadcount = 0;
        if(mysqli_num_rows($qur) != 0) {
          while($r = mysqli_fetch_assoc($qur)) {

            if($r['isread'] == 0) {
              $isreadcount++;
            }
            $msg[] = array("membercode" => $r['membercode'], "transtype" => $r['transtype'], "amount" => $r['amount'], "transcode" => $r['transcode'], "memberemail" => $r['memberemail'], "transdate" => $r['transdate'], "isread" => $r['isread']);
          }
        }

        $jsonArray = array("status" => true, "data" => $msg, "readcount" => $isreadcount);

        echo json_encode($jsonArray);

        @mysqli_close($mysqli);
      } else {
        // normal user
        $mememail = $_SESSION['email'];
        $membcode = $_SESSION['regcode'];
        $getData = "SELECT * FROM transactions WHERE membercode = '$membcode'  ORDER BY id DESC";
        $qur = $mysqli->query($getData) or mysqli_error($mysqli);
        $msg = [];

        if(mysqli_num_rows($qur) != 0) {
          while($r = mysqli_fetch_assoc($qur)) {
            $msg[] = array("membercode" => $r['membercode'], "transtype" => $r['transtype'], "amount" => $r['amount'], "transcode" => $r['transcode'], "memberemail" => $r['memberemail'], "transdate" => $r['transdate'], "isread" => $r['isread']);
          }
        }

        $jsonArray = array("status" => true, "data" => $msg);

        echo json_encode($jsonArray);

        @mysqli_close($mysqli);
      }
    } else {
      // Not logged in
      $jsonArray = array("message" => "User is not signed in", "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
    }
  }
}

?>
