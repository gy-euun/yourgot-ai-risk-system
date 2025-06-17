-- 사용자 인증 관련 테이블 스키마

-- 사용자 테이블
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '사용자 이름',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT '이메일 주소',
    password VARCHAR(255) NOT NULL COMMENT '암호화된 비밀번호',
    company VARCHAR(200) COMMENT '회사명',
    position VARCHAR(100) COMMENT '직책',
    role ENUM('user', 'admin', 'manager') DEFAULT 'user' COMMENT '사용자 역할',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' COMMENT '계정 상태',
    email_verified BOOLEAN DEFAULT FALSE COMMENT '이메일 인증 여부',
    email_verification_token VARCHAR(255) COMMENT '이메일 인증 토큰',
    last_login TIMESTAMP NULL COMMENT '마지막 로그인 시간',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '계정 생성 시간',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '정보 수정 시간',
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 정보';

-- 사용자 로그인 로그 테이블
CREATE TABLE IF NOT EXISTS user_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    ip_address VARCHAR(45) COMMENT 'IP 주소',
    user_agent TEXT COMMENT '브라우저 정보',
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '로그인 시간',
    login_success BOOLEAN DEFAULT TRUE COMMENT '로그인 성공 여부',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_login_time (login_time),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 로그인 로그';

-- 비밀번호 재설정 토큰 테이블
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    token VARCHAR(255) NOT NULL UNIQUE COMMENT '재설정 토큰',
    expires_at TIMESTAMP NOT NULL COMMENT '토큰 만료 시간',
    used BOOLEAN DEFAULT FALSE COMMENT '토큰 사용 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '토큰 생성 시간',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='비밀번호 재설정 토큰';

-- 이메일 인증 토큰 테이블
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    token VARCHAR(255) NOT NULL UNIQUE COMMENT '인증 토큰',
    expires_at TIMESTAMP NOT NULL COMMENT '토큰 만료 시간',
    verified BOOLEAN DEFAULT FALSE COMMENT '인증 완료 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '토큰 생성 시간',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='이메일 인증 토큰';

-- 사용자 세션 테이블 수정 (사용자 ID 연결)
ALTER TABLE user_sessions 
ADD COLUMN user_id INT NULL COMMENT '연결된 사용자 ID' AFTER session_id,
ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- 프로젝트 테이블 수정 (사용자 ID 연결)
ALTER TABLE projects 
ADD COLUMN user_id INT NULL COMMENT '프로젝트 소유자 ID' AFTER id,
ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- 관리자 계정 생성 (초기 데이터)
INSERT IGNORE INTO users (
    name, email, password, company, position, role, status, email_verified
) VALUES (
    '시스템 관리자',
    'admin@yourgot.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'YourGot',
    'System Administrator',
    'admin',
    'active',
    TRUE
);

-- 테스트 사용자 계정 생성
INSERT IGNORE INTO users (
    name, email, password, company, position, role, status, email_verified
) VALUES (
    '김안전',
    'test@yourgot.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'ABC건설',
    '안전관리팀장',
    'user',
    'active',
    TRUE
);

-- 프로젝트 접근 권한 테이블
CREATE TABLE IF NOT EXISTS project_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL COMMENT '프로젝트 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    permission ENUM('owner', 'editor', 'viewer') DEFAULT 'viewer' COMMENT '권한 수준',
    granted_by INT COMMENT '권한 부여자 ID',
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '권한 부여 시간',
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_project_user (project_id, user_id),
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='프로젝트 접근 권한';

-- 사용자 활동 로그 테이블
CREATE TABLE IF NOT EXISTS user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT COMMENT '사용자 ID',
    action VARCHAR(100) NOT NULL COMMENT '수행 작업',
    target_type VARCHAR(50) COMMENT '대상 타입 (project, assessment 등)',
    target_id INT COMMENT '대상 ID',
    details JSON COMMENT '추가 세부 정보',
    ip_address VARCHAR(45) COMMENT 'IP 주소',
    user_agent TEXT COMMENT '브라우저 정보',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '작업 시간',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 활동 로그';

-- 시스템 설정 테이블
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE COMMENT '설정 키',
    setting_value TEXT COMMENT '설정 값',
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string' COMMENT '값 타입',
    description TEXT COMMENT '설정 설명',
    is_public BOOLEAN DEFAULT FALSE COMMENT '공개 설정 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 설정';

-- 기본 시스템 설정 값 삽입
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'YourGot AI 위험성평가', 'string', '사이트 이름', TRUE),
('site_description', 'AI 기반 위험성평가 솔루션', 'string', '사이트 설명', TRUE),
('max_file_upload_size', '10485760', 'number', '최대 파일 업로드 크기 (바이트)', FALSE),
('allowed_file_types', '["pdf", "doc", "docx", "xls", "xlsx", "jpg", "jpeg", "png", "gif"]', 'json', '허용되는 파일 확장자', FALSE),
('email_verification_required', 'false', 'boolean', '이메일 인증 필수 여부', FALSE),
('registration_enabled', 'true', 'boolean', '회원가입 허용 여부', TRUE),
('ai_analysis_enabled', 'true', 'boolean', 'AI 분석 기능 활성화', TRUE),
('maintenance_mode', 'false', 'boolean', '유지보수 모드', FALSE);

-- 트리거: 사용자 생성 시 프로젝트 권한 자동 부여
DELIMITER ;;
CREATE TRIGGER tr_auto_project_permission 
AFTER INSERT ON projects
FOR EACH ROW
BEGIN
    IF NEW.user_id IS NOT NULL THEN
        INSERT INTO project_permissions (project_id, user_id, permission, granted_by)
        VALUES (NEW.id, NEW.user_id, 'owner', NEW.user_id);
    END IF;
END;;
DELIMITER ;

-- 트리거: 사용자 활동 로그 자동 생성 (프로젝트 생성)
DELIMITER ;;
CREATE TRIGGER tr_log_project_creation
AFTER INSERT ON projects
FOR EACH ROW
BEGIN
    INSERT INTO user_activity_logs (user_id, action, target_type, target_id, details)
    VALUES (NEW.user_id, 'create_project', 'project', NEW.id, 
            JSON_OBJECT('project_name', NEW.project_name, 'work_location', NEW.work_location));
END;;
DELIMITER ;

-- 성능 최적화를 위한 추가 인덱스
ALTER TABLE users ADD INDEX idx_email_status (email, status);
ALTER TABLE projects ADD INDEX idx_user_creation (user_id, created_at);
ALTER TABLE risk_assessments ADD INDEX idx_project_created (project_id, created_at);

-- 뷰: 사용자별 프로젝트 통계
CREATE OR REPLACE VIEW user_project_stats AS
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    u.company,
    COUNT(DISTINCT p.id) as total_projects,
    COUNT(DISTINCT CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.id END) as projects_last_30_days,
    COUNT(DISTINCT ra.id) as total_assessments,
    MAX(p.created_at) as last_project_date
FROM users u
LEFT JOIN projects p ON u.id = p.user_id
LEFT JOIN risk_assessments ra ON p.id = ra.project_id
WHERE u.status = 'active'
GROUP BY u.id, u.name, u.email, u.company;

-- 뷰: 최근 활동 요약
CREATE OR REPLACE VIEW recent_activities AS
SELECT 
    'user_registration' as activity_type,
    u.name as actor_name,
    u.email as actor_email,
    NULL as target_name,
    u.created_at as activity_time
FROM users u
WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

UNION ALL

SELECT 
    'project_creation' as activity_type,
    u.name as actor_name,
    u.email as actor_email,
    p.project_name as target_name,
    p.created_at as activity_time
FROM projects p
JOIN users u ON p.user_id = u.id
WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

ORDER BY activity_time DESC;

-- 자동 정리 프로시저: 만료된 토큰 정리
DELIMITER ;;
CREATE PROCEDURE CleanupExpiredTokens()
BEGIN
    -- 만료된 비밀번호 재설정 토큰 삭제
    DELETE FROM password_reset_tokens 
    WHERE expires_at < NOW() OR (used = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR));
    
    -- 만료된 이메일 인증 토큰 삭제
    DELETE FROM email_verification_tokens 
    WHERE expires_at < NOW() OR (verified = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR));
    
    -- 오래된 로그인 로그 삭제 (6개월 이상)
    DELETE FROM user_login_logs 
    WHERE login_time < DATE_SUB(NOW(), INTERVAL 6 MONTH);
    
    -- 오래된 활동 로그 삭제 (1년 이상)
    DELETE FROM user_activity_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
END;;
DELIMITER ;

-- 이벤트 스케줄러: 매일 자정에 정리 작업 실행
SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS daily_cleanup
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURRENT_DATE + INTERVAL 1 DAY, '00:00:00')
DO
  CALL CleanupExpiredTokens();

-- 권한 확인 함수
DELIMITER ;;
CREATE FUNCTION CheckProjectPermission(p_project_id INT, p_user_id INT, p_required_permission VARCHAR(10))
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_permission VARCHAR(10);
    DECLARE v_user_role VARCHAR(10);
    
    -- 관리자는 모든 권한 보유
    SELECT role INTO v_user_role FROM users WHERE id = p_user_id;
    IF v_user_role = 'admin' THEN
        RETURN TRUE;
    END IF;
    
    -- 프로젝트 소유자 확인
    SELECT 'owner' INTO v_permission 
    FROM projects 
    WHERE id = p_project_id AND user_id = p_user_id;
    
    IF v_permission IS NOT NULL THEN
        RETURN TRUE;
    END IF;
    
    -- 권한 테이블에서 확인
    SELECT permission INTO v_permission
    FROM project_permissions
    WHERE project_id = p_project_id AND user_id = p_user_id;
    
    IF v_permission IS NULL THEN
        RETURN FALSE;
    END IF;
    
    -- 권한 수준 확인
    CASE p_required_permission
        WHEN 'viewer' THEN RETURN TRUE;
        WHEN 'editor' THEN RETURN v_permission IN ('editor', 'owner');
        WHEN 'owner' THEN RETURN v_permission = 'owner';
        ELSE RETURN FALSE;
    END CASE;
END;;
DELIMITER ; 