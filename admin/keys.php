<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/key_manager.php';

// Verificar se o usuário é administrador
requireAdmin();

$error = '';
$success = '';
$generated_key = '';

// Processar ações (gerar/revogar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Erro de segurança: token inválido. Por favor, tente novamente.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'generate') {
            $usuario_id = (int)$_POST['usuario_id'];
            $dias_validade = (int)$_POST['dias_validade'];
            
            if ($usuario_id <= 0) {
                $error = 'Selecione um usuário válido.';
            } elseif ($dias_validade <= 0) {
                $error = 'O período de validade deve ser maior que zero.';
            } else {
                // Verificar se o usuário existe
                $conn = getDbConnection();
                $stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE id = ? AND tipo = 'cliente'");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $error = 'Usuário não encontrado.';
                } else {
                    $usuario = $result->fetch_assoc();
                    
                    // Gerar a chave
                    $result = generateActivationKey($usuario_id, $dias_validade);
                    
                    if (isset($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $success = 'Chave gerada com sucesso para ' . $usuario['nome'] . '!';
                        $generated_key = $result['chave'];
                        logAction($_SESSION['user_id'], $result['chave_id'], 'admin', 'Geração de chave para usuário: ' . $usuario['email']);
                    }
                }
                
                $stmt->close();
                $conn->close();
            }
        } elseif ($action === 'revoke') {
            $chave_id = (int)$_POST['chave_id'];
            
            if ($chave_id <= 0) {
                $error = 'ID de chave inválido.';
            } else {
                // Revogar a chave
                $result = revokeActivationKey($chave_id);
                
                if (isset($result['error'])) {
                    $error = $result['error'];
                } else {
                    $success = 'Chave revogada com sucesso!';
                    logAction($_SESSION['user_id'], $chave_id, 'admin', 'Revogação de chave ID: ' . $chave_id);
                }
            }
        }
    }
}

// Buscar chaves para listagem
$conn = getDbConnection();

// Parâmetros de filtro e paginação
$status_filter = sanitize($_GET['status'] ?? 'todos');
$search = sanitize($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construir consulta baseada nos filtros
$where_clauses = [];

if ($status_filter !== 'todos') {
    $where_clauses[] = "c.status = '$status_filter'";
}

if (!empty($search)) {
    $where_clauses[] = "(u.nome LIKE '%$search%' OR u.email LIKE '%$search%' OR c.chave LIKE '%$search%')";
}

$where_clause = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Contar total de registros para paginação
$count_query = "SELECT COUNT(*) as total 
               FROM chaves_ativacao c
               LEFT JOIN usuarios u ON c.usuario_id = u.id
               $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();

// Buscar chaves com paginação
$query = "SELECT c.id, c.chave, c.data_criacao, c.data_expiracao, c.status,
         u.id as usuario_id, u.nome as usuario_nome, u.email as usuario_email
         FROM chaves_ativacao c
         LEFT JOIN usuarios u ON c.usuario_id = u.id
         $where_clause
         ORDER BY c.data_criacao DESC
         LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$chaves = $stmt->get_result();
$stmt->close();

// Buscar usuários para o dropdown de geração de chave
$users_stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo' ORDER BY nome");
$users_stmt->execute();
$usuarios = $users_stmt->get_result();
$users_stmt->close();

$conn->close();

// Gerar token CSRF
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Chaves de Ativação - Farpax Tools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #343a40;
            color: white;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            color: #e9ecef;
            padding: .75rem 1rem;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, .1);
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, .075);
        }
        .sidebar .nav-link i {
            margin-right: 8px;
        }
        main {
            padding-top: 20px;
        }
        .key-box {
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-family: monospace;
            margin: 20px 0;
            word-break: break-all;
        }
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .btn-revoke {
            color: #dc3545;
            cursor: pointer;
        }
        .btn-revoke:hover {
            color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (igual ao arquivo index.php) -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i> Usuários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="keys.php">
                                <i class="fas fa-key"></i> Chaves de Ativação
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logs.php">
                                <i class="fas fa-history"></i> Logs de Atividade
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="remote.php">
                                <i class="fas fa-server"></i> Acesso Remoto
                            </a>
                        </li>
                    </ul>
                    
                    <hr>
                    
                    <div class="dropdown pb-4 px-3">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2 fa-lg"></i>
                            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="../logout.php">Sair</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <!-- Conteúdo principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-key"></i> Chaves de Ativação</h1>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateKeyModal">
                        <i class="fas fa-plus"></i> Gerar Nova Chave
                    </button>
                </div>
                
                <!-- Alertas -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($generated_key)): ?>
                    <div class="alert alert-info">
                        <p><strong>Chave gerada:</strong></p>
                        <div class="key-box"><?php echo $generated_key; ?></div>
                        <p class="mb-0"><i class="fas fa-exclamation-triangle"></i> Copie esta chave agora pois ela não será mostrada novamente.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Filtros de pesquisa -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <form action="keys.php" method="get" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Buscar por usuário ou chave..." value="<?php echo htmlspecialchars($search); ?>">
                            <select name="status" class="form-select me-2" style="width: 150px;">
                                <option value="todos" <?php echo $status_filter === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                <option value="gerada" <?php echo $status_filter === 'gerada' ? 'selected' : ''; ?>>Gerada</option>
                                <option value="ativa" <?php echo $status_filter === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                                <option value="expirada" <?php echo $status_filter === 'expirada' ? 'selected' : ''; ?>>Expirada</option>
                                <option value="revogada" <?php echo $status_filter === 'revogada' ? 'selected' : ''; ?>>Revogada</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </form>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="text-muted">Total de chaves: <?php echo $total_records; ?></span>
                    </div>
                </div>
                
                <!-- Tabela de chaves -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Chave (parcial)</th>
                                <th>Data de Criação</th>
                                <th>Validade</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($chaves->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        Nenhuma chave encontrada com os filtros atuais.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($chave = $chaves->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $chave['id']; ?></td>
                                        <td>
                                            <?php if ($chave['usuario_id']): ?>
                                                <a href="users.php?view=<?php echo $chave['usuario_id']; ?>" title="Ver usuário">
                                                    <?php echo htmlspecialchars($chave['usuario_nome']); ?>
                                                </a>
                                                <div class="small text-muted"><?php echo htmlspecialchars($chave['usuario_email']); ?></div>
                                            <?php else: ?>
                                                <span class="text-muted">Usuário removido</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="d-inline-block text-truncate" style="max-width: 150px;">
                                                <?php echo substr($chave['chave'], 0, 16) . '...'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($chave['data_criacao'])); ?></td>
                                        <td>
                                            <?php 
                                            $dias_restantes = ceil((strtotime($chave['data_expiracao']) - time()) / 86400);
                                            if ($dias_restantes > 0 && $chave['status'] !== 'revogada' && $chave['status'] !== 'expirada'): 
                                            ?>
                                                <span class="badge bg-info"><?php echo $dias_restantes; ?> dias</span>
                                                <div class="small text-muted">
                                                    <?php echo date('d/m/Y', strtotime($chave['data_expiracao'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Expirada</span>
                                                <div class="small text-muted">
                                                    <?php echo date('d/m/Y', strtotime($chave['data_expiracao'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($chave['status'] === 'ativa'): ?>
                                                <span class="badge bg-success">Ativa</span>
                                            <?php elseif ($chave['status'] === 'gerada'): ?>
                                                <span class="badge bg-warning">Gerada</span>
                                            <?php elseif ($chave['status'] === 'expirada'): ?>
                                                <span class="badge bg-secondary">Expirada</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Revogada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-key" 
                                                    data-id="<?php echo $chave['id']; ?>"
                                                    data-key="<?php echo htmlspecialchars($chave['chave']); ?>"
                                                    data-username="<?php echo htmlspecialchars($chave['usuario_nome'] ?? 'Desconhecido'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($chave['status'] === 'ativa' || $chave['status'] === 'gerada'): ?>
                                                <button class="btn btn-sm btn-danger revoke-key" 
                                                        data-id="<?php echo $chave['id']; ?>"
                                                        data-username="<?php echo htmlspecialchars($chave['usuario_nome'] ?? 'Desconhecido'); ?>">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Anterior</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Próximo</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <!-- Modal de Gerar Chave -->
    <div class="modal fade" id="generateKeyModal" tabindex="-1" aria-labelledby="generateKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateKeyModalLabel">Gerar Nova Chave de Ativação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" action="keys.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="generate">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="usuario_id" class="form-label">Usuário</label>
                            <select class="form-select" id="usuario_id" name="usuario_id" required>
                                <option value="">Selecione um usuário...</option>
                                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                                    <option value="<?php echo $usuario['id']; ?>">
                                        <?php echo htmlspecialchars($usuario['nome']) . ' (' . htmlspecialchars($usuario['email']) . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="dias_validade" class="form-label">Período de Validade (dias)</label>
                            <input type="number" class="form-control" id="dias_validade" name="dias_validade" min="1" max="3650" value="365" required>
                            <div class="form-text">Entre 1 e 3650 dias (10 anos)</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Gerar Chave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Visualizar Chave -->
    <div class="modal fade" id="viewKeyModal" tabindex="-1" aria-labelledby="viewKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewKeyModalLabel">Chave de Ativação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p id="view_key_user"></p>
                    <div class="key-box" id="view_key_content"></div>
                    <button class="btn btn-sm btn-primary" id="copyKeyBtn">
                        <i class="fas fa-copy"></i> Copiar Chave
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Revogar Chave -->
    <div class="modal fade" id="revokeKeyModal" tabindex="-1" aria-labelledby="revokeKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="revokeKeyModalLabel">Confirmar Revogação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" action="keys.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="revoke">
                    <input type="hidden" name="chave_id" id="revoke_key_id">
                    
                    <div class="modal-body">
                        <p id="revoke_message"></p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Atenção: Esta ação não pode ser desfeita. 
                            O usuário perderá o acesso ao sistema até que uma nova chave seja gerada.
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Revogar Chave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Visualizar chave
            $('.view-key').click(function() {
                const keyId = $(this).data('id');
                const keyContent = $(this).data('key');
                const username = $(this).data('username');
                
                $('#view_key_user').text('Chave para usuário: ' + username);
                $('#view_key_content').text(keyContent);
                
                $('#viewKeyModal').modal('show');
            });
            
            // Copiar chave para clipboard
            $('#copyKeyBtn').click(function() {
                const keyText = $('#view_key_content').text();
                
                navigator.clipboard.writeText(keyText).then(function() {
                    alert('Chave copiada para a área de transferência!');
                }).catch(function(err) {
                    console.error('Erro ao copiar texto: ', err);
                    
                    // Fallback para navegadores mais antigos
                    const textArea = document.createElement("textarea");
                    textArea.value = keyText;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('Chave copiada para a área de transferência!');
                });
            });
            
            // Revogar chave
            $('.revoke-key').click(function() {
                const keyId = $(this).data('id');
                const username = $(this).data('username');
                
                $('#revoke_key_id').val(keyId);
                $('#revoke_message').text('Tem certeza que deseja revogar a chave do usuário "' + username + '"?');
                
                $('#revokeKeyModal').modal('show');
            });
        });
    </script>
</body>
</html> 