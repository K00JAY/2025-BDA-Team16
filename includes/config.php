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


// 개발용 (관리자 로그인 활성화)
// if (!isset($_SESSION['user_role'])) {
//     $_SESSION['user_role'] = 'admin';
//     $_SESSION['admin_id']  =1;
//     $_SESSION['admin_name']  ='team16';

// }
// 세션 초기화용 (현재 로그인/로그아웃 기능이 없어서..! 테스트용으로!)
// session_start();
// session_unset();  // 세션 데이터 삭제
// session_destroy(); // 세션 파일 자체 삭제

?>