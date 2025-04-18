<?php
// Configuração do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');  // Substitua pelo seu usuário do banco
define('DB_PASS', 'sua_senha');    // Substitua pela sua senha
define('DB_NAME', 'farpax_tools');

// Função para obter conexão com o banco de dados
function getDbConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        // Em produção, você deve logar o erro, não exibi-lo
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
}
