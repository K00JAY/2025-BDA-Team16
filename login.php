<?php
session_start();

// 이미 로그인된 경우 메인으로 리다이렉트
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8" />
    <title>관리자 로그인</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/login.css" />
</head>
<body>

<div class="navbar">
    <a href="index.php" class="home-btn">HOME</a>
</div>

<div class="login-container">
    <div class="login-card">
        <div class="login-title">관리자 로그인</div>
        <div class="login-desc">범죄 데이터 분석 시스템 관리자 전용</div>

        <form action="login_process.php" method="POST" autocomplete="off">

            <div class="input-label">사용자명</div>
            <input type="text" name="username" class="input-field" placeholder="사용자명을 입력하세요" required />

            <div class="input-label">비밀번호</div>
            <input type="password" name="password" id="password" class="input-field" placeholder="비밀번호를 입력하세요" required />

            <button type="submit" class="btn-login">로그인</button>
        </form>
    </div>
</div>

</body>
</html>
