-- 프로젝트 테이블 업데이트 스크립트
-- ANSI 오류 방지를 위한 설정
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

USE yourgot_db;

-- 기존 프로젝트 테이블에 새 컬럼들 추가
ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL COMMENT '사용자 ID' AFTER id;

ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS manager_contact VARCHAR(50) DEFAULT NULL COMMENT '담당자 연락처' AFTER manager_position;

ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS company_name VARCHAR(255) DEFAULT NULL COMMENT '회사명' AFTER manager_contact;

ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS project_description TEXT DEFAULT NULL COMMENT '프로젝트 설명' AFTER company_name;

ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS processes JSON DEFAULT NULL COMMENT '공정정보 (JSON)' AFTER project_description;

ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS ai_analysis JSON DEFAULT NULL COMMENT 'AI 분석 결과 (JSON)' AFTER processes;

ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS status ENUM('draft', 'step1', 'step2', 'step3', 'completed') DEFAULT 'draft' COMMENT '진행 상태' AFTER ai_analysis;

ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL DEFAULT NULL COMMENT '완료일시' AFTER status;

-- 기본 프로젝트 데이터 추가 (테스트용)
INSERT IGNORE INTO projects (
    id, project_name, work_location, creation_date, 
    manager_name, manager_position, company_name, 
    project_description, status
) VALUES (
    1, 
    '아파트 신축공사 안전관리', 
    '서울시 강남구 테헤란로 123', 
    CURDATE(), 
    '김안전', 
    '안전관리팀장', 
    'ABC건설주식회사',
    '25층 규모의 주거용 아파트 신축공사 프로젝트입니다.',
    'draft'
);

-- 샘플 공정 데이터 (JSON 형태)
UPDATE projects SET processes = '[
    {
        "id": 1,
        "process_name": "철근 조립 작업",
        "detail_work": "기둥 및 보 철근 배근",
        "work_position": "지하 1층 ~ 25층",
        "equipment_name": "타워크레인, 철근절곡기",
        "main_materials": "철근 D16, D19, D25",
        "weather_condition": "맑음",
        "work_time": "주간",
        "height_work": "yes",
        "confined_space": "no",
        "created_at": "2024-01-15 09:00:00"
    },
    {
        "id": 2,
        "process_name": "콘크리트 타설",
        "detail_work": "슬래브 콘크리트 타설",
        "work_position": "각 층 슬래브",
        "equipment_name": "콘크리트펌프카, 진동기",
        "main_materials": "레미콘 24-24-150",
        "weather_condition": "맑음",
        "work_time": "주간",
        "height_work": "yes",
        "confined_space": "no",
        "created_at": "2024-01-15 09:05:00"
    }
]' WHERE id = 1;

-- 샘플 AI 분석 결과 (JSON 형태)
UPDATE projects SET ai_analysis = '[
    {
        "process_name": "철근 조립 작업",
        "detail_work": "기둥 및 보 철근 배근",
        "classification": "추락",
        "cause": "고소에서의 작업 중 안전장비 미착용",
        "hazard_factor": "2m 이상 높이에서 철근 조립 시 추락 위험",
        "current_measures": "안전대 착용, 안전난간 설치",
        "risk_level": "상",
        "reduction_measures": "이동식 작업발판 설치, 안전대 체크리스트 작성",
        "responsible_person": "현장안전관리자"
    },
    {
        "process_name": "철근 조립 작업",
        "detail_work": "기둥 및 보 철근 배근",
        "classification": "끼임",
        "cause": "크레인 작업 시 부주의",
        "hazard_factor": "철근 자재 이동 중 끼임 사고",
        "current_measures": "신호수 배치, 작업반경 통제",
        "risk_level": "중",
        "reduction_measures": "크레인 작업 시 안전거리 확보, 경고음 설치",
        "responsible_person": "크레인 운전원"
    },
    {
        "process_name": "콘크리트 타설",
        "detail_work": "슬래브 콘크리트 타설",
        "classification": "추락",
        "cause": "슬래브 가장자리 작업 시 안전조치 부족",
        "hazard_factor": "콘크리트 타설 중 슬래브 가장자리 추락",
        "current_measures": "안전난간 설치, 안전대 착용",
        "risk_level": "상",
        "reduction_measures": "이동식 안전난간 보강, 안전감시자 배치",
        "responsible_person": "현장안전관리자"
    },
    {
        "process_name": "콘크리트 타설",
        "detail_work": "슬래브 콘크리트 타설",
        "classification": "화학적 유해",
        "cause": "콘크리트 직접 접촉",
        "hazard_factor": "콘크리트 알칼리 성분에 의한 피부 손상",
        "current_measures": "보호장갑 착용, 안전화 착용",
        "risk_level": "하",
        "reduction_measures": "내화학 장갑 사용, 피부 보호크림 지급",
        "responsible_person": "작업반장"
    }
]' WHERE id = 1;

-- 인덱스 추가
CREATE INDEX IF NOT EXISTS idx_projects_user ON projects(user_id);
CREATE INDEX IF NOT EXISTS idx_projects_status ON projects(status);
CREATE INDEX IF NOT EXISTS idx_projects_created ON projects(created_at);

COMMIT; 