<?php

namespace TypechoPlugin\HelloWorld;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Hello World
 *
 * @package HelloWorld
 * @author qining
 * @version 1.0.0
 * @link http://typecho.org
 */
class Plugin implements PluginInterface
{
    /**
     * Kích hoạt phương thức plug-in Nếu kích hoạt không thành công, một ngoại lệ sẽ được đưa ra trực tiếp.
     */
    public static function activate()
    {
        \Typecho\Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::render';
    }

    /**
     * Vô hiệu hóa phương thức plug-in Nếu việc vô hiệu hóa không thành công, một ngoại lệ sẽ được đưa ra trực tiếp.
     */
    public static function deactivate()
    {
    }

    /**
     * Nhận bảng cấu hình plug-in
     *
     * @param Form $form Bảng cấu hình
     */
    public static function config(Form $form)
    {
        /** Tên danh mục */
        $name = new Text('word', null, 'Hello World', _t('Nói điều gì đó'));
        $form->addInput($name);
    }

    /**
     * Bảng cấu hình cho người dùng cá nhân
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * Phương pháp triển khai plug-in
     *
     * @access public
     * @return void
     */
    public static function render()
    {
        echo '<span class="message success">'
            . htmlspecialchars(Options::alloc()->plugin('HelloWorld')->word)
            . '</span>';
    }
}
