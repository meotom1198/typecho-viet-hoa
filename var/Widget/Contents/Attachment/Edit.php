<?php

namespace Widget\Contents\Attachment;

use Typecho\Common;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Layout;
use Widget\ActionInterface;
use Widget\Contents\Post\Edit as PostEdit;
use Widget\Notice;
use Widget\Upload;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Chỉnh sửa thành phần bài viết
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
     * Thực thi chức năng
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        /** Phải là cộng tác viên trở lên */
        $this->user->pass('contributor');

        /** Lấy nội dung bài viết */
        if (!empty($this->request->cid)) {
            $this->db->fetchRow($this->select()
                ->where('table.contents.type = ?', 'attachment')
                ->where('table.contents.cid = ?', $this->request->filter('int')->cid)
                ->limit(1), [$this, 'push']);

            if (!$this->have()) {
                throw new Exception(_t('Tập tin không tồn tại!'), 404);
            } elseif (!$this->allow('edit')) {
                throw new Exception(_t('Không có quyền chỉnh sửa!'), 403);
            }
        }
    }

    /**
     * Xác định tên file có hợp pháp hay không sau khi chuyển sang dạng viết tắt
     *
     * @param string $name tên tập tin
     * @return boolean
     */
    public function nameToSlug(string $name): bool
    {
        if (empty($this->request->slug)) {
            $slug = Common::slugName($name);
            if (empty($slug) || !$this->slugExists($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Xác định xem tên viết tắt của tập tin có tồn tại không
     *
     * @param string $slug viết tắt
     * @return boolean
     * @throws \Typecho\Db\Exception
     */
    public function slugExists(string $slug): bool
    {
        $select = $this->db->select()
            ->from('table.contents')
            ->where('type = ?', 'attachment')
            ->where('slug = ?', Common::slugName($slug))
            ->limit(1);

        if ($this->request->cid) {
            $select->where('cid <> ?', $this->request->cid);
        }

        $attachment = $this->db->fetchRow($select);
        return !$attachment;
    }

    /**
     * tập tin cập nhật
     *
     * @throws \Typecho\Db\Exception
     * @throws Exception
     */
    public function updateAttachment()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** Nhận dữ liệu */
        $input = $this->request->from('name', 'slug', 'description');
        $input['slug'] = Common::slugName(empty($input['slug']) ? $input['name'] : $input['slug']);

        $attachment['title'] = $input['name'];
        $attachment['slug'] = $input['slug'];

        $content = $this->attachment->toArray();
        $content['description'] = $input['description'];

        $attachment['text'] = serialize($content);
        $cid = $this->request->filter('int')->cid;

        /** Cập nhật dữ liệu */
        $updateRows = $this->update($attachment, $this->db->sql()->where('cid = ?', $cid));

        if ($updateRows > 0) {
            $this->db->fetchRow($this->select()
                ->where('table.contents.type = ?', 'attachment')
                ->where('table.contents.cid = ?', $cid)
                ->limit(1), [$this, 'push']);

            /** Đặt điểm nổi bật */
            Notice::alloc()->highlight($this->theId);

            /** Tin nhắn nhắc nhở */
            Notice::alloc()->set('publish' == $this->status ?
                _t('Tập tin <a href="%s">%s</a> đã được cập nhật', $this->permalink, $this->title) :
                _t('Tập tin không được lưu trữ %s đã được cập nhật', $this->title), 'success');
        }

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-medias.php?' .
            $this->getPageOffsetQuery($cid, $this->status), $this->options->adminUrl));
    }

    /**
     * Tạo biểu mẫu
     *
     * @return Form
     */
    public function form(): Form
    {
        /** Xây dựng bảng */
        $form = new Form($this->security->getIndex('/action/contents-attachment-edit'), Form::POST_METHOD);

        /** Tên tập tin */
        $name = new Form\Element\Text('name', null, $this->title, _t('Tiêu đề') . ' *');
        $form->addInput($name);

        /** Tập tin viết tắt */
        $slug = new Form\Element\Text(
            'slug',
            null,
            $this->slug,
            _t('Viết tắt'),
            _t('Chữ viết tắt của tập tin được sử dụng để tạo các biểu mẫu liên kết thân thiện. Nên sử dụng các chữ cái, số, dấu gạch dưới và dấu gạch ngang.')
        );
        $form->addInput($slug);

        /** Mô tả tập tin */
        $description = new Form\Element\Textarea(
            'description',
            null,
            $this->attachment->description,
            _t('Mô tả'),
            _t('Văn bản này được sử dụng để mô tả tệp và sẽ được hiển thị trong một số chủ đề.')
        );
        $form->addInput($description);

        /** Hành động phân loại */
        $do = new Form\Element\Hidden('do', null, 'update');
        $form->addInput($do);

        /** Phân loại khóa chính */
        $cid = new Form\Element\Hidden('cid', null, $this->cid);
        $form->addInput($cid);

        /** nút gửi */
        $submit = new Form\Element\Submit(null, null, _t('Gửi thay đổi'));
        $submit->input->setAttribute('class', 'btn primary');
        $delete = new Layout('a', [
            'href'  => $this->security->getIndex('/action/contents-attachment-edit?do=delete&cid=' . $this->cid),
            'class' => 'operate-delete',
            'lang'  => _t('Bạn có chắc chắn xóa tập tin %s không?', $this->attachment->name)
        ]);
        $submit->container($delete->html(_t('Xóa tập tin')));
        $form->addItem($submit);

        $name->addRule('required', _t('Bạn phải nhập tiêu đề của tập tin!'));
        $name->addRule([$this, 'nameToSlug'], _t('Tiêu đề tập tin không thể được chuyển đổi thành tên viết tắt!'));
        $slug->addRule([$this, 'slugExists'], _t('Chữ viết tắt đã tồn tại!'));

        return $form;
    }

    /**
     * Nhận truy vấn URL của trang offset
     *
     * @param integer $cid mã tập tin
     * @param string|null $status tình trạng
     * @return string
     * @throws \Typecho\Db\Exception|Exception
     */
    protected function getPageOffsetQuery(int $cid, string $status = null): string
    {
        return 'page=' . $this->getPageOffset(
            'cid',
            $cid,
            'attachment',
            $status,
            $this->user->pass('editor', true) ? 0 : $this->user->uid
        );
    }

    /**
     * Xóa bài viết
     *
     * @throws \Typecho\Db\Exception
     */
    public function deleteAttachment()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            // Xóa giao diện plug-in
            self::pluginHandle()->delete($post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $row = $this->db->fetchRow($this->select()
                ->where('table.contents.type = ?', 'attachment')
                ->where('table.contents.cid = ?', $post)
                ->limit(1), [$this, 'push']);

            if ($this->isWriteable(clone $condition) && $this->delete($condition)) {
                /** Xóa tập tin */
                Upload::deleteHandle($row);

                /** Xóa bình luận */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $post));

                // Xóa hoàn toàn giao diện plug-in
                self::pluginHandle()->finishDelete($post, $this);

                $deleteCount++;
            }

            unset($condition);
        }

        if ($this->request->isAjax()) {
            $this->response->throwJson($deleteCount > 0 ? ['code' => 200, 'message' => _t('Tập tin đã bị xóa!')]
                : ['code' => 500, 'message' => _t('Không có tập tin nào bị xóa!')]);
        } else {
            /** Đặt thông tin nhắc nhở */
            Notice::alloc()
                ->set(
                    $deleteCount > 0 ? _t('Tập tin đã bị xóa!') : _t('Không có tập tin nào bị xóa!'),
                    $deleteCount > 0 ? 'success' : 'notice'
                );

            /** Quay lại trang web ban đầu */
            $this->response->redirect(Common::url('manage-medias.php', $this->options->adminUrl));
        }
    }

    /**
     * clearAttachment
     *
     * @access public
     * @return void
     * @throws \Typecho\Db\Exception
     */
    public function clearAttachment()
    {
        $page = 1;
        $deleteCount = 0;

        do {
            $posts = array_column($this->db->fetchAll($this->select('cid')
                ->from('table.contents')
                ->where('type = ? AND parent = ?', 'attachment', 0)
                ->page($page, 100)), 'cid');
            $page++;

            foreach ($posts as $post) {
                // Xóa giao diện plug-in
                self::pluginHandle()->delete($post, $this);

                $condition = $this->db->sql()->where('cid = ?', $post);
                $row = $this->db->fetchRow($this->select()
                    ->where('table.contents.type = ?', 'attachment')
                    ->where('table.contents.cid = ?', $post)
                    ->limit(1), [$this, 'push']);

                if ($this->isWriteable(clone $condition) && $this->delete($condition)) {
                    /** Xóa tập tin */
                    Upload::deleteHandle($row);

                    /** Xóa bình luận */
                    $this->db->query($this->db->delete('table.comments')
                        ->where('cid = ?', $post));

                    $status = $this->status;

                    // Xóa hoàn toàn giao diện plug-in
                    self::pluginHandle()->finishDelete($post, $this);

                    $deleteCount++;
                }

                unset($condition);
            }
        } while (count($posts) == 100);

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('Các tập tin không được lưu trữ đã bị xóa!') : _t('Không có tập tin chưa được lưu trữ nào được xóa!'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** Quay lại trang web ban đầu */
        $this->response->redirect(Common::url('manage-medias.php', $this->options->adminUrl));
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
        $this->on($this->request->is('do=delete'))->deleteAttachment();
        $this->on($this->have() && $this->request->is('do=update'))->updateAttachment();
        $this->on($this->request->is('do=clear'))->clearAttachment();
        $this->response->redirect($this->options->adminUrl);
    }
}
