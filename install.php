<?php
require_once 'config/database.php';

// Verificar se já está instalado
$installed = false;
try {
    $conn = getDbConnection();
    // Verificar se a tabela de usuários existe
    $result = $conn->query("SHOW TABLES LIKE 'usuarios'");
    $installed = ($result->num_rows > 0);
    $conn->close();
} catch (Exception $e) {
    // Ignorar erro, significa que precisamos instalar
}

// Se já estiver instalado, redirecionar
if ($installed) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Processar formulário de instalação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_nome = trim($_POST['admin_nome'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_senha = $_POST['admin_senha'] ?? '';
    $admin_senha_confirm = $_POST['admin_senha_confirm'] ?? '';
    
    // Validar campos
    if (empty($admin_nome) || empty($admin_email) || empty($admin_senha)) {
        $error = 'Todos os campos são obrigatórios.';
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } elseif (strlen($admin_senha) < 8) {
        $error = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($admin_senha !== $admin_senha_confirm) {
        $error = 'As senhas não coincidem.';
    } else {
        try {
            $conn = getDbConnection();
            
            // Criar as tabelas necessárias
            
            // Tabela de usuários
            $conn->query("CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                tipo ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
                status ENUM('ativo', 'inativo', 'bloqueado') NOT NULL DEFAULT 'ativo',
                ultimo_acesso DATETIME,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB");
            
            // Tabela de chaves de ativação
            $conn->query("CREATE TABLE IF NOT EXISTS chaves_ativacao (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT,
                chave VARCHAR(64) NOT NULL UNIQUE,
                data_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_ativacao DATETIME,
                data_expiracao DATETIME,
                status ENUM('gerada', 'ativa', 'expirada', 'revogada') NOT NULL DEFAULT 'gerada',
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
            ) ENGINE=InnoDB");
            
            // Tabela de configurações de cliente
            $conn->query("CREATE TABLE IF NOT EXISTS configuracoes_cliente (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT,
                chave_id INT,
                db_host VARCHAR(100) NOT NULL,
                db_porta INT NOT NULL DEFAULT 3306,
                db_usuario VARCHAR(100) NOT NULL,
                db_senha VARCHAR(255) NOT NULL,
                db_nome VARCHAR(100) NOT NULL,
                outras_configs TEXT,
                status ENUM('pendente', 'configurado', 'erro') NOT NULL DEFAULT 'pendente',
                data_configuracao TIMESTAMP,
                ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                FOREIGN KEY (chave_id) REFERENCES chaves_ativacao(id) ON DELETE SET NULL
            ) ENGINE=InnoDB");
            
            // Tabela de logs de acesso
            $conn->query("CREATE TABLE IF NOT EXISTS logs_acesso (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT,
                chave_id INT,
                ip VARCHAR(45),
                user_agent TEXT,
                acao VARCHAR(50) NOT NULL,
                detalhes TEXT,
                data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
                FOREIGN KEY (chave_id) REFERENCES chaves_ativacao(id) ON DELETE SET NULL
            ) ENGINE=InnoDB");
            
            // Criar usuário administrador
            $senha_hash = password_hash($admin_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'admin')");
            $stmt->bind_param("sss", $admin_nome, $admin_email, $senha_hash);
            $stmt->execute();
            $stmt->close();
            
            $success = 'Instalação concluída com sucesso! Você pode fazer login agora.';
            $conn->close();
        } catch (Exception $e) {
            $error = 'Erro durante a instalação: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Farpax Tools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .install-container {
            max-width: 650px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <h2 class="text-center mb-4">Instalação do Farpax Tools</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary">Ir para Login</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <strong>Bem-vindo à instalação do Farpax Tools!</strong><br>
                    Este processo irá criar as tabelas necessárias no banco de dados e configurar o usuário administrador.
                </div>
                
                <form method="post" action="install.php">
                    <h5 class="mb-3">Dados do Administrador</h5>
                    
                    <div class="mb-3">
                        <label for="admin_nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="admin_nome" name="admin_nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_senha" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="admin_senha" name="admin_senha" required>
                        <div class="form-text">Mínimo de 8 caracteres</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_senha_confirm" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="admin_senha_confirm" name="admin_senha_confirm" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Instalar Sistema</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 