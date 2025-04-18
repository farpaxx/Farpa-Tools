<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Função para verificar login
function verifyLogin($email, $password) {
    $conn = getDbConnection();
    
    // Preparar a consulta para buscar o usuário pelo email
    $stmt = $conn->prepare("SELECT id, nome, email, senha, tipo, status FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false; // Usuário não encontrado
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verificar se a conta está ativa
    if ($user['status'] !== 'ativo') {
        return ['error' => 'Conta inativa ou bloqueada. Entre em contato com o suporte.'];
    }
    
    // Verificar a senha
    if (password_verify($password, $user['senha'])) {
        // Atualizar último acesso
        $updateStmt = $conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $user['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Registrar log de acesso
        logAction($user['id'], null, 'login', 'Login bem-sucedido');
        
        // Retornar dados do usuário (sem a senha)
        unset($user['senha']);
        $conn->close();
        return $user;
    } else {
        // Registrar tentativa falha
        logAction(null, null, 'tentativa_falha', 'Tentativa de login falha para: ' . $email);
        $conn->close();
        return false;
    }
}

// Função para criar conta de usuário (somente admin pode fazer isso)
function createUser($nome, $email, $senha, $tipo = 'cliente') {
    $conn = getDbConnection();
    
    // Verificar se o email já existe
    $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        return ['error' => 'Este email já está em uso.'];
    }
    $checkStmt->close();
    
    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Inserir novo usuário
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nome, $email, $senha_hash, $tipo);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return ['success' => true, 'user_id' => $user_id];
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['error' => 'Erro ao criar usuário: ' . $error];
    }
}

// Função para fazer logout
function logout() {
    // Registrar o logout
    if (isset($_SESSION['user_id'])) {
        logAction($_SESSION['user_id'], null, 'logout', 'Logout realizado');
    }
    
    // Limpar sessão
    $_SESSION = [];
    
    // Destruir o cookie da sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir a sessão
    session_destroy();
}