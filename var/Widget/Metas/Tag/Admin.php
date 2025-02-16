<?php

namespace Widget\Metas\Tag;

use Typecho\Db;
use Typecho\Widget\Exception;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * gắn thẻ thành phần đám mây
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Admin extends Cloud
{
    /**
     * Chức năng nhập
     *
     * @throws Db\Exception
     */
    public function execute()
    {
        $select = $this->select()->where('type = ?', 'tag')->order('mid', Db::SORT_DESC);
        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * Nhận tiêu đề thực đơn
     *
     * @return string|null
     * @throws Exception|Db\Exception
     */
    public function getMenuTitle(): ?string
    {
        if (isset($this->request->mid)) {
            $tag = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'tag', $this->request->mid));

            if (!empty($tag)) {
                return _t('Chỉnh sửa thẻ %s', $tag['name']);
            }
        } else {
            return null;
        }

        throw new Exception(_t('Thẻ không tồn tại!'), 404);
    }
}
