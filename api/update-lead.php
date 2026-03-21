<?php
/**
 * API - Update Lead
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

require_once '../config/database.php';

try {
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $id = (int)($data['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID requis');
    }
    
    $pdo = getDB();
    
    // Build update query
    $updates = [];
    $params = [];
    
    $allowedFields = ['status', 'notes', 'firstname', 'lastname', 'email', 'phone', 'city'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = sanitize($data[$field]);
        }
    }
    
    if (empty($updates)) {
        throw new Exception('Aucun champ à mettre à jour');
    }
    
    $params[] = $id;
    $sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Lead non trouvé ou aucune modification');
    }
    
    // Get updated lead
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $stmt->execute([$id]);
    $lead = $stmt->fetch();
    
    echo json_encode(['success' => true, 'lead' => $lead]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
