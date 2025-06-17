<?php
// 공통 함수 파일

/**
 * 현재 사용자의 세션 정보를 가져오거나 생성
 */
function getCurrentSession() {
    if (!isset($_SESSION['user_session_id'])) {
        $_SESSION['user_session_id'] = bin2hex(random_bytes(16));
        
        // 데이터베이스에 세션 저장
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO user_sessions (session_id, current_step, session_data) 
                VALUES (?, 1, '{}')
                ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$_SESSION['user_session_id']]);
        } catch (Exception $e) {
            writeLog("세션 생성 실패: " . $e->getMessage(), 'ERROR');
        }
    }
    
    return $_SESSION['user_session_id'];
}

/**
 * 프로젝트 진행 단계 업데이트
 */
function updateProgressStep($step, $project_id = null) {
    $session_id = getCurrentSession();
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET current_step = ?, project_id = ?, last_activity = CURRENT_TIMESTAMP
            WHERE session_id = ?
        ");
        $stmt->execute([$step, $project_id, $session_id]);
        
        $_SESSION['current_step'] = $step;
        if ($project_id) {
            $_SESSION['project_id'] = $project_id;
        }
        
        return true;
    } catch (Exception $e) {
        writeLog("진행 단계 업데이트 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 현재 프로젝트 ID 가져오기
 */
function getCurrentProjectId() {
    if (isset($_SESSION['project_id'])) {
        return $_SESSION['project_id'];
    }
    
    $session_id = getCurrentSession();
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT project_id FROM user_sessions WHERE session_id = ?");
        $stmt->execute([$session_id]);
        $result = $stmt->fetch();
        
        if ($result && $result['project_id']) {
            $_SESSION['project_id'] = $result['project_id'];
            return $result['project_id'];
        }
    } catch (Exception $e) {
        writeLog("프로젝트 ID 조회 실패: " . $e->getMessage(), 'ERROR');
    }
    
    return null;
}

/**
 * 폼 데이터 유효성 검증
 */
function validateForm($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = isset($data[$field]) ? trim($data[$field]) : '';
        
        // 필수 필드 검증
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[$field] = $rule['name'] . '은(는) 필수 입력 항목입니다.';
            continue;
        }
        
        // 길이 검증
        if (!empty($value) && isset($rule['max_length'])) {
            if (mb_strlen($value, 'UTF-8') > $rule['max_length']) {
                $errors[$field] = $rule['name'] . '은(는) ' . $rule['max_length'] . '자 이내로 입력해주세요.';
            }
        }
        
        if (!empty($value) && isset($rule['min_length'])) {
            if (mb_strlen($value, 'UTF-8') < $rule['min_length']) {
                $errors[$field] = $rule['name'] . '은(는) ' . $rule['min_length'] . '자 이상 입력해주세요.';
            }
        }
        
        // 이메일 검증
        if (!empty($value) && isset($rule['email']) && $rule['email']) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = '올바른 이메일 형식이 아닙니다.';
            }
        }
        
        // 날짜 검증
        if (!empty($value) && isset($rule['date']) && $rule['date']) {
            $date = DateTime::createFromFormat('Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                $errors[$field] = '올바른 날짜 형식이 아닙니다. (YYYY-MM-DD)';
            }
        }
    }
    
    return $errors;
}

/**
 * 프로젝트 정보 저장
 */
function saveProjectInfo($data) {
    try {
        $pdo = getDBConnection();
        $session_id = getCurrentSession();
        
        $stmt = $pdo->prepare("
            INSERT INTO projects (
                project_name, work_location, creation_date, 
                manager_name, manager_position, session_id
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['project_name'],
            $data['work_location'],
            $data['creation_date'],
            $data['manager_name'],
            $data['manager_position'],
            $session_id
        ]);
        
        $project_id = $pdo->lastInsertId();
        updateProgressStep(2, $project_id);
        
        writeLog("프로젝트 생성: ID {$project_id}, 이름: {$data['project_name']}", 'INFO');
        
        return $project_id;
    } catch (Exception $e) {
        writeLog("프로젝트 저장 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 공정 정보 저장
 */
function saveProcessInfo($data) {
    $project_id = getCurrentProjectId();
    if (!$project_id) {
        return false;
    }
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO process_info (
                project_id, process_name, detailed_work, work_location,
                equipment_name, main_materials, weather_conditions, work_hours,
                high_altitude_work, confined_space
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $project_id,
            $data['process_name'],
            $data['detailed_work'],
            $data['work_location'],
            $data['equipment_name'] ?? '',
            $data['main_materials'] ?? '',
            $data['weather_conditions'] ?? '',
            $data['work_hours'] ?? '',
            $data['high_altitude_work'] ?? 'N',
            $data['confined_space'] ?? 'N'
        ]);
        
        $process_id = $pdo->lastInsertId();
        updateProgressStep(3);
        
        writeLog("공정 정보 저장: ID {$process_id}, 공정명: {$data['process_name']}", 'INFO');
        
        return $process_id;
    } catch (Exception $e) {
        writeLog("공정 정보 저장 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 위험성평가 결과 저장
 */
function saveRiskAssessment($data) {
    $project_id = getCurrentProjectId();
    if (!$project_id) {
        return false;
    }
    
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // 기존 평가 결과 삭제
        $stmt = $pdo->prepare("DELETE FROM risk_assessments WHERE project_id = ?");
        $stmt->execute([$project_id]);
        
        // 새로운 평가 결과 저장
        $stmt = $pdo->prepare("
            INSERT INTO risk_assessments (
                project_id, risk_factor, risk_category, likelihood, 
                severity, risk_scale, safety_measures, ai_suggestions,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        foreach ($data['risks'] as $risk) {
            $stmt->execute([
                $project_id,
                $risk['factor'],
                $risk['category'],
                $risk['likelihood'],
                $risk['severity'],
                $risk['scale'],
                $risk['measures'],
                $risk['ai_suggestions'] ?? ''
            ]);
        }
        
        $pdo->commit();
        updateProgressStep(4);
        
        writeLog("위험성평가 저장 완료: 프로젝트 ID {$project_id}", 'INFO');
        
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        writeLog("위험성평가 저장 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 프로젝트 데이터 조회
 */
function getProjectData($project_id) {
    try {
        $pdo = getDBConnection();
        
        // 프로젝트 기본 정보
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        
        if (!$project) {
            return null;
        }
        
        // 공정 정보
        $stmt = $pdo->prepare("SELECT * FROM process_info WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $processes = $stmt->fetchAll();
        
        // 위험성평가 결과
        $stmt = $pdo->prepare("SELECT * FROM risk_assessments WHERE project_id = ? ORDER BY created_at DESC");
        $stmt->execute([$project_id]);
        $assessments = $stmt->fetchAll();
        
        return [
            'project' => $project,
            'processes' => $processes,
            'assessments' => $assessments
        ];
    } catch (Exception $e) {
        writeLog("프로젝트 데이터 조회 실패: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

/**
 * 위험도 색상 반환
 */
function getRiskColor($risk_scale) {
    return RISK_LEVELS[$risk_scale]['color'] ?? '#6c757d';
}

/**
 * 위험도 이름 반환
 */
function getRiskLevelName($risk_scale) {
    return RISK_LEVELS[$risk_scale]['name'] ?? '알 수 없음';
}

/**
 * 위험도 조치사항 반환
 */
function getRiskAction($risk_scale) {
    return RISK_LEVELS[$risk_scale]['action'] ?? '검토 필요';
}

/**
 * 파일 업로드 처리
 */
function handleFileUpload($file, $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'png']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '파일 업로드 실패'];
    }
    
    $upload_dir = UPLOAD_PATH;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => '허용되지 않는 파일 형식입니다.'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        $max_size_mb = MAX_FILE_SIZE / 1024 / 1024;
        return ['success' => false, 'message' => "파일 크기는 {$max_size_mb}MB 이하여야 합니다."];
    }
    
    $new_filename = uniqid() . '.' . $extension;
    $destination = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'original_name' => $file['name'],
            'path' => $destination
        ];
    } else {
        return ['success' => false, 'message' => '파일 저장 실패'];
    }
}

/**
 * 페이지네이션 생성
 */
function generatePagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<nav aria-label="페이지 네비게이션"><ul class="pagination justify-content-center">';
    
    // 이전 페이지
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= "<li class='page-item'><a class='page-link' href='{$base_url}?page={$prev_page}'>이전</a></li>";
    } else {
        $pagination .= "<li class='page-item disabled'><span class='page-link'>이전</span></li>";
    }
    
    // 페이지 번호
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $pagination .= "<li class='page-item'><a class='page-link' href='{$base_url}?page=1'>1</a></li>";
        if ($start > 2) {
            $pagination .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $pagination .= "<li class='page-item {$active}'><a class='page-link' href='{$base_url}?page={$i}'>{$i}</a></li>";
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $pagination .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        $pagination .= "<li class='page-item'><a class='page-link' href='{$base_url}?page={$total_pages}'>{$total_pages}</a></li>";
    }
    
    // 다음 페이지
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= "<li class='page-item'><a class='page-link' href='{$base_url}?page={$next_page}'>다음</a></li>";
    } else {
        $pagination .= "<li class='page-item disabled'><span class='page-link'>다음</span></li>";
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

/**
 * JSON 응답 전송
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 성공 메시지 설정
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * 에러 메시지 설정
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * 메시지 표시 및 삭제
 */
function displayMessages() {
    $messages = '';
    
    if (isset($_SESSION['success_message'])) {
        $messages .= '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        $messages .= escape($_SESSION['success_message']);
        $messages .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        $messages .= '</div>';
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        $messages .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        $messages .= escape($_SESSION['error_message']);
        $messages .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        $messages .= '</div>';
        unset($_SESSION['error_message']);
    }
    
    return $messages;
}

// ===== 인증 관련 함수들 =====

/**
 * 로그인 상태 확인
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * 현재 로그인된 사용자 정보 가져오기
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        writeLog("사용자 정보 조회 실패: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

/**
 * 사용자 인증
 */
function authenticateUser($email, $password) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // 로그인 로그 기록
            $stmt = $pdo->prepare("
                INSERT INTO user_login_logs (user_id, ip_address, user_agent, login_time)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // 마지막 로그인 시간 업데이트
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            return $user;
        }
        
        return false;
    } catch (Exception $e) {
        writeLog("사용자 인증 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 사용자 등록
 */
function registerUser($name, $email, $password, $company = '', $position = '') {
    try {
        $pdo = getDBConnection();
        
        // 비밀번호 해시화
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, company, position, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'user', 'active', NOW())
        ");
        
        $result = $stmt->execute([$name, $email, $password_hash, $company, $position]);
        
        if ($result) {
            $user_id = $pdo->lastInsertId();
            writeLog("신규 사용자 등록: ID {$user_id}, 이메일: {$email}", 'INFO');
            return $user_id;
        }
        
        return false;
    } catch (Exception $e) {
        writeLog("사용자 등록 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 이메일 중복 확인
 */
function isEmailExists($email) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        writeLog("이메일 중복 확인 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 로그아웃
 */
function logout() {
    if (isLoggedIn()) {
        writeLog("사용자 로그아웃: {$_SESSION['user_email']}", 'INFO');
        
        // 세션 변수 정리
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_role']);
    }
    
    // 전체 세션 파괴
    session_destroy();
    session_start();
}

/**
 * 로그인 필요 페이지 보호
 */
function requireLogin($redirect_url = null) {
    if (!isLoggedIn()) {
        $redirect = $redirect_url ?? $_SERVER['REQUEST_URI'];
        header('Location: /yourgot/auth/login.php?redirect=' . urlencode($redirect));
        exit;
    }
}

/**
 * 관리자 권한 확인
 */
function requireAdmin() {
    requireLogin();
    
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('HTTP/1.0 403 Forbidden');
        die('접근 권한이 없습니다.');
    }
}

/**
 * 비밀번호 재설정 토큰 생성
 */
function generatePasswordResetToken($email) {
    try {
        $pdo = getDBConnection();
        
        // 사용자 존재 확인
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // 토큰 생성
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // 기존 토큰 삭제
        $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        // 새 토큰 저장
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user['id'], $token, $expires_at]);
        
        return $token;
    } catch (Exception $e) {
        writeLog("비밀번호 재설정 토큰 생성 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 비밀번호 재설정 토큰 검증
 */
function verifyPasswordResetToken($token) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT prt.*, u.email 
            FROM password_reset_tokens prt
            JOIN users u ON prt.user_id = u.id
            WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used = 0
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    } catch (Exception $e) {
        writeLog("비밀번호 재설정 토큰 검증 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 비밀번호 재설정
 */
function resetPassword($token, $new_password) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // 토큰 검증
        $token_data = verifyPasswordResetToken($token);
        if (!$token_data) {
            $pdo->rollBack();
            return false;
        }
        
        // 비밀번호 업데이트
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$password_hash, $token_data['user_id']]);
        
        // 토큰 사용 처리
        $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        $stmt->execute([$token]);
        
        $pdo->commit();
        
        writeLog("비밀번호 재설정 완료: 사용자 ID {$token_data['user_id']}", 'INFO');
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        writeLog("비밀번호 재설정 실패: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 사용자 활동 로그 기록
 */
function logUserActivity($user_id, $action, $target_type = null, $target_id = null, $data = []) {
    try {
        // 우선 시스템 로그에만 기록 (데이터베이스 테이블이 없을 수 있음)
        $log_message = "사용자 활동: ID {$user_id}, 액션: {$action}";
        if ($target_type && $target_id) {
            $log_message .= ", 대상: {$target_type} #{$target_id}";
        }
        writeLog($log_message, 'INFO');
        
        // 추후 데이터베이스 테이블이 준비되면 아래 코드 활성화
        /*
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (
                user_id, action, target_type, target_id, data, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $target_type,
            $target_id,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        ]);
        */
        
        return true;
    } catch (Exception $e) {
        // 로그 기록 실패 시 시스템 로그에만 기록
        writeLog("사용자 활동 로그 기록 실패: " . $e->getMessage(), 'WARNING');
        return false;
    }
}
?> 