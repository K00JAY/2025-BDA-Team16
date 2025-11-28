<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 공통적으로 사용되는 변수
$db_host = 'localhost';
$db_user = 'team16';
$db_pass = 'team16';
$db_name = 'team16';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_errno) {
    die('[ERROR] DB 연결 실패: ' . $mysqli->connect_error);
}

?>