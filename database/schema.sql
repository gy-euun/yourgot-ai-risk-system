-- AI 기반 위험성평가 지원 시스템 데이터베이스 스키마
-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS yourgot_risk_assessment 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE yourgot_risk_assessment;

-- 프로젝트 기본정보 테이블
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT '사용자 ID',
    project_name VARCHAR(255) NOT NULL COMMENT '프로젝트명',
    work_location VARCHAR(255) NOT NULL COMMENT '작업 장소',
    creation_date DATE NOT NULL COMMENT '작성일',
    manager_name VARCHAR(100) NOT NULL COMMENT '담당자 이름',
    manager_position VARCHAR(100) NOT NULL COMMENT '담당자 직책',
    manager_contact VARCHAR(50) DEFAULT NULL COMMENT '담당자 연락처',
    company_name VARCHAR(255) DEFAULT NULL COMMENT '회사명',
    project_description TEXT DEFAULT NULL COMMENT '프로젝트 설명',
    processes JSON DEFAULT NULL COMMENT '공정정보 (JSON)',
    ai_analysis JSON DEFAULT NULL COMMENT 'AI 분석 결과 (JSON)',
    status ENUM('draft', 'step1', 'step2', 'step3', 'completed') DEFAULT 'draft' COMMENT '진행 상태',
    completed_at TIMESTAMP NULL DEFAULT NULL COMMENT '완료일시',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    session_id VARCHAR(128) DEFAULT NULL COMMENT '세션 연결용'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 공정정보 및 작업조건 테이블
CREATE TABLE process_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    process_name VARCHAR(255) NOT NULL COMMENT '공정명',
    detailed_work TEXT NOT NULL COMMENT '세부작업',
    work_location VARCHAR(255) NOT NULL COMMENT '작업위치',
    equipment_name VARCHAR(255) COMMENT '장비명',
    main_materials TEXT COMMENT '주요자재',
    weather_conditions VARCHAR(100) COMMENT '기상조건',
    work_hours VARCHAR(100) COMMENT '작업시간대',
    high_altitude_work ENUM('Y', 'N') DEFAULT 'N' COMMENT '고소작업 여부',
    confined_space ENUM('Y', 'N') DEFAULT 'N' COMMENT '밀폐공간 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 위험성평가 테이블
CREATE TABLE risk_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    process_id INT NOT NULL,
    process_name VARCHAR(255) NOT NULL COMMENT '공정',
    detailed_work TEXT NOT NULL COMMENT '세부작업',
    risk_category VARCHAR(50) NOT NULL COMMENT '분류(요인)',
    cause TEXT NOT NULL COMMENT '원인',
    hazard_factor TEXT NOT NULL COMMENT '유해위험요인',
    current_safety_measures TEXT COMMENT '현재 안전보건조치',
    risk_scale INT NOT NULL COMMENT '위험성척도 (1-5)',
    probability INT NOT NULL COMMENT '발생가능성 (1-5)',
    severity INT NOT NULL COMMENT '중대성 (1-5)',
    risk_reduction_measures TEXT COMMENT '위험성 감소대책',
    manager_name VARCHAR(100) COMMENT '담당자',
    ai_generated ENUM('Y', 'N') DEFAULT 'N' COMMENT 'AI 생성 여부',
    ai_confidence DECIMAL(5,2) DEFAULT NULL COMMENT 'AI 신뢰도',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (process_id) REFERENCES process_info(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI 분석 로그 테이블
CREATE TABLE ai_analysis_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    process_id INT NOT NULL,
    ai_request TEXT NOT NULL COMMENT 'AI 요청 내용',
    ai_response TEXT NOT NULL COMMENT 'AI 응답 내용',
    processing_time DECIMAL(10,3) COMMENT '처리 시간(초)',
    token_usage INT COMMENT '토큰 사용량',
    api_cost DECIMAL(10,4) COMMENT 'API 비용',
    status ENUM('success', 'failed', 'partial') DEFAULT 'success',
    error_message TEXT COMMENT '오류 메시지',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (process_id) REFERENCES process_info(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 위험 요인 템플릿 테이블 (AI 학습용 데이터)
CREATE TABLE risk_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    process_type VARCHAR(100) NOT NULL COMMENT '공정 유형',
    work_type VARCHAR(100) NOT NULL COMMENT '작업 유형',
    risk_category VARCHAR(50) NOT NULL COMMENT '위험 분류',
    common_hazards TEXT NOT NULL COMMENT '일반적 위험요인',
    standard_measures TEXT NOT NULL COMMENT '표준 안전조치',
    typical_causes TEXT COMMENT '일반적 원인',
    reference_regulations TEXT COMMENT '관련 법규',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 사용자 세션 테이블
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL UNIQUE,
    project_id INT DEFAULT NULL,
    current_step INT DEFAULT 1 COMMENT '현재 진행 단계',
    session_data TEXT COMMENT '세션 데이터 (JSON)',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 보고서 생성 이력 테이블
CREATE TABLE report_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    report_type ENUM('pdf', 'excel', 'word') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    generated_by VARCHAR(100) COMMENT '생성자',
    downloaded_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 시스템 설정 테이블
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 인덱스 생성
CREATE INDEX idx_projects_session ON projects(session_id);
CREATE INDEX idx_process_project ON process_info(project_id);
CREATE INDEX idx_risk_project ON risk_assessments(project_id);
CREATE INDEX idx_risk_process ON risk_assessments(process_id);
CREATE INDEX idx_risk_category ON risk_assessments(risk_category);
CREATE INDEX idx_ai_logs_project ON ai_analysis_logs(project_id);
CREATE INDEX idx_templates_type ON risk_templates(process_type, work_type);
CREATE INDEX idx_sessions_activity ON user_sessions(last_activity);
CREATE INDEX idx_reports_project ON report_history(project_id);

-- 기본 데이터 삽입
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('openai_model', 'gpt-4', 'string', '사용할 OpenAI 모델'),
('max_tokens', '2000', 'number', '최대 토큰 수'),
('temperature', '0.7', 'number', 'AI 창의성 정도'),
('risk_assessment_prompt', '당신은 산업안전 전문가입니다. 주어진 작업 정보를 바탕으로 위험성평가를 수행해주세요.', 'string', 'AI 위험성평가 프롬프트'),
('session_timeout', '3600', 'number', '세션 타임아웃 (초)');

-- 샘플 위험 요인 템플릿 데이터
INSERT INTO risk_templates (process_type, work_type, risk_category, common_hazards, standard_measures, typical_causes) VALUES
('건설', '고소작업', 'physical', '추락, 낙하물', '안전대 착용, 안전망 설치, 작업발판 설치', '안전장비 미착용, 작업발판 불량'),
('제조', '기계가공', 'mechanical', '끼임, 절단, 충돌', '방호장치 설치, 개인보호구 착용, 작업절차 준수', '방호장치 미설치, 부주의한 작업'),
('화학', '화학물질 취급', 'chemical', '화상, 중독, 폭발', '환기장치 가동, 보호복 착용, 물질안전보건자료 숙지', '환기 불량, 보호구 미착용'),
('전기', '전기작업', 'electrical', '감전, 화재', '전원차단, 절연장갑 착용, 접지 확인', '전원 미차단, 절연불량');

-- 뷰 생성 (보고서용)
CREATE VIEW v_project_summary AS
SELECT 
    p.id,
    p.project_name,
    p.work_location,
    p.manager_name,
    p.creation_date,
    COUNT(DISTINCT pi.id) as process_count,
    COUNT(DISTINCT ra.id) as risk_count,
    AVG(ra.risk_scale) as avg_risk_level,
    MAX(ra.risk_scale) as max_risk_level
FROM projects p
LEFT JOIN process_info pi ON p.id = pi.project_id
LEFT JOIN risk_assessments ra ON p.id = ra.project_id
GROUP BY p.id;

-- 트리거 생성 (위험성 척도 자동 계산)
DELIMITER //
CREATE TRIGGER tr_calculate_risk_scale
BEFORE INSERT ON risk_assessments
FOR EACH ROW
BEGIN
    IF NEW.risk_scale IS NULL OR NEW.risk_scale = 0 THEN
        SET NEW.risk_scale = NEW.probability * NEW.severity;
        IF NEW.risk_scale > 5 THEN
            SET NEW.risk_scale = 5;
        END IF;
    END IF;
END//
DELIMITER ;

-- 업데이트 트리거
DELIMITER //
CREATE TRIGGER tr_update_risk_scale
BEFORE UPDATE ON risk_assessments
FOR EACH ROW
BEGIN
    IF NEW.probability != OLD.probability OR NEW.severity != OLD.severity THEN
        SET NEW.risk_scale = NEW.probability * NEW.severity;
        IF NEW.risk_scale > 5 THEN
            SET NEW.risk_scale = 5;
        END IF;
    END IF;
END//
DELIMITER ; 