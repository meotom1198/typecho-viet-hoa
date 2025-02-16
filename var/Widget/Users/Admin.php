<?php

namespace Widget\Users;

use Typecho\Common;
use Typecho\Db;
use Typecho\Db\Query;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần danh sách thành viên hậu trường
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Admin extends Users
{
    /**
     * Đối tượng tính toán phân trang
     *
     * @var Query
     */
    private $countSql;

    /**
     * Số lượng tất cả các bài viết
     *
     * @var integer
     */
    private $total;

    /**
     * Trang hiện tại
     *
     * @var integer
     */
    private $currentPage;

    /**
     * Thực thi chức năng
     *
     * @throws Db\Exception
     */
    public function execute()
    {
        $this->parameter->setDefault('pageSize=20');
        $select = $this->select();
        $this->currentPage = $this->request->get('page', 1);

        /** Lọc tiêu đề */
        if (null != ($keywords = $this->request->keywords)) {
            $select->where(
                'name LIKE ? OR screenName LIKE ?',
                '%' . Common::filterSearchQuery($keywords) . '%',
                '%' . Common::filterSearchQuery($keywords) . '%'
            );
        }

        $this->countSql = clone $select;

        $select->order('table.users.uid', Db::SORT_ASC)
            ->page($this->currentPage, $this->parameter->pageSize);

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * Phân trang đầu ra
     *
     * @throws Exception|Db\Exception
     */
    public function pageNav()
    {
        $query = $this->request->makeUriByRequest('page={page}');

        /** Sử dụng phân trang hộp */
        $nav = new Box(
            !isset($this->total) ? $this->total = $this->size($this->countSql) : $this->total,
            $this->currentPage,
            $this->parameter->pageSize,
            $query
        );
        $nav->render('&laquo;', '&raquo;');
    }

    /**
     * Chỉ xuất tên miền và đường dẫn
     *
     * @return string
     */
    protected function ___domainPath(): string
    {
        $parts = parse_url($this->url);
        return $parts['host'] . ($parts['path'] ?? null);
    }

    /**
     * Số bài viết được xuất bản
     *
     * @return integer
     * @throws Db\Exception
     */
    protected function ___postsNum(): int
    {
        return $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.authorId = ?', $this->uid))->num;
    }
}
