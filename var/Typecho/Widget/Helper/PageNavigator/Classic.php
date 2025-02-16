<?php

namespace Typecho\Widget\Helper\PageNavigator;

use Typecho\Widget\Helper\PageNavigator;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Kiểu phân trang cổ điển
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Classic extends PageNavigator
{
    /**
     * Xuất ra phân trang kiểu cổ điển
     *
     * @access public
     * @param string $prevWord Văn bản trang trước
     * @param string $nextWord Văn bản trang sau
     * @return void
     */
    public function render(string $prevWord = 'PREV', string $nextWord = 'NEXT')
    {
        $this->prev($prevWord);
        $this->next($nextWord);
    }

    /**
     * Xuất trang trước
     *
     * @access public
     * @param string $prevWord Văn bản trang trước
     * @return void
     */
    public function prev(string $prevWord = 'PREV')
    {
        // Xuất trang trước
        if ($this->total > 0 && $this->currentPage > 1) {
            echo '<a class="prev" href="'
                . str_replace($this->pageHolder, $this->currentPage - 1, $this->pageTemplate)
                . $this->anchor . '">'
                . $prevWord . '</a>';
        }
    }

    /**
     * Xuất trang tiếp theo
     *
     * @access public
     * @param string $nextWord Văn bản trang sau
     * @return void
     */
    public function next(string $nextWord = 'NEXT')
    {
        // Xuất trang tiếp theo
        if ($this->total > 0 && $this->currentPage < $this->totalPage) {
            echo '<a class="next" title="" href="'
                . str_replace($this->pageHolder, $this->currentPage + 1, $this->pageTemplate)
                . $this->anchor . '">'
                . $nextWord . '</a>';
        }
    }
}
