<?php
/**
 * Página de Registro - Sistema de Inventário D&D 3.5
 */
require_once 'config.php';

// Se já estiver logado, redirecionar para dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Processar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_personagem = sanitizeInput($_POST['nome_personagem'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($nome_personagem) || empty($senha) || empty($confirmar_senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (strlen($nome_personagem) < 3) {
        $error = 'O nome do personagem deve ter pelo menos 3 caracteres.';
    } elseif (strlen($nome_personagem) > 50) {
        $error = 'O nome do personagem não pode ter mais de 50 caracteres.';
    } elseif (!validatePassword($senha)) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem.';
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Verificar se o personagem já existe
            $stmt = $conn->prepare("SELECT id FROM personagens WHERE nome_personagem = :nome");
            $stmt->bindParam(':nome', $nome_personagem);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Este nome de personagem já existe. Escolha outro.';
            } else {
                // Criar novo personagem
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO personagens (nome_personagem, senha) VALUES (:nome, :senha)");
                $stmt->bindParam(':nome', $nome_personagem);
                $stmt->bindParam(':senha', $senha_hash);
                
                if ($stmt->execute()) {
                    $success = 'Personagem criado com sucesso! Redirecionando para login...';
                    header("refresh:2;url=login.php");
                } else {
                    $error = 'Erro ao criar personagem. Tente novamente.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro no sistema. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registro - Sistema de Inventário D&D 3.5</title>
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
                            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                            <h3 class="card-title">Criar Personagem</h3>
                            <p class="text-muted">Registre seu novo personagem</p>
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
                                    minlength="3"
                                    maxlength="50"
                                >
                                <div class="form-text">Mínimo 3 caracteres, máximo 50.</div>
                            </div>

                            <div class="mb-3">
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
                                    minlength="6"
                                >
                                <div class="form-text">Mínimo 6 caracteres.</div>
                            </div>

                            <div class="mb-4">
                                <label for="confirmar_senha" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Confirmar Senha
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirmar_senha" 
                                    name="confirmar_senha" 
                                    required 
                                    placeholder="Confirme sua senha"
                                    minlength="6"
                                >
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-custom btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Criar Personagem
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">Já tem um personagem?</p>
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Fazer Login
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
    
    <!-- Validação de senha em tempo real -->
    <script>
        document.getElementById('confirmar_senha').addEventListener('input', function() {
            const senha = document.getElementById('senha').value;
            const confirmarSenha = this.value;
            
            if (senha !== confirmarSenha) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>