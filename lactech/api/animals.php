<?php
// API para Gestão de Animais

// Iniciar output buffering para evitar saída antes do JSON
ob_start();

// Desabilitar erros visíveis
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $dbPath;

function sendResponse($data = null, $error = null) {
    // Limpar qualquer saída anterior (warnings, notices, etc)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    $response = ['success' => $error === null];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    // Limpar headers anteriores
    header_remove();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'search':
                // Busca rápida de animais por nome ou número
                $searchTerm = $_GET['q'] ?? '';
                if (empty($searchTerm)) {
                    sendResponse([]);
                }
                
                $searchTerm = '%' . $searchTerm . '%';
                $animals = $db->query("
                    SELECT 
                        id, 
                        animal_number, 
                        name, 
                        breed, 
                        status, 
                        gender,
                        birth_date,
                        DATEDIFF(CURDATE(), birth_date) as age_days
                    FROM animals 
                    WHERE farm_id = 1 
                    AND is_active = 1
                    AND (name LIKE ? OR animal_number LIKE ?)
                    ORDER BY 
                        CASE 
                            WHEN animal_number LIKE ? THEN 1
                            WHEN name LIKE ? THEN 2
                            ELSE 3
                        END,
                        animal_number ASC
                    LIMIT 10
                ", [$searchTerm, $searchTerm, $_GET['q'] . '%', $_GET['q'] . '%']);
                
                sendResponse($animals);
                break;
                
            case 'select':
            case 'get_all':
                $animals = $db->getAllAnimals();
                sendResponse($animals);
                break;
                
            case 'get_by_id':
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    sendResponse(null, 'ID não fornecido');
                }
                $animal = $db->getAnimalById($id);
                if ($animal === null || $animal === false) {
                    sendResponse(null, 'Animal não encontrado');
                } else {
                    // Adicionar cálculo de idade
                    if (isset($animal['birth_date']) && $animal['birth_date']) {
                        $animal['age_days'] = floor((time() - strtotime($animal['birth_date'])) / 86400);
                    } else {
                        $animal['age_days'] = 0;
                    }
                    sendResponse($animal);
                }
                break;
                
            case 'get_pregnant':
                $animals = $db->getPregnantAnimals();
                sendResponse($animals);
                break;
                
            case 'get_pedigree':
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    sendResponse(null, 'ID não fornecido');
                }
                $pedigree = $db->getAnimalPedigree($id);
                // Sempre retornar array, mesmo que vazio
                if (!is_array($pedigree)) {
                    $pedigree = [];
                }
                sendResponse($pedigree);
                break;
                
            case 'get_productivity':
                $animals = $db->getAnimalsProductivity();
                sendResponse($animals);
                break;
                
            case 'get_reproductive_history':
                $animalId = $_GET['animal_id'] ?? null;
                if (!$animalId) {
                    sendResponse(null, 'ID do animal não fornecido');
                }
                
                try {
                    $pdo = $db->getConnection();
                    $history = [];
                    
                    // Buscar inseminações
                    $stmt = $pdo->prepare("
                        SELECT 
                            'insemination' as type,
                            insemination_date as date,
                            CONCAT('Inseminação - ', insemination_method) as description,
                            pregnancy_result as result,
                            notes
                        FROM inseminations
                        WHERE animal_id = ? AND farm_id = 1
                        ORDER BY insemination_date DESC
                    ");
                    $stmt->execute([$animalId]);
                    $inseminations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $history = array_merge($history, $inseminations);
                    
                    // Buscar controles de prenhez
                    $stmt = $pdo->prepare("
                        SELECT 
                            'pregnancy' as type,
                            pregnancy_date as date,
                            CONCAT('Controle de Prenhez - ', pregnancy_stage) as description,
                            ultrasound_result as result,
                            notes
                        FROM pregnancy_controls
                        WHERE animal_id = ? AND farm_id = 1
                        ORDER BY pregnancy_date DESC
                    ");
                    $stmt->execute([$animalId]);
                    $pregnancies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $history = array_merge($history, $pregnancies);
                    
                    // Buscar partos
                    $stmt = $pdo->prepare("
                        SELECT 
                            'birth' as type,
                            birth_date as date,
                            CONCAT('Parto - ', IFNULL(calf_sex, 'N/A')) as description,
                            'concluido' as result,
                            notes
                        FROM births
                        WHERE animal_id = ? AND farm_id = 1
                        ORDER BY birth_date DESC
                    ");
                    $stmt->execute([$animalId]);
                    $births = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $history = array_merge($history, $births);
                    
                    // Ordenar por data (mais recente primeiro)
                    usort($history, function($a, $b) {
                        return strtotime($b['date']) - strtotime($a['date']);
                    });
                    
                    sendResponse($history);
                } catch (PDOException $e) {
                    error_log("Erro ao buscar histórico reprodutivo: " . $e->getMessage());
                    sendResponse(null, 'Erro ao buscar histórico reprodutivo');
                }
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'insert':
                $result = $db->createAnimal($input);
                sendResponse($result);
                break;
                
            case 'update':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                unset($input['id']);
                $result = $db->updateAnimal($id, $input);
                sendResponse($result);
                break;
                
            case 'delete':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                $result = $db->deleteAnimal($id);
                sendResponse($result);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
} catch (Exception $e) {
    error_log("Erro na API animals.php: " . $e->getMessage());
    sendResponse(null, $e->getMessage());
} catch (Error $e) {
    error_log("Erro fatal na API animals.php: " . $e->getMessage());
    sendResponse(null, 'Erro interno: ' . $e->getMessage());
}
?>

