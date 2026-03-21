<?php
/**
 * ÉCOSYSTÈME IMMO LOCAL+ - Détail d'un Lead
 * Page pour consulter et modifier les infos d'un lead
 */

session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../config/admin-config.php';

// Vérifier authentification
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/auth/login');
    exit;
}

// Récupérer l'ID du lead
$leadId = intval($_GET['id'] ?? 0);
if (!$leadId) {
    header('Location: /admin/crm/leads');
    exit;
}

// ============================================
// RÉCUPÉRER LE LEAD
// ============================================
$stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
$stmt->execute([$leadId]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) {
    header('Location: /admin/crm/leads');
    exit;
}

// ============================================
// TRAITER LES MODIFICATIONS
// ============================================
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? $lead['status'];
    $newNotes = $_POST['notes'] ?? $lead['notes'];
    $callBooked = isset($_POST['call_booked']) ? 1 : 0;
    
    $stmt = $pdo->prepare("
        UPDATE leads 
        SET status = ?, notes = ?, call_booked_at = IF(? = 1, NOW(), call_booked_at), updated_at = NOW()
        WHERE id = ?
    ");
    
    if ($stmt->execute([$newStatus, $newNotes, $callBooked, $leadId])) {
        $message = '✅ Lead mis à jour avec succès';
        $messageType = 'success';
        
        // Recharger les données
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$leadId]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = '❌ Erreur lors de la mise à jour';
        $messageType = 'error';
    }
}

// Récupérer les statuts disponibles
$stmt = $pdo->query("SELECT DISTINCT status FROM leads WHERE status IS NOT NULL ORDER BY status");
$statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Détail du Lead - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 220px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 2rem;
        }
        
        .sidebar-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            opacity: 0.8;
            letter-spacing: 0.5px;
        }
        
        .sidebar-section {
            margin-bottom: 2rem;
            padding: 0 1rem;
        }
        
        .sidebar-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            opacity: 0.6;
            margin-bottom: 0.75rem;
            padding: 0 0.5rem;
            letter-spacing: 0.5px;
        }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }
        
        .sidebar-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-item.active {
            background: rgba(255,255,255,0.2);
            color: white;
            font-weight: 600;
        }
        
        .sidebar-icon {
            font-size: 1.2rem;
            width: 1.5rem;
        }
        
        .sidebar-badge {
            margin-left: auto;
            background: rgba(255,255,255,0.3);
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        
        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .user-info {
            flex: 1;
            font-size: 0.85rem;
        }
        
        .user-name {
            font-weight: 600;
            display: block;
        }
        
        .user-email {
            opacity: 0.7;
            font-size: 0.75rem;
        }
        
        .logout-btn {
            width: 100%;
            padding: 0.5rem;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .main {
            flex: 1;
            margin-left: 220px;
            padding: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        
        .header-left h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }
        
        .breadcrumb {
            font-size: 0.85rem;
            color: var(--gray-500);
        }
        
        .back-btn {
            padding: 0.75rem 1.5rem;
            background: var(--gray-200);
            color: var(--gray-800);
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            background: var(--gray-300);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .info-group {
            margin-bottom: 1.5rem;
        }
        
        .info-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--gray-500);
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: var(--gray-900);
            word-break: break-all;
        }
        
        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-diagnostic { background: #fca5a5; color: #7f1d1d; }
        .badge-ressource { background: #86efac; color: #166534; }
        .badge-outil { background: #fcd34d; color: #78350f; }
        .badge-nouveau { background: #93c5fd; color: #1e3a8a; }
        .badge-reflexion { background: #fbcfe8; color: #831843; }
        .badge-actif { background: #c7d2fe; color: #3730a3; }
        .badge-appel { background: #fecaca; color: #7f1d1d; }
        .badge-client { background: #a7f3d0; color: #065f46; }
        .badge-cold { background: #fcd34d; color: #78350f; }
        .badge-demo { background: #c7d2fe; color: #3730a3; }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
        }
        
        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-800);
        }
        
        .btn-secondary:hover {
            background: var(--gray-300);
        }
        
        .score-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .button-group .btn {
            flex: 1;
        }
        
        @media (max-width: 1024px) {
            .sidebar { width: 200px; }
            .main { margin-left: 200px; }
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 1rem 0;
            }
            .main { margin-left: 0; padding: 1rem; }
            .sidebar-footer {
                position: relative;
                bottom: auto;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            .grid-2 {
                grid-template-columns: 1fr;
            }
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">🎯 ÉCOSYSTÈME IMMO LOCAL+</div>
            </div>
            
            <nav class="sidebar-menu">
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Principal</div>
                    <a href="/admin/crm" class="sidebar-item">
                        <span class="sidebar-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="/admin/crm/leads" class="sidebar-item active">
                        <span class="sidebar-icon">👥</span>
                        <span>Tous les Leads</span>
                    </a>
                </div>
                
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Par Intent</div>
                    <a href="/admin/crm/leads?intent=diagnostic" class="sidebar-item">
                        <span class="sidebar-icon">🔍</span>
                        <span>Diagnostic</span>
                    </a>
                    <a href="/admin/crm/leads?intent=demo" class="sidebar-item">
                        <span class="sidebar-icon">🎥</span>
                        <span>Demo</span>
                    </a>
                    <a href="/admin/crm/leads?intent=outil" class="sidebar-item">
                        <span class="sidebar-icon">🛠️</span>
                        <span>Outil</span>
                    </a>
                    <a href="/admin/crm/leads?intent=ressource" class="sidebar-item">
                        <span class="sidebar-icon">📚</span>
                        <span>Ressource</span>
                    </a>
                    <a href="/admin/crm/leads?intent=cold" class="sidebar-item">
                        <span class="sidebar-icon">❄️</span>
                        <span>Cold</span>
                    </a>
                    <a href="/admin/crm/leads?status=client" class="sidebar-item">
                        <span class="sidebar-icon">✅</span>
                        <span>Clients</span>
                    </a>
                </div>
                
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Outils</div>
                    <a href="/admin/emails" class="sidebar-item">
                        <span class="sidebar-icon">📧</span>
                        <span>Messages</span>
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-card">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['admin_firstname'] ?? 'A', 0, 1)) ?></div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['admin_firstname'] ?? 'Admin') ?></span>
                        <span class="user-email"><?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?></span>
                    </div>
                </div>
                <a href="/admin/auth/logout" class="logout-btn">Déconnexion</a>
            </div>
        </aside>
        
        <main class="main">
            <div class="header">
                <div class="header-left">
                    <h1>👤 <?= htmlspecialchars($lead['firstname'] ?? '') ?> <?= htmlspecialchars($lead['lastname'] ?? '') ?></h1>
                    <div class="breadcrumb">Dashboard > Leads > Détail</div>
                </div>
                <a href="/admin/crm/leads" class="back-btn">← Retour</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <span><?= $message ?></span>
                </div>
            <?php endif; ?>
            
            <div class="grid-2">
                <!-- Infos Lead -->
                <div class="card">
                    <div class="card-title">ℹ️ Informations Générales</div>
                    
                    <div class="info-group">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" style="color: var(--primary); text-decoration: none;">
                                <?= htmlspecialchars($lead['email']) ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value">
                            <?php if ($lead['phone']): ?>
                                <a href="tel:<?= htmlspecialchars($lead['phone']) ?>" style="color: var(--primary); text-decoration: none;">
                                    <?= htmlspecialchars($lead['phone']) ?>
                                </a>
                            <?php else: ?>
                                <span style="color: var(--gray-500);">Non fourni</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Ville</div>
                        <div class="info-value"><?= htmlspecialchars($lead['city'] ?? 'Non fournie') ?></div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Intent</div>
                        <div class="info-value">
                            <?php
                            $intentMap = [
                                'cold' => 'badge-cold',
                                'ressource' => 'badge-ressource',
                                'outil' => 'badge-outil',
                                'diagnostic' => 'badge-diagnostic',
                                'demo' => 'badge-demo',
                            ];
                            $intentClass = $intentMap[$lead['intent']] ?? 'badge-cold';
                            ?>
                            <span class="badge <?= $intentClass ?>"><?= ucfirst(htmlspecialchars($lead['intent'] ?? 'cold')) ?></span>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Score</div>
                        <div class="info-value">
                            <span class="score-badge"><?= intval($lead['score'] ?? 0) ?></span>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Date d'ajout</div>
                        <div class="info-value"><?= date('d/m/Y H:i', strtotime($lead['created_at'])) ?></div>
                    </div>
                </div>
                
                <!-- Modification -->
                <div class="card">
                    <div class="card-title">✏️ Modifier</div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>" 
                                            <?= $lead['status'] === $status ? 'selected' : '' ?>>
                                        <?= ucfirst(str_replace('_', ' ', htmlspecialchars($status))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-textarea" placeholder="Ajouter des notes sur ce lead..."><?= htmlspecialchars($lead['notes'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="call_booked" name="call_booked" class="checkbox" 
                                       <?= !empty($lead['call_booked_at']) ? 'checked' : '' ?>>
                                <label for="call_booked" style="margin: 0; cursor: pointer;">
                                    ☎️ Appel programmé
                                </label>
                            </div>
                        </div>
                        
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                            <a href="/admin/crm/leads" class="btn btn-secondary" style="text-decoration: none; text-align: center;">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Message Lead -->
            <?php if (!empty($lead['message'])): ?>
            <div class="card" style="margin-top: 2rem;">
                <div class="card-title">💬 Message Initial</div>
                <div class="info-value" style="line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($lead['message'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>