<?php

namespace Widget\Metas\Tag;

use Typecho\Common;
use Typecho\Db;
use Widget\Base\Metas;

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
class Cloud extends Metas
{
    /**
     * Chức năng nhập
     *
     * @throws Db\Exception
     */
    public function execute()
    {
        $this->parameter->setDefault(['sort' => 'count', 'ignoreZeroCount' => false, 'desc' => true, 'limit' => 0]);
        $select = $this->select()->where('type = ?', 'tag')
            ->order($this->parameter->sort, $this->parameter->desc ? Db::SORT_DESC : Db::SORT_ASC);

        /** Bỏ qua số lượng bằng 0 */
        if ($this->parameter->ignoreZeroCount) {
            $select->where('count > 0');
        }

        /** tổng giới hạn */
        if ($this->parameter->limit) {
            $select->limit($this->parameter->limit);
        }

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * Chuỗi đầu ra theo số phần
     *
     * @param mixed ...$args Giá trị cần xuất
     */
    public function split(...$args)
    {
        array_unshift($args, $this->count);
        echo call_user_func_array([Common::class, 'splitByCount'], $args);
    }
}
