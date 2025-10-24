<?php
/**
 * API: Feed Records
 * Controle de alimentação e consumo de concentrado
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/Database.class.php';

function sendResponse($data = null, $error = null, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => $error === null,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Listar/Buscar
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
                $date_to = $_GET['date_to'] ?? date('Y-m-d');
                
                $stmt = $db->query("
                    SELECT 
                        f.*,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        a.status,
                        u.name as recorded_by_name
                    FROM feed_records f
                    LEFT JOIN animals a ON f.animal_id = a.id
                    LEFT JOIN users u ON f.recorded_by = u.id
                    WHERE f.feed_date BETWEEN ? AND ?
                    ORDER BY f.feed_date DESC, f.created_at DESC
                ", [$date_from, $date_to]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'by_animal':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
                
                $stmt = $db->query("
                    SELECT f.*, u.name as recorded_by_name
                    FROM feed_records f
                    LEFT JOIN users u ON f.recorded_by = u.id
                    WHERE f.animal_id = ? AND f.feed_date >= ?
                    ORDER BY f.feed_date DESC, f.shift
                ", [$animal_id, $date_from]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'daily_summary':
                $date = $_GET['date'] ?? date('Y-m-d');
                
                $stmt = $db->query("
                    SELECT 
                        COUNT(DISTINCT f.animal_id) as animals_fed,
                        SUM(f.concentrate_kg) as total_concentrate,
                        SUM(f.roughage_kg) as total_roughage,
                        SUM(f.silage_kg) as total_silage,
                        SUM(f.hay_kg) as total_hay,
                        SUM(f.total_cost) as total_cost,
                        AVG(f.concentrate_kg) as avg_concentrate_per_animal
                    FROM feed_records f
                    WHERE f.feed_date = ?
                ", [$date]);
                sendResponse($stmt->fetch(PDO::FETCH_ASSOC));
                break;
                
            case 'stats':
                $days = $_GET['days'] ?? 30;
                $date_from = date('Y-m-d', strtotime("-$days days"));
                
                $stmt = $db->query("
                    SELECT 
                        a.id as animal_id,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        a.status,
                        COUNT(f.id) as total_records,
                        AVG(f.concentrate_kg) as avg_concentrate,
                        SUM(f.concentrate_kg) as total_concentrate,
                        SUM(f.total_cost) as total_cost,
                        MAX(f.feed_date) as last_feed_date,
                        DATEDIFF(CURDATE(), MAX(f.feed_date)) as days_since_last_feed
                    FROM animals a
                    LEFT JOIN feed_records f ON a.id = f.animal_id 
                        AND f.feed_date >= ?
                    WHERE a.is_active = 1
                    GROUP BY a.id, a.animal_number, a.name, a.breed, a.status
                    ORDER BY total_concentrate DESC
                ", [$date_from]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'cost_analysis':
                $date_from = $_GET['date_from'] ?? date('Y-m-01'); // Primeiro dia do mês
                $date_to = $_GET['date_to'] ?? date('Y-m-d');
                
                $stmt = $db->query("
                    SELECT 
                        DATE(f.feed_date) as date,
                        SUM(f.total_cost) as daily_cost,
                        SUM(f.concentrate_kg) as daily_concentrate,
                        COUNT(DISTINCT f.animal_id) as animals_count
                    FROM feed_records f
                    WHERE f.feed_date BETWEEN ? AND ?
                    GROUP BY DATE(f.feed_date)
                    ORDER BY DATE(f.feed_date) DESC
                ", [$date_from, $date_to]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST - Criar registro
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $action = $input['action'] ?? 'create';
        
        if ($action === 'create') {
            // Validações
            if (empty($input['animal_id'])) sendResponse(null, 'ID do animal obrigatório');
            if (empty($input['feed_date'])) sendResponse(null, 'Data obrigatória');
            if (empty($input['concentrate_kg']) && empty($input['roughage_kg'])) {
                sendResponse(null, 'Informar pelo menos concentrado ou volumoso');
            }
            
            // Calcular custo total se não fornecido
            $total_cost = $input['total_cost'] ?? null;
            if (!$total_cost && isset($input['cost_per_kg']) && isset($input['concentrate_kg'])) {
                $total_cost = $input['cost_per_kg'] * $input['concentrate_kg'];
            }
            
            // Inserir
            $stmt = $db->query("
                INSERT INTO feed_records (
                    animal_id, feed_date, shift, concentrate_kg,
                    roughage_kg, silage_kg, hay_kg, feed_type,
                    feed_brand, protein_percentage, cost_per_kg,
                    total_cost, automatic, notes, recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ", [
                $input['animal_id'],
                $input['feed_date'],
                $input['shift'] ?? 'unico',
                $input['concentrate_kg'] ?? 0,
                $input['roughage_kg'] ?? null,
                $input['silage_kg'] ?? null,
                $input['hay_kg'] ?? null,
                $input['feed_type'] ?? null,
                $input['feed_brand'] ?? null,
                $input['protein_percentage'] ?? null,
                $input['cost_per_kg'] ?? null,
                $total_cost,
                $input['automatic'] ?? 0,
                $input['notes'] ?? null,
                $_SESSION['user_id'] ?? 1
            ]);
            
            $id = $db->getConnection()->lastInsertId();
            sendResponse([
                'id' => $id,
                'message' => 'Alimentação registrada com sucesso',
                'total_cost' => $total_cost
            ]);
        }
        
        if ($action === 'bulk_create') {
            // Criar múltiplos registros de uma vez
            $records = $input['records'] ?? [];
            if (empty($records)) sendResponse(null, 'Nenhum registro fornecido');
            
            $inserted = 0;
            foreach ($records as $record) {
                try {
                    $db->query("
                        INSERT INTO feed_records (
                            animal_id, feed_date, shift, concentrate_kg,
                            roughage_kg, total_cost, recorded_by, farm_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                    ", [
                        $record['animal_id'],
                        $record['feed_date'],
                        $record['shift'] ?? 'unico',
                        $record['concentrate_kg'] ?? 0,
                        $record['roughage_kg'] ?? null,
                        $record['total_cost'] ?? null,
                        $_SESSION['user_id'] ?? 1
                    ]);
                    $inserted++;
                } catch (Exception $e) {
                    error_log("Erro inserindo registro: " . $e->getMessage());
                }
            }
            
            sendResponse([
                'message' => "Registros criados: $inserted de " . count($records),
                'inserted' => $inserted
            ]);
        }
    }
    
    // PUT - Atualizar
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? null;
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        $updates = [];
        $values = [];
        
        $allowed = ['feed_date', 'shift', 'concentrate_kg', 'roughage_kg', 
                    'silage_kg', 'hay_kg', 'feed_type', 'feed_brand',
                    'protein_percentage', 'cost_per_kg', 'total_cost', 'notes'];
        
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        
        if (empty($updates)) sendResponse(null, 'Nenhum campo para atualizar');
        
        $values[] = $id;
        $db->query("
            UPDATE feed_records 
            SET " . implode(', ', $updates) . "
            WHERE id = ?
        ", $values);
        
        sendResponse(['message' => 'Registro atualizado']);
    }
    
    // DELETE - Remover
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        $db->query("DELETE FROM feed_records WHERE id = ?", [$id]);
        sendResponse(['message' => 'Registro removido']);
    }
    
} catch (Exception $e) {
    error_log("Erro API Feed: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

