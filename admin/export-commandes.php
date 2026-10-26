<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: login.php'); exit;
}
require_once '../includes/config.php';
require_once '../vendor/fpdf/fpdf.php';
$db = getDB();

// ── Lecture des filtres ─────────────────────────────
$statut    = $_GET['statut']    ?? 'all';   // 'all' ou une clé de ORDER_STATUSES
$categorie = $_GET['categorie'] ?? 'all';   // 'all' ou une clé de CATEGORIES
$date_debut = trim($_GET['date_debut'] ?? '');
$date_fin   = trim($_GET['date_fin']   ?? '');
$tri        = $_GET['tri'] ?? 'date';       // date | statut | montant

// ── Construction de la requête ──────────────────────
$where  = ['1=1'];
$params = [];

if ($statut !== 'all' && array_key_exists($statut, ORDER_STATUSES)) {
    $where[] = 'c.statut = ?';
    $params[] = $statut;
}
if ($date_debut !== '') {
    $where[] = 'c.created_at >= ?';
    $params[] = $date_debut . ' 00:00:00';
}
if ($date_fin !== '') {
    $where[] = 'c.created_at <= ?';
    $params[] = $date_fin . ' 23:59:59';
}

$joinCategorie = '';
if ($categorie !== 'all' && array_key_exists($categorie, CATEGORIES)) {
    // Ne garder que les commandes contenant au moins un article de cette catégorie
    $joinCategorie = 'JOIN commande_items ci_f ON ci_f.commande_id = c.id
                       JOIN produits p_f ON p_f.id = ci_f.produit_id AND p_f.categorie = ?';
    // On insère ce paramètre en tout début (avant les autres filtres, il vient après le JOIN dans la requête)
}

$orderSQL = 'c.created_at DESC';
if ($tri === 'statut')   $orderSQL = 'c.statut ASC, c.created_at DESC';
if ($tri === 'montant')  $orderSQL = 'c.total DESC';
if ($tri === 'date')     $orderSQL = 'c.created_at DESC';

$whereSQL = implode(' AND ', $where);

$sql = "SELECT DISTINCT c.* FROM commandes c $joinCategorie WHERE $whereSQL ORDER BY $orderSQL";
$finalParams = [];
if ($joinCategorie) $finalParams[] = $categorie;
$finalParams = array_merge($finalParams, $params);

$stmt = $db->prepare($sql);
$stmt->execute($finalParams);
$commandes = $stmt->fetchAll();

// ── Catégories de chaque commande (pour affichage dans le PDF) ──
$categoriesParCommande = [];
if (!empty($commandes)) {
    $ids = array_column($commandes, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $catStmt = $db->prepare("
        SELECT ci.commande_id, GROUP_CONCAT(DISTINCT p.categorie SEPARATOR ', ') AS cats
        FROM commande_items ci
        LEFT JOIN produits p ON p.id = ci.produit_id
        WHERE ci.commande_id IN ($in)
        GROUP BY ci.commande_id
    ");
    $catStmt->execute($ids);
    foreach ($catStmt->fetchAll() as $row) {
        $labels = array_map(function($slug) {
            return CATEGORIES[$slug]['label'] ?? $slug;
        }, array_filter(explode(', ', $row['cats'])));
        $categoriesParCommande[$row['commande_id']] = implode(', ', array_unique($labels));
    }
}

// ── Aide : conversion UTF-8 -> Latin-1 pour FPDF (police de base) ──
function t(string $s): string {
    $r = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
    return $r !== false ? $r : $s;
}
// Tronque une chaîne (déjà convertie en Latin-1 par t()) à $len caractères
function tw(string $s, int $len): string {
    $s = t($s);
    return strlen($s) > $len ? substr($s, 0, $len - 3) . '...' : $s;
}

// ── Génération du PDF ────────────────────────────────
class CommandesPDF extends FPDF {
    public $sousTitre = '';
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(255, 102, 0);
        $this->Cell(0, 10, t('FYTYA-G'), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, t('Export des commandes'), 0, 1, 'L');
        if ($this->sousTitre) {
            $this->Cell(0, 5, t($this->sousTitre), 0, 1, 'L');
        }
        $this->SetTextColor(0, 0, 0);
        $this->Ln(3);
        $this->SetDrawColor(230, 230, 230);
        $this->Line(10, $this->GetY(), 287, $this->GetY());
        $this->Ln(4);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, t('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'C');
    }
    function TableHeader() {
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(255, 102, 0);
        $this->SetTextColor(255, 255, 255);
        $widths = [30, 38, 22, 26, 30, 24, 26, 24];
        $labels = ['Reference', 'Client', 'Ville', 'Paiement', 'Categorie(s)', 'Total', 'Statut', 'Date'];
        foreach ($labels as $i => $label) {
            $this->Cell($widths[$i], 8, t($label), 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
    }
}

$pdf = new CommandesPDF('L', 'mm', 'A4');

// Résumé des filtres appliqués (affiché en sous-titre)
$filtres = [];
if ($statut !== 'all')    $filtres[] = 'Statut : ' . ORDER_STATUSES[$statut]['label'];
if ($categorie !== 'all') $filtres[] = 'Categorie : ' . (CATEGORIES[$categorie]['label'] ?? $categorie);
if ($date_debut)          $filtres[] = 'Du ' . date('d/m/Y', strtotime($date_debut));
if ($date_fin)            $filtres[] = 'Au ' . date('d/m/Y', strtotime($date_fin));
$pdf->sousTitre = $filtres ? implode('   |   ', $filtres) : 'Toutes les commandes';

$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->TableHeader();

$widths = [30, 38, 22, 26, 30, 24, 26, 24];
$total_general = 0;
$rowCount = 0;

foreach ($commandes as $c) {
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        $pdf->TableHeader();
    }
    $st = ORDER_STATUSES[$c['statut']] ?? ['label' => $c['statut']];
    $cats = $categoriesParCommande[$c['id']] ?? '-';
    $client = $c['client_prenom'] . ' ' . $c['client_nom'];

    $pdf->Cell($widths[0], 7, tw($c['reference'], 18), 1, 0, 'L');
    $pdf->Cell($widths[1], 7, tw($client, 24), 1, 0, 'L');
    $pdf->Cell($widths[2], 7, tw($c['client_ville'] ?? '', 12), 1, 0, 'L');
    $pdf->Cell($widths[3], 7, tw($c['paiement'] ?? '', 14), 1, 0, 'L');
    $pdf->Cell($widths[4], 7, tw($cats, 18), 1, 0, 'L');
    $pdf->Cell($widths[5], 7, number_format((int)$c['total'], 0, ',', ' ') . ' F', 1, 0, 'R');
    $pdf->Cell($widths[6], 7, t($st['label']), 1, 0, 'L');
    $pdf->Cell($widths[7], 7, date('d/m/Y', strtotime($c['created_at'])), 1, 1, 'C');

    if ($c['statut'] !== 'annulee') $total_general += (int)$c['total'];
    $rowCount++;
}

if (empty($commandes)) {
    $pdf->Cell(array_sum($widths), 10, t('Aucune commande ne correspond a ces criteres.'), 1, 1, 'C');
}

// Ligne de synthèse
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(array_sum(array_slice($widths, 0, 5)), 8, t($rowCount . ' commande(s)'), 1, 0, 'L', true);
$pdf->Cell($widths[5], 8, number_format($total_general, 0, ',', ' ') . ' F', 1, 0, 'R', true);
$pdf->Cell($widths[6] + $widths[7], 8, '', 1, 1, 'C', true);

$filename = 'commandes_fytya-g_' . date('Y-m-d_His') . '.pdf';
$pdf->Output('D', $filename);
