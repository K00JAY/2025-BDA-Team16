<?php
session_start();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그아웃</title>
</head>
<body>
    <script>
        alert('성공적으로 로그아웃되었습니다.');
        window.location.href = 'index.php';
    </script>
</body>
</html>
<?php
exit; 
?>