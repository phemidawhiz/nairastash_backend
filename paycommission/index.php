<?php

include_once '../dbconn.php';
include_once '../mainClass.php';

$mc = new Main();
$mc->sec_session_start(); // our custom way of starting a session

header('content-type: application/json');

if (isset($_GET['regcode'], $_GET['orderkey'], $_GET['totalamount'], $_GET['productid'])) {
  $refcode = mysqli_real_escape_string( $mysqli, $mc->clean($_GET['regcode']));
  $orderkey = mysqli_real_escape_string( $mysqli, $mc->clean($_GET['orderkey']));
  $totalamount = mysqli_real_escape_string( $mysqli, $mc->clean($_GET['totalamount']));
  $productid = mysqli_real_escape_string( $mysqli, $mc->clean($_GET['productid']));
  $commission = 0.1 * $totalamount;

  // check if order already exist in token table
  $sql1 = "SELECT order_key FROM esales WHERE order_key = '$orderkey' ";
  $result1 = mysqli_query($mysqli,$sql1) or die(mysqli_error($mysqli));
  $data = mysqli_fetch_assoc($result1);

  if($result1) {
    if(mysqli_num_rows($result1) == 0) {
      // Insert new token and corresponding email into the database
      $insert_stmt = $mysqli->prepare("INSERT INTO esales (regcode, amount, commission, order_key, productid) VALUES (?, ?, ?, ?, ?)") ;
      $insert_stmt->bind_param('siiss', $refcode, $totalamount, $commission, $orderkey, $productid);

      // Execute prepared query for inserting new tokenr.
      if($insert_stmt->execute()) {

        $sql = "UPDATE members SET affiliatecomm = affiliatecomm + $commission WHERE regcode='$refcode'";
        $result = mysqli_query($mysqli, $sql) or die("Unable to update member table: " . mysqli_error($mysqli));
        if($result) {
          $jsonArray = array("message" => "Affiliate commission processed successfully!",  "status" => true);
          $jsonData = json_encode( $jsonArray );
          echo $jsonData;
        } else {
          $jsonArray = array("message" => "Unable to process affiliate commission",  "status" => false);
          $jsonData = json_encode( $jsonArray );
          echo $jsonData;
        }

      } else {
        $jsonArray = array("message" => "Unable to process affiliate transaction",  "status" => false);
        $jsonData = json_encode( $jsonArray );
        echo $jsonData;
      }
    } else {
      $jsonArray = array("message" => "This order has been logged",  "status" => false);
      $jsonData = json_encode( $jsonArray );
      echo $jsonData;
    }
  } else {
    $jsonArray = array("message" => "Unable to complete request",  "status" => false);
    $jsonData = json_encode( $jsonArray );
    echo $jsonData;
  }


} else {
  // Required parameters not found
  $jsonArray = array("message" => "Required parameters not found",  "status" => false);
  $jsonData = json_encode( $jsonArray );

  echo $jsonData;
}

?>
