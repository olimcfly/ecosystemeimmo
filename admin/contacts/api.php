<?php
/**
 * ÉCOSYSTÈME IMMO LOCAL+ - API Contacts
 * Endpoints pour traiter les soumissions de contact en AJAX
 * CORRECTION: Utilise contact_messages au lieu de leads
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// ============================================
// TRAITER LA SOUMISSION
// ============================================
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    
    // =============================================
    // SUBMIT CONTACT
    // =============================================
    case 'submit_contact':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $intent = trim($_POST['intent'] ?? 'cold');
        $message = trim($_POST['message'] ?? '');
        
        // Validation
        if (!$firstname || !$lastname || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Veuillez remplir tous les champs obligatoires'
            ]);
            exit;
        }
        
        if (strlen($firstname) < 2 || strlen($firstname) > 50) {
            echo json_encode(['success' => false, 'message' => 'Prénom invalide']);
            exit;
        }
        
        if (strlen($message) < 10) {
            echo json_encode(['success' => false, 'message' => 'Message trop court']);
            exit;
        }
        
        try {
            // Mapper intent à type_demande
            $intentMap = [
                'diagnostic' => 'Diagnostic',
                'demo' => 'Démo',
                'ressource' => 'Ressource',
                'outil' => 'Outil',
                'cold' => 'Autre'
            ];
            $type_demande = $intentMap[$intent] ?? 'Autre';
            
            // ✅ CORRECTION: Insérer dans contact_messages (pas leads)
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (nom, email, telephone, ville, type_demande, message, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $firstname . ' ' . $lastname,
                $email,
                $phone,
                $city,
                $type_demande,
                $message
            ]);
            
            $contact_id = $pdo->lastInsertId();
            
            // Envoyer email de confirmation
            $subject = 'Merci pour votre demande - ÉCOSYSTÈME IMMO LOCAL+';
            $body = "Bonjour {$firstname},\n\n";
            $body .= "Merci pour votre demande concernant : {$type_demande}\n\n";
            $body .= "Nous examinerons votre dossier et vous recontacterons dans les 24 heures.\n\n";
            $body .= "Cordialement,\n";
            $body .= "L'équipe ÉCOSYSTÈME IMMO LOCAL+";
            
            // ✅ CORRECTION: Utiliser SMTP_FROM_EMAIL (pas EMAIL_FROM)
            $headers = "From: " . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'admin@ecosystemeimmo.fr') . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            @mail($email, $subject, $body, $headers);
            
            // Envoyer notification à l'admin
            $admin_email = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@ecosystemeimmo.fr';
            $admin_subject = "Nouvelle demande - {$firstname} {$lastname}";
            $admin_body = "Nouvelle demande de contact:\n\n";
            $admin_body .= "Nom: {$firstname} {$lastname}\n";
            $admin_body .= "Email: {$email}\n";
            $admin_body .= "Téléphone: {$phone}\n";
            $admin_body .= "Ville: {$city}\n";
            $admin_body .= "Type: {$type_demande}\n\n";
            $admin_body .= "Message:\n{$message}\n";
            
            @mail($admin_email, $admin_subject, $admin_body, $headers);
            
            echo json_encode([
                'success' => true,
                'message' => 'Votre demande a été envoyée avec succès',
                'redirect_url' => '/contacts/thank-you?email=' . urlencode($email) . '&intent=' . urlencode($intent)
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Contact API Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ]);
        }
        break;
    
    // =============================================
    // CHECK EMAIL
    // =============================================
    case 'check_email':
        $email = trim($_POST['email'] ?? '');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'exists' => false]);
            exit;
        }
        
        try {
            // ✅ CORRECTION: Utiliser contact_messages
            $stmt = $pdo->prepare("SELECT id FROM contact_messages WHERE email = ?");
            $stmt->execute([$email]);
            $exists = $stmt->fetch() ? true : false;
            
            echo json_encode([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'Email déjà enregistré' : 'Email disponible'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur']);
        }
        break;
    
    // =============================================
    // DEFAULT
    // =============================================
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action inconnue',
            'available_actions' => [
                'submit_contact',
                'check_email'
            ]
        ]);
}

exit;
?>