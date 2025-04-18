<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Verificar se o usuário é administrador
requireAdmin();

$error = '';
$success = '';

// Processar ações (adicionar/editar/alterar status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Erro de segurança: token inválido. Por favor, tente novamente.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Conexão com banco de dados
        $conn = getDbConnection();
        
        if ($action === 'add' || $action === 'edit') {
            $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $nome = sanitize($_POST['nome'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $status = sanitize($_POST['status'] ?? 'ativo');
            
            // Para adição, senha é obrigatória
            if ($action === 'add') {
                $senha = $_POST['senha'] ?? '';
                if (empty($nome) || empty($email) || empty($senha)) {
                    $error = 'Por favor, preencha todos os campos obrigatórios.';
                } elseif (strlen($senha) < 8) {
                    $error = 'A senha deve ter pelo menos 8 caracteres.';
                } else {
                    // Criar usuário
                    $result = createUser($nome, $email, $senha, 'cliente');
                    if (isset($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $success = 'Usuário criado com sucesso!';
                        logAction($_SESSION['user_id'], null, 'admin', 'Criação de usuário: ' . $email);
                    }
                }
            } else {
                // Editar usuário
                if (empty($nome) || empty($email)) {
                    $error = 'Por favor, preencha todos os campos obrigatórios.';
                } else {
                    // Verificar se o email já existe para outro usuário
                    $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                    $checkStmt->bind_param("si", $email, $id);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows > 0) {
                        $error = 'Este email já está em uso por outro usuário.';
                    } else {
                        // Atualizar usuário
                        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, status = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $nome, $email, $status, $id);
                        
                        if ($stmt->execute()) {
                            $success = 'Usuário atualizado com sucesso!';
                            logAction($_SESSION['user_id'], null, 'admin', 'Edição de usuário: ' . $email);
                        } else {
                            $error = 'Erro ao atualizar usuário: ' . $stmt->error;
                        }
                    }
                }
            }
        } elseif ($action === 'status') {
            $id = (int)$_POST['user_id'];
            $status = sanitize($_POST['status']);
            
            if ($id <= 0) {
                $error = 'ID de usuário inválido.';
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $id);
                
                if ($stmt->execute()) {
                    $success = 'Status do usuário alterado com sucesso!';
                    logAction($_SESSION['user_id'], null, 'admin', 'Alteração de status de usuário ID: ' . $id . ' para ' . $status);
                } else {
                    $error = 'Erro ao alterar status do usuário: ' . $stmt->error;
                }
            }
        }
        
        $conn->close();
    }
}

// Buscar usuários para listagem
$conn = getDbConnection();

// Parâmetros de filtro e paginação
$status_filter = sanitize($_GET['status'] ?? 'todos');
$search = sanitize($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construir consulta baseada nos filtros
$where_clauses = ["tipo = 'cliente'"];

if ($status_filter !== 'todos') {
    $where_clauses[] = "status = '$status_filter'";
}

if (!empty($search)) {
    $where_clauses[] = "(nome LIKE '%$search%' OR email LIKE '%$search%')";
}

$where_clause = implode(' AND ', $where_clauses);

// Contar total de registros para paginação
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE $where_clause");
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();

// Buscar usuários com paginação
$stmt = $conn->prepare("SELECT id, nome, email, status, ultimo_acesso, data_criacao 
                       FROM usuarios 
                       WHERE $where_clause
                       ORDER BY data_criacao DESC
                       LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$usuarios = $stmt->get_result();
$stmt->close();

$conn->close();

// Gerar token CSRF
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Farpax Tools</title>
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
        .table-actions .btn {
            padding: .25rem .5rem;
            font-size: .875rem;
            margin-right: 5px;
        }
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Farpax Tools - Admin</a>
            <div class="d-flex">
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="users.php">
                                <i class="fas fa-users"></i> Usuários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="keys.php">
                                <i class="fas fa-key"></i> Chaves de Ativação
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logs.php">
                                <i class="fas fa-history"></i> Logs de Atividade
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="configs.php">
                                <i class="fas fa-cogs"></i> Configurações
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gerenciar Usuários</h1>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus"></i> Novo Usuário
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <form method="get" action="users.php" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Buscar por nome ou email..." value="<?php echo htmlspecialchars($search); ?>">
                            <select name="status" class="form-select me-2" style="width: auto;">
                                <option value="todos" <?php echo $status_filter === 'todos' ? 'selected' : ''; ?>>Todos os Status</option>
                                <option value="ativo" <?php echo $status_filter === 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                                <option value="inativo" <?php echo $status_filter === 'inativo' ? 'selected' : ''; ?>>Inativos</option>
                                <option value="bloqueado" <?php echo $status_filter === 'bloqueado' ? 'selected' : ''; ?>>Bloqueados</option>
                            </select>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="m-0 p-2">Total: <strong><?php echo $total_records; ?></strong> usuários</p>
                    </div>
                </div>
                
                <!-- Tabela de Usuários -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Último Acesso</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($usuarios->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Nenhum usuário encontrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $usuario['id']; ?></td>
                                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td>
                                            <?php if ($usuario['status'] == 'ativo'): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php elseif ($usuario['status'] == 'inativo'): ?>
                                                <span class="badge bg-warning">Inativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Bloqueado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Nunca'; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></td>
                                        <td class="table-actions">
                                            <button type="button" class="btn btn-sm btn-primary edit-user" 
                                                    data-id="<?php echo $usuario['id']; ?>" 
                                                    data-nome="<?php echo htmlspecialchars($usuario['nome']); ?>" 
                                                    data-email="<?php echo htmlspecialchars($usuario['email']); ?>" 
                                                    data-status="<?php echo $usuario['status']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($usuario['status'] == 'ativo'): ?>
                                                <button type="button" class="btn btn-sm btn-warning change-status" 
                                                        data-id="<?php echo $usuario['id']; ?>" 
                                                        data-status="inativo" 
                                                        data-name="<?php echo htmlspecialchars($usuario['nome']); ?>">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php elseif ($usuario['status'] == 'inativo'): ?>
                                                <button type="button" class="btn btn-sm btn-success change-status" 
                                                        data-id="<?php echo $usuario['id']; ?>" 
                                                        data-status="ativo" 
                                                        data-name="<?php echo htmlspecialchars($usuario['nome']); ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-success change-status" 
                                                        data-id="<?php echo $usuario['id']; ?>" 
                                                        data-status="ativo" 
                                                        data-name="<?php echo htmlspecialchars($usuario['nome']); ?>">
                                                    <i class="fas fa-unlock"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="user_detail.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navegação de página">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>">Anterior</a>
                            </li>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>">Próxima</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <!-- Modal de Adicionar Usuário -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Adicionar Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" action="users.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3 password-field">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                            <i class="fas fa-eye toggle-password" data-target="senha"></i>
                            <div class="form-text">Mínimo de 8 caracteres</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                                <option value="bloqueado">Bloqueado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Editar Usuário -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" action="users.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                                <option value="bloqueado">Bloqueado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Mudar Status -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Confirmar Alteração de Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" action="users.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="user_id" id="status_user_id">
                    <input type="hidden" name="status" id="status_value">
                    
                    <div class="modal-body">
                        <p id="status_message"></p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Controle de visibilidade de senha
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
            
            // Abrir modal de edição
            $('.edit-user').click(function() {
                const id = $(this).data('id');
                const nome = $(this).data('nome');
                const email = $(this).data('email');
                const status = $(this).data('status');
                
                $('#edit_user_id').val(id);
                $('#edit_nome').val(nome);
                $('#edit_email').val(email);
                $('#edit_status').val(status);
                
                $('#editUserModal').modal('show');
            });
            
            // Abrir modal de alteração de status
            $('.change-status').click(function() {
                const id = $(this).data('id');
                const status = $(this).data('status');
                const name = $(this).data('name');
                
                $('#status_user_id').val(id);
                $('#status_value').val(status);
                
                let message = '';
                if (status === 'ativo') {
                    message = `Tem certeza que deseja ativar o usuário "${name}"?`;
                } else if (status === 'inativo') {
                    message = `Tem certeza que deseja inativar o usuário "${name}"?`;
                } else {
                    message = `Tem certeza que deseja bloquear o usuário "${name}"?`;
                }
                
                $('#status_message').text(message);
                $('#statusModal').modal('show');
            });
        });
    </script>
</body>
</html> 