<?php
require_once __DIR__ . '/includes/config.php';
$current_page = 'ranking'; 
$is_analyzed = false;

// 차트 및 테이블 데이터 초기화
$chartLabels = [];
$chartData = [];
$tableRows = [];


// 변수 기본값 초기화 
$sel_year    = 'all'; // 기본값: 전체
$sel_quarter = 'all'; // 기본값: 전체
$sel_month   = 'all'; // 기본값: 전체
$sel_limit   = 'all';
$categories  = ['all'];

// 체크박스 상태 변수 초기화
$use_year    = 0;
$use_quarter = 0;
$use_month   = 0;

// 필터용 범죄 유형 목록 가져오기
$filter_categories = []; // DB에서 가져온 카테고리 이름을 담을 배열
$category_info = []; // 설명을 담을 배열 추가

// description 컬럼 조회
$cat_sql = "SELECT category_name, category_description FROM crime_category ORDER BY category_name ASC";

if ($cat_result = $mysqli->query($cat_sql)) {
    while ($row = $cat_result->fetch_assoc()) {
        // 체크박스용 (이름만)
        $filter_categories[] = $row['category_name'];
        
        // 설명 출력용 (이름 + 설명)
        $category_info[] = [
            'name' => $row['category_name'],
            'desc' => $row['category_description']
        ];
    }
    $cat_result->close();
}

// 연도 목록 가져오기 (DB에 존재하는 연도만 조회)
$filter_years = [];
// crime_record 테이블에서 연도를 가져와 정렬
$year_sql = "SELECT DISTINCT year FROM crime_record ORDER BY year ASC";
if ($year_result = $mysqli->query($year_sql)) {
    while ($row = $year_result->fetch_assoc()) {
        // null 값이나 0이 있을 경우 제외
        if (!empty($row['year'])) {
            $filter_years[] = $row['year'];
        }
    }
    $year_result->close();
}

// 순위 표시 옵션 
$limit_options = [
    ['val' => 'all', 'txt' => '전체'],
    ['val' => '5',   'txt' => 'Top 5'],
    ['val' => '10',  'txt' => 'Top 10'],
    ['val' => '20',  'txt' => 'Top 20']
];


// 분석 요청 처리 (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $is_analyzed = true; 

    // 사용자 입력 받기
    $categories = $_POST['category'] ?? ['all']; 
    $sel_year   = $_POST['year'] ?? 'all';
    $use_year   = $_POST['use_year'] ?? 0;
    $sel_quarter = $_POST['quarter'] ?? 'all';
    $use_quarter = $_POST['use_quarter'] ?? 0;
    $sel_month  = $_POST['month'] ?? 'all';
    $use_month  = $_POST['use_month'] ?? 0;
    $sel_limit   = $_POST['limit'] ?? 'all';

    // 쿼리 빌더 준비
    $sql = "SELECT C.category_name, COUNT(*) as crime_count 
            FROM crime_record R
            JOIN crime_category C ON R.category_id = C.category_id
            WHERE 1=1 ";
    
    $param_types = ""; 
    $params = [];

    // 범죄 유형 필터
    if (!in_array('all', $categories)) {
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $sql .= " AND C.category_name IN ($placeholders)";
        foreach ($categories as $cat) {
            $params[] = $cat;
            $param_types .= "s";
        }
    }

    // 연도 필터
    if ($sel_year !== 'all') {
        $sql .= " AND R.year = ?";
        $params[] = $sel_year;
        $param_types .= "s";
    }

    // 분기 필터
    if ($use_quarter && $sel_quarter !== 'all') {
        $sql .= " AND QUARTER(R.occurred_at) = ?";
        $params[] = $sel_quarter;
        $param_types .= "s";
    }

    // 월 필터
    if ($use_month && $sel_month !== 'all') {
        $sql .= " AND R.month = ?";
        $params[] = $sel_month;
        $param_types .= "s";
    }

    // 그룹화 및 정렬
    $sql .= " GROUP BY C.category_name ORDER BY crime_count DESC";

    // 개수 제한
    if ($sel_limit !== 'all') {
        $sql .= " LIMIT " . (int)$sel_limit; 
    }

    // 쿼리 실행
    if ($stmt = $mysqli->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result(); 
            while ($row = $result->fetch_assoc()) {
                $chartLabels[] = $row['category_name'];
                $chartData[]   = $row['crime_count'];
                $tableRows[]   = $row;
            }
        }
        $stmt->close();
    }
}
include __DIR__ . '/ranking_view.php';
?>

