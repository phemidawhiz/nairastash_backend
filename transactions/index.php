<?php

include_once '../dbconn.php';
include_once '../mainClass.php';
include_once '../reflogic.php';

$main = new Main();
$reflogic = new RefLogic();
$main->sec_session_start(); // Our custom secure way of starting a PHP session.

header('content-type: application/json');

$reflogic->getTransactions($mysqli);


?>
