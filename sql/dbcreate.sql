-- CREATE DATABASE team16
Use team16; 

-- (1) weathercondition - 날씨 상태 분류
CREATE TABLE weathercondition (
    condition_id INT AUTO_INCREMENT PRIMARY KEY,
    condition_name VARCHAR(50) NOT NULL,
    temp_range VARCHAR(50),
    precipitation_level VARCHAR(50),
    UNIQUE KEY unique_weather_combo (condition_name, temp_range, precipitation_level)
);

-- (2) admins - 관리자 정보 기록 
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);

-- (3) weather - 날씨 기록
CREATE TABLE weather (
    weather_id INT AUTO_INCREMENT PRIMARY KEY,
    record_date DATE UNIQUE NOT NULL,
    temp_max DECIMAL(5,2),
    temp_min DECIMAL(5,2),
    temp_avg DECIMAL(5,2),
    precipitation DECIMAL(5,2),
    snow DECIMAL(5,2),
    snow_depth DECIMAL(5,2),
    weather_condition_id INT NOT NULL,
    FOREIGN KEY (weather_condition_id) REFERENCES weathercondition(condition_id)
);

-- (4) data_logs - 데이터 관리 로그 기록
CREATE TABLE data_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(20) NOT NULL,
    target_table VARCHAR(50) NOT NULL,
    target_id BIGINT,
    action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- (5) crime_category - 범죄 카테고리 분류
CREATE TABLE crime_category (
  category_id   INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(64) NOT NULL,
  category_description VARCHAR(255)
);

-- (6) precinct - 경찰서 관할 구역 분류
CREATE TABLE precinct (
  precinct_id   INT AUTO_INCREMENT PRIMARY KEY,
  precinct_name VARCHAR(64) NOT NULL UNIQUE
);

-- (7) case_status - 사건 해결 상태 분류
CREATE TABLE case_status (
  status_id    INT AUTO_INCREMENT PRIMARY KEY,
  status_label VARCHAR(64) NOT NULL
);

--(8) crime_record - 범죄 사건 기록
CREATE TABLE crime_record (
  crime_id     BIGINT AUTO_INCREMENT PRIMARY KEY,

  -- 원 데이터
  occurred_at  DATETIME NOT NULL,
  address      VARCHAR(255),
  lon          DECIMAL(12,9),
  lat          DECIMAL(12,9),
  descript     VARCHAR(255),

  category_id  INT NOT NULL,
  precinct_id  INT NULL,
  status_id    INT NULL,

  -- 분석용 파생 컬럼
  report_date  DATE     AS (DATE(occurred_at)) STORED,
  year         INT      AS (YEAR(occurred_at)) STORED,
  month        TINYINT  AS (MONTH(occurred_at)) STORED,
  dow          TINYINT  AS (DAYOFWEEK(occurred_at)) STORED, -- 1=일 ~ 7=토
  day_name     VARCHAR(10) AS (DAYNAME(occurred_at)) STORED,

  -- FK 설정
  CONSTRAINT fk_crime_category FOREIGN KEY (category_id) REFERENCES crime_category(category_id),
  CONSTRAINT fk_crime_precinct FOREIGN KEY (precinct_id) REFERENCES precinct(precinct_id),
  CONSTRAINT fk_crime_status   FOREIGN KEY (status_id)   REFERENCES case_status(status_id),

  -- 인덱스
  KEY idx_crime_date (report_date),
  KEY idx_crime_year_month (year, month),
  KEY idx_crime_cat_date (category_id, report_date),
  KEY idx_crime_precinct (precinct_id),
  KEY idx_crime_status (status_id)
);
