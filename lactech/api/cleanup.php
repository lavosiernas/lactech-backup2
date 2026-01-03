<?php
/**
 * API de Limpeza de Dados
 * Permite limpar dados do banco por categoria
 * IMPORTANTE: NUNCA apaga usuários (users) ou fazendas (farms)
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/Database.class.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$farm_id = $_SESSION['farm_id'] ?? 1;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';
        $categories = $_POST['categories'] ?? [];
        
        if ($action !== 'cleanup') {
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
            exit;
        }
        
        if (empty($categories) || !is_array($categories)) {
            echo json_encode(['success' => false, 'error' => 'Nenhuma categoria selecionada']);
            exit;
        }
        
        // Definir categorias de tabelas (NUNCA incluir users ou farms)
        $tables_by_category = [
            'rebanho' => [
                'animals',
                'animal_weights',
                'animal_groups',
                'body_condition_scores',
                'pedigree_records'
            ],
            'alimentacao' => [
                'feed_records',
                'feed_compositions',
                'nutritional_parameters',
                'ideal_feed_calculations',
                'feed_comparisons',
                'animal_weights' // Pesos também são usados em alimentação
            ],
            'volume' => [
                'volume_records',
                'milk_production'
            ],
            'qualidade' => [
                'quality_tests'
            ],
            'reproducao' => [
                'pregnancies',
                'inseminations',
                'heat_records',
                'calvings'
            ],
            'sanitario' => [
                'health_records',
                'vaccinations',
                'treatments',
                'medical_examinations'
            ],
            'financeiro' => [
                'financial_records',
                'payments',
                'expenses'
            ],
            'touros' => [
                'bulls',
                'bull_services',
                'bull_performances',
                'bull_health_records'
            ],
            'outros' => [
                'notifications',
                'activities',
                'photos',
                'transponders',
                'milking_groups'
            ]
        ];
        
        // Se "tudo" está selecionado, usar todas as categorias exceto users/farms
        if (in_array('tudo', $categories)) {
            $categories = array_keys($tables_by_category);
        }
        
        // Coletar todas as tabelas a serem limpas
        $tables_to_clean = [];
        foreach ($categories as $category) {
            if (isset($tables_by_category[$category])) {
                $tables_to_clean = array_merge($tables_to_clean, $tables_by_category[$category]);
            }
        }
        
        // Remover duplicatas
        $tables_to_clean = array_unique($tables_to_clean);
        
        if (empty($tables_to_clean)) {
            echo json_encode(['success' => false, 'error' => 'Nenhuma tabela encontrada para limpar']);
            exit;
        }
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        $cleaned_tables = [];
        $errors = [];
        
        try {
            // Limpar cada tabela
            foreach ($tables_to_clean as $table) {
                // Verificar se a tabela existe
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    continue; // Tabela não existe, pular
                }
                
                // Verificar se a tabela tem farm_id
                $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'farm_id'");
                $has_farm_id = $stmt->rowCount() > 0;
                
                try {
                    if ($has_farm_id) {
                        // Limpar apenas registros da fazenda atual
                        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE farm_id = ?");
                        $stmt->execute([$farm_id]);
                        $cleaned_tables[] = $table;
                    } else {
                        // Limpar todos os registros (tabelas sem farm_id)
                        // Mas NUNCA limpar users ou farms
                        if ($table === 'users' || $table === 'farms') {
                            continue; // Pular users e farms
                        }
                        $pdo->exec("DELETE FROM `$table`");
                        $cleaned_tables[] = $table;
                    }
                } catch (PDOException $e) {
                    $errors[] = "Erro ao limpar $table: " . $e->getMessage();
                }
            }
            
            // Confirmar transação
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Limpeza concluída com sucesso',
                'cleaned_tables' => $cleaned_tables,
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao executar limpeza: ' . $e->getMessage()
            ]);
        }
        
    } else {
        // GET: Retornar informações sobre categorias disponíveis
        $categories_info = [
            'tudo' => [
                'name' => 'Limpar Tudo',
                'description' => 'Remove todos os dados do sistema (exceto usuários e fazendas)',
                'tables_count' => 0
            ],
            'rebanho' => [
                'name' => 'Rebanho',
                'description' => 'Animais, grupos, pesos e genealogia',
                'tables' => ['animals', 'animal_weights', 'animal_groups', 'body_condition_scores', 'pedigree_records']
            ],
            'alimentacao' => [
                'name' => 'Alimentação',
                'description' => 'Registros de alimentação, composições e cálculos',
                'tables' => ['feed_records', 'feed_compositions', 'nutritional_parameters', 'ideal_feed_calculations', 'feed_comparisons']
            ],
            'volume' => [
                'name' => 'Volume',
                'description' => 'Registros de volume e produção de leite',
                'tables' => ['volume_records', 'milk_production']
            ],
            'qualidade' => [
                'name' => 'Qualidade',
                'description' => 'Testes de qualidade do leite',
                'tables' => ['quality_tests']
            ],
            'reproducao' => [
                'name' => 'Reprodução',
                'description' => 'Prenhezes, inseminações, cio e partos',
                'tables' => ['pregnancies', 'inseminations', 'heat_records', 'calvings']
            ],
            'sanitario' => [
                'name' => 'Sanitário',
                'description' => 'Registros de saúde, vacinas e tratamentos',
                'tables' => ['health_records', 'vaccinations', 'treatments', 'medical_examinations']
            ],
            'financeiro' => [
                'name' => 'Financeiro',
                'description' => 'Registros financeiros, pagamentos e despesas',
                'tables' => ['financial_records', 'payments', 'expenses']
            ],
            'touros' => [
                'name' => 'Touros',
                'description' => 'Cadastro de touros, serviços e desempenhos',
                'tables' => ['bulls', 'bull_services', 'bull_performances', 'bull_health_records']
            ],
            'outros' => [
                'name' => 'Outros',
                'description' => 'Notificações, atividades, fotos e outros',
                'tables' => ['notifications', 'activities', 'photos', 'transponders', 'milking_groups']
            ]
        ];
        
        // Contar tabelas em "tudo"
        $all_tables = [];
        foreach ($categories_info as $key => $info) {
            if ($key !== 'tudo' && isset($info['tables'])) {
                $all_tables = array_merge($all_tables, $info['tables']);
            }
        }
        $categories_info['tudo']['tables_count'] = count(array_unique($all_tables));
        
        echo json_encode([
            'success' => true,
            'categories' => $categories_info
        ]);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error' => 'Erro: ' . $e->getMessage()
    ]);
}

