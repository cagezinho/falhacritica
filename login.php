<?php
/**
 * Página de Login - Sistema de Inventário D&D 3.5
 */
require_once 'config.php';

// Se já estiver logado, redirecionar para dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_personagem = sanitizeInput($_POST['nome_personagem'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($nome_personagem) || empty($senha)) {
        $_SESSION['login_error'] = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Buscar personagem
            $stmt = $conn->prepare("SELECT id, nome_personagem, senha FROM personagens WHERE nome_personagem = :nome");
            $stmt->bindParam(':nome', $nome_personagem);
            $stmt->execute();
            
            $personagem = $stmt->fetch();
            
            if ($personagem && password_verify($senha, $personagem['senha'])) {
                // Login bem-sucedido
                $_SESSION['personagem_id'] = $personagem['id'];
                $_SESSION['nome_personagem'] = $personagem['nome_personagem'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $_SESSION['login_error'] = 'Nome do personagem ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $_SESSION['login_error'] = 'Erro no sistema. Tente novamente.';
        }
    }
    
    // Redirecionar para evitar reenvio de formulário
    header("Location: login.php");
    exit();
}

// Recuperar mensagens da sessão
$error = '';
$success = '';

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['login_success'])) {
    $success = $_SESSION['login_success'];
    unset($_SESSION['login_success']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Sistema de Inventário D&D 3.5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card-custom {
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-radius: 15px;
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
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card card-custom">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-dragon fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Login do Personagem</h3>
                            <p class="text-muted">Entre com suas credenciais</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nome_personagem" class="form-label">
                                    <i class="fas fa-user me-2"></i>Nome do Personagem
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="nome_personagem" 
                                    name="nome_personagem" 
                                    required 
                                    placeholder="Digite o nome do personagem"
                                    value="<?php echo isset($_POST['nome_personagem']) ? htmlspecialchars($_POST['nome_personagem']) : ''; ?>"
                                >
                            </div>

                            <div class="mb-4">
                                <label for="senha" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Senha
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="senha" 
                                    name="senha" 
                                    required 
                                    placeholder="Digite sua senha"
                                >
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-custom btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Entrar
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">Não tem personagem ainda?</p>
                            <a href="registro.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>
                                Criar Personagem
                            </a>
                        </div>

                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-link">
                                <i class="fas fa-arrow-left me-2"></i>
                                Voltar ao Início
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>