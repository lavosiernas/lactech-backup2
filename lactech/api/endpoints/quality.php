<?php
/**
 * Endpoint para gerenciar testes de qualidade
 * GET /api/rest.php/quality - Listar testes de qualidade
 * POST /api/rest.php/quality - Adicionar teste de qualidade
 * PUT /api/rest.php/quality - Atualizar teste
 * DELETE /api/rest.php/quality - Deletar teste
 */

$method = Request::getMethod();

switch ($method) {
    case 'GET':
        $userId = Auth::checkAuth();
        
        $limit = Request::getParam('limit', 50);
        $dateFrom = Request::getParam('date_from', null);
        $dateTo = Request::getParam('date_to', null);
        $status = Request::getParam('status', null);
        
        try {
            $query = "SELECT 
                qt.id,
                qt.test_date,
                qt.fat_percentage,
                qt.protein_percentage,
                qt.lactose_percentage,
                qt.ccs,
                qt.cbt,
                qt.temperature,
                qt.ph,
                qt.status,
                qt.created_at,
                u.name as tested_by_name,
                p.name as producer_name
                FROM quality_tests qt
                LEFT JOIN users u ON qt.tested_by = u.id
                LEFT JOIN producers p ON qt.producer_id = p.id
                WHERE 1=1";
            
            $params = [];
            
            if ($dateFrom) {
                $query .= " AND DATE(qt.test_date) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $query .= " AND DATE(qt.test_date) <= ?";
                $params[] = $dateTo;
            }
            
            if ($status) {
                $query .= " AND qt.status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY qt.test_date DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ApiResponse::success($tests, 'Testes de qualidade carregados com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao buscar testes de qualidade: ' . $e->getMessage());
        }
        break;
        
    case 'POST':
        $userId = Auth::checkAuth();
        
        Validator::required($data, ['test_date']);
        
        try {
            $stmt = $db->prepare("INSERT INTO quality_tests (producer_id, test_date, fat_percentage, protein_percentage, lactose_percentage, ccs, cbt, temperature, ph, status, tested_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['producer_id'] ?? null,
                $data['test_date'],
                $data['fat_percentage'] ?? null,
                $data['protein_percentage'] ?? null,
                $data['lactose_percentage'] ?? null,
                $data['ccs'] ?? null,
                $data['cbt'] ?? null,
                $data['temperature'] ?? null,
                $data['ph'] ?? null,
                $data['status'] ?? 'pending',
                $userId
            ]);
            
            $testId = $db->lastInsertId();
            
            ApiResponse::success([
                'id' => $testId,
                'test_date' => $data['test_date'],
                'status' => $data['status'] ?? 'pending'
            ], 'Teste de qualidade adicionado com sucesso', 201);
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao adicionar teste de qualidade: ' . $e->getMessage());
        }
        break;
        
    case 'PUT':
        $userId = Auth::checkAuth();
        
        $testId = Request::getParam('id');
        if (!$testId) {
            ApiResponse::error('ID do teste é obrigatório');
        }
        
        try {
            $updateFields = [];
            $params = [];
            
            if (isset($data['test_date'])) {
                $updateFields[] = "test_date = ?";
                $params[] = $data['test_date'];
            }
            
            if (isset($data['fat_percentage'])) {
                $updateFields[] = "fat_percentage = ?";
                $params[] = $data['fat_percentage'];
            }
            
            if (isset($data['protein_percentage'])) {
                $updateFields[] = "protein_percentage = ?";
                $params[] = $data['protein_percentage'];
            }
            
            if (isset($data['lactose_percentage'])) {
                $updateFields[] = "lactose_percentage = ?";
                $params[] = $data['lactose_percentage'];
            }
            
            if (isset($data['ccs'])) {
                $updateFields[] = "ccs = ?";
                $params[] = $data['ccs'];
            }
            
            if (isset($data['cbt'])) {
                $updateFields[] = "cbt = ?";
                $params[] = $data['cbt'];
            }
            
            if (isset($data['temperature'])) {
                $updateFields[] = "temperature = ?";
                $params[] = $data['temperature'];
            }
            
            if (isset($data['ph'])) {
                $updateFields[] = "ph = ?";
                $params[] = $data['ph'];
            }
            
            if (isset($data['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $data['status'];
            }
            
            if (empty($updateFields)) {
                ApiResponse::error('Nenhum campo para atualizar');
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $testId;
            
            $query = "UPDATE quality_tests SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Teste não encontrado');
            }
            
            ApiResponse::success(null, 'Teste atualizado com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao atualizar teste: ' . $e->getMessage());
        }
        break;
        
    case 'DELETE':
        $userId = Auth::checkAuth();
        
        $testId = Request::getParam('id');
        if (!$testId) {
            ApiResponse::error('ID do teste é obrigatório');
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM quality_tests WHERE id = ?");
            $stmt->execute([$testId]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Teste não encontrado');
            }
            
            ApiResponse::success(null, 'Teste removido com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao remover teste: ' . $e->getMessage());
        }
        break;
        
    default:
        ApiResponse::error('Método não permitido', 405);
}
?>

