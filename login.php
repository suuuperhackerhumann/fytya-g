<?php
// Compatibilité : l'ancienne page login.php redirige vers index.php,
// qui est désormais la page de connexion du site.
$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: index.php' . ($qs ? '?' . $qs : ''));
exit;
