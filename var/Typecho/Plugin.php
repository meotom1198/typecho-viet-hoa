<?php

namespace Typecho;

use Typecho\Plugin\Exception as PluginException;

/**
 * Lớp xử lý trình cắm
 *
 * @category typecho
 * @package Plugin
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Plugin
{
    /**
     * Tất cả các plugin được kích hoạt
     *
     * @var array
     */
    private static $plugin = [];

    /**
     * Đối tượng plugin được khởi tạo
     *
     * @var array
     */
    private static $instances;

    /**
     * biến lưu trữ tạm thời
     *
     * @var array
     */
    private static $tmp = [];

    /**
     * tay cầm độc đáo
     *
     * @var string
     */
    private $handle;

    /**
     * thành phần
     *
     * @var string
     */
    private $component;

    /**
     * Có kích hoạt tín hiệu plug-in hay không
     *
     * @var boolean
     */
    private $signal;

    /**
     * Khởi tạo plug-in
     *
     * @param string $handle trình cắm thêm
     */
    public function __construct(string $handle)
    {
        if (defined('__TYPECHO_CLASS_ALIASES__')) {
            $alias = array_search('\\' . ltrim($handle, '\\'), __TYPECHO_CLASS_ALIASES__);
            $handle = $alias ?: $handle;
        }

        $this->handle = Common::nativeClassName($handle);
    }

    /**
     * Khởi tạo plug-in
     *
     * @param array $plugins Danh sách plugin
     */
    public static function init(array $plugins)
    {
        $plugins['activated'] = array_key_exists('activated', $plugins) ? $plugins['activated'] : [];
        $plugins['handles'] = array_key_exists('handles', $plugins) ? $plugins['handles'] : [];

        /** khởi tạo biến */
        self::$plugin = $plugins;
    }

    /**
     * Nhận đối tượng plug-in đã được khởi tạo
     *
     * @param string $handle trình cắm thêm
     * @return Plugin
     */
    public static function factory(string $handle): Plugin
    {
        return self::$instances[$handle] ?? (self::$instances[$handle] = new self($handle));
    }

    /**
     * Kích hoạt plugin
     *
     * @param string $pluginName Tên plugin
     */
    public static function activate(string $pluginName)
    {
        self::$plugin['activated'][$pluginName] = self::$tmp;
        self::$tmp = [];
    }

    /**
     * Tắt plugin
     *
     * @param string $pluginName Tên plugin
     */
    public static function deactivate(string $pluginName)
    {
        /** Xóa tất cả các chức năng gọi lại có liên quan */
        if (
            isset(self::$plugin['activated'][$pluginName]['handles'])
            && is_array(self::$plugin['activated'][$pluginName]['handles'])
        ) {
            foreach (self::$plugin['activated'][$pluginName]['handles'] as $handle => $handles) {
                self::$plugin['handles'][$handle] = self::pluginHandlesDiff(
                    empty(self::$plugin['handles'][$handle]) ? [] : self::$plugin['handles'][$handle],
                    empty($handles) ? [] : $handles
                );
                if (empty(self::$plugin['handles'][$handle])) {
                    unset(self::$plugin['handles'][$handle]);
                }
            }
        }

        /** Vô hiệu hóa plugin hiện tại */
        unset(self::$plugin['activated'][$pluginName]);
    }

    /**
     * So sánh tay cầm plug-in
     *
     * @param array $pluginHandles
     * @param array $otherPluginHandles
     * @return array
     */
    private static function pluginHandlesDiff(array $pluginHandles, array $otherPluginHandles): array
    {
        foreach ($otherPluginHandles as $handle) {
            while (false !== ($index = array_search($handle, $pluginHandles))) {
                unset($pluginHandles[$index]);
            }
        }

        return $pluginHandles;
    }

    /**
     * Xuất cài đặt plugin hiện tại
     *
     * @return array
     */
    public static function export(): array
    {
        return self::$plugin;
    }

    /**
     * Lấy thông tin tiêu đề của tệp plug-in
     *
     * @param string $pluginFile Đường dẫn tệp trình cắm
     * @return array
     */
    public static function parseInfo(string $pluginFile): array
    {
        $tokens = token_get_all(file_get_contents($pluginFile));
        $isDoc = false;
        $isFunction = false;
        $isClass = false;
        $isInClass = false;
        $isInFunction = false;
        $isDefined = false;
        $current = null;

        /** thông tin ban đầu */
        $info = [
            'description' => '',
            'title' => '',
            'author' => '',
            'homepage' => '',
            'version' => '',
            'since' => '',
            'activate' => false,
            'deactivate' => false,
            'config' => false,
            'personalConfig' => false
        ];

        $map = [
            'package' => 'title',
            'author' => 'author',
            'link' => 'homepage',
            'since' => 'since',
            'version' => 'version'
        ];

        foreach ($tokens as $token) {
            /** Nhận bình luận tài liệu */
            if (!$isDoc && is_array($token) && T_DOC_COMMENT == $token[0]) {

                /** Đọc từng dòng riêng biệt */
                $described = false;
                $lines = preg_split("(\r|\n)", $token[1]);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && '*' == $line[0]) {
                        $line = trim(substr($line, 1));
                        if (!$described && !empty($line) && '@' == $line[0]) {
                            $described = true;
                        }

                        if (!$described && !empty($line)) {
                            $info['description'] .= $line . "\n";
                        } elseif ($described && !empty($line) && '@' == $line[0]) {
                            $info['description'] = trim($info['description']);
                            $line = trim(substr($line, 1));
                            $args = explode(' ', $line);
                            $key = array_shift($args);

                            if (isset($map[$key])) {
                                $info[$map[$key]] = trim(implode(' ', $args));
                            }
                        }
                    }
                }

                $isDoc = true;
            }

            if (is_array($token)) {
                switch ($token[0]) {
                    case T_FUNCTION:
                        $isFunction = true;
                        break;
                    case T_IMPLEMENTS:
                        $isClass = true;
                        break;
                    case T_WHITESPACE:
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    case T_STRING:
                        $string = strtolower($token[1]);
                        switch ($string) {
                            case 'typecho_plugin_interface':
                            case 'plugininterface':
                                $isInClass = $isClass;
                                break;
                            case 'activate':
                            case 'deactivate':
                            case 'config':
                            case 'personalconfig':
                                if ($isFunction) {
                                    $current = ('personalconfig' == $string ? 'personalConfig' : $string);
                                }
                                break;
                            default:
                                if (!empty($current) && $isInFunction && $isInClass) {
                                    $info[$current] = true;
                                }
                                break;
                        }
                        break;
                    default:
                        if (!empty($current) && $isInFunction && $isInClass) {
                            $info[$current] = true;
                        }
                        break;
                }
            } else {
                $token = strtolower($token);
                switch ($token) {
                    case '{':
                        if ($isDefined) {
                            $isInFunction = true;
                        }
                        break;
                    case '(':
                        if ($isFunction && !$isDefined) {
                            $isDefined = true;
                        }
                        break;
                    case '}':
                    case ';':
                        $isDefined = false;
                        $isFunction = false;
                        $isInFunction = false;
                        $current = null;
                        break;
                    default:
                        if (!empty($current) && $isInFunction && $isInClass) {
                            $info[$current] = true;
                        }
                        break;
                }
            }
        }

        return $info;
    }

    /**
     * Nhận đường dẫn plugin và tên lớp
     * Giá trị trả về là một mảng
     * Mục đầu tiên là đường dẫn plug-in, mục thứ hai là tên lớp
     * @param string $pluginName Tên trình cắm
     * @param string $path Thư mục trình cắm
     * @return array
     * @throws PluginException
     */
    public static function portal(string $pluginName, string $path): array
    {
        switch (true) {
            case file_exists($pluginFileName = $path . '/' . $pluginName . '/Plugin.php'):
                $className = "\\" . PLUGIN_NAMESPACE . "\\{$pluginName}\\Plugin";
                break;
            case file_exists($pluginFileName = $path . '/' . $pluginName . '.php'):
                $className = "\\" . PLUGIN_NAMESPACE . "\\" . $pluginName;
                break;
            default:
                throw new PluginException('Missing Plugin ' . $pluginName, 404);
        }

        return [$pluginFileName, $className];
    }

    /**
     * Phát hiện phụ thuộc phiên bản
     *
     * @param string|null $version Phiên bản plugin
     * @return boolean
     */
    public static function checkDependence(?string $version): bool
    {
        // Nếu không có quy tắc phát hiện, hãy trực tiếp bỏ qua nó
        if (empty($version)) {
            return true;
        }

        return version_compare(Common::VERSION, $version, '>=');
    }

    /**
     * Xác định xem plugin có tồn tại không
     *
     * @param string $pluginName Tên plugin
     * @return mixed
     */
    public static function exists(string $pluginName)
    {
        return array_key_exists($pluginName, self::$plugin['activated']);
    }

    /**
     * Kích hoạt sau cuộc gọi plug-in
     *
     * @param boolean|null $signal cò súng
     * @return Plugin
     */
    public function trigger(?bool &$signal): Plugin
    {
        $signal = false;
        $this->signal = &$signal;
        return $this;
    }

    /**
     * Đặt vị trí thành phần hiện tại thông qua chức năng ma thuật
     *
     * @param string $component thành phần hiện tại
     * @return Plugin
     */
    public function __get(string $component)
    {
        $this->component = $component;
        return $this;
    }

    /**
     * Đặt chức năng gọi lại
     *
     * @param string $component thành phần hiện tại
     * @param callable $value chức năng gọi lại
     */
    public function __set(string $component, callable $value)
    {
        $weight = 0;

        if (strpos($component, '_') > 0) {
            $parts = explode('_', $component, 2);
            [$component, $weight] = $parts;
            $weight = intval($weight) - 10;
        }

        $component = $this->handle . ':' . $component;

        if (!isset(self::$plugin['handles'][$component])) {
            self::$plugin['handles'][$component] = [];
        }

        if (!isset(self::$tmp['handles'][$component])) {
            self::$tmp['handles'][$component] = [];
        }

        foreach (self::$plugin['handles'][$component] as $key => $val) {
            $key = floatval($key);

            if ($weight > $key) {
                break;
            } elseif ($weight == $key) {
                $weight += 0.001;
            }
        }

        self::$plugin['handles'][$component][strval($weight)] = $value;
        self::$tmp['handles'][$component][] = $value;

        ksort(self::$plugin['handles'][$component], SORT_NUMERIC);
    }

    /**
     * chức năng xử lý gọi lại
     *
     * @param string $component thành phần hiện tại
     * @param array $args tham số
     * @return mixed
     */
    public function __call(string $component, array $args)
    {
        $component = $this->handle . ':' . $component;
        $last = count($args);
        $args[$last] = $last > 0 ? $args[0] : false;

        if (isset(self::$plugin['handles'][$component])) {
            $args[$last] = null;
            $this->signal = true;
            foreach (self::$plugin['handles'][$component] as $callback) {
                $args[$last] = call_user_func_array($callback, $args);
            }
        }

        return $args[$last];
    }
}
