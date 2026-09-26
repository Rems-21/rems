<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdminAuth();

$pdo = getDatabaseConnection();
if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'Base de données non accessible.'
    ]);
    exit;
}

$range = $_GET['range'] ?? '30d';
$days = 30;
if ($range === '7d') $days = 7;
if ($range === '14d') $days = 14;
if ($range === '30d') $days = 30;
if ($range === 'all') $days = 90;

$startDate = date('Y-m-d 00:00:00', strtotime("-{$days} days"));

// ── 1. TIMELINE DES VISITES & VISITEURS UNIQUES PAR JOUR ────────────
$stmtVis = $pdo->prepare("SELECT 
    DATE(visited_at) as visit_date,
    COUNT(*) as total_visits,
    COUNT(DISTINCT ip_address) as unique_visits
    FROM `site_visits`
    WHERE visited_at >= ?
    GROUP BY DATE(visited_at)
    ORDER BY visit_date ASC");
$stmtVis->execute([$startDate]);
$rows = $stmtVis->fetchAll();

// Remplir tous les jours de la plage
$datesMap = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $datesMap[$d] = [
        'label'   => date('d M', strtotime("-{$i} days")),
        'visits'  => 0,
        'uniques' => 0
    ];
}

foreach ($rows as $r) {
    $vd = $r['visit_date'];
    if (isset($datesMap[$vd])) {
        $datesMap[$vd]['visits'] = (int)$r['total_visits'];
        $datesMap[$vd]['uniques'] = (int)$r['unique_visits'];
    }
}

$labels = [];
$visits = [];
$uniques = [];
foreach ($datesMap as $item) {
    $labels[] = $item['label'];
    $visits[] = $item['visits'];
    $uniques[] = $item['uniques'];
}

// ── 2. SOURCES DE TRAFIC RÉELLES (DEPUIS LE REFERRER) ───────────────
$stmtSources = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN referrer LIKE '%google%' OR referrer LIKE '%bing%' OR referrer LIKE '%yahoo%' OR referrer LIKE '%duckduckgo%' THEN 1 ELSE 0 END) as google,
    SUM(CASE WHEN referrer LIKE '%whatsapp%' OR referrer LIKE '%wa.me%' OR referrer LIKE '%linkedin%' OR referrer LIKE '%facebook%' OR referrer LIKE '%instagram%' OR referrer LIKE '%t.co%' OR referrer LIKE '%twitter%' OR referrer LIKE '%github%' THEN 1 ELSE 0 END) as social,
    SUM(CASE WHEN referrer IS NULL OR referrer = '' OR referrer LIKE '%localhost%' OR referrer LIKE '%127.0.0.1%' THEN 1 ELSE 0 END) as direct
    FROM `site_visits`
    WHERE visited_at >= ?");
$stmtSources->execute([$startDate]);
$srcRow = $stmtSources->fetch() ?: [];

$totalSrc = (int)($srcRow['total'] ?? 0);
$googleCount = (int)($srcRow['google'] ?? 0);
$socialCount = (int)($srcRow['social'] ?? 0);
$directCount = (int)($srcRow['direct'] ?? 0);
$referralCount = max(0, $totalSrc - ($googleCount + $socialCount + $directCount));

if ($totalSrc > 0) {
    $pctGoogle = round(($googleCount / $totalSrc) * 100, 1);
    $pctSocial = round(($socialCount / $totalSrc) * 100, 1);
    $pctDirect = round(($directCount / $totalSrc) * 100, 1);
    $pctReferral = round(($referralCount / $totalSrc) * 100, 1);
    $pctOther = max(0, round(100 - ($pctGoogle + $pctSocial + $pctDirect + $pctReferral), 1));
} else {
    // Si aucune visite dans la période, valeurs neutres de production
    $pctGoogle = 0.0;
    $pctSocial = 0.0;
    $pctDirect = 100.0;
    $pctReferral = 0.0;
    $pctOther = 0.0;
}

// ── 3. RÉPARTITION PAR FORMATION (RÉEL) ─────────────────────────────
$stmtResa = $pdo->query("SELECT 
    formation_titre, 
    COUNT(*) as count 
    FROM `reservations` 
    GROUP BY formation_titre 
    ORDER BY count DESC 
    LIMIT 5");
$resaRows = $stmtResa->fetchAll();

$doughnutLabels = [];
$doughnutValues = [];
if (!empty($resaRows)) {
    foreach ($resaRows as $r) {
        $doughnutLabels[] = $r['formation_titre'];
        $doughnutValues[] = (int)$r['count'];
    }
}

echo json_encode([
    'success'  => true,
    'timeline' => [
        'labels'  => $labels,
        'visits'  => $visits,
        'uniques' => $uniques
    ],
    'sources'  => [
        'total'    => $totalSrc,
        'labels'   => ['Recherche Google', 'Réseaux sociaux', 'Accès directs', 'Sites référents', 'Autres'],
        'counts'   => [$googleCount, $socialCount, $directCount, $referralCount, 0],
        'percents' => [$pctGoogle, $pctSocial, $pctDirect, $pctReferral, $pctOther]
    ],
    'doughnut' => [
        'labels' => $doughnutLabels,
        'values' => $doughnutValues
    ]
], JSON_UNESCAPED_UNICODE);
