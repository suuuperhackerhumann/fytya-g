<?php
/**
 * Endpoint de "polling" appelé en JS toutes les X secondes depuis le panel admin.
 * Permet de détecter l'arrivée d'une nouvelle commande en temps réel : un admin
 * qui a l'écran ouvert voit la commande apparaître et le panel se rafraîchit
 * tout seul (badge, bip sonore, actualisation).
 */
session_start();
if (!isset($_SESSION['admin_logged'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

require_once '../includes/config.php';
$db = getDB();
header('Content-Type: application/json; charset=utf-8');

$last_id  = (int)$db->query("SELECT COALESCE(MAX(id),0) FROM commandes")->fetchColumn();
$last_ref = $db->query("SELECT reference FROM commandes ORDER BY id DESC LIMIT 1")->fetchColumn();
$nb_attente = (int)$db->query("SELECT COUNT(*) FROM commandes WHERE statut='en_attente'")->fetchColumn();

echo json_encode([
    'last_id'    => $last_id,
    'last_ref'   => $last_ref ?: null,
    'nb_attente' => $nb_attente,
]);
