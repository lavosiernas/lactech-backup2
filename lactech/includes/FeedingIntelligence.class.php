<?php
/**
 * CLASSE DE INTELIGÊNCIA DE ALIMENTAÇÃO
 * Sistema de cálculo e comparação de alimentação ideal vs real
 * Versão: 1.0.0
 */

require_once __DIR__ . '/Database.class.php';

class FeedingIntelligence {
    private $db;
    private $farm_id;
    
    public function __construct($farm_id = 1) {
        $this->db = Database::getInstance();
        $this->farm_id = $farm_id;
    }
    
    /**
     * Obter peso mais recente de um animal
     */
    public function getAnimalWeight($animal_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // Tentar obter peso real mais recente
            $stmt = $pdo->prepare("
                SELECT weight_kg, weighing_date, weighing_type
                FROM animal_weights
                WHERE animal_id = ? AND farm_id = ?
                ORDER BY weighing_date DESC, weighing_type ASC
                LIMIT 1
            ");
            $stmt->execute([$animal_id, $this->farm_id]);
            $weight = $stmt->fetch();
            
            if ($weight) {
                return [
                    'weight_kg' => (float)$weight['weight_kg'],
                    'weighing_date' => $weight['weighing_date'],
                    'weighing_type' => $weight['weighing_type'],
                    'source' => 'weight_record'
                ];
            }
            
            // Se não tem peso, tentar estimar pela idade e categoria
            $stmt = $pdo->prepare("
                SELECT a.birth_date, a.status, a.breed, a.birth_weight,
                       DATEDIFF(CURDATE(), a.birth_date) as age_days
                FROM animals a
                WHERE a.id = ? AND a.farm_id = ?
            ");
            $stmt->execute([$animal_id, $this->farm_id]);
            $animal = $stmt->fetch();
            
            if ($animal) {
                $estimatedWeight = $this->estimateWeightByAge($animal);
                return [
                    'weight_kg' => $estimatedWeight,
                    'weighing_date' => date('Y-m-d'),
                    'weighing_type' => 'estimated',
                    'source' => 'estimated'
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Erro ao obter peso do animal: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Estimar peso pela idade e categoria
     */
    private function estimateWeightByAge($animal) {
        $ageDays = (int)$animal['age_days'];
        $status = $animal['status'];
        $birthWeight = $animal['birth_weight'] ? (float)$animal['birth_weight'] : 35.0;
        
        // Pesos médios estimados por categoria (kg)
        $categoryWeights = [
            'Bezerra' => min(35 + ($ageDays * 0.5), 200),
            'Novilha' => min(200 + (($ageDays - 365) * 0.3), 450),
            'Lactante' => 550,
            'Seco' => 550,
            'Vaca' => 550,
            'Touro' => 800,
            'Bezerro' => min(40 + ($ageDays * 0.6), 250)
        ];
        
        // Se tem idade, usar crescimento estimado
        if ($ageDays < 365) {
            return min($birthWeight + ($ageDays * 0.5), 200);
        } elseif ($ageDays < 730) {
            return min(200 + (($ageDays - 365) * 0.3), 450);
        } else {
            return $categoryWeights[$status] ?? 500;
        }
    }
    
    /**
     * Obter peso médio de um grupo/lote
     * PRIORIDADE: Calcula pela média dos pesos individuais dos animais cadastrados no lote
     * Fallback: Se não houver pesos individuais suficientes, usa peso do lote registrado diretamente
     */
    public function getGroupAverageWeight($group_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // PRIMEIRO: Calcular pela média dos pesos individuais dos animais do lote
            $stmt = $pdo->prepare("
                SELECT a.id
                FROM animals a
                WHERE a.current_group_id = ? AND a.farm_id = ? AND a.is_active = 1
            ");
            $stmt->execute([$group_id, $this->farm_id]);
            $animals = $stmt->fetchAll();
            
            if (empty($animals)) {
                return null;
            }
            
            $totalWeight = 0;
            $count = 0;
            $animalWeights = [];
            
            // Buscar pesos individuais dos animais (mais recente de cada animal)
            foreach ($animals as $animal) {
                $weightStmt = $pdo->prepare("
                    SELECT weight_kg, weighing_date
                    FROM animal_weights
                    WHERE animal_id = ? AND farm_id = ? AND weight_kg IS NOT NULL
                    ORDER BY weighing_date DESC, id DESC
                    LIMIT 1
                ");
                $weightStmt->execute([$animal['id'], $this->farm_id]);
                $weightRecord = $weightStmt->fetch();
                
                if ($weightRecord) {
                    $totalWeight += (float)$weightRecord['weight_kg'];
                    $count++;
                    $animalWeights[] = (float)$weightRecord['weight_kg'];
                } else {
                    // Se não tem peso registrado, tentar estimar
                    $weightData = $this->getAnimalWeight($animal['id']);
                    if ($weightData && isset($weightData['weight_kg'])) {
                        $totalWeight += $weightData['weight_kg'];
                        $count++;
                    }
                }
            }
            
            // Se temos pesos de pelo menos 50% dos animais, usar a média calculada
            $minAnimalsForCalculation = max(1, ceil(count($animals) * 0.5)); // Mínimo 50% dos animais
            
            if ($count >= $minAnimalsForCalculation) {
                return [
                    'avg_weight_kg' => $totalWeight / $count,
                    'animal_count' => count($animals),
                    'source' => 'calculated_from_individual_weights',
                    'animals_with_weight' => $count,
                    'total_animals' => count($animals)
                ];
            }
            
            // FALLBACK: Se não temos pesos individuais suficientes, tentar peso do lote registrado diretamente
            $groupWeightStmt = $pdo->prepare("
                SELECT group_avg_weight_kg, weighing_date, animal_count
                FROM animal_weights
                WHERE group_id = ? AND farm_id = ? AND group_avg_weight_kg IS NOT NULL
                ORDER BY weighing_date DESC
                LIMIT 1
            ");
            $groupWeightStmt->execute([$group_id, $this->farm_id]);
            $groupWeight = $groupWeightStmt->fetch();
            
            if ($groupWeight) {
                // Obter contagem atual de animais
                $countStmt = $pdo->prepare("
                    SELECT COUNT(*) as count
                    FROM animals
                    WHERE current_group_id = ? AND farm_id = ? AND is_active = 1
                ");
                $countStmt->execute([$group_id, $this->farm_id]);
                $currentCount = $countStmt->fetch();
                
                return [
                    'avg_weight_kg' => (float)$groupWeight['group_avg_weight_kg'],
                    'animal_count' => (int)($currentCount['count'] ?? $groupWeight['animal_count']),
                    'source' => 'group_weight_record',
                    'weighing_date' => $groupWeight['weighing_date']
                ];
            }
            
            // Se não temos nenhum peso, retornar null
            return null;
        } catch (Exception $e) {
            error_log("Erro ao obter peso médio do grupo: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obter categoria do animal baseado no status
     */
    private function mapStatusToCategory($status) {
        $mapping = [
            'Lactante' => 'lactante',
            'Seco' => 'seco',
            'Novilha' => 'novilha',
            'Bezerra' => 'bezerra',
            'Touro' => 'touro',
            'Vaca' => 'lactante', // Default para vaca
            'Bezerro' => 'bezerra' // Tratar como bezerra
        ];
        
        return $mapping[$status] ?? 'lactante';
    }
    
    /**
     * Obter parâmetros nutricionais para uma categoria
     */
    public function getNutritionalParameters($category) {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                SELECT ms_consumption_pct, protein_requirement_pct, 
                       min_ms_pct, max_ms_pct, description
                FROM nutritional_parameters
                WHERE category = ? AND farm_id = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$category, $this->farm_id]);
            $params = $stmt->fetch();
            
            if (!$params) {
                // Valores padrão se não encontrar
                return [
                    'ms_consumption_pct' => 3.5,
                    'protein_requirement_pct' => 16.0,
                    'min_ms_pct' => null,
                    'max_ms_pct' => null,
                    'description' => 'Parâmetros padrão'
                ];
            }
            
            return [
                'ms_consumption_pct' => (float)$params['ms_consumption_pct'],
                'protein_requirement_pct' => (float)$params['protein_requirement_pct'],
                'min_ms_pct' => $params['min_ms_pct'] ? (float)$params['min_ms_pct'] : null,
                'max_ms_pct' => $params['max_ms_pct'] ? (float)$params['max_ms_pct'] : null,
                'description' => $params['description']
            ];
        } catch (Exception $e) {
            error_log("Erro ao obter parâmetros nutricionais: " . $e->getMessage());
            return [
                'ms_consumption_pct' => 3.5,
                'protein_requirement_pct' => 16.0,
                'min_ms_pct' => null,
                'max_ms_pct' => null,
                'description' => 'Parâmetros padrão (erro)'
            ];
        }
    }
    
    /**
     * Calcular alimentação ideal para um animal individual
     */
    public function calculateIdealFeedForAnimal($animal_id, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        try {
            $pdo = $this->db->getConnection();
            
            // Obter dados do animal
            $stmt = $pdo->prepare("
                SELECT id, status, current_group_id
                FROM animals
                WHERE id = ? AND farm_id = ? AND is_active = 1
            ");
            $stmt->execute([$animal_id, $this->farm_id]);
            $animal = $stmt->fetch();
            
            if (!$animal) {
                return ['success' => false, 'error' => 'Animal não encontrado'];
            }
            
            // Obter peso
            $weightData = $this->getAnimalWeight($animal_id);
            if (!$weightData) {
                return ['success' => false, 'error' => 'Não foi possível obter peso do animal'];
            }
            
            $weightKg = $weightData['weight_kg'];
            $category = $this->mapStatusToCategory($animal['status']);
            
            // Obter parâmetros nutricionais
            $nutritionParams = $this->getNutritionalParameters($category);
            
            // Calcular MS ideal (kg) = peso × % consumo MS
            $idealMsKg = $weightKg * ($nutritionParams['ms_consumption_pct'] / 100);
            
            // Distribuição padrão: 60% concentrado, 40% volumoso/silagem
            // (Pode ser ajustado posteriormente)
            $idealConcentrateKg = $idealMsKg * 0.60;
            $idealRoughageKg = $idealMsKg * 0.25;
            $idealSilageKg = $idealMsKg * 0.15;
            $idealHayKg = 0;
            
            // Salvar cálculo
            $calcId = $this->saveIdealCalculation([
                'calculation_date' => $date,
                'calculation_type' => 'individual',
                'animal_id' => $animal_id,
                'group_id' => null,
                'category' => $category,
                'avg_weight_kg' => $weightKg,
                'animal_count' => 1,
                'ms_consumption_pct' => $nutritionParams['ms_consumption_pct'],
                'ideal_ms_total_kg' => $idealMsKg,
                'ideal_concentrate_kg' => $idealConcentrateKg,
                'ideal_roughage_kg' => $idealRoughageKg,
                'ideal_silage_kg' => $idealSilageKg,
                'ideal_hay_kg' => $idealHayKg
            ]);
            
            return [
                'success' => true,
                'calculation_id' => $calcId,
                'animal_id' => $animal_id,
                'category' => $category,
                'weight_kg' => $weightKg,
                'weight_source' => $weightData['source'],
                'ms_consumption_pct' => $nutritionParams['ms_consumption_pct'],
                'ideal' => [
                    'ms_total_kg' => round($idealMsKg, 2),
                    'concentrate_kg' => round($idealConcentrateKg, 2),
                    'roughage_kg' => round($idealRoughageKg, 2),
                    'silage_kg' => round($idealSilageKg, 2),
                    'hay_kg' => round($idealHayKg, 2)
                ]
            ];
        } catch (Exception $e) {
            error_log("Erro ao calcular alimentação ideal: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Calcular alimentação ideal para um lote/grupo
     * Este é o método principal: calcula baseado no peso do lote
     * Fórmula: (Peso médio do lote × Número de animais) × % MS = MS Total do Lote
     */
    public function calculateIdealFeedForGroup($group_id, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        try {
            $pdo = $this->db->getConnection();
            
            // Obter dados do grupo
            $stmt = $pdo->prepare("
                SELECT g.id, g.group_name, g.group_type
                FROM animal_groups g
                WHERE g.id = ? AND g.farm_id = ? AND g.is_active = 1
            ");
            $stmt->execute([$group_id, $this->farm_id]);
            $group = $stmt->fetch();
            
            if (!$group) {
                return ['success' => false, 'error' => 'Grupo não encontrado'];
            }
            
            // Obter peso médio do lote (prioriza peso registrado do lote)
            $groupWeightData = $this->getGroupAverageWeight($group_id);
            if (!$groupWeightData) {
                return ['success' => false, 'error' => 'Não foi possível obter peso do lote. Registre o peso do lote primeiro.'];
            }
            
            $avgWeightKg = $groupWeightData['avg_weight_kg'];
            $animalCount = $groupWeightData['animal_count'];
            
            // Mapear tipo do grupo para categoria nutricional
            $category = $this->mapGroupTypeToCategory($group['group_type']);
            
            // Obter parâmetros nutricionais
            $nutritionParams = $this->getNutritionalParameters($category);
            
            // CÁLCULO PRINCIPAL: Peso médio do lote × Número de animais × % MS = MS Total do Lote
            $totalLoteWeight = $avgWeightKg * $animalCount;
            $idealMsKgTotal = $totalLoteWeight * ($nutritionParams['ms_consumption_pct'] / 100);
            
            // MS por animal (para referência)
            $idealMsKgPerAnimal = $idealMsKgTotal / $animalCount;
            
            // Distribuição padrão: 60% concentrado, 25% volumoso, 15% silagem
            $idealConcentrateKg = $idealMsKgTotal * 0.60;
            $idealRoughageKg = $idealMsKgTotal * 0.25;
            $idealSilageKg = $idealMsKgTotal * 0.15;
            $idealHayKg = 0;
            
            // Salvar cálculo
            $calcId = $this->saveIdealCalculation([
                'calculation_date' => $date,
                'calculation_type' => 'group',
                'animal_id' => null,
                'group_id' => $group_id,
                'category' => $category,
                'avg_weight_kg' => $avgWeightKg,
                'animal_count' => $animalCount,
                'ms_consumption_pct' => $nutritionParams['ms_consumption_pct'],
                'ideal_ms_total_kg' => $idealMsKgTotal,
                'ideal_concentrate_kg' => $idealConcentrateKg,
                'ideal_roughage_kg' => $idealRoughageKg,
                'ideal_silage_kg' => $idealSilageKg,
                'ideal_hay_kg' => $idealHayKg
            ]);
            
            return [
                'success' => true,
                'calculation_id' => $calcId,
                'group_id' => $group_id,
                'group_name' => $group['group_name'],
                'group_type' => $group['group_type'],
                'category' => $category,
                'avg_weight_kg' => round($avgWeightKg, 2),
                'animal_count' => $animalCount,
                'total_lote_weight_kg' => round($totalLoteWeight, 2),
                'ms_consumption_pct' => $nutritionParams['ms_consumption_pct'],
                'weight_source' => $groupWeightData['source'],
                'ideal' => [
                    'ms_total_kg' => round($idealMsKgTotal, 2),
                    'ms_per_animal_kg' => round($idealMsKgPerAnimal, 2),
                    'concentrate_kg' => round($idealConcentrateKg, 2),
                    'roughage_kg' => round($idealRoughageKg, 2),
                    'silage_kg' => round($idealSilageKg, 2),
                    'hay_kg' => round($idealHayKg, 2)
                ]
            ];
        } catch (Exception $e) {
            error_log("Erro ao calcular alimentação ideal do lote: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Mapear tipo de grupo para categoria nutricional
     */
    private function mapGroupTypeToCategory($groupType) {
        $mapping = [
            'lactante' => 'lactante',
            'seco' => 'seco',
            'novilha' => 'novilha',
            'pre_parto' => 'seco',
            'pos_parto' => 'lactante',
            'hospital' => 'lactante',
            'quarentena' => 'lactante',
            'pasto' => 'lactante',
            'outros' => 'lactante'
        ];
        
        return $mapping[$groupType] ?? 'lactante';
    }
    
    /**
     * Salvar cálculo ideal no banco
     */
    private function saveIdealCalculation($data) {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO ideal_feed_calculations (
                    calculation_date, calculation_type, animal_id, group_id, category,
                    avg_weight_kg, animal_count, ms_consumption_pct,
                    ideal_ms_total_kg, ideal_concentrate_kg, ideal_roughage_kg,
                    ideal_silage_kg, ideal_hay_kg, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['calculation_date'],
                $data['calculation_type'],
                $data['animal_id'],
                $data['group_id'],
                $data['category'],
                $data['avg_weight_kg'],
                $data['animal_count'],
                $data['ms_consumption_pct'],
                $data['ideal_ms_total_kg'],
                $data['ideal_concentrate_kg'],
                $data['ideal_roughage_kg'],
                $data['ideal_silage_kg'],
                $data['ideal_hay_kg'],
                $this->farm_id
            ]);
            
            return $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Erro ao salvar cálculo ideal: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Comparar alimentação real vs ideal
     */
    public function compareRealVsIdeal($feed_record_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // Obter registro real
            $stmt = $pdo->prepare("
                SELECT fr.*, a.status, a.current_group_id
                FROM feed_records fr
                LEFT JOIN animals a ON fr.animal_id = a.id
                WHERE fr.id = ? AND fr.farm_id = ?
            ");
            $stmt->execute([$feed_record_id, $this->farm_id]);
            $realRecord = $stmt->fetch();
            
            if (!$realRecord) {
                return ['success' => false, 'error' => 'Registro não encontrado'];
            }
            
            // Obter cálculo ideal correspondente
            $idealCalc = null;
            $idealCalcId = null;
            if ($realRecord['record_type'] === 'individual' && $realRecord['animal_id']) {
                // Calcular ideal para animal individual
                $idealResult = $this->calculateIdealFeedForAnimal($realRecord['animal_id'], $realRecord['feed_date']);
                if ($idealResult['success']) {
                    $idealCalc = $idealResult['ideal'];
                    $idealCalcId = $idealResult['calculation_id'] ?? null;
                }
            } elseif ($realRecord['record_type'] === 'group' && $realRecord['group_id']) {
                // Calcular ideal para grupo
                $idealResult = $this->calculateIdealFeedForGroup($realRecord['group_id'], $realRecord['feed_date']);
                if ($idealResult['success']) {
                    $idealCalc = $idealResult['ideal'];
                    $idealCalcId = $idealResult['calculation_id'] ?? null;
                }
            }
            
            if (!$idealCalc) {
                return ['success' => false, 'error' => 'Não foi possível calcular o ideal'];
            }
            
            // Converter valores reais para MS (se necessário)
            // Por enquanto, assumir que os valores já estão em MS ou usar conversão simples
            $realConcentrateKg = (float)($realRecord['concentrate_kg'] ?? 0);
            $realRoughageKg = (float)($realRecord['roughage_kg'] ?? 0);
            $realSilageKg = (float)($realRecord['silage_kg'] ?? 0);
            $realHayKg = (float)($realRecord['hay_kg'] ?? 0);
            
            // Calcular MS real (usando % MS padrão se não tiver composição)
            // Concentrado: ~88% MS, Volumoso: ~25% MS, Silagem: ~35% MS, Feno: ~85% MS
            $realMsConcentrate = $realConcentrateKg * 0.88;
            $realMsRoughage = $realRoughageKg * 0.25;
            $realMsSilage = $realSilageKg * 0.35;
            $realMsHay = $realHayKg * 0.85;
            $realMsTotal = $realMsConcentrate + $realMsRoughage + $realMsSilage + $realMsHay;
            
            $idealMsTotal = $idealCalc['ms_total_kg'];
            
            // Calcular diferenças
            $diffConcentrate = $realConcentrateKg - $idealCalc['concentrate_kg'];
            $diffConcentratePct = $idealCalc['concentrate_kg'] > 0 
                ? ($diffConcentrate / $idealCalc['concentrate_kg']) * 100 
                : 0;
            
            $diffRoughage = $realRoughageKg - $idealCalc['roughage_kg'];
            $diffRoughagePct = $idealCalc['roughage_kg'] > 0 
                ? ($diffRoughage / $idealCalc['roughage_kg']) * 100 
                : 0;
            
            $diffSilage = $realSilageKg - $idealCalc['silage_kg'];
            $diffSilagePct = $idealCalc['silage_kg'] > 0 
                ? ($diffSilage / $idealCalc['silage_kg']) * 100 
                : 0;
            
            $diffHay = $realHayKg - $idealCalc['hay_kg'];
            $diffHayPct = $idealCalc['hay_kg'] > 0 
                ? ($diffHay / $idealCalc['hay_kg']) * 100 
                : 0;
            
            $diffMs = $realMsTotal - $idealMsTotal;
            $diffMsPct = $idealMsTotal > 0 
                ? ($diffMs / $idealMsTotal) * 100 
                : 0;
            
            // Determinar status
            $status = 'ok';
            $alertMessage = null;
            
            if ($diffMsPct < -15) {
                $status = 'below';
                $alertMessage = 'Consumo abaixo do ideal. Considere aumentar a quantidade fornecida.';
            } elseif ($diffMsPct > 15) {
                $status = 'above';
                $alertMessage = 'Consumo acima do ideal. Verifique se não há desperdício.';
            } elseif (abs($diffMsPct) > 10) {
                $status = 'warning';
                $alertMessage = 'Consumo próximo ao limite. Monitore o desempenho dos animais.';
            }
            
            // Salvar comparação
            $comparisonId = $this->saveComparison([
                'feed_record_id' => $feed_record_id,
                'ideal_calculation_id' => $idealCalcId ?? null,
                'comparison_date' => $realRecord['feed_date'],
                'record_type' => $realRecord['record_type'],
                'animal_id' => $realRecord['animal_id'],
                'group_id' => $realRecord['group_id'],
                'real_concentrate_kg' => $realConcentrateKg,
                'ideal_concentrate_kg' => $idealCalc['concentrate_kg'],
                'diff_concentrate_kg' => $diffConcentrate,
                'diff_concentrate_pct' => $diffConcentratePct,
                'real_roughage_kg' => $realRoughageKg,
                'ideal_roughage_kg' => $idealCalc['roughage_kg'],
                'diff_roughage_kg' => $diffRoughage,
                'diff_roughage_pct' => $diffRoughagePct,
                'real_silage_kg' => $realSilageKg,
                'ideal_silage_kg' => $idealCalc['silage_kg'],
                'diff_silage_kg' => $diffSilage,
                'diff_silage_pct' => $diffSilagePct,
                'real_hay_kg' => $realHayKg,
                'ideal_hay_kg' => $idealCalc['hay_kg'],
                'diff_hay_kg' => $diffHay,
                'diff_hay_pct' => $diffHayPct,
                'real_ms_total_kg' => $realMsTotal,
                'ideal_ms_total_kg' => $idealMsTotal,
                'diff_ms_kg' => $diffMs,
                'diff_ms_pct' => $diffMsPct,
                'status' => $status,
                'alert_message' => $alertMessage
            ]);
            
            return [
                'success' => true,
                'comparison_id' => $comparisonId,
                'status' => $status,
                'alert_message' => $alertMessage,
                'real' => [
                    'concentrate_kg' => $realConcentrateKg,
                    'roughage_kg' => $realRoughageKg,
                    'silage_kg' => $realSilageKg,
                    'hay_kg' => $realHayKg,
                    'ms_total_kg' => round($realMsTotal, 2)
                ],
                'ideal' => [
                    'concentrate_kg' => round($idealCalc['concentrate_kg'], 2),
                    'roughage_kg' => round($idealCalc['roughage_kg'], 2),
                    'silage_kg' => round($idealCalc['silage_kg'], 2),
                    'hay_kg' => round($idealCalc['hay_kg'], 2),
                    'ms_total_kg' => round($idealMsTotal, 2)
                ],
                'differences' => [
                    'concentrate_kg' => round($diffConcentrate, 2),
                    'concentrate_pct' => round($diffConcentratePct, 2),
                    'roughage_kg' => round($diffRoughage, 2),
                    'roughage_pct' => round($diffRoughagePct, 2),
                    'silage_kg' => round($diffSilage, 2),
                    'silage_pct' => round($diffSilagePct, 2),
                    'hay_kg' => round($diffHay, 2),
                    'hay_pct' => round($diffHayPct, 2),
                    'ms_kg' => round($diffMs, 2),
                    'ms_pct' => round($diffMsPct, 2)
                ]
            ];
        } catch (Exception $e) {
            error_log("Erro ao comparar real vs ideal: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Salvar comparação no banco
     */
    private function saveComparison($data) {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO feed_comparisons (
                    feed_record_id, ideal_calculation_id, comparison_date, record_type,
                    animal_id, group_id,
                    real_concentrate_kg, ideal_concentrate_kg, diff_concentrate_kg, diff_concentrate_pct,
                    real_roughage_kg, ideal_roughage_kg, diff_roughage_kg, diff_roughage_pct,
                    real_silage_kg, ideal_silage_kg, diff_silage_kg, diff_silage_pct,
                    real_hay_kg, ideal_hay_kg, diff_hay_kg, diff_hay_pct,
                    real_ms_total_kg, ideal_ms_total_kg, diff_ms_kg, diff_ms_pct,
                    status, alert_message, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['feed_record_id'],
                $data['ideal_calculation_id'],
                $data['comparison_date'],
                $data['record_type'],
                $data['animal_id'],
                $data['group_id'],
                $data['real_concentrate_kg'],
                $data['ideal_concentrate_kg'],
                $data['diff_concentrate_kg'],
                $data['diff_concentrate_pct'],
                $data['real_roughage_kg'],
                $data['ideal_roughage_kg'],
                $data['diff_roughage_kg'],
                $data['diff_roughage_pct'],
                $data['real_silage_kg'],
                $data['ideal_silage_kg'],
                $data['diff_silage_kg'],
                $data['diff_silage_pct'],
                $data['real_hay_kg'],
                $data['ideal_hay_kg'],
                $data['diff_hay_kg'],
                $data['diff_hay_pct'],
                $data['real_ms_total_kg'],
                $data['ideal_ms_total_kg'],
                $data['diff_ms_kg'],
                $data['diff_ms_pct'],
                $data['status'],
                $data['alert_message'],
                $this->farm_id
            ]);
            
            return $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Erro ao salvar comparação: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Registrar peso do lote diretamente
     * O gerente pesa o lote e registra o peso médio
     */
    public function registerGroupWeight($group_id, $avg_weight_kg, $animal_count, $weighing_date = null, $notes = null) {
        if (!$weighing_date) {
            $weighing_date = date('Y-m-d');
        }
        
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se grupo existe
            $stmt = $pdo->prepare("
                SELECT id, group_name
                FROM animal_groups
                WHERE id = ? AND farm_id = ? AND is_active = 1
            ");
            $stmt->execute([$group_id, $this->farm_id]);
            $group = $stmt->fetch();
            
            if (!$group) {
                return ['success' => false, 'error' => 'Grupo não encontrado'];
            }
            
            // Registrar peso do lote
            $stmt = $pdo->prepare("
                INSERT INTO animal_weights (
                    group_id, group_avg_weight_kg, animal_count, 
                    weighing_date, weighing_type, notes, 
                    recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, 'real', ?, ?, ?)
            ");
            
            $user_id = $_SESSION['user_id'] ?? null;
            $stmt->execute([
                $group_id,
                $avg_weight_kg,
                $animal_count,
                $weighing_date,
                $notes,
                $user_id,
                $this->farm_id
            ]);
            
            $weightId = $pdo->lastInsertId();
            
            return [
                'success' => true,
                'weight_id' => $weightId,
                'group_id' => $group_id,
                'group_name' => $group['group_name'],
                'avg_weight_kg' => $avg_weight_kg,
                'animal_count' => $animal_count,
                'weighing_date' => $weighing_date
            ];
        } catch (Exception $e) {
            error_log("Erro ao registrar peso do lote: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

