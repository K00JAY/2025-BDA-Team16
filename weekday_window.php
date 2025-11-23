<?php
require_once __DIR__ . '/includes/config.php';
$current_page = 'weekday_window'; 

/**
 * GET 파라미터 값 읽기
 */
// 요일 선택 (1=일 ~ 7=토), 기본값 전체
$selectedDow = isset($_GET['dow']) ? (int)$_GET['dow'] : 0;

// 범죄 카테고리 필터 (배열)
$selectedCategories = isset($_GET['category']) && is_array($_GET['category'])
    ? array_map('intval', $_GET['category'])
    : [];

// 연도 범위 (기본: 전체)
$startYear = isset($_GET['start_year']) ? (int)$_GET['start_year'] : 2004;
$endYear = isset($_GET['end_year'])   ? (int)$_GET['end_year']   : 2014;

if ($startYear < 2004) $startYear = 2004;
if ($endYear > 2014) $endYearv= 2014;
if ($endYear < $startYear) $endYear = $startYear;

// 날짜로 변환
$startDate = sprintf('%04d-01-01', $startYear);
$endDate = sprintf('%04d-12-31', $endYear);


/**
 * 필터용 범죄 카테고리 목록
 */
$categories = [];
$catSql = "SELECT category_id, category_name FROM crime_category ORDER BY category_name";
if ($catRes = $mysqli->query($catSql)) {
    while ($row = $catRes->fetch_assoc()) {
        $categories[] = $row;
    }
    $catRes->free();
}


/**
 * 연도별 요일 통계 (window - 각 년도 기준 3년 이동평균)
 */
$dowKorean = [
    1 => '일요일',
    2 => '월요일',
    3 => '화요일',
    4 => '수요일',
    5 => '목요일',
    6 => '금요일',
    7 => '토요일',
];

$sql = "
WITH yearly_weekday AS (
    SELECT
        YEAR(report_date) AS yr,
        dow,
        day_name,
        COUNT(*) AS crimes
    FROM crime_record
    WHERE report_date BETWEEN ? AND ?
";
$params = [$startDate, $endDate];
$types  = 'ss';

// 범죄 카테고리 필터링
if (!empty($selectedCategories)) {
    $placeholders = implode(',', array_fill(0, count($selectedCategories), '?'));
    $sql .= " AND category_id IN ($placeholders) ";
    $types .= str_repeat('i', count($selectedCategories));  // 바인딩용 type 추가
    foreach ($selectedCategories as $category) {
        $params[] = $category;
    }
}

$sql .= "
    GROUP BY YEAR(report_date), dow, day_name
)
";

// 이동평균 계산
// 특정 요일을 선택한 경우
if ($selectedDow > 0) {
    $sql .= "
SELECT
    yr,
    dow,
    day_name,
    crimes,
    AVG(crimes) OVER (
        PARTITION BY dow
        ORDER BY yr
        ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING
    ) AS moving_avg_3yr
FROM yearly_weekday
WHERE dow = ?
ORDER BY yr
";
    $types   .= 'i';
    $params[] = $selectedDow;

} else {
    // 요일 구분 없이 연도별 합계
    $sql .= "
SELECT
    yr,
    crimes_total AS crimes,
    AVG(crimes_total) OVER (
        ORDER BY yr
        ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING
    ) AS moving_avg_3yr
FROM (
    SELECT
        yr,
        SUM(crimes) AS crimes_total
    FROM yearly_weekday
    GROUP BY yr
) AS yearly_total
ORDER BY yr
";
}

// prepared statement 실행
if ($stmt = mysqli_prepare($mysqli, $sql)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);  // 바인딩
    mysqli_stmt_execute($stmt); // 실행

    $result = mysqli_stmt_get_result($stmt);

    $dataRows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dataRows[] = $row;
    }

    // 정리
    mysqli_free_result($result);
    mysqli_stmt_close($stmt);
} else {
    echo "ERROR: Could not prepate query: $sql. " . mysqli_error($mysqli);
}


/**
 * 막대그래프용 요일별 범죄수 비교
 */
$sql2 = "
    SELECT
        dow,
        day_name,
        COUNT(*) AS total_crimes
    FROM crime_record
    WHERE report_date BETWEEN ? AND ?
";

// 동일한 필터 적용
$params2 = [$startDate, $endDate];
$type2   = "ss";

if (!empty($selectedCategories)) {
    $ph = implode(',', array_fill(0, count($selectedCategories), '?'));
    $sql2 .= " AND category_id IN ($ph) ";
    $type2 .= str_repeat('i', count($selectedCategories));
    foreach ($selectedCategories as $cid) {
        $params2[] = $cid;
    }
}

$sql2 .= " GROUP BY dow, day_name ORDER BY dow ";

// 실행
if($stmt2 = mysqli_prepare($mysqli, $sql2)){
    mysqli_stmt_bind_param($stmt2, $type2, ...$params2);  // 바인딩
    mysqli_stmt_execute($stmt2); // 실행

    $result2 = mysqli_stmt_get_result($stmt2);

    $weekdayCount = [];
    while ($row = mysqli_fetch_assoc($result2)) {
        $weekdayCount[] = $row;
    }

    // 정리
    mysqli_free_result($result2);
    mysqli_stmt_close($stmt2);
} else{
    echo "ERROR: Could not prepate query: $sql. " . mysqli_error($mysqli);
}

/**
 * view로 넘길 데이터 가공
 */
$chartLabels = [];    // 연도 배열
$chartCrimes = [];    // 해당 요일 연도별 건수
$chartMoving = [];    // 3년 이동평균

foreach ($dataRows as $row) {
    $chartLabels[] = (int)$row['yr'];
    $chartCrimes[] = (int)$row['crimes'];
    $chartMoving[] = isset($row['moving_avg_3yr']) ? (float)$row['moving_avg_3yr'] : null;
}

// 템플릿에서 사용할 공통 변수들
$viewData = [
    'dowKoreanMap' => $dowKorean,
    'selectedDow' => $selectedDow,
    'selectedCategories' => $selectedCategories,
    'startYear' => $startYear,
    'endYear' => $endYear,
    'categories' => $categories,
    'rows' => $dataRows,
    'chartLabels' => $chartLabels,
    'chartCrimes' => $chartCrimes,
    'chartMoving' => $chartMoving,
    'weekdayCount' => $weekdayCount
];

// 뷰 파일 로드
include 'weekday_window_view.php';
