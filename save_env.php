<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'saveEnv') {
    $response = array('success' => false, 'message' => '');
    
    if (!isset($_POST['envContent'])) {
        $response['message'] = 'Conteúdo do arquivo .env não fornecido';
        echo json_encode($response);
        exit;
    }
    
    $envContent = $_POST['envContent'];
    
    // Validar o conteúdo do .env
    $lines = explode("\n", $envContent);
    $validContent = true;
    $invalidLines = array();
    
    foreach ($lines as $i => $line) {
        $line = trim($line);
        
        // Ignorar linhas vazias e comentários
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Verificar se a linha segue o formato CHAVE=VALOR
        if (strpos($line, '=') === false) {
            $validContent = false;
            $invalidLines[] = ($i + 1) . ': ' . $line;
        }
    }
    
    if (!$validContent) {
        $response['message'] = 'O arquivo .env contém linhas inválidas: ' . implode(', ', $invalidLines);
        echo json_encode($response);
        exit;
    }
    
    // Ler o arquivo .env atual
    $envFile = __DIR__ . '/.env';
    
    // Salvar o novo conteúdo
    if (file_put_contents($envFile, $envContent) !== false) {
        // Limpar o cache de variáveis de ambiente para recarregar
        clearstatcache();
        
        $response['success'] = true;
        $response['message'] = 'Arquivo .env salvo com sucesso';
    } else {
        $response['message'] = 'Erro ao salvar o arquivo .env';
    }
    
    echo json_encode($response);
    exit;
}

// Se não for um post válido
header("HTTP/1.1 400 Bad Request");
echo json_encode(array('success' => false, 'message' => 'Requisição inválida'));
?> 