<?php
/**
 * Funções do Sistema de XP - D&D 3.5
 */

/**
 * Calcular nível baseado no XP atual
 */
function calcularNivel($xp_atual) {
    $niveis = [
        1 => 0,
        2 => 1000,
        3 => 3000,
        4 => 6000,
        5 => 10000,
        6 => 15000,
        7 => 21000,
        8 => 28000,
        9 => 36000,
        10 => 45000,
        11 => 55000,
        12 => 66000,
        13 => 78000,
        14 => 91000,
        15 => 105000,
        16 => 120000,
        17 => 136000,
        18 => 153000,
        19 => 171000,
        20 => 190000
    ];
    
    $nivel = 1;
    foreach ($niveis as $n => $xp_necessario) {
        if ($xp_atual >= $xp_necessario) {
            $nivel = $n;
        } else {
            break;
        }
    }
    
    return $nivel;
}

/**
 * Obter XP necessário para o próximo nível
 */
function getXpProximoNivel($nivel_atual) {
    $niveis = [
        1 => 1000,
        2 => 3000,
        3 => 6000,
        4 => 10000,
        5 => 15000,
        6 => 21000,
        7 => 28000,
        8 => 36000,
        9 => 45000,
        10 => 55000,
        11 => 66000,
        12 => 78000,
        13 => 91000,
        14 => 105000,
        15 => 120000,
        16 => 136000,
        17 => 153000,
        18 => 171000,
        19 => 190000,
        20 => 190000 // Nível máximo
    ];
    
    return $niveis[$nivel_atual] ?? 190000;
}

/**
 * Obter XP necessário para o nível atual
 */
function getXpNivelAtual($nivel_atual) {
    $niveis = [
        1 => 0,
        2 => 1000,
        3 => 3000,
        4 => 6000,
        5 => 10000,
        6 => 15000,
        7 => 21000,
        8 => 28000,
        9 => 36000,
        10 => 45000,
        11 => 55000,
        12 => 66000,
        13 => 78000,
        14 => 91000,
        15 => 105000,
        16 => 120000,
        17 => 136000,
        18 => 153000,
        19 => 171000,
        20 => 190000
    ];
    
    return $niveis[$nivel_atual] ?? 0;
}

/**
 * Adicionar XP a um personagem
 */
function adicionarXP($conn, $personagem_id, $xp_ganho, $motivo, $adicionado_por) {
    try {
        // Buscar dados atuais do personagem
        $stmt = $conn->prepare("SELECT xp_atual, nivel_atual FROM personagens WHERE id = :id");
        $stmt->bindParam(':id', $personagem_id);
        $stmt->execute();
        $personagem = $stmt->fetch();
        
        if (!$personagem) {
            return false;
        }
        
        $xp_anterior = $personagem['xp_atual'];
        $nivel_anterior = $personagem['nivel_atual'];
        $xp_novo = $xp_anterior + $xp_ganho;
        $nivel_novo = calcularNivel($xp_novo);
        
        // Atualizar XP e nível do personagem
        $stmt = $conn->prepare("UPDATE personagens SET xp_atual = :xp_novo, nivel_atual = :nivel_novo WHERE id = :id");
        $stmt->bindParam(':xp_novo', $xp_novo);
        $stmt->bindParam(':nivel_novo', $nivel_novo);
        $stmt->bindParam(':id', $personagem_id);
        $stmt->execute();
        
        // Registrar no histórico
        $stmt = $conn->prepare("INSERT INTO historico_xp (personagem_id, xp_ganho, xp_anterior, xp_novo, nivel_anterior, nivel_novo, motivo, adicionado_por) VALUES (:personagem_id, :xp_ganho, :xp_anterior, :xp_novo, :nivel_anterior, :nivel_novo, :motivo, :adicionado_por)");
        $stmt->bindParam(':personagem_id', $personagem_id);
        $stmt->bindParam(':xp_ganho', $xp_ganho);
        $stmt->bindParam(':xp_anterior', $xp_anterior);
        $stmt->bindParam(':xp_novo', $xp_novo);
        $stmt->bindParam(':nivel_anterior', $nivel_anterior);
        $stmt->bindParam(':nivel_novo', $nivel_novo);
        $stmt->bindParam(':motivo', $motivo);
        $stmt->bindParam(':adicionado_por', $adicionado_por);
        $stmt->execute();
        
        return [
            'sucesso' => true,
            'nivel_anterior' => $nivel_anterior,
            'nivel_novo' => $nivel_novo,
            'subiu_nivel' => $nivel_novo > $nivel_anterior,
            'xp_anterior' => $xp_anterior,
            'xp_novo' => $xp_novo
        ];
        
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Obter progresso de XP para exibição
 */
function getProgressoXP($xp_atual, $nivel_atual) {
    $xp_nivel_atual = getXpNivelAtual($nivel_atual);
    $xp_proximo_nivel = getXpProximoNivel($nivel_atual);
    
    if ($nivel_atual >= 20) {
        return [
            'xp_para_proximo' => 0,
            'xp_necessario' => 0,
            'porcentagem' => 100,
            'nivel_maximo' => true
        ];
    }
    
    $xp_para_proximo = $xp_proximo_nivel - $xp_atual;
    $xp_necessario = $xp_proximo_nivel - $xp_nivel_atual;
    $xp_progresso = $xp_atual - $xp_nivel_atual;
    $porcentagem = ($xp_progresso / $xp_necessario) * 100;
    
    return [
        'xp_para_proximo' => $xp_para_proximo,
        'xp_necessario' => $xp_necessario,
        'xp_progresso' => $xp_progresso,
        'porcentagem' => round($porcentagem, 1),
        'nivel_maximo' => false
    ];
}

/**
 * Obter histórico de XP de um personagem
 */
function getHistoricoXP($conn, $personagem_id, $limite = 10) {
    try {
        $stmt = $conn->prepare("
            SELECT h.*, p.nome_personagem as adicionado_por_nome
            FROM historico_xp h
            LEFT JOIN personagens p ON h.adicionado_por = p.id
            WHERE h.personagem_id = :personagem_id
            ORDER BY h.data_ganho DESC
            LIMIT :limite
        ");
        $stmt->bindParam(':personagem_id', $personagem_id);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}
?>