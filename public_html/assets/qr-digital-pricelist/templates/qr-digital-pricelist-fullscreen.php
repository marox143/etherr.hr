<?php
/**
 * Fullscreen shell for QR Digital Pricelist output.
 *
 * Template Name: QR Pricelist – Fullscreen
 * @package QR_Digital_Pricelist
 */

if (!defined('ABSPATH')) {
    exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            background: transparent;
        }

        body.qr-pricelist-fullscreen {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        .qr-pricelist-fullscreen__main {
            flex: 1;
            display: flex;
            align-items: stretch;
            justify-content: center;
        }
    </style>
</head>
<body <?php body_class('qr-pricelist-fullscreen'); ?>>
    <main class="qr-pricelist-fullscreen__main">
        <?php echo do_shortcode('[qr_digital_pricelist]'); ?>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
