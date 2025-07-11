<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Dashboard Admin - Sistema de Inventário D&D 3.5
 * Visualização de todos os inventários
 */
require_once 'config.php';
require_once 'admin_config.php';

// Verificar se está logado e é administrador
requireLogin();
requireAdmin();

// Instanciar conexão com banco
$database = new Database();
$conn = $database->getConnection();

// Buscar todos os personagens e seus inventários
try {
    $stmt = $conn->prepare("
        SELECT 
            p.nome_personagem,
            p.data_criacao,
            i.nome_item,
            i.descricao,
            i.peso,
            i.quantidade,
            (i.peso * i.quantidade) as peso_total_item
        FROM personagens p
        LEFT JOIN inventario i ON p.id = i.personagem_id
        ORDER BY p.nome_personagem ASC, i.nome_item ASC
    ");
    $stmt->execute();
    $dados = $stmt->fetchAll();
    
    // Organizar dados por personagem
    $personagens = [];
    $total_personagens = 0;
    $total_itens = 0;
    
    foreach ($dados as $row) {
        $nome = $row['nome_personagem'];
        
        if (!isset($personagens[$nome])) {
            $personagens[$nome] = [
                'data_criacao' => $row['data_criacao'],
                'itens' => [],
                'total_itens' => 0,
                'peso_total' => 0
            ];
            $total_personagens++;
        }
        
        if ($row['nome_item']) {
            $personagens[$nome]['itens'][] = [
                'nome' => $row['nome_item'],
                'descricao' => $row['descricao'],
                'peso' => $row['peso'],
                'quantidade' => $row['quantidade'],
                'peso_total' => $row['peso_total_item']
            ];
            $personagens[$nome]['total_itens'] += $row['quantidade'];
            $personagens[$nome]['peso_total'] += $row['peso_total_item'];
            $total_itens += $row['quantidade'];
        }
    }
    
} catch (PDOException $e) {
    $personagens = [];
    $total_personagens = 0;
    $total_itens = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Admin - Sistema D&D 3.5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-admin {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        .card-custom {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .personagem-card {
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }
        .personagem-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .item-badge {
            font-size: 0.85em;
            margin: 2px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .empty-inventory {
            background-color: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar Admin -->
    <nav class="navbar navbar-admin navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt me-2"></i>
                Dashboard Administrativo
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-user me-2"></i>
                    <span class="d-none d-sm-inline">Meu Inventário</span>
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    <span class="d-none d-sm-inline">Sair</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Estatísticas Gerais -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h5>Total de Personagens</h5>
                        <h2><?php echo $total_personagens; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-cubes fa-2x mb-2"></i>
                        <h5>Total de Itens</h5>
                        <h2><?php echo $total_itens; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Personagens e Inventários -->
        <div class="row">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Inventários de Todos os Personagens
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($personagens)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhum personagem encontrado</h5>
                                <p class="text-muted">Não há personagens cadastrados no sistema.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($personagens as $nome_personagem => $dados_personagem): ?>
                                <div class="card personagem-card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <div class="row align-items-center">
                                            <div class="col">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-user me-2"></i>
                                                    <?php echo htmlspecialchars($nome_personagem); ?>
                                                </h6>
                                            </div>
                                            <div class="col-auto">
                                                <span class="badge bg-light text-dark">
                                                    <?php echo $dados_personagem['total_itens']; ?> itens
                                                </span>
                                                <span class="badge bg-warning text-dark">
                                                    <?php echo number_format($dados_personagem['peso_total'], 2); ?> kg
                                                </span>
                                            </div>
                                        </div>
                                        <small class="text-light">
                                            <i class="fas fa-calendar me-1"></i>
                                            Criado em <?php echo date('d/m/Y', strtotime($dados_personagem['data_criacao'])); ?>
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($dados_personagem['itens'])): ?>
                                            <div class="empty-inventory">
                                                <i class="fas fa-box-open fa-2x mb-2"></i>
                                                <p class="mb-0">Inventário vazio</p>
                                            </div>
                                        <?php else: ?>
                                            <!-- Layout Desktop -->
                                            <div class="d-none d-lg-block">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Item</th>
                                                                <th>Descrição</th>
                                                                <th>Peso Unit.</th>
                                                                <th>Qtd</th>
                                                                <th>Peso Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($dados_personagem['itens'] as $item): ?>
                                                                <tr>
                                                                    <td>
                                                                        <i class="fas fa-cube me-2 text-primary"></i>
                                                                        <strong><?php echo htmlspecialchars($item['nome']); ?></strong>
                                                                    </td>
                                                                    <td class="text-muted">
                                                                        <?php echo htmlspecialchars($item['descricao']); ?>
                                                                    </td>
                                                                    <td><?php echo number_format($item['peso'], 2); ?> kg</td>
                                                                    <td>
                                                                        <span class="badge bg-primary"><?php echo $item['quantidade']; ?></span>
                                                                    </td>
                                                                    <td class="fw-bold"><?php echo number_format($item['peso_total'], 2); ?> kg</td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            
                                            <!-- Layout Mobile -->
                                            <div class="d-lg-none">
                                                <?php foreach ($dados_personagem['itens'] as $item): ?>
                                                    <div class="card mb-2 border-start border-primary">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <h6 class="mb-1">
                                                                        <i class="fas fa-cube me-2 text-primary"></i>
                                                                        <?php echo htmlspecialchars($item['nome']); ?>
                                                                    </h6>
                                                                    <?php if (!empty($item['descricao'])): ?>
                                                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($item['descricao']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <span class="badge bg-primary fs-6"><?php echo $item['quantidade']; ?></span>
                                                            </div>
                                                            <div class="row g-2 mt-2">
                                                                <div class="col-6">
                                                                    <small class="text-muted">Peso Unit.</small>
                                                                    <div><?php echo number_format($item['peso'], 2); ?> kg</div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="text-muted">Peso Total</small>
                                                                    <div class="fw-bold"><?php echo number_format($item['peso_total'], 2); ?> kg</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar animação de entrada suave
            const cards = document.querySelectorAll('.personagem-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Contador animado para estatísticas
            function animateCounter(element, target) {
                let current = 0;
                const increment = target / 30;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    element.textContent = Math.floor(current);
                }, 30);
            }
            
            // Animar contadores
            const counters = document.querySelectorAll('.stats-card h2');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                if (target > 0) {
                    animateCounter(counter, target);
                }
            });
        });
    </script>
</body>
</html>