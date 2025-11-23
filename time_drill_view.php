<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>날짜 단계별 분석</title>
    <link rel="stylesheet" href="css/time_drill.css">
</head>

<body>
<div class="layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <h1>날짜 단계별 분석</h1>

        <!-- 필터 패널 -->
        <section class="filter-panel">
            <h2>데이터 필터 조건 설정</h2>
            <form method="get">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>기간 From</label>
                        <input type="datetime-local" name="date_from"
                               value="<?= htmlspecialchars($date_from_input) ?>">
                    </div>
                    <div class="filter-group">
                        <label>기간 To</label>
                        <input type="datetime-local" name="date_to"
                               value="<?= htmlspecialchars($date_to_input) ?>">
                    </div>

                    <div class="filter-group">
                        <label>단계(Level)</label>
                        <select name="level">
                            <option value="year"  <?= $level === 'year'  ? 'selected' : '' ?>>연</option>
                            <option value="month" <?= $level === 'month' ? 'selected' : '' ?>>월</option>
                            <option value="day"   <?= $level === 'day'   ? 'selected' : '' ?>>일</option>
                            <option value="hour"  <?= $level === 'hour'  ? 'selected' : '' ?>>시</option>
                        </select>
                    </div>
                </div>

                <!-- 범죄 필터 -->
                <div class="filter-row">
                    <div class="filter-group">
                        <label>범죄 카테고리</label>
                        <select name="category">
                            <option value="">전체</option>
                            <?php foreach ($crime_categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"
                                    <?= $filter_category == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>관할 구역</label>
                        <select name="precinct_id">
                            <option value="">전체</option>
                            <?php foreach ($precincts as $p): ?>
                                <option value="<?= $p['precinct_id'] ?>"
                                    <?= $filter_precinct == $p['precinct_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['precinct_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>사건 상태</label>
                        <select name="status_id">
                            <option value="">전체</option>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s['status_id'] ?>"
                                    <?= $filter_status == $s['status_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['status_label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 날씨 필터 -->
                <div class="filter-row">
                    <div class="filter-group">
                        <label>날씨 조건</label>
                        <!-- 단일 선택 드롭다운 -->
                        <select name="wcond">
                            <option value="">전체</option>
                            <?php foreach ($weather_conditions as $wc): ?>
                                <?php
                                $label = $wc['condition_name'];
                                if (!empty($wc['temp_range']) || !empty($wc['precipitation_level'])) {
                                    $label .= ' / ' . $wc['temp_range'] . ' / ' . $wc['precipitation_level'];
                                }
                                ?>
                                <option value="<?= $wc['condition_id'] ?>"
                                    <?= $filter_wcond == $wc['condition_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 버튼 영역 -->
                <div class="filter-actions">
                    <button type="button" class="btn btn-secondary"
                            onclick="location.href='time_drill.php';">초기화</button>
                    <button type="submit" class="btn btn-primary">조회</button>
                </div>
            </form>
        </section>

        <!-- 좌: 집계 / 우: 차트 -->
        <section class="main-panels">
            <!-- 좌: 집계 표 -->
            <div class="card">
                <h2>범죄 데이터 집계</h2>
                <div class="top-bar">
                    <div class="breadcrumbs">
                        <?php
                        foreach ($breadcrumbs as $idx => $b) {
                            if ($idx > 0) {
                                echo '<span class="separator">&gt;</span>';
                            }
                            if ($b['url'] === '#') {
                                echo htmlspecialchars($b['label']);
                            } else {
                                echo '<a href="' . htmlspecialchars($b['url']) . '">'
                                   . htmlspecialchars($b['label']) . '</a>';
                            }
                        }
                        ?>
                    </div>
                    <a href="time_drill.php" class="reset-link">초기화</a>
                </div>
                <p class="metric">총 범죄 건수: <?= number_format($total_crime) ?>건</p>

                <table>
                    <thead>
                    <tr>
                        <th>버킷 (연/월/일/시)</th>
                        <th>범죄 건수</th>
                        <th>평균 강수량 (mm)</th>
                        <th>평균 기온 (F)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $label = '';
                        $next_url = '';
                        $is_clickable = true;

                        $base_q_for_drill = [
                            'date_from' => $date_from_input,
                            'date_to'   => $date_to_input,
                        ];

                        switch ($level) {
                            case 'year':
                                $y = (int)$row['bucket_year'];
                                $label = $y . '년';
                                $next_q = array_merge($base_q_for_drill, [
                                    'level' => 'month',
                                    'year'  => $y
                                ]);
                                $next_url = 'time_drill.php?' . http_build_query($next_q);
                                break;

                            case 'month':
                                $y = (int)$row['bucket_year'];
                                $m = (int)$row['bucket_month'];
                                $label = sprintf('%04d-%02d', $y, $m);
                                $next_q = array_merge($base_q_for_drill, [
                                    'level' => 'day',
                                    'year'  => $y,
                                    'month' => $m
                                ]);
                                $next_url = 'time_drill.php?' . http_build_query($next_q);
                                break;

                            case 'day':
                                $d = $row['bucket_date'];
                                $label = $d;
                                $next_q = array_merge($base_q_for_drill, [
                                    'level' => 'hour',
                                    'day'   => $d,
                                    'year'  => $year,
                                    'month' => $month
                                ]);
                                $next_url = 'time_drill.php?' . http_build_query($next_q);
                                break;

                            case 'hour':
                                $d = $row['bucket_date'];
                                $h = (int)$row['bucket_hour'];
                                $label = $d . ' ' . sprintf('%02d시', $h);
                                $is_clickable = false;
                                break;
                        }
                        ?>
                        <tr <?= $is_clickable ? 'onclick="location.href=\'' . htmlspecialchars($next_url) . '\'"' : '' ?>>
                            <td><?= htmlspecialchars($label) ?></td>
                            <td><?= number_format($row['crime_count']) ?></td>
                            <td><?= $row['avg_precip'] !== null ? round($row['avg_precip'], 2) : '-' ?></td>
                            <td><?= $row['avg_temp']   !== null ? round($row['avg_temp'], 1)   : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="total-row">
                        <td>(총합)</td>
                        <td><?= number_format($total_crime) ?></td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- 우: 차트 영역 -->
            <div class="card">
                <h2>통계 시각화</h2>
                <div class="chart-tabs">
                    <button type="button" class="chart-tab active" data-type="bar">막대 그래프</button>
                    <button type="button" class="chart-tab" data-type="line">선 그래프</button>
                </div>
                <div class="chart-container">
                    <canvas id="timeChart"
                            data-labels='<?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>'
                            data-crime-counts='<?= json_encode($chartCrimeCounts) ?>'
                            data-avg-precip='<?= json_encode($chartAvgPrecip) ?>'
                            data-avg-temp='<?= json_encode($chartAvgTemp) ?>'></canvas>
                </div>
            </div>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/time_drill.js"></script>
</body>
</html>
