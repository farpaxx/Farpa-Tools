<?php
// Adicione este código no início do seu arquivo index.php, antes de qualquer output
// Tratamento de erros e redirecionamento para página de configuração

// Desativa a exibição de erros (para evitar mostrar a página de erro)
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

// Verificar se .env existe
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    header('Location: fix_config.php?error=env_not_found');
    exit;
}

// Carregar configurações do arquivo .env
$envConfig = parse_ini_file($envFile);
if ($envConfig === false) {
    header('Location: fix_config.php?error=invalid_env');
    exit;
}

// Verificar configurações necessárias
$requiredConfigs = ['DB_SERVER', 'DB_USERNAME', 'DB_NAME', 'IMAGES_PATH'];
foreach ($requiredConfigs as $config) {
    if (!isset($envConfig[$config]) || empty($envConfig[$config])) {
        header('Location: fix_config.php?error=missing_config&config=' . $config);
        exit;
    }
}

// Verificar se o caminho das imagens existe
if (!file_exists($envConfig['IMAGES_PATH'])) {
    header('Location: fix_config.php?error=invalid_images_path&path=' . urlencode($envConfig['IMAGES_PATH']));
    exit;
}

// Testar conexão com o banco de dados
try {
    $conn = new mysqli(
        $envConfig['DB_SERVER'], 
        $envConfig['DB_USERNAME'], 
        $envConfig['DB_PASSWORD'] ?? '', 
        $envConfig['DB_NAME']
    );
    
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão: " . $conn->connect_error);
    }
    
    // Fechar conexão de teste
    $conn->close();
} catch (Exception $e) {
    // Redirecionar para a página de configuração em caso de erro de conexão
    header('Location: fix_config.php?error=db_connection&message=' . urlencode($e->getMessage()));
    exit;
}

// Se chegou aqui, continua com o restante do código normalmente...

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("Il file .env non è stato trovato in: {$path}");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

$servername = getenv('DB_SERVER');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

if (!$servername || !$username || !$dbname) {
    die("Erro: Configurações de banco de dados não encontradas no arquivo .env");
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Erro na conexão: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (isset($_POST['itemId'], $_POST['label'], $_POST['limit'], $_POST['can_remove'], $_POST['type'], $_POST['usable'], $_POST['desc'])) {
        $itemId = $_POST['itemId'];
        $label = $_POST['label'];
        $limit = $_POST['limit'];
        $canRemove = $_POST['can_remove'];
        $type = $_POST['type'];
        $usable = $_POST['usable'];
        $desc = $_POST['desc'];
        
        if (empty($itemId)) {
            die("Erro: ID do item não fornecido");
        }
        
        error_log("Atualizando item: " . $itemId . " - Label: " . $label);
        
        $stmt = $conn->prepare("UPDATE items SET label = ?, `limit` = ?, can_remove = ?, type = ?, usable = ?, `desc` = ? WHERE item = ?");
        $stmt->bind_param("siissss", $label, $limit, $canRemove, $type, $usable, $desc, $itemId);
        
        if ($stmt->execute()) {
            error_log("Item atualizado com sucesso: " . $itemId);
            echo 'success';
        } else {
            error_log("Erro ao atualizar item: " . $stmt->error);
            echo 'error';
        }
    } else {
        echo 'error';
    }

    exit;
}

$sql = "SELECT item, label, `limit`, can_remove, type, usable, `desc` FROM items";
$result = $conn->query($sql);

// Após a consulta SQL, adicione um contador para itens sem imagem
$itemsWithoutImage = 0;
$possibleImagePaths = [
    "D:/ReDM-Base-DJ/server-data/resources/[vorp_resources]/vorp_inventory/html/img/",
    "D:/ReDM-Base-DJ/server-data/resources/[vorp_resources]/vorp_inventory/html/img/items/",
    "D:/ReDM-Base-DJ/server-data/resources/[vorp_resources]/vorp_inventory/img/",
    "D:/ReDM-Base-DJ/server-data/resources/[vorp_resources]/vorp_inventory/img/items/"
];

$detectedPath = "";

// Primeiro item para testar
$testItemName = null;
$testResult = $conn->query("SELECT item FROM items LIMIT 1");
if ($testResult && $testResult->num_rows > 0) {
    $testRow = $testResult->fetch_assoc();
    $testItemName = $testRow['item'];
    
    // Testar possíveis caminhos
    foreach ($possibleImagePaths as $path) {
        if (file_exists($path . $testItemName . ".png")) {
            $detectedPath = $path;
            break;
        }
    }
}

// Função para verificar se a imagem existe
function imageExists($itemName) {
    $imagePath = getenv('IMAGES_PATH') ?: "D:/ReDM-Base-DJ/server-data/resources/[vorp_resources]/vorp_inventory/html/img/items/";
    
    // Verificar diretamente no caminho configurado
    if (file_exists($imagePath . $itemName . ".png")) {
        return true;
    }
    
    // Verificar no caminho sem 'items/'
    $altPath = preg_replace('/items\/$/', '', $imagePath);
    if (file_exists($altPath . $itemName . ".png")) {
        return true;
    }
    
    return false;
}

// Função para obter o URL da imagem relativo ao seu sistema web
function getImageUrl($itemName) {
    // Esta é uma função exemplo - você precisará ajustar o caminho para corresponder ao URL correto
    // que aponta para as imagens em seu servidor web
    return "./img/{$itemName}.png";
}

// Após a consulta SQL existente, adicione:
// Caminho base para as imagens
$imagePath = getenv('IMAGES_PATH') ?: "D:/ReDM-Base-DJ/server-data/resources/[vorp_resources]/vorp_inventory/html/img/items/";
$webImagePath = "./img/items/"; // Defina o caminho web equivalente

// Criar diretório de imagens se não existir
if (!file_exists("./img/items/")) {
    mkdir("./img/items/", 0777, true);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VORP | Web Item List</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        /* Mover a barra de navegação principal para a parte inferior */
        .navbar, 
        header .navbar, 
        nav.navbar {
            position: fixed !important;
            top: auto !important;
            bottom: 100px !important; /* Posicionado acima das outras barras */
            left: 0;
            right: 0;
            z-index: 1030;
            background-color: #343a40 !important; /* Manter a cor original do navbar */
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.2);
        }
        
        /* Atualizar posições das outras barras */
        .fixed-alert {
            bottom: 50px !important;
        }
        
        .fixed-nav {
            bottom: 0 !important;
        }
        
        /* Ajustar o botão de voltar ao topo para ficar acima de todas as barras */
        #backToTop {
            bottom: 160px !important;
        }
        
        /* Aumentar o padding inferior do body para acomodar todas as barras */
        body {
            padding-bottom: 180px !important;
            padding-top: 0 !important;
        }
        
        /* Correção para evitar corte de conteúdo no topo */
        main, 
        .container,
        .container-fluid {
            padding-top: 20px;
        }
        
        /* Ajustar posição para telas pequenas/móveis */
        @media (max-width: 768px) {
            body {
                padding-bottom: 220px !important; /* Espaço adicional para telas menores */
            }
            
            #backToTop {
                bottom: 220px !important;
                right: 20px;
            }
        }
        
        .img-container {
            text-align: center;
        }
        
        .img-filename {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .no-image {
            width: 96px;
            height: 96px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
            border: 1px dashed #dee2e6;
            color: #6c757d;
            font-size: 0.9em;
            text-align: center;
        }
        
        .top-bar {
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            padding: 8px 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        body {
            padding-top: 140px; /* Espaço aumentado para acomodar os dois elementos fixos */
        }
        
        .logo {
            max-height: 40px;
            width: auto;
        }
        
        .language-selector button {
            margin-right: 5px;
            font-size: 0.8rem;
        }
        
        .language-selector button.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: bold;
        }
        
        .contact-links a {
            margin-left: 8px;
            font-size: 0.85rem;
        }
        
        .discord-btn {
            background-color: #7289DA;
            border-color: #7289DA;
        }
        
        .discord-btn:hover {
            background-color: #5b73c7;
            border-color: #5b73c7;
        }
        
        /* Estilização específica por idioma, se necessário */
        body.lang-en .only-pt,
        body.lang-es .only-pt {
            display: none;
        }
        
        body.lang-pt-br .only-en,
        body.lang-es .only-en {
            display: none;
        }
        
        body.lang-pt-br .only-es,
        body.lang-en .only-es {
            display: none;
        }
        
        /* Estilos para mensagens de alerta fixas - VERSÃO REDUZIDA */
        .fixed-alert {
            position: fixed;
            top: auto !important;
            bottom: 50px !important; /* Posicionar acima da barra de navegação */
            left: 0;
            right: 0;
            z-index: 1020;
            background-color: rgba(255, 220, 220, 0.8);
            border: none;
            margin: 0;
            padding: 5px 20px;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
        }
        
        /* Estilos para o menu de navegação fixo - VERSÃO REDUZIDA */
        .fixed-nav {
            position: fixed;
            top: auto !important;
            bottom: 0 !important;
            left: 0;
            right: 0;
            z-index: 1019;
            background-color: rgba(220, 220, 255, 0.8);
            border: none;
            margin: 0;
            padding: 5px 20px;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
        }
        
        .fixed-nav .btn {
            margin: 0 3px;
            padding: 3px 8px; /* Botões menores */
            background-color: rgba(255, 255, 255, 0.7);
            border: none; /* Sem bordas */
            color: #007bff;
            font-size: 0.85rem; /* Texto menor */
        }
        
        .fixed-nav .pagination-counter {
            background-color: #dc3545;
            color: white;
            padding: 3px 8px; /* Menor */
            border-radius: 3px;
            font-size: 0.85rem; /* Texto menor */
            display: inline-block;
            margin-left: 10px;
        }
        
        /* Ajustar o conteúdo principal para ficar abaixo do menu de navegação - MAIS ESPAÇO */
        body {
            padding-top: 140px; /* Espaço aumentado para acomodar os dois elementos fixos */
            padding-bottom: 120px !important; /* Espaço para as barras fixas no final */
            padding-left: 0 !important; /* Remover o padding do lado esquerdo */
            padding-right: 0 !important; /* Remover o padding do lado direito */
        }
        
        .container {
            margin-top: 30px; /* Espaço adicional */
        }
        
        /* Esconder a barra de busca original */
        #searchContainer {
            display: none;
        }
        
        /* Estilos melhorados para os elementos fixos */
        .fixed-nav {
            position: fixed;
            top: auto !important;
            bottom: 0 !important;
            left: 0;
            right: 0;
            z-index: 1019;
            background-color: rgba(220, 220, 255, 0.8);
            border: none;
            margin: 0;
            padding: 5px 20px;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
        }
        
        .fixed-nav .search-input {
            height: 31px;
            border-radius: 4px;
            border: 1px solid #007bff;
        }
        
        .fixed-nav .btn {
            margin: 0 3px;
            padding: 3px 8px;
            font-size: 0.85rem;
        }
        
        /* Ajuste responsivo */
        @media (max-width: 768px) {
            .fixed-nav .row {
                flex-direction: column;
            }
            
            .fixed-nav .col-auto, 
            .fixed-nav .col {
                width: 100%;
                margin-bottom: 5px;
            }
        }
        
        /* Estilo para barra de pesquisa dinâmica */
        .search-input {
            width: 150px;
            transition: width 0.3s ease-in-out;
        }
        
        .search-input:focus {
            width: 100%;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* Estilo para o botão de voltar ao topo */
        #backToTop {
            position: fixed;
            bottom: 110px !important; /* Posicionado acima das barras fixas */
            right: 30px;
            width: 50px;
            height: 50px;
            background-color: rgba(220, 53, 69, 0.8);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 50px;
            font-size: 20px;
            cursor: pointer;
            z-index: 1999;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        #backToTop.visible {
            opacity: 1;
            visibility: visible;
        }
        
        #backToTop:hover {
            background-color: #c82333;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
<div class="top-bar">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="#" class="branding">
                    <i class="fas fa-leaf text-success fa-2x"></i>
                </a>
            </div>
            <div class="col">
                <div class="language-selector">
                    <button type="button" data-lang="pt-br" class="btn btn-sm btn-outline-light active">
                        🇧🇷 Português
                    </button>
                    <button type="button" data-lang="en" class="btn btn-sm btn-outline-light">
                        🇺🇸 English
                    </button>
                    <button type="button" data-lang="es" class="btn btn-sm btn-outline-light">
                        🇪🇸 Español
                    </button>
                </div>
            </div>
            <div class="col-auto">
                <div class="contact-links">
                    <a href="https://github.com/seu-usuario-github" target="_blank" class="btn btn-sm btn-dark">
                        <i class="fab fa-github"></i> GitHub
                    </a>
                    <a href="#" class="btn btn-sm btn-primary discord-btn" data-toggle="tooltip" title="seu-usuario#1234">
                        <i class="fab fa-discord"></i> Discord
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h2 data-translate="items">Lista Items</h2>
    
    <!-- Adicionar um card de configuração -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Configurações</h5>
        </div>
        <div class="card-body">
            <a href="fix_config.php" class="btn btn-outline-info config-button">
                <i class="fas fa-cog"></i> Configurações
            </a>
            
            <hr>
            
            <?php if ($detectedPath && $detectedPath != getenv('IMAGES_PATH')): ?>
            <div class="alert alert-info mt-3">
                <strong>Caminho de imagens detectado:</strong> <?php echo $detectedPath; ?>
                <button type="button" id="useDetectedPath" class="btn btn-sm btn-outline-info ml-2">Usar este caminho</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Contador e aviso de itens sem imagem (será preenchido via JavaScript) -->
    <div class="alert alert-danger fixed-alert">
        <strong data-translate="atencao">Atenção!</strong> 
        <span data-translate="sem-imagem-count">708 items sem imagem.</span>
        <span data-translate="verificar-caminho">Verifique se o caminho das imagens está correto. Sugestão:</span>
        <code>vorp_inventory/html/img/</code>
    </div>

    <!-- Adicionar abaixo do aviso de itens sem imagem -->
    <div class="fixed-nav">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-auto navigation-buttons">
                    <button class="btn btn-sm btn-outline-primary" id="firstNoImg">« <span data-translate="primeiro-item">First item without image</span></button>
                    <button class="btn btn-sm btn-outline-primary" id="prevItem">‹ <span data-translate="anterior">Previous</span></button>
                    <button class="btn btn-sm btn-outline-primary" id="nextItem"><span data-translate="proximo">Next</span> ›</button>
                    <button class="btn btn-sm btn-outline-primary" id="lastNoImg"><span data-translate="ultimo-item">Last item without image</span> »</button>
                    <span class="pagination-counter">708 / 708</span>
                </div>
                <div class="col search-container">
                    <input type="text" class="form-control form-control-sm search-input" id="searchBox" placeholder="Search..." data-translate-placeholder="search">
                </div>
            </div>
        </div>
    </div>

    <input class="form-control mb-4" id="tableSearch" type="text" placeholder="Cerca..." data-translate-placeholder="search">
    <table class="table" id="itemsTable">
        <thead>
            <tr>
                <th>Item</th>
                <th>Label</th>
                <th>Limite</th>
                <th>Can</th>
                <th>Type</th>
                <th>Usável</th>
                <th>Descrição</th>
                <th>Imagem</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $itemName = $row["item"];
                    $imageFile = "{$itemName}.png";
                    $imageExists = file_exists($imagePath . $imageFile);
                    
                    // Se a imagem existe no local original, copie para a pasta web
                    if ($imageExists && !file_exists("./img/items/{$imageFile}")) {
                        copy($imagePath . $imageFile, "./img/items/{$imageFile}");
                    }
                ?>
                    <tr data-item-id="<?php echo $itemName; ?>">
                        <td><?php echo $itemName; ?></td>
                        <td><?php echo $row["label"]; ?></td>
                        <td><?php echo $row["limit"]; ?></td>
                        <td><?php echo $row["can_remove"]; ?></td>
                        <td><?php echo $row["type"]; ?></td>
                        <td><?php echo $row["usable"] == 1 ? 'Sim' : 'Não'; ?></td>
                        <td><?php echo $row["desc"]; ?></td>
                        <td>
                            <?php if ($imageExists): ?>
                                <div class="img-container">
                                    <img src="./img/items/<?php echo $imageFile; ?>" alt="<?php echo $itemName; ?>" width="96" height="96">
                                    <div class="img-filename"><?php echo $imageFile; ?></div>
                                    <div class="mt-2">
                                        <a href="download_image.php?file=<?php echo $imageFile; ?>" class="btn btn-sm btn-success">
                                            <i class="fa fa-download"></i> Baixar
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    Sem imagem<br>
                                    <small><?php echo $imageFile; ?></small>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-secondary prev-no-image" title="Item anterior sem imagem">
                                            <i class="fa fa-arrow-up"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary next-no-image" title="Próximo item sem imagem">
                                            <i class="fa fa-arrow-down"></i>
                                        </button>
                                        <a href="https://br.freepik.com/search?query=<?php echo urlencode($itemName); ?>" target="_blank" class="btn btn-sm btn-outline-info mt-1" title="Buscar no Freepik">
                                            <i class="fa fa-search"></i> Freepik
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm editBtn">Modificar</button>
                            <button class="btn btn-info btn-sm uploadImgBtn" data-item="<?php echo $itemName; ?>">Upload Imagem</button>
                            <button class="btn btn-success btn-sm processImgBtn" data-item="<?php echo $itemName; ?>">
                                <i class="fa fa-magic"></i> Processar Imagem
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">Nenhum dado encontrado, verifique a tabela "items" no banco de dados</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Modifica Item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editItemId" name="itemId">
                    <div class="form-group">
                        <label for="editLabel">Label:</label>
                        <input type="text" class="form-control" id="editLabel" name="label">
                    </div>
                    <div class="form-group">
                        <label for="editLimit">Limite:</label>
                        <input type="number" class="form-control" id="editLimit" name="limit">
                    </div>
                    <div class="form-group">
                        <label for="editCanRemove">Can Remove:</label>
                        <input type="number" class="form-control" id="editCanRemove" name="can_remove">
                    </div>
                    <div class="form-group">
                        <label for="editType">Type:</label>
                        <input type="text" class="form-control" id="editType" name="type">
                    </div>
                    <div class="form-group">
                        <label for="editUsable">Usável:</label>
                        <select class="form-control" id="editUsable" name="usable">
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editDesc">Descrição:</label>
                        <textarea class="form-control" id="editDesc" name="desc"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-primary" id="saveChanges">Salva</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadImageModal" tabindex="-1" role="dialog" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadImageModalLabel">Upload de Imagem</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="uploadImageForm" enctype="multipart/form-data">
                    <input type="hidden" id="uploadItemId" name="itemId">
                    <div class="form-group">
                        <label for="imageFile">Selecione uma imagem (PNG):</label>
                        <input type="file" class="form-control-file" id="imageFile" name="imageFile" accept=".png">
                    </div>
                    <div class="alert alert-info">
                        A imagem será salva como <span id="imageFileName"></span>.png
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="uploadImageBtn">Upload</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="envConfigModal" tabindex="-1" role="dialog" aria-labelledby="envConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="envConfigModalLabel">Configurar Arquivo .env</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Edite as configurações do arquivo .env. Cada linha deve seguir o formato CHAVE=VALOR.
                </div>
                
                <form id="envConfigForm">
                    <div class="form-group">
                        <textarea class="form-control" id="envConfigContent" rows="10"><?php 
                            $envPath = __DIR__ . '/.env';
                            if (file_exists($envPath)) {
                                echo htmlspecialchars(file_get_contents($envPath));
                            }
                        ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveEnvConfig">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imageProcessModal" tabindex="-1" role="dialog" aria-labelledby="imageProcessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageProcessModalLabel">Processar Imagem</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Faça upload de uma imagem, configure as opções de processamento e visualize o resultado em tempo real.
                </div>
                
                <form id="processImageForm" enctype="multipart/form-data">
                    <input type="hidden" id="processItemId" name="itemId">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="processImageFile">Selecione uma imagem:</label>
                                <input type="file" class="form-control-file" id="processImageFile" name="imageFile" accept="image/*">
                            </div>
                            
                            <div class="form-group">
                                <label>Método de Remoção de Fundo:</label>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="methodChroma" name="backgroundMethod" value="1" class="custom-control-input preview-update-trigger" checked>
                                    <label class="custom-control-label" for="methodChroma">Chroma key (detectar cor de fundo)</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="methodEdge" name="backgroundMethod" value="2" class="custom-control-input preview-update-trigger">
                                    <label class="custom-control-label" for="methodEdge">Detecção de bordas</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="methodRemoveBg" name="backgroundMethod" value="3" class="custom-control-input preview-update-trigger">
                                    <label class="custom-control-label" for="methodRemoveBg">Remove.bg (IA avançada)</label>
                                </div>
                                <div id="removebgApiKeyContainer" class="mt-2" style="display: none;">
                                    <input type="text" class="form-control form-control-sm" id="removebgApiKey" placeholder="Chave API do Remove.bg">
                                    <small class="form-text text-muted">
                                        Obtenha sua chave API gratuita em <a href="https://www.remove.bg/pt-br/api" target="_blank">remove.bg/api</a>
                                    </small>
                                </div>
                                <div class="alert alert-info mt-2 small py-1">
                                    <strong>Dica:</strong> O serviço Remove.bg oferece resultados profissionais, mas requer uma chave API.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="smoothLevel">Nível de Suavização das Bordas: <span id="smoothLevelValue">5</span></label>
                                <input type="range" class="custom-range preview-update-trigger" id="smoothLevel" name="smoothLevel" min="0" max="20" value="5">
                                <small class="form-text text-muted">
                                    <ul class="mb-0 pl-3">
                                        <li><strong>0-5:</strong> Bordas nítidas (melhor para imagens técnicas com contornos definidos)</li>
                                        <li><strong>6-12:</strong> Suavização moderada (melhor para a maioria dos objetos)</li>
                                        <li><strong>13-20:</strong> Suavização alta (melhor para imagens de plantas, árvores e objetos complexos)</li>
                                    </ul>
                                </small>
                                <div class="mt-2">
                                    <div class="alert alert-info small py-1">
                                        <i class="fa fa-lightbulb"></i> <strong>Dica:</strong> Use valores altos (15-20) para árvores e plantas para eliminar completamente as bordas brancas.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="preview-container">
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h6 class="m-0">Imagem Original</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <img id="originalPreview" src="" alt="Original" style="max-width: 100%; max-height: 200px; display: none;">
                                        <div id="noImageSelected" class="text-muted pt-3 pb-3">
                                            <i class="fa fa-image fa-3x mb-2"></i><br>
                                            Selecione uma imagem para visualizar
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="m-0">Resultado com Fundo Removido</h6>
                                    </div>
                                    <div class="card-body text-center preview-background">
                                        <img id="processedPreview" src="" alt="Processada" style="max-width: 100%; max-height: 200px; display: none;">
                                        <div id="processingIndicator" style="display: none;">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Processando...</span>
                                            </div>
                                            <p class="mt-2">Processando imagem...</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group mt-3">
                                    <label>Visualizar fundo:</label>
                                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                        <label class="btn btn-outline-secondary active">
                                            <input type="radio" name="previewBackground" value="transparent" checked> Transparente
                                        </label>
                                        <label class="btn btn-outline-secondary">
                                            <input type="radio" name="previewBackground" value="checkered"> Xadrez
                                        </label>
                                        <label class="btn btn-outline-secondary">
                                            <input type="radio" name="previewBackground" value="black"> Preto
                                        </label>
                                        <label class="btn btn-outline-secondary">
                                            <input type="radio" name="previewBackground" value="white"> Branco
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="progress" style="height: 20px;">
                            <div id="processProgress" class="progress-bar" role="progressbar" style="width: 0%;" 
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="processImageBtn">Processar e Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $(".editBtn").click(function(){
        var row = $(this).closest("tr");
        var itemId = row.data("item-id");
        
        console.log("ID do item a ser editado:", itemId);
        
        var label = row.find("td:eq(1)").text();
        var limit = row.find("td:eq(2)").text();
        var canRemove = row.find("td:eq(3)").text();
        var type = row.find("td:eq(4)").text();
        var usable = row.find("td:eq(5)").text() === "Sim" ? 1 : 0;
        var desc = row.find("td:eq(6)").text();

        $("#editLabel").val(label);
        $("#editLimit").val(limit);
        $("#editCanRemove").val(canRemove);
        $("#editType").val(type);
        $("#editUsable").val(usable);
        $("#editDesc").val(desc);

        $("#editItemId").val(itemId);
        $("#editModal").modal("show");
    });

    $("#saveChanges").click(function(){
        var label = $("#editLabel").val();
        var limit = $("#editLimit").val();
        var canRemove = $("#editCanRemove").val();
        var type = $("#editType").val();
        var usable = $("#editUsable").val();
        var desc = $("#editDesc").val();
        
        var itemId = $("#editItemId").val();
        
        console.log("Enviando atualização para o item:", itemId);

        $.ajax({
            url: window.location.href,
            method: "POST",
            data: {
                action: "update",
                itemId: itemId,
                label: label,
                limit: limit,
                can_remove: canRemove,
                type: type,
                usable: usable,
                desc: desc
            },
            success: function(response){
                console.log("Resposta do servidor:", response);
                if (response === 'success') {
                    location.reload();
                } else {
                    alert("Erro ao atualizar o item");
                }
            },
            error: function(xhr, status, error){
                console.log(xhr.responseText);
                alert("Erro de comunicação com o servidor");
            }
        });

        $("#editModal").modal("hide");
    });

    // Adicione o código para lidar com o upload de imagens
    $(".uploadImgBtn").click(function(){
        var itemId = $(this).data("item");
        $("#uploadItemId").val(itemId);
        $("#imageFileName").text(itemId);
        $("#uploadImageModal").modal("show");
    });
    
    $("#uploadImageBtn").click(function(){
        var formData = new FormData();
        var fileInput = document.getElementById('imageFile');
        var itemId = $("#uploadItemId").val();
        
        if (fileInput.files.length === 0) {
            alert("Por favor, selecione uma imagem!");
            return;
        }
        
        formData.append('action', 'uploadImage');
        formData.append('itemId', itemId);
        formData.append('imageFile', fileInput.files[0]);
        formData.append('backgroundMethod', $('input[name="backgroundMethod"]:checked').val());
        
        $.ajax({
            url: 'upload_image.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert("Imagem enviada com sucesso!");
                        location.reload();
                    } else {
                        alert("Erro ao enviar imagem: " + result.message);
                    }
                } catch (e) {
                    alert("Erro no processamento da resposta do servidor");
                    console.error(response);
                }
            },
            error: function() {
                alert("Erro na comunicação com o servidor");
            }
        });
        
        $("#uploadImageModal").modal("hide");
    });

    $("#saveConfig").click(function(){
        var imagesPath = $("#imagesPath").val();
        
        if (!imagesPath.trim()) {
            alert("Por favor, informe um caminho válido para as imagens");
            return;
        }
        
        $.ajax({
            url: 'save_config.php',
            method: 'POST',
            data: {
                action: 'saveConfig',
                imagesPath: imagesPath
            },
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert("Configurações salvas com sucesso!");
                        location.reload();
                    } else {
                        alert("Erro ao salvar configurações: " + result.message);
                    }
                } catch (e) {
                    alert("Erro ao processar resposta do servidor");
                    console.error(response);
                }
            },
            error: function() {
                alert("Erro na comunicação com o servidor");
            }
        });
    });

    // Adicionar contador de itens sem imagem
    var itemsWithoutImage = $(".no-image").length;
    if (itemsWithoutImage > 30) {
        $("#noImageCount").text(itemsWithoutImage);
        $("#imageWarning").show();
    }
    
    // Usar o caminho detectado
    $("#useDetectedPath").click(function(){
        var detectedPath = "<?php echo $detectedPath; ?>";
        $("#imagesPath").val(detectedPath);
        // Opcional: salvar automaticamente
        $("#saveConfig").click();
    });

    // Implementar navegação entre itens sem imagem
    var noImageItems = $(".no-image").closest("tr");
    var currentNoImageIndex = -1;

    if (noImageItems.length > 0) {
        // Mostrar navegação se houver itens sem imagem
        $("#imageNavigation").show();
        $("#noImageCounter").text("1 / " + noImageItems.length);
        
        // Função para scrollar até um item sem imagem
        function scrollToNoImageItem(index) {
            if (index >= 0 && index < noImageItems.length) {
                currentNoImageIndex = index;
                
                // Atualizar contador
                $("#noImageCounter").text((currentNoImageIndex + 1) + " / " + noImageItems.length);
                
                // Remover destaque de todos os itens
                $("tr").removeClass('table-danger');
                
                // Destacar o item atual
                $(noImageItems[currentNoImageIndex]).addClass('table-danger');
                
                // Scrollar até o item
                $('html, body').animate({
                    scrollTop: $(noImageItems[currentNoImageIndex]).offset().top - 100
                }, 500);
            }
        }
        
        // Botão para ir ao primeiro item sem imagem
        $("#firstNoImg").click(function() {
            scrollToNoImageItem(0);
        });
        
        // Botão para ir ao item anterior sem imagem
        $("#prevItem").click(function() {
            if (currentNoImageIndex > 0) {
                scrollToNoImageItem(currentNoImageIndex - 1);
            } else {
                scrollToNoImageItem(noImageItems.length - 1); // Circular para o último
            }
        });
        
        // Botão para ir ao próximo item sem imagem
        $("#nextItem").click(function() {
            if (currentNoImageIndex < noImageItems.length - 1) {
                scrollToNoImageItem(currentNoImageIndex + 1);
            } else {
                scrollToNoImageItem(0); // Circular para o primeiro
            }
        });
        
        // Botão para ir ao último item sem imagem
        $("#lastNoImg").click(function() {
            scrollToNoImageItem(noImageItems.length - 1);
        });
        
        // Botões dentro dos itens sem imagem
        $(".prev-no-image").click(function() {
            var row = $(this).closest("tr");
            var index = noImageItems.index(row);
            
            if (index > 0) {
                scrollToNoImageItem(index - 1);
            } else {
                scrollToNoImageItem(noImageItems.length - 1);
            }
        });
        
        $(".next-no-image").click(function() {
            var row = $(this).closest("tr");
            var index = noImageItems.index(row);
            
            if (index < noImageItems.length - 1) {
                scrollToNoImageItem(index + 1);
            } else {
                scrollToNoImageItem(0);
            }
        });
        
        // Iniciar no primeiro item sem imagem
        if (itemsWithoutImage > 0) {
            setTimeout(function() {
                scrollToNoImageItem(0);
            }, 500);
        }
    }

    // Abrir o modal de configuração do arquivo .env
    $("#editEnvFile").click(function() {
        $("#envConfigModal").modal("show");
    });

    // Salvar as configurações do arquivo .env
    $("#saveEnvConfig").click(function() {
        var envContent = $("#envConfigContent").val();
        
        // Validar o conteúdo do .env
        var lines = envContent.split('\n');
        var isValid = true;
        var invalidLines = [];
        
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();
            
            // Ignorar linhas vazias e comentários
            if (line === '' || line.startsWith('#')) {
                continue;
            }
            
            // Verificar se a linha segue o formato CHAVE=VALOR
            if (!line.includes('=')) {
                isValid = false;
                invalidLines.push((i + 1) + ': ' + line);
            }
        }
        
        if (!isValid) {
            alert("O arquivo .env contém linhas inválidas:\n" + invalidLines.join('\n') + 
                  "\n\nCada linha deve seguir o formato CHAVE=VALOR.");
            return;
        }
        
        $.ajax({
            url: 'save_env.php',
            method: 'POST',
            data: {
                action: 'saveEnv',
                envContent: envContent
            },
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert("Arquivo .env salvo com sucesso!");
                        location.reload();
                    } else {
                        alert("Erro ao salvar arquivo .env: " + result.message);
                    }
                } catch (e) {
                    alert("Erro ao processar resposta do servidor");
                    console.error(response);
                }
            },
            error: function() {
                alert("Erro na comunicação com o servidor");
            }
        });
        
        $("#envConfigModal").modal("hide");
    });

    // Script para visualização em tempo real
    $(document).ready(function() {
        // Atualizar valor do slider
        $("#smoothLevel").on("input", function() {
            $("#smoothLevelValue").text($(this).val());
        });
        
        // Alternar estilo de fundo na visualização
        $("input[name='previewBackground']").change(function() {
            const bgType = $(this).val();
            const previewContainer = $(".preview-background");
            
            previewContainer.removeClass("bg-transparent bg-checkered bg-black bg-white");
            
            switch (bgType) {
                case "transparent": 
                    previewContainer.addClass("bg-transparent"); 
                    break;
                case "checkered": 
                    previewContainer.addClass("bg-checkered"); 
                    break;
                case "black": 
                    previewContainer.addClass("bg-black"); 
                    break;
                case "white": 
                    previewContainer.addClass("bg-white"); 
                    break;
            }
        });
        
        // Função para processar a pré-visualização
        function updatePreview() {
            const fileInput = document.getElementById('processImageFile');
            if (!fileInput.files || fileInput.files.length === 0) {
                return;
            }
            
            // Exibir indicador de processamento
            $("#processingIndicator").show();
            $("#processedPreview").hide();
            
            // Preparar dados
            const formData = new FormData();
            formData.append('action', 'previewProcessing');
            formData.append('image', fileInput.files[0]);
            formData.append('method', $('input[name="backgroundMethod"]:checked').val());
            formData.append('smoothLevel', $('#smoothLevel').val());
            
            // Adicionar a chave API se o método for remove.bg
            if ($('input[name="backgroundMethod"]:checked').val() == '3') {
                formData.append('removebgApiKey', $('#removebgApiKey').val());
            }
            
            // Enviar para processamento
            $.ajax({
                url: 'process_image.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // Atualizar a imagem processada
                            $("#processedPreview").attr('src', result.dataUrl).show();
                        } else {
                            console.error("Erro ao processar preview:", result.error);
                        }
                    } catch (e) {
                        console.error("Erro ao processar resposta:", e);
                    }
                    
                    // Esconder indicador de processamento
                    $("#processingIndicator").hide();
                },
                error: function() {
                    alert("Erro na comunicação com o servidor");
                    $("#processingIndicator").hide();
                }
            });
        }
        
        // Exibir imagem original ao selecionar arquivo
        $("#processImageFile").change(function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $("#originalPreview").attr('src', e.target.result).show();
                    $("#noImageSelected").hide();
                    
                    // Atualizar preview da imagem processada
                    updatePreview();
                }
                reader.readAsDataURL(file);
            } else {
                $("#originalPreview").hide();
                $("#processedPreview").hide();
                $("#noImageSelected").show();
            }
        });
        
        // Atualizar preview quando as opções mudam
        $(".preview-update-trigger").change(function() {
            if ($("#originalPreview").is(":visible")) {
                updatePreview();
            }
        });
        
        // CSS para o fundo xadrez
        $("<style>")
            .prop("type", "text/css")
            .html(`
                .bg-transparent { background: transparent; }
                .bg-black { background: #000; }
                .bg-white { background: #fff; }
                .bg-checkered {
                    background-image: 
                        linear-gradient(45deg, #ccc 25%, transparent 25%), 
                        linear-gradient(-45deg, #ccc 25%, transparent 25%),
                        linear-gradient(45deg, transparent 75%, #ccc 75%),
                        linear-gradient(-45deg, transparent 75%, #ccc 75%);
                    background-size: 20px 20px;
                    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
                }
                .preview-container .card-body {
                    min-height: 220px;
                }
            `)
            .appendTo("head");
        
        // Processar e salvar a imagem final
        $("#processImageBtn").click(function() {
            const formData = new FormData();
            const fileInput = document.getElementById('processImageFile');
            const itemId = $("#processItemId").val();
            
            if (fileInput.files.length === 0) {
                alert("Por favor, selecione uma imagem!");
                return;
            }
            
            formData.append('action', 'processImage');
            formData.append('itemId', itemId);
            formData.append('imageFile', fileInput.files[0]);
            formData.append('removeBackground', '1');
            formData.append('backgroundMethod', $('input[name="backgroundMethod"]:checked').val());
            formData.append('smoothLevel', $('#smoothLevel').val());
            
            // Adicionar a chave API se o método for remove.bg
            if ($('input[name="backgroundMethod"]:checked').val() == '3') {
                formData.append('removebgApiKey', $('#removebgApiKey').val());
            }
            
            // Iniciar o upload com barra de progresso
            $.ajax({
                url: 'process_image.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = (evt.loaded / evt.total) * 100;
                            $("#processProgress").css("width", percentComplete + "%").text(Math.round(percentComplete) + "%");
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    try {
                        var result = JSON.parse(response);
                        if (result.success) {
                            alert("Imagem processada com sucesso!");
                            location.reload();
                        } else {
                            alert("Erro ao processar imagem: " + result.message);
                        }
                    } catch (e) {
                        alert("Erro no processamento da resposta do servidor");
                        console.error(response);
                    }
                },
                error: function() {
                    alert("Erro na comunicação com o servidor");
                }
            });
        });
    });
});

</script>

<script>
$(document).ready(function(){
  $("#tableSearch").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#itemsTable tbody tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
// Adicionar a funcionalidade de busca aqui
$(document).ready(function(){
  $("#tableSearch").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#itemsTable tbody tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });
  
  // Adicionando log para depuração dos botões da tabela
  $(".processImgBtn").on("click", function(e) {
    console.log("Botão de processar imagem clicado!");
    var itemId = $(this).data("item");
    console.log("Item ID:", itemId);
    $("#processItemId").val(itemId);
    $("#imageProcessModal").modal("show");
    $("#previewContainer").hide();
    $("#processImageFile").val("");
    $("#processProgress").css("width", "0%").text("0%");
  });
});
</script>

<script>
$(document).ready(function() {
    // Mostrar/esconder campo de API key quando o método remove.bg for selecionado
    $('input[name="backgroundMethod"]').change(function() {
        if ($(this).val() == '3') {
            $('#removebgApiKeyContainer').slideDown();
        } else {
            $('#removebgApiKeyContainer').slideUp();
        }
    });
    
    // Atualizar o botão de processamento para incluir a chave API
    $("#processImageBtn").click(function() {
        const formData = new FormData();
        const fileInput = document.getElementById('processImageFile');
        const itemId = $("#processItemId").val();
        
        if (fileInput.files.length === 0) {
            alert("Por favor, selecione uma imagem!");
            return;
        }
        
        formData.append('action', 'processImage');
        formData.append('itemId', itemId);
        formData.append('imageFile', fileInput.files[0]);
        formData.append('removeBackground', '1');
        formData.append('backgroundMethod', $('input[name="backgroundMethod"]:checked').val());
        formData.append('smoothLevel', $('#smoothLevel').val());
        
        // Adicionar a chave API se o método for remove.bg
        if ($('input[name="backgroundMethod"]:checked').val() == '3') {
            formData.append('removebgApiKey', $('#removebgApiKey').val());
        }
        
        // Resto do código AJAX permanece o mesmo
        // ...
    });
    
    // Também adicionar a chave API no preview
    function updatePreview() {
        // ... código existente ...
        
        // Adicionar a chave API se o método for remove.bg
        if ($('input[name="backgroundMethod"]:checked').val() == '3') {
            formData.append('removebgApiKey', $('#removebgApiKey').val());
        }
        
        // Resto do código permanece o mesmo
        // ...
    }
});
</script>

<script src="translator.js"></script>

<script>
// Aplicar traduções a elementos específicos da página
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar atributos data-translate para elementos específicos
    const elementsToTranslate = [
        { selector: 'h2:contains("Lista Items")', key: 'lista-items' },
        { selector: '.nav-link:contains("Configurações")', key: 'configuracoes' },
        { selector: 'label:contains("Caminho das Imagens:")', key: 'caminho-imagens' },
        { selector: 'button:contains("Salvar Configurações")', key: 'salvar-configuracoes' },
        { selector: 'button:contains("Configurar Arquivo .env")', key: 'configure-env' },
        { selector: 'strong:contains("Atenção!")', key: 'atencao' },
        { selector: 'th:contains("Item")', key: 'item' },
        { selector: 'th:contains("Label")', key: 'label' },
        { selector: 'th:contains("Limite")', key: 'limite' },
        { selector: 'th:contains("Ações")', key: 'acoes' },
        { selector: 'button:contains("Modificar")', key: 'modificar' },
        
        // Elementos marcados em amarelo
        { selector: '.nav-tabs .nav-link:contains("Configurações")', key: 'configuracoes-tab' },
        { selector: '.text-danger:contains("sem imagem")', key: 'sem-imagem' },
        { selector: 'button:contains("Primeiro item sem imagem")', key: 'primeiro-item' },
        { selector: 'button:contains("Anterior")', key: 'anterior' },
        { selector: 'button:contains("Próximo")', key: 'proximo' },
        { selector: 'button:contains("Último item sem imagem")', key: 'ultimo-item' },
        { selector: '.alert:contains("Verifique se o caminho das imagens está correto")', key: 'verificar-caminho' },
        { selector: '.btn:contains("Baixar")', key: 'baixar-btn' },
        { selector: 'button.btn:contains("Baixar")', key: 'baixar-btn' },
        { selector: 'a.btn:contains("Baixar")', key: 'baixar-btn' },
        { selector: 'button:contains("Processar Imagem")', key: 'processar-imagem' },
        { selector: 'li.nav-item a.nav-link:contains("Configurações")', key: 'config-tab' },
        { selector: 'th:contains("Can")', key: 'can' },
        { selector: 'th:contains("Remove")', key: 'remove' },
        { selector: 'th:contains("Type")', key: 'type' },
        { selector: 'th:contains("Usável")', key: 'usavel' },
        { selector: 'th:contains("Descrição")', key: 'descricao' },
        { selector: 'th:contains("Imagem"):not([data-translate])', key: 'imagem-col' }
    ];
    
    // Função auxiliar para encontrar elementos por texto
    jQuery.expr[':'].contains = function(a, i, m) {
        return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    };
    
    // Aplicar atributos data-translate
    elementsToTranslate.forEach(item => {
        $(item.selector).attr('data-translate', item.key);
    });
    
    // Alguns elementos precisam de tratamento especial devido ao HTML interno
    $('button:contains("Primeiro item sem imagem")').html('&#171; <span data-translate="primeiro-item">Primeiro item sem imagem</span>');
    $('button:contains("Anterior")').html('&#8249; <span data-translate="anterior">Anterior</span>');
    $('button:contains("Próximo")').html('<span data-translate="proximo">Próximo</span> &#8250;');
    $('button:contains("Último item sem imagem")').html('<span data-translate="ultimo-item">Último item sem imagem</span> &#187;');
    
    // Para elementos compostos com múltiplos textos (como o aviso)
    $('.alert-danger').each(function() {
        const text = $(this).text();
        if (text.includes('Verifique se o caminho')) {
            // Extrair o caminho sugerido
            const suggestion = text.match(/Sugestão:\s*(.*)/);
            const suggestedPath = suggestion ? suggestion[1].trim() : '';
            
            // Adicionar classe para fixar o alerta
            $(this).addClass('fixed-alert');
            
            // Reconstruir o alerta preservando o caminho sugerido
            $(this).html('<strong data-translate="atencao">Atenção!</strong> ' + 
                        '<span data-translate="sem-imagem-count">708 items sem imagem.</span> ' +
                        '<span data-translate="verificar-caminho">Verifique se o caminho das imagens está correto. Sugestão:</span> ' + 
                        '<code>' + suggestedPath + '</code>');
        }
    });
    
    // Adicione traduções específicas para partes do alerta
    translator.addTranslations({
        'sem-imagem-count': {
            'pt-br': '708 items sem imagem.',
            'en': '708 items without images.',
            'es': '708 artículos sin imagen.'
        }
    });
    
    // Inicializar o tradutor novamente para pegar os novos elementos
    translator.registerAllElements();
    translator.updateAllElements();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para clonar handlers de eventos
    function cloneButtonHandlers(originalSelector, newSelector) {
        const originalButton = document.querySelector(originalSelector);
        const newButton = document.querySelector(newSelector);
        
        if (originalButton && newButton) {
            // Copiar o onclick event
            if (originalButton.onclick) {
                newButton.onclick = originalButton.onclick;
            }
            
            // Copiar todos os event listeners usando jQuery se estiver disponível
            if (typeof $ !== 'undefined') {
                const events = $._data(originalButton, 'events');
                if (events) {
                    Object.keys(events).forEach(function(event) {
                        events[event].forEach(function(eventObj) {
                            $(newButton).on(event, eventObj.handler);
                        });
                    });
                }
            }
            
            // Adicionar evento de clique que dispara o clique no botão original
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                originalButton.click();
            });
        }
    }
    
    // Clonar os handlers para os novos botões
    cloneButtonHandlers('button:contains("Primeiro item sem imagem")', '#firstNoImg');
    cloneButtonHandlers('button:contains("Anterior")', '#prevItem');
    cloneButtonHandlers('button:contains("Próximo")', '#nextItem');
    cloneButtonHandlers('button:contains("Último item sem imagem")', '#lastNoImg');
    
    // Sincronizar a barra de pesquisa nova com a original
    const originalSearch = document.querySelector('#searchContainer input');
    const newSearch = document.querySelector('.search-input');
    
    if (originalSearch && newSearch) {
        // Sincronizar valores
        newSearch.value = originalSearch.value;
        
        // Sincronizar eventos de busca
        newSearch.addEventListener('input', function() {
            originalSearch.value = this.value;
            
            // Disparar evento de input no campo original
            const inputEvent = new Event('input', { bubbles: true });
            originalSearch.dispatchEvent(inputEvent);
            
            // Se houver algum evento de keyup registrado
            const keyupEvent = new KeyboardEvent('keyup', { bubbles: true });
            originalSearch.dispatchEvent(keyupEvent);
        });
    }
    
    // Botão de voltar ao topo
    const backToTopBtn = document.getElementById('backToTop');
    
    // Função para mostrar/ocultar o botão com base na posição de rolagem
    function toggleBackToTopButton() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    }
    
    // Adicionar listener para o evento de scroll
    window.addEventListener('scroll', toggleBackToTopButton);
    
    // Botão de voltar ao topo - adicionar evento de clique
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Verificar posição inicial de scroll
    toggleBackToTopButton();
});
</script>

<!-- Adicione o botão de voltar ao topo ao final do body -->
<div id="backToTop" title="Voltar ao topo">
    <i class="fas fa-arrow-up"></i>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Forçar o scroll para o topo ao carregar a página
    window.scrollTo(0, 0);
    
    // Verificar se há um hash na URL (como #primeiro-item-sem-imagem)
    if (window.location.hash) {
        // Remover o hash para evitar o scroll automático
        history.replaceState(null, null, ' ');
    }
    
    // Referência ao botão de voltar ao topo
    const backToTopBtn = document.getElementById('backToTop');
    
    // Função para mostrar/ocultar o botão com base na posição do scroll
    function toggleBackToTopButton() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    }
    
    // Adicionar listener para o evento de scroll
    window.addEventListener('scroll', toggleBackToTopButton);
    
    // Botão de voltar ao topo - adicionar evento de clique
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Verificar posição inicial de scroll
    toggleBackToTopButton();
});
</script>

<!-- Opcional: Script para garantir que o conteúdo seja ajustado corretamente -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Certificar que o body tenha o padding correto
    document.body.style.paddingTop = '0';
    document.body.style.paddingBottom = '180px';
    
    // Recalcular posições ao redimensionar
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            document.body.style.paddingBottom = '220px';
        } else {
            document.body.style.paddingBottom = '180px';
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Encontrar e remover o botão de configuração
    const configButton = document.querySelector('button:contains("Configurar Arquivo .env Completo")');
    if (configButton) {
        configButton.parentElement.removeChild(configButton);
    }
    
    // Método alternativo para encontrar o botão por classe ou aparência
    const grayButtons = document.querySelectorAll('.btn-secondary');
    grayButtons.forEach(button => {
        if (button.textContent.includes('Configurar') && button.textContent.includes('env')) {
            button.parentElement.removeChild(button);
        }
    });
});
</script>

<!-- Adicionar este script para restaurar a funcionalidade da barra de pesquisa -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referência às barras de pesquisa
    const origSearch = document.querySelector('#searchContainer input[type="text"]') || document.querySelector('#searchContainer input');
    const newSearch = document.getElementById('searchBox');
    
    // Função para sincronizar a filtragem
    function syncSearchAndFilter() {
        if (origSearch && newSearch) {
            // Verificar se existe uma função de filtro na página
            const filterFunction = window.filterItems || window.filterTable || window.filter;
            
            // Sincronizar o valor da barra de pesquisa original com a nova
            origSearch.value = newSearch.value;
            
            // Disparar eventos no campo original para ativar a filtragem
            const events = ['input', 'keyup', 'change'];
            events.forEach(eventType => {
                const event = new Event(eventType, { bubbles: true });
                origSearch.dispatchEvent(event);
            });
            
            // Se encontrou uma função de filtro, chamá-la diretamente
            if (typeof filterFunction === 'function') {
                filterFunction();
            } else {
                // Método alternativo: simular pressionamento de tecla
                const keyEvent = new KeyboardEvent('keyup', {
                    key: 'a',
                    code: 'KeyA',
                    bubbles: true
                });
                origSearch.dispatchEvent(keyEvent);
                
                // Tentar acionar o evento de jquery se estiver disponível
                if (typeof $ !== 'undefined') {
                    $(origSearch).trigger('input');
                    $(origSearch).trigger('keyup');
                }
            }
        }
    }
    
    // Adicionar evento ao campo de pesquisa novo
    if (newSearch) {
        // Quando o usuário digita, sincroniza e filtra
        newSearch.addEventListener('input', syncSearchAndFilter);
        
        // Também quando o campo recebe foco, sincroniza valores
        newSearch.addEventListener('focus', function() {
            if (origSearch) {
                newSearch.value = origSearch.value;
            }
        });
    }
    
    // Adicionar um evento de detecção para inputs dinamicamente criados
    document.addEventListener('input', function(e) {
        // Verificar se é um campo de pesquisa
        if (e.target.id === 'searchBox' || 
            (e.target.closest('#searchContainer') && e.target.tagName === 'INPUT')) {
            
            // Atualizar referências
            const origSearch = document.querySelector('#searchContainer input[type="text"]') || 
                              document.querySelector('#searchContainer input');
            const newSearch = document.getElementById('searchBox');
            
            // Sincronizar os valores em ambas as direções
            if (e.target.closest('#searchContainer')) {
                // Input original ativado, sincronizar com o novo
                if (newSearch) newSearch.value = origSearch.value;
            } else if (e.target.id === 'searchBox') {
                // Novo input ativado, sincronizar com o original
                if (origSearch) origSearch.value = newSearch.value;
                
                // Disparar evento no original
                if (origSearch) {
                    const inputEvent = new Event('input', { bubbles: true });
                    origSearch.dispatchEvent(inputEvent);
                }
            }
        }
    });
    
    // Implementação direta de um filtro universal caso o original não funcione
    window.universalFilter = function() {
        const searchValue = (newSearch ? newSearch.value : origSearch.value).toLowerCase();
        const rows = document.querySelectorAll('table tr:not(.table-header)');
        
        rows.forEach(row => {
            const textContent = row.textContent.toLowerCase();
            if (textContent.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    };
    
    // Temporizador para verificar periodicamente a sincronização
    // Isso ajuda quando os elementos são carregados dinamicamente
    setTimeout(function checkSearchInputs() {
        const origSearch = document.querySelector('#searchContainer input[type="text"]') || 
                         document.querySelector('#searchContainer input');
        const newSearch = document.getElementById('searchBox');
        
        if (origSearch && newSearch && origSearch.value !== newSearch.value) {
            // Sincronizar valores
            newSearch.value = origSearch.value;
        }
        
        // Verificar novamente em 2 segundos
        setTimeout(checkSearchInputs, 2000);
    }, 2000);
});
</script>

<!-- Adicionar este script para garantir a mesma funcionalidade nas duas barras de pesquisa -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função de filtro unificada
    function filterTable(searchValue) {
        // Converter para minúsculas para pesquisa sem distinção entre maiúsculas e minúsculas
        searchValue = searchValue.toLowerCase();
        
        // Selecionar todas as linhas da tabela (exceto cabeçalhos)
        const rows = document.querySelectorAll('table tr:not(.table-header):not(th)');
        
        // Contar itens visíveis e total
        let visibleCount = 0;
        const totalRows = rows.length;
        
        // Filtrar as linhas
        rows.forEach(row => {
            // Obter o texto completo da linha
            const textContent = row.textContent.toLowerCase();
            
            // Se o texto da linha contém o valor de pesquisa, mostrar a linha
            if (textContent.includes(searchValue)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Atualizar contador de resultados (se existir)
        const countDisplay = document.getElementById('resultCount');
        if (countDisplay) {
            countDisplay.textContent = `${visibleCount} de ${totalRows} itens`;
        }
    }
    
    // Identificar as barras de pesquisa
    const mainSearch = document.querySelector('#searchContainer input') || 
                      document.querySelector('input[type="search"]') ||
                      document.querySelector('input[placeholder*="Pesquisar"]');
    
    const navbarSearch = document.getElementById('searchBox');
    
    // Adicionar a função de filtro na janela global para que esteja disponível em qualquer lugar
    window.filterTableItems = filterTable;
    
    // Função para sincronizar valores e acionar o filtro
    function syncAndFilter(value) {
        // Atualizar ambos os campos com o mesmo valor
        if (mainSearch) mainSearch.value = value;
        if (navbarSearch) navbarSearch.value = value;
        
        // Aplicar o filtro
        filterTable(value);
    }
    
    // Adicionar listeners aos campos de pesquisa
    if (mainSearch) {
        mainSearch.addEventListener('input', function() {
            syncAndFilter(this.value);
        });
    }
    
    if (navbarSearch) {
        navbarSearch.addEventListener('input', function() {
            syncAndFilter(this.value);
        });
    }
    
    // Verificar se há um valor inicial em algum dos campos
    const initialValue = (mainSearch && mainSearch.value) || 
                        (navbarSearch && navbarSearch.value) || 
                        '';
    
    // Inicializar os campos com o valor inicial
    syncAndFilter(initialValue);
    
    // Examinar a página para encontrar qualquer função de filtro existente
    // e substituí-la pela nossa implementação unificada
    if (typeof window.filter === 'function') window.filter = filterTable;
    if (typeof window.filterItems === 'function') window.filterItems = filterTable;
    if (typeof window.filterTable === 'function') window.filterTable = filterTable;
    
    // Adicionar um ouvinte de evento global para capturar qualquer entrada em campos de pesquisa
    document.addEventListener('input', function(e) {
        if (e.target.type === 'text' || e.target.type === 'search') {
            // Verificar se o elemento de entrada está relacionado à pesquisa
            if (e.target.id === 'searchBox' || 
                e.target.placeholder && e.target.placeholder.toLowerCase().includes('pesq') ||
                e.target.closest('#searchContainer') ||
                e.target.classList.contains('search-input')) {
                
                syncAndFilter(e.target.value);
            }
        }
    });
});
</script>
</body>
</html>
