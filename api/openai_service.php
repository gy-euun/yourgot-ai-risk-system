<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

class OpenAIService {
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $model;
    private $max_tokens;
    private $temperature;
    
    public function __construct() {
        $this->api_key = OPENAI_API_KEY;
        $this->model = 'gpt-4';
        $this->max_tokens = 4000;
        $this->temperature = 0.7;
        
        // 데이터베이스에서 설정 값 가져오기
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('openai_model', 'max_tokens', 'temperature')");
            $stmt->execute();
            $settings = $stmt->fetchAll();
            
            foreach ($settings as $setting) {
                switch ($setting['setting_key']) {
                    case 'openai_model':
                        $this->model = $setting['setting_value'];
                        break;
                    case 'max_tokens':
                        $this->max_tokens = intval($setting['setting_value']);
                        break;
                    case 'temperature':
                        $this->temperature = floatval($setting['setting_value']);
                        break;
                }
            }
        } catch (Exception $e) {
            writeLog("OpenAI 설정 로딩 실패: " . $e->getMessage(), 'ERROR');
        }
    }
    
    public function analyzeRisk($project_data, $processes) {
        // OpenAI API 키가 설정되지 않은 경우 폴백 분석 사용
        if (empty($this->api_key) || $this->api_key === 'your_openai_api_key_here') {
            error_log("[" . date('Y-m-d H:i:s') . "] [INFO] OpenAI API 키가 설정되지 않음. 폴백 분석 사용.\n", 3, "../logs/system.log");
            return $this->getFallbackAnalysis($processes);
        }
        
        try {
            $prompt = $this->buildRiskAssessmentPrompt($project_data, $processes);
            
            $data = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => '당신은 산업안전보건법을 준수하는 전문 위험성평가 분석가입니다. 한국의 산업안전 기준에 따라 정확하고 체계적인 위험성평가를 수행해주세요.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $this->max_tokens,
                'temperature' => $this->temperature
            ];
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->api_key
                    ],
                    'content' => json_encode($data)
                ]
            ]);
            
            $response = file_get_contents($this->api_url, false, $context);
            
            if ($response === false) {
                throw new Exception('OpenAI API 요청 실패');
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['error'])) {
                throw new Exception('OpenAI API 오류: ' . $result['error']['message']);
            }
            
            $analysis_text = $result['choices'][0]['message']['content'];
            return $this->parseAnalysisResult($analysis_text);
            
        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] [ERROR] OpenAI API 오류: " . $e->getMessage() . "\n", 3, "../logs/system.log");
            return $this->getFallbackAnalysis($processes);
        }
    }
    
    private function buildRiskAssessmentPrompt($project_data, $processes) {
        $prompt = "다음 프로젝트의 위험성평가를 실시해주세요.\n\n";
        $prompt .= "【프로젝트 정보】\n";
        $prompt .= "- 프로젝트명: {$project_data['project_name']}\n";
        $prompt .= "- 작업장소: {$project_data['work_location']}\n";
        $prompt .= "- 회사명: {$project_data['company_name']}\n";
        $prompt .= "- 관리자: {$project_data['manager_name']} ({$project_data['manager_position']})\n\n";
        
        $prompt .= "【분석할 공정들】\n";
        foreach ($processes as $i => $process) {
            $prompt .= "공정 " . ($i + 1) . ":\n";
            $prompt .= "- 공정명: {$process['process_name']}\n";
            $prompt .= "- 세부작업: {$process['detailed_work']}\n";
            $prompt .= "- 작업위치: {$process['work_position']}\n";
            $prompt .= "- 장비명: {$process['equipment_name']}\n";
            $prompt .= "- 주요자재: {$process['main_materials']}\n";
            $prompt .= "- 기상조건: {$process['weather_conditions']}\n";
            $prompt .= "- 작업시간: {$process['work_time']}\n";
            $prompt .= "- 고소작업: " . ($process['height_work'] === 'yes' ? '예' : '아니오') . "\n";
            $prompt .= "- 밀폐공간: " . ($process['confined_space'] === 'yes' ? '예' : '아니오') . "\n\n";
        }
        
        $prompt .= "【요구사항】\n";
        $prompt .= "각 공정에 대해 실제 위험성평가표 양식에 맞춰 다음 항목들을 분석해주세요:\n\n";
        $prompt .= "1. 분류: 위험요인의 유형 분류 (예: 기계적 위험, 전기적 위험, 화학적 위험, 물리적 위험 등)\n";
        $prompt .= "2. 원인: 위험이 발생하는 구체적 원인\n";
        $prompt .= "3. 유해위험요인: 구체적인 위험 요소\n";
        $prompt .= "4. 현재안전보건조치: 현재 적용되고 있는 안전조치\n";
        $prompt .= "5. 위험성 등급: 상/중/하 중 선택\n";
        $prompt .= "   - 상(4): 중대재해 위험이 높거나 발생빈도가 높은 경우\n";
        $prompt .= "   - 중(2~3): 보통 수준의 위험\n";
        $prompt .= "   - 하(1): 경미한 위험\n";
        $prompt .= "6. 감소대책: 위험을 줄이기 위한 구체적 방안\n";
        $prompt .= "7. 담당자: 안전조치 담당자 (관리자명 사용)\n\n";
        
        $prompt .= "【출력 형식】\n";
        $prompt .= "반드시 다음 JSON 형식으로만 응답해주세요:\n";
        $prompt .= "[\n";
        $prompt .= "  {\n";
        $prompt .= "    \"classification\": \"기계적 위험\",\n";
        $prompt .= "    \"cause\": \"회전체 접촉\",\n";
        $prompt .= "    \"hazard_factors\": \"톱날에 의한 절단 위험\",\n";
        $prompt .= "    \"current_measures\": \"안전덮개 설치, 개인보호구 착용\",\n";
        $prompt .= "    \"risk_level\": \"상\",\n";
        $prompt .= "    \"reduction_measures\": \"추가 안전교육 실시, 작업절차서 준수\",\n";
        $prompt .= "    \"responsible_person\": \"안전관리자\"\n";
        $prompt .= "  }\n";
        $prompt .= "]\n\n";
        $prompt .= "※ 한국어로 작성하고, 산업안전보건법 기준을 준수하여 실무에 적용 가능한 구체적인 내용으로 작성해주세요.";
        
        return $prompt;
    }
    
    private function parseAnalysisResult($analysis_text) {
        // JSON 부분만 추출
        $json_start = strpos($analysis_text, '[');
        $json_end = strrpos($analysis_text, ']');
        
        if ($json_start !== false && $json_end !== false) {
            $json_text = substr($analysis_text, $json_start, $json_end - $json_start + 1);
            $parsed = json_decode($json_text, true);
            
            if ($parsed !== null) {
                return $parsed;
            }
        }
        
        // JSON 파싱 실패 시 폴백
        error_log("[" . date('Y-m-d H:i:s') . "] [WARNING] AI 응답 파싱 실패, 원본 텍스트: " . $analysis_text . "\n", 3, "../logs/system.log");
        return $this->getFallbackAnalysis([]);
    }
    
    private function getFallbackAnalysis($processes) {
        $fallback = [];
        
        // 위험 요소 템플릿
        $risk_templates = [
            '기계적 위험' => [
                'cause' => '회전체 접촉, 끼임',
                'hazard_factors' => '절단, 타박상, 골절',
                'current_measures' => '안전덮개 설치, 개인보호구 착용',
                'risk_level' => '상',
                'reduction_measures' => '안전교육 강화, 작업절차서 준수, 정기점검'
            ],
            '추락 위험' => [
                'cause' => '고소작업, 안전난간 미설치',
                'hazard_factors' => '추락으로 인한 중상, 사망',
                'current_measures' => '안전벨트 착용, 안전난간 설치',
                'risk_level' => '상',
                'reduction_measures' => '추락방지시설 보강, 안전교육 실시'
            ],
            '화재 위험' => [
                'cause' => '전기설비 이상, 가연물질 취급',
                'hazard_factors' => '화재, 폭발, 화상',
                'current_measures' => '소화기 비치, 화기 금지',
                'risk_level' => '중',
                'reduction_measures' => '소방시설 점검, 화재예방교육'
            ],
            '전기 위험' => [
                'cause' => '전선 노출, 누전',
                'hazard_factors' => '감전, 화재',
                'current_measures' => '누전차단기 설치, 절연보호구 착용',
                'risk_level' => '중',
                'reduction_measures' => '전기설비 정기점검, 안전교육'
            ]
        ];
        
        $risk_types = array_keys($risk_templates);
        
        foreach ($processes as $i => $process) {
            // 공정별로 2-3개의 위험요소 생성
            $process_risks = min(3, count($risk_types));
            
            for ($j = 0; $j < $process_risks; $j++) {
                $risk_type = $risk_types[($i + $j) % count($risk_types)];
                $template = $risk_templates[$risk_type];
                
                $fallback[] = [
                    'process_name' => $process['process_name'] ?? '공정 ' . ($i + 1),
                    'detail_work' => $process['detailed_work'] ?? '세부작업',
                    'classification' => $risk_type,
                    'cause' => $template['cause'],
                    'hazard_factors' => $process['process_name'] . ' 작업 중 ' . $template['hazard_factors'],
                    'current_measures' => $template['current_measures'],
                    'risk_level' => $template['risk_level'],
                    'reduction_measures' => $template['reduction_measures'],
                    'responsible_person' => '안전관리자'
                ];
            }
        }
        
        return $fallback;
    }
    
    /**
     * 위험 요인 템플릿 검색
     */
    public function getRiskTemplates($process_type, $work_type = null) {
        try {
            $pdo = getDBConnection();
            
            $sql = "SELECT * FROM risk_templates WHERE process_type LIKE ?";
            $params = ["%{$process_type}%"];
            
            if ($work_type) {
                $sql .= " AND work_type LIKE ?";
                $params[] = "%{$work_type}%";
            }
            
            $sql .= " ORDER BY id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            writeLog("위험 요인 템플릿 조회 실패: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * AI 분석 통계 조회
     */
    public function getAIStats($project_id = null) {
        try {
            $pdo = getDBConnection();
            
            $sql = "
                SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                    AVG(processing_time) as avg_processing_time,
                    SUM(token_usage) as total_tokens,
                    SUM(api_cost) as total_cost
                FROM ai_analysis_logs
            ";
            
            if ($project_id) {
                $sql .= " WHERE project_id = ?";
                $params = [$project_id];
            } else {
                $params = [];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            writeLog("AI 통계 조회 실패: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
}

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['project_data']) || !isset($input['processes'])) {
            throw new Exception('필수 데이터가 누락되었습니다.');
        }
        
        $openai = new OpenAIService();
        $analysis = $openai->analyzeRisk($input['project_data'], $input['processes']);
        
        echo json_encode([
            'success' => true,
            'data' => $analysis
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?> 