<?php

namespace Widget\Themes;

use Typecho\Widget;
use Widget\Base;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần danh sách tệp kiểu
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Files extends Base
{
    /**
     * phong cách hiện tại
     *
     * @access private
     * @var string
     */
    private $currentTheme;

    /**
     * tập tin hiện tại
     *
     * @access private
     * @var string
     */
    private $currentFile;

    /**
     * Thực thi chức năng
     *
     * @throws Widget\Exception
     */
    public function execute()
    {
        /** Quyền quản trị viên */
        $this->user->pass('administrator');
        $this->currentTheme = $this->request->filter('slug')->get('theme', Options::alloc()->theme);

        if (
            preg_match("/^([_0-9a-z-\.\ ])+$/i", $this->currentTheme)
            && is_dir($dir = Options::alloc()->themeFile($this->currentTheme))
            && (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__)
        ) {
            $files = array_filter(glob($dir . '/*'), function ($path) {
                return preg_match("/\.(php|js|css|vbs)$/i", $path);
            });

            $this->currentFile = $this->request->get('file', 'index.php');

            if (
                preg_match("/^([_0-9a-z-\.\ ])+$/i", $this->currentFile)
                && file_exists($dir . '/' . $this->currentFile)
            ) {
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        $file = basename($file);
                        $this->push([
                            'file'    => $file,
                            'theme'   => $this->currentTheme,
                            'current' => ($file == $this->currentFile)
                        ]);
                    }
                }

                return;
            }
        }

        throw new Widget\Exception('Kiểu tập tin không tồn tại!', 404);
    }

    /**
     * Xác định xem bạn có quyền viết hay không
     *
     * @return bool
     */
    public static function isWriteable(): bool
    {
        return (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__)
            && !Options::alloc()->missingTheme;
    }

    /**
     * Nhận tiêu đề thực đơn
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('Chỉnh sửa tập tin %s', $this->currentFile);
    }

    /**
     * Lấy nội dung tập tin
     *
     * @return string
     */
    public function currentContent(): string
    {
        return htmlspecialchars(file_get_contents(Options::alloc()
            ->themeFile($this->currentTheme, $this->currentFile)));
    }

    /**
     * Nhận xem tập tin có thể đọc được hay không
     *
     * @return bool
     */
    public function currentIsWriteable(): bool
    {
        return is_writeable(Options::alloc()
                ->themeFile($this->currentTheme, $this->currentFile))
            && self::isWriteable();
    }

    /**
     * Nhận tập tin hiện tại
     *
     * @return string
     */
    public function currentFile(): string
    {
        return $this->currentFile;
    }

    /**
     * Lấy phong cách hiện tại
     *
     * @return string
     */
    public function currentTheme(): string
    {
        return $this->currentTheme;
    }
}
