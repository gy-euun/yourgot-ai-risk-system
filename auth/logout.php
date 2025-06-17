<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// 로그아웃 처리
logout();

// 로그아웃 완료 메시지와 함께 메인 페이지로 리다이렉트
header('Location: ../index.php?message=logout_success');
exit;
?> 