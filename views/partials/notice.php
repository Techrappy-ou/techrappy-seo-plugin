<?php
// views/partials/notice.php
// Rôle : afficher un message de notice admin (succès, erreur, avertissement).
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Variables attendues : $notice_type ('success'|'error'|'warning'|'info'), $notice_message (string).
$notice_type    = $notice_type    ?? 'info';
$notice_message = $notice_message ?? '';

if ( empty( $notice_message ) ) {
    return;
}
?>
<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
    <p><?php echo esc_html( $notice_message ); ?></p>
</div>
