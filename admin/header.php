<?php
if (!defined('__TYPECHO_ADMIN__')) {
    exit;
}

$header = '<link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'normalize.css', true) . '">
            <!-- start cdn fontawesome 6.7.1 ver Free -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/js/all.js" integrity="sha512-uNrBiKhFm8UOf0IXqkeojIesJ5glWJt8+epL5xwBBe1J9tcmd54f/vwQ6+g2ahXBHuayqaQcelUK7CULdWHinQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/js/all.min.js" integrity="sha512-1JkMy1LR9bTo3psH+H4SV5bO2dFylgOy+UJhMus1zF4VEFuZVu5lsi4I6iIndE4N9p01z1554ZDcvMSjMaqCBQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <!-- end cdn fontawesome 6.7.1 ver Free -->
<link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'grid.css', true) . '">
<link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'style.css', true) . '">';

/** Đăng ký một plug-in khởi tạo */
$header = \Typecho\Plugin::factory('admin/header.php')->header($header);

?><!DOCTYPE HTML>
<html lang="vi-VN">
    <head>
        <meta charset="<?php $options->charset(); ?>">
        <meta name="renderer" content="webkit">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title><?php _e('%s', $menu->title, $options->title); ?></title>
        <link rel="shortcut icon" href="https://wapvn.top/favicon-16x16.png" />
        <meta name="robots" content="noindex, nofollow">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
        <?php echo $header; ?>
        <style>body {word-wrap: normal|break-word|initial|inherit; font-family: "Josefin Sans", sans-serif; font-optical-sizing: auto; font-weight: auto; font-style: normal;}</style>
    </head>
    <body<?php if (isset($bodyClass)) {echo ' class="' . $bodyClass . '"';} ?>>
