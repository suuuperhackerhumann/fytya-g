<?php
    
require_once __DIR__ . '/secrets.php';

define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('ALLOW_AUTO_SETUP', filter_var(getenv('ALLOW_AUTO_SETUP') ?: '0', FILTER_VALIDATE_BOOLEAN));
define('DB_HOST', 'A_REMPLIR');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', 'A_REMPLIR');
define('DB_USER', 'A_REMPLIR');
define('DB_PASS', 'A_REMPLIR');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
define('SITE_NAME', 'FYTYA-G');
define('COMPANY',   'FYTYA-G');
define('COMPANY_FULL', 'FYTYA-G');
define('COMPANY_EMAIL', 'ETSFYTYAG@GMAIL.COM');
define('COMPANY_PHONE_1', '+225 07 89 06 06 89');
define('COMPANY_PHONE_2', '+225 01 43 95 37 37');
define('COMPANY_WHATSAPP', '+225 07 89 06 06 89');
define('COMPANY_ADDRESS', 'Angré Château, à 100 m du boulevard Latrille, Abidjan, Côte d\'Ivoire');
define('COMPANY_HOURS', 'Lun–Sam · 8h – 19h');
define('COMPANY_TAGLINE', 'Entreprise ivoirienne multi-activités spécialisée dans les épices, œufs, volailles et matériaux BTP.');
define('CURRENCY',  'FCFA');
define('LOGO_PATH', 'img.jpeg');
define('UPLOAD_DIR', 'uploads/products/');
// Numéro WhatsApp au format international sans + ni espaces.
define('WHATSAPP_NUMBER', '2250789060689');

// --- PayDunya ---------------------------------------------------------
// Les clés sont définies dans includes/secrets.php (require_once
// ci-dessus). On NE passe PAS par putenv()/getenv() ici : InfinityFree
// désactive putenv() côté serveur, donc getenv() renverrait toujours
// false et paydunyaConfigured() croirait que rien n'est configuré.
// Ces define() ne servent que de filet de sécurité si secrets.php
// venait à manquer une des clés.
if (!defined('PAYDUNYA_MODE'))        define('PAYDUNYA_MODE', 'test');
if (!defined('PAYDUNYA_MASTER_KEY'))  define('PAYDUNYA_MASTER_KEY', '');
if (!defined('PAYDUNYA_PUBLIC_KEY'))  define('PAYDUNYA_PUBLIC_KEY', '');
if (!defined('PAYDUNYA_PRIVATE_KEY')) define('PAYDUNYA_PRIVATE_KEY', '');
if (!defined('PAYDUNYA_TOKEN'))       define('PAYDUNYA_TOKEN', '');

// L'URL de l'API dépend du mode : sandbox pour les clés test_*, production
// pour les clés live_*. Ne jamais mélanger les deux (erreur d'authentification).
define('PAYDUNYA_LIVE', PAYDUNYA_MODE === 'live');
define('PAYDUNYA_API_BASE', PAYDUNYA_LIVE
    ? 'https://app.paydunya.com/api/v1'
    : 'https://app.paydunya.com/sandbox-api/v1');
define('PAYDUNYA_CHECKOUT_BASE', PAYDUNYA_LIVE
    ? 'https://app.paydunya.com/checkout/invoice/'
    : 'https://app.paydunya.com/sandbox-checkout/invoice/');
// URL publique du site, utilisée pour construire les callback/return/cancel.
define('SITE_URL', rtrim(getenv('SITE_URL') ?: 'https://fitya-g.freedev.app', '/'));
// Méthodes de paiement gérées à la caisse mais qui doivent passer par
// PayDunya (tout sauf le paiement à la livraison).
const PAYDUNYA_METHODS = ['Wave', 'Orange Money', 'MTN Money', 'Moov Money', 'Djamo'];

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Sécurité : l'auto-setup n'est autorisé que si l'application est explicitement en mode développement
            // et avec une variable ALLOW_AUTO_SETUP=1. En production, on laisse le setup manuel via setup.php.
            if (APP_ENV !== 'production' && ALLOW_AUTO_SETUP) {
                $check = $pdo->query("SHOW TABLES LIKE 'admins'");
                if ($check->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE admins (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(100) UNIQUE NOT NULL,
                        password VARCHAR(255) NOT NULL,
                        nom VARCHAR(100),
                        email VARCHAR(100),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_username (username)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                }

                $check2 = $pdo->query("SHOW TABLES LIKE 'password_resets'");
                if ($check2->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE password_resets (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        token_hash VARCHAR(64) NOT NULL,
                        expires_at DATETIME NOT NULL,
                        used TINYINT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        INDEX idx_token_hash (token_hash),
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                }

                $check3 = $pdo->query("SHOW COLUMNS FROM commandes LIKE 'frais_livraison'");
                if ($check3 && $check3->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE commandes ADD COLUMN frais_livraison INT NOT NULL DEFAULT 0 AFTER total");
                }
            }
        } catch (PDOException $e) {
            try {
                $pdo = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";charset=".DB_CHARSET, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $sql_file = __DIR__ . '/../database.sql';
                if (file_exists($sql_file)) {
                    $sql = file_get_contents($sql_file);
                    $pdo->exec($sql);
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]);
                }
            } catch (PDOException $e2) {
                die('<div style="font-family:sans-serif;padding:40px;background:#fff5f5;color:#c00;border-left:4px solid #c00;margin:40px">
                    <h2>Erreur de base de données</h2>
                    <p>'.$e2->getMessage().'</p>
                    <p>Vérifiez que MySQL est démarré et vos identifiants sont corrects</p>
                </div>');
            }
        }
    }
    return $pdo;
}

function initUserSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookieParams['path'] ?? '/',
            'domain' => $cookieParams['domain'] ?? '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strpos((string)($_SERVER['SERVER_NAME'] ?? ''), 'localhost') === false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}

function applySecurityHeaders(): void {
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self' https://wa.me");
}

function getClientIp(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP'] as $key) {
        if (!empty($_SERVER[$key])) {
            $candidate = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                $ip = $candidate;
                break;
            }
        }
    }
    return $ip;
}

function loginAttemptKey(string $identifier): string {
    $clean = strtolower(trim($identifier));
    if ($clean === '') {
        $clean = 'unknown';
    }
    return 'login_block_' . md5(getClientIp() . ':' . $clean);
}

function canAttemptLogin(string $identifier, int $maxAttempts = 5, int $windowSeconds = 300): bool {
    initUserSession();
    $key = loginAttemptKey($identifier);
    $attempts = $_SESSION[$key] ?? [];
    $now = time();
    $attempts = array_values(array_filter($attempts, fn($ts) => $ts > $now - $windowSeconds));
    if (count($attempts) >= $maxAttempts) {
        $_SESSION[$key] = $attempts;
        return false;
    }
    return true;
}

function registerFailedLogin(string $identifier): void {
    initUserSession();
    $key = loginAttemptKey($identifier);
    $attempts = $_SESSION[$key] ?? [];
    $attempts[] = time();
    $_SESSION[$key] = array_values($attempts);
}

function clearLoginAttempts(string $identifier): void {
    initUserSession();
    unset($_SESSION[loginAttemptKey($identifier)]);
}

function csrfToken(): string {
    initUserSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function validateCsrfToken(?string $token): bool {
    initUserSession();
    return is_string($token)
        && is_string($_SESSION['csrf_token'] ?? null)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function getCurrentUser() {
    initUserSession();
    return $_SESSION['user'] ?? null;
}

function formatPrice(int $p): string {
    return number_format($p, 0, ',', ' ').' FCFA';
}
function stars(float $r): string {
    return starIcons($r);
}
function sanitize(string $s): string {
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

function isSafeRedirectTarget(string $url): bool {
    if ($url === '') {
        return false;
    }
    if (preg_match('/^(?:https?:)?\/\//i', $url)) {
        return false;
    }
    if (!preg_match('/^[A-Za-z0-9_\-\.\/\\?=&%]+$/', $url)) {
        return false;
    }
    return true;
}

function redirect(string $url): void {
    if (!isSafeRedirectTarget($url)) {
        $url = 'accueil.php';
    }
    header("Location: $url");
    exit;
}

function genRef(): string {
    return 'FYT-'.date('Y').'-'.strtoupper(substr(uniqid(),0,6));
}

// Frais de livraison : gratuite pour œufs & poulet, 500 FCFA dès qu'il y a des épices dans le panier.
function computeDeliveryFee(array $items): int {
    foreach ($items as $it) {
        if (($it['categorie'] ?? '') === 'epices') {
            return 500;
        }
    }
    return 0;
}

// Icône Font Awesome utilisée pour un produit quand il n'a pas de photo.
// -> Modifiable ici en un seul endroit : par défaut on reprend l'icône de la
//    catégorie du produit (définie dans CATEGORIES ci-dessous), mais on peut
//    aussi la personnaliser produit par produit via ce tableau, en utilisant
//    l'id ou le nom du produit comme clé.
const PRODUCT_ICON_OVERRIDES = [
    // Exemples (décommente et adapte pour personnaliser une icône précise) :
    // 12                => 'pepper-hot',      // par id de produit
    // 'Poulet fermier'  => 'drumstick-bite',  // par nom exact de produit
];

// Retourne le nom d'icône Font Awesome (sans le préfixe "fa-") à utiliser
// pour un produit donné.
function getProductIcon(array $p): string {
    if (!empty($p['id']) && isset(PRODUCT_ICON_OVERRIDES[(int)$p['id']])) {
        return PRODUCT_ICON_OVERRIDES[(int)$p['id']];
    }
    if (!empty($p['nom']) && isset(PRODUCT_ICON_OVERRIDES[$p['nom']])) {
        return PRODUCT_ICON_OVERRIDES[$p['nom']];
    }
    $cat = $p['categorie'] ?? '';
    if ($cat && isset(CATEGORIES[$cat]['icon'])) {
        return CATEGORIES[$cat]['icon'];
    }
    return 'box'; // icône par défaut si aucune catégorie ne correspond
}

// Affiche la photo du produit si elle existe, sinon retombe sur une icône Font Awesome.
function productVisual(array $p): string {
    if (!empty($p['image'])) {
        return '<img src="'.sanitize($p['image']).'" alt="'.sanitize($p['nom']).'" loading="lazy">';
    }
    $icon = sanitize(getProductIcon($p));
    return '<i class="fa-solid fa-'.$icon.'" aria-hidden="true"></i>';
}

const ORDER_STATUSES = [
    'en_attente'   => ['label'=>'En attente',   'color'=>'#B45309','bg'=>'#FEF3C7'],
    'confirmee'    => ['label'=>'Confirmée',    'color'=>'#1D4ED8','bg'=>'#DBEAFE'],
    'en_livraison' => ['label'=>'En livraison', 'color'=>'#6D28D9','bg'=>'#EDE9FE'],
    'livree'       => ['label'=>'Livrée',       'color'=>'#065F46','bg'=>'#D1FAE5'],
    'annulee'      => ['label'=>'Annulée',      'color'=>'#991B1B','bg'=>'#FEE2E2'],
];


const CATEGORIES = [
    'btp'    => ['label'=>'BTP',           'icon'=>'hard-hat',      'desc'=>'Matériaux et équipements de construction pour vos chantiers.',    'page'=>'btp.php'],
    'epices' => ['label'=>'Épices',        'icon'=>'pepper-hot',    'desc'=>'Épices et mélanges du monde entier, sélectionnés avec soin.',      'page'=>'epices.php'],
    'oeuf'   => ['label'=>'Œufs',          'icon'=>'egg',           'desc'=>'Œufs frais, contrôlés et livrés au quotidien.',                    'page'=>'oeufs.php'],
    'poulet' => ['label'=>'Poulet',        'icon'=>'feather',       'desc'=>'Volaille fraîche et de qualité, du fermier à votre table.',        'page'=>'poulet.php'],
];
?>
