<?php
// Configuração de idioma
$availableLanguages = [
    'pt-br' => 'Português',
    'en' => 'English',
    'es' => 'Español'
];

// Detecção do idioma atual (a partir de cookie, sessão ou parâmetro)
$currentLanguage = 'pt-br'; // Idioma padrão
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $availableLanguages)) {
    $currentLanguage = $_GET['lang'];
    // Definir cookie para lembrar a preferência
    setcookie('lang', $currentLanguage, time() + (86400 * 30), "/"); // Cookie válido por 30 dias
} elseif (isset($_COOKIE['lang']) && array_key_exists($_COOKIE['lang'], $availableLanguages)) {
    $currentLanguage = $_COOKIE['lang'];
}

// Traduções
$translations = [
    'title' => [
        'pt-br' => 'Correção de Configuração',
        'en' => 'Configuration Fix',
        'es' => 'Corrección de Configuración'
    ],
    'config_saved' => [
        'pt-br' => 'Configuração salva com sucesso!',
        'en' => 'Configuration saved successfully!',
        'es' => '¡Configuración guardada con éxito!'
    ],
    'save_error' => [
        'pt-br' => 'Erro ao salvar o arquivo de configuração!',
        'en' => 'Error saving the configuration file!',
        'es' => '¡Error al guardar el archivo de configuración!'
    ],
    'folder_created' => [
        'pt-br' => 'Pasta local para imagens criada com sucesso!',
        'en' => 'Local folder for images created successfully!',
        'es' => '¡Carpeta local para imágenes creada con éxito!'
    ],
    'folder_error' => [
        'pt-br' => 'Erro ao criar pasta local para imagens!',
        'en' => 'Error creating local folder for images!',
        'es' => '¡Error al crear carpeta local para imágenes!'
    ],
    'folder_exists' => [
        'pt-br' => 'A pasta local para imagens já existe! Foi configurada no arquivo .env.',
        'en' => 'The local folder for images already exists! It was configured in the .env file.',
        'es' => '¡La carpeta local para imágenes ya existe! Se configuró en el archivo .env.'
    ],
    'issues_detected' => [
        'pt-br' => 'Problemas detectados:',
        'en' => 'Issues detected:',
        'es' => 'Problemas detectados:'
    ],
    'env_not_found' => [
        'pt-br' => 'O arquivo .env não foi encontrado. Por favor, crie-o.',
        'en' => 'The .env file was not found. Please create it.',
        'es' => 'No se encontró el archivo .env. Por favor, créelo.'
    ],
    'invalid_env' => [
        'pt-br' => 'O arquivo .env está mal formatado. Verifique a sintaxe.',
        'en' => 'The .env file is malformed. Check the syntax.',
        'es' => 'El archivo .env tiene formato incorrecto. Verifique la sintaxis.'
    ],
    'missing_config' => [
        'pt-br' => 'A configuração %s está ausente ou vazia.',
        'en' => 'The %s configuration is missing or empty.',
        'es' => 'La configuración %s falta o está vacía.'
    ],
    'invalid_path' => [
        'pt-br' => 'O diretório de imagens especificado não existe: %s',
        'en' => 'The specified image directory does not exist: %s',
        'es' => 'El directorio de imágenes especificado no existe: %s'
    ],
    'db_error' => [
        'pt-br' => 'Erro na conexão com o banco de dados: %s',
        'en' => 'Database connection error: %s',
        'es' => 'Error de conexión a la base de datos: %s'
    ],
    'how_to_solve' => [
        'pt-br' => 'Como resolver problemas de configuração:',
        'en' => 'How to solve configuration issues:',
        'es' => 'Cómo resolver problemas de configuración:'
    ],
    'database_check' => [
        'pt-br' => 'Banco de dados: Verifique se o usuário e senha do MySQL estão corretos',
        'en' => 'Database: Check if MySQL username and password are correct',
        'es' => 'Base de datos: Verifique si el nombre de usuario y la contraseña de MySQL son correctos'
    ],
    'images_path_check' => [
        'pt-br' => 'Caminho das imagens: O diretório deve existir no sistema',
        'en' => 'Images path: The directory must exist in the system',
        'es' => 'Ruta de imágenes: El directorio debe existir en el sistema'
    ],
    'format_check' => [
        'pt-br' => 'Formato: Cada linha deve ser CHAVE=valor sem espaços extras',
        'en' => 'Format: Each line must be KEY=value without extra spaces',
        'es' => 'Formato: Cada línea debe ser CLAVE=valor sin espacios adicionales'
    ],
    'quick_solution' => [
        'pt-br' => 'Solução rápida: Use o botão "Criar pasta local" para configurar automaticamente',
        'en' => 'Quick solution: Use the "Create Local Folder" button to configure automatically',
        'es' => 'Solución rápida: Use el botón "Crear carpeta local" para configurar automáticamente'
    ],
    'env_missing_alert' => [
        'pt-br' => 'O arquivo de configuração .env não foi encontrado. Você pode criá-lo agora:',
        'en' => 'The .env configuration file was not found. You can create it now:',
        'es' => 'No se encontró el archivo de configuración .env. Puede crearlo ahora:'
    ],
    'config_template' => [
        'pt-br' => 'Modelo de configuração:',
        'en' => 'Configuration template:',
        'es' => 'Plantilla de configuración:'
    ],
    'edit_env' => [
        'pt-br' => 'Editar arquivo .env',
        'en' => 'Edit .env file',
        'es' => 'Editar archivo .env'
    ],
    'edit_instructions' => [
        'pt-br' => 'Edite a configuração acima e clique em Salvar para corrigir os problemas.',
        'en' => 'Edit the configuration above and click Save to fix the problems.',
        'es' => 'Edite la configuración anterior y haga clic en Guardar para solucionar los problemas.'
    ],
    'suggested_paths' => [
        'pt-br' => 'Caminhos sugeridos para imagens:',
        'en' => 'Suggested paths for images:',
        'es' => 'Rutas sugeridas para imágenes:'
    ],
    'exists' => [
        'pt-br' => 'Existe',
        'en' => 'Exists',
        'es' => 'Existe'
    ],
    'not_exists' => [
        'pt-br' => 'Não existe',
        'en' => 'Does not exist',
        'es' => 'No existe'
    ],
    'use_btn' => [
        'pt-br' => 'Usar',
        'en' => 'Use',
        'es' => 'Usar'
    ],
    'available_databases' => [
        'pt-br' => 'Bancos de dados disponíveis:',
        'en' => 'Available databases:',
        'es' => 'Bases de datos disponibles:'
    ],
    'save_config' => [
        'pt-br' => 'Salvar Configuração',
        'en' => 'Save Configuration',
        'es' => 'Guardar Configuración'
    ],
    'create_folder' => [
        'pt-br' => 'Criar Pasta Local',
        'en' => 'Create Local Folder',
        'es' => 'Crear Carpeta Local'
    ],
    'try_interface' => [
        'pt-br' => 'Tentar Acessar a Interface',
        'en' => 'Try Accessing the Interface',
        'es' => 'Intentar Acceder a la Interfaz'
    ],
    'tips' => [
        'pt-br' => 'Dicas e Soluções Comuns',
        'en' => 'Tips and Common Solutions',
        'es' => 'Consejos y Soluciones Comunes'
    ],
    'mysql_error' => [
        'pt-br' => 'Erro de conexão com MySQL:',
        'en' => 'MySQL Connection Error:',
        'es' => 'Error de conexión MySQL:'
    ],
    'mysql_tip1' => [
        'pt-br' => 'Verifique se o MySQL está rodando no servidor',
        'en' => 'Verify that MySQL is running on the server',
        'es' => 'Verifique que MySQL esté ejecutándose en el servidor'
    ],
    'mysql_tip2' => [
        'pt-br' => 'Para XAMPP/WAMP, a senha geralmente é vazia (deixe DB_PASSWORD=)',
        'en' => 'For XAMPP/WAMP, the password is usually empty (leave DB_PASSWORD=)',
        'es' => 'Para XAMPP/WAMP, la contraseña suele estar vacía (deje DB_PASSWORD=)'
    ],
    'mysql_tip3' => [
        'pt-br' => 'Para usuário root com senha definida, atualize DB_PASSWORD=SuaSenha',
        'en' => 'For root user with a defined password, update DB_PASSWORD=YourPassword',
        'es' => 'Para el usuario root con contraseña definida, actualice DB_PASSWORD=SuContraseña'
    ],
    'images_path_title' => [
        'pt-br' => 'Caminho das imagens:',
        'en' => 'Images path:',
        'es' => 'Ruta de imágenes:'
    ],
    'path_tip1' => [
        'pt-br' => 'Use barras normais (/) em vez de barras invertidas, mesmo no Windows',
        'en' => 'Use forward slashes (/) instead of backslashes, even on Windows',
        'es' => 'Use barras normales (/) en lugar de barras invertidas, incluso en Windows'
    ],
    'path_tip2' => [
        'pt-br' => 'O caminho deve apontar para uma pasta existente',
        'en' => 'The path must point to an existing folder',
        'es' => 'La ruta debe apuntar a una carpeta existente'
    ],
    'path_tip3' => [
        'pt-br' => 'A pasta img/items/ local é a opção mais segura',
        'en' => 'The local img/items/ folder is the safest option',
        'es' => 'La carpeta local img/items/ es la opción más segura'
    ],
    'path_tip4' => [
        'pt-br' => 'Para RedM/VORP, o caminho comum é resources/[vorp_resources]/vorp_inventory/html/img/items/',
        'en' => 'For RedM/VORP, the common path is resources/[vorp_resources]/vorp_inventory/html/img/items/',
        'es' => 'Para RedM/VORP, la ruta común es resources/[vorp_resources]/vorp_inventory/html/img/items/'
    ],
    'permissions' => [
        'pt-br' => 'Permissões:',
        'en' => 'Permissions:',
        'es' => 'Permisos:'
    ],
    'perm_tip1' => [
        'pt-br' => 'Certifique-se de que o arquivo .env tenha permissões de leitura/escrita',
        'en' => 'Make sure the .env file has read/write permissions',
        'es' => 'Asegúrese de que el archivo .env tenga permisos de lectura/escritura'
    ],
    'perm_tip2' => [
        'pt-br' => 'A pasta de imagens deve ter permissão para receber arquivos',
        'en' => 'The images folder must have permission to receive files',
        'es' => 'La carpeta de imágenes debe tener permiso para recibir archivos'
    ],
    'language' => [
        'pt-br' => 'Idioma:',
        'en' => 'Language:',
        'es' => 'Idioma:'
    ]
];

// Função para obter texto traduzido
function __($key, $params = []) {
    global $translations, $currentLanguage;
    
    if (isset($translations[$key][$currentLanguage])) {
        $text = $translations[$key][$currentLanguage];
        // Substituir parâmetros se existirem
        if (!empty($params)) {
            $text = vsprintf($text, $params);
        }
        return $text;
    }
    
    // Fallback para inglês
    if (isset($translations[$key]['en'])) {
        $text = $translations[$key]['en'];
        if (!empty($params)) {
            $text = vsprintf($text, $params);
        }
        return $text;
    }
    
    // Se não encontrar, retornar a chave
    return $key;
}

// Verificar se o arquivo .env existe
$envFile = __DIR__ . '/.env';
$envExists = file_exists($envFile);
$envContent = '';
$saveMessage = '';
$saveStatus = '';
$suggestedPaths = [];

// Detectar automaticamente possíveis caminhos para as imagens
function detectPossibleImagePaths() {
    $paths = [];
    
    // Caminhos comuns para servidores XAMPP/WAMP
    $serverRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $parentDir = dirname($serverRoot);
    
    // Para ambiente Windows
    $driveLetter = substr(__DIR__, 0, 2); // Exemplo: C:
    
    // Adicionar possíveis caminhos
    $paths[] = $driveLetter . '/ReDM-Base-DJ/server-data/resources/[vorp_resources]/vorp_inventory/html/img/items/';
    $paths[] = $driveLetter . '/server-data/resources/[vorp_resources]/vorp_inventory/html/img/items/';
    $paths[] = __DIR__ . '/img/items/';
    $paths[] = $serverRoot . '/vorp_inventory/html/img/items/';
    
    // Verificar se os diretórios existem
    foreach ($paths as $key => $path) {
        if (!file_exists($path)) {
            // Tente criar o diretório local de imagens se não existir
            if ($path === __DIR__ . '/img/items/') {
                if (!file_exists(__DIR__ . '/img')) {
                    mkdir(__DIR__ . '/img', 0777, true);
                }
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
            }
        }
    }
    
    return $paths;
}

// Função para tentar detectar o banco de dados MySQL no servidor local
function detectDatabases() {
    $databases = [];
    
    try {
        // Testar conexão com MySQL sem especificar banco de dados
        $conn = new mysqli('localhost', 'root', '');
        
        if (!$conn->connect_error) {
            $result = $conn->query("SHOW DATABASES");
            
            if ($result) {
                while ($row = $result->fetch_array()) {
                    $dbname = $row[0];
                    // Ignorar bancos de dados do sistema
                    if (!in_array($dbname, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                        $databases[] = $dbname;
                    }
                }
            }
            
            $conn->close();
        }
    } catch (Exception $e) {
        // Silenciar erros - apenas retornar uma lista vazia
    }
    
    return $databases;
}

// Se o arquivo existe, carregar o conteúdo
if ($envExists) {
    $envContent = file_get_contents($envFile);
}

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit'])) {
        $newEnvContent = $_POST['env_content'];
        
        // Salvar o arquivo
        if (file_put_contents($envFile, $newEnvContent) !== false) {
            $saveMessage = 'Configuração salva com sucesso!';
            $saveStatus = 'success';
            $envContent = $newEnvContent;
        } else {
            $saveMessage = 'Erro ao salvar o arquivo de configuração!';
            $saveStatus = 'danger';
        }
    } elseif (isset($_POST['create_local_folder'])) {
        // Criar pasta local para imagens
        $localImgDir = __DIR__ . '/img/items/';
        
        if (!file_exists(__DIR__ . '/img')) {
            mkdir(__DIR__ . '/img', 0777, true);
        }
        
        if (!file_exists($localImgDir)) {
            if (mkdir($localImgDir, 0777, true)) {
                $saveMessage = 'Pasta local para imagens criada com sucesso!';
                $saveStatus = 'success';
                
                // Atualizar o arquivo .env com o novo caminho
                if ($envExists) {
                    $envContent = preg_replace('/IMAGES_PATH=.*/', 'IMAGES_PATH=' . str_replace('\\', '/', $localImgDir), $envContent);
                    file_put_contents($envFile, $envContent);
                }
            } else {
                $saveMessage = 'Erro ao criar pasta local para imagens!';
                $saveStatus = 'danger';
            }
        } else {
            $saveMessage = 'A pasta local para imagens já existe! Foi configurada no arquivo .env.';
            $saveStatus = 'info';
            
            // Atualizar o arquivo .env com o novo caminho
            if ($envExists) {
                $envContent = preg_replace('/IMAGES_PATH=.*/', 'IMAGES_PATH=' . str_replace('\\', '/', $localImgDir), $envContent);
                file_put_contents($envFile, $envContent);
            }
        }
    }
}

// Buscar caminhos sugeridos e bancos de dados
$suggestedPaths = detectPossibleImagePaths();
$suggestedDatabases = detectDatabases();

// Tentar detectar problemas comuns
$configIssues = [];
$errorType = isset($_GET['error']) ? $_GET['error'] : '';
$errorMessage = isset($_GET['message']) ? urldecode($_GET['message']) : '';

// Adicionar mensagem de erro específica se foi redirecionado do index.php
if (!empty($errorType)) {
    switch ($errorType) {
        case 'env_not_found':
            $configIssues[] = 'O arquivo .env não foi encontrado. Por favor, crie-o.';
            break;
        case 'invalid_env':
            $configIssues[] = 'O arquivo .env está mal formatado. Verifique a sintaxe.';
            break;
        case 'missing_config':
            $missingConfig = isset($_GET['config']) ? $_GET['config'] : '';
            $configIssues[] = "A configuração {$missingConfig} está ausente ou vazia.";
            break;
        case 'invalid_images_path':
            $path = isset($_GET['path']) ? urldecode($_GET['path']) : '';
            $configIssues[] = "O diretório de imagens especificado não existe: {$path}";
            break;
        case 'db_connection':
            $configIssues[] = "Erro na conexão com o banco de dados: {$errorMessage}";
            break;
    }
}

// Verificar outras questões no arquivo .env se ele existir
if ($envExists) {
    // Verificar se há uma linha IMAGES_PATH
    if (!preg_match('/IMAGES_PATH\s*=/', $envContent)) {
        $configIssues[] = 'A configuração IMAGES_PATH não foi encontrada.';
    }
    
    // Verificar se o caminho das imagens existe
    preg_match('/IMAGES_PATH\s*=\s*([^\n\r]+)/', $envContent, $matches);
    if (isset($matches[1])) {
        $imagesPath = trim($matches[1]);
        if (!file_exists($imagesPath)) {
            $configIssues[] = 'O diretório especificado para IMAGES_PATH ('.$imagesPath.') não existe.';
        }
    }
    
    // Verificar configurações do banco de dados
    if (!preg_match('/DB_SERVER\s*=/', $envContent)) {
        $configIssues[] = 'A configuração DB_SERVER não foi encontrada.';
    }
    if (!preg_match('/DB_USERNAME\s*=/', $envContent)) {
        $configIssues[] = 'A configuração DB_USERNAME não foi encontrada.';
    }
    if (!preg_match('/DB_NAME\s*=/', $envContent)) {
        $configIssues[] = 'A configuração DB_NAME não foi encontrada.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLanguage; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('title'); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 30px;
        }
        .config-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #343a40;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .env-editor {
            font-family: monospace;
            min-height: 200px;
            margin-bottom: 20px;
        }
        .config-template {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
        }
        .instructions {
            background-color: #e9f7fe;
            border-left: 4px solid #4a89dc;
            padding: 15px;
            margin-bottom: 20px;
        }
        .path-suggestion {
            cursor: pointer;
            padding: 8px 12px;
            margin-bottom: 5px;
            background-color: #f0f0f0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .path-suggestion:hover {
            background-color: #e3e3e3;
        }
        .copy-btn {
            margin-left: 10px;
            padding: 2px 8px;
            font-size: 0.8em;
        }
        .section-title {
            font-weight: 600;
            color: #495057;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .alert-issues {
            margin-top: 20px;
        }
        /* Adicionar estilos para o seletor de idiomas */
        .language-selector {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 100;
        }
        
        .language-btn {
            margin-left: 5px;
            opacity: 0.6;
            transition: all 0.2s;
        }
        
        .language-btn:hover {
            opacity: 1;
        }
        
        .language-btn.active {
            opacity: 1;
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="config-container">
        <!-- Seletor de idiomas -->
        <div class="language-selector">
            <span><?php echo __('language'); ?></span>
            <?php foreach ($availableLanguages as $code => $name): ?>
                <a href="?lang=<?php echo $code; ?>" class="language-btn <?php echo ($currentLanguage === $code ? 'active' : ''); ?>">
                    <?php echo $name; ?>
                </a>
            <?php endforeach; ?>
        </div>
    
        <h1><?php echo __('title'); ?></h1>
        
        <?php if (!empty($saveMessage)): ?>
            <div class="alert alert-<?php echo $saveStatus; ?> alert-dismissible fade show">
                <?php 
                switch ($saveMessage) {
                    case 'Configuração salva com sucesso!':
                        echo __('config_saved');
                        break;
                    case 'Erro ao salvar o arquivo de configuração!':
                        echo __('save_error');
                        break;
                    case 'Pasta local para imagens criada com sucesso!':
                        echo __('folder_created');
                        break;
                    case 'Erro ao criar pasta local para imagens!':
                        echo __('folder_error');
                        break;
                    case 'A pasta local para imagens já existe! Foi configurada no arquivo .env.':
                        echo __('folder_exists');
                        break;
                    default:
                        echo $saveMessage;
                }
                ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($configIssues)): ?>
            <div class="alert alert-warning alert-issues">
                <h5><i class="fas fa-exclamation-triangle"></i> <?php echo __('issues_detected'); ?></h5>
                <ul>
                    <?php foreach ($configIssues as $issue): ?>
                        <li><?php echo $issue; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="instructions">
            <h4><i class="fas fa-info-circle"></i> <?php echo __('how_to_solve'); ?></h4>
            <ol>
                <li><strong><?php echo __('database_check'); ?></strong></li>
                <li><strong><?php echo __('images_path_check'); ?></strong></li>
                <li><strong><?php echo __('format_check'); ?></strong></li>
                <li><strong><?php echo __('quick_solution'); ?></strong></li>
            </ol>
        </div>
        
        <?php if (!$envExists): ?>
            <div class="alert alert-danger">
                <i class="fas fa-file-excel"></i> <?php echo __('env_missing_alert'); ?>
            </div>
            <div class="config-template">
                <p><?php echo __('config_template'); ?></p>
                <pre>DB_SERVER=localhost
DB_USERNAME=root
DB_PASSWORD=
DB_NAME=vorpv2
IMAGES_PATH=<?php echo str_replace('\\', '/', __DIR__ . '/img/items/'); ?></pre>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-edit"></i> <?php echo __('edit_env'); ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <textarea class="form-control env-editor" id="env_content" name="env_content" rows="8"><?php echo htmlspecialchars($envContent); ?></textarea>
                        <small class="form-text text-muted"><?php echo __('edit_instructions'); ?></small>
                    </div>
                    
                    <div class="section-title"><?php echo __('suggested_paths'); ?></div>
                    <div class="mb-3">
                        <?php foreach ($suggestedPaths as $path): ?>
                            <div class="path-suggestion">
                                <?php echo htmlspecialchars(str_replace('\\', '/', $path)); ?>
                                <button type="button" class="btn btn-sm btn-outline-primary copy-btn" 
                                    data-path="<?php echo htmlspecialchars(str_replace('\\', '/', $path)); ?>">
                                    <i class="fas fa-copy"></i> <?php echo __('use_btn'); ?>
                                </button>
                                <span class="badge <?php echo file_exists($path) ? 'badge-success' : 'badge-danger'; ?> float-right">
                                    <?php echo file_exists($path) ? __('exists') : __('not_exists'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($suggestedDatabases)): ?>
                    <div class="section-title"><?php echo __('available_databases'); ?></div>
                    <div class="mb-3">
                        <?php foreach ($suggestedDatabases as $db): ?>
                            <button type="button" class="btn btn-sm btn-outline-info mr-2 mb-2 database-btn" 
                                data-db="<?php echo htmlspecialchars($db); ?>">
                                <i class="fas fa-database"></i> <?php echo htmlspecialchars($db); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group mt-4">
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo __('save_config'); ?>
                        </button>
                        <button type="submit" name="create_local_folder" class="btn btn-success ml-2">
                            <i class="fas fa-folder-plus"></i> <?php echo __('create_folder'); ?>
                        </button>
                        <a href="index.php" class="btn btn-secondary ml-2">
                            <i class="fas fa-arrow-right"></i> <?php echo __('try_interface'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="fas fa-lightbulb"></i> <?php echo __('tips'); ?>
            </div>
            <div class="card-body">
                <h5><?php echo __('mysql_error'); ?></h5>
                <ul>
                    <li><?php echo __('mysql_tip1'); ?></li>
                    <li><?php echo __('mysql_tip2'); ?></li>
                    <li><?php echo __('mysql_tip3'); ?></li>
                </ul>
                
                <h5><?php echo __('images_path_title'); ?></h5>
                <ul>
                    <li><?php echo __('path_tip1'); ?></li>
                    <li><?php echo __('path_tip2'); ?></li>
                    <li><?php echo __('path_tip3'); ?></li>
                    <li><?php echo __('path_tip4'); ?></li>
                </ul>
                
                <h5><?php echo __('permissions'); ?></h5>
                <ul>
                    <li><?php echo __('perm_tip1'); ?></li>
                    <li><?php echo __('perm_tip2'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Botões para usar caminhos sugeridos
            $('.copy-btn').click(function() {
                const path = $(this).data('path');
                const content = $('#env_content').val();
                
                // Substituir o caminho no conteúdo
                const updatedContent = content.replace(/IMAGES_PATH=.*/, 'IMAGES_PATH=' + path);
                $('#env_content').val(updatedContent);
            });
            
            // Botões para usar bancos de dados
            $('.database-btn').click(function() {
                const db = $(this).data('db');
                const content = $('#env_content').val();
                
                // Substituir o nome do banco no conteúdo
                const updatedContent = content.replace(/DB_NAME=.*/, 'DB_NAME=' + db);
                $('#env_content').val(updatedContent);
            });
        });
    </script>
</body>
</html> 