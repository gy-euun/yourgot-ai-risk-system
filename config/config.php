<?php
/**
 * 데이터베이스 및 시스템 설정
 */

// 환경변수 로더 불러오기
require_once __DIR__ . '/env.php';

// .env 파일 로드
try {
    Env::load();
} catch (Exception $e) {
    // .env 파일이 없는 경우 기본값 사용
    error_log('Warning: ' . $e->getMessage());
}

// UTF-8 설정 (ANSI 오류 방지)
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// 출력 버퍼링 시작 (ANSI 오류 방지)
if (!ob_get_level()) {
    ob_start();
}

// 타임존 설정
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Seoul'));

// 에러 리포팅 설정
if (Env::get('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 세션 설정 (세션 시작 전에 설정)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', Env::get('SESSION_LIFETIME', 3600));
    ini_set('session.cookie_lifetime', Env::get('SESSION_LIFETIME', 3600));
    
    if (Env::get('SESSION_SAVE_PATH')) {
        ini_set('session.save_path', Env::get('SESSION_SAVE_PATH'));
    }
    
    // 세션 시작
    session_start();
}

// 데이터베이스 설정
define('DB_HOST', Env::get('DB_HOST', 'localhost'));
define('DB_NAME', Env::get('DB_NAME', 'yourgot_db'));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASSWORD', ''));
define('DB_CHARSET', 'utf8mb4');

// OpenAI API 설정
define('OPENAI_API_KEY', Env::get('OPENAI_API_KEY', ''));
define('OPENAI_MODEL', Env::get('OPENAI_MODEL', 'gpt-4-turbo-preview'));
define('OPENAI_MAX_TOKENS', (int)Env::get('OPENAI_MAX_TOKENS', 2000));
define('OPENAI_TEMPERATURE', (float)Env::get('OPENAI_TEMPERATURE', 0.7));

// CSRF 보안 설정
define('CSRF_SECRET_KEY', Env::get('CSRF_SECRET_KEY', 'default_secret_key_change_this'));

// 애플리케이션 설정
define('APP_DEBUG', Env::get('APP_DEBUG', false));
define('APP_TIMEZONE', Env::get('APP_TIMEZONE', 'Asia/Seoul'));
define('APP_LANGUAGE', Env::get('APP_LANGUAGE', 'ko'));

// 시스템 설정
define('SITE_URL', 'http://localhost/yourgot');
define('SITE_NAME', 'AI 기반 위험성평가 지원 시스템');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// 파일 업로드 설정
define('MAX_FILE_SIZE', (int)Env::get('MAX_FILE_SIZE', 10485760)); // 10MB
$allowed_types_json = Env::get('ALLOWED_FILE_TYPES', '["pdf","doc","docx","xls","xlsx","jpg","jpeg","png","gif"]');
define('ALLOWED_FILE_TYPES', json_decode($allowed_types_json, true) ?: ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','gif']);

// 위험성 척도 등급 정의
define('RISK_LEVELS', [
    1 => [
        'name' => '매우낮음',
        'color' => '#28a745',
        'action' => '현재 수준 유지'
    ],
    2 => [
        'name' => '낮음', 
        'color' => '#6f42c1',
        'action' => '현재 수준 유지'
    ],
    3 => [
        'name' => '보통',
        'color' => '#ffc107', 
        'action' => '위험성 감소 검토'
    ],
    4 => [
        'name' => '높음',
        'color' => '#fd7e14',
        'action' => '위험성 감소 필요'
    ],
    5 => [
        'name' => '매우높음',
        'color' => '#dc3545',
        'action' => '즉시 개선 필요'
    ]
]);

// 위험 분류 카테고리
define('RISK_CATEGORIES', [
    'mechanical' => '기계적 위험',
    'electrical' => '전기적 위험',
    'chemical' => '화학적 위험',
    'physical' => '물리적 위험',
    'biological' => '생물학적 위험',
    'ergonomic' => '인간공학적 위험',
    'psychological' => '심리사회적 위험',
    'environmental' => '환경적 위험'
]);

// 로그 레벨 정의
define('LOG_LEVELS', [
    'DEBUG' => 0,
    'INFO' => 1,
    'WARNING' => 2,
    'ERROR' => 3,
    'CRITICAL' => 4
]);

/**
 * 데이터베이스 연결
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            writeLog("데이터베이스 연결 실패: " . $e->getMessage(), 'CRITICAL');
            throw new Exception('데이터베이스 연결에 실패했습니다.');
        }
    }
    
    return $pdo;
}

/**
 * HTML 특수문자 이스케이프
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 로그 기록
 */
function writeLog($message, $level = 'INFO') {
    $log_dir = __DIR__ . '/../logs/';
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . 'system.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * CSRF 토큰 생성
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF 토큰 검증
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 안전한 리다이렉트
 */
function safeRedirect($url, $default = '/') {
    // 상대 URL만 허용
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        if (strpos($url, '/') === 0 || strpos($url, './') === 0) {
            header('Location: ' . $url);
            exit;
        }
    }
    
    header('Location: ' . $default);
    exit;
}

/**
 * 시스템 상태 확인
 */
function checkSystemHealth() {
    $health = [
        'database' => false,
        'openai_api' => false,
        'file_permissions' => false
    ];
    
    // 데이터베이스 연결 확인
    try {
        getDBConnection();
        $health['database'] = true;
    } catch (Exception $e) {
        writeLog("시스템 상태 확인 - 데이터베이스 오류: " . $e->getMessage(), 'ERROR');
    }
    
    // OpenAI API 키 확인
    if (!empty(OPENAI_API_KEY) && OPENAI_API_KEY !== 'your_openai_api_key_here') {
        $health['openai_api'] = true;
    }
    
    // 파일 권한 확인
    if (is_writable(UPLOAD_PATH) && is_writable(__DIR__ . '/../logs/')) {
        $health['file_permissions'] = true;
    }
    
    return $health;
}

// 기본 에러 핸들러 설정
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    
    $error_type = '';
    switch ($severity) {
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            $error_type = 'ERROR';
            break;
        case E_WARNING:
        case E_CORE_WARNING:
        case E_COMPILE_WARNING:
        case E_USER_WARNING:
            $error_type = 'WARNING';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $error_type = 'NOTICE';
            break;
        default:
            $error_type = 'UNKNOWN';
    }
    
    writeLog("{$error_type}: {$message} in {$file} on line {$line}", $error_type);
});

// 예외 핸들러 설정
set_exception_handler(function($exception) {
    writeLog("Uncaught Exception: " . $exception->getMessage() . " in " . 
             $exception->getFile() . " on line " . $exception->getLine(), 'CRITICAL');
    
    if (Env::get('APP_DEBUG', false)) {
        echo "Exception: " . $exception->getMessage();
    } else {
        echo "시스템 오류가 발생했습니다. 관리자에게 문의하세요.";
    }
});

/**
 * 시스템 초기화 완료 로그
 */
writeLog("시스템 초기화 완료", 'INFO');
?> 