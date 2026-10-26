<?php
/**
 * Icônes du panel admin — Font Awesome (Solid) uniquement.
 * Usage : <?=icon('trash-2')?>  ou  <?=icon('bell', 20, 'nav-badge-icon')?>
 */
function icon(string $name, int $size = 18, string $class = ''): string
{
    static $map = [
        'dashboard' => 'table-columns', 'shopping-cart' => 'cart-shopping', 'package' => 'box',
        'external-link' => 'arrow-up-right-from-square', 'log-out' => 'right-from-bracket', 'menu' => 'bars',
        'x' => 'xmark', 'x-circle' => 'circle-xmark', 'check' => 'check', 'check-circle' => 'circle-check',
        'bell' => 'bell', 'alert-triangle' => 'triangle-exclamation', 'plus' => 'plus', 'plus-circle' => 'circle-plus',
        'arrow-left' => 'arrow-left', 'arrow-right' => 'arrow-right', 'arrow-up' => 'arrow-up', 'save' => 'floppy-disk',
        'star' => 'star', 'clock' => 'clock', 'truck' => 'truck', 'dollar-sign' => 'coins', 'inbox' => 'inbox',
        'lock' => 'lock', 'eye' => 'eye', 'edit-2' => 'pen', 'trash-2' => 'trash-can', 'search' => 'magnifying-glass',
        'file-text' => 'file-lines', 'download' => 'download', 'shopping-bag' => 'bag-shopping', 'user' => 'user',
        'phone' => 'phone', 'message-circle' => 'comment', 'mail' => 'envelope', 'note' => 'note-sticky',
        'settings' => 'gear', 'chevron-right' => 'chevron-right', 'circle-dot' => 'circle-dot',
        'toggle-on' => 'toggle-on', 'toggle-off' => 'toggle-off', 'refresh-cw' => 'rotate', 'receipt' => 'receipt',
    ];
    $fa = $map[$name] ?? (preg_match('/^[a-z0-9-]+$/', $name) ? $name : '');
    if ($fa === '') return '';
    $cls = $class ? ' ' . htmlspecialchars($class, ENT_QUOTES) : '';
    return '<i class="icon-svg fa-solid fa-' . $fa . $cls . '" style="font-size:' . max(10, (int)round($size * .92)) . 'px" aria-hidden="true"></i>';
}
