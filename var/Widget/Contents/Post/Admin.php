<?php

namespace Widget\Contents\Post;

use Typecho\Cookie;
use Typecho\Db;
use Typecho\Db\Exception as DbException;
use Typecho\Widget\Exception;
use Typecho\Db\Query;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần danh sách quản lý bài viết
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
     * Nhận tiêu đề thực đơn
     *
     * @return string
     * @throws Exception|DbException
     */
    public function getMenuTitle(): string
    {
        if (isset($this->request->uid)) {
            return _t('Bài viết của: %s', $this->db->fetchObject($this->db->select('screenName')->from('table.users')
                ->where('uid = ?', $this->request->filter('int')->uid))->screenName);
        }

        throw new Exception(_t('Tài khoản không tồn tại!'), 404);
    }

    /**
     * Chức năng lọc quá tải
     *
     * @param array $value
     * @return array
     * @throws DbException
     */
    public function filter(array $value): array
    {
        $value = parent::filter($value);

        if (!empty($value['parent'])) {
            $parent = $this->db->fetchObject($this->select()->where('cid = ?', $value['parent']));

            if (!empty($parent)) {
                $value['commentsNum'] = $parent->commentsNum;
            }
        }

        return $value;
    }

    /**
     * Thực thi chức năng
     *
     * @throws DbException
     */
    public function execute()
    {
        $this->parameter->setDefault('pageSize=20');
        $this->currentPage = $this->request->get('page', 1);

        /** Xây dựng một truy vấn cơ bản */
        $select = $this->select();

        /** Nếu bạn có quyền chỉnh sửa trở lên, bạn có thể xem tất cả các bài viết, nếu không bạn chỉ có thể xem các bài viết của chính mình. */
        if (!$this->user->pass('editor', true)) {
            $select->where('table.contents.authorId = ?', $this->user->uid);
        } else {
            if ('on' == $this->request->__typecho_all_posts) {
                Cookie::set('__typecho_all_posts', 'on');
            } else {
                if ('off' == $this->request->__typecho_all_posts) {
                    Cookie::set('__typecho_all_posts', 'off');
                }

                if ('on' != Cookie::get('__typecho_all_posts')) {
                    $select->where('table.contents.authorId = ?', isset($this->request->uid) ?
                        $this->request->filter('int')->uid : $this->user->uid);
                }
            }
        }

        /** Truy vấn theo trạng thái */
        if ('draft' == $this->request->status) {
            $select->where('table.contents.type = ?', 'post_draft');
        } elseif ('waiting' == $this->request->status) {
            $select->where(
                '(table.contents.type = ? OR table.contents.type = ?) AND table.contents.status = ?',
                'post',
                'post_draft',
                'waiting'
            );
        } else {
            $select->where(
                'table.contents.type = ? OR (table.contents.type = ? AND table.contents.parent = ?)',
                'post',
                'post_draft',
                0
            );
        }

        /** Lọc danh mục */
        if (null != ($category = $this->request->category)) {
            $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                ->where('table.relationships.mid = ?', $category);
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
        $select->order('table.contents.cid', Db::SORT_DESC)
            ->page($this->currentPage, $this->parameter->pageSize);

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * Phân trang đầu ra
     *
     * @throws Exception
     * @throws DbException
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
     * Bản thảo của bài viết hiện tại
     *
     * @return bool
     * @throws DbException
     */
    protected function ___hasSaved(): bool
    {
        if (in_array($this->type, ['post_draft', 'page_draft'])) {
            return true;
        }

        $savedPost = $this->db->fetchRow($this->db->select('cid', 'modified', 'status')
            ->from('table.contents')
            ->where(
                'table.contents.parent = ? AND (table.contents.type = ? OR table.contents.type = ?)',
                $this->cid,
                'post_draft',
                'page_draft'
            )
            ->limit(1));

        if ($savedPost) {
            $this->modified = $savedPost['modified'];
            return true;
        }

        return false;
    }
}

