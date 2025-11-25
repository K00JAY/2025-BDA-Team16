<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>데이터 관리</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/data_manage.css">
</head>
<body>
<div class="layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <h1>데이터 관리</h1>

        <?php if ($success_message): ?>
            <div class="flash flash-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <!-- 상단 필터 -->
        <section class="filter-panel">
            <h2>데이터 필터 조건 설정</h2>
            <form method="get">
                <!--  데이터 유형 + 기간 -->
                <div class="filter-row">
                    <div class="filter-group">
                        <label>데이터 유형</label>
                        <select name="data_type">
                            <option value="crime" <?= $data_type === 'crime' ? 'selected' : '' ?>>범죄 데이터</option>
                            <option value="weather" <?= $data_type === 'weather' ? 'selected' : '' ?>>날씨 데이터</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>기간 From</label>
                        <input type="datetime-local" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="filter-group">
                        <label>기간 To</label>
                        <input type="datetime-local" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                </div>

                <!--범죄 / 날씨 필터 -->
                <?php if ($data_type === 'crime'): ?>
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
                                    <option value="<?= $p['precinct_id'] ?>" <?= $filter_precinct == $p['precinct_id'] ? 'selected' : '' ?>>
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
                                    <option value="<?= $s['status_id'] ?>" <?= $filter_status == $s['status_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['status_label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>날씨 조건</label>
                            <select name="filter_wcond">
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
                <?php endif; ?>

                <!-- 하단 버튼 -->
                <div class="filter-actions-bottom">
                    <button type="reset" class="btn btn-secondary">초기화하기</button>
                    <button type="submit" class="btn btn-primary">적용하기</button>
                </div>
            </form>
        </section>

        <!-- 좌: 그리드 / 우: 편집 -->
        <div class="main-panels">
            <div>
                <div class="card card--list">
                    <h2>
                        <?= $data_type === 'crime' ? '범죄 원본 데이터' : '날씨 원본 데이터' ?>
                    </h2>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                            <?php if ($data_type === 'crime'): ?>
                                <tr>
                                    <th>Occurred At</th>
                                    <th>Category</th>
                                    <th>Precinct</th>
                                    <th>Status</th>
                                    <th>Address</th>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <th>Record Date</th>
                                    <th>Condition</th>
                                    <th>Temp Avg</th>
                                    <th>Precipitation</th>
                                    <th>Snow</th>
                                </tr>
                            <?php endif; ?>
                            </thead>
                            <tbody>
                            <?php foreach ($list_rows as $row): ?>
                                <?php if ($data_type === 'crime'): ?>
                                    <tr onclick="selectCrimeRow(this)"
                                        data-crime_id="<?= $row['crime_id'] ?>"
                                        data-occurred_at="<?= htmlspecialchars($row['occurred_at']) ?>"
                                        data-address="<?= htmlspecialchars($row['address']) ?>"
                                        data-lon="<?= htmlspecialchars($row['lon']) ?>"
                                        data-lat="<?= htmlspecialchars($row['lat']) ?>"
                                        data-descript="<?= htmlspecialchars($row['descript']) ?>"
                                        data-category_id="<?= $row['category_id'] ?>"
                                        data-precinct_id="<?= $row['precinct_id'] ?>"
                                        data-status_id="<?= $row['status_id'] ?>"
                                    >
                                        <td><?= htmlspecialchars($row['occurred_at']) ?></td>
                                        <td><?= htmlspecialchars($row['category_name']) ?></td>
                                        <td><?= htmlspecialchars($row['precinct_name']) ?></td>
                                        <td><?= htmlspecialchars($row['status_label']) ?></td>
                                        <td><?= htmlspecialchars($row['address']) ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr onclick="selectWeatherRow(this)"
                                        data-weather_id="<?= $row['weather_id'] ?>"
                                        data-record_date="<?= htmlspecialchars($row['record_date']) ?>"
                                        data-temp_max="<?= htmlspecialchars($row['temp_max']) ?>"
                                        data-temp_min="<?= htmlspecialchars($row['temp_min']) ?>"
                                        data-temp_avg="<?= htmlspecialchars($row['temp_avg']) ?>"
                                        data-precipitation="<?= htmlspecialchars($row['precipitation']) ?>"
                                        data-snow="<?= htmlspecialchars($row['snow']) ?>"
                                        data-snow_depth="<?= htmlspecialchars($row['snow_depth']) ?>"
                                        data-weather_condition_id="<?= $row['weather_condition_id'] ?>"
                                    >
                                        <td><?= htmlspecialchars($row['record_date']) ?></td>
                                        <td><?= htmlspecialchars($row['condition_name']) ?></td>
                                        <td><?= htmlspecialchars($row['temp_avg']) ?></td>
                                        <td><?= htmlspecialchars($row['precipitation']) ?></td>
                                        <td><?= htmlspecialchars($row['snow']) ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <?php $base_query = $_GET; ?>
                        <div class="pagination">
                            <?php
                            $max_links = 10; 

                            // 보여줄 구간 계산 
                            $start = max(1, $page - intdiv($max_links, 2));
                            $end   = $start + $max_links - 1;

                            if ($end > $total_pages) {
                                $end   = $total_pages;
                                $start = max(1, $end - $max_links + 1);
                            }

                            // Prev
                            if ($page > 1) {
                                $base_query['page'] = $page - 1;
                                $prev_url = 'data_manage.php?' . http_build_query($base_query);
                                echo '<a href="' . htmlspecialchars($prev_url) . '">&laquo; Prev</a>';
                            } else {
                                echo '<a class="disabled">&laquo; Prev</a>';
                            }

                            // 첫 페이지 + ...
                            if ($start > 1) {
                                $base_query['page'] = 1;
                                $first_url = 'data_manage.php?' . http_build_query($base_query);
                                echo '<a href="' . htmlspecialchars($first_url) . '">1</a>';
                                if ($start > 2) {
                                    echo '<a class="disabled">...</a>';
                                }
                            }

                            // 중간 페이지들
                            for ($i = $start; $i <= $end; $i++) {
                                $base_query['page'] = $i;
                                $url = 'data_manage.php?' . http_build_query($base_query);
                                $class = $i === $page ? 'active' : '';
                                echo '<a href="' . htmlspecialchars($url) . '" class="' . $class . '">' . $i . '</a>';
                            }

                            // ... + 마지막 페이지
                            if ($end < $total_pages) {
                                if ($end < $total_pages - 1) {
                                    echo '<a class="disabled">...</a>';
                                }
                                $base_query['page'] = $total_pages;
                                $last_url = 'data_manage.php?' . http_build_query($base_query);
                                echo '<a href="' . htmlspecialchars($last_url) . '">' . $total_pages . '</a>';
                            }

                            // Next
                            if ($page < $total_pages) {
                                $base_query['page'] = $page + 1;
                                $next_url = 'data_manage.php?' . http_build_query($base_query);
                                echo '<a href="' . htmlspecialchars($next_url) . '">Next &raquo;</a>';
                            } else {
                                echo '<a class="disabled">Next &raquo;</a>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="card">
                    <h2>선택 데이터 수정 / 삭제</h2>
                    <?php if ($data_type === 'crime'): ?>
                        <form method="post" id="crimeForm">
                            <input type="hidden" name="data_type" value="crime">
                            <input type="hidden" name="crime_id" id="crime_id">

                            <div class="form-row">
                                <label for="occurred_at">Occurred At (DATETIME)</label>
                                <input type="datetime-local" name="occurred_at" id="occurred_at">
                            </div>
                            <div class="form-row">
                                <label for="category_id">Category</label>
                                <select name="category_id" id="category_id">
                                    <?php foreach ($crime_categories as $cat): ?>
                                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="precinct_id">Precinct <span class="nullable-hint">(NULL 허용)</span></label>
                                <select name="precinct_id" id="precinct_id">
                                    <option value="">(NULL)</option>
                                    <?php foreach ($precincts as $p): ?>
                                        <option value="<?= $p['precinct_id'] ?>"><?= htmlspecialchars($p['precinct_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="status_id">Status <span class="nullable-hint">(NULL 허용)</span></label>
                                <select name="status_id" id="status_id">
                                    <option value="">(NULL)</option>
                                    <?php foreach ($statuses as $s): ?>
                                        <option value="<?= $s['status_id'] ?>"><?= htmlspecialchars($s['status_label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="address">Address</label>
                                <input type="text" name="address" id="address">
                            </div>
                            <div class="form-row">
                                <label for="lon">Longitude <span class="nullable-hint">(NULL 허용)</span></label>
                                <input type="text" name="lon" id="lon">
                            </div>
                            <div class="form-row">
                                <label for="lat">Latitude <span class="nullable-hint">(NULL 허용)</span></label>
                                <input type="text" name="lat" id="lat">
                            </div>
                            <div class="form-row">
                                <label for="descript">Description</label>
                                <input type="text" name="descript" id="descript">
                            </div>

                            <div class="edit-actions">
                                <button type="submit" name="action" value="create" class="btn btn-outline">
                                    + 새 범죄 데이터 추가
                                </button>
                                <div>
                                    <button type="submit" name="action" value="update" class="btn btn-primary">Update</button>
                                    <button type="submit" name="action" value="delete" class="btn btn-danger" onclick="return confirm('정말 삭제할까요?');">Delete</button>
                                </div>
                            </div>
                        </form>

                    <?php else: ?>

                        <form method="post" id="weatherForm">
                            <input type="hidden" name="data_type" value="weather">
                            <input type="hidden" name="weather_id" id="weather_id">

                            <div class="form-row">
                                <label for="record_date">Record Date (DATE)</label>
                                <input type="date" name="record_date" id="record_date" required>
                            </div>
                            <div class="form-row">
                                <label for="weather_condition_id">Weather Condition</label>
                                <select name="weather_condition_id" id="weather_condition_id" required>
                                    <?php foreach ($weather_conditions as $wc): ?>
                                        <?php
                                            $label = $wc['condition_name'];

                                            if (!empty($wc['temp_range']) || !empty($wc['precipitation_level'])) {
                                                $label .= ' / ' . ($wc['temp_range'] ?: '-') . ' / ' . ($wc['precipitation_level'] ?: '-');
                                            }
                                        ?>
                                        <option value="<?= $wc['condition_id'] ?>">
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="temp_max">Temp Max (°F) <span class="nullable-hint">(NULL 허용)</span></label>
                                <input type="number" step="0.1" name="temp_max" id="temp_max">
                            </div>
                            <div class="form-row">
                                <label for="temp_min">Temp Min (°F) <span class="nullable-hint">(NULL 허용)</span></label>
                                <input type="number" step="0.1" name="temp_min" id="temp_min">
                            </div>
                            <div class="form-row">
                                <label for="temp_avg">Temp Avg (°F) <span class="nullable-hint">(NULL 허용)</span></label>
                                <input type="number" step="0.1" name="temp_avg" id="temp_avg">
                            </div>
                            <div class="form-row">
                                <label for="precipitation">Precipitation (mm) <span class="nullable-hint">(NULL 허용)</span></label>
                                <input type="number" step="0.1" name="precipitation" id="precipitation">
                            </div>
                            <div class="form-row">
                                <label for="snow">Snow (mm) <span class="nullable-hint">(NULL 허용)</span></label>
                                <input type="number" step="0.1" name="snow" id="snow">
                            </div>
                            <div class="form-row">
                                <label for="snow_depth">Snow Depth (mm) <span class="nullable-hint">(NULL 허용)</span></label>
                                <input type="number" step="0.1" name="snow_depth" id="snow_depth">
                            </div>

                            <div class="edit-actions">
                                <button type="submit" name="action" value="create" class="btn btn-outline">
                                    + 새 날씨 데이터 추가
                                </button>
                                <div>
                                    <button type="submit" name="action" value="update" class="btn btn-primary">Update</button>
                                    <button type="submit" name="action" value="delete" class="btn btn-danger" onclick="return confirm('정말 삭제할까요?');">Delete</button>
                                </div>
                            </div>
                        </form>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="js/data_manage.js"></script>
</body>
</html>
