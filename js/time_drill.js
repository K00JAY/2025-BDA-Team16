(function () {
    const canvas = document.getElementById('timeChart');
    if (!canvas) return;

    const container = canvas.parentElement;

    let labels = [];
    let crimeCounts = [];
    let avgPrecip = [];
    let avgTemp = [];

    try {
        labels = JSON.parse(canvas.dataset.labels || '[]');
    } catch (e) {
        labels = [];
    }
    try {
        crimeCounts = JSON.parse(canvas.dataset.crimeCounts || '[]');
    } catch (e) {
        crimeCounts = [];
    }
    try {
        avgPrecip = JSON.parse(canvas.dataset.avgPrecip || '[]');
    } catch (e) {
        avgPrecip = [];
    }
    try {
        avgTemp = JSON.parse(canvas.dataset.avgTemp || '[]');
    } catch (e) {
        avgTemp = [];
    }

    if (!Array.isArray(labels)) labels = [];
    if (!Array.isArray(crimeCounts)) crimeCounts = [];
    if (!Array.isArray(avgPrecip)) avgPrecip = [];
    if (!Array.isArray(avgTemp)) avgTemp = [];

    const safePrecip = avgPrecip.map(v => v === null ? 0 : v);
    const safeTemp   = avgTemp.map(v   => v === null ? 0 : v);

    if (!labels.length) {
        if (container) {
            container.innerHTML = '<div style="font-size:13px;color:#6b7280;padding:12px;">표의 집계 결과가 없습니다.</div>';
        }
        return;
    }

    const ctx = canvas.getContext('2d');

    let currentMode = 'bar'; // bar | line

    function buildConfig(mode) {
        const isLine = (mode === 'line');

        let chartType, crimeType, precipType, tempType;

        if (isLine) {
            chartType  = 'line';
            crimeType  = 'line';
            precipType = 'line';
            tempType   = 'line';
        } else {
            chartType  = 'bar';
            crimeType  = 'bar';
            precipType = 'line';
            tempType   = 'line';
        }

        return {
            type: chartType,
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '범죄 건수',
                        data: crimeCounts,
                        yAxisID: 'y',
                        type: crimeType,
                        borderWidth: 2,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                    },
                    {
                        label: '평균 강수량 (mm)',
                        data: safePrecip,
                        yAxisID: 'y1',
                        type: precipType,
                        borderWidth: 2,
                        backgroundColor: 'rgba(16, 185, 129, 0.5)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                    },
                    {
                        label: '평균 기온 (F)',
                        data: safeTemp,
                        yAxisID: 'y1',
                        type: tempType,
                        borderWidth: 2,
                        pointRadius: 3,
                        backgroundColor: 'rgba(249, 115, 22, 0.6)',
                        borderColor: 'rgba(249, 115, 22, 1)',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y;
                                return label + ': ' + value;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: '범죄 건수' }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: '강수량/기온' }
                    }
                }
            }
        };
    }

    let timeChart = new Chart(ctx, buildConfig(currentMode));

    document.querySelectorAll('.chart-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.type;
            if (!mode || mode === currentMode) return;

            currentMode = mode;

            document.querySelectorAll('.chart-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            timeChart.destroy();
            timeChart = new Chart(ctx, buildConfig(currentMode));
        });
    });
})();
