<?php

// include dependencies
include_once '../dbconn.php';
include_once '../mainclass.php';

// Initiate main class
$mc = new Main();
$mc->sec_session_start(); // Our custom secure way of starting a PHP session.
//Validate json echoed out
header('content-type: application/json');


  if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {
    if($_SESSION['urole'] >= 4) {

      if(isset($_GET["memcode"])) {
        //Fetch form JSON details
        $paymentMemberCode = mysqli_real_escape_string( $mysqli, $mc->clean($_GET["memcode"]));

        // get reg code from session
        $adminEmail = $_SESSION['email'];
        $transcode = rand(10,2999) . strtolower(substr($adminEmail,3,1)) . rand(3000,6999) . strtolower(substr($adminEmail,-1,1)) . rand(7000,9999) ;

          // fetch pending payment and wallet balance info from database
          $sql1 = "SELECT pendingcomm, acrecieved, affiliatecomm FROM members WHERE regcode = '$paymentMemberCode' ";
          $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
          $data = mysqli_fetch_assoc($result1);

          if($result1) {
            if($data['pendingcomm'] != 0 ) {
              $pendingcomm = $data['pendingcomm'];
              $acrecieved = $data['acrecieved'];
              $affiliatecomm = $data['affiliatecomm'];
              if($pendingcomm >= 1000 ) {
                // Insert new record into transactions table
                $insert_stmt = $mysqli->prepare("INSERT INTO transactions (membercode, memberemail, amount, transcode, transtype) VALUES (?, ?, ?, ?, ?)") ;
                $transtype = 1;
                $insert_stmt->bind_param('ssisi', $paymentMemberCode, $adminEmail, $pendingcomm, $transcode, $transtype);

                // Execute prepared query
                if($insert_stmt->execute()) {
                  //Update pendingpayment field of member table
                  $sql = "UPDATE members SET pendingcomm = pendingcomm - $pendingcomm, acrecieved = acrecieved + $pendingcomm WHERE regcode='$paymentMemberCode'";
                  $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli));

                  if($result) {
                    $message1 = "You have successfully authorise payment ";
                    $notiftype1 = "payment";
                    $message2 = "A new commission payment has been authorised. Check your bank account from time to time. If you're not credited after 24 hours from now, please contact us ";
                    $notiftype2 = "payment";
                    $mc->paymentNotification($mysqli, $message1, $_SESSION['regcode'], $notiftype1);
                    $mc->paymentNotification($mysqli, $message2, $paymentMemberCode, $notiftype2);
                    $jsonArray = array("message" => "Payment successful. User info is updated",  "status" => true);
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
                $jsonArray = array("message" => "Invalid request amount: minimum payout is ₦1000!",  "status" => false);
                $jsonData = json_encode($jsonArray);
                echo $jsonData;
              }
            } else {
              $jsonArray = array("message" => "This user did not request any payment", "status" => false);
              $jsonData = json_encode( $jsonArray);
              echo $jsonData;
              die();
            }
          }
      } else {
        $jsonArray = array("message" => "Member code not set",  "status" => false);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
      }

    } else {
      $jsonArray = array("message" => "Only Admin Members Can Authorise Payment", "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
      die();
    }

  } else {
      $jsonArray = array("message" => "Unable to process request. Please login", "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
      die();
    }


?>
