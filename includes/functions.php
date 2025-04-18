<?php
session_start();

// Função para sanitizar entrada de dados
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Função para gerar um token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para verificar um token CSRF
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Função para registrar logs de ações
function logAction($usuario_id, $chave_id, $acao, $detalhes = '') {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDbConnection();
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO logs_acesso (usuario_id, chave_id, ip, user_agent, acao, detalhes) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $usuario_id, $chave_id, $ip, $user_agent, $acao, $detalhes);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Função para redirecionar com mensagem
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Função para verificar se o usuário é admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Função para exigir login
function requireLogin() {
    if (!isLoggedIn()) {
        redirectWithMessage('login.php', 'Por favor, faça login para acessar esta página.', 'warning');
    }
}

// Função para exigir perfil de admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirectWithMessage('index.php', 'Acesso negado. Você não tem permissão para acessar esta página.', 'danger');
    }
}