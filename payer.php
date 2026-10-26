<?php
require_once 'includes/config.php';
require_once 'includes/paydunya.php';
initUserSession();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$ref  = $_GET['ref'] ?? '';
$db   = getDB();

$stmt = $db->prepare("SELECT * FROM commandes WHERE reference=? AND user_id=?");
$stmt->execute([$ref, $user['id']]);
$commande = $stmt->fetch();

if (!$commande) {
    header('Location: mes-commandes.php');
    exit;
}

if ($commande['paiement'] === 'A la livraison' || ($commande['payment_status'] ?? 'non_paye') === 'paye') {
    header('Location: commande-detail.php?ref=' . urlencode($ref));
    exit;
}

$items_stmt = $db->prepare("SELECT * FROM commande_items WHERE commande_id=? ORDER BY id");
$items_stmt->execute([$commande['id']]);
$items = $items_stmt->fetchAll();

$result = paydunyaCreateInvoice($commande, $items);

// On ne journalise que les échecs, et sans jamais écrire de clé d'API.
if (empty($result['ok'])) {
    file_put_contents(__DIR__ . '/includes/paydunya-debug.log',
        date('c') . ' mode=' . PAYDUNYA_MODE . ' ref=' . $ref
            . ' erreur=' . ($result['error'] ?? 'inconnue') . "\n",
        FILE_APPEND
    );
}

if ($result['ok']) {
    $db->prepare("UPDATE commandes SET paydunya_token=? WHERE id=?")
       ->execute([$result['token'], $commande['id']]);
    header('Location: ' . $result['invoice_url']);
    exit;
}

header('Location: commande-detail.php?ref=' . urlencode($ref) . '&paiement=erreur');
exit;