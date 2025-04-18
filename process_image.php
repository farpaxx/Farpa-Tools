<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'processImage') {
    $response = array('success' => false, 'message' => '');
    
    if (!isset($_POST['itemId']) || !isset($_FILES['imageFile'])) {
        $response['message'] = 'Dados incompletos';
        echo json_encode($response);
        exit;
    }
    
    $itemId = $_POST['itemId'];
    $uploadFile = $_FILES['imageFile'];
    $removeBackground = true;
    $backgroundMethod = isset($_POST['backgroundMethod']) ? intval($_POST['backgroundMethod']) : 1;
    $smoothLevel = isset($_POST['smoothLevel']) ? intval($_POST['smoothLevel']) : 5;
    
    // Limitar valores
    $smoothLevel = max(0, min(20, $smoothLevel));
    
    // Verificar se houve erro no upload
    if ($uploadFile['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Erro no upload: ' . $uploadFile['error'];
        echo json_encode($response);
        exit;
    }
    
    // Verificar se há alguma biblioteca de processamento de imagem disponível
    $hasImageProcessor = false;
    $processorType = '';
    
    if (extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
        $hasImageProcessor = true;
        $processorType = 'GD';
    } elseif (extension_loaded('imagick') && class_exists('Imagick')) {
        $hasImageProcessor = true;
        $processorType = 'ImageMagick';
    }
    
    // Caminho para as imagens
    $localImgDir = './img/items/';
    if (!file_exists($localImgDir)) {
        mkdir($localImgDir, 0777, true);
    }
    
    // Caminho para imagem no servidor de jogo
    $gameImgDir = getenv('IMAGES_PATH') ?: 'D:/ReDM-Base-DJ/server-data/resources/[vorp_resources]/vorp_inventory/html/img/items/';
    
    // Nomes de arquivos
    $tempFilePath = './img/temp_' . $itemId . '.tmp';
    $localDestination = $localImgDir . $itemId . '.png';
    $gameDestination = $gameImgDir . $itemId . '.png';
    
    // No início do processamento da imagem
    $logMsg = "Processando imagem:\n";
    $logMsg .= "Item ID: {$itemId}\n";
    $logMsg .= "Método: {$backgroundMethod}\n";
    $logMsg .= "Nível de suavização: {$smoothLevel}\n";
    error_log($logMsg);
    
    try {
        // Mover o arquivo para um local temporário primeiro
        if (move_uploaded_file($uploadFile['tmp_name'], $tempFilePath)) {
            $resized = false;
            
            // Verificar se o arquivo é uma imagem válida
            $imageInfo = @getimagesize($tempFilePath);
            if ($imageInfo === false) {
                throw new Exception('O arquivo enviado não é uma imagem válida');
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];
            
            // Verificar dimensões da imagem
            if ($width <= 0 || $height <= 0) {
                throw new Exception('Dimensões de imagem inválidas: ' . $width . 'x' . $height);
            }
            
            // Tentar redimensionar usando a biblioteca disponível
            if ($hasImageProcessor) {
                if ($processorType == 'GD') {
                    // Carregar a imagem com base no tipo
                    $sourceImage = null;
                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            $sourceImage = @imagecreatefromjpeg($tempFilePath);
                            break;
                        case IMAGETYPE_PNG:
                            $sourceImage = @imagecreatefrompng($tempFilePath);
                            break;
                        case IMAGETYPE_GIF:
                            $sourceImage = @imagecreatefromgif($tempFilePath);
                            break;
                        default:
                            throw new Exception('Formato de imagem não suportado: ' . image_type_to_mime_type($type));
                    }
                    
                    if (!$sourceImage) {
                        throw new Exception('Falha ao carregar a imagem');
                    }
                    
                    // Redimensionar para no máximo 96x96, mantendo a proporção
                    $sourceRatio = $width / $height;
                    if ($sourceRatio > 1) {
                        $newWidth = 96;
                        $newHeight = 96 / $sourceRatio;
                    } else {
                        $newHeight = 96;
                        $newWidth = 96 * $sourceRatio;
                    }
                    
                    $tempImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagealphablending($tempImage, false);
                    imagesavealpha($tempImage, true);
                    $transparent = imagecolorallocatealpha($tempImage, 0, 0, 0, 127);
                    imagefill($tempImage, 0, 0, $transparent);
                    
                    // Redimensionar
                    imagecopyresampled($tempImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    
                    // Remover fundo se solicitado
                    if ($removeBackground) {
                        if ($backgroundMethod == 3) {
                            // API Remove.bg
                            $apiKey = isset($_POST['removebgApiKey']) ? $_POST['removebgApiKey'] : '';
                            
                            if (empty($apiKey)) {
                                $response['message'] = 'Chave API do Remove.bg não fornecida';
                                echo json_encode($response);
                                exit;
                            }
                            
                            $resized = removeBackgroundWithAPI($tempFilePath, $localDestination, $apiKey);
                            
                            if ($resized) {
                                $response['success'] = true;
                                $response['message'] = 'Imagem processada com o serviço Remove.bg';
                            } else {
                                $response['message'] = 'Erro ao processar com o Remove.bg. Verifique sua chave API ou tente outro método.';
                            }
                        } else {
                            // Métodos locais (GD)
                            $finalImage = imagecreatetruecolor(96, 96);
                            imagealphablending($finalImage, false);
                            imagesavealpha($finalImage, true);
                            $transparent = imagecolorallocatealpha($finalImage, 0, 0, 0, 127);
                            imagefill($finalImage, 0, 0, $transparent);
                            
                            // Processar com o método escolhido
                            $processedImage = removeBackground($tempImage, $newWidth, $newHeight, $backgroundMethod, $smoothLevel);
                            
                            // Centralizar na imagem 96x96
                            $offsetX = (96 - $newWidth) / 2;
                            $offsetY = (96 - $newHeight) / 2;
                            
                            imagecopy($finalImage, $processedImage, $offsetX, $offsetY, 0, 0, $newWidth, $newHeight);
                            imagepng($finalImage, $localDestination);
                            imagedestroy($finalImage);
                            imagedestroy($processedImage);
                            $resized = true;
                        }
                    } else {
                        // Apenas centralizar sem remover fundo
                        $finalImage = imagecreatetruecolor(96, 96);
                        imagealphablending($finalImage, false);
                        imagesavealpha($finalImage, true);
                        $transparent = imagecolorallocatealpha($finalImage, 0, 0, 0, 127);
                        imagefill($finalImage, 0, 0, $transparent);
                        
                        $offsetX = (96 - $newWidth) / 2;
                        $offsetY = (96 - $newHeight) / 2;
                        
                        imagecopy($finalImage, $tempImage, $offsetX, $offsetY, 0, 0, $newWidth, $newHeight);
                        imagepng($finalImage, $localDestination);
                        imagedestroy($finalImage);
                        $resized = true;
                    }
                    
                    imagedestroy($tempImage);
                    imagedestroy($sourceImage);
                    
                    // Tentar copiar para o diretório do jogo
                    if (file_exists($localDestination)) {
                        if (copy($localDestination, $gameDestination)) {
                            $response['success'] = true;
                            if (!isset($response['message'])) {
                                $response['message'] = "Imagem processada com {$processorType} e salva com sucesso";
                            }
                        } else {
                            $response['success'] = true;
                            $response['message'] = 'Imagem salva localmente, mas não foi possível copiar para o diretório do jogo';
                        }
                    } else {
                        $response['message'] = 'Erro: o arquivo processado não existe';
                    }
                    
                    // Remover arquivo temporário
                    if (file_exists($tempFilePath)) {
                        unlink($tempFilePath);
                    }

                    // Log para depuração
                    $logFile = fopen("debug_image.log", "a");
                    fwrite($logFile, date('Y-m-d H:i:s') . " - Processando imagem para item: {$itemId}\n");
                    fwrite($logFile, "Método de remoção de fundo: {$backgroundMethod}\n");
                    fwrite($logFile, "Processador de imagem: {$processorType}\n");

                    // E adicionar logs em cada etapa crítica
                    if ($hasImageProcessor && $processorType == 'GD') {
                        fwrite($logFile, "Dimensões originais: {$width}x{$height}\n");
                        fwrite($logFile, "Novas dimensões: {$newWidth}x{$newHeight}\n");
                        
                        if ($removeBackground) {
                            fwrite($logFile, "Aplicando remoção de fundo com método {$backgroundMethod}\n");
                            // Resto do código...
                        }
                    }

                    fclose($logFile);
                }
            }
        } else {
            $response['message'] = 'Erro ao salvar o arquivo temporário';
        }
    } catch (Exception $e) {
        $response['message'] = 'Erro: ' . $e->getMessage();
        
        // Limpar arquivos temporários em caso de erro
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }

        // E no catch:
        $logFile = fopen("debug_image.log", "a");
        fwrite($logFile, "ERRO: " . $e->getMessage() . "\n");
        fclose($logFile);
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Redimensiona a imagem usando a biblioteca GD
 */
function resizeWithGD($sourcePath, $destinationPath) {
    try {
        // Identificar o tipo de imagem
        list($width, $height, $type) = getimagesize($sourcePath);
        
        // Criar imagem de origem baseada no tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                throw new Exception('Formato de imagem não suportado');
        }
        
        // Criar uma nova imagem
        $newImage = imagecreatetruecolor(96, 96);
        
        // Preservar transparência para PNG
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefilledrectangle($newImage, 0, 0, 96, 96, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, 96, 96, $width, $height);
        
        // Salvar a imagem
        imagepng($newImage, $destinationPath);
        
        // Liberar memória
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return true;
    } catch (Exception $e) {
        error_log('Erro ao redimensionar com GD: ' . $e->getMessage());
        return false;
    }
}

/**
 * Redimensiona a imagem usando a biblioteca ImageMagick
 */
function resizeWithImageMagick($sourcePath, $destinationPath) {
    try {
        $imagick = new Imagick();
        $imagick->readImage($sourcePath);
        $imagick->setImageFormat('png');
        
        // Redimensionar para 96x96
        $imagick->resizeImage(96, 96, Imagick::FILTER_LANCZOS, 1);
        
        // Salvar a imagem
        $imagick->writeImage($destinationPath);
        $imagick->clear();
        
        return true;
    } catch (Exception $e) {
        error_log('Erro ao redimensionar com ImageMagick: ' . $e->getMessage());
        return false;
    }
}

/**
 * Algoritmo otimizado para remover bordas brancas com níveis de suavização de 0-20
 */
function removeBackground($sourceImage, $width, $height, $method = 1, $smoothLevel = 5) {
    // Garantir que o smoothLevel esteja dentro dos limites
    $smoothLevel = max(0, min(20, $smoothLevel));
    
    // Criar nova imagem com transparência
    $newImage = imagecreatetruecolor($width, $height);
    imagealphablending($newImage, false);
    imagesavealpha($newImage, true);
    $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
    imagefill($newImage, 0, 0, $transparent);
    
    // Método 1: Otimizado para árvores com eliminação de bordas brancas
    if ($method == 1) {
        // Parâmetros de processamento ajustados para escala 0-20
        $tolerance = 30 + ($smoothLevel * 2);
        $whiteBorderTolerance = 220; 
        
        // Intensidade de remoção de borda branca baseada no nível
        $borderRemovalIntensity = min(100, $smoothLevel * 5);
        
        // Processar cada pixel com atenção especial às bordas brancas
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($sourceImage, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                
                // Verificar se é um pixel branco ou muito claro (possível borda)
                $isWhiteBorder = ($r > $whiteBorderTolerance && $g > $whiteBorderTolerance && $b > $whiteBorderTolerance);
                
                // Verificar se é verde (folha) ou marrom (tronco)
                $isGreen = ($g > max($r, $b) * 1.2);
                $isBrown = ($r > $b * 1.5 && $g > $b * 1.2 && $r > 60 && $g > 40);
                
                // Verificar se é borda da imagem
                $isBorderRegion = ($x < 5 || $y < 5 || $x > $width - 5 || $y > $height - 5);
                
                // Para níveis muito altos (15+), verificar vizinhança ampliada
                $checkRadius = ($smoothLevel >= 15) ? 5 : 3;
                
                // Verificar vizinhos para detecção de bordas brancas
                $hasColorNeighbor = false;
                if ($isWhiteBorder) {
                    for ($ny = max(0, $y - $checkRadius); $ny <= min($height - 1, $y + $checkRadius); $ny++) {
                        for ($nx = max(0, $x - $checkRadius); $nx <= min($width - 1, $x + $checkRadius); $nx++) {
                            if ($nx == $x && $ny == $y) continue;
                            
                            $neighborColor = imagecolorat($sourceImage, $nx, $ny);
                            $nr = ($neighborColor >> 16) & 0xFF;
                            $ng = ($neighborColor >> 8) & 0xFF;
                            $nb = $neighborColor & 0xFF;
                            
                            // Se tiver vizinho verde ou marrom, este pixel branco pode ser uma borda a tratar
                            if (($ng > max($nr, $nb) * 1.2) || ($nr > $nb * 1.5 && $ng > $nb * 1.2 && $nr > 60)) {
                                $hasColorNeighbor = true;
                                break 2;
                            }
                        }
                    }
                }
                
                // Determinar o alpha baseado nas condições
                $alpha = 127; // Totalmente transparente por padrão
                
                if ($isGreen || $isBrown) {
                    // Folhas ou tronco - completamente opaco
                    $alpha = 0;
                } 
                else if ($isWhiteBorder) {
                    // Pixel branco - verificar se é borda
                    if ($hasColorNeighbor) {
                        // Borda branca perto de objeto - aplicar transparência gradual
                        // Para níveis muito altos (15+), fazer as bordas brancas quase completamente transparentes
                        if ($smoothLevel >= 15) {
                            $alpha = 127; // Completamente transparente para níveis altos
                        } else {
                            $alpha = min(127, 80 + ($smoothLevel * 3));
                        }
                    } else if ($isBorderRegion) {
                        // Branco na borda externa - totalmente transparente
                        $alpha = 127;
                    } else {
                        // Branco em outras regiões - baseado no nível de suavidade (mais agressivo em níveis altos)
                        if ($smoothLevel >= 15) {
                            $alpha = 127; // Totalmente transparente para níveis muito altos
                        } else {
                            $alpha = min(127, 60 + ($smoothLevel * 3));
                        }
                    }
                }
                else {
                    // Outros pixels - verificação de luminosidade
                    $luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                    
                    if ($luminance > 240) {
                        // Quase branco - provavelmente fundo
                        $alpha = 127;
                    } 
                    else if ($luminance > 180) {
                        // Claro mas não branco - pode ser detalhe ou fundo
                        // Níveis mais altos aplicam mais transparência a áreas claras
                        $alpha = min(127, 70 + ($smoothLevel * 2.5));
                    }
                    else if ($luminance < 60) {
                        // Áreas escuras - geralmente é o objeto
                        $alpha = 0;
                    }
                    else {
                        // Luminosidade média - ajuste baseado na cor
                        // Se tiver componente verde forte, provavelmente é parte da árvore
                        if ($g > max($r, $b) * 1.1) {
                            $alpha = 0;
                        } else {
                            // Calcular baseado na distância do branco e no nível de suavidade
                            $whiteDist = sqrt(pow(255-$r, 2) + pow(255-$g, 2) + pow(255-$b, 2));
                            $alpha = max(0, min(127, 127 - ($whiteDist / (2 + ($smoothLevel/10)))));
                        }
                    }
                }
                
                // Aplicar suavização adicional para níveis extremamente altos (>15)
                if ($smoothLevel >= 15 && $alpha > 0 && $alpha < 127) {
                    // Bordas brancas recebem tratamento ainda mais agressivo
                    if ($isWhiteBorder) {
                        $whiteRemovalFactor = ($smoothLevel - 10) * 10; // 50-100 para níveis 15-20
                        $alpha = min(127, $alpha + $whiteRemovalFactor);
                    }
                }
                
                // Certeza absoluta para pixels verdes ou marrons
                if ($isGreen || $isBrown) {
                    $alpha = 0; // Sempre opaco
                }
                // Certeza absoluta para pixels totalmente brancos em qualquer lugar (para níveis altos)
                else if ($smoothLevel >= 18 && $r > 245 && $g > 245 && $b > 245) {
                    $alpha = 127; // Sempre transparente para níveis muito altos
                }
                
                $newColor = imagecolorallocatealpha($newImage, $r, $g, $b, $alpha);
                imagesetpixel($newImage, $x, $y, $newColor);
            }
        }
        
        // Pós-processamento para eliminar halos brancos restantes
        // Mais agressivo para níveis mais altos
        if ($smoothLevel >= 5) {
            $tempImage = imagecreatetruecolor($width, $height);
            imagealphablending($tempImage, false);
            imagesavealpha($tempImage, true);
            imagecopy($tempImage, $newImage, 0, 0, 0, 0, $width, $height);
            
            // Para níveis extremamente altos, aplicar múltiplas passagens
            $passes = ($smoothLevel >= 15) ? 2 : 1;
            
            for ($pass = 0; $pass < $passes; $pass++) {
                // Detectar e remover halos brancos
                for ($y = 1; $y < $height - 1; $y++) {
                    for ($x = 1; $x < $width - 1; $x++) {
                        $color = imagecolorat($tempImage, $x, $y);
                        $r = ($color >> 16) & 0xFF;
                        $g = ($color >> 8) & 0xFF;
                        $b = $color & 0xFF;
                        $alpha = ($color >> 24) & 0x7F;
                        
                        // Nível de brancura ajustado - mais agressivo em níveis altos
                        $whiteThreshold = max(200, 240 - $smoothLevel); // 240 para nível 0, 220 para 20
                        
                        // Verificar se é um halo branco (branco com alguma transparência)
                        if ($r > $whiteThreshold && $g > $whiteThreshold && $b > $whiteThreshold && $alpha < 120) {
                            // Transparência adicional baseada no nível
                            $extraTransparency = min(70, $smoothLevel * 3.5); // 0-70 para níveis 0-20
                            
                            $newAlpha = min(127, $alpha + $extraTransparency);
                            $newColor = imagecolorallocatealpha($newImage, $r, $g, $b, $newAlpha);
                            imagesetpixel($newImage, $x, $y, $newColor);
                        }
                    }
                }
            }
            
            imagedestroy($tempImage);
        }
    }
    
    return $newImage;
}

/**
 * Calcula a diferença entre duas cores RGB
 */
function colorDistance($color1, $color2) {
    $c1 = array(
        'r' => ($color1 >> 16) & 0xFF,
        'g' => ($color1 >> 8) & 0xFF,
        'b' => $color1 & 0xFF
    );
    
    $c2 = array(
        'r' => ($color2 >> 16) & 0xFF,
        'g' => ($color2 >> 8) & 0xFF,
        'b' => $color2 & 0xFF
    );
    
    return sqrt(
        pow($c1['r'] - $c2['r'], 2) +
        pow($c1['g'] - $c2['g'], 2) +
        pow($c1['b'] - $c2['b'], 2)
    );
}

/**
 * Remove o fundo usando a API do Remove.bg
 * @param string $imagePath Caminho para a imagem
 * @param string $outputPath Caminho para salvar a imagem resultante
 * @param string $apiKey Chave da API remove.bg
 * @return bool True se a operação foi bem-sucedida
 */
function removeBackgroundWithAPI($imagePath, $outputPath, $apiKey) {
    // Verificar se a chave API foi fornecida
    if (empty($apiKey)) {
        error_log('Erro: API key do Remove.bg não fornecida');
        return false;
    }
    
    // Iniciar a requisição cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.remove.bg/v1.0/removebg');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'image_file' => new CURLFile($imagePath),
        'size' => 'regular',
        'format' => 'png',
    ]);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Api-Key: ' . $apiKey,
    ]);
    
    $result = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($statusCode == 200) {
        file_put_contents($outputPath, $result);
        return true;
    } else {
        $errorMessage = json_decode($result, true);
        $errorDetail = isset($errorMessage['errors'][0]['title']) ? $errorMessage['errors'][0]['title'] : 'Erro desconhecido';
        error_log('Erro ao usar API Remove.bg: ' . $statusCode . ' - ' . $errorDetail);
        return false;
    }
}

/**
 * Endpoint para processamento em tempo real
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'previewProcessing') {
    $response = array('success' => false, 'dataUrl' => '');
    
    // Verificar dados
    if (!isset($_FILES['image']) || !isset($_POST['method']) || !isset($_POST['smoothLevel'])) {
        echo json_encode($response);
        exit;
    }
    
    $method = intval($_POST['method']);
    $smoothLevel = intval($_POST['smoothLevel']);
    
    // Limitar valores
    $smoothLevel = max(0, min(20, $smoothLevel));
    
    try {
        // Processar a imagem temporariamente
        $tmpFile = './img/preview_temp_' . time() . '.tmp';
        if (move_uploaded_file($_FILES['image']['tmp_name'], $tmpFile)) {
            // Carregar imagem
            $imageInfo = getimagesize($tmpFile);
            if (!$imageInfo) {
                throw new Exception("Imagem inválida");
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];
            
            // Carregar a imagem
            $sourceImage = null;
            switch ($type) {
                case IMAGETYPE_JPEG: $sourceImage = imagecreatefromjpeg($tmpFile); break;
                case IMAGETYPE_PNG: $sourceImage = imagecreatefrompng($tmpFile); break;
                case IMAGETYPE_GIF: $sourceImage = imagecreatefromgif($tmpFile); break;
                default: throw new Exception("Formato não suportado");
            }
            
            if (!$sourceImage) {
                throw new Exception("Falha ao processar imagem");
            }
            
            // Redimensionar para tamanho adequado para preview
            $previewWidth = 300;
            $previewHeight = ($height / $width) * $previewWidth;
            
            $previewImage = imagecreatetruecolor($previewWidth, $previewHeight);
            imagealphablending($previewImage, false);
            imagesavealpha($previewImage, true);
            $transparent = imagecolorallocatealpha($previewImage, 0, 0, 0, 127);
            imagefill($previewImage, 0, 0, $transparent);
            
            imagecopyresampled($previewImage, $sourceImage, 0, 0, 0, 0, $previewWidth, $previewHeight, $width, $height);
            
            // Processar com o método e suavidade escolhidos
            $processedImage = removeBackground($previewImage, $previewWidth, $previewHeight, $method, $smoothLevel);
            
            // Salvar como PNG para manter transparência
            $previewOutputFile = './img/preview_' . time() . '.png';
            imagepng($processedImage, $previewOutputFile);
            
            // Converter para base64 para enviar como data URL
            $dataUrl = 'data:image/png;base64,' . base64_encode(file_get_contents($previewOutputFile));
            
            // Limpar
            imagedestroy($sourceImage);
            imagedestroy($previewImage);
            imagedestroy($processedImage);
            unlink($tmpFile);
            unlink($previewOutputFile);
            
            $response['success'] = true;
            $response['dataUrl'] = $dataUrl;
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Se não for um post válido
header("HTTP/1.1 400 Bad Request");
echo json_encode(array('success' => false, 'message' => 'Requisição inválida'));
?> 