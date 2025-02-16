<?php

namespace Typecho\Widget\Helper;

/**
 * Trình trợ giúp đối tượng widget, được sử dụng để xử lý các phương thức đối tượng trống
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class EmptyClass
{
    /**
     * Tay cầm đơn
     *
     * @access private
     * @var EmptyClass
     */
    private static $instance = null;

    /**
     * Nhận xử lý đơn
     *
     * @access public
     * @return EmptyClass
     */
    public static function getInstance(): EmptyClass
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Tất cả các yêu cầu phương thức đều trả về trực tiếp
     *
     * @access public
     * @param string $name tên phương thức
     * @param array $args Danh sách tham số
     * @return void
     */
    public function __call(string $name, array $args)
    {
    }
}
