<?php
/**
 * Página Principal - Sistema de Inventário D&D 3.5
 */
require_once 'config.php';

// Se já estiver logado, redirecionar para dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sistema de Inventário D&D 3.5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .card-custom {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card-custom:hover {
            transform: translateY(-5px);
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-custom:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">
                <i class="fas fa-dragon me-3"></i>
                Sistema de Inventário D&D 3.5
            </h1>
            <p class="lead mb-5">Gerencie seus itens, equipamentos e organize seu inventário de forma simples e eficiente!</p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="d-grid gap-3">
                        <a href="login.php" class="btn btn-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Entrar com Personagem
                        </a>
                        <a href="registro.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Criar Novo Personagem
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card card-custom h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-backpack fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Inventário Pessoal</h5>
                            <p class="card-text">Gerencie todos os seus itens em um inventário organizado e fácil de usar.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card card-custom h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-exchange-alt fa-3x text-success mb-3"></i>
                            <h5 class="card-title">Transferência de Itens</h5>
                            <p class="card-text">Envie itens para outros personagens de forma rápida e segura.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card card-custom h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-weight-hanging fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">Controle de Peso</h5>
                            <p class="card-text">Monitore o peso total dos seus itens para gerenciar a carga do personagem.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 Sistema de Inventário D&D 3.5 - Desenvolvido para aventureiros!</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>