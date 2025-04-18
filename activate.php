<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/key_manager.php';

// Exigir login
requireLogin();

$error = '';
$success = '';
$usuario_id = $_SESSION['user_id'];

// Verificar se o usuário já tem uma chave ativa
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT c.id, c.chave, c.data_expiracao, c.status 
                       FROM chaves_ativacao c
                       WHERE c.usuario_id = ? AND c.status = 'ativa'");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$chave_ativa = $result->fetch_assoc();
$stmt->close();

// Processar formulário de ativação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Erro de segurança: token inválido. Por favor, tente novamente.';
    } else {
        $chave = sanitize($_POST['chave'] ?? '');
        
        if (empty($chave)) {
            $error = 'Por favor, digite a chave de ativação.';
        } else {
            $result = verifyActivationKey($chave);
            
            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                // Verificar se a chave pertence a esse usuário
                if ($result['usuario_id'] != $usuario_id) {
                    $error = 'Esta chave não está associada à sua conta.';
                } else {
                    $success = 'Chave ativada com sucesso!';
                    $_SESSION['key_id'] = $result['key_id'];
                    
                    // Redirecionar para página de configuração
                    header('Location: cliente/config.php');
                    exit;
                }
            }
        }
    }
}

// Gerar token CSRF
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativação da Chave - Farpax Tools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .activation-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .key-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="activation-container">
            <h2 class="text-center mb-4">Ativação do Sistema</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($chave_ativa): ?>
                <div class="alert alert-info">
                    <h5>Você já possui uma chave ativa</h5>
                    <p>Sua licença é válida até: <strong><?php echo date('d/m/Y H:i', strtotime($chave_ativa['data_expiracao'])); ?></strong></p>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="cliente/config.php" class="btn btn-primary">Configurar Sistema</a>
                    <a href="cliente/dashboard.php" class="btn btn-outline-secondary">Ir para Dashboard</a>
                </div>
            <?php else: ?>
                <p>Para continuar, você precisa ativar o sistema com a chave fornecida pelo administrador.</p>
                
                <form method="post" action="activate.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="chave" class="form-label">Chave de Ativação</label>
                        <input type="text" class="form-control" id="chave" name="chave" required>
                        <div class="form-text">Digite a chave de ativação de 64 caracteres fornecida pelo administrador.</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Ativar Sistema</button>
                    </div>
                </form>
                
                <div class="key-info mt-4">
                    <h5>Não tem uma chave?</h5>
                    <p>Entre em contato com o administrador do sistema para obter uma chave de ativação válida.</p>
                    <a href="mailto:admin@farpax.com" class="btn btn-sm btn-outline-primary">Solicitar Chave</a>
                </div>
            <?php endif; ?>
            
            <hr class="my-4">
            
            <div class="text-center">
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Sair</a>
            </div>
        </div>
    </div>
</body>
</html>