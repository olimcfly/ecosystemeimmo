<?php
/**
 * API - Supprimer un Lead et son Historique
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Supprimer l'historique des téléchargements (si la table existe)
    $tableExists = $pdo->query("SHOW TABLES LIKE 'lead_downloads'")->rowCount() > 0;
    if ($tableExists) {
        $deleteDownloads = $pdo->prepare("DELETE FROM lead_downloads WHERE lead_id = ?");
        $deleteDownloads->execute([$id]);
    }
    
    // Supprimer le lead
    $deleteLead = $pdo->prepare("DELETE FROM leads WHERE id = ?");
    $deleteLead->execute([$id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact et historique supprimés'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la suppression',
        'debug' => $e->getMessage()
    ]);
}