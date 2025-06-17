<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// 이미 로그인된 경우 리다이렉트
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

// 로그인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = '이메일과 비밀번호를 모두 입력해주세요.';
    } else {
        $user = authenticateUser($email, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            writeLog("사용자 로그인: {$user['email']}", 'INFO');
            
            $redirect = $_GET['redirect'] ?? '../index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = '이메일 또는 비밀번호가 올바르지 않습니다.';
        }
    }
}

// 회원가입 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $company = trim($_POST['company'] ?? '');
    $position = trim($_POST['position'] ?? '');
    
    // 유효성 검사
    if (empty($name) || empty($email) || empty($password)) {
        $error = '필수 항목을 모두 입력해주세요.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일 주소를 입력해주세요.';
    } elseif (strlen($password) < 6) {
        $error = '비밀번호는 최소 6자리 이상이어야 합니다.';
    } elseif ($password !== $password_confirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } elseif (isEmailExists($email)) {
        $error = '이미 등록된 이메일 주소입니다.';
    } else {
        $result = registerUser($name, $email, $password, $company, $position);
        if ($result) {
            $success = '회원가입이 완료되었습니다. 로그인해주세요.';
            writeLog("신규 사용자 등록: {$email}", 'INFO');
        } else {
            $error = '회원가입 중 오류가 발생했습니다. 다시 시도해주세요.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - YourGot AI 위험성평가</title>
    
    <!-- 피그마 디자인 기반 스타일 -->
    <link rel="stylesheet" href="../assets/css/figma-design.css">
    
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--neutral-100);
            padding: var(--spacing-6);
        }
        
        .auth-card {
            width: 100%;
            max-width: 480px;
            background-color: var(--neutral-50);
            border-radius: var(--radius-lg);
            padding: var(--spacing-12);
            box-shadow: var(--shadow-lg);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: var(--spacing-8);
        }
        
        .auth-logo {
            font-size: 28px;
            font-weight: var(--font-weight-bold);
            color: var(--primary-500);
            margin-bottom: var(--spacing-4);
        }
        
        .auth-tabs {
            display: flex;
            border-radius: var(--radius-md);
            background-color: var(--neutral-100);
            padding: var(--spacing-1);
            margin-bottom: var(--spacing-8);
        }
        
        .auth-tab {
            flex: 1;
            padding: var(--spacing-3) var(--spacing-4);
            text-align: center;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: var(--font-weight-bold);
            font-size: 16px;
        }
        
        .auth-tab.active {
            background-color: var(--neutral-50);
            color: var(--primary-500);
            box-shadow: var(--shadow-sm);
        }
        
        .auth-tab.inactive {
            color: var(--neutral-700);
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .alert {
            padding: var(--spacing-3) var(--spacing-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-6);
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #fee;
            color: var(--danger);
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background-color: #efe;
            color: var(--success);
            border: 1px solid #cfc;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            background-color: var(--neutral-300);
            margin-top: var(--spacing-1);
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: var(--danger); width: 33%; }
        .strength-medium { background-color: var(--warning); width: 66%; }
        .strength-strong { background-color: var(--success); width: 100%; }
        
        .divider {
            position: relative;
            text-align: center;
            margin: var(--spacing-6) 0;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: var(--neutral-300);
        }
        
        .divider span {
            background-color: var(--neutral-50);
            padding: 0 var(--spacing-4);
            color: var(--neutral-500);
            font-size: 14px;
        }
        
        .social-login {
            display: flex;
            gap: var(--spacing-3);
            margin-bottom: var(--spacing-6);
        }
        
        .social-btn {
            flex: 1;
            padding: var(--spacing-3);
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-md);
            background-color: var(--neutral-50);
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            font-size: 14px;
            color: var(--neutral-700);
        }
        
        .social-btn:hover {
            background-color: var(--neutral-100);
            border-color: var(--neutral-500);
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">YourGot</div>
                <h1 class="text-h3">AI 위험성평가 시작하기</h1>
                <p class="text-body text-secondary">전문가 수준의 위험성평가를 AI로 간편하게</p>
            </div>
            
            <!-- 탭 메뉴 -->
            <div class="auth-tabs">
                <div class="auth-tab active" id="login-tab">로그인</div>
                <div class="auth-tab inactive" id="register-tab">회원가입</div>
            </div>
            
            <!-- 에러/성공 메시지 -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?= escape($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= escape($success) ?></div>
            <?php endif; ?>
            
            <!-- 로그인 폼 -->
            <form class="auth-form active" id="login-form" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label class="form-label required" for="login-email">이메일</label>
                    <input type="email" class="form-control" id="login-email" name="email" 
                           placeholder="이메일을 입력하세요" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="login-password">비밀번호</label>
                    <input type="password" class="form-control" id="login-password" name="password" 
                           placeholder="비밀번호를 입력하세요" required>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-6);">
                    <label style="display: flex; align-items: center; gap: var(--spacing-2); cursor: pointer;">
                        <input type="checkbox" name="remember" style="margin: 0;">
                        <span class="text-body-sm">로그인 상태 유지</span>
                    </label>
                    <a href="#" class="text-body-sm text-primary" style="text-decoration: none;">비밀번호 찾기</a>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: var(--spacing-4);">
                    로그인
                </button>
            </form>
            
            <!-- 회원가입 폼 -->
            <form class="auth-form" id="register-form" method="POST">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label class="form-label required" for="register-name">이름</label>
                    <input type="text" class="form-control" id="register-name" name="name" 
                           placeholder="이름을 입력하세요" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="register-email">이메일</label>
                    <input type="email" class="form-control" id="register-email" name="email" 
                           placeholder="이메일을 입력하세요" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="register-company">회사명</label>
                    <input type="text" class="form-control" id="register-company" name="company" 
                           placeholder="회사명을 입력하세요">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="register-position">직책</label>
                    <input type="text" class="form-control" id="register-position" name="position" 
                           placeholder="직책을 입력하세요">
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="register-password">비밀번호</label>
                    <input type="password" class="form-control" id="register-password" name="password" 
                           placeholder="비밀번호를 입력하세요 (최소 6자)" required>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strength-bar"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="register-password-confirm">비밀번호 확인</label>
                    <input type="password" class="form-control" id="register-password-confirm" name="password_confirm" 
                           placeholder="비밀번호를 다시 입력하세요" required>
                </div>
                
                <div style="margin-bottom: var(--spacing-6);">
                    <label style="display: flex; align-items: flex-start; gap: var(--spacing-2); cursor: pointer;">
                        <input type="checkbox" required style="margin-top: 2px;">
                        <span class="text-body-sm">
                            <a href="#" class="text-primary" style="text-decoration: none;">이용약관</a> 및 
                            <a href="#" class="text-primary" style="text-decoration: none;">개인정보처리방침</a>에 동의합니다.
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: var(--spacing-4);">
                    회원가입
                </button>
            </form>
            
            <!-- 소셜 로그인 (선택사항) -->
            <div class="divider">
                <span>또는</span>
            </div>
            
            <div class="social-login">
                <button class="social-btn" type="button">
                    <svg width="20" height="20" viewBox="0 0 24 24" style="margin-right: 8px;">
                        <path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Google
                </button>
                <button class="social-btn" type="button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877f2" style="margin-right: 8px;">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
                </button>
            </div>
            
            <div class="text-center">
                <p class="text-body-sm text-secondary">
                    계정이 없으신가요? 
                    <a href="#" class="text-primary" style="text-decoration: none;" id="switch-to-register">회원가입하기</a>
                </p>
                <p class="text-body-sm text-secondary">
                    이미 계정이 있으신가요? 
                    <a href="#" class="text-primary" style="text-decoration: none;" id="switch-to-login">로그인하기</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // 탭 전환
        const loginTab = document.getElementById('login-tab');
        const registerTab = document.getElementById('register-tab');
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const switchToRegister = document.getElementById('switch-to-register');
        const switchToLogin = document.getElementById('switch-to-login');

        function showLogin() {
            loginTab.className = 'auth-tab active';
            registerTab.className = 'auth-tab inactive';
            loginForm.className = 'auth-form active';
            registerForm.className = 'auth-form';
        }

        function showRegister() {
            loginTab.className = 'auth-tab inactive';
            registerTab.className = 'auth-tab active';
            loginForm.className = 'auth-form';
            registerForm.className = 'auth-form active';
        }

        loginTab.addEventListener('click', showLogin);
        registerTab.addEventListener('click', showRegister);
        switchToRegister.addEventListener('click', (e) => {
            e.preventDefault();
            showRegister();
        });
        switchToLogin.addEventListener('click', (e) => {
            e.preventDefault();
            showLogin();
        });

        // 비밀번호 강도 체크
        const passwordInput = document.getElementById('register-password');
        const strengthBar = document.getElementById('strength-bar');

        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            return strength;
        }

        passwordInput.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            strengthBar.className = 'password-strength-bar';
            
            if (strength <= 1) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });

        // 비밀번호 확인 검증
        const confirmPassword = document.getElementById('register-password-confirm');
        confirmPassword.addEventListener('input', function() {
            if (this.value && this.value !== passwordInput.value) {
                this.style.borderColor = 'var(--danger)';
            } else {
                this.style.borderColor = 'var(--neutral-300)';
            }
        });

        // URL 파라미터로 초기 탭 설정
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const mode = urlParams.get('mode');
            
            if (mode === 'register') {
                showRegister();
            }
        });

        // 소셜 로그인 (추후 구현)
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                alert('소셜 로그인 기능은 추후 구현 예정입니다.');
            });
        });
    </script>
</body>
</html> 