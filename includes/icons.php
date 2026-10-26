<?php
/**
 * Icônes de l'interface — Font Awesome (Solid) uniquement.
 * Usage : <?=icon('shopping-cart', 18)?>  ou  <?=icon('check', 16, 'ma-classe')?>
 * Les noms historiques (style Feather) sont convertis vers leur équivalent Font Awesome ;
 * un nom Font Awesome direct (ex. 'pepper-hot', 'truck-fast') est aussi accepté.
 */
if (!function_exists('icon')) {
function icon(string $name, int $size = 18, string $class = ''): string
{
    static $map = [
        'shopping-cart' => 'cart-shopping', 'shopping-bag' => 'bag-shopping', 'package' => 'box',
        'user' => 'user', 'x' => 'xmark', 'x-circle' => 'circle-xmark', 'check' => 'check',
        'check-circle' => 'circle-check', 'alert-triangle' => 'triangle-exclamation', 'info' => 'circle-info',
        'clock' => 'clock', 'arrow-left' => 'arrow-left', 'arrow-right' => 'arrow-right', 'star' => 'star',
        'map-pin' => 'location-dot', 'phone' => 'phone', 'mail' => 'envelope', 'smartphone' => 'mobile-screen',
        'credit-card' => 'credit-card', 'file-text' => 'file-lines', 'wrench' => 'wrench',
        'hard-hat' => 'helmet-safety', 'flask-conical' => 'flask', 'egg' => 'egg', 'feather' => 'feather',
        'log-out' => 'right-from-bracket', 'menu' => 'bars', 'edit-2' => 'pen', 'lock' => 'lock',
    ];
    $fa = $map[$name] ?? (preg_match('/^[a-z0-9-]+$/', $name) ? $name : '');
    if ($fa === '') return '';
    $cls = $class ? ' ' . htmlspecialchars($class, ENT_QUOTES) : '';
    return '<i class="icon-svg fa-solid fa-' . $fa . $cls . '" style="font-size:' . max(10, (int)round($size * .92)) . 'px" aria-hidden="true"></i>';
}
}

/**
 * Note en étoiles (Font Awesome) — pleine, demi, vide.
 */
if (!function_exists('starIcons')) {
function starIcons(float $r, int $size = 14): string
{
    $r = max(0, min(5, $r));
    $full = (int)$r;
    $half = ($r - $full >= .5) ? 1 : 0;
    $empty = 5 - $full - $half;
    $fs = 'font-size:' . $size . 'px';
    $out = str_repeat('<i class="icon-svg star-icon star-full fa-solid fa-star" style="' . $fs . '" aria-hidden="true"></i>', $full);
    if ($half) $out .= '<i class="icon-svg star-icon star-half fa-solid fa-star-half-stroke" style="' . $fs . '" aria-hidden="true"></i>';
    $out .= str_repeat('<i class="icon-svg star-icon star-empty fa-regular fa-star" style="' . $fs . '" aria-hidden="true"></i>', $empty);
    return '<span class="stars-icons" role="img" aria-label="Note ' . number_format($r, 1) . ' sur 5">' . $out . '</span>';
}
}
