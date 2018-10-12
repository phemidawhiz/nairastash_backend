<?php

include_once '../dbconn.php';
include_once '../mainClass.php';

$main = new Main();
$main->sec_session_start(); // Our custom secure way of starting a PHP session.

header('content-type: application/json');

if(isset($_SESSION['urole']) && $_SESSION['urole'] >= 4) {
  $sql = "SELECT id, sum(received) AS totalMoneyOut, sum(status) AS totalMembers, sum(walletbalance) AS totalMembersBalance, sum(pendingpayment) AS totalPendingPayment , sum(activityscore) AS totalActivityScore FROM members ";
  $result = mysqli_query($mysqli, $sql) or die('could not retreive login details ' . mysqli_error($mysqli));
  $row = mysqli_fetch_assoc($result);
  $totalMembers = $row['totalMembers'];//mysqli_num_rows($result);
  $totalMoneyIn = $totalMembers * 5000;
  $totalMoneyOut = $row['totalMoneyOut'];
  $totalMembersBalance = $row['totalMembersBalance'];
  $totalPendingPayment = $row['totalPendingPayment'];
  $totalActivityScore = $row['totalActivityScore'];

  $jsonArray = array("status" => true, "totalMembers" => $totalMembers, "totalMoneyIn" => $totalMoneyIn, "totalMoneyOut" => $totalMoneyOut, "totalMembersBalance" => $totalMembersBalance, "totalPendingPayment" => $totalPendingPayment);
  echo json_encode($jsonArray);
} else {
  $jsonArray = array("status" => false, "message" => "Unable to process request");
  echo json_encode($jsonArray);
}



?>
