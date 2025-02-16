<?php
if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

define('__TYPECHO_ADMIN__', true);

/** Tải tập tin cấu hình */
if (!defined('__TYPECHO_ROOT_DIR__') && !@include_once __DIR__ . '/../config.inc.php') {
    file_exists(__DIR__ . '/../install.php') ? header('Location: ../install.php') : print('Missing Config File');
    exit;
}

/** Khởi tạo thành phần */
\Widget\Init::alloc();

/** Đăng ký một plug-in khởi tạo */
\Typecho\Plugin::factory('admin/common.php')->begin();

\Widget\Options::alloc()->to($options);
\Widget\User::alloc()->to($user);
\Widget\Security::alloc()->to($security);
\Widget\Menu::alloc()->to($menu);

/** Khởi tạo bối cảnh */
$request = $options->request;
$response = $options->response;

/** Kiểm tra xem đây có phải là lần đăng nhập đầu tiên không */
$currentMenu = $menu->getCurrentMenu();

if (!empty($currentMenu)) {
    $params = parse_url($currentMenu[2]);
    $adminFile = basename($params['path']);

    if (!$user->logged && !\Typecho\Cookie::get('__typecho_first_run')) {
        if ('welcome.php' != $adminFile) {
            $response->redirect(\Typecho\Common::url('welcome.php', $options->adminUrl));
        } else {
            \Typecho\Cookie::set('__typecho_first_run', 1);
        }
    } elseif ($user->pass('administrator', true)) {
        /** Kiểm tra xem phiên bản đã được nâng cấp chưa */
        $mustUpgrade = version_compare(\Typecho\Common::VERSION, $options->version, '>');

        if ($mustUpgrade && 'upgrade.php' != $adminFile && 'backup.php' != $adminFile) {
            $response->redirect(\Typecho\Common::url('upgrade.php', $options->adminUrl));
        } elseif (!$mustUpgrade && 'upgrade.php' == $adminFile) {
            $response->redirect($options->adminUrl);
        } elseif (!$mustUpgrade && 'welcome.php' == $adminFile && $user->logged) {
            $response->redirect($options->adminUrl);
        }
    }
}
