<?php
/**
 * API: Gestão de Estoque/Insumos
 * Sistema completo de controle de rações, medicamentos e insumos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Limpar qualquer saída anterior
while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config_mysql.php';
require_once __DIR__ . '/../includes/Database.class.php';

function sendResponse($success, $data = null, $error = null, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header_remove();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    
    $response = ['success' => $success];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Obter ID do usuário logado
    $userId = $_SESSION['user_id'] ?? 1;
    $farmId = 1; // Lagoa do Mato
    
    switch ($action) {
        // ==================== PRODUTOS ====================
        case 'list_products':
            $type = $_GET['type'] ?? null;
            $search = $_GET['search'] ?? '';
            $lowStock = isset($_GET['low_stock']) && $_GET['low_stock'] === '1';
            
            $sql = "SELECT * FROM products WHERE farm_id = ? AND is_active = 1";
            $params = [$farmId];
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            if ($search) {
                $sql .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($lowStock) {
                $sql .= " AND current_stock <= min_stock";
            }
            
            $sql .= " ORDER BY name ASC";
            
            $products = $db->query($sql, $params);
            
            // Adicionar informações de alerta
            foreach ($products as &$product) {
                $product['has_alert'] = $product['current_stock'] <= $product['min_stock'];
                $product['stock_percentage'] = $product['min_stock'] > 0 
                    ? round(($product['current_stock'] / $product['min_stock']) * 100, 2) 
                    : 100;
            }
            
            sendResponse(true, $products);
            break;
            
        case 'get_product':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                sendResponse(false, null, 'ID do produto não fornecido', 400);
            }
            
            $products = $db->query("SELECT * FROM products WHERE id = ? AND farm_id = ?", [$id, $farmId]);
            if (empty($products)) {
                sendResponse(false, null, 'Produto não encontrado', 404);
            }
            
            $product = $products[0];
            $product['has_alert'] = $product['current_stock'] <= $product['min_stock'];
            
            sendResponse(true, $product);
            break;
            
        case 'create_product':
            if ($method !== 'POST') {
                sendResponse(false, null, 'Método não permitido', 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $required = ['name', 'type'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(false, null, "Campo '{$field}' é obrigatório", 400);
                }
            }
            
            $data = [
                'name' => trim($input['name']),
                'type' => $input['type'],
                'unit' => $input['unit'] ?? 'unidade',
                'current_stock' => floatval($input['current_stock'] ?? 0),
                'min_stock' => floatval($input['min_stock'] ?? 0),
                'max_stock' => !empty($input['max_stock']) ? floatval($input['max_stock']) : null,
                'cost_per_unit' => !empty($input['cost_per_unit']) ? floatval($input['cost_per_unit']) : null,
                'supplier' => !empty($input['supplier']) ? trim($input['supplier']) : null,
                'description' => !empty($input['description']) ? trim($input['description']) : null,
                'barcode' => !empty($input['barcode']) ? trim($input['barcode']) : null,
                'category' => !empty($input['category']) ? trim($input['category']) : null,
                'location' => !empty($input['location']) ? trim($input['location']) : null,
                'farm_id' => $farmId
            ];
            
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    name, type, unit, current_stock, min_stock, max_stock, 
                    cost_per_unit, supplier, description, barcode, category, location, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
                $stmt->execute([
                    $data['name'], $data['type'], $data['unit'], $data['current_stock'], 
                    $data['min_stock'], $data['max_stock'], $data['cost_per_unit'], 
                    $data['supplier'], $data['description'], $data['barcode'], 
                    $data['category'], $data['location'], $data['farm_id']
                ]);
                
                $productId = $pdo->lastInsertId();
                
                // Se houver estoque inicial, criar movimentação de entrada
                if ($data['current_stock'] > 0) {
                    $totalCost = $data['cost_per_unit'] ? $data['current_stock'] * $data['cost_per_unit'] : null;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO stock_movements (
                            product_id, movement_type, quantity, unit_price, total_cost,
                            stock_before, stock_after, movement_date, recorded_by, farm_id
                        ) VALUES (?, 'entrada', ?, ?, ?, 0, ?, CURDATE(), ?, ?)
                    ");
                    
                    $stmt->execute([
                        $productId, $data['current_stock'], $data['cost_per_unit'], 
                        $totalCost, $data['current_stock'], $userId, $farmId
                    ]);
                }
            
            sendResponse(true, ['id' => $productId, 'message' => 'Produto criado com sucesso']);
            break;
            
        case 'update_product':
            if ($method !== 'POST' && $method !== 'PUT') {
                sendResponse(false, null, 'Método não permitido', 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = intval($input['id'] ?? 0);
            
            if (!$id) {
                sendResponse(false, null, 'ID do produto não fornecido', 400);
            }
            
            // Verificar se o produto existe
            $existing = $db->query("SELECT * FROM products WHERE id = ? AND farm_id = ?", [$id, $farmId]);
            if (empty($existing)) {
                sendResponse(false, null, 'Produto não encontrado', 404);
            }
            
            $updateFields = [];
            $params = [];
            
            $fields = ['name', 'type', 'unit', 'min_stock', 'max_stock', 'cost_per_unit', 
                      'supplier', 'description', 'barcode', 'category', 'location', 'is_active'];
            
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "{$field} = ?";
                    if (in_array($field, ['min_stock', 'max_stock', 'cost_per_unit'])) {
                        $params[] = !empty($input[$field]) ? floatval($input[$field]) : null;
                    } elseif ($field === 'is_active') {
                        $params[] = intval($input[$field]);
                    } else {
                        $params[] = !empty($input[$field]) ? trim($input[$field]) : null;
                    }
                }
            }
            
            if (empty($updateFields)) {
                sendResponse(false, null, 'Nenhum campo para atualizar', 400);
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $id;
            $params[] = $farmId;
            
            $sql = "UPDATE products SET " . implode(', ', $updateFields) . " WHERE id = ? AND farm_id = ?";
            $db->query($sql, $params);
            
            sendResponse(true, ['message' => 'Produto atualizado com sucesso']);
            break;
            
        case 'delete_product':
            $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
            if (!$id) {
                sendResponse(false, null, 'ID do produto não fornecido', 400);
            }
            
            // Soft delete (marcar como inativo)
            $db->query("UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ? AND farm_id = ?", [$id, $farmId]);
            
            sendResponse(true, ['message' => 'Produto excluído com sucesso']);
            break;
            
        // ==================== MOVIMENTAÇÕES ====================
        case 'list_movements':
            $productId = $_GET['product_id'] ?? null;
            $movementType = $_GET['movement_type'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            
            $sql = "
                SELECT 
                    sm.*,
                    p.name as product_name,
                    p.type as product_type,
                    p.unit as product_unit,
                    u.name as recorded_by_name
                FROM stock_movements sm
                INNER JOIN products p ON sm.product_id = p.id
                LEFT JOIN users u ON sm.recorded_by = u.id
                WHERE sm.farm_id = ?
            ";
            $params = [$farmId];
            
            if ($productId) {
                $sql .= " AND sm.product_id = ?";
                $params[] = intval($productId);
            }
            
            if ($movementType) {
                $sql .= " AND sm.movement_type = ?";
                $params[] = $movementType;
            }
            
            if ($dateFrom) {
                $sql .= " AND sm.movement_date >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND sm.movement_date <= ?";
                $params[] = $dateTo;
            }
            
            $sql .= " ORDER BY sm.movement_date DESC, sm.created_at DESC";
            
            $movements = $db->query($sql, $params);
            sendResponse(true, $movements);
            break;
            
        case 'create_movement':
            if ($method !== 'POST') {
                sendResponse(false, null, 'Método não permitido', 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $required = ['product_id', 'movement_type', 'quantity', 'movement_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(false, null, "Campo '{$field}' é obrigatório", 400);
                }
            }
            
            $productId = intval($input['product_id']);
            $quantity = floatval($input['quantity']);
            $movementType = $input['movement_type'];
            
            // Verificar produto
            $products = $db->query("SELECT * FROM products WHERE id = ? AND farm_id = ? AND is_active = 1", [$productId, $farmId]);
            if (empty($products)) {
                sendResponse(false, null, 'Produto não encontrado', 404);
            }
            
            $product = $products[0];
            $stockBefore = floatval($product['current_stock']);
            
            // Calcular estoque depois
            if ($movementType === 'entrada' || $movementType === 'ajuste') {
                $stockAfter = $stockBefore + $quantity;
            } elseif ($movementType === 'saida') {
                if ($stockBefore < $quantity) {
                    sendResponse(false, null, 'Estoque insuficiente', 400);
                }
                $stockAfter = $stockBefore - $quantity;
            } else {
                $stockAfter = $quantity; // Para ajuste direto
            }
            
            if ($stockAfter < 0) {
                sendResponse(false, null, 'Estoque não pode ser negativo', 400);
            }
            
            $unitPrice = !empty($input['unit_price']) ? floatval($input['unit_price']) : $product['cost_per_unit'];
            $totalCost = $unitPrice ? $quantity * $unitPrice : null;
            
            $pdo = $db->getConnection();
            $pdo->beginTransaction();
            
            try {
                // Criar movimentação
                $stmt = $pdo->prepare("
                    INSERT INTO stock_movements (
                        product_id, movement_type, quantity, unit_price, total_cost,
                        stock_before, stock_after, reference, notes, movement_date,
                        recorded_by, farm_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $productId, $movementType, $quantity, $unitPrice, $totalCost,
                    $stockBefore, $stockAfter, $input['reference'] ?? null,
                    $input['notes'] ?? null, $input['movement_date'], $userId, $farmId
                ]);
                
                // Atualizar estoque do produto
                $pdo->prepare("UPDATE products SET current_stock = ? WHERE id = ? AND farm_id = ?")
                    ->execute([$stockAfter, $productId, $farmId]);
                
                // Verificar se precisa criar alerta
                if ($stockAfter <= $product['min_stock']) {
                    $alertType = $stockAfter == 0 ? 'estoque_zerado' : 'estoque_baixo';
                    
                    $pdo->prepare("
                        INSERT INTO stock_alerts (product_id, alert_type, current_stock, min_stock, farm_id, notified_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                            alert_type = VALUES(alert_type),
                            current_stock = VALUES(current_stock),
                            is_read = 0,
                            notified_at = NOW()
                    ")->execute([$productId, $alertType, $stockAfter, $product['min_stock'], $farmId]);
                } else {
                    // Remover alerta se estoque foi reposto
                    $pdo->prepare("UPDATE stock_alerts SET resolved_at = NOW() WHERE product_id = ? AND is_read = 1")
                        ->execute([$productId]);
                }
                
                $pdo->commit();
                
                $movementId = $pdo->lastInsertId();
                sendResponse(true, ['id' => $movementId, 'message' => 'Movimentação registrada com sucesso']);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        // ==================== ALERTAS ====================
        case 'list_alerts':
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';
            
            $sql = "
                SELECT 
                    sa.*,
                    p.name as product_name,
                    p.type as product_type,
                    p.unit as product_unit
                FROM stock_alerts sa
                INNER JOIN products p ON sa.product_id = p.id
                WHERE sa.farm_id = ?
            ";
            $params = [$farmId];
            
            if ($unreadOnly) {
                $sql .= " AND sa.is_read = 0";
            }
            
            $sql .= " ORDER BY sa.created_at DESC";
            
            $alerts = $db->query($sql, $params);
            sendResponse(true, $alerts);
            break;
            
        case 'mark_alert_read':
            $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
            if (!$id) {
                sendResponse(false, null, 'ID do alerta não fornecido', 400);
            }
            
            $db->query("UPDATE stock_alerts SET is_read = 1 WHERE id = ? AND farm_id = ?", [$id, $farmId]);
            sendResponse(true, ['message' => 'Alerta marcado como lido']);
            break;
            
        case 'stats':
            // Estatísticas gerais de estoque
            $totalProducts = $db->query("SELECT COUNT(*) as total FROM products WHERE farm_id = ? AND is_active = 1", [$farmId])[0]['total'] ?? 0;
            
            $lowStockCount = $db->query("
                SELECT COUNT(*) as total 
                FROM products 
                WHERE farm_id = ? AND is_active = 1 AND current_stock <= min_stock
            ", [$farmId])[0]['total'] ?? 0;
            
            $totalValue = $db->query("
                SELECT SUM(current_stock * COALESCE(cost_per_unit, 0)) as total
                FROM products
                WHERE farm_id = ? AND is_active = 1
            ", [$farmId])[0]['total'] ?? 0;
            
            $recentMovements = $db->query("
                SELECT COUNT(*) as total
                FROM stock_movements
                WHERE farm_id = ? AND movement_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ", [$farmId])[0]['total'] ?? 0;
            
            sendResponse(true, [
                'total_products' => (int)$totalProducts,
                'low_stock_count' => (int)$lowStockCount,
                'total_value' => floatval($totalValue),
                'recent_movements' => (int)$recentMovements
            ]);
            break;
            
        default:
            sendResponse(false, null, 'Ação inválida', 400);
    }
    
} catch (Exception $e) {
    error_log("Erro na API stock.php: " . $e->getMessage());
    sendResponse(false, null, 'Erro interno: ' . $e->getMessage(), 500);
} catch (Error $e) {
    error_log("Erro fatal na API stock.php: " . $e->getMessage());
    sendResponse(false, null, 'Erro interno: ' . $e->getMessage(), 500);
}
?>

