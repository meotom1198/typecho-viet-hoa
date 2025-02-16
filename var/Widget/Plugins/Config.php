<?php

namespace Widget\Plugins;

use Typecho\Plugin;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Widget\Base\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần cấu hình trình cắm
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Config extends Options
{
    /**
     * Nhận thông tin plug-in
     *
     * @var array
     */
    public $info;

    /**
     * Đường dẫn tệp trình cắm
     *
     * @var string
     */
    private $pluginFileName;

    /**
     * Lớp trình cắm
     *
     * @var string
     */
    private $className;

    /**
     * Hành động ràng buộc
     *
     * @throws Plugin\Exception
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        $this->user->pass('administrator');
        $config = $this->request->filter('slug')->config;
        if (empty($config)) {
            throw new Exception(_t('Plugin không tồn tại!'), 404);
        }

        /** Nhận mục nhập plugin */
        [$this->pluginFileName, $this->className] = Plugin::portal($config, $this->options->pluginDir);
        $this->info = Plugin::parseInfo($this->pluginFileName);
    }

    /**
     * Nhận tiêu đề thực đơn
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('Cài đặt Plugin: %s', $this->info['title']);
    }

    /**
     * Định cấu hình plugin
     *
     * @return Form
     * @throws Exception|Plugin\Exception
     */
    public function config()
    {
        /** Nhận tên plugin */
        $pluginName = $this->request->filter('slug')->config;

        /** Nhận các plugin được kích hoạt */
        $plugins = Plugin::export();
        $activatedPlugins = $plugins['activated'];

        /** Xác định xem việc khởi tạo có thành công hay không */
        if (!$this->info['config'] || !isset($activatedPlugins[$pluginName])) {
            throw new Exception(_t('Không thể định cấu hình plugin!'), 500);
        }

        /** Tải plugin */
        require_once $this->pluginFileName;
        $form = new Form($this->security->getIndex('/action/plugins-edit?config=' . $pluginName), Form::POST_METHOD);
        call_user_func([$this->className, 'config'], $form);

        $options = $this->options->plugin($pluginName);

        if (!empty($options)) {
            foreach ($options as $key => $val) {
                $form->getInput($key)->value($val);
            }
        }

        $submit = new Form\Element\Submit(null, null, _t('Lưu cài đặt'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        return $form;
    }
}
