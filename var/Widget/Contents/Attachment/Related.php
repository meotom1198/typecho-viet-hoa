<?php

namespace Widget\Contents\Attachment;

use Typecho\Db;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Các thành phần tập tin liên quan đến bài viết
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Related extends Contents
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
        $this->parameter->setDefault('parentId=0&limit=0');

        // Nếu không có giá trị cid
        if (!$this->parameter->parentId) {
            return;
        }

        /** Xây dựng một truy vấn cơ bản */
        $select = $this->select()->where('table.contents.type = ?', 'attachment');

        // Trường thứ tự đại diện cho bài viết mà nó thuộc về trong tệp
        $select->where('table.contents.parent = ?', $this->parameter->parentId);

        /** Gửi truy vấn */
        $select->order('table.contents.created', Db::SORT_ASC);

        if ($this->parameter->limit > 0) {
            $select->limit($this->parameter->limit);
        }

        if ($this->parameter->offset > 0) {
            $select->offset($this->parameter->offset);
        }

        $this->db->fetchAll($select, [$this, 'push']);
    }
}
