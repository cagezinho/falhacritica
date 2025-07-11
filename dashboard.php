<?php
// DEBUG - adicione isso temporariamente no topo após os requires
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Dashboard - Sistema de Inventário D&D 3.5
 */
require_once 'config.php';
require_once 'xp_functions.php';

// Verificar se está logado
requireLogin();

$error = '';
$success = '';

// Instanciar conexão com banco
$database = new Database();
$conn = $database->getConnection();

// Processar ações do bestiário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'adicionar_criatura':
            $nome_criatura = sanitizeInput($_POST['nome_criatura'] ?? '');
            $tipo_criatura = sanitizeInput($_POST['tipo_criatura'] ?? '');
            $pontos_vida = sanitizeInput($_POST['pontos_vida'] ?? '');
            $classe_armadura = sanitizeInput($_POST['classe_armadura'] ?? '');
            $descricao = sanitizeInput($_POST['descricao'] ?? '');
            
            if (empty($nome_criatura)) {
                $_SESSION['error'] = 'Nome da criatura é obrigatório.';
            } elseif (empty($tipo_criatura)) {
                $_SESSION['error'] = 'Tipo da criatura é obrigatório.';
            } elseif (empty($pontos_vida)) {
                $_SESSION['error'] = 'Pontos de vida são obrigatórios.';
            } elseif (empty($classe_armadura)) {
                $_SESSION['error'] = 'Classe de armadura é obrigatória.';
            } else {
                try {
                    $stmt = $conn->prepare("INSERT INTO bestiario (nome_criatura, tipo_criatura, pontos_vida, classe_armadura, descricao, criado_por) VALUES (:nome, :tipo, :pv, :ca, :desc, :criado_por)");
                    $stmt->bindParam(':nome', $nome_criatura);
                    $stmt->bindParam(':tipo', $tipo_criatura);
                    $stmt->bindParam(':pv', $pontos_vida);
                    $stmt->bindParam(':ca', $classe_armadura);
                    $stmt->bindParam(':desc', $descricao);
                    $stmt->bindParam(':criado_por', $_SESSION['personagem_id']);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'Criatura adicionada ao bestiário com sucesso!';
                    } else {
                        $_SESSION['error'] = 'Erro ao adicionar criatura.';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
                }
            }
            break;
            
        case 'editar_criatura':
            $criatura_id = intval($_POST['criatura_id'] ?? 0);
            $nome_criatura = sanitizeInput($_POST['nome_criatura'] ?? '');
            $tipo_criatura = sanitizeInput($_POST['tipo_criatura'] ?? '');
            $pontos_vida = sanitizeInput($_POST['pontos_vida'] ?? '');
            $classe_armadura = sanitizeInput($_POST['classe_armadura'] ?? '');
            $descricao = sanitizeInput($_POST['descricao'] ?? '');
            
            if ($criatura_id > 0 && !empty($nome_criatura) && !empty($tipo_criatura) && !empty($pontos_vida) && !empty($classe_armadura)) {
                try {
                    $stmt = $conn->prepare("UPDATE bestiario SET nome_criatura = :nome, tipo_criatura = :tipo, pontos_vida = :pv, classe_armadura = :ca, descricao = :desc, editado_por = :editado_por WHERE id = :id");
                    $stmt->bindParam(':nome', $nome_criatura);
                    $stmt->bindParam(':tipo', $tipo_criatura);
                    $stmt->bindParam(':pv', $pontos_vida);
                    $stmt->bindParam(':ca', $classe_armadura);
                    $stmt->bindParam(':desc', $descricao);
                    $stmt->bindParam(':editado_por', $_SESSION['personagem_id']);
                    $stmt->bindParam(':id', $criatura_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'Criatura editada com sucesso!';
                    } else {
                        $_SESSION['error'] = 'Erro ao editar criatura.';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
                }
            } else {
                $_SESSION['error'] = 'Dados inválidos para edição.';
            }
            break;
            
        case 'remover_criatura':
            $criatura_id = intval($_POST['criatura_id'] ?? 0);
            
            if ($criatura_id > 0) {
                try {
                    $stmt = $conn->prepare("DELETE FROM bestiario WHERE id = :id");
                    $stmt->bindParam(':id', $criatura_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'Criatura removida do bestiário!';
                    } else {
                        $_SESSION['error'] = 'Erro ao remover criatura.';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
                }
            }
            break;
        case 'adicionar':
            $nome_item = sanitizeInput($_POST['nome_item'] ?? '');
            $descricao = sanitizeInput($_POST['descricao'] ?? '');
            $peso = floatval($_POST['peso'] ?? 0);
            $quantidade = intval($_POST['quantidade'] ?? 1);
            
            if (empty($nome_item)) {
                $_SESSION['error'] = 'Nome do item é obrigatório.';
            } elseif ($peso < 0) {
                $_SESSION['error'] = 'Peso não pode ser negativo.';
            } elseif ($quantidade < 1) {
                $_SESSION['error'] = 'Quantidade deve ser maior que zero.';
            } else {
                try {
                    $stmt = $conn->prepare("INSERT INTO inventario (personagem_id, nome_item, descricao, peso, quantidade) VALUES (:personagem_id, :nome_item, :descricao, :peso, :quantidade)");
                    $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
                    $stmt->bindParam(':nome_item', $nome_item);
                    $stmt->bindParam(':descricao', $descricao);
                    $stmt->bindParam(':peso', $peso);
                    $stmt->bindParam(':quantidade', $quantidade);
                    
                    if ($stmt->execute()) {
                        // Registrar movimentação
                        $stmt = $conn->prepare("INSERT INTO movimentacoes (personagem_origem, nome_item, peso, quantidade, tipo_movimento) VALUES (:personagem_id, :nome_item, :peso, :quantidade, 'adicao')");
                        $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
                        $stmt->bindParam(':nome_item', $nome_item);
                        $stmt->bindParam(':peso', $peso);
                        $stmt->bindParam(':quantidade', $quantidade);
                        $stmt->execute();
                        
                        $_SESSION['success'] = 'Item adicionado com sucesso!';
                    } else {
                        $_SESSION['error'] = 'Erro ao adicionar item.';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
                }
            }
            break;
            
        case 'remover':
            $item_id = intval($_POST['item_id'] ?? 0);
            
            if ($item_id > 0) {
                try {
                    // Buscar item antes de remover (para o log)
                    $stmt = $conn->prepare("SELECT nome_item, peso, quantidade FROM inventario WHERE id = :id AND personagem_id = :personagem_id");
                    $stmt->bindParam(':id', $item_id);
                    $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
                    $stmt->execute();
                    $item = $stmt->fetch();
                    
                    if ($item) {
                        // Remover item
                        $stmt = $conn->prepare("DELETE FROM inventario WHERE id = :id AND personagem_id = :personagem_id");
                        $stmt->bindParam(':id', $item_id);
                        $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
                        
                        if ($stmt->execute()) {
                            // Registrar movimentação
                            $stmt = $conn->prepare("INSERT INTO movimentacoes (personagem_origem, nome_item, peso, quantidade, tipo_movimento) VALUES (:personagem_id, :nome_item, :peso, :quantidade, 'remocao')");
                            $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
                            $stmt->bindParam(':nome_item', $item['nome_item']);
                            $stmt->bindParam(':peso', $item['peso']);
                            $stmt->bindParam(':quantidade', $item['quantidade']);
                            $stmt->execute();
                            
                            $_SESSION['success'] = 'Item removido com sucesso!';
                        } else {
                            $_SESSION['error'] = 'Erro ao remover item.';
                        }
                    } else {
                        $_SESSION['error'] = 'Item não encontrado.';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
                }
            }
            break;

            // Adicionar este case no switch de ações:
       case 'adicionar_xp':
    // Apenas admins podem adicionar XP
    if (!isAdmin()) {
        $_SESSION['error'] = 'Acesso negado.';
        break;
    }
    
    $tipo_distribuicao = $_POST['tipo_distribuicao'] ?? '';
    $xp_total = intval($_POST['xp_total'] ?? 0);
    $motivo_xp = sanitizeInput($_POST['motivo_xp'] ?? '');
    
    if ($xp_total <= 0) {
        $_SESSION['error'] = 'XP deve ser maior que zero.';
    } elseif ($xp_total > 500000) {
        $_SESSION['error'] = 'XP não pode ser maior que 500.000.';
    } else {
        if ($tipo_distribuicao === 'individual') {
            // XP individual (como era antes)
            $personagem_alvo = intval($_POST['personagem_alvo'] ?? 0);
            
            if ($personagem_alvo <= 0) {
                $_SESSION['error'] = 'Personagem é obrigatório.';
                break;
            }
            
            $resultado = adicionarXP($conn, $personagem_alvo, $xp_total, $motivo_xp, $_SESSION['personagem_id']);
            
            if ($resultado && $resultado['sucesso']) {
                if ($resultado['subiu_nivel']) {
                    $_SESSION['success'] = "XP adicionado com sucesso! O personagem subiu do nível {$resultado['nivel_anterior']} para {$resultado['nivel_novo']}!";
                } else {
                    $_SESSION['success'] = "XP adicionado com sucesso! +" . number_format($xp_total) . " XP";
                }
            } else {
                $_SESSION['error'] = 'Erro ao adicionar XP.';
            }
            
        } elseif ($tipo_distribuicao === 'grupo') {
            // XP em grupo (nova funcionalidade)
            $personagens_selecionados = $_POST['personagens_grupo'] ?? [];
            
            if (empty($personagens_selecionados)) {
                $_SESSION['error'] = 'Selecione pelo menos um personagem para distribuir XP.';
                break;
            }
            
            $quantidade_personagens = count($personagens_selecionados);
            $xp_por_personagem = floor($xp_total / $quantidade_personagens);
            
            if ($xp_por_personagem <= 0) {
                $_SESSION['error'] = 'XP por personagem deve ser maior que zero.';
                break;
            }
            
            $sucessos = 0;
            $personagens_uparam = [];
            
            foreach ($personagens_selecionados as $personagem_id) {
                $personagem_id = intval($personagem_id);
                if ($personagem_id > 0) {
                    $resultado = adicionarXP($conn, $personagem_id, $xp_por_personagem, $motivo_xp, $_SESSION['personagem_id']);
                    
                    if ($resultado && $resultado['sucesso']) {
                        $sucessos++;
                        if ($resultado['subiu_nivel']) {
                            // Buscar nome do personagem
                            $stmt = $conn->prepare("SELECT nome_personagem FROM personagens WHERE id = :id");
                            $stmt->bindParam(':id', $personagem_id);
                            $stmt->execute();
                            $nome = $stmt->fetchColumn();
                            
                            $personagens_uparam[] = $nome . " (nível {$resultado['nivel_anterior']} → {$resultado['nivel_novo']})";
                        }
                    }
                }
            }
            
            if ($sucessos > 0) {
                $mensagem = "XP distribuído com sucesso! {$sucessos} personagem(s) receberam " . number_format($xp_por_personagem) . " XP cada.";
                
                if (!empty($personagens_uparam)) {
                    $mensagem .= "\n\n🎉 Personagens que subiram de nível:\n" . implode("\n", $personagens_uparam);
                }
                
                $_SESSION['success'] = $mensagem;
            } else {
                $_SESSION['error'] = 'Erro ao distribuir XP.';
            }
        } else {
            $_SESSION['error'] = 'Tipo de distribuição inválido.';
        }
    }
    break;
    case 'ajustar_moedas':
    if (!isAdmin()) {
        $_SESSION['error'] = 'Acesso negado.';
        break;
    }
    
    $personagem_alvo = intval($_POST['personagem_alvo'] ?? 0);
    $tipo_moeda = $_POST['tipo_moeda'] ?? '';
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $operacao = $_POST['operacao'] ?? '';
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if ($personagem_alvo <= 0) {
        $_SESSION['error'] = 'Personagem é obrigatório.';
    } elseif (!in_array($tipo_moeda, ['cobre', 'prata', 'ouro', 'platina'])) {
        $_SESSION['error'] = 'Tipo de moeda inválido.';
    } elseif ($quantidade <= 0) {
        $_SESSION['error'] = 'Quantidade deve ser maior que zero.';
    } elseif (!in_array($operacao, ['adicionar', 'remover'])) {
        $_SESSION['error'] = 'Operação inválida.';
    } else {
        try {
            $coluna_moeda = "moedas_" . $tipo_moeda;
            
            // Buscar quantidade atual
            $stmt = $conn->prepare("SELECT $coluna_moeda, nome_personagem FROM personagens WHERE id = :id");
            $stmt->bindParam(':id', $personagem_alvo);
            $stmt->execute();
            $personagem = $stmt->fetch();
            
            if ($personagem) {
                $quantidade_atual = $personagem[$coluna_moeda];
                
                if ($operacao === 'adicionar') {
                    $nova_quantidade = $quantidade_atual + $quantidade;
                } else { // remover
                    $nova_quantidade = $quantidade_atual - $quantidade;
                    if ($nova_quantidade < 0) {
                        $_SESSION['error'] = 'Não é possível remover mais moedas do que o personagem possui.';
                        break;
                    }
                }
                
                // Atualizar moedas
                $stmt = $conn->prepare("UPDATE personagens SET $coluna_moeda = :nova_quantidade WHERE id = :id");
                $stmt->bindParam(':nova_quantidade', $nova_quantidade);
                $stmt->bindParam(':id', $personagem_alvo);
                
                if ($stmt->execute()) {
                    // Registrar movimentação
                    $stmt = $conn->prepare("INSERT INTO movimentacoes_moedas (personagem_id, tipo_moeda, quantidade_anterior, quantidade_nova, quantidade_alterada, operacao, motivo, alterado_por) VALUES (:personagem_id, :tipo_moeda, :quantidade_anterior, :quantidade_nova, :quantidade_alterada, :operacao, :motivo, :alterado_por)");
                    $stmt->bindParam(':personagem_id', $personagem_alvo);
                    $stmt->bindParam(':tipo_moeda', $tipo_moeda);
                    $stmt->bindParam(':quantidade_anterior', $quantidade_atual);
                    $stmt->bindParam(':quantidade_nova', $nova_quantidade);
                    $stmt->bindParam(':quantidade_alterada', $quantidade);
                    $stmt->bindParam(':operacao', $operacao);
                    $stmt->bindParam(':motivo', $motivo);
                    $stmt->bindParam(':alterado_por', $_SESSION['personagem_id']);
                    $stmt->execute();
                    
                    $operacao_texto = $operacao === 'adicionar' ? 'adicionadas' : 'removidas';
                    $_SESSION['success'] = $quantidade . " moedas de " . $tipo_moeda . " {$operacao_texto} para " . $personagem['nome_personagem'] . "!";
                } else {
                    $_SESSION['error'] = 'Erro ao ajustar moedas.';
                }
            } else {
                $_SESSION['error'] = 'Personagem não encontrado.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
        }
    }
    break;

    case 'transferir_moedas':
    $personagem_destino_nome = sanitizeInput($_POST['personagem_destino'] ?? '');
    $tipo_moeda = $_POST['tipo_moeda'] ?? '';
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if (empty($personagem_destino_nome)) {
        $_SESSION['error'] = 'Personagem destino é obrigatório.';
    } elseif (!in_array($tipo_moeda, ['cobre', 'prata', 'ouro', 'platina'])) {
        $_SESSION['error'] = 'Tipo de moeda inválido.';
    } elseif ($quantidade <= 0) {
        $_SESSION['error'] = 'Quantidade deve ser maior que zero.';
    } else {
        try {
            // Verificar se o personagem destino existe
            $stmt = $conn->prepare("SELECT id, nome_personagem FROM personagens WHERE nome_personagem = :nome");
            $stmt->bindParam(':nome', $personagem_destino_nome);
            $stmt->execute();
            $destino = $stmt->fetch();
            
            if (!$destino) {
                $_SESSION['error'] = 'Personagem destino não encontrado.';
            } elseif ($destino['id'] == $_SESSION['personagem_id']) {
                $_SESSION['error'] = 'Não é possível transferir moedas para si mesmo.';
            } else {
                $coluna_moeda = "moedas_" . $tipo_moeda;
                
                // Buscar quantidade atual do remetente
                $stmt = $conn->prepare("SELECT $coluna_moeda FROM personagens WHERE id = :id");
                $stmt->bindParam(':id', $_SESSION['personagem_id']);
                $stmt->execute();
                $remetente = $stmt->fetch();
                
                if ($remetente[$coluna_moeda] < $quantidade) {
                    $_SESSION['error'] = 'Você não possui moedas suficientes para esta transferência.';
                } else {
                    // Buscar quantidade atual do destinatário
                    $stmt = $conn->prepare("SELECT $coluna_moeda FROM personagens WHERE id = :id");
                    $stmt->bindParam(':id', $destino['id']);
                    $stmt->execute();
                    $destinatario = $stmt->fetch();
                    
                    // Remover do remetente
                    $nova_quantidade_remetente = $remetente[$coluna_moeda] - $quantidade;
                    $stmt = $conn->prepare("UPDATE personagens SET $coluna_moeda = :nova_quantidade WHERE id = :id");
                    $stmt->bindParam(':nova_quantidade', $nova_quantidade_remetente);
                    $stmt->bindParam(':id', $_SESSION['personagem_id']);
                    $stmt->execute();
                    
                    // Adicionar ao destinatário
                    $nova_quantidade_destinatario = $destinatario[$coluna_moeda] + $quantidade;
                    $stmt = $conn->prepare("UPDATE personagens SET $coluna_moeda = :nova_quantidade WHERE id = :id");
                    $stmt->bindParam(':nova_quantidade', $nova_quantidade_destinatario);
                    $stmt->bindParam(':id', $destino['id']);
                    $stmt->execute();
                    
                    // Registrar movimentação (saída)
                    $stmt = $conn->prepare("INSERT INTO movimentacoes_moedas (personagem_id, personagem_destino, tipo_moeda, quantidade_anterior, quantidade_nova, quantidade_alterada, operacao, motivo, alterado_por) VALUES (:personagem_id, :personagem_destino, :tipo_moeda, :quantidade_anterior, :quantidade_nova, :quantidade_alterada, 'transferencia_saida', :motivo, :alterado_por)");
                    $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
                    $stmt->bindParam(':personagem_destino', $destino['id']);
                    $stmt->bindParam(':tipo_moeda', $tipo_moeda);
                    $stmt->bindParam(':quantidade_anterior', $remetente[$coluna_moeda]);
                    $stmt->bindParam(':quantidade_nova', $nova_quantidade_remetente);
                    $stmt->bindParam(':quantidade_alterada', $quantidade);
                    $stmt->bindParam(':motivo', $motivo);
                    $stmt->bindParam(':alterado_por', $_SESSION['personagem_id']);
                    $stmt->execute();
                    
                    // Registrar movimentação (entrada)
                    $stmt = $conn->prepare("INSERT INTO movimentacoes_moedas (personagem_id, personagem_origem, tipo_moeda, quantidade_anterior, quantidade_nova, quantidade_alterada, operacao, motivo, alterado_por) VALUES (:personagem_id, :personagem_origem, :tipo_moeda, :quantidade_anterior, :quantidade_nova, :quantidade_alterada, 'transferencia_entrada', :motivo, :alterado_por)");
                    $stmt->bindParam(':personagem_id', $destino['id']);
                    $stmt->bindParam(':personagem_origem', $_SESSION['personagem_id']);
                    $stmt->bindParam(':tipo_moeda', $tipo_moeda);
                    $stmt->bindParam(':quantidade_anterior', $destinatario[$coluna_moeda]);
                    $stmt->bindParam(':quantidade_nova', $nova_quantidade_destinatario);
                    $stmt->bindParam(':quantidade_alterada', $quantidade);
                    $stmt->bindParam(':motivo', $motivo);
                    $stmt->bindParam(':alterado_por', $_SESSION['personagem_id']);
                    $stmt->execute();
                    
                    $_SESSION['success'] = $quantidade . " moedas de " . $tipo_moeda . " transferidas com sucesso para " . $destino['nome_personagem'] . "!";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
        }
    }
    break;

    case 'converter_moedas':
    $tipo_origem = $_POST['tipo_origem'] ?? '';
    $tipo_destino = $_POST['tipo_destino'] ?? '';
    $quantidade = intval($_POST['quantidade'] ?? 0);
    
    if (!in_array($tipo_origem, ['cobre', 'prata', 'ouro', 'platina']) || !in_array($tipo_destino, ['cobre', 'prata', 'ouro', 'platina'])) {
        $_SESSION['error'] = 'Tipos de moeda inválidos.';
    } elseif ($tipo_origem === $tipo_destino) {
        $_SESSION['error'] = 'Não é possível converter para o mesmo tipo de moeda.';
    } elseif ($quantidade <= 0) {
        $_SESSION['error'] = 'Quantidade deve ser maior que zero.';
    } else {
        try {
            // Valores das moedas em cobre
            $valores = ['cobre' => 1, 'prata' => 10, 'ouro' => 100, 'platina' => 1000];
            
            $valor_origem = $valores[$tipo_origem];
            $valor_destino = $valores[$tipo_destino];
            
            // Verificar se a conversão é válida
            if ($valor_origem < $valor_destino) {
                // Convertendo para moeda de maior valor
                $taxa_conversao = $valor_destino / $valor_origem;
                if ($quantidade % $taxa_conversao !== 0) {
                    $_SESSION['error'] = "Para converter $tipo_origem para $tipo_destino, você precisa de múltiplos de $taxa_conversao moedas.";
                    break;
                }
                $quantidade_resultado = $quantidade / $taxa_conversao;
            } else {
                // Convertendo para moeda de menor valor
                $taxa_conversao = $valor_origem / $valor_destino;
                $quantidade_resultado = $quantidade * $taxa_conversao;
            }
            
            $coluna_origem = "moedas_" . $tipo_origem;
            $coluna_destino = "moedas_" . $tipo_destino;
            
            // Buscar quantidade atual
            $stmt = $conn->prepare("SELECT $coluna_origem, $coluna_destino FROM personagens WHERE id = :id");
            $stmt->bindParam(':id', $_SESSION['personagem_id']);
            $stmt->execute();
            $personagem = $stmt->fetch();
            
            if ($personagem[$coluna_origem] < $quantidade) {
                $_SESSION['error'] = "Você não possui moedas de $tipo_origem suficientes para esta conversão.";
            } else {
                // Realizar conversão
                $nova_quantidade_origem = $personagem[$coluna_origem] - $quantidade;
                $nova_quantidade_destino = $personagem[$coluna_destino] + $quantidade_resultado;
                
                $stmt = $conn->prepare("UPDATE personagens SET $coluna_origem = :nova_origem, $coluna_destino = :nova_destino WHERE id = :id");
                $stmt->bindParam(':nova_origem', $nova_quantidade_origem);
                $stmt->bindParam(':nova_destino', $nova_quantidade_destino);
                $stmt->bindParam(':id', $_SESSION['personagem_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Conversão realizada! $quantidade moedas de $tipo_origem convertidas para $quantidade_resultado moedas de $tipo_destino.";
                } else {
                    $_SESSION['error'] = 'Erro ao converter moedas.';
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
        }
    }
    break;
            
        case 'transferir':
            $item_id = intval($_POST['item_id'] ?? 0);
            $personagem_destino = sanitizeInput($_POST['personagem_destino'] ?? '');
            $quantidade_transferir = intval($_POST['quantidade_transferir'] ?? 1);
            
            if ($item_id > 0 && !empty($personagem_destino) && $quantidade_transferir > 0) {
                try {
                    // Verificar se o personagem destino existe
                    $stmt = $conn->prepare("SELECT id FROM personagens WHERE nome_personagem = :nome");
                    $stmt->bindParam(':nome', $personagem_destino);
                    $stmt->execute();
                    $destino = $stmt->fetch();
                    
                    if (!$destino) {
                        $_SESSION['error'] = 'Personagem destino não encontrado.';
                    } elseif ($destino['id'] == $_SESSION['personagem_id']) {
                        $_SESSION['error'] = 'Não é possível transferir para si mesmo.';
                    } else {
                        // Buscar item
                        $stmt = $conn->prepare("SELECT nome_item, descricao, peso, quantidade FROM inventario WHERE id = :id AND personagem_id = :personagem_id");
                        $stmt->bindParam(':id', $item_id);
                        $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
                        $stmt->execute();
                        $item = $stmt->fetch();
                        
                        if ($item) {
                            if ($quantidade_transferir > $item['quantidade']) {
                                $_SESSION['error'] = 'Quantidade para transferir é maior que a disponível.';
                            } else {
                                // Se transferir tudo, mover o item
                                if ($quantidade_transferir == $item['quantidade']) {
                                    $stmt = $conn->prepare("UPDATE inventario SET personagem_id = :novo_personagem WHERE id = :id AND personagem_id = :personagem_atual");
                                    $stmt->bindParam(':novo_personagem', $destino['id']);
                                    $stmt->bindParam(':id', $item_id);
                                    $stmt->bindParam(':personagem_atual', $_SESSION['personagem_id']);
                                    $stmt->execute();
                                } else {
                                    // Se transferir parcialmente
                                    // Reduzir quantidade do item original
                                    $nova_quantidade = $item['quantidade'] - $quantidade_transferir;
                                    $stmt = $conn->prepare("UPDATE inventario SET quantidade = :nova_quantidade WHERE id = :id");
                                    $stmt->bindParam(':nova_quantidade', $nova_quantidade);
                                    $stmt->bindParam(':id', $item_id);
                                    $stmt->execute();
                                    
                                    // Verificar se o personagem destino já tem este item
                                    $stmt = $conn->prepare("SELECT id, quantidade FROM inventario WHERE personagem_id = :destino_id AND nome_item = :nome_item AND peso = :peso");
                                    $stmt->bindParam(':destino_id', $destino['id']);
                                    $stmt->bindParam(':nome_item', $item['nome_item']);
                                    $stmt->bindParam(':peso', $item['peso']);
                                    $stmt->execute();
                                    $item_destino = $stmt->fetch();
                                    
                                    if ($item_destino) {
                                        // Se já tem, somar quantidade
                                        $nova_quantidade_destino = $item_destino['quantidade'] + $quantidade_transferir;
                                        $stmt = $conn->prepare("UPDATE inventario SET quantidade = :quantidade WHERE id = :id");
                                        $stmt->bindParam(':quantidade', $nova_quantidade_destino);
                                        $stmt->bindParam(':id', $item_destino['id']);
                                        $stmt->execute();
                                    } else {
                                        // Se não tem, criar novo item
                                        $stmt = $conn->prepare("INSERT INTO inventario (personagem_id, nome_item, descricao, peso, quantidade) VALUES (:personagem_id, :nome_item, :descricao, :peso, :quantidade)");
                                        $stmt->bindParam(':personagem_id', $destino['id']);
                                        $stmt->bindParam(':nome_item', $item['nome_item']);
                                        $stmt->bindParam(':descricao', $item['descricao']);
                                        $stmt->bindParam(':peso', $item['peso']);
                                        $stmt->bindParam(':quantidade', $quantidade_transferir);
                                        $stmt->execute();
                                    }
                                }
                                
                                // Registrar movimentação
                                $stmt = $conn->prepare("INSERT INTO movimentacoes (personagem_origem, personagem_destino, nome_item, peso, quantidade, tipo_movimento) VALUES (:origem, :destino, :nome_item, :peso, :quantidade, 'transferencia')");
                                $stmt->bindParam(':origem', $_SESSION['personagem_id']);
                                $stmt->bindParam(':destino', $destino['id']);
                                $stmt->bindParam(':nome_item', $item['nome_item']);
                                $stmt->bindParam(':peso', $item['peso']);
                                $stmt->bindParam(':quantidade', $quantidade_transferir);
                                $stmt->execute();
                                
                                $_SESSION['success'] = $quantidade_transferir . ' x ' . $item['nome_item'] . ' transferido(s) com sucesso para ' . $personagem_destino . '!';
                            }
                        } else {
                            $_SESSION['error'] = 'Item não encontrado.';
                        }
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
                }
            } else {
                $_SESSION['error'] = 'Dados inválidos para transferência.';
            }
            break;
            
        case 'ajustar_quantidade':
            $item_id = intval($_POST['item_id'] ?? 0);
            $operacao = $_POST['operacao'] ?? '';
            
            if ($item_id > 0 && in_array($operacao, ['aumentar', 'diminuir'])) {
                try {
                    // Buscar item atual
                    $stmt = $conn->prepare("SELECT nome_item, peso, quantidade FROM inventario WHERE id = :id AND personagem_id = :personagem_id");
                    $stmt->bindParam(':id', $item_id);
                    $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
                    $stmt->execute();
                    $item = $stmt->fetch();
                    
                    if ($item) {
                        $nova_quantidade = $item['quantidade'];
                        
                        if ($operacao === 'aumentar') {
                            $nova_quantidade++;
                        } elseif ($operacao === 'diminuir') {
                            if ($item['quantidade'] > 1) {
                                $nova_quantidade--;
                            } else {
                                $_SESSION['error'] = 'Não é possível diminuir abaixo de 1. Use o botão remover para excluir o item.';
                                break;
                            }
                        }
                        
                        // Atualizar quantidade
                        $stmt = $conn->prepare("UPDATE inventario SET quantidade = :quantidade WHERE id = :id");
                        $stmt->bindParam(':quantidade', $nova_quantidade);
                        $stmt->bindParam(':id', $item_id);
                        
                        if ($stmt->execute()) {
                            // Registrar movimentação
                            $stmt = $conn->prepare("INSERT INTO movimentacoes (personagem_origem, nome_item, peso, quantidade, tipo_movimento) VALUES (:personagem_id, :nome_item, :peso, :quantidade, 'ajuste_quantidade')");
                            $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
                            $stmt->bindParam(':nome_item', $item['nome_item']);
                            $stmt->bindParam(':peso', $item['peso']);
                            $stmt->bindParam(':quantidade', $nova_quantidade);
                            $stmt->execute();
                            
                            $_SESSION['success'] = 'Quantidade ajustada com sucesso!';
                        } else {
                            $_SESSION['error'] = 'Erro ao ajustar quantidade.';
                        }
                    } else {
                        $_SESSION['error'] = 'Item não encontrado.';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Erro no sistema. Tente novamente.';
                }
            }
            break;
    }
    
    // PADRÃO PRG (Post-Redirect-Get) - Redireciona após processar formulário
    header("Location: dashboard.php");
    exit();
}

// Recuperar mensagens da sessão
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Buscar itens do inventário
try {
    $stmt = $conn->prepare("SELECT * FROM inventario WHERE personagem_id = :personagem_id ORDER BY data_adicao DESC");
    $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
    $stmt->execute();
    $itens = $stmt->fetchAll();
    
    // Calcular peso total
    $peso_total = 0;
    foreach ($itens as $item) {
        $peso_total += ($item['peso'] * $item['quantidade']);
    }
} catch (PDOException $e) {
    $error = 'Erro ao carregar inventário.';
    $itens = [];
    $peso_total = 0;
}

// Buscar criaturas do bestiário
try {
    $stmt = $conn->prepare("
        SELECT b.*, 
               p1.nome_personagem as criador,
               p2.nome_personagem as editor
        FROM bestiario b
        LEFT JOIN personagens p1 ON b.criado_por = p1.id
        LEFT JOIN personagens p2 ON b.editado_por = p2.id
        ORDER BY b.nome_criatura ASC
    ");
    $stmt->execute();
    $criaturas = $stmt->fetchAll();
} catch (PDOException $e) {
    $criaturas = [];
}

// Buscar dados de XP do personagem atual
try {
    $stmt = $conn->prepare("SELECT xp_atual, nivel_atual FROM personagens WHERE id = :id");
    $stmt->bindParam(':id', $_SESSION['personagem_id']);
    $stmt->execute();
    $dados_xp = $stmt->fetch();
    
    $xp_atual = $dados_xp['xp_atual'] ?? 0;
    $nivel_atual = $dados_xp['nivel_atual'] ?? 1;
    
    // Calcular progresso de XP
    $progresso_xp = getProgressoXP($xp_atual, $nivel_atual);
    
    // Buscar histórico de XP
    $historico_xp = getHistoricoXP($conn, $_SESSION['personagem_id'], 10);
} catch (PDOException $e) {
    $xp_atual = 0;
    $nivel_atual = 1;
    $progresso_xp = getProgressoXP(0, 1);
    $historico_xp = [];
}

// Buscar moedas do personagem atual
try {
    $stmt = $conn->prepare("SELECT moedas_cobre, moedas_prata, moedas_ouro, moedas_platina FROM personagens WHERE id = :id");
    $stmt->bindParam(':id', $_SESSION['personagem_id']);
    $stmt->execute();
    $moedas = $stmt->fetch();
    
    $moedas_cobre = $moedas['moedas_cobre'] ?? 0;
    $moedas_prata = $moedas['moedas_prata'] ?? 0;
    $moedas_ouro = $moedas['moedas_ouro'] ?? 0;
    $moedas_platina = $moedas['moedas_platina'] ?? 0;
    
    // Calcular valor total em cobre
    $valor_total_cobre = $moedas_cobre + ($moedas_prata * 10) + ($moedas_ouro * 100) + ($moedas_platina * 1000);
    
    // Buscar histórico de movimentações de moedas
    $stmt = $conn->prepare("
        SELECT m.*, 
               p1.nome_personagem as alterado_por_nome,
               p2.nome_personagem as personagem_origem_nome,
               p3.nome_personagem as personagem_destino_nome
        FROM movimentacoes_moedas m
        LEFT JOIN personagens p1 ON m.alterado_por = p1.id
        LEFT JOIN personagens p2 ON m.personagem_origem = p2.id
        LEFT JOIN personagens p3 ON m.personagem_destino = p3.id
        WHERE m.personagem_id = :personagem_id
        ORDER BY m.data_movimentacao DESC
        LIMIT 10
    ");
    $stmt->bindParam(':personagem_id', $_SESSION['personagem_id']);
    $stmt->execute();
    $historico_moedas = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $moedas_cobre = 0;
    $moedas_prata = 0;
    $moedas_ouro = 0;
    $moedas_platina = 0;
    $valor_total_cobre = 0;
    $historico_moedas = [];
}

// Buscar todos os personagens para o dropdown (apenas para admins)
$todos_personagens = [];
if (isAdmin()) {
    try {
        $stmt = $conn->prepare("SELECT id, nome_personagem, nivel_atual FROM personagens ORDER BY nome_personagem");
        $stmt->execute();
        $todos_personagens = $stmt->fetchAll();
    } catch (PDOException $e) {
        $todos_personagens = [];
    }
}


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - <?php echo htmlspecialchars($_SESSION['nome_personagem']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/dash.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-custom navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-dragon me-2"></i>
                <span class="d-none d-sm-inline">Inventário de </span><?php echo htmlspecialchars($_SESSION['nome_personagem']); ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin.php">
                    <span class="d-none d-sm-inline">admin</span>
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    <span class="d-none d-sm-inline">Sair</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Alertas -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Resumo do Inventário -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card card-custom">
                    <div class="card-body text-center">
                        <i class="fas fa-backpack fa-2x text-primary mb-2"></i>
                        <h5 class="d-none d-md-block">Total de Itens</h5>
                        <h6 class="d-md-none">Itens</h6>
                        <p class="fs-4"><?php echo count($itens); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card card-custom">
                    <div class="card-body text-center">
                        <i class="fas fa-weight-hanging fa-2x text-warning mb-2"></i>
                        <h5 class="d-none d-md-block">Peso Total</h5>
                        <h6 class="d-md-none">Peso</h6>
                        <p class="fs-4 peso-total"><?php echo number_format($peso_total, 2); ?> kg</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card card-custom">
                    <div class="card-body text-center">
                        <i class="fas fa-coins fa-2x text-warning mb-2"></i>
                        <h5 class="d-none d-md-block">Valor Total</h5>
                        <h6 class="d-md-none">Moedas</h6>
                        <p class="fs-6">
                            <?php if ($moedas_platina > 0): ?>
                                <span class="badge bg-info me-1"><?php echo $moedas_platina; ?>💎</span>
                            <?php endif; ?>
                            <?php if ($moedas_ouro > 0): ?>
                                <span class="badge me-1" style="background-color: #ffd700; color: #000;"><?php echo $moedas_ouro; ?>🟡</span>
                            <?php endif; ?>
                            <?php if ($moedas_prata > 0): ?>
                                <span class="badge bg-secondary me-1"><?php echo $moedas_prata; ?>⚪</span>
                            <?php endif; ?>
                            <?php if ($moedas_cobre > 0): ?>
                                <span class="badge bg-warning me-1"><?php echo $moedas_cobre; ?>🟤</span>
                            <?php endif; ?>
                            <?php if ($valor_total_cobre == 0): ?>
                                <span class="text-muted">Sem moedas</span>
                            <?php endif; ?>
                        </p>
                        <small class="text-muted"><?php echo number_format($valor_total_cobre); ?> MC total</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adicionar Item -->
        <div class="card card-custom mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <button class="btn btn-link text-decoration-none p-0 w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdicionar" aria-expanded="false" aria-controls="collapseAdicionar">
                        <i class="fas fa-plus me-2"></i>
                        Adicionar Novo Item
                        <i class="fas fa-chevron-down ms-2 float-end"></i>
                    </button>
                </h5>
            </div>
            <div class="collapse" id="collapseAdicionar">
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="adicionar">
                        
                        <!-- Layout Mobile-First -->
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nome_item" class="form-label">
                                    <i class="fas fa-cube me-2"></i>Nome do Item
                                </label>
                                <input type="text" class="form-control form-control-lg" 
                                       id="nome_item" name="nome_item" required 
                                       placeholder="Ex: Espada Longa">
                            </div>
                            
                            <div class="col-6">
                                <label for="peso" class="form-label">
                                    <i class="fas fa-weight-hanging me-2"></i>Peso (kg)
                                </label>
                                <input type="number" class="form-control form-control-lg" 
                                       id="peso" name="peso" step="0.01" min="0" value="0" required
                                       inputmode="decimal">
                            </div>
                            
                            <div class="col-6">
                                <label for="quantidade" class="form-label">
                                    <i class="fas fa-sort-numeric-up me-2"></i>Quantidade
                                </label>
                                <input type="number" class="form-control form-control-lg" 
                                       id="quantidade" name="quantidade" min="1" value="1" required
                                       inputmode="numeric">
                            </div>
                            
                            <div class="col-12">
                                <label for="descricao" class="form-label">
                                    <i class="fas fa-align-left me-2"></i>Descrição (Opcional)
                                </label>
                                <textarea class="form-control" id="descricao" name="descricao" 
                                          rows="2" placeholder="Descrição opcional do item"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-custom btn-lg">
                                        <i class="fas fa-plus me-2"></i>
                                        Adicionar Item ao Inventário
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Itens -->
        <div class="card card-custom">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Meu Inventário
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($itens)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Inventário Vazio</h5>
                        <p class="text-muted">Adicione alguns itens para começar sua aventura!</p>
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdicionar">
                            <i class="fas fa-plus me-2"></i>Adicionar Primeiro Item
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Layout Desktop -->
                    <div class="d-none d-lg-block">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Descrição</th>
                                        <th>Peso Unit. (kg)</th>
                                        <th>Quantidade</th>
                                        <th>Peso Total (kg)</th>
                                        <th>Adicionado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itens as $item): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-cube me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($item['nome_item']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                                            <td><?php echo number_format($item['peso'], 2); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <!-- Botão Diminuir -->
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="action" value="ajustar_quantidade">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="operacao" value="diminuir">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm me-1" 
                                                                <?php echo $item['quantidade'] <= 1 ? 'disabled title="Use o botão remover para excluir"' : ''; ?>>
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Quantidade -->
                                                    <span class="badge bg-primary mx-2"><?php echo $item['quantidade']; ?></span>
                                                    
                                                    <!-- Botão Aumentar -->
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="action" value="ajustar_quantidade">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="operacao" value="aumentar">
                                                        <button type="submit" class="btn btn-outline-success btn-sm ms-1">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td><?php echo number_format($item['peso'] * $item['quantidade'], 2); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($item['data_adicao'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Botão Transferir -->
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#transferModal<?php echo $item['id']; ?>">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    
                                                    <!-- Botão Remover -->
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="action" value="remover">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger" 
                                                                onclick="return confirm('Tem certeza que deseja remover este item?\n\nItem: <?php echo htmlspecialchars($item['nome_item']); ?>\nQuantidade: <?php echo $item['quantidade']; ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Layout Mobile -->
                    <div class="d-lg-none">
                        <?php foreach ($itens as $item): ?>
                            <div class="card item-card mb-mobile border-start border-primary border-3">
                                <div class="card-body">
                                    <!-- Cabeçalho do Item -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="card-title mb-1">
                                                <i class="fas fa-cube me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($item['nome_item']); ?>
                                            </h6>
                                            <?php if (!empty($item['descricao'])): ?>
                                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($item['descricao']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Botão de Opções -->
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#transferModal<?php echo $item['id']; ?>">
                                                        <i class="fas fa-exchange-alt me-2 text-primary"></i>Transferir Item
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="" style="display: inline; width: 100%;">
                                                        <input type="hidden" name="action" value="remover">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" class="dropdown-item text-danger" 
                                                                onclick="return confirm('Remover <?php echo htmlspecialchars($item['nome_item']); ?>?\n\nQuantidade: <?php echo $item['quantidade']; ?>')">
                                                            <i class="fas fa-trash me-2"></i>Remover Item
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <!-- Informações do Item -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="text-muted small">Peso Unitário</div>
                                                <div class="fw-bold fs-6"><?php echo number_format($item['peso'], 2); ?> kg</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="text-muted small">Peso Total</div>
                                                <div class="fw-bold fs-6 text-primary"><?php echo number_format($item['peso'] * $item['quantidade'], 2); ?> kg</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Controles de Quantidade -->
                                    <div class="quantity-controls">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="ajustar_quantidade">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="operacao" value="diminuir">
                                                <button type="submit" class="btn btn-outline-danger mobile-action-btn me-4" 
                                                        <?php echo $item['quantidade'] <= 1 ? 'disabled title="Mínimo 1 item"' : ''; ?>>
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </form>
                                            
                                            <div class="text-center mx-3">
                                                <div class="text-muted small mb-1">Quantidade</div>
                                                <div class="badge bg-primary badge-quantity"><?php echo $item['quantidade']; ?></div>
                                            </div>
                                            
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="ajustar_quantidade">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="operacao" value="aumentar">
                                                <button type="submit" class="btn btn-outline-success mobile-action-btn ms-4">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Data de Adição -->
                                    <div class="text-center mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($item['data_adicao'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Modais de Transferência (compartilhados entre desktop e mobile) -->
                    <?php foreach ($itens as $item): ?>
                        <div class="modal fade" id="transferModal<?php echo $item['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-exchange-alt me-2"></i>
                                            Transferir Item
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="transferir">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            
                                            <!-- Informações do Item -->
                                            <div class="card bg-light mb-3">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="fas fa-cube me-2"></i>
                                                        <?php echo htmlspecialchars($item['nome_item']); ?>
                                                    </h6>
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <small class="text-muted">Peso Unitário</small>
                                                            <div><?php echo number_format($item['peso'], 2); ?> kg</div>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted">Disponível</small>
                                                            <div><span class="badge bg-primary"><?php echo $item['quantidade']; ?></span></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Quantidade a Transferir -->
                                            <div class="mb-3">
                                                <label for="quantidade_transferir<?php echo $item['id']; ?>" class="form-label">
                                                    <i class="fas fa-sort-numeric-up me-2"></i>Quantidade a Transferir
                                                </label>
                                                <input type="number" class="form-control form-control-lg" 
                                                       id="quantidade_transferir<?php echo $item['id']; ?>" 
                                                       name="quantidade_transferir" 
                                                       min="1" 
                                                       max="<?php echo $item['quantidade']; ?>"
                                                       value="<?php echo $item['quantidade']; ?>"
                                                       required
                                                       inputmode="numeric">
                                                <div class="form-text">Máximo: <?php echo $item['quantidade']; ?></div>
                                            </div>
                                            
                                            <!-- Personagem Destino -->
                                            <div class="mb-3">
                                                <label for="personagem_destino<?php echo $item['id']; ?>" class="form-label">
                                                    <i class="fas fa-user me-2"></i>Personagem Destino
                                                </label>
                                                <input type="text" class="form-control form-control-lg" 
                                                       id="personagem_destino<?php echo $item['id']; ?>" 
                                                       name="personagem_destino" 
                                                       required 
                                                       placeholder="Nome exato do personagem">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-2"></i>Cancelar
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Transferir
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bestiário Compartilhado -->
        <div class="card card-custom mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-dragon me-2"></i>
                    Bestiário Compartilhado
                </h5>
            </div>
            <div class="card-body">
                <!-- Adicionar Criatura -->
                <div class="card mb-4" style="background-color: #f8f9fa;">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <button class="btn btn-link text-decoration-none p-0 w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdicionarCriatura" aria-expanded="false">
                                <i class="fas fa-plus me-2"></i>
                                Adicionar Nova Criatura
                                <i class="fas fa-chevron-down ms-2 float-end"></i>
                            </button>
                        </h6>
                    </div>
                    <div class="collapse" id="collapseAdicionarCriatura">
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="adicionar_criatura">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nome_criatura" class="form-label">
                                            <i class="fas fa-paw me-2"></i>Nome da Criatura
                                        </label>
                                        <input type="text" class="form-control" id="nome_criatura" name="nome_criatura" required placeholder="Ex: Goblin">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tipo_criatura" class="form-label">
                                            <i class="fas fa-tag me-2"></i>Tipo de Criatura
                                        </label>
                                        <input type="text" class="form-control" id="tipo_criatura" name="tipo_criatura" required placeholder="Ex: Humanoide">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="pontos_vida" class="form-label">
                                            <i class="fas fa-heart me-2"></i>Pontos de Vida
                                        </label>
                                        <input type="text" class="form-control" id="pontos_vida" name="pontos_vida" required placeholder="Ex: 2d6 (7)">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="classe_armadura" class="form-label">
                                            <i class="fas fa-shield-alt me-2"></i>Classe de Armadura
                                        </label>
                                        <input type="text" class="form-control" id="classe_armadura" name="classe_armadura" required placeholder="Ex: 15 (Armadura de Couro, Escudo)">
                                    </div>
                                    <div class="col-12">
                                        <label for="descricao_criatura" class="form-label">
                                            <i class="fas fa-align-left me-2"></i>Descrição (Opcional)
                                        </label>
                                        <textarea class="form-control" id="descricao_criatura" name="descricao" rows="3" placeholder="Descreva a criatura, comportamento, habitat, etc..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-plus me-2"></i>
                                                Adicionar ao Bestiário
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lista de Criaturas -->
                <?php if (empty($criaturas)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-dragon fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Bestiário Vazio</h5>
                        <p class="text-muted">Seja o primeiro a adicionar uma criatura!</p>
                        <button class="btn btn-outline-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdicionarCriatura">
                            <i class="fas fa-plus me-2"></i>Adicionar Primeira Criatura
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Layout Desktop -->
                    <div class="d-none d-lg-block">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Criatura</th>
                                        <th>Tipo</th>
                                        <th>Pontos de Vida</th>
                                        <th>CA</th>
                                        <th>Descrição</th>
                                        <th>Criado por</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($criaturas as $criatura): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-dragon me-2 text-danger"></i>
                                                <strong><?php echo htmlspecialchars($criatura['nome_criatura']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($criatura['tipo_criatura']); ?></td>
                                            <td><?php echo htmlspecialchars($criatura['pontos_vida']); ?></td>
                                            <td><?php echo htmlspecialchars($criatura['classe_armadura']); ?></td>
                                            <td>
                                                <?php if (!empty($criatura['descricao'])): ?>
                                                    <span class="text-muted" title="<?php echo htmlspecialchars($criatura['descricao']); ?>">
                                                        <?php echo strlen($criatura['descricao']) > 50 ? substr(htmlspecialchars($criatura['descricao']), 0, 50) . '...' : htmlspecialchars($criatura['descricao']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <small class="text-muted">Sem descrição</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($criatura['criador']); ?>
                                                    <?php if ($criatura['editor']): ?>
                                                        <br><em>Editado por: <?php echo htmlspecialchars($criatura['editor']); ?></em>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $criatura['id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="action" value="remover_criatura">
                                                        <input type="hidden" name="criatura_id" value="<?php echo $criatura['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Tem certeza que deseja remover <?php echo htmlspecialchars($criatura['nome_criatura']); ?>?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Layout Mobile -->
                    <div class="d-lg-none">
                        <?php foreach ($criaturas as $criatura): ?>
                            <div class="card mb-3 border-start border-danger border-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="card-title mb-1">
                                                <i class="fas fa-dragon me-2 text-danger"></i>
                                                <?php echo htmlspecialchars($criatura['nome_criatura']); ?>
                                            </h6>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($criatura['tipo_criatura']); ?></p>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $criatura['id']; ?>">
                                                        <i class="fas fa-edit me-2 text-primary"></i>Editar
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="" style="display: inline; width: 100%;">
                                                        <input type="hidden" name="action" value="remover_criatura">
                                                        <input type="hidden" name="criatura_id" value="<?php echo $criatura['id']; ?>">
                                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Remover <?php echo htmlspecialchars($criatura['nome_criatura']); ?>?')">
                                                            <i class="fas fa-trash me-2"></i>Remover
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-3 mb-3">
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="text-muted small">Pontos de Vida</div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($criatura['pontos_vida']); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="text-muted small">Classe de Armadura</div>
                                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($criatura['classe_armadura']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($criatura['descricao'])): ?>
                                        <div class="card bg-light mb-3">
                                            <div class="card-body p-2">
                                                <small class="text-muted">Descrição:</small>
                                                <p class="mb-0 small"><?php echo htmlspecialchars($criatura['descricao']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-center mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            Criado por <?php echo htmlspecialchars($criatura['criador']); ?>
                                            <?php if ($criatura['editor']): ?>
                                                <br>Editado por <?php echo htmlspecialchars($criatura['editor']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Modais de Edição -->
                    <?php foreach ($criaturas as $criatura): ?>
                        <div class="modal fade" id="editModal<?php echo $criatura['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-edit me-2"></i>
                                            Editar Criatura
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="editar_criatura">
                                            <input type="hidden" name="criatura_id" value="<?php echo $criatura['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label for="edit_nome_criatura<?php echo $criatura['id']; ?>" class="form-label">Nome da Criatura</label>
                                                <input type="text" class="form-control" id="edit_nome_criatura<?php echo $criatura['id']; ?>" name="nome_criatura" value="<?php echo htmlspecialchars($criatura['nome_criatura']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="edit_tipo_criatura<?php echo $criatura['id']; ?>" class="form-label">Tipo de Criatura</label>
                                                <input type="text" class="form-control" id="edit_tipo_criatura<?php echo $criatura['id']; ?>" name="tipo_criatura" value="<?php echo htmlspecialchars($criatura['tipo_criatura']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="edit_pontos_vida<?php echo $criatura['id']; ?>" class="form-label">Pontos de Vida</label>
                                                <input type="text" class="form-control" id="edit_pontos_vida<?php echo $criatura['id']; ?>" name="pontos_vida" value="<?php echo htmlspecialchars($criatura['pontos_vida']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="edit_classe_armadura<?php echo $criatura['id']; ?>" class="form-label">Classe de Armadura</label>
                                                <input type="text" class="form-control" id="edit_classe_armadura<?php echo $criatura['id']; ?>" name="classe_armadura" value="<?php echo htmlspecialchars($criatura['classe_armadura']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="edit_descricao<?php echo $criatura['id']; ?>" class="form-label">Descrição</label>
                                                <textarea class="form-control" id="edit_descricao<?php echo $criatura['id']; ?>" name="descricao" rows="3"><?php echo htmlspecialchars($criatura['descricao']); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-2"></i>Cancelar
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Salvar Alterações
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
            </div>
            
        </div>
        <!-- Sistema de XP -->
        <div class="card card-custom mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-star me-2"></i>
                    Sistema de Experiência
                </h5>
            </div>
            <div class="card-body">
                <!-- Informações de XP do Personagem -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-level-up-alt fa-2x mb-2"></i>
                                <h6>Nível Atual</h6>
                                <h3><?php echo $nivel_atual; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-star fa-2x mb-2"></i>
                                <h6>XP Atual</h6>
                                <h4><?php echo number_format($xp_atual); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6>Progresso para Nível <?php echo $nivel_atual + 1; ?></h6>
                                <?php if ($progresso_xp['nivel_maximo']): ?>
                                    <h5><i class="fas fa-crown me-2"></i>Nível Máximo Atingido!</h5>
                                <?php else: ?>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo $progresso_xp['porcentagem']; ?>%">
                                            <?php echo $progresso_xp['porcentagem']; ?>%
                                        </div>
                                    </div>
                                    <small>
                                        Faltam <?php echo number_format($progresso_xp['xp_para_proximo']); ?> XP para o próximo nível
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulário para Adicionar XP (apenas para admins) -->
                <?php if (isAdmin()): ?>
    <div class="card mb-4" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
        <div class="card-header">
            <h6 class="mb-0">
                <button class="btn btn-link text-decoration-none p-0 w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdicionarXP" aria-expanded="false">
                    <i class="fas fa-plus me-2"></i>
                    Adicionar XP (Apenas Administradores)
                    <i class="fas fa-chevron-down ms-2 float-end"></i>
                </button>
            </h6>
        </div>
        <div class="collapse" id="collapseAdicionarXP">
            <div class="card-body">
                <form method="POST" action="" id="formXP">
                    <input type="hidden" name="action" value="adicionar_xp">
                    
                    <!-- Tipo de Distribuição -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">
                                <i class="fas fa-users me-2"></i>Tipo de Distribuição
                            </label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="tipo_distribuicao" id="individual" value="individual" checked>
                                <label class="btn btn-outline-primary" for="individual">
                                    <i class="fas fa-user me-2"></i>Individual
                                </label>
                                
                                <input type="radio" class="btn-check" name="tipo_distribuicao" id="grupo" value="grupo">
                                <label class="btn btn-outline-success" for="grupo">
                                    <i class="fas fa-users me-2"></i>Grupo
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- XP Total -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="xp_total" class="form-label">
                                <i class="fas fa-star me-2"></i><span id="labelXP">XP a Adicionar</span>
                            </label>
                            <input type="number" class="form-control" id="xp_total" name="xp_total" 
                                   min="1" max="500000" required placeholder="Ex: 1000">
                            <div class="form-text" id="xpHelp">XP que será adicionado</div>
                        </div>
                        <div class="col-md-6">
                            <label for="motivo_xp" class="form-label">
                                <i class="fas fa-comment me-2"></i>Motivo/Descrição
                            </label>
                            <input type="text" class="form-control" id="motivo_xp" name="motivo_xp" 
                                   placeholder="Ex: Derrotar o Dragão Vermelho" maxlength="255">
                        </div>
                    </div>
                    
                    <!-- Seleção Individual -->
                    <div class="row mb-3" id="selecaoIndividual">
                        <div class="col-12">
                            <label for="personagem_alvo" class="form-label">
                                <i class="fas fa-user me-2"></i>Personagem
                            </label>
                            <select class="form-control" id="personagem_alvo" name="personagem_alvo">
                                <option value="">Selecione um personagem</option>
                                <?php foreach ($todos_personagens as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $p['id'] == $_SESSION['personagem_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['nome_personagem']); ?> (Nível <?php echo $p['nivel_atual']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Seleção em Grupo -->
                    <div class="row mb-3" id="selecaoGrupo" style="display: none;">
                        <div class="col-12">
                            <label class="form-label">
                                <i class="fas fa-users me-2"></i>Personagens do Grupo
                            </label>
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <button type="button" class="btn btn-outline-success btn-sm" id="selecionarTodos">
                                                <i class="fas fa-check-double me-1"></i>Selecionar Todos
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm ms-1" id="limparSelecao">
                                                <i class="fas fa-times me-1"></i>Limpar
                                            </button>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <small class="text-muted">
                                                <span id="contadorSelecionados">0</span> personagem(s) selecionado(s)
                                                <br><span id="xpPorPersonagem">XP por personagem: 0</span>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <?php foreach ($todos_personagens as $p): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input personagem-checkbox" type="checkbox" 
                                                   name="personagens_grupo[]" value="<?php echo $p['id']; ?>" 
                                                   id="personagem_<?php echo $p['id']; ?>">
                                            <label class="form-check-label d-flex justify-content-between" for="personagem_<?php echo $p['id']; ?>">
                                                <span>
                                                    <i class="fas fa-user me-2"></i>
                                                    <?php echo htmlspecialchars($p['nome_personagem']); ?>
                                                </span>
                                                <span class="badge bg-secondary">Nível <?php echo $p['nivel_atual']; ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-plus me-2"></i>
                                    <span id="botaoTexto">Adicionar XP</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

                <!-- Histórico de XP -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Histórico de XP
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($historico_xp)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-star fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Nenhum XP ganho ainda</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>XP Ganho</th>
                                            <th>Nível</th>
                                            <th>Motivo</th>
                                            <th>Adicionado por</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historico_xp as $entrada): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($entrada['data_ganho'])); ?></td>
                                                <td>
                                                    <span class="badge bg-success">+<?php echo number_format($entrada['xp_ganho']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($entrada['nivel_novo'] > $entrada['nivel_anterior']): ?>
                                                        <span class="badge bg-warning">
                                                            <?php echo $entrada['nivel_anterior']; ?> → <?php echo $entrada['nivel_novo']; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <?php echo $entrada['nivel_novo']; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($entrada['motivo'] ?: 'Sem descrição'); ?></td>
                                                <td><?php echo htmlspecialchars($entrada['adicionado_por_nome']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
        <!-- Sistema de Moedas -->
        <div class="card card-custom mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-coins me-2"></i>
                    Sistema de Moedas D&D 3.5
                </h5>
            </div>
            <div class="card-body">
                <!-- Resumo das Moedas -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card border-warning">
                            <div class="card-body text-center p-2">
                                <i class="fas fa-circle text-warning fa-2x mb-2" title="Moeda de Cobre"></i>
                                <h6 class="mb-1">Cobre</h6>
                                <h4 class="text-warning"><?php echo number_format($moedas_cobre); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card border-secondary">
                            <div class="card-body text-center p-2">
                                <i class="fas fa-circle text-secondary fa-2x mb-2" title="Moeda de Prata"></i>
                                <h6 class="mb-1">Prata</h6>
                                <h4 class="text-secondary"><?php echo number_format($moedas_prata); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card border-warning" style="border-color: #ffd700 !important;">
                            <div class="card-body text-center p-2">
                                <i class="fas fa-circle fa-2x mb-2" style="color: #ffd700;" title="Moeda de Ouro"></i>
                                <h6 class="mb-1">Ouro</h6>
                                <h4 style="color: #ffd700;"><?php echo number_format($moedas_ouro); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card border-info">
                            <div class="card-body text-center p-2">
                                <i class="fas fa-circle text-info fa-2x mb-2" title="Moeda de Platina"></i>
                                <h6 class="mb-1">Platina</h6>
                                <h4 class="text-info"><?php echo number_format($moedas_platina); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Valor Total -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-dark text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-calculator fa-2x mb-2"></i>
                                <h6>Valor Total (em moedas de cobre)</h6>
                                <h3><?php echo number_format($valor_total_cobre); ?> MC</h3>
                                <small class="text-muted">
                                    Equivale a: <?php echo number_format($valor_total_cobre / 1000, 2); ?> Platina | 
                                    <?php echo number_format($valor_total_cobre / 100, 2); ?> Ouro | 
                                    <?php echo number_format($valor_total_cobre / 10, 1); ?> Prata
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações com Moedas -->
                <div class="row">
                    <!-- Transferir Moedas -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <button class="btn btn-link text-decoration-none p-0 w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTransferirMoedas">
                                        <i class="fas fa-exchange-alt me-2"></i>
                                        Transferir Moedas
                                        <i class="fas fa-chevron-down ms-2 float-end"></i>
                                    </button>
                                </h6>
                            </div>
                            <div class="collapse" id="collapseTransferirMoedas">
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="transferir_moedas">
                                        
                                        <div class="mb-3">
                                            <label for="tipo_moeda_transferir" class="form-label">Tipo de Moeda</label>
                                            <select class="form-control" id="tipo_moeda_transferir" name="tipo_moeda" required>
                                                <option value="">Selecione</option>
                                                <option value="cobre">🟤 Cobre (<?php echo number_format($moedas_cobre); ?>)</option>
                                                <option value="prata">⚪ Prata (<?php echo number_format($moedas_prata); ?>)</option>
                                                <option value="ouro">🟡 Ouro (<?php echo number_format($moedas_ouro); ?>)</option>
                                                <option value="platina">🔵 Platina (<?php echo number_format($moedas_platina); ?>)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="quantidade_transferir" class="form-label">Quantidade</label>
                                            <input type="number" class="form-control" id="quantidade_transferir" name="quantidade" min="1" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="personagem_destino_moedas" class="form-label">Personagem Destino</label>
                                            <input type="text" class="form-control" id="personagem_destino_moedas" name="personagem_destino" required placeholder="Nome exato do personagem">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="motivo_transferencia" class="form-label">Motivo (Opcional)</label>
                                            <input type="text" class="form-control" id="motivo_transferencia" name="motivo" placeholder="Ex: Pagamento de dívida">
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Transferir Moedas
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Converter Moedas -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <button class="btn btn-link text-decoration-none p-0 w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseConverterMoedas">
                                        <i class="fas fa-sync-alt me-2"></i>
                                        Converter Moedas
                                        <i class="fas fa-chevron-down ms-2 float-end"></i>
                                    </button>
                                </h6>
                            </div>
                            <div class="collapse" id="collapseConverterMoedas">
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="converter_moedas">
                                        
                                        <div class="mb-3">
                                            <label for="tipo_origem" class="form-label">Converter De:</label>
                                            <select class="form-control" id="tipo_origem" name="tipo_origem" required>
                                                <option value="">Selecione</option>
                                                <option value="cobre">🟤 Cobre (<?php echo number_format($moedas_cobre); ?>)</option>
                                                <option value="prata">⚪ Prata (<?php echo number_format($moedas_prata); ?>)</option>
                                                <option value="ouro">🟡 Ouro (<?php echo number_format($moedas_ouro); ?>)</option>
                                                <option value="platina">🔵 Platina (<?php echo number_format($moedas_platina); ?>)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="quantidade_converter" class="form-label">Quantidade</label>
                                            <input type="number" class="form-control" id="quantidade_converter" name="quantidade" min="1" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tipo_destino" class="form-label">Converter Para:</label>
                                            <select class="form-control" id="tipo_destino" name="tipo_destino" required>
                                                <option value="">Selecione</option>
                                                <option value="cobre">🟤 Cobre</option>
                                                <option value="prata">⚪ Prata</option>
                                                <option value="ouro">🟡 Ouro</option>
                                                <option value="platina">🔵 Platina</option>
                                            </select>
                                        </div>
                                        
                                        <div class="alert alert-info small">
                                            <strong>Taxas de conversão:</strong><br>
                                            1 Prata = 10 Cobre | 1 Ouro = 10 Prata | 1 Platina = 10 Ouro
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-sync-alt me-2"></i>Converter Moedas
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Painel Admin para Ajustar Moedas -->
                <?php if (isAdmin()): ?>
                    <div class="card mt-4" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <button class="btn btn-link text-decoration-none p-0 w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAjustarMoedas">
                                    <i class="fas fa-tools me-2"></i>
                                    Ajustar Moedas (Apenas Administradores)
                                    <i class="fas fa-chevron-down ms-2 float-end"></i>
                                </button>
                            </h6>
                        </div>
                        <div class="collapse" id="collapseAjustarMoedas">
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="ajustar_moedas">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="personagem_alvo_moedas" class="form-label">Personagem</label>
                                            <select class="form-control" id="personagem_alvo_moedas" name="personagem_alvo" required>
                                                <option value="">Selecione um personagem</option>
                                                <?php foreach ($todos_personagens as $p): ?>
                                                    <option value="<?php echo $p['id']; ?>">
                                                        <?php echo htmlspecialchars($p['nome_personagem']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="tipo_moeda_admin" class="form-label">Tipo de Moeda</label>
                                            <select class="form-control" id="tipo_moeda_admin" name="tipo_moeda" required>
                                                <option value="">Selecione</option>
                                                <option value="cobre">🟤 Cobre</option>
                                                <option value="prata">⚪ Prata</option>
                                                <option value="ouro">🟡 Ouro</option>
                                                <option value="platina">🔵 Platina</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="operacao_moedas" class="form-label">Operação</label>
                                            <select class="form-control" id="operacao_moedas" name="operacao" required>
                                                <option value="">Selecione</option>
                                                <option value="adicionar">➕ Adicionar</option>
                                                <option value="remover">➖ Remover</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="quantidade_admin" class="form-label">Quantidade</label>
                                            <input type="number" class="form-control" id="quantidade_admin" name="quantidade" min="1" required>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="motivo_admin" class="form-label">Motivo</label>
                                            <input type="text" class="form-control" id="motivo_admin" name="motivo" placeholder="Ex: Recompensa da quest">
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-coins me-2"></i>Ajustar Moedas
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Histórico de Movimentações -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Histórico de Movimentações
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($historico_moedas)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-coins fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Nenhuma movimentação de moedas ainda</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Operação</th>
                                            <th>Moeda</th>
                                            <th>Quantidade</th>
                                            <th>Saldo</th>
                                            <th>Detalhes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historico_moedas as $mov): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($mov['data_movimentacao'])); ?></td>
                                                <td>
                                                    <?php
                                                    $operacao_icons = [
                                                        'adicionar' => '<span class="badge bg-success"><i class="fas fa-plus"></i> Adição</span>',
                                                        'remover' => '<span class="badge bg-danger"><i class="fas fa-minus"></i> Remoção</span>',
                                                        'transferencia_saida' => '<span class="badge bg-warning"><i class="fas fa-arrow-up"></i> Enviou</span>',
                                                        'transferencia_entrada' => '<span class="badge bg-info"><i class="fas fa-arrow-down"></i> Recebeu</span>'
                                                    ];
                                                    echo $operacao_icons[$mov['operacao']] ?? $mov['operacao'];
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $moeda_icons = [
                                                        'cobre' => '🟤',
                                                        'prata' => '⚪',
                                                        'ouro' => '🟡',
                                                        'platina' => '🔵'
                                                    ];
                                                    echo $moeda_icons[$mov['tipo_moeda']] . ' ' . ucfirst($mov['tipo_moeda']);
                                                    ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo number_format($mov['quantidade_alterada']); ?></strong>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo number_format($mov['quantidade_anterior']); ?> → 
                                                        <strong><?php echo number_format($mov['quantidade_nova']); ?></strong>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($mov['motivo'])): ?>
                                                        <small><?php echo htmlspecialchars($mov['motivo']); ?></small><br>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($mov['operacao'] === 'transferencia_saida' && $mov['personagem_destino_nome']): ?>
                                                        <small class="text-muted">Para: <?php echo htmlspecialchars($mov['personagem_destino_nome']); ?></small>
                                                    <?php elseif ($mov['operacao'] === 'transferencia_entrada' && $mov['personagem_origem_nome']): ?>
                                                        <small class="text-muted">De: <?php echo htmlspecialchars($mov['personagem_origem_nome']); ?></small>
                                                    <?php elseif (in_array($mov['operacao'], ['adicionar', 'remover']) && $mov['alterado_por_nome']): ?>
                                                        <small class="text-muted">Por: <?php echo htmlspecialchars($mov['alterado_por_nome']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

     

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script otimizado para mobile -->
    <script>
        // Melhorar experiência mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar feedback visual nos botões
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    if (!this.disabled) {
                        this.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }
                });
            });
            
            // Auto-expandir formulário de adicionar se inventário vazio
            const inventarioVazio = document.querySelector('.text-center .fa-box-open');
            if (inventarioVazio) {
                const collapseElement = document.getElementById('collapseAdicionar');
                if (collapseElement) {
                    new bootstrap.Collapse(collapseElement, { show: true });
                }
            }
            
            // Melhorar modais para mobile
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('shown.bs.modal', function() {
                    // Focar no primeiro input do modal em desktop
                    if (window.innerWidth > 768) {
                        const firstInput = modal.querySelector('input[type="number"], input[type="text"]');
                        if (firstInput) {
                            firstInput.focus();
                        }
                    }
                });
            });
            
            // Otimizar inputs numéricos para mobile
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                // Melhorar UX para quantidade
                if (input.name === 'quantidade' || input.name === 'quantidade_transferir') {
                    input.addEventListener('focus', function() {
                        this.select();
                    });
                }
            });
            
            // Feedback visual para formulários de ajuste de quantidade
            const quantityForms = document.querySelectorAll('form input[name="action"][value="ajustar_quantidade"]');
            quantityForms.forEach(actionInput => {
                const form = actionInput.closest('form');
                const button = form.querySelector('button[type="submit"]');
                
                form.addEventListener('submit', function() {
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    button.disabled = true;
                    
                    // Restaurar após timeout (fallback)
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    }, 3000);
                });
            });
            
            // Melhorar dropdowns para mobile
            const dropdowns = document.querySelectorAll('.dropdown-toggle');
            dropdowns.forEach(dropdown => {
                dropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
            
            // Função para mostrar notificações toast
            function showToast(message, type = 'success') {
                const toastContainer = document.getElementById('toast-container') || createToastContainer();
                
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type} border-0`;
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                toastContainer.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                // Remover toast após esconder
                toast.addEventListener('hidden.bs.toast', function() {
                    toast.remove();
                });
            }
            
            function createToastContainer() {
                const container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
                return container;
            }
            
            // Mostrar toast se houver mensagem de sucesso ou erro via PHP
            <?php if ($success): ?>
                showToast('<?php echo addslashes($success); ?>', 'success');
            <?php endif; ?>
            
            <?php if ($error): ?>
                showToast('<?php echo addslashes($error); ?>', 'danger');
            <?php endif; ?>
        });
        
        // Função para atualizar peso estimado no modal de transferência
        function atualizarPesoTransferencia(itemId, pesoUnitario) {
            const quantidadeInput = document.getElementById('quantidade_transferir' + itemId);
            
            if (quantidadeInput) {
                quantidadeInput.addEventListener('input', function() {
                    const quantidade = parseInt(this.value) || 0;
                    const pesoTotal = (quantidade * pesoUnitario).toFixed(2);
                    
                    // Atualizar display se existir
                    const pesoDisplay = document.getElementById('peso_transferencia_' + itemId);
                    if (pesoDisplay) {
                        pesoDisplay.textContent = pesoTotal + ' kg';
                    }
                });
            }
        }
        
        // Inicializar para todos os modais
        <?php foreach ($itens as $item): ?>
            atualizarPesoTransferencia(<?php echo $item['id']; ?>, <?php echo $item['peso']; ?>);
        <?php endforeach; ?>
        
        // Prevenir zoom no iOS em inputs (opcional - remove se causar problemas)
        if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
            document.addEventListener('touchstart', function() {
                const viewportMeta = document.querySelector('meta[name="viewport"]');
                if (viewportMeta) {
                    viewportMeta.content = 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no';
                }
            });
        }
    </script>
    <script>
// Script para o sistema de XP
document.addEventListener('DOMContentLoaded', function() {
    const radioIndividual = document.getElementById('individual');
    const radioGrupo = document.getElementById('grupo');
    const selecaoIndividual = document.getElementById('selecaoIndividual');
    const selecaoGrupo = document.getElementById('selecaoGrupo');
    const labelXP = document.getElementById('labelXP');
    const xpHelp = document.getElementById('xpHelp');
    const botaoTexto = document.getElementById('botaoTexto');
    const xpTotalInput = document.getElementById('xp_total');
    const checkboxes = document.querySelectorAll('.personagem-checkbox');
    const contadorSelecionados = document.getElementById('contadorSelecionados');
    const xpPorPersonagem = document.getElementById('xpPorPersonagem');
    const selecionarTodos = document.getElementById('selecionarTodos');
    const limparSelecao = document.getElementById('limparSelecao');
    const personagemAlvo = document.getElementById('personagem_alvo');

    function alterarModo() {
        if (radioGrupo.checked) {
            // Modo Grupo
            selecaoIndividual.style.display = 'none';
            selecaoGrupo.style.display = 'block';
            labelXP.textContent = 'XP Total para Distribuir';
            xpHelp.textContent = 'XP total que será dividido entre os personagens selecionados';
            botaoTexto.textContent = 'Distribuir XP em Grupo';
            personagemAlvo.removeAttribute('required');
            atualizarContadores();
        } else {
            // Modo Individual
            selecaoIndividual.style.display = 'block';
            selecaoGrupo.style.display = 'none';
            labelXP.textContent = 'XP a Adicionar';
            xpHelp.textContent = 'XP que será adicionado ao personagem';
            botaoTexto.textContent = 'Adicionar XP';
            personagemAlvo.setAttribute('required', 'required');
        }
    }

    function atualizarContadores() {
        const selecionados = document.querySelectorAll('.personagem-checkbox:checked').length;
        const xpTotal = parseInt(xpTotalInput.value) || 0;
        const xpCada = selecionados > 0 ? Math.floor(xpTotal / selecionados) : 0;
        
        contadorSelecionados.textContent = selecionados;
        xpPorPersonagem.textContent = `XP por personagem: ${xpCada.toLocaleString()}`;
    }

    // Event listeners
    radioIndividual.addEventListener('change', alterarModo);
    radioGrupo.addEventListener('change', alterarModo);
    xpTotalInput.addEventListener('input', atualizarContadores);
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', atualizarContadores);
    });

    selecionarTodos.addEventListener('click', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        atualizarContadores();
    });

    limparSelecao.addEventListener('click', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        atualizarContadores();
    });

    // Inicializar
    alterarModo();
});
</script>
</body>
</html>