<?php

namespace Widget\Plugins;

use Typecho\Common;
use Typecho\Db;
use Typecho\Plugin;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần quản lý plug-in
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Options implements ActionInterface
{
    /**
     * @var bool
     */
    private $configNoticed = false;

    /**
     * Kích hoạt plugin
     *
     * @param $pluginName
     * @throws Exception|Db\Exception|Plugin\Exception
     */
    public function activate($pluginName)
    {
        /** Nhận mục nhập plugin */
        [$pluginFileName, $className] = Plugin::portal($pluginName, $this->options->pluginDir);
        $info = Plugin::parseInfo($pluginFileName);

        /** Kiểm tra thông tin phụ thuộc */
        if (Plugin::checkDependence($info['since'])) {

            /** Nhận các plugin được kích hoạt */
            $plugins = Plugin::export();
            $activatedPlugins = $plugins['activated'];

            /** Tải plugin */
            require_once $pluginFileName;

            /** Xác định xem việc khởi tạo có thành công hay không */
            if (
                isset($activatedPlugins[$pluginName]) || !class_exists($className)
                || !method_exists($className, 'activate')
            ) {
                throw new Exception(_t('Không thể kích hoạt plugin!'), 500);
            }

            try {
                $result = call_user_func([$className, 'activate']);
                Plugin::activate($pluginName);
                $this->update(
                    ['value' => serialize(Plugin::export())],
                    $this->db->sql()->where('name = ?', 'plugins')
                );
            } catch (Plugin\Exception $e) {
                /** Ngoại lệ chặn */
                Notice::alloc()->set($e->getMessage(), 'error');
                $this->response->goBack();
            }

            $form = new Form();
            call_user_func([$className, 'config'], $form);

            $personalForm = new Form();
            call_user_func([$className, 'personalConfig'], $personalForm);

            $options = $form->getValues();
            $personalOptions = $personalForm->getValues();

            if ($options && !$this->configHandle($pluginName, $options, true)) {
                self::configPlugin($pluginName, $options);
            }

            if ($personalOptions && !$this->personalConfigHandle($className, $personalOptions)) {
                self::configPlugin($pluginName, $personalOptions, true);
            }
        } else {
            $result = _t('<a href="%s">%s</a> không hoạt động đúng với phiên bản Typecho này', $info['homepage'], $info['title']);
        }

        /** Đặt điểm nổi bật */
        Notice::alloc()->highlight('plugin-' . $pluginName);

        if (isset($result) && is_string($result)) {
            Notice::alloc()->set($result, 'notice');
        } else {
            Notice::alloc()->set(_t('Plugin đã được kích hoạt!'), 'success');
        }
        $this->response->goBack();
    }

    /**
     * Sử dụng các chức năng riêng để xử lý thông tin cấu hình
     *
     * @access public
     * @param string $pluginName Tên plugin
     * @param array $settings giá trị cấu hình
     * @param boolean $isInit Có phải là khởi tạo?
     * @return boolean
     * @throws Plugin\Exception
     */
    public function configHandle(string $pluginName, array $settings, bool $isInit): bool
    {
        /** Nhận mục nhập plugin */
        [$pluginFileName, $className] = Plugin::portal($pluginName, $this->options->pluginDir);

        if (!$isInit && method_exists($className, 'configCheck')) {
            $result = call_user_func([$className, 'configCheck'], $settings);

            if (!empty($result) && is_string($result)) {
                Notice::alloc()->set($result, 'notice');
                $this->configNoticed = true;
            }
        }

        if (method_exists($className, 'configHandle')) {
            call_user_func([$className, 'configHandle'], $settings, $isInit);
            return true;
        }

        return false;
    }

    /**
     * Định cấu hình các biến plugin theo cách thủ công
     *
     * @param string $pluginName Tên plugin
     * @param array $settings cặp giá trị khóa biến
     * @param bool $isPersonal Cho dù đó là một biến riêng tư
     * @throws Db\Exception
     */
    public static function configPlugin(string $pluginName, array $settings, bool $isPersonal = false)
    {
        $db = Db::get();
        $pluginName = ($isPersonal ? '_' : '') . 'plugin:' . $pluginName;

        $select = $db->select()->from('table.options')
            ->where('name = ?', $pluginName);

        $options = $db->fetchAll($select);

        if (empty($settings)) {
            if (!empty($options)) {
                $db->query($db->delete('table.options')->where('name = ?', $pluginName));
            }
        } else {
            if (empty($options)) {
                $db->query($db->insert('table.options')
                    ->rows([
                        'name'  => $pluginName,
                        'value' => serialize($settings),
                        'user'  => 0
                    ]));
            } else {
                foreach ($options as $option) {
                    $value = unserialize($option['value']);
                    $value = array_merge($value, $settings);

                    $db->query($db->update('table.options')
                        ->rows(['value' => serialize($value)])
                        ->where('name = ?', $pluginName)
                        ->where('user = ?', $option['user']));
                }
            }
        }
    }

    /**
     * Sử dụng các chức năng của riêng bạn để xử lý thông tin cấu hình tùy chỉnh
     *
     * @param string $className Tên lớp
     * @param array $settings giá trị cấu hình
     * @return boolean
     */
    public function personalConfigHandle(string $className, array $settings): bool
    {
        if (method_exists($className, 'personalConfigHandle')) {
            call_user_func([$className, 'personalConfigHandle'], $settings, true);
            return true;
        }

        return false;
    }

    /**
     * Tắt plugin
     *
     * @param string $pluginName
     * @throws Db\Exception
     * @throws Exception
     * @throws Plugin\Exception
     */
    public function deactivate(string $pluginName)
    {
        /** Nhận các plugin được kích hoạt */
        $plugins = Plugin::export();
        $activatedPlugins = $plugins['activated'];
        $pluginFileExist = true;

        try {
            /** Nhận mục nhập plugin */
            [$pluginFileName, $className] = Plugin::portal($pluginName, $this->options->pluginDir);
        } catch (Plugin\Exception $e) {
            $pluginFileExist = false;

            if (!isset($activatedPlugins[$pluginName])) {
                throw $e;
            }
        }

        /** Xác định xem việc khởi tạo có thành công hay không */
        if (!isset($activatedPlugins[$pluginName])) {
            throw new Exception(_t('Không thể tắt plugin!'), 500);
        }

        if ($pluginFileExist) {

            /** Tải plugin */
            require_once $pluginFileName;

            /** Xác định xem việc khởi tạo có thành công hay không */
            if (
                !isset($activatedPlugins[$pluginName]) || !class_exists($className)
                || !method_exists($className, 'deactivate')
            ) {
                throw new Exception(_t('Không thể tắt plugin!'), 500);
            }

            try {
                $result = call_user_func([$className, 'deactivate']);
            } catch (Plugin\Exception $e) {
                /** Ngoại lệ chặn */
                Notice::alloc()->set($e->getMessage(), 'error');
                $this->response->goBack();
            }

            /** Đặt điểm nổi bật */
            Notice::alloc()->highlight('plugin-' . $pluginName);
        }

        Plugin::deactivate($pluginName);
        $this->update(['value' => serialize(Plugin::export())], $this->db->sql()->where('name = ?', 'plugins'));

        $this->delete($this->db->sql()->where('name = ?', 'plugin:' . $pluginName));
        $this->delete($this->db->sql()->where('name = ?', '_plugin:' . $pluginName));

        if (isset($result) && is_string($result)) {
            Notice::alloc()->set($result, 'notice');
        } else {
            Notice::alloc()->set(_t('Plugin đã bị tắt!'), 'success');
        }
        $this->response->goBack();
    }

    /**
     * Định cấu hình plugin
     *
     * @param string $pluginName
     * @throws Db\Exception
     * @throws Exception
     * @throws Plugin\Exception
     */
    public function config(string $pluginName)
    {
        $form = Config::alloc()->config();

        /** Mẫu xác nhận */
        if ($form->validate()) {
            $this->response->goBack();
        }

        $settings = $form->getAllRequest();

        if (!$this->configHandle($pluginName, $settings, false)) {
            self::configPlugin($pluginName, $settings);
        }

        /** Đặt điểm nổi bật */
        Notice::alloc()->highlight('plugin-' . $pluginName);

        if (!$this->configNoticed) {
            /** Tin nhắn nhắc nhở */
            Notice::alloc()->set(_t("Đã lưu cài đặt plugin!"), 'success');
        }

        /** Tới trang gốc */
        $this->response->redirect(Common::url('plugins.php', $this->options->adminUrl));
    }

    /**
     * Hành động ràng buộc
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->is('activate'))->activate($this->request->filter('slug')->activate);
        $this->on($this->request->is('deactivate'))->deactivate($this->request->filter('slug')->deactivate);
        $this->on($this->request->is('config'))->config($this->request->filter('slug')->config);
        $this->response->redirect($this->options->adminUrl);
    }
}
