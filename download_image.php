<?php
if (isset($_GET['file'])) {
    $file = $_GET['file'];
    $imagePath = "./img/items/{$file}";
    
    // Verificar se arquivo existe e não tem caminhos relativos (segurança)
    if (file_exists($imagePath) && !preg_match('/\.\./', $file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($imagePath));
        flush();
        readfile($imagePath);
        exit;
    }
}

// Redirecionar para a página principal em caso de erro
header("Location: index.php");
exit;
?> 