<?php
/**
 * Configuração do Banco de Dados
 * Sistema de Inventário D&D 3.5
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'nicol674_dnd_inventario');
define('DB_USER', 'nicol674_dnd_user');
define('DB_PASS', 'Nicolas@44997615622');

// Configurações de sessão
define('SESSION_NAME', 'dnd_inventario_session');

// Classe para conexão com o banco
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                )
            );
        } catch(PDOException $exception) {
            die("Erro na conexão: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
}

// Função para sanitizar entrada
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Função para validar senha
function validatePassword($password) {
    return strlen($password) >= 6;
}

// Iniciar sessão
session_name(SESSION_NAME);
session_start();

// Verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['personagem_id']) && isset($_SESSION['nome_personagem']);
}

// Função para redirecionar se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Função para logout
function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}

/**
 * Verificar se o usuário é administrador (sem redirecionar)
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}
?>