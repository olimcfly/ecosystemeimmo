<?php
/**
 * API - Récupérer un Lead avec son Historique Complet
 * Chemin: /api/get-lead.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Chemin vers la config (remonte d'un niveau depuis /api/)
require_once dirname(__DIR__) . '/config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

try {
    // Récupérer le lead
    $leadStmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $leadStmt->execute([$id]);
    $lead = $leadStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        echo json_encode(['success' => false, 'error' => 'Lead non trouvé']);
        exit;
    }
    
    // Récupérer l'historique des téléchargements
    $downloads = [];
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'lead_downloads'");
        if ($tableCheck->rowCount() > 0) {
            $downloadStmt = $pdo->prepare("
                SELECT type, resource, source, downloaded_at 
                FROM lead_downloads 
                WHERE lead_id = ? 
                ORDER BY downloaded_at DESC
            ");
            $downloadStmt->execute([$id]);
            $downloads = $downloadStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Table n'existe pas, continuer sans historique
    }
    
    // Calculer les tags uniques
    $allTypes = [];
    $allResources = [];
    
    foreach ($downloads as $dl) {
        if (!empty($dl['type'])) {
            $allTypes[$dl['type']] = true;
        }
        if (!empty($dl['resource'])) {
            $allResources[$dl['resource']] = true;
        }
    }
    
    // Ajouter aussi le type/resource principal du lead (si pas dans l'historique)
    if (!empty($lead['type']) && $lead['type'] !== 'contact') {
        $allTypes[$lead['type']] = true;
    }
    if (!empty($lead['resource'])) {
        $allResources[$lead['resource']] = true;
    }
    
    // Formater les dates pour l'affichage
    $firstContact = isset($lead['created_at']) ? date('d/m/Y H:i', strtotime($lead['created_at'])) : '-';
    $lastActivity = $firstContact;
    
    if (!empty($downloads) && isset($downloads[0]['downloaded_at'])) {
        $lastActivity = date('d/m/Y H:i', strtotime($downloads[0]['downloaded_at']));
    }
    
    echo json_encode([
        'success' => true,
        'lead' => $lead,
        'downloads' => $downloads,
        'tags' => [
            'types' => array_keys($allTypes),
            'resources' => array_keys($allResources)
        ],
        'stats' => [
            'total_downloads' => count($downloads),
            'first_contact' => $firstContact,
            'last_activity' => $lastActivity
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'debug' => $e->getMessage()
    ]);
}