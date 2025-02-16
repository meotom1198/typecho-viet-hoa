<?php

namespace Widget;

use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần đăng xuất
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Logout extends Users implements ActionInterface
{
    /**
     * hàm khởi tạo
     *
     * @access public
     * @return void
     */
    public function action()
    {
        // protect
        $this->security->protect();

        $this->user->logout();
        self::pluginHandle()->logout();
        @session_destroy();
        $this->response->goBack(null, $this->options->index);
    }
}
