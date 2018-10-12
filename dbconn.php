<?php

$user = 'root'; $password = ''; $dbase = 'refmoney';
$ACAO = "Access-Control-Allow-Origin: http://localhost";
//Production
//error_reporting(E_ERROR | E_PARSE);

//Development
//error_reporting(E_ERROR | E_WARNING | E_NOTICE);
$mysqli = mysqli_connect('localhost', $user, $password, $dbase);
if (!$mysqli) {
    die(('Connect Error ('.mysqli_connect_errno().') '. mysqli_connect_error()));
}


?>
