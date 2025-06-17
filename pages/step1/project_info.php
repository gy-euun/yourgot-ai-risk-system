<?php
/**
 * 1단계: 프로젝트 기본정보 입력
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// 로그인 확인
requireLogin();

$error = '';
$success = '';

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_name = trim($_POST['project_name'] ?? '');
    $work_location = trim($_POST['work_location'] ?? '');
    $creation_date = $_POST['creation_date'] ?? '';
    $manager_name = trim($_POST['manager_name'] ?? '');
    $manager_position = trim($_POST['manager_position'] ?? '');
    $manager_contact = trim($_POST['manager_contact'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $project_description = trim($_POST['project_description'] ?? '');
    
    // 유효성 검사
    if (empty($project_name)) {
        $error = '프로젝트명을 입력해주세요.';
    } elseif (empty($work_location)) {
        $error = '작업 장소를 입력해주세요.';
    } elseif (empty($creation_date)) {
        $error = '작성일을 선택해주세요.';
    } elseif (empty($manager_name)) {
        $error = '담당자 이름을 입력해주세요.';
    } elseif (empty($manager_position)) {
        $error = '담당자 직책을 입력해주세요.';
    } else {
        try {
            $pdo = getDBConnection();
            $user = getCurrentUser();
            
            // 프로젝트 저장
            $stmt = $pdo->prepare("
                INSERT INTO projects (
                    user_id, project_name, work_location, creation_date, 
                    manager_name, manager_position, manager_contact, 
                    company_name, project_description, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'step1', NOW())
            ");
            
            $stmt->execute([
                $user['id'],
                $project_name,
                $work_location,
                $creation_date,
                $manager_name,
                $manager_position,
                $manager_contact,
                $company_name,
                $project_description
            ]);
            
            $project_id = $pdo->lastInsertId();
            
            // 세션에 프로젝트 ID 저장
            $_SESSION['current_project_id'] = $project_id;
            
            // 활동 로그 기록
            logUserActivity($user['id'], 'create_project', 'project', $project_id, [
                'project_name' => $project_name,
                'work_location' => $work_location
            ]);
            
            // 다음 단계로 이동
            header('Location: ../step2/process_info.php');
            exit;
            
        } catch (Exception $e) {
            writeLog("프로젝트 생성 오류: " . $e->getMessage(), 'ERROR');
            $error = '프로젝트 생성 중 오류가 발생했습니다.';
        }
    }
}

// 현재 사용자 정보 가져오기 (기본값으로 사용)
$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1단계: 프로젝트 기본정보 - YourGot AI 위험성평가</title>
    <link rel="stylesheet" href="../../assets/css/figma-design.css">
    
    <style>
        body {
            background-color: var(--neutral-100);
        }

        .step-container {
            max-width: 900px;
            margin: 0 auto;
            padding: var(--spacing-10) var(--spacing-6);
        }
        
        .step-header {
            text-align: center;
            margin-bottom: var(--spacing-10);
        }
        
        .step-progress {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: var(--spacing-8);
            position: relative;
        }

        .progress-line {
            position: absolute;
            height: 2px;
            background-color: var(--neutral-300);
            width: 60%;
            top: 50%;
            left: 20%;
            transform: translateY(-50%);
            z-index: 1;
        }

        .progress-line-active {
            position: absolute;
            height: 2px;
            background-color: var(--primary-500);
            width: 0%; /* JSで制御 */
            top: 50%;
            left: 20%;
            transform: translateY(-50%);
            z-index: 2;
            transition: width 0.5s ease-in-out;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 3;
            width: 120px;
        }
        
        .progress-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid var(--neutral-300);
            background-color: var(--neutral-50);
            color: var(--neutral-500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: var(--font-weight-bold);
            font-size: 14px;
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
            margin-bottom: var(--spacing-2);
        }
        
        .progress-step.active .progress-number {
            background-color: var(--primary-500);
            border-color: var(--primary-500);
            color: var(--neutral-50);
        }
        
        .progress-step.completed .progress-number {
            background-color: var(--success);
            border-color: var(--success);
            color: var(--neutral-50);
        }

        .progress-text {
            font-size: 14px;
            color: var(--neutral-700);
            font-weight: 500;
        }

        .progress-step.active .progress-text,
        .progress-step.completed .progress-text {
            color: var(--neutral-900);
        }
        
        .form-section {
            background-color: var(--neutral-50);
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-lg);
            padding: var(--spacing-8);
            box-shadow: var(--shadow-sm);
        }
        
        .form-section-title {
            font-size: 18px;
            font-weight: var(--font-weight-semibold);
            color: var(--neutral-900);
            margin-bottom: var(--spacing-8);
            padding-bottom: var(--spacing-4);
            border-bottom: 1px solid var(--neutral-300);
            display: flex;
            align-items: center;
        }

        .form-section-title svg {
            margin-right: var(--spacing-3);
            color: var(--primary-500);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-6);
        }
        
        .form-row.single {
            grid-template-columns: 1fr;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: var(--spacing-8);
            padding-top: var(--spacing-6);
            border-top: 1px solid var(--neutral-300);
        }
        
        .alert {
            padding: var(--spacing-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-6);
            border: 1px solid;
        }
        
        .alert-error {
            background-color: #fee;
            border-color: var(--danger);
            color: #c53030;
        }
        
        .alert-success {
            background-color: #e6fffa;
            border-color: var(--success);
            color: #2f855a;
        }
        
        @media (max-width: 768px) {
            .step-container {
                padding: var(--spacing-6) var(--spacing-4);
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: var(--spacing-4);
            }
            
            .progress-step:not(:last-child)::after {
                width: 40px;
                right: -20px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: var(--spacing-4);
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header" style="background-color: var(--neutral-50); border-bottom: 1px solid var(--neutral-300);">
        <div class="container">
            <div class="header-logo">
                <a href="../../index.php" style="color: inherit; text-decoration: none;">YourGot</a>
            </div>
            
            <nav class="header-nav">
                <span class="header-nav-item" style="color: var(--primary-500);">
                    위험성평가 진행 중
                </span>
            </nav>
            
            <div class="header-actions">
                <span class="text-body-sm text-secondary">
                    <?= escape($user['name']) ?> 님
                </span>
                <a href="../../auth/logout.php" class="btn btn-secondary btn-sm">
                    로그아웃
                </a>
            </div>
        </div>
    </header>

    <div class="step-container">
        <!-- 진행 상황 표시 -->
        <div class="step-header">
            <div class="step-progress">
                <div class="progress-step active" data-step="1">
                    <div class="progress-number">1</div>
                    <div class="progress-text">기본정보</div>
                </div>
                <div class="progress-step" data-step="2">
                    <div class="progress-number">2</div>
                    <div class="progress-text">공정정보</div>
                </div>
                <div class="progress-step" data-step="3">
                    <div class="progress-number">3</div>
                    <div class="progress-text">AI분석</div>
                </div>
                <div class="progress-step" data-step="4">
                    <div class="progress-number">4</div>
                    <div class="progress-text">보고서</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-line-active" id="progress-line-active"></div>
            </div>
            
            <h1 class="text-h2">프로젝트 기본정보 입력</h1>
            <p class="text-body text-secondary">
                위험성평가를 진행할 프로젝트의 기본 정보를 입력해주세요.
            </p>
        </div>

        <!-- 에러/성공 메시지 -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= escape($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= escape($success) ?>
            </div>
        <?php endif; ?>

        <!-- 프로젝트 정보 입력 폼 -->
        <form method="POST" action="">
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    프로젝트 정보
                </h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="project_name" class="form-label required">프로젝트명</label>
                        <input type="text" id="project_name" name="project_name" class="form-control" placeholder="예: 아파트 신축공사 안전관리" required>
                        <p class="form-text">진행할 프로젝트의 전체 명칭을 입력해주세요.</p>
                    </div>
                    <div class="form-group">
                        <label for="work_location" class="form-label required">작업 장소</label>
                        <input type="text" id="work_location" name="work_location" class="form-control" placeholder="예: 서울시 강남구 테헤란로 123" required>
                        <p class="form-text">작업이 이루어지는 구체적인 주소를 입력합니다.</p>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_name" class="form-label">회사명</label>
                        <input type="text" id="company_name" name="company_name" class="form-control" value="<?= escape($user['company'] ?? '') ?>" placeholder="예: (주)세이프체크">
                        <p class="form-text">프로젝트를 진행하는 회사명을 입력합니다.</p>
                    </div>
                     <div class="form-group">
                        <label for="creation_date" class="form-label required">작성일</label>
                        <input type="date" id="creation_date" name="creation_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        <p class="form-text">위험성평가표가 작성되는 날짜입니다.</p>
                    </div>
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label for="project_description" class="form-label">프로젝트 설명</label>
                        <textarea id="project_description" name="project_description" class="form-control" rows="3" placeholder="프로젝트의 특징이나 주요 사항을 간략히 설명해주세요."></textarea>
                        <p class="form-text">프로젝트의 핵심 목표나 범위를 간략히 설명합니다.</p>
                    </div>
                </div>
            </div>

             <div class="form-section" style="margin-top: var(--spacing-8);">
                <h2 class="form-section-title">
                     <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"></circle><path d="M12 14s-4 4-4 4h8s-4-4-4-4z"></path><path d="M12 14v7"></path></svg>
                    담당자 정보
                </h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="manager_name" class="form-label required">담당자 이름</label>
                        <input type="text" id="manager_name" name="manager_name" class="form-control" value="<?= escape($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="manager_position" class="form-label required">담당자 직책</label>
                        <input type="text" id="manager_position" name="manager_position" class="form-control" value="<?= escape($user['position'] ?? '') ?>" placeholder="예: 안전관리자, 현장소장" required>
                    </div>
                </div>
                 <div class="form-row single">
                    <div class="form-group">
                        <label for="manager_contact" class="form-label">담당자 연락처</label>
                        <input type="text" id="manager_contact" name="manager_contact" class="form-control" placeholder="예: 010-1234-5678">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    저장하고 다음 단계로 →
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activeStep = 1; 
            const totalSteps = 4;
            const progressLine = document.getElementById('progress-line-active');
            
            if (progressLine) {
                const percentage = (activeStep - 1) / (totalSteps - 1) * 60;
                progressLine.style.width = percentage + '%';
            }

            // 모든 completed step 처리
            const steps = document.querySelectorAll('.progress-step');
            steps.forEach(step => {
                const stepNumber = parseInt(step.dataset.step, 10);
                if (stepNumber < activeStep) {
                    step.classList.add('completed');
                }
            });
        });
    </script>
</body>
</html> 