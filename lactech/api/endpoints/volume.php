<?php
/**
 * Endpoint para gerenciar registros de volume
 * GET /api/rest.php/volume - Listar registros de volume
 * POST /api/rest.php/volume - Adicionar registro de volume
 * PUT /api/rest.php/volume - Atualizar registro
 * DELETE /api/rest.php/volume - Deletar registro
 */

$method = Request::getMethod();

switch ($method) {
    case 'GET':
        $userId = Auth::checkAuth();
        
        $limit = Request::getParam('limit', 50);
        $dateFrom = Request::getParam('date_from', null);
        $dateTo = Request::getParam('date_to', null);
        $period = Request::getParam('period', null);
        
        try {
            $query = "SELECT 
                vr.id,
                vr.volume,
                vr.collection_date,
                vr.period,
                vr.temperature,
                vr.created_at,
                u.name as recorded_by_name,
                p.name as producer_name
                FROM volume_records vr
                LEFT JOIN users u ON vr.recorded_by = u.id
                LEFT JOIN producers p ON vr.producer_id = p.id
                WHERE 1=1";
            
            $params = [];
            
            if ($dateFrom) {
                $query .= " AND DATE(vr.collection_date) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $query .= " AND DATE(vr.collection_date) <= ?";
                $params[] = $dateTo;
            }
            
            if ($period) {
                $query .= " AND vr.period = ?";
                $params[] = $period;
            }
            
            $query .= " ORDER BY vr.collection_date DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ApiResponse::success($records, 'Registros de volume carregados com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao buscar registros de volume: ' . $e->getMessage());
        }
        break;
        
    case 'POST':
        $userId = Auth::checkAuth();
        
        Validator::required($data, ['volume', 'collection_date']);
        Validator::numeric($data['volume'], 'volume');
        
        try {
            $stmt = $db->prepare("INSERT INTO volume_records (producer_id, volume, collection_date, period, temperature, recorded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['producer_id'] ?? null,
                $data['volume'],
                $data['collection_date'],
                $data['period'] ?? 'manha',
                $data['temperature'] ?? null,
                $userId
            ]);
            
            $recordId = $db->lastInsertId();
            
            ApiResponse::success([
                'id' => $recordId,
                'volume' => $data['volume'],
                'collection_date' => $data['collection_date'],
                'period' => $data['period'] ?? 'manha'
            ], 'Registro de volume adicionado com sucesso', 201);
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao adicionar registro de volume: ' . $e->getMessage());
        }
        break;
        
    case 'PUT':
        $userId = Auth::checkAuth();
        
        $recordId = Request::getParam('id');
        if (!$recordId) {
            ApiResponse::error('ID do registro é obrigatório');
        }
        
        try {
            $updateFields = [];
            $params = [];
            
            if (isset($data['volume'])) {
                Validator::numeric($data['volume'], 'volume');
                $updateFields[] = "volume = ?";
                $params[] = $data['volume'];
            }
            
            if (isset($data['collection_date'])) {
                $updateFields[] = "collection_date = ?";
                $params[] = $data['collection_date'];
            }
            
            if (isset($data['period'])) {
                $updateFields[] = "period = ?";
                $params[] = $data['period'];
            }
            
            if (isset($data['temperature'])) {
                $updateFields[] = "temperature = ?";
                $params[] = $data['temperature'];
            }
            
            if (empty($updateFields)) {
                ApiResponse::error('Nenhum campo para atualizar');
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $recordId;
            
            $query = "UPDATE volume_records SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Registro não encontrado');
            }
            
            ApiResponse::success(null, 'Registro atualizado com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao atualizar registro: ' . $e->getMessage());
        }
        break;
        
    case 'DELETE':
        $userId = Auth::checkAuth();
        
        $recordId = Request::getParam('id');
        if (!$recordId) {
            ApiResponse::error('ID do registro é obrigatório');
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM volume_records WHERE id = ?");
            $stmt->execute([$recordId]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Registro não encontrado');
            }
            
            ApiResponse::success(null, 'Registro removido com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao remover registro: ' . $e->getMessage());
        }
        break;
        
    default:
        ApiResponse::error('Método não permitido', 405);
}
?>

