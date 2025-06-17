<?php
require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "데이터베이스 연결 성공\n<br>";
    
    // projects 테이블 생성
    $sql = "CREATE TABLE IF NOT EXISTS projects (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        project_name VARCHAR(255) NOT NULL,
        work_location VARCHAR(255) NOT NULL,
        creation_date DATE NOT NULL,
        manager_name VARCHAR(100) NOT NULL,
        manager_position VARCHAR(100) NOT NULL,
        manager_contact VARCHAR(100) NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        project_description TEXT,
        processes JSON,
        ai_analysis JSON,
        status ENUM('draft', 'in_progress', 'completed') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "projects 테이블 생성 완료\n<br>";
    
    // 샘플 데이터 삽입 (있다면 건너뛰기)
    $checkSample = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    
    if ($checkSample == 0) {
        $sampleData = "INSERT INTO projects (
            user_id, project_name, work_location, creation_date, 
            manager_name, manager_position, manager_contact, 
            company_name, project_description, status
        ) VALUES (
            1, '건설현장 안전관리 프로젝트', '서울시 강남구', '2025-06-17',
            '김안전', '안전관리자', '010-1234-5678',
            '(주)세이프빌딩', '고층 빌딩 건설 현장의 위험성 평가 및 안전관리', 'draft'
        )";
        
        $pdo->exec($sampleData);
        echo "샘플 데이터 삽입 완료\n<br>";
    }
    
    echo "데이터베이스 설정이 완료되었습니다!";
    
} catch (PDOException $e) {
    echo "오류: " . $e->getMessage();
}
?> 