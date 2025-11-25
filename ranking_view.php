<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="css/global.css" />
<link rel="stylesheet" href="css/ranking.css">
<div class="layout">

    <!-- 왼쪽 네비게이션 바 -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- 오른쪽 메인 화면 -->
    <main class="content">
        <h1>범죄 유형 순위 분석</h1>
        
        <div class="white-box">
            <h2>순위 분석 필터 조건 설정</h2>

            <hr class="divider">

            <form method="POST" action="ranking.php">
                
                <div class="filter-row">
                    <div class="filter-title">범죄 유형</div>
                    <div class="filter-options"> 
                        <div class="checkbox-container">
                            <label class="checkbox-item">
                                <input type="checkbox" name="category[]" value="all" <?= (in_array('all', $categories)) ? 'checked' : '' ?>> 전체
                            </label>

                            <?php foreach ($filter_categories as $catName): ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="category[]" value="<?= htmlspecialchars($catName) ?>" <?= (in_array($catName, $categories)) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($catName) ?>
                                </label>
                            <?php endforeach; ?>
                            </div>
                    </div>
                </div>

                <hr class="divider">

                <div class="filter-row">
                    <div class="filter-title">기간 선택</div>
                    <div class="filter-options">
                        <div class="option-group">
                            <input type="checkbox" name="use_year" id="chk_year" value="1" checked onclick="return false;">
                            <label for="chk_year" class="label-text" style="cursor: default;">연도별 (필수):</label>
                            <select name="year" class="form-select">
                                <option value="all">전체</option>
                                <?php foreach($filter_years as $yr): ?>
                                    <option value="<?= $yr ?>" <?= ($sel_year == $yr) ? 'selected' : '' ?>>
                                        <?= $yr ?>년
                                    </option>
                                <?php endforeach; ?>
                                </select>
                            <span class="label-text">년</span>
                        </div>

                        <div class="option-group">
                            <input type="checkbox" name="use_quarter" id="chk_quarter" value="1" <?= $use_quarter ? 'checked' : '' ?>>
                            <label for="chk_quarter" class="label-text">분기별:</label>
                            <select name="quarter" id="sel_quarter" class="form-select">
                                <option value="all">전체</option>
                                <option value="1" <?= ($sel_quarter == '1') ? 'selected' : '' ?>>1</option>
                                <option value="2" <?= ($sel_quarter == '2') ? 'selected' : '' ?>>2</option>
                                <option value="3" <?= ($sel_quarter == '3') ? 'selected' : '' ?>>3</option>
                                <option value="4" <?= ($sel_quarter == '4') ? 'selected' : '' ?>>4</option>
                            </select>
                            <span class="label-text">분기</span>
                        </div>

                        <div class="option-group">
                            <input type="checkbox" name="use_month" id="chk_month" value="1" <?= $use_month ? 'checked' : '' ?>>
                            <label for="chk_month" class="label-text">월별:</label>
                            <select name="month" id="sel_month" class="form-select">
                                <option value="all">전체</option>
                                <?php for($m=1; $m<=12; $m++): ?>
                                    <option value="<?= $m ?>" <?= ($sel_month == $m) ? 'selected' : '' ?>>
                                        <?= $m ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <span class="label-text">월</span>
                        </div>
                    </div>
                </div>

                <hr class="divider">

                <div class="filter-row">
                    <div class="filter-title">보여줄 순위</div>
                    <div class="filter-options">
                        <div class="option-group">
                            <?php foreach ($limit_options as $opt): ?>
                                <label class="radio-item">
                                    <input type="radio" name="limit" 
                                           value="<?= $opt['val'] ?>" 
                                           <?= ($sel_limit == $opt['val']) ? 'checked' : '' ?>> 
                                    <?= $opt['txt'] ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="filter-actions">
                    <a href="ranking.php" class="btn btn-secondary">초기화하기</a>
                    
                    <button type="submit" class="btn btn-primary">적용하기</button>
                </div>
            </form>
        </div>

        <div class="white-box">
            <h2>
                <?php echo $is_analyzed ? '순위 분석 결과' : '*범죄 유형 설명'; ?>
            </h2>

            <hr class="divider">
            
            <?php if ($is_analyzed): ?>
                <div class="result-view">
                    <div style="display: flex; gap: 30px; align-items: flex-start;">

                        <div style="flex: 1; min-width: 0;">

                            <table class="result-table">
                                <thead>
                                    <tr>
                                        <th style="width: 15%;">순위</th>
                                        <th>범죄 유형</th>
                                        <th style="width: 25%;">발생 건수</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tableRows)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 30px; color: #888;">
                                                해당 조건에 맞는 데이터가 없습니다.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tableRows as $index => $row): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td> 
                                                
                                                <td><?= htmlspecialchars($row['category_name']) ?></td>
                                                
                                                <td><?= number_format($row['crime_count']) ?>건</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="flex: 1; min-width: 0; height: 400px; background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 10px;">
                            <canvas id="rankingChart"></canvas>
                        </div>

                    </div>
                </div>

            <?php else: ?>
                <div class="description-view">
                    <ul style="line-height: 1.8; color: #888; margin-top: 15px; font-size: 13px;">
                        <?php foreach ($category_info as $info): ?>
                            <li style="margin-bottom: 8px;">
                                <strong style="color: #555;"><?= htmlspecialchars($info['name']) ?></strong> : 
                                <?= htmlspecialchars($info['desc'] ?? '설명 없음') ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed #ddd; color: #888; font-size: 0.9em;">
                        위 필터 박스에서 원하시는 조건을 선택하고 <b>'적용하기'</b> 버튼을 누르면 분석 결과가 이곳에 표시됩니다.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</div>

<script>
    window.rankingData = {
        labels: <?php echo json_encode($chartLabels); ?>,
        data: <?php echo json_encode($chartData); ?>,
        tableRows: <?php echo json_encode($tableRows); ?>
    };
</script>

<script src="js/ranking.js"></script>