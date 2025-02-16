<?php

namespace Typecho;

use Typecho\Router\Parser;
use Typecho\Router\Exception as RouterException;

/**
 * Lớp cơ sở thành phần Typecho
 *
 * @package Router
 */
class Router
{
    /**
     * Tên tuyến đường hiện tại
     *
     * @access public
     * @var string
     */
    public static $current;

    /**
     * Cấu hình bảng định tuyến được phân tích cú pháp
     *
     * @access private
     * @var mixed
     */
    private static $routingTable = [];

    /**
     * đường dẫn phân tích
     *
     * @access public
     *
     * @param string|null $pathInfo đường dẫn đầy đủ
     * @param mixed $parameter thông số đầu vào
     *
     * @return false|Widget
     * @throws \Exception
     */
    public static function match(?string $pathInfo, $parameter = null)
    {
        foreach (self::$routingTable as $key => $route) {
            if (preg_match($route['regx'], $pathInfo, $matches)) {
                self::$current = $key;

                try {
                    /** Tải thông số */
                    $params = null;

                    if (!empty($route['params'])) {
                        unset($matches[0]);
                        $params = array_combine($route['params'], $matches);
                    }

                    return Widget::widget($route['widget'], $parameter, $params);

                } catch (\Exception $e) {
                    if (404 == $e->getCode()) {
                        Widget::destroy($route['widget']);
                        continue;
                    }

                    throw $e;
                }
            }
        }

        return false;
    }

    /**
     * Chức năng phân phối tuyến đường
     *
     * @throws RouterException|\Exception
     */
    public static function dispatch()
    {
        /** Nhận thông tin PATH */
        $pathInfo = Request::getInstance()->getPathInfo();

        foreach (self::$routingTable as $key => $route) {
            if (preg_match($route['regx'], $pathInfo, $matches)) {
                self::$current = $key;

                try {
                    /** Tải thông số */
                    $params = null;

                    if (!empty($route['params'])) {
                        unset($matches[0]);
                        $params = array_combine($route['params'], $matches);
                    }

                    $widget = Widget::widget($route['widget'], null, $params);

                    if (isset($route['action'])) {
                        $widget->{$route['action']}();
                    }

                    return;

                } catch (\Exception $e) {
                    if (404 == $e->getCode()) {
                        Widget::destroy($route['widget']);
                        continue;
                    }

                    throw $e;
                }
            }
        }

        /** Đang tải hỗ trợ ngoại lệ tuyến đường */
        throw new RouterException("Path '{$pathInfo}' not found", 404);
    }

    /**
     * Chức năng chống phân tích tuyến đường
     *
     * @param string $name Tên bảng cấu hình định tuyến
     * @param array|null $value Giá trị điền tuyến đường
     * @param string|null $prefix Tiền tố của đường dẫn tổng hợp cuối cùng
     *
     * @return string
     */
    public static function url(string $name, ?array $value = null, ?string $prefix = null): string
    {
        $route = self::$routingTable[$name];

        // Hoán đổi giá trị khóa mảng
        $pattern = [];
        foreach ($route['params'] as $row) {
            $pattern[$row] = $value[$row] ?? '{' . $row . '}';
        }

        return Common::url(vsprintf($route['format'], $pattern), $prefix);
    }

    /**
     * Đặt cấu hình mặc định của bộ định tuyến
     *
     * @access public
     *
     * @param mixed $routes Thông tin cấu hình
     *
     * @return void
     */
    public static function setRoutes($routes)
    {
        if (isset($routes[0])) {
            self::$routingTable = $routes[0];
        } else {
            /** Phân tích cấu hình định tuyến */
            $parser = new Parser($routes);
            self::$routingTable = $parser->parse();
        }
    }

    /**
     * Nhận thông tin định tuyến
     *
     * @param string $routeName 路由名称
     *
     * @static
     * @access public
     * @return mixed
     */
    public static function get(string $routeName)
    {
        return self::$routingTable[$routeName] ?? null;
    }
}
