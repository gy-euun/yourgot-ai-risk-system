<?php
/**
 * 4단계: 위험성평가 보고서 출력
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

// 공정정보 및 AI 분석 결과 확인
$processes = json_decode($project['processes'] ?? '[]', true);
$analysis_results = json_decode($project['ai_analysis'] ?? '[]', true);

if (empty($processes) || empty($analysis_results)) {
    $_SESSION['error'] = 'AI 분석을 먼저 완료해주세요.';
    header('Location: ../step3/ai_analysis.php');
    exit;
}

// 위험성 통계 계산
$risk_statistics = [
    '상' => 0,
    '중' => 0,
    '하' => 0
];

foreach ($analysis_results as $analysis) {
    $risk_level = $analysis['risk_level'] ?? '';
    if (isset($risk_statistics[$risk_level])) {
        $risk_statistics[$risk_level]++;
    }
}

$total_risks = array_sum($risk_statistics);

// 폼 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'complete_project') {
        // 프로젝트 완료 처리
        try {
            $stmt = $pdo->prepare("UPDATE projects SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$project_id]);
            
            // 활동 로그 기록
            logUserActivity($user['id'], 'complete_project', 'project', $project_id, [
                'total_risks' => $total_risks,
                'high_risks' => $risk_statistics['상'],
                'medium_risks' => $risk_statistics['중'],
                'low_risks' => $risk_statistics['하']
            ]);
            
            $success = '위험성평가 프로젝트가 완료되었습니다.';
            
        } catch (Exception $e) {
            writeLog("프로젝트 완료 처리 오류: " . $e->getMessage(), 'ERROR');
            $error = '프로젝트 완료 처리 중 오류가 발생했습니다.';
        }
    }
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

/**
 * 위험성 레벨에 따른 한글 이름 반환 (로컬 함수)
 */
function getLocalRiskLevelName($level) {
    switch ($level) {
        case '상': return '높음';
        case '중': return '보통';
        case '하': return '낮음';
        default: return '미정';
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>위험성평가표 - YourGot</title>
    <link rel="stylesheet" href="../../assets/css/figma-design.css">
    <style>
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border: 2px solid #000;
            padding: 15px;
        }
        
        .report-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .report-info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .risk-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        
        .risk-table th,
        .risk-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        
        .risk-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .main-header {
            background-color: #e6e6e6;
        }
        
        .sub-header {
            background-color: #f5f5f5;
        }
        
        .process-group {
            background-color: #f8f8f8;
        }
        
        .risk-high { background-color: #ffebee; color: #c62828; }
        .risk-medium { background-color: #fff3e0; color: #ef6c00; }
        .risk-low { background-color: #e8f5e8; color: #2e7d32; }
        
        .no-print {
            margin: 20px 0;
            text-align: center;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 10px;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        @media print {
            .no-print { display: none; }
            .report-container { box-shadow: none; }
            body { margin: 0; }
        }
        
        .risk-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 2px;
        }
        
        .description-cell {
            text-align: left;
            max-width: 200px;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <div class="report-container">
        <div class="report-header">
            <div class="report-title">위험성평가표</div>
            <div class="report-info">
                <div>공정대분류: <?= htmlspecialchars($project['project_name']) ?></div>
                <div>세부분류: 1 목재가공용등급류</div>
            </div>
        </div>

        <table class="risk-table">
            <thead>
                <tr class="main-header">
                    <th rowspan="2">구분</th>
                    <th colspan="3">유해위험요인 파악</th>
                    <th rowspan="2">현재안전보건조치</th>
                    <th colspan="4">현재 위험성</th>
                    <th rowspan="2">감소대책</th>
                </tr>
                <tr class="sub-header">
                    <th>분류</th>
                    <th>원인</th>
                    <th>유해위험요인</th>
                    <th>가능성<br>(빈도)</th>
                    <th>중대성<br>(강도)</th>
                    <th>위험성</th>
                    <th>NO</th>
                    <th>세부내용</th>
                </tr>
            </thead>
            <tbody>
                <?php $row_num = 1; ?>
                <?php foreach ($analysis_results as $analysis_index => $analysis): ?>
                        <?php
                        $risk_level = $analysis['risk_level'] ?? '중';
                        $risk_class = 'risk-' . ($risk_level === '상' ? 'high' : ($risk_level === '중' ? 'medium' : 'low'));
                        
                        // 위험성 점수 매핑
                        $risk_scores = [
                            '상' => ['possibility' => 2, 'severity' => 2, 'risk' => 4],
                            '중' => ['possibility' => 2, 'severity' => 2, 'risk' => 4],
                            '하' => ['possibility' => 1, 'severity' => 2, 'risk' => 2]
                        ];
                        $scores = $risk_scores[$risk_level] ?? $risk_scores['중'];
                        ?>
                        <tr>
                            <td class="process-group"><?= $row_num ?></td>
                            <td class="description-cell"><?= htmlspecialchars($analysis['classification'] ?? '기계적 위험') ?></td>
                            <td class="description-cell"><?= htmlspecialchars($analysis['cause'] ?? '작업 중 발생') ?></td>
                            <td class="description-cell"><?= htmlspecialchars($analysis['hazard_factors'] ?? '위험요인') ?></td>
                            <td class="description-cell"><?= htmlspecialchars($analysis['current_measures'] ?? '기본 안전수칙 준수') ?></td>
                            <td>
                                <span class="risk-number <?= $risk_class ?>">
                                    <?= $scores['possibility'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="risk-number <?= $risk_class ?>">
                                    <?= $scores['severity'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="risk-number <?= $risk_class ?>">
                                    <?= $scores['risk'] ?>
                                </span>
                            </td>
                            <td><?= $row_num ?></td>
                            <td class="description-cell"><?= htmlspecialchars($analysis['reduction_measures'] ?? '추가 안전조치 필요') ?></td>
                        </tr>
                        <?php $row_num++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 30px;">
            <h3>프로젝트 개요</h3>
            <table class="risk-table">
                <tr>
                    <th style="width: 150px;">프로젝트명</th>
                    <td style="text-align: left;"><?= htmlspecialchars($project['project_name']) ?></td>
                </tr>
                <tr>
                    <th>작업장소</th>
                    <td style="text-align: left;"><?= htmlspecialchars($project['work_location']) ?></td>
                </tr>
                <tr>
                    <th>관리자</th>
                    <td style="text-align: left;"><?= htmlspecialchars($project['manager_name']) ?> (<?= htmlspecialchars($project['manager_position']) ?>)</td>
                </tr>
                <tr>
                    <th>회사명</th>
                    <td style="text-align: left;"><?= htmlspecialchars($project['company_name']) ?></td>
                </tr>
                <tr>
                    <th>평가일</th>
                    <td style="text-align: left;"><?= htmlspecialchars($project['creation_date']) ?></td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 30px;">
            <h3>위험성 분석 통계</h3>
            <table class="risk-table">
                <tr>
                    <th>위험도</th>
                    <th class="risk-high">상위험</th>
                    <th class="risk-medium">중위험</th>
                    <th class="risk-low">저위험</th>
                    <th>총계</th>
                </tr>
                <tr>
                    <td><strong>건수</strong></td>
                    <td class="risk-high"><?= $risk_statistics['상'] ?>건</td>
                    <td class="risk-medium"><?= $risk_statistics['중'] ?>건</td>
                    <td class="risk-low"><?= $risk_statistics['하'] ?>건</td>
                    <td><strong><?= array_sum($risk_statistics) ?>건</strong></td>
                </tr>
            </table>
        </div>

        <div class="no-print">
            <button onclick="window.print()" class="btn">인쇄하기</button>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="btn btn-success" onclick="return confirm('프로젝트를 완료하시겠습니까?')">
                    프로젝트 완료
                </button>
            </form>
            <a href="../step3/ai_analysis.php?project_id=<?= $project_id ?>" class="btn" style="background: #6c757d; text-decoration: none;">
                이전 단계로
            </a>
        </div>
    </div>
</body>
</html> 
