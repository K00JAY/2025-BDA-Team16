<?php
session_start();

require_once __DIR__ . "/includes/config.php";

$username = $_POST["username"];
$password = $_POST["password"];

// 관리자 정보 조회
$stmt = $mysqli->prepare("
    SELECT admin_id, username, password_hash
    FROM admins
    WHERE username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// 비밀번호 검증
if ($admin && password_verify($password, $admin["password_hash"])) {

    // 세션 저장
    $_SESSION["admin_id"] = $admin["admin_id"];
    $_SESSION["admin_username"] = $admin["username"];

    header("Location: weather_stats.php");
    exit;    
}

// 로그인 실패
echo "<script>alert('로그인에 실패했습니다. 사용자명 또는 비밀번호를 확인하세요.'); history.back();</script>";

