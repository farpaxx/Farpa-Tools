<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirecionar se já estiver logado
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Erro de segurança: token inválido. Por favor, tente novamente.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Por favor, preencha todos os campos.';
        } else {
            $result = verifyLogin($email, $password);
            
            if ($result === false) {
                $error = 'Email ou senha incorretos.';
            } elseif (isset($result['error'])) {
                $error = $result['error'];
            } else {
                // Login bem-sucedido - configurar sessão
                $_SESSION['user_id'] = $result['id'];
                $_SESSION['user_name'] = $result['nome'];
                $_SESSION['user_email'] = $result['email'];
                $_SESSION['user_type'] = $result['tipo'];
                
                // Redirecionar com base no tipo de usuário
                if ($result['tipo'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    // Verificar se o cliente já tem configuração
                    require_once 'config/database.php';
                    $conn = getDbConnection();
                    $stmt = $conn->prepare("SELECT id FROM configuracoes_cliente WHERE usuario_id = ? AND status = 'configurado'");
                    $stmt->bind_param("i", $result['id']);
                    $stmt->execute();
                    $configResult = $stmt->get_result();
                    $stmt->close();
                    $conn->close();
                    
                    if ($configResult->num_rows > 0) {
                        header('Location: cliente/dashboard.php');
                    } else {
                        header('Location: activate.php');
                    }
                }
                exit;
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
    <title>Login - Farpax Tools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 200px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <h2>Farpax Tools</h2>
                <!-- <img src="assets/img/logo.png" alt="Farpax Tools Logo"> -->
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <a href="forgot_password.php">Esqueceu sua senha?</a>
            </div>
        </div>
    </div>
</body>
</html>