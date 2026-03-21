<?php
/**
 * ÉCOSYSTÈME IMMO LOCAL+ - API Traitement Contact
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Récupérer l'action
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// Vérifier que action existe
if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action manquante']);
    exit;
}

// Vérifier que action = submit_contact
if ($action !== 'submit_contact') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

// ============================================
// RÉCUPÉRER LES DONNÉES
// ============================================
$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
$lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$city = isset($_POST['city']) ? trim($_POST['city']) : '';
$intent = isset($_POST['intent']) ? trim($_POST['intent']) : 'cold';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// ============================================
// VALIDER LES DONNÉES
// ============================================
if (empty($firstname)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Prénom requis']);
    exit;
}

if (empty($lastname)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nom requis']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

if (strlen($message) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message trop court (minimum 30 caractères)']);
    exit;
}

// ============================================
// MAPPER L'INTENT
// ============================================
$intentMap = [
    'diagnostic' => 'Diagnostic',
    'demo' => 'Démo',
    'ressource' => 'Ressource',
    'outil' => 'Outil',
    'cold' => 'Autre'
];
$type_demande = isset($intentMap[$intent]) ? $intentMap[$intent] : 'Autre';

try {
    // ============================================
    // INSÉRER DANS LA BD
    // ============================================
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (nom, email, telephone, ville, type_demande, message, is_read, is_replied, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW())
    ");
    
    $success = $stmt->execute([
        $firstname . ' ' . $lastname,
        $email,
        $phone,
        $city,
        $type_demande,
        $message
    ]);
    
    if (!$success) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur BD']);
        exit;
    }
    
    $contact_id = $pdo->lastInsertId();
    
    // ============================================
    // ENVOYER EMAIL DE CONFIRMATION
    // ============================================
    $subject = 'Merci pour votre demande - ÉCOSYSTÈME IMMO LOCAL+';
    $body = "Bonjour {$firstname},\n\n";
    $body .= "Merci d'avoir pris contact avec ÉCOSYSTÈME IMMO LOCAL+.\n\n";
    $body .= "Nous avons bien reçu votre demande concernant : {$type_demande}\n\n";
    $body .= "Notre équipe étudiera votre dossier et vous recontactera dans les 24 heures.\n\n";
    $body .= "À bientôt,\n";
    $body .= "L'équipe ÉCOSYSTÈME IMMO LOCAL+";
    
    $from_email = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'admin@ecosystemeimmo.fr';
    $headers = "From: {$from_email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    @mail($email, $subject, $body, $headers);
    
    // ============================================
    // ENVOYER NOTIFICATION À L'ADMIN
    // ============================================
    $admin_email = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@ecosystemeimmo.fr';
    $admin_subject = "Nouvelle demande - {$firstname} {$lastname}";
    $admin_body = "Nouvelle demande de contact:\n\n";
    $admin_body .= "Nom: {$firstname} {$lastname}\n";
    $admin_body .= "Email: {$email}\n";
    $admin_body .= "Téléphone: {$phone}\n";
    $admin_body .= "Ville: {$city}\n";
    $admin_body .= "Type: {$type_demande}\n\n";
    $admin_body .= "Message:\n{$message}\n\n";
    $admin_body .= "Voir dans admin: /admin/contacts/?id={$contact_id}";
    
    @mail($admin_email, $admin_subject, $admin_body, $headers);
    
    // ============================================
    // RETOUR SUCCÈS
    // ============================================
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Votre demande a été envoyée',
        'redirect_url' => '/contacts/merci?email=' . urlencode($email) . '&intent=' . urlencode($intent)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Contact API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

exit;
?>