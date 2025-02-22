<?php

namespace Widget\Contents\Page;

use Typecho\Db;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần danh sách trang độc lập
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Rows extends Contents
{
    /**
     * Thực thi chức năng
     *
     * @access public
     * @return void
     * @throws Db\Exception
     */
    public function execute()
    {
        $select = $this->select()->where('table.contents.type = ?', 'page')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $this->options->time)
            ->order('table.contents.order', Db::SORT_ASC);

        // Xóa trang chủ tùy chỉnh
        $frontPage = explode(':', $this->options->frontPage);
        if (2 == count($frontPage) && 'page' == $frontPage[0]) {
            $select->where('table.contents.cid <> ?', $frontPage[1]);
        }

        $this->db->fetchAll($select, [$this, 'push']);
    }
}
