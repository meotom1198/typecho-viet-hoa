<?php

namespace Widget\Contents\Attachment;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần danh sách quản lý tập tin
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Admin extends Contents
{
    /**
     * đối tượng câu lệnh được sử dụng để tính toán các giá trị số
     *
     * @var Query
     */
    private $countSql;

    /**
     * Số lượng tất cả các bài viết
     *
     * @var integer
     */
    private $total = false;

    /**
     * Trang hiện tại
     *
     * @var integer
     */
    private $currentPage;

    /**
     * Thực thi chức năng
     *
     * @return void
     * @throws Exception|\Typecho\Widget\Exception
     */
    public function execute()
    {
        $this->parameter->setDefault('pageSize=20');
        $this->currentPage = $this->request->get('page', 1);

        /** Xây dựng một truy vấn cơ bản */
        $select = $this->select()->where('table.contents.type = ?', 'attachment');

        /** Nếu bạn có quyền chỉnh sửa trở lên, bạn có thể xem tất cả các tệp, nếu không, bạn chỉ có thể xem các tệp của riêng mình. */
        if (!$this->user->pass('editor', true)) {
            $select->where('table.contents.authorId = ?', $this->user->uid);
        }

        /** Lọc tiêu đề */
        if (null != ($keywords = $this->request->filter('search')->keywords)) {
            $args = [];
            $keywordsList = explode(' ', $keywords);
            $args[] = implode(' OR ', array_fill(0, count($keywordsList), 'table.contents.title LIKE ?'));

            foreach ($keywordsList as $keyword) {
                $args[] = '%' . $keyword . '%';
            }

            call_user_func_array([$select, 'where'], $args);
        }

        /** Gán một giá trị cho đối tượng số được tính toán và sao chép đối tượng */
        $this->countSql = clone $select;

        /** Gửi truy vấn */
        $select->order('table.contents.created', Db::SORT_DESC)
            ->page($this->currentPage, $this->parameter->pageSize);

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * Phân trang đầu ra
     *
     * @return void
     * @throws Exception|\Typecho\Widget\Exception
     */
    public function pageNav()
    {
        $query = $this->request->makeUriByRequest('page={page}');

        /** Sử dụng phân trang hộp */
        $nav = new Box(
            false === $this->total ? $this->total = $this->size($this->countSql) : $this->total,
            $this->currentPage,
            $this->parameter->pageSize,
            $query
        );

        $nav->render('&laquo;', '&raquo;');
    }

    /**
     * Bài báo
     *
     * @return Config
     * @throws Exception
     */
    protected function ___parentPost(): Config
    {
        return new Config($this->db->fetchRow(
            $this->select()->where('table.contents.cid = ?', $this->parentId)->limit(1)
        ));
    }
}
