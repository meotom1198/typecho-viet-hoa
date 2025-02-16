<?php

namespace Widget;

use Typecho\Cookie;
use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần hộp nhắc nhở
 *
 * @package Widget
 */
class Notice extends Widget
{
    /**
     * Làm nổi bật mẹo
     *
     * @var string
     */
    public $highlight;

    /**
     * Làm nổi bật các yếu tố liên quan
     *
     * @param string $theId Id của phần tử cần làm nổi bật
     */
    public function highlight(string $theId)
    {
        $this->highlight = $theId;
        Cookie::set(
            '__typecho_notice_highlight',
            $theId
        );
    }

    /**
     * Lấy id được đánh dấu
     *
     * @return integer
     */
    public function getHighlightId(): int
    {
        return preg_match("/[0-9]+/", $this->highlight, $matches) ? $matches[0] : 0;
    }

    /**
     * Đặt giá trị cho mỗi hàng của ngăn xếp
     *
     * @param string|array $value Giá trị khóa tương ứng với giá trị
     * @param string|null $type loại lời nhắc
     * @param string $typeFix Tương thích với các plugin cũ
     */
    public function set($value, ?string $type = 'notice', string $typeFix = 'notice')
    {
        $notice = is_array($value) ? array_values($value) : [$value];
        if (empty($type) && $typeFix) {
            $type = $typeFix;
        }

        Cookie::set(
            '__typecho_notice',
            json_encode($notice)
        );
        Cookie::set(
            '__typecho_notice_type',
            $type
        );
    }
}
