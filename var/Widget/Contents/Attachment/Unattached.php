<?php

namespace Widget\Contents\Attachment;

use Typecho\Db;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
/**
 * Không có tập tin liên quan
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * Không có thành phần tập tin liên quan
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Unattached extends Contents
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
        /** Xây dựng một truy vấn cơ bản */
        $select = $this->select()->where('table.contents.type = ? AND
        (table.contents.parent = 0 OR table.contents.parent IS NULL)', 'attachment');

        /** cộng với sự phán xét về người dùng */
        $this->where('table.contents.authorId = ?', $this->user->uid);

        /** Gửi truy vấn */
        $select->order('table.contents.created', Db::SORT_DESC);

        $this->db->fetchAll($select, [$this, 'push']);
    }
}
