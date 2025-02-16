<?php
/**
 * Theme Mặc Định
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: index.php 1153 2009-07-02 10:53:22Z magike.net $
 */

/** Hỗ trợ cấu hình tải */
if (!defined('__TYPECHO_ROOT_DIR__') && !@include_once 'config.inc.php') {
    file_exists('./install.php') ? header('Location: install.php') : print('Missing Config File');
    exit;
}

/** Khởi tạo thành phần */
\Widget\Init::alloc();

/** Đăng ký một plugin khởi tạo */
\Typecho\Plugin::factory('index.php')->begin();

/** Bắt đầu phân phối định tuyến */
\Typecho\Router::dispatch();

/** Đăng ký một plugin kết thúc */
\Typecho\Plugin::factory('index.php')->end();
