<?php
/**
 * Endpoint para gerenciar solicitações de alteração de senha
 * GET /api/rest.php/password-requests - Listar solicitações
 * POST /api/rest.php/password-requests - Criar solicitação
 * PUT /api/rest.php/password-requests - Atualizar solicitação
 * DELETE /api/rest.php/password-requests - Deletar solicitação
 */

$method = Request::getMethod();

switch ($method) {
    case 'GET':
        // Listar solicitações de senha
        $userId = Auth::checkAuth();
        
        $limit = Request::getParam('limit', 50);
        $status = Request::getParam('status', null);
        $dateFrom = Request::getParam('date_from', null);
        $dateTo = Request::getParam('date_to', null);
        
        try {
            $query = "SELECT 
                pr.id,
                pr.user_id,
                u.name as user_name,
                u.email as user_email,
                pr.status,
                pr.created_at,
                pr.updated_at,
                pr.expires_at
                FROM password_requests pr
                JOIN users u ON pr.user_id = u.id
                WHERE 1=1";
            
            $params = [];
            
            if ($status) {
                $query .= " AND pr.status = ?";
                $params[] = $status;
            }
            
            if ($dateFrom) {
                $query .= " AND pr.created_at >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $query .= " AND pr.created_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }
            
            $query .= " ORDER BY pr.created_at DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ApiResponse::success($requests, 'Solicitações carregadas com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao buscar solicitações: ' . $e->getMessage());
        }
        break;
        
    case 'POST':
        // Criar nova solicitação de senha
        $userId = Auth::checkAuth();
        
        Validator::required($data, ['email']);
        Validator::email($data['email']);
        
        try {
            // Verificar se usuário existe
            $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                ApiResponse::error('Usuário não encontrado', 404);
            }
            
            // Verificar se já existe solicitação pendente
            $stmt = $db->prepare("SELECT id FROM password_requests WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$user['id']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                ApiResponse::error('Já existe uma solicitação pendente para este usuário', 409);
            }
            
            // Criar token único
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $db->prepare("INSERT INTO password_requests (user_id, token, status, expires_at, created_at) VALUES (?, ?, 'pending', ?, NOW())");
            $stmt->execute([$user['id'], $token, $expiresAt]);
            
            $requestId = $db->lastInsertId();
            
            // Enviar email (implementar conforme necessário)
            // TODO: Implementar envio de email
            
            ApiResponse::success([
                'id' => $requestId,
                'user' => $user,
                'token' => $token,
                'expires_at' => $expiresAt
            ], 'Solicitação criada com sucesso', 201);
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao criar solicitação: ' . $e->getMessage());
        }
        break;
        
    case 'PUT':
        // Atualizar status da solicitação
        $userId = Auth::checkRole('gerente');
        
        Validator::required($data, ['id', 'status']);
        
        $allowedStatuses = ['pending', 'approved', 'rejected', 'completed'];
        if (!in_array($data['status'], $allowedStatuses)) {
            ApiResponse::error('Status inválido. Valores permitidos: ' . implode(', ', $allowedStatuses));
        }
        
        try {
            $stmt = $db->prepare("UPDATE password_requests SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$data['status'], $data['id']]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Solicitação não encontrada');
            }
            
            ApiResponse::success(null, 'Status atualizado com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao atualizar solicitação: ' . $e->getMessage());
        }
        break;
        
    case 'DELETE':
        // Deletar solicitação
        $userId = Auth::checkRole('gerente');
        
        $requestId = Request::getParam('id');
        if (!$requestId) {
            ApiResponse::error('ID da solicitação é obrigatório');
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM password_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Solicitação não encontrada');
            }
            
            ApiResponse::success(null, 'Solicitação removida com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao remover solicitação: ' . $e->getMessage());
        }
        break;
        
    default:
        ApiResponse::error('Método não permitido', 405);
}
?>

