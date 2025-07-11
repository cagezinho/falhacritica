<?php
/**
 * Configuração de Administradores
 * Sistema de Inventário D&D 3.5
 */

// Lista de personagens que têm acesso ao painel admin
// Adicione aqui os nomes exatos dos personagens que devem ter acesso administrativo
$admin_personagens = [
    'Admin',
    'GameMaster',
    'Mestre',
    'teste'
    // Adicione mais nomes conforme necessário
];

/**
 * Verifica se o personagem atual é administrador
 */
function isAdmin() {
    global $admin_personagens;
    
    // Verificar se está logado (sem usar isLoggedIn())
    if (!isset($_SESSION['personagem_id']) || !isset($_SESSION['nome_personagem'])) {
        return false;
    }
    
    return in_array($_SESSION['nome_personagem'], $admin_personagens);
}

/**
 * Redireciona se não for administrador
 */
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}
?>