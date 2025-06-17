# YourGot - AI 기반 위험성평가 지원 시스템

![YourGot Logo](assets/images/logo.png)

**AI와 함께하는 스마트한 작업장 안전관리**

YourGot은 OpenAI GPT-4를 활용한 혁신적인 위험성평가 솔루션입니다. 피그마 디자인 시스템 기반의 현대적 UI/UX와 완전한 사용자 인증 시스템을 통해 전문가 수준의 안전관리를 제공합니다.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)](https://php.net)
[![OpenAI API](https://img.shields.io/badge/OpenAI-GPT--4-green)](https://openai.com)

## ✨ 주요 특징

### 🤖 AI 기반 스마트 분석
- **GPT-4 기반 위험성평가**: 전문가 수준의 위험요인 식별 및 평가
- **실시간 안전조치 제안**: 맞춤형 위험 감소 대책 자동 생성
- **법규 준수**: 산업안전보건법 및 관련 규정 완벽 준수
- **지속적 학습**: 평가 데이터를 통한 AI 모델 최적화

### 🎨 모던한 디자인
- **피그마 기반 디자인 시스템**: 일관성 있는 UI/UX 경험
- **반응형 웹 디자인**: 모든 기기에서 최적화된 사용자 경험
- **다크모드 지원**: 사용자 환경에 맞는 테마 제공
- **접근성 준수**: WCAG 2.1 가이드라인 준수

### 🔐 완전한 사용자 관리
- **안전한 회원가입/로그인**: 비밀번호 암호화 및 보안 강화
- **역할 기반 권한 관리**: 사용자, 관리자, 매니저 등급별 접근 제어
- **프로젝트 접근 권한**: 소유자, 편집자, 열람자 권한 세분화
- **소셜 로그인 지원**: Google, Facebook 등 외부 계정 연동

### 📊 체계적인 평가 프로세스
1. **프로젝트 기본정보**: 프로젝트명, 작업장소, 담당자 정보
2. **공정정보 입력**: 상세 작업 조건 및 환경 정보
3. **AI 위험성분석**: 자동화된 위험요소 식별 및 평가
4. **전문 보고서**: PDF/Excel 형태의 완성된 평가서

## 🚀 빠른 시작

### 시스템 요구사항

- **웹서버**: Apache 2.4+ / Nginx 1.18+
- **PHP**: 7.4+ (권장: 8.0+)
- **데이터베이스**: MySQL 5.7+ / MariaDB 10.3+
- **확장모듈**: PDO, cURL, mbstring, JSON, GD
- **OpenAI API 키**: GPT-4 액세스 권한

### 설치 가이드

#### 1. 소스코드 설치
```bash
# XAMPP 환경
cd C:\xampp\htdocs
git clone https://github.com/yourgot/risk-assessment.git yourgot

# Linux/macOS 환경
cd /var/www/html
git clone https://github.com/yourgot/risk-assessment.git yourgot
```

#### 2. 환경변수 설정
```bash
# 환경변수 파일 생성
cp env.example .env

# .env 파일 편집
# 필수 설정값들을 입력하세요
```

**.env 파일 예시:**
```env
# 데이터베이스 설정
DB_HOST=localhost
DB_NAME=yourgot_risk_assessment
DB_USER=root
DB_PASSWORD=your_database_password

# OpenAI API 설정
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4-turbo-preview
OPENAI_MAX_TOKENS=2000

# 보안 설정
CSRF_SECRET_KEY=your_random_secret_key_here

# 애플리케이션 설정
APP_DEBUG=false
APP_TIMEZONE=Asia/Seoul
APP_LANGUAGE=ko

# 파일 업로드 설정
MAX_FILE_SIZE=10485760
ALLOWED_FILE_TYPES=pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif
```

#### 3. 데이터베이스 설정
```sql
-- 데이터베이스 생성
CREATE DATABASE yourgot_risk_assessment 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 기본 스키마 설치
mysql -u root -p yourgot_risk_assessment < database/schema.sql

-- 사용자 인증 스키마 설치
mysql -u root -p yourgot_risk_assessment < database/auth_schema.sql
```

#### 4. 권한 설정
```bash
# Linux/macOS
chmod -R 755 uploads/ logs/
chown -R www-data:www-data uploads/ logs/

# Windows (XAMPP)
# 폴더 속성에서 쓰기 권한 부여
```

#### 5. 웹서버 설정

**Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# UTF-8 인코딩 설정
AddDefaultCharset UTF-8

# 보안 헤더
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
```

**Nginx**
```nginx
server {
    listen 80;
    server_name yourgot.local;
    root /var/www/html/yourgot;
    
    index index.php;
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 💡 사용 방법

### 계정 생성 및 로그인
1. 웹 브라우저에서 `http://localhost/yourgot` 접속
2. **회원가입** 클릭하여 새 계정 생성
3. 이메일과 비밀번호로 로그인

### 위험성평가 수행
1. **무료 시작하기** 버튼 클릭
2. 4단계 프로세스를 순차적으로 진행:
   - **1단계**: 프로젝트 기본정보 입력
   - **2단계**: 공정정보 및 작업조건 설정
   - **3단계**: AI 기반 위험성평가 검토 및 편집
   - **4단계**: 완성된 보고서 다운로드

### 관리자 기능
- **사용자 관리**: 계정 승인, 권한 설정, 활동 모니터링
- **시스템 설정**: AI 모델 파라미터, 안전 기준 조정
- **통계 대시보드**: 사용량 분석, 성능 모니터링

## 🏗️ 프로젝트 구조

```
yourgot/
├── 📁 auth/                    # 사용자 인증
│   ├── login.php              # 로그인/회원가입 페이지
│   └── logout.php             # 로그아웃 처리
├── 📁 config/                 # 설정 파일
│   ├── config.php             # 메인 설정
│   └── env.php                # 환경변수 로더
├── 📁 includes/               # 공통 함수
│   └── functions.php          # 핵심 비즈니스 로직
├── 📁 pages/                  # 메인 기능 페이지
│   ├── step1/project_info.php # 프로젝트 정보 입력
│   ├── step2/process_info.php # 공정정보 입력
│   ├── step3/risk_assessment.php # AI 위험성평가
│   └── step4/report_output.php   # 보고서 출력
├── 📁 api/                    # API 서비스
│   └── openai_service.php     # OpenAI 연동 서비스
├── 📁 assets/                 # 정적 자원
│   ├── css/
│   │   ├── figma-design.css   # 피그마 디자인 시스템
│   │   └── style.css          # 기존 스타일
│   ├── js/                    # JavaScript 파일
│   └── images/                # 이미지 자원
├── 📁 database/               # 데이터베이스
│   ├── schema.sql             # 기본 스키마
│   └── auth_schema.sql        # 인증 시스템 스키마
├── 📁 uploads/                # 업로드 파일
├── 📁 logs/                   # 시스템 로그
├── .env                       # 환경변수 (생성 필요)
├── env.example                # 환경변수 템플릿
├── index.php                  # 메인 랜딩 페이지
└── README.md                  # 이 파일
```

## 🔧 설정 및 커스터마이징

### AI 모델 설정
```env
# .env 파일에서 조정
OPENAI_MODEL=gpt-4-turbo-preview
OPENAI_MAX_TOKENS=2000
OPENAI_TEMPERATURE=0.7
```

### 위험성 척도 기준
```php
// config/config.php
define('RISK_LEVELS', [
    1 => ['name' => '매우낮음', 'color' => '#28a745', 'action' => '현재 수준 유지'],
    2 => ['name' => '낮음', 'color' => '#6f42c1', 'action' => '현재 수준 유지'],
    3 => ['name' => '보통', 'color' => '#ffc107', 'action' => '위험성 감소 검토'],
    4 => ['name' => '높음', 'color' => '#fd7e14', 'action' => '위험성 감소 필요'],
    5 => ['name' => '매우높음', 'color' => '#dc3545', 'action' => '즉시 개선 필요']
]);
```

### 디자인 커스터마이징
피그마 디자인 시스템 기반으로 CSS 변수를 통해 쉽게 커스터마이징 가능:

```css
/* assets/css/figma-design.css */
:root {
    --primary-500: #004fff;      /* 메인 색상 */
    --neutral-900: #17191a;      /* 텍스트 색상 */
    --font-family: 'Pretendard'; /* 기본 폰트 */
}
```

## 🛡️ 보안 기능

### 데이터 보호
- **비밀번호 암호화**: PHP `password_hash()` 함수 사용
- **SQL 인젝션 방지**: PDO Prepared Statements
- **CSRF 보호**: 토큰 기반 요청 검증
- **XSS 방지**: `htmlspecialchars()` 함수로 출력 이스케이프

### 세션 보안
- **안전한 세션 관리**: 세션 하이재킹 방지
- **로그인 로그**: 접속 IP, 브라우저 정보 기록
- **자동 로그아웃**: 일정 시간 비활성화 시 자동 로그아웃

### 파일 업로드 보안
- **파일 형식 검증**: 허용된 확장자만 업로드 허용
- **파일 크기 제한**: 설정 가능한 최대 업로드 크기
- **악성 파일 스캔**: 업로드 파일 보안 검사

## 📊 모니터링 및 로깅

### 시스템 로그
```php
// logs/system.log
[2024-01-15 10:30:25] [INFO] 사용자 로그인: user@example.com
[2024-01-15 10:31:12] [INFO] 프로젝트 생성: ID 123, 이름: 건설현장 안전평가
[2024-01-15 10:35:44] [ERROR] OpenAI API 호출 실패: Rate limit exceeded
```

### AI 사용량 추적
- **토큰 사용량**: 실시간 모니터링
- **비용 추적**: API 호출 비용 계산
- **성능 지표**: 응답 시간, 성공률 측정

### 사용자 활동 로그
- **프로젝트 생성/수정**: 상세 변경 이력 기록
- **위험성평가 수행**: AI 분석 결과 저장
- **보고서 다운로드**: 접근 로그 관리

## 🐛 문제 해결

### 일반적인 문제

**🔴 데이터베이스 연결 오류**
```bash
# MySQL 서비스 상태 확인
systemctl status mysql
# 또는
service mysql status

# 연결 테스트
mysql -u root -p -h localhost
```

**🔴 OpenAI API 오류**
- API 키 유효성 확인: [OpenAI Dashboard](https://platform.openai.com/api-keys)
- 계정 잔액 확인: [Billing 페이지](https://platform.openai.com/account/billing)
- 요청 한도 확인: [Usage 페이지](https://platform.openai.com/account/usage)

**🔴 파일 업로드 오류**
```php
// PHP 설정 확인
phpinfo(); // upload_max_filesize, post_max_size 확인

// 폴더 권한 확인
ls -la uploads/
chmod 755 uploads/
```

**🔴 UTF-8/ANSI 인코딩 문제**
```php
// config/config.php에서 확인
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
```

### 디버그 모드
```env
# .env 파일에서 활성화
APP_DEBUG=true
```

## 🚀 성능 최적화

### PHP 설정 권장값
```ini
# php.ini
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 12M
max_input_vars = 3000
```

### 데이터베이스 최적화
```sql
-- 인덱스 확인
SHOW INDEX FROM projects;
SHOW INDEX FROM risk_assessments;

-- 쿼리 성능 분석
EXPLAIN SELECT * FROM projects WHERE user_id = 1;
```

### 캐싱 전략
- **세션 캐싱**: Redis/Memcached 사용 권장
- **정적 자원 캐싱**: 브라우저 캐시 활용
- **데이터베이스 쿼리 캐싱**: MySQL 쿼리 캐시 설정

## 🤝 기여하기

우리는 커뮤니티의 기여를 환영합니다!

### 개발 환경 설정
1. Fork this repository
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

### 코드 스타일
- PSR-12 PHP 코딩 표준 준수
- 한국어 주석 및 변수명 사용
- PHPDoc 주석 작성 권장

## 📄 라이선스

이 프로젝트는 MIT 라이선스 하에 배포됩니다. 자세한 내용은 [LICENSE](LICENSE) 파일을 참조하세요.

## 🙏 감사의 말

- **OpenAI**: GPT-4 API 제공
- **Pretendard**: 한국어 최적화 폰트
- **Figma Community**: 디자인 시스템 영감
- **PHP Community**: 오픈소스 라이브러리

## 📞 지원 및 문의

- **기술 지원**: [support@yourgot.com](mailto:support@yourgot.com)
- **영업 문의**: [sales@yourgot.com](mailto:sales@yourgot.com)
- **버그 리포트**: [GitHub Issues](https://github.com/yourgot/risk-assessment/issues)
- **문서**: [Wiki](https://github.com/yourgot/risk-assessment/wiki)

---

**🏆 Made with ❤️ by YourGot Team**

*AI와 함께하는 더 안전한 작업장을 만들어갑니다.* 