<?php
/**
 * Configuração de Administradores
 * Sistema de Inventário D&D 3.5
 */

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