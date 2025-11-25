<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>관리자 화면 - 작업 로그</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <h1>관리자 화면</h1>

        <!-- 상단 필터 패널 -->
        <section class="filter-panel">
            <h2>작업 로그 필터 조건 설정</h2>
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
                        <label>관리자</label>
                        <select name="admin_id">
                            <option value="">전체</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?= $admin['admin_id'] ?>"
                                    <?= $filter_admin_id == $admin['admin_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($admin['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Action</label>
                        <select name="action_type">
                            <option value="" <?= $filter_action === '' ? 'selected' : '' ?>>전체</option>
                            <option value="CREATE" <?= $filter_action === 'CREATE' ? 'selected' : '' ?>>삽입 (CREATE)</option>
                            <option value="UPDATE" <?= $filter_action === 'UPDATE' ? 'selected' : '' ?>>수정 (UPDATE)</option>
                            <option value="DELETE" <?= $filter_action === 'DELETE' ? 'selected' : '' ?>>삭제 (DELETE)</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="button" class="btn btn-secondary" id="resetFilterBtn">
                            초기화하기
                        </button>
                        <button type="submit" class="btn btn-primary">적용하기</button>
                    </div>
                </div>
            </form>
        </section>

        <!-- 하단: 로그 테이블 -->
        <section class="card">
            <h2>데이터 작업 로그</h2>
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Timestamp</th>
                    <th>관리자</th>
                    <th>Action</th>
                    <th>Target Table</th>
                    <th>Target ID</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6">로그가 없습니다.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $idx => $log): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars($log['action_timestamp']) ?></td>
                            <td><?= htmlspecialchars($log['username']) ?></td>
                            <td><?= htmlspecialchars($log['action_type']) ?></td>
                            <td><?= htmlspecialchars($log['target_table']) ?></td>
                            <td><?= htmlspecialchars($log['target_id']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<script src="js/admin.js"></script>
</body>
</html>
