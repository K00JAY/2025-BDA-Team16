<?php
require_once __DIR__ . '/includes/config.php';
$current_page = 'time_drill';

// 1. 공통 옵션 로딩 (범죄 / 날씨 조건 선택용)
$crime_categories = [];
$res = $mysqli->query("SELECT category_id, category_name FROM crime_category ORDER BY category_name");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $crime_categories[] = $row;
    }
    $res->free();
}

$precincts = [];
$res = $mysqli->query("SELECT precinct_id, precinct_name FROM precinct ORDER BY precinct_name");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $precincts[] = $row;
    }
    $res->free();
}

$statuses = [];
$res = $mysqli->query("SELECT status_id, status_label FROM case_status ORDER BY status_label");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $statuses[] = $row;
    }
    $res->free();
}

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

// 2. 필터 & 계층 파라미터 읽기
$allowed_levels = ['year', 'month', 'day', 'hour'];
$level = $_GET['level'] ?? 'year';
if (!in_array($level, $allowed_levels)) {
    $level = 'year';
}

$date_from_input = $_GET['date_from'] ?? '';
$date_to_input   = $_GET['date_to'] ?? '';

$date_from_db = $date_from_input ? str_replace('T', ' ', $date_from_input) : '';
$date_to_db   = $date_to_input   ? str_replace('T', ' ', $date_to_input)   : '';

// 계층 경로 (Breadcrumb용)
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : null;
$month = isset($_GET['month']) ? (int)$_GET['month'] : null;
$day   = $_GET['day'] ?? '';          // 'YYYY-MM-DD' 문자열
$hour  = isset($_GET['hour'])  ? (int)$_GET['hour']  : null;

// 범죄 필터 
$filter_category = $_GET['category'] ?? '';
$filter_precinct = $_GET['precinct_id'] ?? '';
$filter_status   = $_GET['status_id'] ?? '';

// 날씨 필터
$rain_min     = $_GET['rain_min'] ?? '';
$temp_band    = $_GET['temp_band'] ?? '';      
$filter_wcond = $_GET['wcond'] ?? '';         

// 3. 집계 쿼리
$select_bucket = '';
$group_by = '';
$order_by = '';

switch ($level) {
    case 'year':
        $select_bucket = "YEAR(c.occurred_at) AS bucket_year";
        $group_by = "bucket_year";
        $order_by = "bucket_year";
        break;

    case 'month':
        $select_bucket = "YEAR(c.occurred_at) AS bucket_year,
                          MONTH(c.occurred_at) AS bucket_month";
        $group_by = "bucket_year, bucket_month";
        $order_by = "bucket_year, bucket_month";
        break;

    case 'day':
        $select_bucket = "DATE(c.occurred_at) AS bucket_date";
        $group_by = "bucket_date";
        $order_by = "bucket_date";
        break;

    case 'hour':
        $select_bucket = "DATE(c.occurred_at) AS bucket_date,
                          HOUR(c.occurred_at) AS bucket_hour";
        $group_by = "bucket_date, bucket_hour";
        $order_by = "bucket_date, bucket_hour";
        break;
}

$sql = "
    SELECT
        $select_bucket,
        COUNT(*) AS crime_count,
        AVG(w.precipitation) AS avg_precip,
        AVG(w.temp_avg)      AS avg_temp
    FROM crime_record c
    LEFT JOIN weather w
      ON DATE(c.occurred_at) = w.record_date
";

$need_wcond_join = ($filter_wcond !== '');
if ($need_wcond_join) {
    $sql .= " LEFT JOIN weathercondition wc ON w.weather_condition_id = wc.condition_id ";
}

$sql .= " WHERE 1=1 ";

$params = [];
$types  = '';

if ($date_from_db !== '') {
    $sql .= " AND c.occurred_at >= ? ";
    $types .= 's';
    $params[] = $date_from_db;
}
if ($date_to_db !== '') {
    $sql .= " AND c.occurred_at <= ? ";
    $types .= 's';
    $params[] = $date_to_db;
}

// 계층 경로 제약 (Roll-down 된 상태 유지)
if ($year !== null) {
    $sql .= " AND YEAR(c.occurred_at) = ? ";
    $types .= 'i';
    $params[] = $year;
}
if ($month !== null) {
    $sql .= " AND MONTH(c.occurred_at) = ? ";
    $types .= 'i';
    $params[] = $month;
}
if ($day !== '') {
    $sql .= " AND DATE(c.occurred_at) = ? ";
    $types .= 's';
    $params[] = $day;
}
if ($hour !== null) {
    $sql .= " AND HOUR(c.occurred_at) = ? ";
    $types .= 'i';
    $params[] = $hour;
}

// 범죄 필터 
if ($filter_category !== '') {
    $sql   .= " AND c.category_id = ? ";
    $types .= 'i';
    $params[] = (int)$filter_category;
}
if ($filter_precinct !== '') {
    $sql   .= " AND c.precinct_id = ? ";
    $types .= 'i';
    $params[] = (int)$filter_precinct;
}
if ($filter_status !== '') {
    $sql   .= " AND c.status_id = ? ";
    $types .= 'i';
    $params[] = (int)$filter_status;
}

// 날씨 필터
if ($rain_min !== '') {
    $sql   .= " AND w.precipitation >= ? ";
    $types .= 'd';
    $params[] = (float)$rain_min;
}
if ($temp_band !== '') {
    switch ($temp_band) {
        case 'below0':
            $sql .= " AND w.temp_avg <= 0 ";
            break;
        case '0_10':
            $sql .= " AND w.temp_avg > 0 AND w.temp_avg <= 10 ";
            break;
        case '10_20':
            $sql .= " AND w.temp_avg > 10 AND w.temp_avg <= 20 ";
            break;
        case '20_plus':
            $sql .= " AND w.temp_avg > 20 ";
            break;
    }
}
if ($filter_wcond !== '') {
    $sql   .= " AND w.weather_condition_id = ? ";
    $types .= 'i';
    $params[] = (int)$filter_wcond;
}

$sql .= " GROUP BY $group_by ORDER BY $order_by";

// 쿼리 실행
$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$total_crime = 0;
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
    $total_crime += (int)$r['crime_count'];
}
$stmt->close();


// 4. Breadcrumb 텍스트 구성
$breadcrumbs = [];

$base_q = [
    'date_from' => $date_from_input,
    'date_to'   => $date_to_input,
];

// 루트(연 단위)로 가는 링크
$breadcrumbs[] = [
    'label' => '전체',
    'url'   => 'time_drill.php?' . http_build_query(array_merge($base_q, ['level' => 'year']))
];

if ($year !== null) {
    $breadcrumbs[] = [
        'label' => $year . '년',
        'url'   => 'time_drill.php?' . http_build_query(array_merge($base_q, [
            'level' => 'month',
            'year'  => $year
        ]))
    ];
}
if ($month !== null) {
    $breadcrumbs[] = [
        'label' => sprintf('%02d월', $month),
        'url'   => 'time_drill.php?' . http_build_query(array_merge($base_q, [
            'level' => 'day',
            'year'  => $year,
            'month' => $month
        ]))
    ];
}
if ($day !== '') {
    $breadcrumbs[] = [
        'label' => $day,
        'url'   => 'time_drill.php?' . http_build_query(array_merge($base_q, [
            'level' => 'hour',
            'day'   => $day,
            'year'  => $year,
            'month' => $month
        ]))
    ];
}
if ($hour !== null) {
    $breadcrumbs[] = [
        'label' => sprintf('%02d시', $hour),
        'url'   => '#'
    ];
}

// 4-1. Chart.js용 데이터 배열 준비
$chartLabels      = [];
$chartCrimeCounts = [];
$chartAvgPrecip   = [];
$chartAvgTemp     = [];

foreach ($rows as $row) {
    switch ($level) {
        case 'year':
            $label = $row['bucket_year'] . '년';
            break;
        case 'month':
            $label = sprintf('%04d-%02d', $row['bucket_year'], $row['bucket_month']);
            break;
        case 'day':
            $label = $row['bucket_date'];
            break;
        case 'hour':
            $label = $row['bucket_date'] . ' ' . sprintf('%02d시', $row['bucket_hour']);
            break;
        default:
            $label = 'N/A';
    }

    $chartLabels[]      = $label;
    $chartCrimeCounts[] = (int)$row['crime_count'];
    $chartAvgPrecip[]   = $row['avg_precip'] !== null ? (float)$row['avg_precip'] : null;
    $chartAvgTemp[]     = $row['avg_temp']   !== null ? (float)$row['avg_temp']   : null;
}

// 5. 뷰 파일 include
include __DIR__ . '/time_drill_view.php';
