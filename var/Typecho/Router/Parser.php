<?php

namespace Typecho\Router;

/**
 * bộ phân tích cú pháp bộ định tuyến
 *
 * @category typecho
 * @package Router
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Parser
{
    /**
     * Bảng so khớp mặc định
     *
     * @access private
     * @var array
     */
    private $defaultRegex;

    /**
     * Bảng ánh xạ bộ định tuyến
     *
     * @access private
     * @var array
     */
    private $routingTable;

    /**
     * Bảng thông số
     *
     * @access private
     * @var array
     */
    private $params;

    /**
     * Đặt bảng định tuyến
     *
     * @access public
     * @param array $routingTable Bảng ánh xạ bộ định tuyến
     */
    public function __construct(array $routingTable)
    {
        $this->routingTable = $routingTable;

        $this->defaultRegex = [
            'string' => '(.%s)',
            'char' => '([^/]%s)',
            'digital' => '([0-9]%s)',
            'alpha' => '([_0-9a-zA-Z-]%s)',
            'alphaslash' => '([_0-9a-zA-Z-/]%s)',
            'split' => '((?:[^/]+/)%s[^/]+)',
        ];
    }

    /**
     * Khớp một phần và thay thế các chuỗi thông thường
     *
     * @access public
     * @param array $matches phần phù hợp
     * @return string
     */
    public function match(array $matches): string
    {
        $params = explode(' ', $matches[1]);
        $paramsNum = count($params);
        $this->params[] = $params[0];

        if (1 == $paramsNum) {
            return sprintf($this->defaultRegex['char'], '+');
        } elseif (2 == $paramsNum) {
            return sprintf($this->defaultRegex[$params[1]], '+');
        } elseif (3 == $paramsNum) {
            return sprintf($this->defaultRegex[$params[1]], $params[2] > 0 ? '{' . $params[2] . '}' : '*');
        } elseif (4 == $paramsNum) {
            return sprintf($this->defaultRegex[$params[1]], '{' . $params[2] . ',' . $params[3] . '}');
        }

        return $matches[0];
    }

    /**
     * Phân tích bảng định tuyến
     *
     * @access public
     * @return array
     */
    public function parse(): array
    {
        $result = [];

        foreach ($this->routingTable as $key => $route) {
            $this->params = [];
            $route['regx'] = preg_replace_callback(
                "/%([^%]+)%/",
                [$this, 'match'],
                preg_quote(str_replace(['[', ']', ':'], ['%', '%', ' '], $route['url']))
            );

            /** Xử lý dấu gạch chéo */
            $route['regx'] = rtrim($route['regx'], '/');
            $route['regx'] = '|^' . $route['regx'] . '[/]?$|';

            $route['format'] = preg_replace("/\[([^\]]+)\]/", "%s", $route['url']);
            $route['params'] = $this->params;

            $result[$key] = $route;
        }

        return $result;
    }
}
