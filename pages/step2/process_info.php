<?php
/**
 * 2단계: 공정정보 및 작업조건 입력
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// 로그인 확인
requireLogin();

$error = '';
$success = '';

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

// 기존 공정정보 조회 (프로젝트 테이블에서 JSON으로 저장된 데이터 사용)
$processes = json_decode($project['processes'] ?? '[]', true);

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_process') {
        // 새 공정 추가
        $process_name = trim($_POST['process_name'] ?? '');
        $detail_work = trim($_POST['detail_work'] ?? '');
        $work_position = trim($_POST['work_position'] ?? '');
        $equipment_name = trim($_POST['equipment_name'] ?? '');
        $main_materials = trim($_POST['main_materials'] ?? '');
        $weather_condition = $_POST['weather_condition'] ?? '';
        $work_time = $_POST['work_time'] ?? '';
        $height_work = $_POST['height_work'] ?? 'no';
        $confined_space = $_POST['confined_space'] ?? 'no';
        
        // 유효성 검사
        if (empty($process_name)) {
            $error = '공정명을 입력해주세요.';
        } elseif (empty($detail_work)) {
            $error = '세부작업을 입력해주세요.';
        } elseif (empty($work_position)) {
            $error = '작업위치를 입력해주세요.';
        } else {
            try {
                // 새 공정 데이터 생성
                $new_process = [
                    'id' => count($processes) + 1,
                    'process_name' => $process_name,
                    'detail_work' => $detail_work,
                    'work_position' => $work_position,
                    'equipment_name' => $equipment_name,
                    'main_materials' => $main_materials,
                    'weather_condition' => $weather_condition,
                    'work_time' => $work_time,
                    'height_work' => $height_work,
                    'confined_space' => $confined_space,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $processes[] = $new_process;
                
                // 프로젝트에 공정 정보 저장
                $stmt = $pdo->prepare("UPDATE projects SET processes = ? WHERE id = ?");
                $stmt->execute([json_encode($processes), $project_id]);
                
                $success = '공정정보가 추가되었습니다.';
                
                // 활동 로그 기록
                logUserActivity($user['id'], 'add_process', 'project', $project_id, [
                    'process_name' => $process_name
                ]);
                
            } catch (Exception $e) {
                writeLog("공정정보 추가 오류: " . $e->getMessage(), 'ERROR');
                $error = '공정정보 추가 중 오류가 발생했습니다.';
            }
        }
    } elseif ($action === 'delete_process') {
        // 공정 삭제
        $process_id = $_POST['process_id'] ?? 0;
        
        try {
            // 공정 ID로 삭제
            $processes = array_filter($processes, function($process) use ($process_id) {
                return $process['id'] != $process_id;
            });
            
            // ID 재정렬
            $processes = array_values($processes);
            
            // 프로젝트에 공정 정보 업데이트
            $stmt = $pdo->prepare("UPDATE projects SET processes = ? WHERE id = ?");
            $stmt->execute([json_encode($processes), $project_id]);
            
            $success = '공정정보가 삭제되었습니다.';
            
        } catch (Exception $e) {
            writeLog("공정정보 삭제 오류: " . $e->getMessage(), 'ERROR');
            $error = '공정정보 삭제 중 오류가 발생했습니다.';
        }
    } elseif ($action === 'next_step') {
        // 다음 단계로 진행
        if (empty($processes)) {
            $error = '최소 하나 이상의 공정정보를 입력해주세요.';
        } else {
            // 프로젝트 상태 업데이트
            $stmt = $pdo->prepare("UPDATE projects SET status = 'step2' WHERE id = ?");
            $stmt->execute([$project_id]);
            
            header('Location: ../step3/ai_analysis.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2단계: 공정정보 및 작업조건 - YourGot AI 위험성평가</title>
    <link rel="stylesheet" href="../../assets/css/figma-design.css">
    
    <style>
        body {
            background-color: var(--neutral-100);
        }

        .step-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: var(--spacing-10) var(--spacing-6);
        }
        
        .step-header {
            text-align: center;
            margin-bottom: var(--spacing-10);
        }
        
        .step-progress { display: flex; justify-content: center; align-items: center; margin-bottom: var(--spacing-8); position: relative; }
        .progress-line { position: absolute; height: 2px; background-color: var(--neutral-300); width: 60%; top: 50%; left: 20%; transform: translateY(-50%); z-index: 1; }
        .progress-line-active { position: absolute; height: 2px; background-color: var(--primary-500); width: 0%; top: 50%; left: 20%; transform: translateY(-50%); z-index: 2; transition: width 0.5s ease-in-out; }
        .progress-step { display: flex; flex-direction: column; align-items: center; position: relative; z-index: 3; width: 120px; }
        .progress-number { width: 32px; height: 32px; border-radius: 50%; border: 2px solid var(--neutral-300); background-color: var(--neutral-50); color: var(--neutral-500); display: flex; align-items: center; justify-content: center; font-weight: var(--font-weight-bold); font-size: 14px; transition: all 0.3s; margin-bottom: var(--spacing-2); }
        .progress-step.active .progress-number { background-color: var(--primary-500); border-color: var(--primary-500); color: var(--neutral-50); }
        .progress-step.completed .progress-number { background-color: var(--success); border-color: var(--success); color: var(--neutral-50); }
        .progress-text { font-size: 14px; color: var(--neutral-700); font-weight: 500; }
        .progress-step.active .progress-text, .progress-step.completed .progress-text { color: var(--neutral-900); }


        .content-section {
            background-color: var(--neutral-50);
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-lg);
            padding: var(--spacing-8);
            box-shadow: var(--shadow-sm);
            margin-bottom: var(--spacing-8);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-6);
            padding-bottom: var(--spacing-6);
            border-bottom: 1px solid var(--neutral-300);
        }

        .section-title {
            font-size: 18px;
            font-weight: var(--font-weight-semibold);
            color: var(--neutral-900);
            display: flex;
            align-items: center;
        }

        .section-title svg {
            margin-right: var(--spacing-3);
            color: var(--primary-500);
        }

        .process-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .process-table th, .process-table td {
            padding: var(--spacing-3) var(--spacing-4);
            border-bottom: 1px solid var(--neutral-300);
            text-align: left;
        }
        .process-table th {
            color: var(--neutral-700);
            font-weight: 500;
        }
        .process-table td {
            color: var(--neutral-900);
        }
        .process-table tr:last-child td {
            border-bottom: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-6);
        }
        
        .form-row.single {
            grid-template-columns: 1fr;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: var(--spacing-8);
            padding-top: var(--spacing-6);
            border-top: 1px solid var(--neutral-300);
        }
    </style>
</head>

<body>
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
                <span class="text-body-sm text-white">
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
                <div class="progress-step active" data-step="2"><div class="progress-number">2</div><div class="progress-text">공정정보</div></div>
                <div class="progress-step" data-step="3"><div class="progress-number">3</div><div class="progress-text">AI분석</div></div>
                <div class="progress-step" data-step="4"><div class="progress-number">4</div><div class="progress-text">보고서</div></div>
                <div class="progress-line"></div><div class="progress-line-active" id="progress-line-active"></div>
            </div>
            <h1 class="text-h2">공정정보 및 작업조건 입력</h1>
            <p class="text-body text-secondary">AI가 분석할 작업 공정의 상세 정보를 추가해주세요. 정보가 상세할수록 분석이 정확해집니다.</p>
        </div>

        <!-- 에러/성공 메시지 -->
        <?php if ($error): ?><div class="alert alert-danger" style="margin-bottom: var(--spacing-6);"><?= escape($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success" style="margin-bottom: var(--spacing-6);"><?= escape($success) ?></div><?php endif; ?>

        <!-- 추가된 공정 목록 -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                     <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    추가된 공정 목록 (<?= count($processes) ?>개)
                </h2>
                <button type="button" class="btn btn-primary btn-sm" onclick="toggleAddForm()">
                    + 새 공정 추가하기
                </button>
            </div>

            <?php if (!empty($processes)): ?>
                <table class="process-table">
                    <thead>
                        <tr>
                            <th>공정명</th><th>세부작업</th><th>작업위치</th><th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processes as $proc): ?>
                        <tr>
                            <td><?= escape($proc['process_name']) ?></td>
                            <td><?= escape($proc['detail_work']) ?></td>
                            <td><?= escape($proc['work_position']) ?></td>
                            <td>
                                <form method="POST" action="" onsubmit="return confirm('정말로 이 공정을 삭제하시겠습니까?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_process">
                                    <input type="hidden" name="process_id" value="<?= $proc['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 2px 8px; font-size: 12px;">삭제</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-body text-secondary text-center" style="padding: var(--spacing-8) 0;">아직 추가된 공정이 없습니다. '새 공정 추가하기' 버튼을 눌러 시작하세요.</p>
            <?php endif; ?>
        </div>

        <!-- 새 공정 추가 폼 (기본 숨김) -->
        <div class="content-section" id="add-process-form" style="display: none;">
            <h2 class="section-title" style="margin-bottom: var(--spacing-6); padding-bottom: var(--spacing-6); border-bottom: 1px solid var(--neutral-300);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                새 공정 추가
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_process">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="process_name">
                            공정명 <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="text" class="form-control" id="process_name" name="process_name" 
                               placeholder="예: 철근 조립, 콘크리트 타설, 도장 작업" required>
                        <div class="form-text">수행할 공정의 명칭을 입력하세요.</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="detail_work">
                            세부작업 <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="text" class="form-control" id="detail_work" name="detail_work" 
                               placeholder="예: 기둥 철근 배근, 슬래브 콘크리트 타설" required>
                        <div class="form-text">공정의 구체적인 세부작업 내용을 입력하세요.</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="work_position">
                            작업위치 <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="text" class="form-control" id="work_position" name="work_position" 
                               placeholder="예: 지하 1층, 3층 슬래브, 외부 발코니" required>
                        <div class="form-text">작업이 이루어지는 구체적인 위치를 입력하세요.</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="equipment_name">
                            장비명
                        </label>
                        <input type="text" class="form-control" id="equipment_name" name="equipment_name" 
                               placeholder="예: 타워크레인, 콘크리트펌프, 용접기">
                        <div class="form-text">사용하는 주요 장비나 기계를 입력하세요.</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="main_materials">
                            주요자재
                        </label>
                        <input type="text" class="form-control" id="main_materials" name="main_materials" 
                               placeholder="예: 철근, 콘크리트, 목재, 화학물질">
                        <div class="form-text">작업에 사용되는 주요 자재나 물질을 입력하세요.</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="weather_condition">
                            기상조건
                        </label>
                        <select class="form-control form-select" id="weather_condition" name="weather_condition">
                            <option value="">선택하세요</option>
                            <option value="맑음">맑음</option>
                            <option value="흐림">흐림</option>
                            <option value="비">비</option>
                            <option value="바람">바람</option>
                            <option value="추위">추위</option>
                            <option value="더위">더위</option>
                            <option value="실내">실내 작업</option>
                        </select>
                        <div class="form-text">작업 시의 기상조건을 선택하세요.</div>
                    </div>
                </div>

                <div class="form-row triple">
                    <div class="form-group">
                        <label class="form-label" for="work_time">
                            작업시간대
                        </label>
                        <select class="form-control form-select" id="work_time" name="work_time">
                            <option value="">선택하세요</option>
                            <option value="주간">주간 (08:00-18:00)</option>
                            <option value="야간">야간 (18:00-08:00)</option>
                            <option value="24시간">24시간 연속</option>
                            <option value="교대">교대 근무</option>
                        </select>
                        <div class="form-text">작업이 이루어지는 시간대를 선택하세요.</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="height_work">
                            고소작업 여부
                        </label>
                        <select class="form-control form-select" id="height_work" name="height_work">
                            <option value="no">아니오</option>
                            <option value="yes">예 (2m 이상)</option>
                        </select>
                        <div class="form-text">2m 이상 높이에서의 작업 여부를 선택하세요.</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confined_space">
                            밀폐공간 여부
                        </label>
                        <select class="form-control form-select" id="confined_space" name="confined_space">
                            <option value="no">아니오</option>
                            <option value="yes">예</option>
                        </select>
                        <div class="form-text">밀폐공간에서의 작업 여부를 선택하세요.</div>
                    </div>
                </div>
                
                <div class="form-actions" style="border-top:none; padding-top:0;">
                    <button type="button" class="btn btn-secondary" onclick="toggleAddForm()">취소</button>
                    <button type="submit" class="btn btn-primary">공정 추가</button>
                </div>
            </form>
        </div>

        <!-- 다음 단계로 이동 -->
        <div class="form-actions">
            <a href="../step1/project_info.php" class="btn btn-outline">
                ← 이전 단계
            </a>
            <form method="POST" action="" style="margin:0;">
                <input type="hidden" name="action" value="next_step">
                <button type="submit" class="btn btn-primary btn-lg" <?= empty($processes) ? 'disabled' : '' ?>>
                    AI 분석 시작하기 →
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activeStep = 2; 
            const totalSteps = 4;
            const progressLine = document.getElementById('progress-line-active');
            
            if (progressLine) {
                const percentage = (activeStep - 1) / (totalSteps - 1) * 66; // 33% per step
                progressLine.style.width = percentage + '%';
            }
        });

        function toggleAddForm() {
            const form = document.getElementById('add-process-form');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html> 