<?php

namespace Widget\Contents\Page;

use Typecho\Common;
use Typecho\Date;
use Typecho\Widget\Exception;
use Widget\Contents\Post\Edit as PostEdit;
use Widget\ActionInterface;
use Widget\Notice;
use Widget\Service;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Chỉnh sửa thành phần trang
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends PostEdit implements ActionInterface
{
    /**
     * Tên hook trường tùy chỉnh
     *
     * @var string
     * @access protected
     */
    protected $themeCustomFieldsHook = 'themePageFields';

    /**
     * Thực thi chức năng
     *
     * @access public
     * @return void
     * @throws Exception
     * @throws \Typecho\Db\Exception
     */
    public function execute()
    {
        /** Phải có quyền chỉnh sửa trở lên */
        $this->user->pass('editor');

        /** Lấy nội dung bài viết */
        if (!empty($this->request->cid)) {
            $this->db->fetchRow($this->select()
                ->where('table.contents.type = ? OR table.contents.type = ?', 'page', 'page_draft')
                ->where('table.contents.cid = ?', $this->request->filter('int')->cid)
                ->limit(1), [$this, 'push']);

            if ('page_draft' == $this->status && $this->parent) {
                $this->response->redirect(Common::url('write-page.php?cid=' . $this->parent, $this->options->adminUrl));
            }

            if (!$this->have()) {
                throw new Exception(_t('Trang không tồn tại!'), 404);
            } elseif (!$this->allow('edit')) {
                throw new Exception(_t('Bạn không có quyền chỉnh sửa!'), 403);
            }
        }
    }

    /**
     * Đăng bài viết
     */
    public function writePage()
    {
        $contents = $this->request->from(
            'text',
            'template',
            'allowComment',
            'allowPing',
            'allowFeed',
            'slug',
            'order',
            'visibility'
        );

        $contents['title'] = $this->request->get('title', _t('Trang chưa có tên'));
        $contents['created'] = $this->getCreated();
        $contents['visibility'] = ('hidden' == $contents['visibility'] ? 'hidden' : 'publish');

        if ($this->request->markdown && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }

        $contents = self::pluginHandle()->write($contents, $this);

        if ($this->request->is('do=publish')) {
            /** Xuất bản lại một bài viết hiện có */
            $contents['type'] = 'page';
            $this->publish($contents);

            // Hoàn thiện giao diện plug-in phát hành
            self::pluginHandle()->finishPublish($contents, $this);

            /** gửi ping */
            Service::alloc()->sendPing($this);

            /** Đặt thông tin nhắc nhở */
            Notice::alloc()->set(
                _t('Trang "<b><a href="%s">%s</a></b>" đã được phát hành!', $this->permalink, $this->title),
                'success'
            );

            /** Đặt điểm nổi bật */
            Notice::alloc()->highlight($this->theId);

            /** Nhảy trang */
            $this->response->redirect(Common::url('manage-pages.php?', $this->options->adminUrl));
        } else {
            /** Lưu bài viết làm bản nháp */
            $contents['type'] = 'page_draft';
            $this->save($contents);

            // Hoàn thiện giao diện plug-in phát hành
            self::pluginHandle()->finishSave($contents, $this);

            /** Đặt điểm nổi bật */
            Notice::alloc()->highlight($this->cid);

            if ($this->request->isAjax()) {
                $created = new Date($this->options->time);
                $this->response->throwJson([
                    'success' => 1,
                    'time'    => $created->format('s:i:H'),
                    'cid'     => $this->cid,
                    'draftId' => $this->draft['cid']
                ]);
            } else {
                /** Đặt thông tin nhắc nhở */
                Notice::alloc()->set(_t('Đã lưu "%s" làm bản nháp!', $this->title), 'success');

                /** Trở về trang gốc */
                $this->response->redirect(Common::url('write-page.php?cid=' . $this->cid, $this->options->adminUrl));
            }
        }
    }

    /**
     * Đánh dấu trang
     *
     * @throws \Typecho\Db\Exception
     */
    public function markPage()
    {
        $status = $this->request->get('status');
        $statusList = [
            'publish' => _t('Công khai'),
            'hidden'  => _t('Ẩn')
        ];

        if (!isset($statusList[$status])) {
            $this->response->goBack();
        }

        $pages = $this->request->filter('int')->getArray('cid');
        $markCount = 0;

        foreach ($pages as $page) {
            // Đánh dấu giao diện plug-in
            self::pluginHandle()->mark($status, $page, $this);
            $condition = $this->db->sql()->where('cid = ?', $page);

            if ($this->db->query($condition->update('table.contents')->rows(['status' => $status]))) {
                // Làm việc trên bản nháp
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $page, 'page_draft')
                    ->limit(1));

                if (!empty($draft)) {
                    $this->db->query($this->db->update('table.contents')->rows(['status' => $status])
                        ->where('cid = ?', $draft['cid']));
                }

                // Hoàn thiện giao diện plugin đánh dấu
                self::pluginHandle()->finishMark($status, $page, $this);

                $markCount++;
            }

            unset($condition);
        }

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()
            ->set(
                $markCount > 0 ? _t('Trang đã đổi trạng thái thành <strong>%s</strong>', $statusList[$status]) : _t('Không có trang nào được đánh dấu'),
                $markCount > 0 ? 'success' : 'notice'
            );

        /** Quay lại trang web ban đầu */
        $this->response->goBack();
    }

    /**
     * xóa trang
     *
     * @throws \Typecho\Db\Exception
     */
    public function deletePage()
    {
        $pages = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($pages as $page) {
            // Xóa giao diện plug-in
            self::pluginHandle()->delete($page, $this);

            if ($this->delete($this->db->sql()->where('cid = ?', $page))) {
                /** Xóa bình luận */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $page));

                /** Tách các tệp đính kèm */
                $this->unAttach($page);

                /** Hủy liên kết trang chủ */
                if ($this->options->frontPage == 'page:' . $page) {
                    $this->db->query($this->db->update('table.options')
                        ->rows(['value' => 'recent'])
                        ->where('name = ?', 'frontPage'));
                }

                /** Xóa bản nháp */
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $page, 'page_draft')
                    ->limit(1));

                /** Xóa trường tùy chỉnh */
                $this->deleteFields($page);

                if ($draft) {
                    $this->deleteDraft($draft['cid']);
                    $this->deleteFields($draft['cid']);
                }

                // Xóa hoàn toàn giao diện plug-in
                self::pluginHandle()->finishDelete($page, $this);

                $deleteCount++;
            }
        }

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()
            ->set(
                $deleteCount > 0 ? _t('Trang đã bị xóa!') : _t('Không có trang nào bị xóa!'),
                $deleteCount > 0 ? 'success' : 'notice'
            );

        /** Quay lại trang web ban đầu */
        $this->response->goBack();
    }

    /**
     * Xóa bản nháp chứa trang đó
     *
     * @throws \Typecho\Db\Exception
     */
    public function deletePageDraft()
    {
        $pages = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($pages as $page) {
            /** Xóa bản nháp */
            $draft = $this->db->fetchRow($this->db->select('cid')
                ->from('table.contents')
                ->where('table.contents.parent = ? AND table.contents.type = ?', $page, 'page_draft')
                ->limit(1));

            if ($draft) {
                $this->deleteDraft($draft['cid']);
                $this->deleteFields($draft['cid']);
                $deleteCount++;
            }
        }

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()
            ->set(
                $deleteCount > 0 ? _t('Bản nháp đã bị xóa!') : _t('Không có bản nháp nào bị xóa!'),
                $deleteCount > 0 ? 'success' : 'notice'
            );

        /** Quay lại trang web ban đầu */
        $this->response->goBack();
    }

    /**
     * Sắp xếp trang
     *
     * @throws \Typecho\Db\Exception
     */
    public function sortPage()
    {
        $pages = $this->request->filter('int')->getArray('cid');

        if ($pages) {
            foreach ($pages as $sort => $cid) {
                $this->db->query($this->db->update('table.contents')->rows(['order' => $sort + 1])
                    ->where('cid = ?', $cid));
            }
        }

        if (!$this->request->isAjax()) {
            /** Tới trang gốc */
            $this->response->goBack();
        } else {
            $this->response->throwJson(['success' => 1, 'message' => _t('Sắp xếp trang đã hoàn tất!')]);
        }
    }

    /**
     * Hành động ràng buộc
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))->writePage();
        $this->on($this->request->is('do=delete'))->deletePage();
        $this->on($this->request->is('do=mark'))->markPage();
        $this->on($this->request->is('do=deleteDraft'))->deletePageDraft();
        $this->on($this->request->is('do=sort'))->sortPage();
        $this->response->redirect($this->options->adminUrl);
    }
}
