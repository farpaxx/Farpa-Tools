<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Função para gerar uma nova chave de ativação
function generateActivationKey($usuario_id, $dias_validade = 365) {
    $conn = getDbConnection();
    
    // Gerar chave com segurança criptográfica
    $chave = bin2hex(random_bytes(32));
    
    // Calcular data de expiração
    $data_expiracao = date('Y-m-d H:i:s', strtotime("+$dias_validade days"));
    
    // Inserir a chave no banco
    $stmt = $conn->prepare("INSERT INTO chaves_ativacao (usuario_id, chave, data_expiracao, status) 
                           VALUES (?, ?, ?, 'gerada')");
    $stmt->bind_param("iss", $usuario_id, $chave, $data_expiracao);
    
    if ($stmt->execute()) {
        $chave_id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        
        // Registrar log
        logAction($usuario_id, $chave_id, 'acesso_remoto', 'Nova chave de ativação gerada');
        
        return [
            'success' => true,
            'chave' => $chave,
            'chave_id' => $chave_id,
            'data_expiracao' => $data_expiracao
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['error' => 'Erro ao gerar chave: ' . $error];
    }
}

// Função para verificar a validade de uma chave
function verifyActivationKey($chave) {
    $conn = getDbConnection();
    
    // Buscar a chave no banco
    $stmt = $conn->prepare("SELECT id, usuario_id, data_expiracao, status FROM chaves_ativacao 
                           WHERE chave = ? AND status IN ('gerada', 'ativa')");
    $stmt->bind_param("s", $chave);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['error' => 'Chave de ativação inválida ou já utilizada.'];
    }
    
    $key_data = $result->fetch_assoc();
    $stmt->close();
    
    // Verificar expiração
    if (strtotime($key_data['data_expiracao']) < time()) {
        // Atualizar status da chave para expirada
        $updateStmt = $conn->prepare("UPDATE chaves_ativacao SET status = 'expirada' WHERE id = ?");
        $updateStmt->bind_param("i", $key_data['id']);
        $updateStmt->execute();
        $updateStmt->close();
        $conn->close();
        
        return ['error' => 'Chave de ativação expirada.'];
    }
    
    // Se a chave estiver apenas gerada, atualizar para ativa
    if ($key_data['status'] === 'gerada') {
        $updateStmt = $conn->prepare("UPDATE chaves_ativacao SET status = 'ativa' WHERE id = ?");
        $updateStmt->bind_param("i", $key_data['id']);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    // Registrar log
    logAction($key_data['usuario_id'], $key_data['id'], 'acesso_remoto', 'Chave de ativação verificada com sucesso');
    
    $conn->close();
    return [
        'success' => true,
        'key_id' => $key_data['id'],
        'usuario_id' => $key_data['usuario_id'],
        'data_expiracao' => $key_data['data_expiracao']
    ];
}

// Função para revogar uma chave
function revokeActivationKey($chave_id) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE chaves_ativacao SET status = 'revogada' WHERE id = ?");
    $stmt->bind_param("i", $chave_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        // Registrar log
        logAction(null, $chave_id, 'acesso_remoto', 'Chave de ativação revogada');
        
        return ['success' => true];
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['error' => 'Erro ao revogar chave: ' . $error];
    }
}