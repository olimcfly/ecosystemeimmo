<?php
/**
 * ÉCOSYSTÈME IMMO LOCAL+ - Dashboard CRM
 * Page principale de l'espace administrateur
 */

session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../config/admin-config.php';

// Vérifier authentification
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/auth/login');
    exit;
}

// Total des leads
$stmt = $pdo->query("SELECT COUNT(*) as total FROM leads");
$totalLeads = $stmt->fetch()['total'];

// Leads cette semaine
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM leads 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$thisWeekLeads = $stmt->fetch()['total'];

// À appeler
$stmt = $pdo->query("SELECT COUNT(*) as total FROM v_leads_to_call");
$toCall = $stmt->fetch()['total'] ?? 0;

// Clients
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM leads 
    WHERE status = 'client' OR status = 'Clients'
");
$clients = $stmt->fetch()['total'];

// En séquence
$stmt = $pdo->query("SELECT COUNT(*) as total FROM v_leads_in_sequence");
$inSequence = $stmt->fetch()['total'] ?? 0;

// Leads récents
$stmt = $pdo->query("
    SELECT 
        id, 
        CONCAT(firstname, ' ', lastname) as name, 
        email, 
        intent, 
        status, 
        created_at 
    FROM leads 
    ORDER BY created_at DESC 
    LIMIT 10
");
$recentLeads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard CRM - <?= SITE_NAME ?></title>
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
        }
        
        .header-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
        }
        
        .header-date {
            font-size: 0.85rem;
            color: var(--gray-500);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--gray-500);
            font-weight: 500;
        }
        
        .table-section {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .view-all-btn {
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .view-all-btn:hover {
            background: var(--secondary);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table thead {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--gray-700);
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.9rem;
        }
        
        .table tbody tr:hover {
            background: var(--gray-50);
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
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
        
        .action-btn {
            padding: 0.4rem 0.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.4rem;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background: var(--secondary);
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
            .main { margin-left: 0; }
            .sidebar-footer {
                position: relative;
                bottom: auto;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                    <a href="/admin/crm" class="sidebar-item active">
                        <span class="sidebar-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="/admin/crm/leads" class="sidebar-item">
                        <span class="sidebar-icon">👥</span>
                        <span>Tous les Leads</span>
                        <span class="sidebar-badge"><?= $totalLeads ?></span>
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
                        <span class="sidebar-badge"><?= $clients ?></span>
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
                <div>
                    <h1 class="header-title">📊 Dashboard</h1>
                </div>
                <div class="header-date"><?= date('d/m/Y H:i') ?></div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?= $totalLeads ?></div>
                    <div class="stat-label">Total Leads</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value"><?= $thisWeekLeads ?></div>
                    <div class="stat-label">Cette semaine</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">☎️</div>
                    <div class="stat-value"><?= $toCall ?></div>
                    <div class="stat-label">À appeler</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?= $clients ?></div>
                    <div class="stat-label">Clients</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📧</div>
                    <div class="stat-value"><?= $inSequence ?></div>
                    <div class="stat-label">En séquence</div>
                </div>
            </div>
            
            <div class="table-section">
                <div class="table-header">
                    <div class="table-title">Leads récents</div>
                    <a href="/admin/crm/leads" class="view-all-btn">Voir tout</a>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Intent</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLeads as $lead): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($lead['name']) ?></strong></td>
                            <td><?= htmlspecialchars($lead['email']) ?></td>
                            <td>
                                <?php
                                $intentMap = [
                                    'cold' => ['label' => 'Cold', 'class' => 'badge-cold'],
                                    'ressource' => ['label' => 'Ressource', 'class' => 'badge-ressource'],
                                    'outil' => ['label' => 'Outil', 'class' => 'badge-outil'],
                                    'diagnostic' => ['label' => 'Diagnostic', 'class' => 'badge-diagnostic'],
                                    'demo' => ['label' => 'Demo', 'class' => 'badge-demo'],
                                ];
                                $intent = $lead['intent'] ?? 'cold';
                                $intentInfo = $intentMap[$intent] ?? ['label' => ucfirst($intent), 'class' => 'badge-outil'];
                                ?>
                                <span class="badge <?= $intentInfo['class'] ?>"><?= htmlspecialchars($intentInfo['label']) ?></span>
                            </td>
                            <td>
                                <?php
                                $statusMap = [
                                    'nouveau' => 'badge-nouveau',
                                    'en_reflexion' => 'badge-reflexion',
                                    'actif' => 'badge-actif',
                                    'appel_re' => 'badge-appel',
                                    'client' => 'badge-client',
                                ];
                                $statusClass = $statusMap[$lead['status']] ?? 'badge-nouveau';
                                $statusLabel = ucfirst(str_replace('_', ' ', $lead['status'] ?? 'nouveau'));
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($lead['created_at'])) ?></td>
                            <td>
                                <a href="/admin/crm/lead-detail?id=<?= $lead['id'] ?>" class="action-btn">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>