document.addEventListener('DOMContentLoaded', function() {
    const chkQuarter = document.getElementById('chk_quarter');
    const selQuarter = document.getElementById('sel_quarter');
    
    const chkMonth = document.getElementById('chk_month');
    const selMonth = document.getElementById('sel_month');

    function syncState(isQuarterActive) {
        if (isQuarterActive) {
            selQuarter.disabled = false;
            
            chkMonth.checked = false;
            chkMonth.disabled = true;
            selMonth.value = 'all';
            selMonth.disabled = true;
        } else {
            chkMonth.disabled = false;
            if (!chkMonth.checked) selMonth.disabled = false; 
        }
    }

    function syncStateMonth(isMonthActive) {
        if (isMonthActive) {
            selMonth.disabled = false;

            chkQuarter.checked = false;
            chkQuarter.disabled = true;
            selQuarter.value = 'all';
            selQuarter.disabled = true;
        } else {
            chkQuarter.disabled = false;
            if (!chkQuarter.checked) selQuarter.disabled = false;
        }
    }

    chkQuarter.addEventListener('change', function() {
        if (this.checked) {
            chkMonth.disabled = true;
            selMonth.disabled = true;
            chkMonth.checked = false;
            selMonth.value = 'all';
        } else {
            chkMonth.disabled = false;
            selMonth.disabled = false;
        }
    });

    chkMonth.addEventListener('change', function() {
        if (this.checked) {
            chkQuarter.disabled = true;
            selQuarter.disabled = true;
            chkQuarter.checked = false;
            selQuarter.value = 'all';
        } else {
            chkQuarter.disabled = false;
            selQuarter.disabled = false;
        }
    });

    chkMonth.addEventListener('change', function() {
        syncStateMonth(this.checked);
    });

    selQuarter.addEventListener('change', function() {
        chkQuarter.checked = true;
        syncState(true);
    });

    selMonth.addEventListener('change', function() {
        chkMonth.checked = true;
        syncStateMonth(true);
    });

    if (chkQuarter.checked) syncState(true);
    if (chkMonth.checked) syncStateMonth(true);
});

document.addEventListener('DOMContentLoaded', function() {
    
    if (!window.rankingData || window.rankingData.labels.length === 0) {
        return;
    }

    const rData = window.rankingData;

    const ctx = document.getElementById('rankingChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: rData.labels,
                datasets: [{
                    label: '범죄 발생 건수',
                    data: rData.data, 
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false } 
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: '건수' }
                    },
                    x: {
                        ticks: { autoSkip: false } 
                    }
                }
            }
        });
    }

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