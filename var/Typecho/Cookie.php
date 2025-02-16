<?php

namespace Typecho;

/**
 * cookie ủng hộ
 *
 * @author qining
 * @category typecho
 * @package Cookie
 */
class Cookie
{
    /**
     * tiền tố
     *
     * @var string
     * @access private
     */
    private static $prefix = '';

    /**
     * con đường
     *
     * @var string
     * @access private
     */
    private static $path = '/';

    /**
     * @var string
     * @access private
     */
    private static $domain = '';

    /**
     * @var bool
     * @access private
     */
    private static $secure = false;

    /**
     * @var bool
     * @access private
     */
    private static $httponly = false;

    /**
     * Nhận tiền tố
     *
     * @access public
     * @return string
     */
    public static function getPrefix(): string
    {
        return self::$prefix;
    }

    /**
     * Đặt tiền tố
     *
     * @param string $url
     *
     * @access public
     * @return void
     */
    public static function setPrefix(string $url)
    {
        self::$prefix = md5($url);
        $parsed = parse_url($url);

        self::$domain = $parsed['host'];
        /** Buộc gạch chéo sau đường dẫn */
        self::$path = empty($parsed['path']) ? '/' : Common::url(null, $parsed['path']);
    }

    /**
     * Nhận thư mục
     *
     * @access public
     * @return string
     */
    public static function getPath(): string
    {
        return self::$path;
    }

    /**
     * @access public
     * @return string
     */
    public static function getDomain(): string
    {
        return self::$domain;
    }

    /**
     * @access public
     * @return bool
     */
    public static function getSecure(): bool
    {
        return self::$secure ?: false;
    }

    /**
     * Đặt tùy chọn bổ sung
     *
     * @param array $options
     * @return void
     */
    public static function setOptions(array $options)
    {
        self::$domain = $options['domain'] ?: self::$domain;
        self::$secure = $options['secure'] ? (bool) $options['secure'] : false;
        self::$httponly = $options['httponly'] ? (bool) $options['httponly'] : false;
    }

    /**
     * Nhận giá trị COOKIE được chỉ định
     *
     * @param string $key Thông số được chỉ định
     * @param string|null $default Thông số mặc định
     * @return mixed
     */
    public static function get(string $key, ?string $default = null)
    {
        $key = self::$prefix . $key;
        $value = $_COOKIE[$key] ?? $default;
        return is_array($value) ? $default : $value;
    }

    /**
     * Đặt giá trị COOKIE được chỉ định
     *
     * @param string $key Thông số được chỉ định
     * @param mixed $value đặt giá trị
     * @param integer $expire Thời gian hết hạn, mặc định là 0, nghĩa là kết thúc theo thời gian của phiên
     */
    public static function set(string $key, $value, int $expire = 0)
    {
        $key = self::$prefix . $key;
        $_COOKIE[$key] = $value;
        Response::getInstance()->setCookie($key, $value, $expire, self::$path, self::$domain, self::$secure, self::$httponly);
    }

    /**
     * Xóa giá trị COOKIE đã chỉ định
     *
     * @param string $key Thông số được chỉ định
     */
    public static function delete(string $key)
    {
        $key = self::$prefix . $key;
        if (!isset($_COOKIE[$key])) {
            return;
        }

        Response::getInstance()->setCookie($key, '', -1, self::$path, self::$domain, self::$secure, self::$httponly);
        unset($_COOKIE[$key]);
    }
}

