<?php
// $viewData 배열에서 값 꺼내오기
$dowKorean = $viewData['dowKoreanMap'];   // 요일
$selectedDow = $viewData['selectedDow'];
$selectedCats = $viewData['selectedCategories']; // 선택한범죄 유형
$startYear = $viewData['startYear'];
$endYear = $viewData['endYear'];
$categories = $viewData['categories'];
$rows = $viewData['rows'];
$chartLabels = $viewData['chartLabels'];
$chartCrimes = $viewData['chartCrimes'];
$chartMoving = $viewData['chartMoving'];
$weekdayCount = $viewData['weekdayCount'];  // 요일별 총합 데이터

function isCatSelected($id, $selectedCats) {
    return in_array((int)$id, $selectedCats, true);
}

// 요일별 총합 그래프용 데이터
$weekdayLabels = [];
$weekdayValues = [];
foreach ($weekdayCount as $wc) {
    $weekdayLabels[] = $wc['day_name']; // Monday, Tuesday...
    $weekdayValues[] = (int)$wc['total_crimes'];
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>요일별 연도 추세</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="css/weekday_window.css" rel="stylesheet">
</head>
<body>
<div class="layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="content">
        <h1>요일별 연도 추세</h1>

        <!-- 필터 -->
        <section class="card">
            <h2>필터 조건 설정</h2>
            <form method="get" action="weekday_window.php">
                <div class="filters-row">

                    <div class="filter-group-row">
                        <label>분석 요일</label>
                        <select name="dow">
                            <option value="0" <?= ($selectedDow === 0 ? 'selected' : '') ?>>전체</option>
                            <?php foreach ($dowKorean as $d => $label): ?>
                                <option value="<?= $d ?>" <?= ($d === $selectedDow ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group-row">
                        <label>시작 연도</label>
                        <select name="start_year">
                            <?php for ($y = 2004; $y <= 2014; $y++): ?>
                                <option value="<?= $y ?>" <?= ($y === $startYear ? 'selected' : '') ?>>
                                    <?= $y ?>년
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group-row">
                        <label>종료 연도</label>
                        <select name="end_year">
                            <?php for ($y = 2004; $y <= 2014; $y++): ?>
                                <option value="<?= $y ?>" <?= ($y === $endYear ? 'selected' : '') ?>>
                                    <?= $y ?>년
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>범죄 유형</label>
                        <div class="category-checkboxes">
                            <?php foreach ($categories as $cat): ?>
                                <label>
                                    <input type="checkbox"
                                           name="category[]"
                                           value="<?= htmlspecialchars($cat['category_id']) ?>"
                                        <?= isCatSelected($cat['category_id'], $selectedCats) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-group">
                        <div>
                            <button type="submit">적용하기</button>
                            <a href="weekday_window.php" style="margin-left:8px; font-size:14px; color: #8A8585">초기화하기</a>
                        </div>
                    </div>

                </div>
            </form>
        </section>

        <div class="card-row" style="margin-bottom:24px;">
            <!-- 연도별 추세 + 이동평균 -->
            <section class="card">
                <h2>연도별 범죄 추세 (선택 요일)</h2>
                <p class="note">
                    선택한 요일과 연도 범위 및 범죄 유형 기준으로, 범죄 건수를 비교합니다.
                </p>
                <div class="grid-table-chart">
                    <!-- 표 -->
                    <div>
                        <table>
                            <thead>
                            <tr>
                                <th>연도</th>
                                <th>해당 요일 범죄 수</th>
                                <th>3년 이동평균</th>
                            </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['yr']) ?></td>
                                        <td><?= number_format($r['crimes']) ?></td>
                                        <td>
                                            <?= $r['moving_avg_3yr'] !== null
                                                ? number_format($r['moving_avg_3yr'], 2)
                                                : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 이동평균 라인차트 -->
                    <div>
                        <canvas id="weekdayTrendChart" height="250"></canvas>
                    </div>
                </div>
            </section>

            <!--  요일별 전체 범죄 수 -->
            <section class="card">
                <h2>요일별 범죄 수 비교</h2>
                <p class="note">
                    선택한 연도 범위 및 범죄 유형 기준으로, 요일별 총 범죄 건수를 비교합니다.
                </p>
                <canvas id="weekdayTotalChart" height="250"></canvas>
            </section>
        </div>
    </main>
</div>

<script>
// 연도별 이동평균 그래프
const labels   = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
const crimes   = <?= json_encode($chartCrimes) ?>;
const moving   = <?= json_encode($chartMoving) ?>;

const ctx = document.getElementById('weekdayTrendChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: '해당 요일 연도별 범죄 수',
                data: crimes,
                borderWidth: 2,
                tension: 0.25
            },
            {
                label: '3년 이동평균',
                data: moving,
                borderWidth: 2,
                borderDash: [6, 4],
                tension: 0.25
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true },
            x: { title: { display: true, text: '연도' } }
        },
        plugins: { legend: { position: 'bottom' } }
    }
});

// 요일별 전체 범죄 그래프
const weekdayLabels = <?= json_encode($weekdayLabels, JSON_UNESCAPED_UNICODE) ?>;
const weekdayValues = <?= json_encode($weekdayValues) ?>;

const ctx2 = document.getElementById('weekdayTotalChart').getContext('2d');

new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: weekdayLabels,
        datasets: [{
            label: '요일별 총 범죄 수',
            data: weekdayValues,
            borderWidth: 1,
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
