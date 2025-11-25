<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/includes/config.php';

$current_page = 'weather_stats';

// 월별 필터 처리
$selected_month = $_GET['month'] ?? 'ALL';

// 월 리스트 생성
$months = [];
$start = new DateTime("2004-01-01");
$end   = new DateTime("2015-12-01");

while ($start <= $end) {
    $months[] = $start->format("Y-m");
    $start->modify("+1 month");
}

if ($selected_month === "ALL") {
    $monthStart = "2004-01-01";
    $monthEnd   = "2015-12-31";
} else {
    $monthStart = $selected_month . "-01";
    $monthEnd   = (new DateTime($monthStart))->modify("last day of this month")->format("Y-m-d");
}

function q($mysqli, $sql, $params = []) {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) die("SQL Prepare Error: " . $mysqli->error);

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result();
}

// 전체 평균 발생률 계산 (선택된 기간 기준)
$sql_global = "
SELECT
    COUNT(DISTINCT w.record_date) AS weather_days,
    COALESCE(SUM(d.crimes),0) AS total_crimes,
    ROUND(COALESCE(SUM(d.crimes),0) / COUNT(DISTINCT w.record_date), 2) AS rate_per_day
FROM weather w
LEFT JOIN (
    SELECT report_date, COUNT(*) AS crimes
    FROM crime_record
    GROUP BY report_date
) d ON d.report_date = w.record_date
WHERE w.record_date BETWEEN ? AND ?
";

$global = q($mysqli, $sql_global, [$monthStart, $monthEnd])->fetch_assoc();
$global_rate  = (float)$global['rate_per_day'];

// 1) 기온 기반 단일 분석
$sql_temp = "
SELECT
    wc.temp_range,
    COUNT(DISTINCT w.record_date) AS weather_days,
    COALESCE(SUM(d.crimes),0) AS total_crimes,
    ROUND(AVG(d.crimes),2) AS avg_daily,
    MAX(d.crimes) AS max_daily,
    MIN(d.crimes) AS min_daily,
    ROUND(COALESCE(SUM(d.crimes),0) / COUNT(DISTINCT w.record_date), 2) AS rate_per_day
FROM weather w
JOIN weathercondition wc
    ON wc.condition_id = w.weather_condition_id
LEFT JOIN (
    SELECT report_date, COUNT(*) AS crimes
    FROM crime_record
    GROUP BY report_date
) d ON d.report_date = w.record_date
WHERE w.record_date BETWEEN ? AND ?
  AND wc.temp_range IS NOT NULL
GROUP BY wc.temp_range
ORDER BY wc.temp_range
";

$tempRows = [];
$tempLabels = [];
$tempRates  = [];

$r = q($mysqli, $sql_temp, [$monthStart, $monthEnd]);
while ($row = $r->fetch_assoc()) {
    $row['label'] = $row['temp_range'];  

    $row['diff_percent'] =
        $global_rate > 0 ? round(($row['rate_per_day'] - $global_rate) / $global_rate * 100, 1) : 0;

    $tempRows[] = $row;
    $tempLabels[] = $row['label'];
    $tempRates[]  = $row['rate_per_day'];
}

// 2) 강수량 기반 단일 분석
$sql_rain = "
SELECT
    CASE
        WHEN w.precipitation = 0 THEN 'Clear'
        WHEN w.precipitation <= 2.5 THEN 'Light Rain'
        ELSE 'Heavy Rain'
    END AS rain_group,
    COUNT(DISTINCT w.record_date) AS weather_days,
    COALESCE(SUM(d.crimes),0) AS total_crimes,
    ROUND(AVG(d.crimes),2) AS avg_daily,
    MAX(d.crimes) AS max_daily,
    MIN(d.crimes) AS min_daily,
    ROUND(COALESCE(SUM(d.crimes),0)/COUNT(DISTINCT w.record_date),2) AS rate_per_day
FROM weather w
LEFT JOIN (
    SELECT report_date, COUNT(*) AS crimes
    FROM crime_record
    GROUP BY report_date
) d ON d.report_date = w.record_date
WHERE w.record_date BETWEEN ? AND ?
GROUP BY rain_group
ORDER BY rate_per_day DESC
";

$rainRows = [];
$rainLabels = [];
$rainRates  = [];

$r = q($mysqli, $sql_rain, [$monthStart, $monthEnd]);
while ($row = $r->fetch_assoc()) {
    $row['diff_percent'] =
        $global_rate > 0 ? round(($row['rate_per_day'] - $global_rate) / $global_rate * 100, 1) : 0;

    $rainRows[] = $row;
    $rainLabels[] = $row['rain_group'];
    $rainRates[]  = $row['rate_per_day'];
}

// 3) 기온 X 강수량 복합 그룹핑 분석
$sql_cross = "
SELECT
    wc.temp_range,
    CASE
        WHEN w.precipitation = 0 THEN 'Clear'
        WHEN w.precipitation <= 2.5 THEN 'Light Rain'
        ELSE 'Heavy Rain'
    END AS rain_group,
    COUNT(DISTINCT w.record_date) AS weather_days,
    SUM(d.crimes) AS total_crimes,
    ROUND(AVG(d.crimes),2) AS avg_daily_crimes,
    MAX(d.crimes) AS max_daily_crimes,
    MIN(d.crimes) AS min_daily_crimes,
    ROUND(SUM(d.crimes) / COUNT(DISTINCT w.record_date), 2) AS rate_per_day
FROM weather w
JOIN weathercondition wc
    ON wc.condition_id = w.weather_condition_id
LEFT JOIN (
    SELECT report_date, COUNT(*) AS crimes
    FROM crime_record
    GROUP BY report_date
) d ON d.report_date = w.record_date
WHERE w.record_date BETWEEN ? AND ?
GROUP BY wc.temp_range, rain_group
ORDER BY wc.temp_range, rain_group
";

$crossRows = [];
$r = q($mysqli, $sql_cross, [$monthStart, $monthEnd]);
while ($row = $r->fetch_assoc()) {
    $row['diff_percent'] =
        $global_rate > 0 ? round(($row['rate_per_day'] - $global_rate) / $global_rate * 100, 1) : 0;

    $crossRows[] = $row;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>날씨 기반 범죄 발생률 분석</title>
<link rel="stylesheet" href="css/global.css" />
<link rel="stylesheet" href="css/weather_stats.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/weather_stats.js" defer></script>
</head>

<body>

<div class="layout">
<?php include __DIR__ . "/includes/sidebar.php"; ?>

<main class="content">

<h1>날씨 기반 범죄 발생률 분석</h1>

<!-- 월 필터 -->
<form method="GET">
    <div class="filter-box">
    <label>월 선택</label>
    <select name="month">
        <option value="ALL">전체 기간</option>
        <?php foreach ($months as $m): ?>
        <option value="<?= $m ?>" <?= $selected_month===$m?'selected':'' ?>>
            <?= $m ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">적용하기</button>
    </div>
</form>

<!-- 전체 요약 -->
<div class="summary-card">
    전체 평균 발생률: <span><?= number_format($global_rate,2) ?> 건/일</span>
</div>

<!-- 분석 카테고리 탭 -->
<div class="tab-header">
    <button class="tab-button active" data-tab="temp">기온 분석</button>
    <button class="tab-button" data-tab="rain">강수량 분석</button>
    <button class="tab-button" data-tab="cross">교차 분석</button>
</div>

<!-- 기온 분석 -->
<section id="tab-temp" class="tab-panel active">
<div class="white-box">
<h2>기온 기반 범죄 발생률 분석</h2>
<table>
<tr>
    <th>기온 구간</th>
    <th>일수</th>
    <th>총 범죄</th>
    <th>평균</th>
    <th>최대</th>
    <th>최소</th>
    <th>발생률</th>
    <th>평균 대비</th>
</tr>
<?php foreach ($tempRows as $r): ?>
<tr>
    <td><?= $r['label'] ?></td>
    <td><?= $r['weather_days'] ?></td>
    <td><?= number_format($r['total_crimes'] ?? 0) ?></td>
    <td><?= $r['avg_daily'] ?></td>
    <td><?= $r['max_daily'] ?></td>
    <td><?= $r['min_daily'] ?></td>
    <td><?= $r['rate_per_day'] ?></td>
    <td style="color:<?= $r['diff_percent']>0?'red':'green' ?>">
        <?= ($r['diff_percent']>0?'+':'') . $r['diff_percent'] ?>%
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="chart-wrapper"><canvas id="tempChart"></canvas></div>
</section>

<!-- 강수량 분석 -->
<section id="tab-rain" class="tab-panel">
<div class="white-box">
<h2>강수량 기반 범죄 발생률 분석</h2>
<table>
<tr>
    <th>강수 조건</th>
    <th>일수</th>
    <th>총 범죄</th>
    <th>평균</th>
    <th>최대</th>
    <th>최소</th>
    <th>발생률</th>
    <th>평균 대비</th>
</tr>
<?php foreach ($rainRows as $r): ?>
<tr>
    <td><?= $r['rain_group'] ?></td>
    <td><?= $r['weather_days'] ?></td>
    <td><?= number_format($r['total_crimes'] ?? 0) ?></td>
    <td><?= $r['avg_daily'] ?></td>
    <td><?= $r['max_daily'] ?></td>
    <td><?= $r['min_daily'] ?></td>
    <td><?= $r['rate_per_day'] ?></td>
    <td style="color:<?= $r['diff_percent']>0?'red':'green' ?>">
        <?= ($r['diff_percent']>0?'+':'') . $r['diff_percent'] ?>%
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="chart-wrapper"><canvas id="rainChart"></canvas></div>
</section>

<!-- 교차 분석 -->
<section id="tab-cross" class="tab-panel">
    <div class="white-box">
<h2>기온 X 강수량 교차 분석</h2>

<table>
<tr>
    <th>기온</th>
    <th>강수 조건</th>
    <th>일수</th>
    <th>총 범죄</th>
    <th>평균</th>
    <th>최대</th>
    <th>최소</th>
    <th>발생률</th>
    <th>평균 대비</th>
</tr>
<?php foreach ($crossRows as $r): ?>
<tr>
    <td><?= $r['temp_range'] ?></td>
    <td><?= $r['rain_group'] ?></td>
    <td><?= $r['weather_days'] ?></td>
    <td><?= number_format($r['total_crimes'] ?? 0) ?></td>
    <td><?= $r['avg_daily_crimes'] ?></td>
    <td><?= $r['max_daily_crimes'] ?></td>
    <td><?= $r['min_daily_crimes'] ?></td>
    <td><?= $r['rate_per_day'] ?></td>
    <td style="color:<?= $r['diff_percent']>0?'red':'green' ?>">
        <?= ($r['diff_percent']>0?'+':'') . $r['diff_percent'] ?>%
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>
</section>

</main>
</div>

<script>
window.tempLabelsData = <?= json_encode($tempLabels, JSON_UNESCAPED_UNICODE) ?>;
window.tempRatesData  = <?= json_encode($tempRates) ?>;

window.rainLabelsData = <?= json_encode($rainLabels, JSON_UNESCAPED_UNICODE) ?>;
window.rainRatesData  = <?= json_encode($rainRates) ?>;

window.globalRateData = <?= json_encode($global_rate) ?>;
</script>

</body>
</html>
