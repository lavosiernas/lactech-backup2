<?php
/**
 * Endpoint para gerenciar registros financeiros
 * GET /api/rest.php/financial - Listar registros financeiros
 * POST /api/rest.php/financial - Adicionar registro financeiro
 * PUT /api/rest.php/financial - Atualizar registro
 * DELETE /api/rest.php/financial - Deletar registro
 */

$method = Request::getMethod();

switch ($method) {
    case 'GET':
        $userId = Auth::checkAuth();
        
        $limit = Request::getParam('limit', 50);
        $type = Request::getParam('type', null);
        $status = Request::getParam('status', null);
        $dateFrom = Request::getParam('date_from', null);
        $dateTo = Request::getParam('date_to', null);
        
        try {
            $query = "SELECT 
                fr.id,
                fr.type,
                fr.amount,
                fr.description,
                fr.due_date,
                fr.payment_date,
                fr.status,
                fr.created_at,
                u.name as created_by_name
                FROM financial_records fr
                LEFT JOIN users u ON fr.created_by = u.id
                WHERE 1=1";
            
            $params = [];
            
            if ($type) {
                $query .= " AND fr.type = ?";
                $params[] = $type;
            }
            
            if ($status) {
                $query .= " AND fr.status = ?";
                $params[] = $status;
            }
            
            if ($dateFrom) {
                $query .= " AND DATE(fr.created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $query .= " AND DATE(fr.created_at) <= ?";
                $params[] = $dateTo;
            }
            
            $query .= " ORDER BY fr.created_at DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ApiResponse::success($records, 'Registros financeiros carregados com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao buscar registros financeiros: ' . $e->getMessage());
        }
        break;
        
    case 'POST':
        $userId = Auth::checkAuth();
        
        Validator::required($data, ['type', 'amount']);
        Validator::numeric($data['amount'], 'amount');
        
        $allowedTypes = ['income', 'expense'];
        if (!in_array($data['type'], $allowedTypes)) {
            ApiResponse::error('Tipo inválido. Valores permitidos: ' . implode(', ', $allowedTypes));
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO financial_records (type, amount, description, due_date, payment_date, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['type'],
                $data['amount'],
                $data['description'] ?? null,
                $data['due_date'] ?? null,
                $data['payment_date'] ?? null,
                $data['status'] ?? 'pending',
                $userId
            ]);
            
            $recordId = $db->lastInsertId();
            
            ApiResponse::success([
                'id' => $recordId,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'status' => $data['status'] ?? 'pending'
            ], 'Registro financeiro adicionado com sucesso', 201);
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao adicionar registro financeiro: ' . $e->getMessage());
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
            
            if (isset($data['type'])) {
                $allowedTypes = ['income', 'expense'];
                if (!in_array($data['type'], $allowedTypes)) {
                    ApiResponse::error('Tipo inválido. Valores permitidos: ' . implode(', ', $allowedTypes));
                }
                $updateFields[] = "type = ?";
                $params[] = $data['type'];
            }
            
            if (isset($data['amount'])) {
                Validator::numeric($data['amount'], 'amount');
                $updateFields[] = "amount = ?";
                $params[] = $data['amount'];
            }
            
            if (isset($data['description'])) {
                $updateFields[] = "description = ?";
                $params[] = $data['description'];
            }
            
            if (isset($data['due_date'])) {
                $updateFields[] = "due_date = ?";
                $params[] = $data['due_date'];
            }
            
            if (isset($data['payment_date'])) {
                $updateFields[] = "payment_date = ?";
                $params[] = $data['payment_date'];
            }
            
            if (isset($data['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $data['status'];
            }
            
            if (empty($updateFields)) {
                ApiResponse::error('Nenhum campo para atualizar');
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $recordId;
            
            $query = "UPDATE financial_records SET " . implode(', ', $updateFields) . " WHERE id = ?";
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
            $stmt = $db->prepare("DELETE FROM financial_records WHERE id = ?");
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

