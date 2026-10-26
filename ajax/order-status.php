<?php
require_once '../includes/config.php';
initUserSession();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$ref = trim($_POST['ref'] ?? $_GET['ref'] ?? '');
if ($ref === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'missing_ref']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT id, user_id, statut FROM commandes WHERE reference=? LIMIT 1");
$stmt->execute([$ref]);
$cmd = $stmt->fetch();

if (!$cmd || (int)$cmd['user_id'] !== (int)$_SESSION['user']['id']) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'not_found']);
    exit;
}

// Fait automatiquement progresser une commande "en attente" vers "confirmée"
// (simule la validation de la commande par l'équipe, quelques secondes après l'achat).
if ($cmd['statut'] === 'en_attente') {
    $db->prepare("UPDATE commandes SET statut='confirmee' WHERE id=?")->execute([$cmd['id']]);
    $cmd['statut'] = 'confirmee';
}

$info = ORDER_STATUSES[$cmd['statut']] ?? ['label' => $cmd['statut'], 'color' => '#333', 'bg' => '#eee'];
echo json_encode([
    'success' => true,
    'statut'  => $cmd['statut'],
    'label'   => $info['label'],
    'color'   => $info['color'],
    'bg'      => $info['bg'],
]);
