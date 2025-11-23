<?php
// 1. DB 연결 (설정하신 team16 계정 정보)
$host = "localhost";
$user = "team16";
$pass = "team16";
$db   = "team16";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 2. CSV 파일 열기
$csvFile = fopen("data.csv", "r");
if ($csvFile === false) {
    die("data.csv 파일을 찾을 수 없습니다. team16 폴더 안에 파일이 있는지 확인하세요.");
}

// 헤더가 2줄이므로 두 번 건너뜁니다!
fgetcsv($csvFile); // 1번째 줄 (지점명) 건너뛰기
fgetcsv($csvFile); // 2번째 줄 (컬럼명 Date, TAVG...) 건너뛰기

echo "<h3>데이터 가져오기 시작...</h3>";
$count = 0;
$skipped_count = 0;

// 3. 한 줄씩 읽어서 처리
while (($row = fgetcsv($csvFile)) !== false) {
    // CSV 컬럼 순서에 맞춰 변수 할당 (이미지 기준)
    // Date(0), TAVG(1), TMAX(2), TMIN(3), PRCP(4), SNOW(5), SNWD(6)
    $date = $row[0];
    if ($date < '2004-01-04' || $date > '2015-05-14') {
        $skipped_count++;
        continue; // 다음 반복으로 넘어감
    }
    $tavg_raw = $row[1];
    $tmax = floatval($row[2]);
    $tmin = floatval($row[3]);
    $prcp = floatval($row[4]);
    $snow = floatval($row[5]);
    $snwd = floatval($row[6]);

    // [로직 1] TAVG(평균기온) 계산: 값이 없으면 (최고+최저)/2
    if (is_numeric($tavg_raw)) {
        $final_tavg = floatval($tavg_raw);
    } else {
        $final_tavg = ($tmax + $tmin) / 2;
    }

    // [로직 2] 기온 구간(Temp Range) 판단 (4단계)
    if ($final_tavg < 40) {
        $temp_range = 'Cold (<40F)';
    } elseif ($final_tavg <= 60) {
        $temp_range = 'Mild (40-60F)';
    } elseif ($final_tavg <= 80) {
        $temp_range = 'Warm (60-80F)';
    } else {
        $temp_range = 'Hot (>80F)';
    }

    // [로직 3] 강수량(Precipitation) 및 날씨 상태 판단
    $condition_name = 'Clear'; // 기본값
    if ($prcp == 0) {
        $prcp_level = 'None';
        $condition_name = 'Clear';
    } elseif ($prcp < 0.1) {
        $prcp_level = 'Light (<0.1")';
        $condition_name = 'Rain';
    } else {
        $prcp_level = 'Heavy (>=0.1")';
        $condition_name = 'Heavy Rain';
    }

    // [로직 4] 눈이 온 경우 (예외 처리)
    if ($snow > 0) {
        $condition_name = ($prcp >= 0.1) ? 'Heavy Snow' : 'Snow';
        // 눈은 주로 추울 때 오므로 Cold가 아니어도 강제로 Cold 로직에 맞추거나,
        // DB에 있는 조합만 허용해야 함. 여기서는 DB에 있는 'Cold' 조합을 우선 매칭 시도.
        // (만약 Warm인데 눈이 오면 매칭이 안 될 수 있으나, 샌프란시스코 특성상 드뭄)
    }

    // 4. WeatherCondition 테이블에서 ID 찾기
    // (주의: 눈이 왔는데 날씨가 Hot이면 ID를 못 찾을 수 있음 -> Clear로 예외처리 등 필요할 수 있음)
    $sql_find = "SELECT condition_id FROM WeatherCondition 
                 WHERE condition_name = '$condition_name' 
                 AND temp_range = '$temp_range' 
                 AND precipitation_level = '$prcp_level'";
    
    $result = $conn->query($sql_find);
    
    if ($result->num_rows > 0) {
        $row_cond = $result->fetch_assoc();
        $cond_id = $row_cond['condition_id'];

        // 5. Weather 테이블에 INSERT
        // 데이터가 너무 많으므로 IGNORE를 써서 중복 날짜는 무시
        $sql_insert = "INSERT IGNORE INTO Weather 
                       (record_date, temp_max, temp_min, temp_avg, precipitation, snow, snow_depth, weather_condition_id)
                       VALUES 
                       ('$date', $tmax, $tmin, $final_tavg, $prcp, $snow, $snwd, $cond_id)";
        
        if ($conn->query($sql_insert) === TRUE) {
            $count++;
        }
    } else {
        // 매칭되는 날씨 기준이 없는 경우 (예: 더운데 눈이 옴)
        // 그냥 넘어가거나, 로그를 찍어볼 수 있음
        echo "매칭 실패: $date ($condition_name, $temp_range, $prcp_level) <br>";
    }
    
    // 1000건마다 진행상황 출력 (화면 멈춤 방지)
    if ($count > 0 && $count % 1000 == 0) {
        echo ".";
        flush(); 
    }
}

fclose($csvFile);
$conn->close();

echo "<h3>완료!</h3>";
echo "<p>저장된 데이터: <b>$count</b> 건 (2004-01-04 ~ 2015-05-14)</p>";
echo "<p>제외된 데이터: $skipped_count 건</p>";
?>