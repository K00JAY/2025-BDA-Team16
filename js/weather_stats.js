// 분석 카테고리 탭 전환
document.addEventListener('DOMContentLoaded', () => {
  const buttons = document.querySelectorAll('.tab-button');
  const panels = document.querySelectorAll('.tab-panel');

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      buttons.forEach((b) => b.classList.remove('active'));
      panels.forEach((p) => p.classList.remove('active'));

      btn.classList.add('active');
      document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
  });
});

// PHP에서 전달된 데이터 파싱
const tempLabels = window.tempLabelsData;
const tempRates = window.tempRatesData;
const rainLabels = window.rainLabelsData;
const rainRates = window.rainRatesData;
const globalRate = window.globalRateData;

// 공통 차트 생성 함수
function makeChart(id, labels, rates) {
  const ctx = document.getElementById(id);

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: '일일 발생률',
          data: rates,
          backgroundColor: '#2d6cdf',
        },
        {
          label: '전체 평균',
          data: new Array(labels.length).fill(globalRate),
          type: 'line',
          borderColor: '#f97316',
          borderWidth: 2,
          tension: 0.2,
        },
      ],
    },
    options: {
      animation: false,
      responsive: true,
      scales: {
        y: { beginAtZero: true },
      },
    },
  });
}

// 페이지 로드 후 차트 실행
document.addEventListener('DOMContentLoaded', () => {
  makeChart('tempChart', tempLabels, tempRates);
  makeChart('rainChart', rainLabels, rainRates);
});
