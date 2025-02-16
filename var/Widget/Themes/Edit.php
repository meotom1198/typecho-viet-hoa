<?php

namespace Widget\Themes;

use Typecho\Common;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Chỉnh sửa thành phần kiểu
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
     * Thay đổi diện mạo
     *
     * @param string $theme tên ngoại hình
     * @throws Exception
     * @throws \Typecho\Db\Exception
     */
    public function changeTheme(string $theme)
    {
        $theme = trim($theme, './');
        if (is_dir($this->options->themeFile($theme))) {
            /** Xóa thông tin cài đặt giao diện ban đầu */
            $oldTheme = $this->options->missingTheme ?: $this->options->theme;
            $this->delete($this->db->sql()->where('name = ?', 'theme:' . $oldTheme));

            $this->update(['value' => $theme], $this->db->sql()->where('name = ?', 'theme'));

            /** Hủy liên kết trang chủ */
            if (0 === strpos($this->options->frontPage, 'file:')) {
                $this->update(['value' => 'recent'], $this->db->sql()->where('name = ?', 'frontPage'));
            }

            $this->options->themeUrl = $this->options->themeUrl(null, $theme);

            $configFile = $this->options->themeFile($theme, 'functions.php');

            if (file_exists($configFile)) {
                require_once $configFile;

                if (function_exists('themeConfig')) {
                    $form = new Form();
                    themeConfig($form);
                    $options = $form->getValues();

                    if ($options && !$this->configHandle($options, true)) {
                        $this->insert([
                            'name'  => 'theme:' . $theme,
                            'value' => serialize($options),
                            'user'  => 0
                        ]);
                    }
                }
            }

            Notice::alloc()->highlight('theme-' . $theme);
            Notice::alloc()->set(_t("Giao diện đã thay đổi!"), 'success');
            $this->response->goBack();
        } else {
            throw new Exception(_t('Giao diện bạn chọn không tồn tại!'));
        }
    }

    /**
     * Sử dụng các chức năng riêng để xử lý thông tin cấu hình
     *
     * @param array $settings giá trị cấu hình
     * @param boolean $isInit Có phải là khởi tạo?
     * @return boolean
     */
    public function configHandle(array $settings, bool $isInit): bool
    {
        if (function_exists('themeConfigHandle')) {
            themeConfigHandle($settings, $isInit);
            return true;
        }

        return false;
    }

    /**
     * Chỉnh sửa tập tin xuất hiện
     *
     * @param string $theme tên ngoại hình
     * @param string $file tên tập tin
     * @throws Exception
     */
    public function editThemeFile($theme, $file)
    {
        $path = $this->options->themeFile($theme, $file);

        if (
            file_exists($path) && is_writeable($path)
            && (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__)
        ) {
            $handle = fopen($path, 'wb');
            if ($handle && fwrite($handle, $this->request->content)) {
                fclose($handle);
                Notice::alloc()->set(_t("Những thay đổi đối với tập tin %s đã được lưu!", $file), 'success');
            } else {
                Notice::alloc()->set(_t("Không thể sửa tệp %s", $file), 'error');
            }
            $this->response->goBack();
        } else {
            throw new Exception(_t('Tập tin bạn chỉnh sửa không tồn tại!'));
        }
    }

    /**
     * Cấu hình giao diện
     *
     * @param string $theme Tên ngoại hình
     * @throws \Typecho\Db\Exception
     */
    public function config(string $theme)
    {
        // Chức năng hiển thị đã được tải
        $form = Config::alloc()->config();

        /** Mẫu xác nhận */
        if (!Config::isExists() || $form->validate()) {
            $this->response->goBack();
        }

        $settings = $form->getAllRequest();

        if (!$this->configHandle($settings, false)) {
            if ($this->options->__get('theme:' . $theme)) {
                $this->update(
                    ['value' => serialize($settings)],
                    $this->db->sql()->where('name = ?', 'theme:' . $theme)
                );
            } else {
                $this->insert([
                    'name'  => 'theme:' . $theme,
                    'value' => serialize($settings),
                    'user'  => 0
                ]);
            }
        }

        /** Đặc điểm nổi bật */
        Notice::alloc()->highlight('theme-' . $theme);

        /** 提示信息 */
        Notice::alloc()->set(_t("Đã lưu cài đặt giao diện!"), 'success');

        /** Tới trang gốc */
        $this->response->redirect(Common::url('options-theme.php', $this->options->adminUrl));
    }

    /**
     * Hành động ràng buộc
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function action()
    {
        /** Yêu cầu quyền quản trị viên */
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->is('change'))->changeTheme($this->request->filter('slug')->change);
        $this->on($this->request->is('edit&theme'))
            ->editThemeFile($this->request->filter('slug')->theme, $this->request->edit);
        $this->on($this->request->is('config'))->config($this->options->theme);
        $this->response->redirect($this->options->adminUrl);
    }
}
