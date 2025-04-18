<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Verificar se o usuário é administrador
requireAdmin();

// Obter estatísticas para o dashboard
$conn = getDbConnection();

// Total de usuários
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'cliente'");
$stmt->execute();
$result = $stmt->get_result();
$totalUsuarios = $result->fetch_assoc()['total'];
$stmt->close();

// Usuários ativos
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo'");
$stmt->execute();
$result = $stmt->get_result();
$usuariosAtivos = $result->fetch_assoc()['total'];
$stmt->close();

// Total de chaves
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM chaves_ativacao");
$stmt->execute();
$result = $stmt->get_result();
$totalChaves = $result->fetch_assoc()['total'];
$stmt->close();

// Chaves ativas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM chaves_ativacao WHERE status = 'ativa'");
$stmt->execute();
$result = $stmt->get_result();
$chavesAtivas = $result->fetch_assoc()['total'];
$stmt->close();

// Usuários recentes
$stmt = $conn->prepare("SELECT id, nome, email, status, data_criacao FROM usuarios 
                       WHERE tipo = 'cliente' 
                       ORDER BY data_criacao DESC LIMIT 5");
$stmt->execute();
$usuariosRecentes = $stmt->get_result();
$stmt->close();

// Atividades recentes (logs)
$stmt = $conn->prepare("SELECT l.id, l.acao, l.detalhes, l.data_hora, u.nome as usuario 
                       FROM logs_acesso l
                       LEFT JOIN usuarios u ON l.usuario_id = u.id
                       ORDER BY l.data_hora DESC LIMIT 10");
$stmt->execute();
$logsRecentes = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Farpax Tools</title>
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
        .stat-card {
            border-left: 4px solid;
            border-radius: 3px;
        }
        .card-usuarios {
            border-left-color: #4e73df;
        }
        .card-chaves {
            border-left-color: #1cc88a;
        }
        .card-ativos {
            border-left-color: #36b9cc;
        }
        .card-alertas {
            border-left-color: #f6c23e;
        }
        .stat-icon {
            font-size: 1.75rem;
            color: #dddfeb;
        }
        .stat-card .card-body {
            padding: 1rem;
        }
        .stat-card .card-title {
            text-transform: uppercase;
            color: #4e73df;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        .stat-card .card-value {
            color: #5a5c69;
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0;
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
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
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
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cogs"></i> Configurações
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="keys.php?action=new" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Nova Chave
                            </a>
                            <a href="users.php?action=new" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-user-plus"></i> Novo Usuário
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Cards de Estatísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card card-usuarios">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title">Total de Usuários</div>
                                        <div class="card-value"><?php echo $totalUsuarios; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card card-ativos">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title">Usuários Ativos</div>
                                        <div class="card-value"><?php echo $usuariosAtivos; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card card-chaves">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title">Total de Chaves</div>
                                        <div class="card-value"><?php echo $totalChaves; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-key stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card card-alertas">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="card-title">Chaves Ativas</div>
                                        <div class="card-value"><?php echo $chavesAtivas; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabelas de Conteúdo -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Usuários Recentes</h6>
                                <a href="users.php" class="btn btn-sm btn-primary">Ver Todos</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($usuario = $usuariosRecentes->fetch_assoc()): ?>
                                            <tr>
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
                                                <td><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Atividades Recentes</h6>
                                <a href="logs.php" class="btn btn-sm btn-primary">Ver Todos</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Usuário</th>
                                                <th>Ação</th>
                                                <th>Data/Hora</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($log = $logsRecentes->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($log['usuario'] ?? 'Sistema'); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($log['acao']) {
                                                        case 'login':
                                                            echo '<span class="badge bg-info">Login</span>';
                                                            break;
                                                        case 'logout':
                                                            echo '<span class="badge bg-secondary">Logout</span>';
                                                            break;
                                                        case 'tentativa_falha':
                                                            echo '<span class="badge bg-danger">Tentativa Falha</span>';
                                                            break;
                                                        case 'acesso_remoto':
                                                            echo '<span class="badge bg-primary">Acesso Remoto</span>';
                                                            break;
                                                        case 'alteracao_config':
                                                            echo '<span class="badge bg-warning">Alteração Config</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge bg-dark">Outra</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('d/m H:i', strtotime($log['data_hora'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 