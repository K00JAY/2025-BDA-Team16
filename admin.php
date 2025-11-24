<?php
require_once __DIR__ . '/includes/config.php';
$current_page = 'admin';

// 1. 관리자 권한 체크
if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_id = $admin_id !== null ? (int)$admin_id : null;

// 2. 드롭다운용 관리자 목록 로딩
$admins = [];
$res = $mysqli->query("SELECT admin_id, username FROM admins ORDER BY username");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $admins[] = $row;
    }
    $res->free();
}

// 3. 필터 값 읽기 (GET)
$date_from_input = $_GET['date_from'] ?? '';
$date_to_input   = $_GET['date_to'] ?? '';
$filter_admin_id = $_GET['admin_id'] ?? '';
$filter_action   = $_GET['action_type'] ?? '';

$date_from_db = '';
$date_to_db   = '';

if ($date_from_input !== '') {
    $date_from_db = str_replace('T', ' ', $date_from_input);
}
if ($date_to_input !== '') {
    $date_to_db = str_replace('T', ' ', $date_to_input);
}

// 4. 로그 조회 쿼리 구성
$logs   = [];
$sql    = "
    SELECT l.log_id,
           l.admin_id,
           a.username,
           l.action_type,
           l.target_table,
           l.target_id,
           l.action_timestamp
    FROM data_logs l
    JOIN admins a ON l.admin_id = a.admin_id
    WHERE 1=1
";

$params = [];
$types  = '';

if ($date_from_db !== '') {
    $sql    .= " AND l.action_timestamp >= ? ";
    $types  .= 's';
    $params[] = $date_from_db;
}
if ($date_to_db !== '') {
    $sql    .= " AND l.action_timestamp <= ? ";
    $types  .= 's';
    $params[] = $date_to_db;
}

if ($filter_admin_id !== '') {
    $sql    .= " AND l.admin_id = ? ";
    $types  .= 'i';
    $params[] = (int)$filter_admin_id;
}

if ($filter_action !== '' && $filter_action !== 'ALL') {
    $sql    .= " AND l.action_type = ? ";
    $types  .= 's';
    $params[] = $filter_action;
}

$sql .= " ORDER BY l.action_timestamp DESC, l.log_id DESC LIMIT 500";

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();

// 5. 뷰 로드
include __DIR__ . '/admin_view.php';
