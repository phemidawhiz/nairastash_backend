<?php
include_once('dbconn.php');

Class Main {
    public function sec_session_start() {
      $session_name = 'sec_session_id';   // Set a custom session name
      //$secure = SECURE;

      // Forces sessions to only use cookies.
      if (ini_set('session.use_only_cookies', 1) === FALSE) {
        $jsonArray = array("message" => "Could not initiate a safe session",  "status" => false);
        $jsonData = json_encode( $jsonArray );

        echo $jsonData;
      }
      // Gets current cookies params.
      $cookieParams = session_get_cookie_params();
      session_set_cookie_params($cookieParams["lifetime"],
          $cookieParams["path"],
          $cookieParams["domain"]
          );
      // Sets the session name to the one set above.
      session_name($session_name);
      session_start();            // Start the PHP session
  		 $_SESSION['started'] = $_SERVER['REQUEST_TIME'];

      session_regenerate_id();    // regenerated the session, delete the old one.
    }

    public function login($email, $password, $mysqli) {
  	$sqli = "SELECT id, regcode, mememail, role, password, activated, togglecount FROM members WHERE mememail = '$email' AND password = '$password' LIMIT 1 ";
  	$resulti = mysqli_query($mysqli, $sqli) or die('could not retreive login details ' . mysqli_error($mysqli));
  	$rowi = mysqli_fetch_assoc($resulti);

      if ($resulti) {
    		$user_id = $rowi['id'];
    		$useremail = $rowi['mememail'];
    		$db_password = $rowi['password'];
        $urole = $rowi['role'];
        $memcode = $rowi['regcode'];
        $activated = $rowi['activated'];
        $togglecount = $rowi['togglecount'];

        if (mysqli_num_rows($resulti) == 1) {
          if($activated == "0" && $togglecount == 0) {
            // user exists but not activated.
            $jsonArray = array("message" => "Your account is not activated yet",  "status" => false);
            $jsonData = json_encode( $jsonArray );
            echo $jsonData;
            die();
          } else if($activated == "0" && $togglecount > 0) {
            $jsonArray = array("message" => "Your account was suspended by admin",  "status" => false);
            $jsonData = json_encode( $jsonArray );
            echo $jsonData;

            die();
          }
          // Get the user-agent string of the user.
          $user_browser = $_SERVER['HTTP_USER_AGENT'];

          // XSS protection as we might print this value
          $user_id = preg_replace("/[^0-9]+/", "", $user_id);
          $_SESSION['user_id'] = $user_id;

          // XSS protection as we might print this value

          $_SESSION['email'] = $useremail;
          $_SESSION['urole'] = $urole;
          $_SESSION['regcode'] = $memcode;
          $_SESSION['activated'] = $activated;
          $_SESSION['login_string'] = hash('sha512', $password . $user_browser);

          // Login successful.
          $jsonArray = array("message" => "Login Successful", "useremail" => $_SESSION['email'], "regcode" => $_SESSION['regcode'], "urole" => $_SESSION['urole'], "activated" => $activated, "status" => true);
          $jsonData = json_encode( $jsonArray );

          echo $jsonData;
      } else {
        // No user exists.
        $jsonArray = array("message" => "Unable to login. Please check your email or password",  "status" => false);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
      }
    }
  }

  public function login_check($mysqli) {
      // Check if all session variables are set
      if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {
          $user_id = $_SESSION['user_id'];
          $login_string = $_SESSION['login_string'];
          $useremail = $_SESSION['email'];

          // Get the user-agent string of the user.
          $user_browser = $_SERVER['HTTP_USER_AGENT'];

          if ($stmt = $mysqli->prepare("SELECT password
  				      FROM members
  				      WHERE id = ? LIMIT 1")) {
              // Bind "$user_id" to parameter.
              $stmt->bind_param('i', $user_id);
              $stmt->execute();   // Execute the prepared query.
              $stmt->store_result();

              if ($stmt->num_rows == 1) {

                $stmt->bind_result($password);
                $stmt->fetch();
                $login_check = hash('sha512', $password . $user_browser);

                // logged in.
                $jsonArray = array("message" => "User is signed in",  "status" => true);
                $jsonData = json_encode( $jsonArray );

                echo $jsonData;

              } else {
                // Not logged in
                $jsonArray = array("message" => "User is not signed in",  "status" => false);
                $jsonData = json_encode( $jsonArray );

                echo $jsonData;
              }
          } else {
              // Could not prepare statement
              $jsonArray = array("message" => "Could not prepare statement", "status" => false);
              $jsonData = json_encode( $jsonArray );
              echo $jsonData;
          }
      } else {
          // session variables not set
          $jsonArray = array("message" => "User is not signed in",  "status" => false);
          $jsonData = json_encode( $jsonArray );
          echo $jsonData;
      }
  }

  public function getAllMemberInfo($mysqli) {
    if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {
      if($_SESSION['urole'] >= 4) {
        $getData = "SELECT * FROM members ORDER BY id DESC";
        $qur = $mysqli->query($getData) or mysqli_error($mysqli);
        $msg = [];

        if(mysqli_num_rows($qur) != 0) {
          while($r = mysqli_fetch_assoc($qur)){
            $msg[] = array("regCode" => $r['regcode'], "fullName" => $r['fullname'], "phoneNumber" => $r['phonenumber'], "occupation" => $r['occupation'], "totalPlatRefs" => $r['referrals'], "dateReg" => $r['datereg'], "totalRefs" => $r['totalrefs'], "totalDiaRefs" => $r['subrefs'], "totalRubyRefs" => $r['floorrefs'], "walletBalance" => $r['walletbalance'], "activityScore" => $r['activityscore'], "platBalance" => $r['firstbalance'], "diaBalance" => $r['secondbalance'], "rubyBalance" => $r['thirdbalance'], "memEmail" => $r['mememail'], "memAddress" => $r['address'], "memrole" => $r['role'], "amtReceived" => $r['received'], "pendingpayment" => $r['pendingpayment'], "acctnum" => $r['acctnum'], "bankname" => $r['bankname'], "activated" => $r["activated"], "affiliatecomm" => $r['affiliatecomm'], "acrecieved" => $r['acrecieved'], "pendingcomm" => $r['pendingcomm']);
          }
        }

        $jsonArray = array("status" => true, "data" => $msg);
        echo json_encode($jsonArray);
        @mysqli_close($mysqli);
      } else {
        $jsonArray = array("status" => false, "message" => "You're are not authorised to access this info");
        echo json_encode($jsonArray);
      }
    } else {
      $jsonArray = array("status" => false, "message" => "You're not signed in");
      echo json_encode($jsonArray);
    }
  }

  public function getMemberinfo($mysqli, $regcode) {
    if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'], $_SESSION['regcode'])) {
        $getData = "SELECT * FROM members WHERE regcode='$regcode'";
        $qur = $mysqli->query($getData) or mysqli_error($mysqli);

        $r = mysqli_fetch_assoc($qur);
        $msg = array("regCode" => $r['regcode'], "fullName" => $r['fullname'], "phoneNumber" => $r['phonenumber'], "occupation" => $r['occupation'], "totalPlatRefs" => $r['referrals'], "dateReg" => $r['datereg'], "totalRefs" => $r['totalrefs'], "totalDiaRefs" => $r['subrefs'], "totalRubyRefs" => $r['floorrefs'], "walletBalance" => $r['walletbalance'], "activityScore" => $r['activityscore'], "platBalance" => $r['firstbalance'], "diaBalance" => $r['secondbalance'], "rubyBalance" => $r['thirdbalance'], "memEmail" => $r['mememail'], "memAddress" => $r['address'], "memrole" => $r['role'], "amtReceived" => $r['received'], "acctnum" => $r['acctnum'], "bankname" => $r['bankname'], "pendingpayment" => $r['pendingpayment'], "affiliatecomm" => $r['affiliatecomm'], "acrecieved" => $r['acrecieved']);

        $jsonArray = array("status" => true, "data" => $msg);

        echo json_encode($jsonArray);

        @mysqli_close($mysqli);

    } else {
      $jsonArray = array("status" => false, "message" => "User is not signed in");

      echo json_encode($jsonArray);
    }
  }

  public function logout() {
    if (!isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['urole'], $_SESSION['login_string'])) {
      // User not signed in.
      $jsonArray = array("message" => "User not signed in",  "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;

    } else {
      $_SESSION["user_id"] = "";
      $_SESSION['email'] = "";
      $_SESSION['urole'] = "";
      $_SESSION['login_string'] = "";
      $_SESSION['regcode'] = "";

      echo $jsonData;
  	  session_destroy();

      // Logout successful.
      $jsonArray = array("message" => "User successfully signed out",  "status" => true);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
    }
  }

  public function clean($input) {
    // clean data
  	$input = trim( $input );
  	$input = strip_tags( $input );
    $input = htmlspecialchars($input);
  	return $input;
  }

  public function UpdateAndNotify($mysqli, $message, $ownercode, $notiftype, $reftype) {
    // notification query
    $insert_notif = $mysqli->prepare("INSERT INTO notifications (message, ownercode, notiftype) VALUES (?, ?, ?)") ;
    $insert_notif->bind_param('sss', $message, $ownercode, $notiftype);

    // Execute prepared query for notification and increase number of referrals and balance according to level.
    if($insert_notif->execute()) {
      if($reftype == "firstcode") {
        $sql = "UPDATE members SET referrals=referrals+1, totalrefs=totalrefs+1, firstbalance=firstbalance+2000, walletbalance=walletbalance+2000 WHERE regcode='$ownercode'";
        $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli));
      } elseif ($reftype == "secondcode") {
        $sql = "UPDATE members SET subrefs=subrefs+1, totalrefs=totalrefs+1, secondbalance=secondbalance+700, walletbalance=walletbalance+700 WHERE regcode='$ownercode'";
        $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli));
      } else {
        $sql = "UPDATE members SET floorrefs=floorrefs+1, totalrefs=totalrefs+1, thirdbalance=thirdbalance+300, walletbalance=walletbalance+300 WHERE regcode='$ownercode'";
        $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli));
      }
    }
  }

  public function paymentNotification($mysqli, $message, $ownercode, $notiftype) {
    // notification query
    $insert_notif = $mysqli->prepare("INSERT INTO notifications (message, ownercode, notiftype) VALUES (?, ?, ?)") ;
    $insert_notif->bind_param('sss', $message, $ownercode, $notiftype);
    $insert_notif->execute();
  }

  public function increaseActivityScore($mysqli, $memcode, $scorepoints) {
    $sql = "UPDATE members SET activityscore = activityscore + $scorepoints WHERE regcode='$memcode'";
    $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli));
    if($result) {
      // Activiity score increased successfully.
      $jsonArray = array("message" => "User activity score increased successfully!",  "status" => true);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
    } else {
      // Activiity score increased successfully.
      $jsonArray = array("message" => "Unable to process request",  "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
    }
  }

  public function getUserLocation() {
    $ip_address ="";
    //whether ip is from share internet
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
      {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
      }
    //whether ip is from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
      {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }
    //whether ip is from remote address
    else
      {
        $ip_address = $_SERVER['REMOTE_ADDR'];
      }
    return $ip_address;
  }

  public function confirmRead($mysqli, $ownercode, $notiftype) {
    $sql = "UPDATE notifications SET readmsg=1 WHERE ownercode='$ownercode' AND notiftype='$notiftype' ";
    $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli));
    if($result) {
      $jsonArray = array("status" => true, "message" => "message read status confirmed");
      echo json_encode($jsonArray);
    }
  }

  public function confirmTransRead($mysqli, $ownercode) {
    $sql = "UPDATE transactions SET isread=1 WHERE membercode='$ownercode' ";
    $result = mysqli_query($mysqli, $sql) or die("Unable to update transaction table: " . mysqli_error($mysqli));
    if($result) {
      $jsonArray = array("status" => true, "message" => "message read status confirmed");
      echo json_encode($jsonArray);
    }
  }

  public function activateUser($mysqli, $ownercode) {
    $am = "1";
    $sql = "UPDATE members SET activated='$am', togglecount = togglecount+1 WHERE regcode='$ownercode' ";
    $result = mysqli_query($mysqli, $sql) or die("Unable to update member record: " . mysqli_error($mysqli));
    if($result) {
      $jsonArray = array("status" => true, "message" => "Member activated successfully");
      echo json_encode($jsonArray);
    }
  }

  public function deactivateUser($mysqli, $ownercode) {
    $am = "0";
    $sql = "UPDATE members SET activated='$am', togglecount = togglecount+1 WHERE regcode='$ownercode' ";
    $result = mysqli_query($mysqli, $sql) or die("Unable to update member record: " . mysqli_error($mysqli));
    if($result) {
      $jsonArray = array("status" => true, "message" => "Member deactivated successfully");
      echo json_encode($jsonArray);
    }
  }
}

?>
