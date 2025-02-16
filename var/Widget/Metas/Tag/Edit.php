<?php

namespace Widget\Metas\Tag;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Widget\Helper\Form;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần chỉnh sửa nhãn
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Metas implements ActionInterface
{
    /**
     * Chức năng nhập
     */
    public function execute()
    {
        /** Chỉnh sửa quyền ở trên */
        $this->user->pass('editor');
    }

    /**
     * Xác định xem thẻ có tồn tại không
     *
     * @param integer $mid Gắn thẻ khóa chính
     * @return boolean
     * @throws Exception
     */
    public function tagExists(int $mid): bool
    {
        $tag = $this->db->fetchRow($this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->where('mid = ?', $mid)->limit(1));

        return (bool)$tag;
    }

    /**
     * Xác định xem tên thẻ có tồn tại không
     *
     * @param string $name Tên thẻ
     * @return boolean
     * @throws Exception
     */
    public function nameExists(string $name): bool
    {
        $select = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->where('name = ?', $name)
            ->limit(1);

        if ($this->request->mid) {
            $select->where('mid <> ?', $this->request->filter('int')->mid);
        }

        $tag = $this->db->fetchRow($select);
        return !$tag;
    }

    /**
     * Xác định tên thẻ có hợp pháp hay không sau khi chuyển sang dạng viết tắt
     *
     * @param string $name tên thẻ
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
     * Xác định xem có tồn tại tên viết tắt của nhãn hay không
     *
     * @param string $slug viết tắt
     * @return boolean
     * @throws Exception
     */
    public function slugExists(string $slug): bool
    {
        $select = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->where('slug = ?', Common::slugName($slug))
            ->limit(1);

        if ($this->request->mid) {
            $select->where('mid <> ?', $this->request->mid);
        }

        $tag = $this->db->fetchRow($select);
        return !$tag;
    }

    /**
     * Chèn thẻ
     *
     * @throws Exception
     */
    public function insertTag()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        /** Nhận dữ liệu */
        $tag = $this->request->from('name', 'slug');
        $tag['type'] = 'tag';
        $tag['slug'] = Common::slugName(empty($tag['slug']) ? $tag['name'] : $tag['slug']);

        /** Chèn dữ liệu */
        $tag['mid'] = $this->insert($tag);
        $this->push($tag);

        /** Đặt điểm nổi bật */
        Notice::alloc()->highlight($this->theId);

        /** Tin nhắn nhắc nhở */
        Notice::alloc()->set(
            _t('Thẻ <a href="%s">%s</a> đã được thêm', $this->permalink, $this->name),
            'success'
        );

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * Tạo biểu mẫu
     *
     * @param string|null $action hình thức hành động
     * @return Form
     * @throws Exception
     */
    public function form(?string $action = null): Form
    {
        /** Xây dựng bảng */
        $form = new Form($this->security->getIndex('/action/metas-tag-edit'), Form::POST_METHOD);

        /** Tên thẻ */
        $name = new Form\Element\Text(
            'name',
            null,
            null,
            _t('Tên thẻ') . ' *',
            _t('Đây là tên của nhãn xuất hiện trên trang web. Chẳng hạn như "Trái đất".')
        );
        $form->addInput($name);

        /** Thẻ viết tắt */
        $slug = new Form\Element\Text(
            'slug',
            null,
            null,
            _t('Thẻ viết tắt'),
            _t('Viết tắt thẻ dùng để tạo dạng liên kết thân thiện, nếu để trống tên thẻ sẽ được sử dụng mặc định.')
        );
        $form->addInput($slug);

        /** gắn thẻ hành động */
        $do = new Form\Element\Hidden('do');
        $form->addInput($do);

        /** Gắn thẻ khóa chính */
        $mid = new Form\Element\Hidden('mid');
        $form->addInput($mid);

        /** nút gửi */
        $submit = new Form\Element\Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (isset($this->request->mid) && 'insert' != $action) {
            /** chế độ cập nhật */
            $meta = $this->db->fetchRow($this->select()
                ->where('mid = ?', $this->request->mid)
                ->where('type = ?', 'tag')->limit(1));

            if (!$meta) {
                $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
            }

            $name->value($meta['name']);
            $slug->value($meta['slug']);
            $do->value('update');
            $mid->value($meta['mid']);
            $submit->value(_t('Sửa thẻ'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('Thêm thẻ'));
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** Thêm quy tắc vào biểu mẫu */
        if ('insert' == $action || 'update' == $action) {
            $name->addRule('required', _t('Bạn phải điền tên thẻ!'));
            $name->addRule([$this, 'nameExists'], _t('Tên thẻ đã tồn tại!'));
            $name->addRule([$this, 'nameToSlug'], _t('Tên thẻ không thể được chuyển đổi thành tên viết tắt!'));
            $name->addRule('xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong tên thẻ!'));
            $slug->addRule([$this, 'slugExists'], _t('Chữ viết tắt đã tồn tại!'));
            $slug->addRule('xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong chữ viết tắt!'));
        }

        if ('update' == $action) {
            $mid->addRule('required', _t('Khóa chính của thẻ không tồn tại!'));
            $mid->addRule([$this, 'tagExists'], _t('Thẻ không tồn tại!'));
        }

        return $form;
    }

    /**
     * Cập nhật nhãn
     *
     * @throws Exception
     */
    public function updateTag()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** Nhận dữ liệu */
        $tag = $this->request->from('name', 'slug', 'mid');
        $tag['type'] = 'tag';
        $tag['slug'] = Common::slugName(empty($tag['slug']) ? $tag['name'] : $tag['slug']);

        /** Cập nhật dữ liệu */
        $this->update($tag, $this->db->sql()->where('mid = ?', $this->request->filter('int')->mid));
        $this->push($tag);

        /** Đặt điểm nổi bật */
        Notice::alloc()->highlight($this->theId);

        /** Tin nhắn nhắc nhở */
        Notice::alloc()->set(
            _t('Thẻ <a href="%s">%s</a> đã được cập nhật', $this->permalink, $this->name),
            'success'
        );

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * Xóa thẻ
     *
     * @throws Exception
     */
    public function deleteTag()
    {
        $tags = $this->request->filter('int')->getArray('mid');
        $deleteCount = 0;

        if ($tags && is_array($tags)) {
            foreach ($tags as $tag) {
                if ($this->delete($this->db->sql()->where('mid = ?', $tag))) {
                    $this->db->query($this->db->delete('table.relationships')->where('mid = ?', $tag));
                    $deleteCount++;
                }
            }
        }

        /** Tin nhắn nhắc nhở */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('Thẻ đã bị xóa!') : _t('Không có thẻ nào bị xóa!'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * Hợp nhất thẻ
     *
     * @throws Exception
     */
    public function mergeTag()
    {
        if (empty($this->request->merge)) {
            Notice::alloc()->set(_t('Hãy điền vào các thẻ cần ghép vào'), 'notice');
            $this->response->goBack();
        }

        $merge = $this->scanTags($this->request->merge);
        if (empty($merge)) {
            Notice::alloc()->set(_t('Tên thẻ được hợp nhất không hợp lệ!'), 'error');
            $this->response->goBack();
        }

        $tags = $this->request->filter('int')->getArray('mid');

        if ($tags) {
            $this->merge($merge, 'tag', $tags);

            /** Tin nhắn nhắc nhở */
            Notice::alloc()->set(_t('Thẻ đã được hợp nhất!'), 'success');
        } else {
            Notice::alloc()->set(_t('Không có thẻ nào được chọn!'), 'notice');
        }

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * Làm mới nhãn
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function refreshTag()
    {
        $tags = $this->request->filter('int')->getArray('mid');
        if ($tags) {
            foreach ($tags as $tag) {
                $this->refreshCountByTypeAndStatus($tag, 'post', 'publish');
            }

            // Tự động làm sạch thẻ
            $this->clearTags();

            Notice::alloc()->set(_t('Làm mới nhãn đã hoàn tất!'), 'success');
        } else {
            Notice::alloc()->set(_t('Không có thẻ nào được chọn!'), 'notice');
        }

        /** Tới trang gốc */
        $this->response->goBack();
    }

    /**
     * Hàm đầu vào, sự kiện ràng buộc
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=insert'))->insertTag();
        $this->on($this->request->is('do=update'))->updateTag();
        $this->on($this->request->is('do=delete'))->deleteTag();
        $this->on($this->request->is('do=merge'))->mergeTag();
        $this->on($this->request->is('do=refresh'))->refreshTag();
        $this->response->redirect($this->options->adminUrl);
    }
}
