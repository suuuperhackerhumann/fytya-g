<?php
/**
 * Intégration PayDunya (Checkout Invoice API).
 * Doc : https://paydunya.com/developers/api
 *
 * Toutes les fonctions retournent un tableau ['ok' => bool, ...].
 * Aucune clé n'est stockée ici : elles viennent de includes/config.php
 * (constantes PAYDUNYA_*, elles-mêmes lues depuis les variables d'env).
 */

function paydunyaHeaders(): array {
    return [
        'Content-Type: application/json',
        'PAYDUNYA-MASTER-KEY: ' . PAYDUNYA_MASTER_KEY,
        'PAYDUNYA-PRIVATE-KEY: ' . PAYDUNYA_PRIVATE_KEY,
        'PAYDUNYA-PUBLIC-KEY: ' . PAYDUNYA_PUBLIC_KEY,
        'PAYDUNYA-TOKEN: ' . PAYDUNYA_TOKEN,
    ];
}

function paydunyaConfigured(): bool {
    if (PAYDUNYA_MASTER_KEY === '' || PAYDUNYA_PRIVATE_KEY === ''
        || PAYDUNYA_PUBLIC_KEY === '' || PAYDUNYA_TOKEN === '') {
        return false;
    }
    // Garde-fou : les clés doivent correspondre au mode actif, sinon PayDunya
    // rejette la requête avec une erreur d'authentification peu explicite.
    $prefix = PAYDUNYA_LIVE ? 'live_' : 'test_';
    return strncmp(PAYDUNYA_PRIVATE_KEY, $prefix, strlen($prefix)) === 0
        && strncmp(PAYDUNYA_PUBLIC_KEY, $prefix, strlen($prefix)) === 0;
}

/**
 * Appel HTTP générique vers l'API PayDunya.
 */
function paydunyaCall(string $endpoint, array $body = [], string $method = 'POST'): array {
    $ch = curl_init(PAYDUNYA_API_BASE . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => paydunyaHeaders(),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    $raw   = curl_exec($ch);
    $errno = curl_errno($ch);
    $err   = curl_error($ch);
    $http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return ['ok' => false, 'error' => 'Erreur réseau PayDunya: ' . $err];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Réponse PayDunya illisible (HTTP ' . $http . ')'];
    }
    return ['ok' => true, 'http' => $http, 'data' => $data];
}

/**
 * Crée une facture PayDunya pour une commande et renvoie l'URL de paiement.
 *
 * @param array $commande Ligne de la table `commandes`
 * @param array $items    Lignes de `commande_items` pour cette commande
 * @return array ['ok'=>bool, 'invoice_url'=>?string, 'token'=>?string, 'error'=>?string]
 */
function paydunyaCreateInvoice(array $commande, array $items): array {
    if (!paydunyaConfigured()) {
        return ['ok' => false, 'error' => "Le paiement en ligne n'est pas configuré pour le moment."];
    }

    $itemsPayload = [];
    foreach (array_values($items) as $i => $it) {
        $itemsPayload['item_' . $i] = [
            'name'        => (string)$it['nom_produit'],
            'quantity'    => (int)$it['quantite'],
            'unit_price'  => (int)$it['prix_unit'],
            'total_price' => (int)$it['prix_unit'] * (int)$it['quantite'],
        ];
    }
    if ((int)$commande['frais_livraison'] > 0) {
        $n = count($itemsPayload);
        $itemsPayload['item_' . $n] = [
            'name'        => 'Frais de livraison',
            'quantity'    => 1,
            'unit_price'  => (int)$commande['frais_livraison'],
            'total_price' => (int)$commande['frais_livraison'],
        ];
    }

    $ref = $commande['reference'];

    $body = [
        'invoice' => [
            'total_amount' => (int)$commande['total'],
            'description'  => 'Commande ' . $ref . ' - ' . SITE_NAME,
        ],
        'store' => [
            'name' => SITE_NAME,
        ],
        'actions' => [
            'cancel_url'   => SITE_URL . '/commande-detail.php?ref=' . urlencode($ref) . '&paiement=annule',
            'return_url'   => SITE_URL . '/paiement-retour.php?ref=' . urlencode($ref),
            'callback_url' => SITE_URL . '/paydunya-ipn.php',
        ],
        'custom_data' => [
            'reference'   => $ref,
            'commande_id' => (int)$commande['id'],
        ],
    ];
    if (!empty($itemsPayload)) {
        $body['items']  = $itemsPayload;
        $body['taxes']  = [];
    }

    $resp = paydunyaCall('/checkout-invoice/create', $body, 'POST');
    if (!$resp['ok']) {
        return $resp;
    }
    $data = $resp['data'];
    if (($data['response_code'] ?? null) === '00' && !empty($data['token'])) {
        return [
            'ok'          => true,
            'token'       => $data['token'],
            'invoice_url' => (!empty($data['response_text']) && strncmp($data['response_text'], 'http', 4) === 0)
                ? $data['response_text']
                : (PAYDUNYA_CHECKOUT_BASE . $data['token']),
        ];
    }
    return ['ok' => false, 'error' => $data['response_text'] ?? 'Échec de création de la facture PayDunya.'];
}

/**
 * Vérifie l'état réel d'une facture directement auprès de PayDunya
 * (ne JAMAIS faire confiance uniquement aux données envoyées par l'IPN,
 * on reconfirme toujours avec ce endpoint côté serveur).
 */
function paydunyaConfirm(string $token): array {
    if (!paydunyaConfigured() || $token === '') {
        return ['ok' => false, 'error' => 'Configuration ou jeton manquant.'];
    }
    $ch = curl_init(PAYDUNYA_API_BASE . '/checkout-invoice/confirm/' . rawurlencode($token));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => paydunyaHeaders(),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw   = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($errno) {
        return ['ok' => false, 'error' => 'Erreur réseau PayDunya (confirm).'];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Réponse PayDunya illisible (confirm).'];
    }
    $status = $data['status'] ?? null; // 'completed' | 'pending' | 'cancelled'
    return ['ok' => true, 'status' => $status, 'data' => $data];
}