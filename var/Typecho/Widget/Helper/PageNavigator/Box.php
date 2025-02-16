<?php

namespace Typecho\Widget\Helper\PageNavigator;

use Typecho\Widget\Helper\PageNavigator;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Kiểu phân trang đóng hộp
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Box extends PageNavigator
{
    /**
     * Thanh phân trang kiểu đóng hộp đầu ra
     *
     * @access public
     * @param string $prevWord Văn bản trang trước
     * @param string $nextWord Văn bản trang sau
     * @param int $splitPage Phạm vi phân chia
     * @param string $splitWord ký tự phân chia
     * @param array $template
     * @return void
     */
    public function render(
        string $prevWord = 'PREV',
        string $nextWord = 'NEXT',
        int $splitPage = 3,
        string $splitWord = '...',
        array $template = []
    ) {
        if ($this->total < 1) {
            return;
        }

        $default = [
            'itemTag' => 'li',
            'textTag' => 'span',
            'currentClass' => 'current',
            'prevClass' => 'prev',
            'nextClass' => 'next'
        ];

        $template = array_merge($default, $template);
        extract($template);

        // sự định nghĩa item
        $itemBegin = empty($itemTag) ? '' : ('<' . $itemTag . '>');
        $itemCurrentBegin = empty($itemTag) ? '' : ('<' . $itemTag
            . (empty($currentClass) ? '' : ' class="' . $currentClass . '"') . '>');
        $itemPrevBegin = empty($itemTag) ? '' : ('<' . $itemTag
            . (empty($prevClass) ? '' : ' class="' . $prevClass . '"') . '>');
        $itemNextBegin = empty($itemTag) ? '' : ('<' . $itemTag
            . (empty($nextClass) ? '' : ' class="' . $nextClass . '"') . '>');
        $itemEnd = empty($itemTag) ? '' : ('</' . $itemTag . '>');
        $textBegin = empty($textTag) ? '' : ('<' . $textTag . '>');
        $textEnd = empty($textTag) ? '' : ('</' . $textTag . '>');
        $linkBegin = '<a href="%s">';
        $linkCurrentBegin = empty($itemTag) ? ('<a href="%s"'
            . (empty($currentClass) ? '' : ' class="' . $currentClass . '"') . '>')
            : $linkBegin;
        $linkPrevBegin = empty($itemTag) ? ('<a href="%s"'
            . (empty($prevClass) ? '' : ' class="' . $prevClass . '"') . '>')
            : $linkBegin;
        $linkNextBegin = empty($itemTag) ? ('<a href="%s"'
            . (empty($nextClass) ? '' : ' class="' . $nextClass . '"') . '>')
            : $linkBegin;
        $linkEnd = '</a>';

        $from = max(1, $this->currentPage - $splitPage);
        $to = min($this->totalPage, $this->currentPage + $splitPage);

        // Xuất trang trước
        if ($this->currentPage > 1) {
            echo $itemPrevBegin . sprintf(
                $linkPrevBegin,
                str_replace($this->pageHolder, $this->currentPage - 1, $this->pageTemplate) . $this->anchor
            )
                . $prevWord . $linkEnd . $itemEnd;
        }

        // Xuất trang đầu tiên
        if ($from > 1) {
            echo $itemBegin
                . sprintf($linkBegin, str_replace($this->pageHolder, 1, $this->pageTemplate) . $this->anchor)
                . '1' . $linkEnd . $itemEnd;

            if ($from > 2) {
                // Hình elip đầu ra
                echo $itemBegin . $textBegin . $splitWord . $textEnd . $itemEnd;
            }
        }

        // Trang trung gian đầu ra
        for ($i = $from; $i <= $to; $i++) {
            $current = ($i == $this->currentPage);

            echo ($current ? $itemCurrentBegin : $itemBegin) . sprintf(
                ($current ? $linkCurrentBegin : $linkBegin),
                str_replace($this->pageHolder, $i, $this->pageTemplate) . $this->anchor
            )
                . $i . $linkEnd . $itemEnd;
        }

        // Xuất trang cuối cùng
        if ($to < $this->totalPage) {
            if ($to < $this->totalPage - 1) {
                echo $itemBegin . $textBegin . $splitWord . $textEnd . $itemEnd;
            }

            echo $itemBegin
                . sprintf(
                    $linkBegin,
                    str_replace($this->pageHolder, $this->totalPage, $this->pageTemplate) . $this->anchor
                )
                . $this->totalPage . $linkEnd . $itemEnd;
        }

        // Xuất trang tiếp theo
        if ($this->currentPage < $this->totalPage) {
            echo $itemNextBegin . sprintf(
                $linkNextBegin,
                str_replace($this->pageHolder, $this->currentPage + 1, $this->pageTemplate) . $this->anchor
            )
                . $nextWord . $linkEnd . $itemEnd;
        }
    }
}
