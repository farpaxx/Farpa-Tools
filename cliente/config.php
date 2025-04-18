<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/key_manager.php';

// Exigir login e verificar se existe key_id na sessão
requireLogin();

if (!isset($_SESSION['key_id'])) {
    // Verificar se o usuário tem uma chave ativa
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM chaves_ativacao WHERE usuario_id = ? AND status = 'ativa'");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Não tem chave ativa, redirecionar para a página de ativação
        redirectWithMessage('../activate.php', 'Você precisa ativar uma chave antes de configurar o sistema.', 'warning');
    } else {
        // Tem chave ativa, guardar na sessão
        $key = $result->fetch_assoc();
        $_SESSION['key_id'] = $key['id'];
    }
    
    $stmt->close();
    $conn->close();
}

// Verificar se já existe configuração para este usuário
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM configuracoes_cliente WHERE usuario_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$config = $result->fetch_assoc();
$stmt->close();

$error = '';
$success = '';
$testSuccess = false;

// Processar formulário de configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Erro de segurança: token inválido. Por favor, tente novamente.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'test') {
            // Testar conexão
            $db_host = sanitize($_POST['db_host'] ?? '');
            $db_porta = (int)($_POST['db_porta'] ?? 3306);
            $db_usuario = sanitize($_POST['db_usuario'] ?? '');
            $db_senha = $_POST['db_senha'] ?? '';
            $db_nome = sanitize($_POST['db_nome'] ?? '');
            
            if (empty($db_host) || empty($db_usuario) || empty($db_nome)) {
                $error = 'Por favor, preencha todos os campos obrigatórios.';
            } else {
                // Testar conexão
                try {
                    $testConn = new mysqli($db_host, $db_usuario, $db_senha, $db_nome, $db_porta);
                    
                    if ($testConn->connect_error) {
                        $error = 'Falha na conexão: ' . $testConn->connect_error;
                    } else {
                        $success = 'Conexão bem-sucedida! Você pode salvar esta configuração.';
                        $testSuccess = true;
                        $testConn->close();
                    }
                } catch (Exception $e) {
                    $error = 'Erro ao conectar: ' . $e->getMessage();
                }
            }
        } else if ($action === 'save') {
            // Salvar configuração
            $db_host = sanitize($_POST['db_host'] ?? '');
            $db_porta = (int)($_POST['db_porta'] ?? 3306);
            $db_usuario = sanitize($_POST['db_usuario'] ?? '');
            $db_senha = $_POST['db_senha'] ?? '';
            $db_nome = sanitize($_POST['db_nome'] ?? '');
            $outras_configs = sanitize($_POST['outras_configs'] ?? '');
            
            if (empty($db_host) || empty($db_usuario) || empty($db_nome)) {
                $error = 'Por favor, preencha todos os campos obrigatórios.';
            } else {
                // Criptografar senha antes de salvar
                // Normalmente você usaria uma chave de criptografia armazenada separadamente
                $encryption_key = 'seu_segredo_muito_seguro'; // Em produção, use uma chave mais segura
                $encrypted_senha = openssl_encrypt($db_senha, 'AES-256-CBC', $encryption_key, 0, substr(hash('sha256', $encryption_key), 0, 16));
                
                // Verificar se já existe configuração para atualizar ou criar nova
                if ($config) {
                    // Atualizar configuração existente
                    $stmt = $conn->prepare("UPDATE configuracoes_cliente SET 
                                        db_host = ?, db_porta = ?, db_usuario = ?, db_senha = ?, 
                                        db_nome = ?, outras_configs = ?, status = 'configurado',
                                        data_configuracao = NOW() 
                                        WHERE id = ?");
                    $stmt->bind_param("sissssi", $db_host, $db_porta, $db_usuario, $encrypted_senha, 
                                     $db_nome, $outras_configs, $config['id']);
                } else {
                    // Inserir nova configuração
                    $stmt = $conn->prepare("INSERT INTO configuracoes_cliente 
                                        (usuario_id, chave_id, db_host, db_porta, db_usuario, db_senha, 
                                        db_nome, outras_configs, status, data_configuracao) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'configurado', NOW())");
                    $stmt->bind_param("iisissss", $_SESSION['user_id'], $_SESSION['key_id'], 
                                     $db_host, $db_porta, $db_usuario, $encrypted_senha, 
                                     $db_nome, $outras_configs);
                }
                
                if ($stmt->execute()) {
                    // Registrar log
                    logAction($_SESSION['user_id'], $_SESSION['key_id'], 'alteracao_config', 'Configuração de conexão salva/atualizada');
                    
                    // Redirecionar para o dashboard
                    redirectWithMessage('dashboard.php', 'Configuração salva com sucesso!', 'success');
                } else {
                    $error = 'Erro ao salvar configuração: ' . $stmt->error;
                }
                
                $stmt->close();
            }
        }
    }
}

$conn->close();

// Gerar token CSRF
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração do Sistema - Farpax Tools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .config-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .test-connection {
            margin-bottom: 20px;
        }
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="config-container">
            <h2 class="text-center mb-4">Configuração do Sistema</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post" action="config.php" id="configForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" id="formAction" value="test">
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-database me-2"></i> Configuração do Banco de Dados</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="db_host" class="form-label">Host do Banco de Dados <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="db_host" name="db_host" required
                                       value="<?php echo htmlspecialchars($config['db_host'] ?? ''); ?>">
                                <div class="form-text">Ex: localhost, 127.0.0.1, ou endereço do servidor remoto</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="db_porta" class="form-label">Porta</label>
                                <input type="number" class="form-control" id="db_porta" name="db_porta" 
                                       value="<?php echo htmlspecialchars($config['db_porta'] ?? '3306'); ?>">
                                <div class="form-text">Padrão: 3306 (MySQL/MariaDB)</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_nome" class="form-label">Nome do Banco de Dados <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="db_nome" name="db_nome" required
                                       value="<?php echo htmlspecialchars($config['db_nome'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="db_usuario" class="form-label">Usuário <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="db_usuario" name="db_usuario" required
                                       value="<?php echo htmlspecialchars($config['db_usuario'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3 password-field">
                            <label for="db_senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="db_senha" name="db_senha"
                                   value="<?php echo !empty($config) ? '••••••••' : ''; ?>">
                            <i class="fas fa-eye toggle-password" data-target="db_senha"></i>
                            <?php if (!empty($config)): ?>
                                <div class="form-text">Deixe em branco para manter a senha atual</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i> Configurações Adicionais</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="outras_configs" class="form-label">Outras Configurações (opcional)</label>
                            <textarea class="form-control" id="outras_configs" name="outras_configs" rows="3"
                                     placeholder="Insira outras configurações no formato JSON, se necessário"><?php echo htmlspecialchars($config['outras_configs'] ?? ''); ?></textarea>
                            <div class="form-text">Exemplo: {"timezone": "America/Sao_Paulo", "debug": false}</div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="../index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Voltar
                    </a>
                    
                    <div>
                        <button type="button" id="testConnectionBtn" class="btn btn-info me-2">
                            <i class="fas fa-plug me-2"></i> Testar Conexão
                        </button>
                        
                        <button type="button" id="saveConfigBtn" class="btn btn-primary" <?php echo $testSuccess ? '' : 'disabled'; ?>>
                            <i class="fas fa-save me-2"></i> Salvar Configuração
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="mt-3 text-center">
            <a href="../logout.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Sair
            </a>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Alternar visibilidade da senha
            $('.toggle-password').click(function() {
                const targetId = $(this).data('target');
                const input = $('#' + targetId);
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
            
            // Botão de testar conexão
            $('#testConnectionBtn').click(function() {
                $('#formAction').val('test');
                $('#configForm').submit();
            });
            
            // Botão de salvar configuração
            $('#saveConfigBtn').click(function() {
                $('#formAction').val('save');
                $('#configForm').submit();
            });
            
            <?php if ($testSuccess): ?>
            // Se o teste foi bem-sucedido, habilitar o botão de salvar
            $('#saveConfigBtn').prop('disabled', false);
            <?php endif; ?>
        });
    </script>
</body>
</html>
