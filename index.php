<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 기반 위험성평가 지원 시스템 - YourGot</title>
    <meta name="description" content="AI 기반 솔루션으로 위험성평가를 자동화하고 안전관리를 효율화하세요">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- 피그마 디자인 기반 스타일 -->
    <link rel="stylesheet" href="assets/css/figma-design.css">
    
    <!-- 추가 스타일 -->
    <style>
        .top-banner {
            background-color: var(--neutral-50);
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--neutral-300);
        }
        
        .top-banner-content {
            display: flex;
            align-items: center;
            gap: var(--spacing-4);
        }
        
        .progress-card {
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--secondary-500) 100%);
            color: var(--neutral-50);
            border: none;
        }
        
        .cta-section {
            background-color: var(--neutral-100);
            padding: var(--spacing-20) 0;
        }
        
        .cta-card {
            background: linear-gradient(135deg, var(--primary-50) 0%, var(--neutral-50) 100%);
            border-radius: var(--radius-lg);
            padding: var(--spacing-12);
            text-align: center;
            border: 1px solid var(--neutral-300);
        }

        .header {
            position: sticky;
            top: 0;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid var(--neutral-300);
            transition: background-color 0.3s ease;
        }

        .process-section {
            padding: var(--spacing-20) 0;
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--secondary-500) 100%);
            color: var(--neutral-50);
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-8);
            margin-top: var(--spacing-12);
        }

        .process-card {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            padding: var(--spacing-8);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .process-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .process-number {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-50);
            background-color: var(--secondary-500);
            border-radius: var(--radius-sm);
            padding: var(--spacing-1) var(--spacing-2);
            display: inline-block;
            margin-bottom: var(--spacing-4);
        }

        .header .header-logo a,
        .header .header-nav a {
            color: var(--neutral-900);
            font-weight: var(--font-weight-semibold);
        }
    </style>
</head>

<body>
    <!-- Top Banner -->
    <div class="top-banner">
        <div class="top-banner-content">
            <span class="badge badge-primary">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0zM6.5 4.5v3h3v-3h-3z"/>
                </svg>
                NEW
            </span>
            <span class="text-h5">AI 기반 위험성평가 자동화! 지금 시작하세요</span>
            <a href="#start" class="text-body-sm text-primary" style="text-decoration: none;">
                확인 →
            </a>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-logo">
                <a href="/" style="text-decoration: none;">YourGot</a>
            </div>
            
            <nav class="header-nav">
                <a href="#features" class="header-nav-item">솔루션</a>
                <a href="#process" class="header-nav-item">서비스</a>
                <a href="#contact" class="header-nav-item">문의하기</a>
            </nav>
            
            <div class="header-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="pages/step1/project_info.php" class="btn btn-primary btn-sm">
                        위험성평가 시작
                    </a>
                    <a href="auth/logout.php" class="btn btn-secondary btn-sm">
                        로그아웃
                    </a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-secondary btn-sm">
                        로그인
                    </a>
                    <a href="auth/login.php?mode=register" class="btn btn-primary btn-sm">
                        회원가입
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content fade-in-up">
                <div class="hero-badge">
                    <span class="badge badge-new">NEW</span>
                    <span class="text-body-sm text-white" style="margin-left: 8px;">AI 기반 위험성평가 자동화 시스템</span>
                </div>
                
                <h1 class="text-display hero-title">
                    AI 기반 위험성평가로<br>
                    안전관리를 혁신하세요
                </h1>
                
                <p class="text-body-lg hero-description">
                    AI 기반 솔루션으로 위험성평가를 자동화하여 운영을 간소화하고<br>
                    안전관리 효율을 향상시키세요
                </p>
                
                <div class="hero-actions">
                    <a href="pages/step1/project_info.php" class="btn btn-primary btn-lg">
                        무료 시작하기
                    </a>
                    <a href="#features" class="btn btn-outline btn-lg">
                        기능 알아보기
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="text-center fade-in-up">
                <span class="badge badge-primary">특장점</span>
                <h2 class="text-h1" style="margin: var(--spacing-6) 0;">
                    AI 기반 위험성평가의 핵심 기능
                </h2>
                <p class="text-body-lg text-secondary">
                    전문가 수준의 위험성평가를 AI가 자동으로 수행하여 시간과 비용을 절약하세요.
                </p>
            </div>
            
            <div class="features-grid fade-in-up">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-h4 feature-title">AI 자동 분석</h3>
                    <p class="text-body feature-description">
                        GPT-4 기반 AI가 작업환경과 공정을 분석하여 자동으로 위험요소를 식별하고 평가합니다.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2.5-9H18V0h-2v2H8V0H6v2H3.5C2.67 2 2 2.67 2 3.5v15C2 19.33 2.67 20 3.5 20h15c.83 0 1.5-.67 1.5-1.5v-15C20 2.67 19.33 2 18.5 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-h4 feature-title">실시간 보고서</h3>
                    <p class="text-body feature-description">
                        평가 결과를 즉시 전문적인 보고서 형태로 생성하여 즉시 활용 가능합니다.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-h4 feature-title">맞춤형 솔루션</h3>
                    <p class="text-body feature-description">
                        업종과 작업환경에 특화된 맞춤형 위험성평가로 정확도를 극대화합니다.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 0 0-5.5-1.65l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-h4 feature-title">법규 준수</h3>
                    <p class="text-body feature-description">
                        산업안전보건법 및 관련 법규를 완벽히 준수하는 전문 수준의 평가를 제공합니다.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section" id="process">
        <div class="container">
            <div class="text-center fade-in-up">
                <h2 class="text-h1" style="color: var(--neutral-50);">
                    4단계로 완성하는 위험성평가
                </h2>
                <p class="text-body-lg" style="color: var(--neutral-300); margin-top: var(--spacing-4);">
                    간단한 정보 입력으로 전문가 수준의 위험성평가 보고서를 완성하세요.
                </p>
            </div>
            
            <div class="process-grid fade-in-up">
                <!-- Step 1 -->
                <div class="process-card">
                    <div class="process-number">1단계</div>
                    <h3 class="text-h4" style="margin-bottom: var(--spacing-2);">프로젝트 기본정보</h3>
                    <p class="text-body" style="color: var(--neutral-300);">프로젝트명, 작업장소, 담당자 등 기본 정보를 입력합니다.</p>
                </div>
                <!-- Step 2 -->
                <div class="process-card">
                    <div class="process-number">2단계</div>
                    <h3 class="text-h4" style="margin-bottom: var(--spacing-2);">공정정보 입력</h3>
                    <p class="text-body" style="color: var(--neutral-300);">작업 공정, 사용 장비, 작업 조건 등 상세 정보를 입력합니다.</p>
                </div>
                <!-- Step 3 -->
                <div class="process-card">
                    <div class="process-number">3단계</div>
                    <h3 class="text-h4" style="margin-bottom: var(--spacing-2);">AI 위험성분석</h3>
                    <p class="text-body" style="color: var(--neutral-300);">AI가 입력된 정보를 분석하여 위험요소를 식별하고 평가합니다.</p>
                </div>
                <!-- Step 4 -->
                <div class="process-card">
                    <div class="process-number">4단계</div>
                    <h3 class="text-h4" style="margin-bottom: var(--spacing-2);">보고서 출력</h3>
                    <p class="text-body" style="color: var(--neutral-300);">전문적인 위험성평가 보고서를 PDF로 출력하고 관리합니다.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section style="padding: var(--spacing-20) 0; background-color: var(--neutral-100);">
        <div class="container">
            <div class="text-center fade-in-up">
                <h2 class="text-h2" style="margin-bottom: var(--spacing-6);">
                    이미 많은 기업들이 YourGot과 함께하고 있습니다
                </h2>
                
                <div class="card" style="max-width: 800px; margin: 0 auto;">
                    <div class="card-body">
                        <p class="text-h4" style="margin-bottom: var(--spacing-6); color: var(--neutral-700);">
                            "위험성평가 작업시간이 90% 단축되었고, 전문성도 크게 향상되었습니다. 
                            AI 기반 분석으로 놓칠 수 있는 위험요소까지 정확히 찾아줍니다."
                        </p>
                        
                        <div style="display: flex; align-items: center; justify-content: center; gap: var(--spacing-4);">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background-color: var(--neutral-300);"></div>
                            <div>
                                <div class="text-h5">김안전</div>
                                <div class="text-body-sm text-secondary">ABC건설 안전관리팀장</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="start">
        <div class="container fade-in-up">
            <div class="cta-card">
                <span class="badge badge-primary">지금 바로 시작</span>
                <h2 class="text-h1" style="margin-top: var(--spacing-4);">
                    지금 바로 AI 위험성평가를<br>무료로 경험해보세요
                </h2>
                <p class="text-body-lg text-secondary" style="margin: var(--spacing-6) 0;">
                    복잡한 과정 없이 몇 단계만으로 안전한 작업 환경을 만드세요.<br>YourGot이 도와드립니다.
                </p>
                <a href="pages/step1/project_info.php" class="btn btn-primary btn-lg">
                    무료로 시작하기
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" style="background-color: var(--neutral-900); color: var(--neutral-500); padding: var(--spacing-16) 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-12);">
                <div>
                    <h3 class="text-h5" style="color: var(--neutral-50);">YourGot</h3>
                    <p class="text-body-sm" style="margin-top: var(--spacing-4);">
                        AI 기반 위험성평가 솔루션으로<br>산업 안전의 새로운 기준을 제시합니다.
                    </p>
                </div>
                <div>
                    <h4 class="text-body" style="color: var(--neutral-50); font-weight: 500;">빠른 링크</h4>
                    <ul style="list-style: none; margin-top: var(--spacing-4); display: flex; flex-direction: column; gap: var(--spacing-2);">
                        <li><a href="#features" class="text-body-sm" style="text-decoration: none; color: var(--neutral-500); hover: {color: var(--primary-500)}">솔루션</a></li>
                        <li><a href="#process" class="text-body-sm" style="text-decoration: none; color: var(--neutral-500);">서비스</a></li>
                        <li><a href="#contact" class="text-body-sm" style="text-decoration: none; color: var(--neutral-500);">문의하기</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-body" style="color: var(--neutral-50); font-weight: 500;">지원</h4>
                    <ul style="list-style: none; margin-top: var(--spacing-4); display: flex; flex-direction: column; gap: var(--spacing-2);">
                        <li><a href="#" class="text-body-sm" style="text-decoration: none; color: var(--neutral-500);">자주 묻는 질문</a></li>
                        <li><a href="#" class="text-body-sm" style="text-decoration: none; color: var(--neutral-500);">고객센터</a></li>
                        <li><a href="#" class="text-body-sm" style="text-decoration: none; color: var(--neutral-500);">개인정보 처리방침</a></li>
                    </ul>
                </div>
                 <div>
                    <h4 class="text-body" style="color: var(--neutral-50); font-weight: 500;">연락처</h4>
                    <ul style="list-style: none; margin-top: var(--spacing-4); display: flex; flex-direction: column; gap: var(--spacing-2);">
                        <li class="text-body-sm" style="color: var(--neutral-500);">이메일: contact@yourgot.com</li>
                        <li class="text-body-sm" style="color: var(--neutral-500);">전화: 02-1234-5678</li>
                    </ul>
                </div>
            </div>
            <div style="border-top: 1px solid var(--neutral-700); margin-top: var(--spacing-12); padding-top: var(--spacing-8); text-align: center;">
                <p class="text-caption">&copy; <?= date('Y') ?> YourGot. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // 스크롤 애니메이션
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // 애니메이션 대상 요소들 관찰
        document.querySelectorAll('.fade-in-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // 부드러운 스크롤
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html> 