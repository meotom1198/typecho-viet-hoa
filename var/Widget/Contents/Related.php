<?php

namespace Widget\Contents;

use Typecho\Db;
use Typecho\Db\Query;
use Typecho\Db\Exception;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Các thành phần nội dung liên quan (được liên kết dựa trên thẻ)
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Related extends Contents
{
    /**
     * Thực thi hàm, khởi tạo dữ liệu
     *
     * @throws Exception
     */
    public function execute()
    {
        $this->parameter->setDefault('limit=5');

        if ($this->parameter->tags) {
            $tagsGroup = implode(',', array_column($this->parameter->tags, 'mid'));
            $this->db->fetchAll($this->select()
                ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                ->where('table.relationships.mid IN (' . $tagsGroup . ')')
                ->where('table.contents.cid <> ?', $this->parameter->cid)
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.password IS NULL')
                ->where('table.contents.created < ?', $this->options->time)
                ->where('table.contents.type = ?', $this->parameter->type)
                ->order('table.contents.created', Db::SORT_DESC)
                ->limit($this->parameter->limit), [$this, 'push']);
        }
    }

    /**
     * Nhận đối tượng truy vấn
     *
     * @return Query
     * @throws Exception
     */
    public function select(): Query
    {
        return $this->db->select(
            'DISTINCT table.contents.cid',
            'table.contents.title',
            'table.contents.slug',
            'table.contents.created',
            'table.contents.authorId',
            'table.contents.modified',
            'table.contents.type',
            'table.contents.status',
            'table.contents.text',
            'table.contents.commentsNum',
            'table.contents.order',
            'table.contents.template',
            'table.contents.password',
            'table.contents.allowComment',
            'table.contents.allowPing',
            'table.contents.allowFeed'
        )
            ->from('table.contents');
    }
}
