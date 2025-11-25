<?php
require_once __DIR__ . '/includes/config.php';
?>

<?php
$isLoggedIn = isset($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8" />
    <title>날씨와 범죄의 상관관계 분석</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<!-- 상단 네비게이션 바 -->
<div class="navbar">
    <?php if ($isLoggedIn): ?>
        <a class="login-btn" href="logout_process.php">Log Out</a>
    <?php else: ?>
        <a class="login-btn" href="login.php">Log In</a>
    <?php endif; ?>
</div>

<!-- 메인 영역 -->
<div class="main-container">
    <img src="assets/weather-icon.png" alt="Weather Icon" class="weather-icon"/>

    <div class="main-content">
        <div class="subtitle">빅데이터 분석 시뮬레이션 프로젝트</div>
        <div class="title">날씨와 범죄의 상관관계 분석</div>
        <div class="description">
            기상 데이터와 범죄 통계를 결합하여 날씨 조건이 범죄 발생에 미치는 영향을 분석합니다.
        </div>
        <a href="weather_stats.php" class="btn-primary">분석하러 가기</a>
    </div>
</div>

<!-- 하단 Footer -->
<footer class="footer">
    2025-2 빅데이터응용 팀 쿼리라이스
</footer>


</body>
</html>

