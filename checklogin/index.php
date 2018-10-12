<?php
include_once '../dbconn.php';
include_once '../mainclass.php';

$main = new Main();
$main->sec_session_start(); // Our custom secure way of starting a PHP session.

//Validate json echoed out
header('content-type: application/json');

$main->login_check($mysqli);

?>
