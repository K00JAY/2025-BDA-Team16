document.addEventListener('DOMContentLoaded', function() {
    const chkQuarter = document.getElementById('chk_quarter');
    const selQuarter = document.getElementById('sel_quarter');
    
    const chkMonth = document.getElementById('chk_month');
    const selMonth = document.getElementById('sel_month');

    // 체크박스 상태에 따라 화면 제어 (잠금/해제 로직)
    function syncState(isQuarterActive) {
        if (isQuarterActive) {
            // 분기 활성화 -> 월 죽이기
            selQuarter.disabled = false;
            
            chkMonth.checked = false;
            chkMonth.disabled = true;
            selMonth.value = 'all';
            selMonth.disabled = true;
        } else {
            // 분기 비활성화 -> 월 살리기
            chkMonth.disabled = false;
            if (!chkMonth.checked) selMonth.disabled = false; 
        }
    }

    function syncStateMonth(isMonthActive) {
        if (isMonthActive) {
            // 월 활성화 -> 분기 죽이기
            selMonth.disabled = false;

            chkQuarter.checked = false;
            chkQuarter.disabled = true;
            selQuarter.value = 'all';
            selQuarter.disabled = true;
        } else {
            // 월 비활성화 -> 분기 살리기
            chkQuarter.disabled = false;
            if (!chkQuarter.checked) selQuarter.disabled = false;
        }
    }

    // 1. 분기별 체크박스 변경 시
    chkQuarter.addEventListener('change', function() {
        if (this.checked) {
            // 월별 비활성화 (잠금)
            chkMonth.disabled = true;
            selMonth.disabled = true;
            chkMonth.checked = false;
            selMonth.value = 'all';
        } else {
            // 월별 활성화 (잠금 해제)
            chkMonth.disabled = false;
            selMonth.disabled = false;
        }
    });

    // 2. 월별 체크박스 변경 시
    chkMonth.addEventListener('change', function() {
        if (this.checked) {
            // 분기별 비활성화 (잠금)
            chkQuarter.disabled = true;
            selQuarter.disabled = true;
            chkQuarter.checked = false;
            selQuarter.value = 'all';
        } else {
            // 분기별 활성화 (잠금 해제)
            chkQuarter.disabled = false;
            selQuarter.disabled = false;
        }
    });

    // 2. 월별 체크박스 클릭 시
    chkMonth.addEventListener('change', function() {
        syncStateMonth(this.checked);
    });

    // 3. 분기 드롭박스 변경 시 -> 체크박스 자동 체크
    selQuarter.addEventListener('change', function() {
        // 드롭박스 값을 바꾸면 -> 체크박스를 켠다
        chkQuarter.checked = true;
        // 체크박스가 켜졌으니 잠금 로직 실행
        syncState(true);
    });

    // 4. 월 드롭박스 변경 시 -> 체크박스 자동 체크
    selMonth.addEventListener('change', function() {
        chkMonth.checked = true;
        syncStateMonth(true);
    });

    // 초기 상태 세팅 (새로고침 시 상태 유지)
    if (chkQuarter.checked) syncState(true);
    if (chkMonth.checked) syncStateMonth(true);
});

// 차트 그리기 및 테이블 채우기 로직
document.addEventListener('DOMContentLoaded', function() {
    
    // PHP에서 넘겨받은 데이터가 없으면 실행하지 않음 (첫 로딩 시 등)
    if (!window.rankingData || window.rankingData.labels.length === 0) {
        return;
    }

    const rData = window.rankingData;

    // 1. 차트 그리기 (Chart.js 필요)
    const ctx = document.getElementById('rankingChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar', // 막대 그래프
            data: {
                labels: rData.labels, // 범죄 유형 이름들
                datasets: [{
                    label: '범죄 발생 건수',
                    data: rData.data, // 건수 데이터
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // 높이 조절 허용
                plugins: {
                    legend: { display: false } // 범례 숨김 (깔끔하게)
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: '건수' }
                    },
                    x: {
                        ticks: { autoSkip: false } // 레이블 겹쳐도 다 보여주기
                    }
                }
            }
        });
    }

    // 2. 테이블 데이터 채우기
    const tableBody = document.querySelector('.result-table tbody');
    if (tableBody) {
        let html = '';
        rData.tableRows.forEach((row, index) => {
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${row.category_name}</td>
                    <td>${Number(row.crime_count).toLocaleString()}건</td>
                </tr>
            `;
        });
        tableBody.innerHTML = html;
    }
});