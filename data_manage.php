<?php
require_once __DIR__ . '/includes/config.php';

$current_page = 'data_manage';

// --- 0. 관리자 권한 체크 ---
if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// FIXME 임시용 -> 나중에 실제 로그인 로직과 연결
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_id = $admin_id !== null ? (int)$admin_id : null;

//1. 공통 옵션 로딩 

// 범죄 카테고리
$crime_categories = [];
$res = $mysqli->query("SELECT category_id, category_name FROM crime_category ORDER BY category_name");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $crime_categories[] = $row;
    }
    $res->free();
}

// 관할 구역
$precincts = [];
$res = $mysqli->query("SELECT precinct_id, precinct_name FROM precinct ORDER BY precinct_name");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $precincts[] = $row;
    }
    $res->free();
}

// 사건 상태
$statuses = [];
$res = $mysqli->query("SELECT status_id, status_label FROM case_status ORDER BY status_label");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $statuses[] = $row;
    }
    $res->free();
}

// 날씨 상태
$weather_conditions = [];
$res = $mysqli->query("
    SELECT condition_id, condition_name, temp_range, precipitation_level
    FROM weathercondition
    ORDER BY condition_name, temp_range, precipitation_level
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $weather_conditions[] = $row;
    }
    $res->free();
}

$success_message = '';
$error_message   = '';

// 2. POST 액션 처리 (create / update / delete) 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $data_type = $_POST['data_type'] ?? 'crime'; // crime | weather

    $mysqli->begin_transaction();

    try {
        if ($data_type === 'crime') {
            $crime_id    = $_POST['crime_id'] ?? null;
            $occurred_at = $_POST['occurred_at'] ?? '';
            $occurred_at = $occurred_at !== '' ? $occurred_at : null;
            $address     = $_POST['address'] ?? '';
            $lon         = $_POST['lon'] !== '' ? (float)$_POST['lon'] : null;
            $lat         = $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
            $descript    = $_POST['descript'] ?? '';
            $category_id = $_POST['category_id'] ?? null;
            $precinct_id = $_POST['precinct_id'] !== '' ? $_POST['precinct_id'] : null;
            $status_id   = $_POST['status_id'] !== '' ? $_POST['status_id'] : null;

            if ($action === 'create') {
                $stmt = $mysqli->prepare("
                    INSERT INTO crime_record
                    (occurred_at, address, lon, lat, descript,
                     category_id, precinct_id, status_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "ssddssii",
                    $occurred_at,
                    $address,
                    $lon,
                    $lat,
                    $descript,
                    $category_id,
                    $precinct_id,
                    $status_id
                );
                $stmt->execute();
                $new_id = $stmt->insert_id;
                $stmt->close();

                // 로그 기록
                if ($admin_id) {
                    $log = $mysqli->prepare("
                        INSERT INTO data_logs (admin_id, action_type, target_table, target_id)
                        VALUES (?, 'CREATE', 'crime_record', ?)
                    ");
                    $log->bind_param("ii", $admin_id, $new_id);
                    $log->execute();
                    $log->close();
                }

                $success_message = "범죄 데이터가 추가되었습니다. (ID: {$new_id})";

            } elseif ($action === 'update' && $crime_id) {
                $stmt = $mysqli->prepare("
                    UPDATE crime_record
                    SET occurred_at = ?, address = ?, lon = ?, lat = ?, descript = ?,
                        category_id = ?, precinct_id = ?, status_id = ?
                    WHERE crime_id = ?
                ");
                $stmt->bind_param(
                    "ssddssiii",
                    $occurred_at,
                    $address,
                    $lon,
                    $lat,
                    $descript,
                    $category_id,
                    $precinct_id,
                    $status_id,
                    $crime_id
                );
                $stmt->execute();
                $stmt->close();

                if ($admin_id) {
                    $log = $mysqli->prepare("
                        INSERT INTO data_logs (admin_id, action_type, target_table, target_id)
                        VALUES (?, 'UPDATE', 'crime_record', ?)
                    ");
                    $log->bind_param("ii", $admin_id, $crime_id);
                    $log->execute();
                    $log->close();
                }

                $success_message = "범죄 데이터가 수정되었습니다. (ID: {$crime_id})";

            } elseif ($action === 'delete' && $crime_id) {
                $stmt = $mysqli->prepare("DELETE FROM crime_record WHERE crime_id = ?");
                $stmt->bind_param("i", $crime_id);
                $stmt->execute();
                $stmt->close();

                if ($admin_id) {
                    $log = $mysqli->prepare("
                        INSERT INTO data_logs (admin_id, action_type, target_table, target_id)
                        VALUES (?, 'DELETE', 'crime_record', ?)
                    ");
                    $log->bind_param("ii", $admin_id, $crime_id);
                    $log->execute();
                    $log->close();
                }

                $success_message = "범죄 데이터가 삭제되었습니다. (ID: {$crime_id})";
            }

        } elseif ($data_type === 'weather') {
            $weather_id   = $_POST['weather_id'] ?? null;
            $record_date  = $_POST['record_date'] ?? '';
            $record_date  = $record_date !== '' ? $record_date : null;
            $temp_max     = $_POST['temp_max'] !== '' ? $_POST['temp_max'] : null;
            $temp_min     = $_POST['temp_min'] !== '' ? $_POST['temp_min'] : null;
            $temp_avg     = $_POST['temp_avg'] !== '' ? $_POST['temp_avg'] : null;
            $precip       = $_POST['precipitation'] !== '' ? $_POST['precipitation'] : null;
            $snow         = $_POST['snow'] !== '' ? $_POST['snow'] : null;
            $snow_depth   = $_POST['snow_depth'] !== '' ? $_POST['snow_depth'] : null;
            $condition_id = $_POST['weather_condition_id'] ?? null;

            if ($action === 'create') {
                $stmt = $mysqli->prepare("
                    INSERT INTO weather
                    (record_date, temp_max, temp_min, temp_avg,
                     precipitation, snow, snow_depth, weather_condition_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "sddddddi",
                    $record_date,
                    $temp_max,
                    $temp_min,
                    $temp_avg,
                    $precip,
                    $snow,
                    $snow_depth,
                    $condition_id
                );
                $stmt->execute();
                $new_id = $stmt->insert_id;
                $stmt->close();

                if ($admin_id) {
                    $log = $mysqli->prepare("
                        INSERT INTO data_logs (admin_id, action_type, target_table, target_id)
                        VALUES (?, 'CREATE', 'weather', ?)
                    ");
                    $log->bind_param("ii", $admin_id, $new_id);
                    $log->execute();
                    $log->close();
                }

                $success_message = "날씨 데이터가 추가되었습니다. (ID: {$new_id})";

            } elseif ($action === 'update' && $weather_id) {
                $stmt = $mysqli->prepare("
                    UPDATE weather
                    SET record_date = ?, temp_max = ?, temp_min = ?, temp_avg = ?,
                        precipitation = ?, snow = ?, snow_depth = ?, weather_condition_id = ?
                    WHERE weather_id = ?
                ");
                $stmt->bind_param(
                    "sddddddii",
                    $record_date,
                    $temp_max,
                    $temp_min,
                    $temp_avg,
                    $precip,
                    $snow,
                    $snow_depth,
                    $condition_id,
                    $weather_id
                );
                $stmt->execute();
                $stmt->close();

                if ($admin_id) {
                    $log = $mysqli->prepare("
                        INSERT INTO data_logs (admin_id, action_type, target_table, target_id)
                        VALUES (?, 'UPDATE', 'weather', ?)
                    ");
                    $log->bind_param("ii", $admin_id, $weather_id);
                    $log->execute();
                    $log->close();
                }

                $success_message = "날씨 데이터가 수정되었습니다. (ID: {$weather_id})";

            } elseif ($action === 'delete' && $weather_id) {
                $stmt = $mysqli->prepare("DELETE FROM weather WHERE weather_id = ?");
                $stmt->bind_param("i", $weather_id);
                $stmt->execute();
                $stmt->close();

                if ($admin_id) {
                    $log = $mysqli->prepare("
                        INSERT INTO data_logs (admin_id, action_type, target_table, target_id)
                        VALUES (?, 'DELETE', 'weather', ?)
                    ");
                    $log->bind_param("ii", $admin_id, $weather_id);
                    $log->execute();
                    $log->close();
                }

                $success_message = "날씨 데이터가 삭제되었습니다. (ID: {$weather_id})";
            }
        }

        $mysqli->commit();

    } catch (Throwable $e) {
        $mysqli->rollback();
        $error_message = "처리 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 3. GET 필터 값 읽기 & 조회용 쿼리

$data_type = $_GET['data_type'] ?? 'crime';

$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to'] ?? '';

$per_page = 25; //페이지당 조회회
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

// 범죄 필터
$filter_category = $_GET['category'] ?? ''; 
$filter_precinct = $_GET['precinct_id'] ?? '';
$filter_status   = $_GET['status_id'] ?? '';

// 날씨 필터
$filter_wcond = $_GET['filter_wcond'] ?? '';

$list_rows   = [];
$total_rows  = 0;
$total_pages = 1;

if ($data_type === 'crime') {

    $where  = " WHERE 1=1 ";
    $params = [];
    $types  = '';

    if ($date_from !== '') {
        $where   .= " AND c.occurred_at >= ? ";
        $types   .= 's';
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $where   .= " AND c.occurred_at <= ? ";
        $types   .= 's';
        $params[] = $date_to;
    }

    if ($filter_category !== '') {
        $where   .= " AND c.category_id = ? ";
        $types   .= 'i';
        $params[] = (int)$filter_category;
    }

    if ($filter_precinct !== '') {
        $where   .= " AND c.precinct_id = ? ";
        $types   .= 'i';
        $params[] = (int)$filter_precinct;
    }

    if ($filter_status !== '') {
        $where   .= " AND c.status_id = ? ";
        $types   .= 'i';
        $params[] = (int)$filter_status;
    }

    // 3-1. 전체 개수 조회
    $sql_count = "
        SELECT COUNT(*) AS total
        FROM crime_record c
        JOIN crime_category cat ON c.category_id = cat.category_id
        LEFT JOIN precinct p ON c.precinct_id = p.precinct_id
        LEFT JOIN case_status s ON c.status_id = s.status_id
        $where
    ";
    $stmt = $mysqli->prepare($sql_count);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $total_rows = (int)$row['total'];
    }
    $stmt->close();
    $total_pages = max(1, (int)ceil($total_rows / $per_page));

    // 3-2. 실제 데이터 조회 
    $sql = "
        SELECT c.crime_id, c.occurred_at, c.address, c.lon, c.lat, c.descript,
               cat.category_name, p.precinct_name, s.status_label,
               c.category_id, c.precinct_id, c.status_id
        FROM crime_record c
        JOIN crime_category cat ON c.category_id = cat.category_id
        LEFT JOIN precinct p ON c.precinct_id = p.precinct_id
        LEFT JOIN case_status s ON c.status_id = s.status_id
        $where
        ORDER BY c.occurred_at
        LIMIT ? OFFSET ?
    ";

    $params_data = $params;
    $types_data  = $types . 'ii';
    $params_data[] = $per_page;
    $params_data[] = $offset;

    $stmt = $mysqli->prepare($sql);
    if (!empty($params_data)) {
        $stmt->bind_param($types_data, ...$params_data);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $list_rows[] = $row;
    }
    $stmt->close();

} else { // weather

    $where  = " WHERE 1=1 ";
    $params = [];
    $types  = '';

    if ($date_from !== '') {
        $where   .= " AND w.record_date >= ? ";
        $types   .= 's';
        $params[] = substr($date_from, 0, 10); // datetime-local → date
    }
    if ($date_to !== '') {
        $where   .= " AND w.record_date <= ? ";
        $types   .= 's';
        $params[] = substr($date_to, 0, 10);
    }

    if ($filter_wcond !== '') {
        $where   .= " AND w.weather_condition_id = ? ";
        $types   .= 'i';
        $params[] = (int)$filter_wcond;
    }

    // 3-1. 전체 개수 조회
    $sql_count = "
        SELECT COUNT(*) AS total
        FROM weather w
        JOIN weathercondition wc ON w.weather_condition_id = wc.condition_id
        $where
    ";
    $stmt = $mysqli->prepare($sql_count);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $total_rows = (int)$row['total'];
    }
    $stmt->close();
    $total_pages = max(1, (int)ceil($total_rows / $per_page));

    // 3-2. 실제 데이터 조회
    $sql = "
        SELECT w.weather_id, w.record_date, w.temp_max, w.temp_min, w.temp_avg,
               w.precipitation, w.snow, w.snow_depth,
               wc.condition_name, w.weather_condition_id
        FROM weather w
        JOIN weathercondition wc ON w.weather_condition_id = wc.condition_id
        $where
        ORDER BY w.record_date
        LIMIT ? OFFSET ?
    ";

    $params_data = $params;
    $types_data  = $types . 'ii';
    $params_data[] = $per_page;
    $params_data[] = $offset;

    $stmt = $mysqli->prepare($sql);
    if (!empty($params_data)) {
        $stmt->bind_param($types_data, ...$params_data);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $list_rows[] = $row;
    }
    $stmt->close();
}

// 4. 뷰 로드
include __DIR__ . '/data_manage_view.php';
