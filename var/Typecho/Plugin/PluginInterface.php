<?php

namespace Typecho\Plugin;

use Typecho\Widget\Helper\Form;

/**
 * Giao diện trình cắm
 *
 * @package Plugin
 * @abstract
 */
interface PluginInterface
{
    /**
     * Kích hoạt phương thức plug-in. Nếu kích hoạt không thành công, một ngoại lệ sẽ được đưa ra trực tiếp.
     *
     * @static
     * @access public
     * @return void
     */
    public static function activate();

    /**
     * Vô hiệu hóa phương thức plug-in Nếu việc vô hiệu hóa không thành công, một ngoại lệ sẽ được đưa ra trực tiếp.
     *
     * @static
     * @access public
     * @return void
     */
    public static function deactivate();

    /**
     * Nhận bảng cấu hình plug-in
     *
     * @param Form $form Bảng cấu hình
     */
    public static function config(Form $form);

    /**
     * Bảng cấu hình cho người dùng cá nhân
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form);
}
