<?php
/**
 * 3단계: AI 위험성평가 분석 및 편집
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../api/openai_service.php';

// 로그인 확인
requireLogin();

$error = '';
$success = '';
$ai_analysis_result = null;
$is_analyzing = false;

// 현재 프로젝트 확인
$project_id = $_SESSION['current_project_id'] ?? null;
if (!$project_id) {
    header('Location: ../step1/project_info.php');
    exit;
}

// 프로젝트 정보 조회
$user = getCurrentUser();
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
$stmt->execute([$project_id, $user['id']]);
$project = $stmt->fetch();

if (!$project) {
    $_SESSION['error'] = '프로젝트를 찾을 수 없습니다.';
    header('Location: ../step1/project_info.php');
    exit;
}

// 공정정보 가져오기
$processes = json_decode($project['processes'] ?? '[]', true);
if (empty($processes)) {
    $_SESSION['error'] = '공정정보를 먼저 입력해주세요.';
    header('Location: ../step2/process_info.php');
    exit;
}

// 기존 AI 분석 결과 조회
$existing_analysis = json_decode($project['ai_analysis'] ?? '[]', true);

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'start_ai_analysis') {
        // AI 분석 시작
        $is_analyzing = true;
        
        try {
            // OpenAI API 서비스 인스턴스 생성
            $openai_service = new OpenAIService();
            
            // AI 분석 실행 (프로젝트 데이터와 공정 데이터 전달)
            $ai_analysis_result = $openai_service->analyzeRisk($project, $processes);
            
            if ($ai_analysis_result && !empty($ai_analysis_result)) {
                // 결과를 데이터베이스에 저장
                $stmt = $pdo->prepare("UPDATE projects SET ai_analysis = ?, status = 'step3' WHERE id = ?");
                $stmt->execute([json_encode($ai_analysis_result), $project_id]);
                
                $success = 'AI 위험성평가 분석이 완료되었습니다.';
                
                // 활동 로그 기록
                logUserActivity($_SESSION['user_id'], 'ai_analysis', 'project', $project_id, [
                    'analysis_count' => count($ai_analysis_result)
                ]);
                
                $existing_analysis = $ai_analysis_result;
                
            } else {
                $error = 'AI 분석 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
            }
            
        } catch (Exception $e) {
            writeLog("AI 분석 오류: " . $e->getMessage(), 'ERROR');
            $error = 'AI 분석 중 시스템 오류가 발생했습니다.';
        }
        
        $is_analyzing = false;
        
    } elseif ($action === 'update_analysis') {
        // 위험성평가 수정
        $analysis_index = $_POST['analysis_index'] ?? 0;
        $classification = trim($_POST['classification'] ?? '');
        $cause = trim($_POST['cause'] ?? '');
        $hazard_factor = trim($_POST['hazard_factor'] ?? '');
        $current_measures = trim($_POST['current_measures'] ?? '');
        $risk_level = $_POST['risk_level'] ?? '';
        $reduction_measures = trim($_POST['reduction_measures'] ?? '');
        $responsible_person = trim($_POST['responsible_person'] ?? '');
        
        if (!empty($existing_analysis) && isset($existing_analysis[$analysis_index])) {
            $existing_analysis[$analysis_index] = array_merge($existing_analysis[$analysis_index], [
                'classification' => $classification,
                'cause' => $cause,
                'hazard_factors' => $hazard_factor,
                'current_measures' => $current_measures,
                'risk_level' => $risk_level,
                'reduction_measures' => $reduction_measures,
                'responsible_person' => $responsible_person,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // 데이터베이스 업데이트
            $stmt = $pdo->prepare("UPDATE projects SET ai_analysis = ? WHERE id = ?");
            $stmt->execute([json_encode($existing_analysis), $project_id]);
            
            $success = '위험성평가 항목이 수정되었습니다.';
        }
        
    } elseif ($action === 'next_step') {
        // 다음 단계로 진행
        if (empty($existing_analysis)) {
            $error = 'AI 분석을 먼저 실행해주세요.';
        } else {
            header('Location: ../step4/report.php');
            exit;
        }
    }
}

/**
 * 위험성평가 AI 프롬프트 생성
 */
function generateRiskAssessmentPrompt($project, $processes) {
    $prompt = "다음 프로젝트에 대한 위험성평가를 수행해주세요.\n\n";
    
    // 프로젝트 기본 정보
    $prompt .= "=== 프로젝트 정보 ===\n";
    $prompt .= "프로젝트명: " . $project['project_name'] . "\n";
    $prompt .= "작업장소: " . $project['work_location'] . "\n";
    $prompt .= "담당자: " . $project['manager_name'] . " (" . $project['manager_position'] . ")\n";
    if (!empty($project['project_description'])) {
        $prompt .= "프로젝트 설명: " . $project['project_description'] . "\n";
    }
    $prompt .= "\n";
    
    // 공정 정보
    $prompt .= "=== 공정 정보 ===\n";
    foreach ($processes as $i => $process) {
        $prompt .= ($i + 1) . ". 공정명: " . $process['process_name'] . "\n";
        $prompt .= "   세부작업: " . $process['detail_work'] . "\n";
        $prompt .= "   작업위치: " . $process['work_position'] . "\n";
        if (!empty($process['equipment_name'])) {
            $prompt .= "   장비명: " . $process['equipment_name'] . "\n";
        }
        if (!empty($process['main_materials'])) {
            $prompt .= "   주요자재: " . $process['main_materials'] . "\n";
        }
        if (!empty($process['weather_condition'])) {
            $prompt .= "   기상조건: " . $process['weather_condition'] . "\n";
        }
        if (!empty($process['work_time'])) {
            $prompt .= "   작업시간대: " . $process['work_time'] . "\n";
        }
        $prompt .= "   고소작업: " . ($process['height_work'] === 'yes' ? '예' : '아니오') . "\n";
        $prompt .= "   밀폐공간: " . ($process['confined_space'] === 'yes' ? '예' : '아니오') . "\n";
        $prompt .= "\n";
    }
    
    $prompt .= "=== 요청사항 ===\n";
    $prompt .= "위 정보를 바탕으로 다음 형식의 위험성평가를 JSON 배열로 작성해주세요:\n\n";
    $prompt .= "[\n";
    $prompt .= "  {\n";
    $prompt .= "    \"process_name\": \"공정명\",\n";
    $prompt .= "    \"detail_work\": \"세부작업\",\n";
    $prompt .= "    \"classification\": \"분류(요인) - 예: 추락, 충돌, 화재, 화학물질 등\",\n";
    $prompt .= "    \"cause\": \"원인 - 구체적인 위험 발생 원인\",\n";
    $prompt .= "    \"hazard_factors\": \"유해위험요인 - 상세한 위험 요소 설명\",\n";
    $prompt .= "    \"current_measures\": \"현재 안전보건조치 - 기존에 적용된 안전 조치\",\n";
    $prompt .= "    \"risk_level\": \"위험성척도 - 상, 중, 하 중 하나\",\n";
    $prompt .= "    \"reduction_measures\": \"위험성 감소대책 - 추가 안전 조치 제안\",\n";
    $prompt .= "    \"responsible_person\": \"담당자 - 해당 안전조치 담당자\"\n";
    $prompt .= "  }\n";
    $prompt .= "]\n\n";
    $prompt .= "각 공정별로 3-5개의 주요 위험요소를 분석하여 총 " . (count($processes) * 3) . "개 이상의 위험성평가 항목을 생성해주세요.\n";
    $prompt .= "위험성척도는 발생가능성과 중대성을 고려하여 '상', '중', '하'로 분류해주세요.\n";
    $prompt .= "한국의 산업안전보건법 기준에 맞춰 작성해주세요.\n";
    $prompt .= "JSON 형식으로만 응답하고, 다른 설명은 포함하지 마세요.";
    
    return $prompt;
}

/**
 * 위험성 레벨에 따른 색상 클래스 반환
 */
function getRiskLevelClass($level) {
    switch ($level) {
        case '상': return 'risk-high';
        case '중': return 'risk-medium';
        case '하': return 'risk-low';
        default: return 'risk-unknown';
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3단계: AI 위험성평가 분석 - YourGot AI 위험성평가</title>
    <link rel="stylesheet" href="../../assets/css/figma-design.css">
    
    <style>
        .page-wrapper { background-color: var(--neutral-100); min-height: 100vh; }
        .step-container { max-width: 1000px; margin: 0 auto; padding: var(--spacing-10) var(--spacing-6); }
        .step-header { text-align: center; margin-bottom: var(--spacing-10); }
        
        .step-progress { display: flex; justify-content: center; align-items: center; margin-bottom: var(--spacing-8); position: relative; }
        .progress-line { position: absolute; height: 2px; background-color: var(--neutral-300); width: 60%; top: 50%; left: 20%; transform: translateY(-50%); z-index: 1; }
        .progress-line-active { position: absolute; height: 2px; background-color: var(--primary-500); width: 0%; top: 50%; left: 20%; transform: translateY(-50%); z-index: 2; transition: width 0.5s ease-in-out; }
        .progress-step { display: flex; flex-direction: column; align-items: center; position: relative; z-index: 3; width: 120px; }
        .progress-number { width: 32px; height: 32px; border-radius: 50%; border: 2px solid var(--neutral-300); background-color: var(--neutral-50); color: var(--neutral-500); display: flex; align-items: center; justify-content: center; font-weight: var(--font-weight-bold); font-size: 14px; transition: all 0.3s; margin-bottom: var(--spacing-2); }
        .progress-step.active .progress-number { background-color: var(--primary-500); border-color: var(--primary-500); color: var(--neutral-50); }
        .progress-step.completed .progress-number { background-color: var(--success); border-color: var(--success); color: var(--neutral-50); }
        .progress-text { font-size: 14px; color: var(--neutral-700); font-weight: 500; }
        .progress-step.active .progress-text, .progress-step.completed .progress-text { color: var(--neutral-900); }
        
        .ai-analysis-section {
            background: linear-gradient(135deg, var(--primary-500), var(--secondary-500));
            color: var(--neutral-50);
            border-radius: var(--radius-lg);
            padding: var(--spacing-10);
            margin-bottom: var(--spacing-8);
            text-align: center;
            box-shadow: var(--shadow-md);
        }
        
        .analysis-item {
            background-color: var(--neutral-50);
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-lg);
            padding: var(--spacing-6);
            margin-bottom: var(--spacing-6);
            box-shadow: var(--shadow-sm);
        }
        
        .risk-level-badge {
            padding: var(--spacing-1) var(--spacing-3);
            border-radius: var(--radius-md);
            font-size: 12px;
            font-weight: var(--font-weight-semibold);
            border: 1px solid transparent;
        }
        
        .risk-high { background-color: #fee2e2; color: #b91c1c; border-color: #fecaca; }
        .risk-medium { background-color: #fffbeb; color: #b45309; border-color: #fde68a; }
        .risk-low { background-color: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        
        .edit-form {
            background-color: var(--neutral-100);
            padding: var(--spacing-6);
            border-radius: var(--radius-md);
            margin-top: var(--spacing-6);
            border: 1px solid var(--neutral-300);
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <!-- Header -->
        <header class="header" style="background-color: var(--neutral-50); border-bottom: 1px solid var(--neutral-300);">
            <div class="container">
                <div class="header-logo">
                    <a href="../../index.php" style="color: inherit; text-decoration: none;">
                        YourGot
                    </a>
                </div>
                
                <nav class="header-nav">
                    <span class="header-nav-item" style="color: var(--primary-500);">
                        위험성평가 진행 중
                    </span>
                </nav>
                
                <div class="header-actions">
                    <span class="text-body-sm" style="color: var(--neutral-700);">
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
                    <div class="progress-step completed" data-step="1"><div class="progress-number">✓</div><div class="progress-text">기본정보</div></div>
                    <div class="progress-step completed" data-step="2"><div class="progress-number">✓</div><div class="progress-text">공정정보</div></div>
                    <div class="progress-step active" data-step="3"><div class="progress-number">3</div><div class="progress-text">AI분석</div></div>
                    <div class="progress-step" data-step="4"><div class="progress-number">4</div><div class="progress-text">보고서</div></div>
                    <div class="progress-line"></div><div class="progress-line-active" id="progress-line-active"></div>
                </div>
                <h1 class="text-h2">AI 위험성평가 분석</h1>
                <p class="text-body text-secondary">AI가 입력된 정보를 분석하여 위험성평가를 자동으로 생성합니다. 결과를 검토하고 필요시 수정하세요.</p>
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

            <!-- AI 분석 섹션 -->
            <div class="ai-analysis-section">
                <?php if ($is_analyzing): ?>
                    <div class="ai-status">
                        <div class="loading-spinner"></div>
                        <span class="text-h4">AI가 위험성평가를 분석하고 있습니다...</span>
                    </div>
                    <p class="text-body">잠시만 기다려주세요. 입력하신 공정정보를 바탕으로 AI가 종합적인 위험성평가를 생성중입니다.</p>
                <?php elseif (empty($existing_analysis)): ?>
                    <div class="ai-status">
                        <span style="font-size: 32px;">🤖</span>
                        <span class="text-h4">AI 위험성평가 분석 준비 완료</span>
                    </div>
                    <p class="text-body" style="margin-bottom: var(--spacing-6);">
                        <?= count($processes) ?>개의 공정정보가 준비되었습니다. AI가 각 공정의 위험요소를 분석하여 포괄적인 위험성평가를 생성합니다.
                    </p>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="start_ai_analysis">
                        <button type="submit" class="btn btn-primary btn-lg" style="background-color: var(--neutral-50); color: var(--primary-500);">
                            🚀 AI 분석 시작하기
                        </button>
                    </form>
                <?php else: ?>
                    <div class="ai-status">
                        <span style="font-size: 32px;">✅</span>
                        <span class="text-h4">AI 분석 완료</span>
                    </div>
                    <p class="text-body">총 <?= count($existing_analysis) ?>개의 위험성평가 항목이 생성되었습니다. 아래에서 결과를 확인하고 필요시 수정하세요.</p>
                <?php endif; ?>
            </div>

            <!-- 분석 결과 표시 -->
            <?php if (!empty($existing_analysis)): ?>
                <div class="analysis-results">
                    <h2 class="text-h3" style="margin-bottom: var(--spacing-6);">AI 위험성평가 결과</h2>
                    
                    <?php foreach ($existing_analysis as $index => $analysis): ?>
                        <div class="analysis-item" id="analysis-<?= $index ?>">
                            <div class="analysis-header">
                                <div>
                                    <h3 class="analysis-title">
                                        <?= escape($analysis['process_name'] ?? '알 수 없는 공정') ?> - <?= escape($analysis['detail_work'] ?? '알 수 없는 세부작업') ?>
                                    </h3>
                                </div>
                                <div class="risk-level-badge <?= getRiskLevelClass($analysis['risk_level'] ?? '') ?>">
                                    위험도: <?= escape($analysis['risk_level'] ?? '미정') ?>
                                </div>
                            </div>
                            
                            <div class="analysis-grid">
                                <div class="analysis-field">
                                    <div class="analysis-field-label">분류(요인)</div>
                                    <div class="analysis-field-value"><?= escape($analysis['classification'] ?? '') ?></div>
                                </div>
                                <div class="analysis-field">
                                    <div class="analysis-field-label">원인</div>
                                    <div class="analysis-field-value"><?= escape($analysis['cause'] ?? '') ?></div>
                                </div>
                                <div class="analysis-field">
                                    <div class="analysis-field-label">유해위험요인</div>
                                    <div class="analysis-field-value"><?= escape($analysis['hazard_factors'] ?? '') ?></div>
                                </div>
                                <div class="analysis-field">
                                    <div class="analysis-field-label">현재 안전보건조치</div>
                                    <div class="analysis-field-value"><?= escape($analysis['current_measures'] ?? '') ?></div>
                                </div>
                                <div class="analysis-field">
                                    <div class="analysis-field-label">위험성 감소대책</div>
                                    <div class="analysis-field-value"><?= escape($analysis['reduction_measures'] ?? '') ?></div>
                                </div>
                                <div class="analysis-field">
                                    <div class="analysis-field-label">담당자</div>
                                    <div class="analysis-field-value"><?= escape($analysis['responsible_person'] ?? '') ?></div>
                                </div>
                            </div>
                            
                            <!-- 수정 버튼 -->
                            <div style="text-align: center; margin-top: var(--spacing-6);">
                                <button type="button" class="btn btn-outline btn-sm" onclick="toggleEditForm(<?= $index ?>)">
                                    수정하기
                                </button>
                            </div>
                            
                            <!-- 수정 폼 (기본적으로 숨김) -->
                            <div class="edit-form" id="edit-form-<?= $index ?>" style="display: none;">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_analysis">
                                    <input type="hidden" name="analysis_index" value="<?= $index ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label" for="classification-<?= $index ?>">분류(요인)</label>
                                            <input type="text" class="form-control" id="classification-<?= $index ?>" 
                                                   name="classification" value="<?= escape($analysis['classification'] ?? '') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="cause-<?= $index ?>">원인</label>
                                            <input type="text" class="form-control" id="cause-<?= $index ?>" 
                                                   name="cause" value="<?= escape($analysis['cause'] ?? '') ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row single">
                                        <div class="form-group">
                                            <label class="form-label" for="hazard-factor-<?= $index ?>">유해위험요인</label>
                                            <textarea class="form-control" id="hazard-factor-<?= $index ?>" 
                                                      name="hazard_factor" rows="2"><?= escape($analysis['hazard_factors'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row single">
                                        <div class="form-group">
                                            <label class="form-label" for="current-measures-<?= $index ?>">현재 안전보건조치</label>
                                            <textarea class="form-control" id="current-measures-<?= $index ?>" 
                                                      name="current_measures" rows="2"><?= escape($analysis['current_measures'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label" for="risk-level-<?= $index ?>">위험성척도</label>
                                            <select class="form-control form-select" id="risk-level-<?= $index ?>" name="risk_level">
                                                <option value="상" <?= ($analysis['risk_level'] ?? '') === '상' ? 'selected' : '' ?>>상</option>
                                                <option value="중" <?= ($analysis['risk_level'] ?? '') === '중' ? 'selected' : '' ?>>중</option>
                                                <option value="하" <?= ($analysis['risk_level'] ?? '') === '하' ? 'selected' : '' ?>>하</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="responsible-person-<?= $index ?>">담당자</label>
                                            <input type="text" class="form-control" id="responsible-person-<?= $index ?>" 
                                                   name="responsible_person" value="<?= escape($analysis['responsible_person'] ?? '') ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row single">
                                        <div class="form-group">
                                            <label class="form-label" for="reduction-measures-<?= $index ?>">위험성 감소대책</label>
                                            <textarea class="form-control" id="reduction-measures-<?= $index ?>" 
                                                      name="reduction_measures" rows="3"><?= escape($analysis['reduction_measures'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div style="text-align: center; margin-top: var(--spacing-4);">
                                        <button type="submit" class="btn btn-primary btn-sm">저장</button>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="toggleEditForm(<?= $index ?>)">취소</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 다음 단계 진행 -->
            <div class="form-actions" style="background-color: transparent; border: none; padding: var(--spacing-8) 0;">
                <a href="../step2/process_info.php" class="btn btn-outline">
                    ← 이전 단계
                </a>
                
                <form method="POST" action="" style="margin:0;">
                    <input type="hidden" name="action" value="next_step">
                    <button type="submit" class="btn btn-primary btn-lg" <?= empty($existing_analysis) ? 'disabled' : '' ?>>
                        보고서 생성 →
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activeStep = 3;
            const totalSteps = 4;
            const progressLine = document.getElementById('progress-line-active');
            
            if (progressLine) {
                const percentage = (activeStep - 1) / (totalSteps - 1) * 66;
                progressLine.style.width = percentage + '%';
            }
        });
        
        function toggleEditForm(index) {
            const editForm = document.getElementById('edit-form-' + index);
            if (editForm.style.display === 'none' || editForm.style.display === '') {
                editForm.style.display = 'block';
                editForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                editForm.style.display = 'none';
            }
        }
        
        // 위험도 레벨에 따른 실시간 색상 변경
        document.addEventListener('DOMContentLoaded', function() {
            const riskSelects = document.querySelectorAll('select[name="risk_level"]');
            
            riskSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const item = this.closest('.analysis-item');
                    if (!item) return;

                    const badge = item.querySelector('.risk-level-badge');
                    if (badge) {
                        // 기존 클래스 제거
                        badge.classList.remove('risk-high', 'risk-medium', 'risk-low', 'risk-unknown');
                        
                        // 새 클래스 추가
                        let riskClass = 'risk-unknown';
                        switch (this.value) {
                            case '상': riskClass = 'risk-high'; break;
                            case '중': riskClass = 'risk-medium'; break;
                            case '하': riskClass = 'risk-low'; break;
                        }
                        badge.classList.add(riskClass);
                        
                        badge.textContent = '위험도: ' + this.value;
                    }
                });
            });
        });
    </script>
</body>
</html> 