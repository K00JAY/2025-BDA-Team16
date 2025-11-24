<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 현재 페이지 표시용
if (!isset($current_page)) {
    $current_page = '';
}

// 관리자 여부
$is_admin = !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>

<aside class="sidebar">

    <div class="sidebar__top">
        <a href="login.php" class="sidebar__user-icon">
            <!-- 사람 아이콘 (SVG) -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2d6cdf" viewBox="0 0 24 24">
                <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/>
            </svg>
        </a>
    </div>
    <nav class="sidebar__nav">
        <ul class="sidebar__section">
            <li class="sidebar__item <?= $current_page === 'weather_stats' ? 'active' : '' ?>">
                <a href="weather_stats.php">날씨별 범죄 통계 분석</a>
            </li>
            <li class="sidebar__item <?= $current_page === 'time_drill' ? 'active' : '' ?>">
                <a href="time_drill.php">날짜 단계별 분석</a>
            </li>
            <li class="sidebar__item <?= $current_page === 'ranking' ? 'active' : '' ?>">
                <a href="ranking.php">범죄 유형 순위 분석</a>
            </li>
            <li class="sidebar__item <?= $current_page === 'weekday_window' ? 'active' : '' ?>">
                <a href="weekday_window.php">이동평균 추세 분석</a>
            </li>
        </ul>

        <?php if ($is_admin): ?>
            <hr class="sidebar__divider" />
            <ul class="sidebar__section sidebar__section--admin">
                <li class="sidebar__label">Admin</li>
                <li class="sidebar__item <?= $current_page === 'data_manage' ? 'active' : '' ?>">
                    <a href="data_manage.php">데이터 관리</a>
                </li>
                <li class="sidebar__item <?= $current_page === 'admin' ? 'active' : '' ?>">
                    <a href="admin.php">관리자 화면</a>
                </li>
            </ul>
        <?php endif; ?>
    </nav>

    
</aside>

<style>


.sidebar {
    /* flex 컨테이너에서 사이드바 폭 고정 */
    flex: 0 0 220px;        /* flex-grow:0, flex-shrink:0, 기본폭 220px */
    width: 220px;
    flex-shrink: 0;         /* 혹시 모를 축소 방지 (중복이지만 안전용) */

    min-height: 100vh;
    background: #f5f7fb;
    border-right: 1px solid #dde1ea;
    padding: 20px 16px;
    box-sizing: border-box;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}


.sidebar__logo {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 24px;
}

.sidebar__section {
    list-style: none;
    padding: 0;
    margin: 0 0 12px 0;
}

.sidebar__item {
    margin-bottom: 6px;
}

.sidebar__item a {
    display: block;
    padding: 8px 10px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    color: #444;
}

.sidebar__item.active a {
    background: #2d6cdf;
    color: white;
    font-weight: 600;
}

.sidebar__divider {
    border: none;
    border-top: 1px solid #dde1ea;
    margin: 12px 0;
}

.sidebar__section--admin .sidebar__label {
    font-size: 12px;
    color: #888;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.sidebar__bottom {
    margin-top: 24px;
    font-size: 13px;
}

.sidebar__login,
.sidebar__logout {
    display: inline-block;
    margin-top: 6px;
    font-size: 13px;
    text-decoration: none;
    color: #2d6cdf;
}

.sidebar__user {
    display: block;
    color: #555;
}

.sidebar__top {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.sidebar__user-icon {
    display: inline-block;
    padding: 8px;
    border-radius: 50%;
    transition: background 0.2s;
}

.sidebar__user-icon:hover {
    background: #e6ebf5;
}

</style>
