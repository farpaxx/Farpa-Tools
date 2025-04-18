<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'uploadImage') {
    $response = array('success' => false, 'message' => '');
    
    if (!isset($_POST['itemId']) || !isset($_FILES['imageFile'])) {
        $response['message'] = 'Dados incompletos';
        echo json_encode($response);
        exit;
    }
    
    $itemId = $_POST['itemId'];
    $uploadFile = $_FILES['imageFile'];
    
    // Verificar se houve erro no upload
    if ($uploadFile['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Erro no upload: ' . $uploadFile['error'];
        echo json_encode($response);
        exit;
    }
    
    // Verificar se é realmente uma imagem PNG
    $imageInfo = getimagesize($uploadFile['tmp_name']);
    if ($imageInfo === false || $imageInfo[2] !== IMAGETYPE_PNG) {
        $response['message'] = 'O arquivo não é uma imagem PNG válida';
        echo json_encode($response);
        exit;
    }
    
    // Criar diretório local para salvar as imagens
    $localImgDir = './img/items/';
    if (!file_exists($localImgDir)) {
        mkdir($localImgDir, 0777, true);
    }
    
    // Caminho para imagem no servidor de jogo
    $gameImgDir = getenv('IMAGES_PATH') ?: 'D:/ReDM-Base-DJ/server-data/resources/[vorp_resources]/vorp_inventory/html/img/items/';
    
    // Salvar a imagem localmente
    $localDestination = $localImgDir . $itemId . '.png';
    if (move_uploaded_file($uploadFile['tmp_name'], $localDestination)) {
        // Copiar para o diretório do jogo se tiver permissão
        $gameDestination = $gameImgDir . $itemId . '.png';
        try {
            if (copy($localDestination, $gameDestination)) {
                $response['success'] = true;
                $response['message'] = 'Imagem salva com sucesso em ambos os diretórios';
            } else {
                $response['success'] = true;
                $response['message'] = 'Imagem salva localmente, mas não foi possível copiar para o diretório do jogo';
            }
        } catch (Exception $e) {
            $response['success'] = true;
            $response['message'] = 'Imagem salva localmente, mas ocorreu um erro ao copiar para o diretório do jogo: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Erro ao salvar a imagem';
    }
    
    echo json_encode($response);
    exit;
}

// Se não for um post de upload válido
header("HTTP/1.1 400 Bad Request");
echo json_encode(array('success' => false, 'message' => 'Requisição inválida'));
?> 