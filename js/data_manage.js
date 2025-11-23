function clearSelected() {
    document.querySelectorAll('tbody tr').forEach(function (tr) {
        tr.classList.remove('selected');
    });
}

function selectCrimeRow(tr) {
    clearSelected();
    tr.classList.add('selected');

    const crimeIdInput    = document.getElementById('crime_id');
    const occurredAtInput = document.getElementById('occurred_at');
    const addressInput    = document.getElementById('address');
    const lonInput        = document.getElementById('lon');
    const latInput        = document.getElementById('lat');
    const descriptInput   = document.getElementById('descript');
    const categorySelect  = document.getElementById('category_id');
    const precinctSelect  = document.getElementById('precinct_id');
    const statusSelect    = document.getElementById('status_id');

    if (!crimeIdInput) return;

    crimeIdInput.value    = tr.dataset.crime_id || '';
    occurredAtInput.value = tr.dataset.occurred_at
        ? tr.dataset.occurred_at.replace(' ', 'T')
        : '';
    addressInput.value    = tr.dataset.address || '';
    lonInput.value        = tr.dataset.lon || '';
    latInput.value        = tr.dataset.lat || '';
    descriptInput.value   = tr.dataset.descript || '';
    categorySelect.value  = tr.dataset.category_id || '';
    precinctSelect.value  = tr.dataset.precinct_id || '';
    statusSelect.value    = tr.dataset.status_id || '';
}

function selectWeatherRow(tr) {
    clearSelected();
    tr.classList.add('selected');

    const weatherIdInput      = document.getElementById('weather_id');
    const recordDateInput     = document.getElementById('record_date');
    const tempMaxInput        = document.getElementById('temp_max');
    const tempMinInput        = document.getElementById('temp_min');
    const tempAvgInput        = document.getElementById('temp_avg');
    const precipInput         = document.getElementById('precipitation');
    const snowInput           = document.getElementById('snow');
    const snowDepthInput      = document.getElementById('snow_depth');
    const conditionSelect     = document.getElementById('weather_condition_id');

    if (!weatherIdInput) return;

    weatherIdInput.value      = tr.dataset.weather_id || '';
    recordDateInput.value     = tr.dataset.record_date || '';
    tempMaxInput.value        = tr.dataset.temp_max || '';
    tempMinInput.value        = tr.dataset.temp_min || '';
    tempAvgInput.value        = tr.dataset.temp_avg || '';
    precipInput.value         = tr.dataset.precipitation || '';
    snowInput.value           = tr.dataset.snow || '';
    snowDepthInput.value      = tr.dataset.snow_depth || '';
    conditionSelect.value     = tr.dataset.weather_condition_id || '';
}
