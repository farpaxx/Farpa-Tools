<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'saveConfig') {
    $response = array('success' => false, 'message' => '');
    
    if (!isset($_POST['imagesPath'])) {
        $response['message'] = 'Caminho das imagens não fornecido';
        echo json_encode($response);
        exit;
    }
    
    $imagesPath = $_POST['imagesPath'];
    
    // Verificar se o caminho termina com uma barra
    if (substr($imagesPath, -1) !== '/' && substr($imagesPath, -1) !== '\\') {
        $imagesPath .= '/';
    }
    
    // Verificar se o diretório existe
    if (!file_exists($imagesPath)) {
        $response['message'] = 'O diretório especificado não existe';
        echo json_encode($response);
        exit;
    }
    
    // Ler o arquivo .env atual
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        $response['message'] = 'Arquivo .env não encontrado';
        echo json_encode($response);
        exit;
    }
    
    $envContent = file_get_contents($envFile);
    
    // Verificar se a configuração IMAGES_PATH já existe
    if (preg_match('/IMAGES_PATH=.*/', $envContent)) {
        // Atualizar a configuração existente
        $envContent = preg_replace('/IMAGES_PATH=.*/', 'IMAGES_PATH=' . $imagesPath, $envContent);
    } else {
        // Adicionar a nova configuração
        $envContent .= PHP_EOL . 'IMAGES_PATH=' . $imagesPath;
    }
    
    // Salvar o arquivo atualizado
    if (file_put_contents($envFile, $envContent) !== false) {
        $response['success'] = true;
        $response['message'] = 'Configurações salvas com sucesso';
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