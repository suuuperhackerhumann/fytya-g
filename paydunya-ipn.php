<?php
/**
 * Callback IPN PayDunya.
 * Configuré comme "callback_url" lors de la création de la facture.
 * PayDunya envoie ici un POST après paiement ; on NE FAIT JAMAIS confiance
 * au contenu du POST seul, on reconfirme toujours via l'API (paydunyaConfirm).
 */
require_once 'includes/config.php';
require_once 'includes/paydunya.php';

header('Content-Type: text/plain');

// PayDunya envoie généralement un champ "data" (JSON) en POST classique.
$raw = $_POST['data'] ?? file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    $payload = $_POST;
}

$token = $payload['invoice']['token']
    ?? $payload['token']
    ?? $_POST['token']
    ?? '';

if ($token === '') {
    http_response_code(400);
    echo 'Missing token';
    exit;
}

$confirm = paydunyaConfirm($token);
if (!$confirm['ok']) {
    http_response_code(502);
    echo 'Confirm failed';
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM commandes WHERE paydunya_token=?");
$stmt->execute([$token]);
$commande = $stmt->fetch();

if (!$commande) {
    http_response_code(404);
    echo 'Order not found';
    exit;
}

if ($confirm['status'] === 'completed') {
    $newStatut = $commande['statut'] === 'en_attente' ? 'confirmee' : $commande['statut'];
    $db->prepare("UPDATE commandes SET payment_status='paye', statut=? WHERE id=?")
       ->execute([$newStatut, $commande['id']]);
} elseif ($confirm['status'] === 'cancelled') {
    $db->prepare("UPDATE commandes SET payment_status='echoue' WHERE id=?")
       ->execute([$commande['id']]);
}
// 'pending' -> on ne change rien, PayDunya rappellera.

http_response_code(200);
echo 'OK';
