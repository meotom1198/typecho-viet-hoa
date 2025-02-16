<?php

namespace Widget\Metas\Category;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Validate;
use Typecho\Widget\Helper\Form;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Chỉnh sửa thành phần danh mục
 *
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
     * Xác định xem danh mục có tồn tại hay không
     *
     * @param integer $mid Phân loại khóa chính
     * @return boolean
     * @throws Exception
     */
    public function categoryExists(int $mid): bool
    {
        $category = $this->db->fetchRow($this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->where('mid = ?', $mid)->limit(1));

        return (bool)$category;
    }

    /**
     * Xác định xem tên danh mục có tồn tại không
     *
     * @param string $name Tên danh mục
     * @return boolean
     * @throws Exception
     */
    public function nameExists(string $name): bool
    {
        $select = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->where('name = ?', $name)
            ->limit(1);

        if ($this->request->mid) {
            $select->where('mid <> ?', $this->request->mid);
        }

        $category = $this->db->fetchRow($select);
        return !$category;
    }

    /**
     * Xác định tên danh mục có hợp pháp hay không sau khi được chuyển sang tên viết tắt
     *
     * @param string $name Tên danh mục
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
     * Xác định xem tên viết tắt của danh mục có tồn tại hay không
     *
     * @param string $slug viết tắt
     * @return boolean
     * @throws Exception
     */
    public function slugExists(string $slug): bool
    {
        $select = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->where('slug = ?', Common::slugName($slug))
            ->limit(1);

        if ($this->request->mid) {
            $select->where('mid <> ?', $this->request->mid);
        }

        $category = $this->db->fetchRow($select);
        return !$category;
    }

    /**
     * Thêm danh mục
     *
     * @throws Exception
     */
    public function insertCategory()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        /** Nhận dữ liệu */
        $category = $this->request->from('name', 'slug', 'fontawesome', 'description', 'parent');

        $category['slug'] = Common::slugName(empty($category['slug']) ? $category['name'] : $category['slug']);
        $category['type'] = 'category';
        $category['fontawesome'] = '' . $category['fontawesome'] . '';
        $category['order'] = $this->getMaxOrder('category', $category['parent']) + 1;

        /** Chèn dữ liệu */
        $category['mid'] = $this->insert($category);
        $this->push($category);

        /** Đặt điểm nổi bật */
        Notice::alloc()->highlight($this->theId);

        /** Tin nhắn nhắc nhở */
        Notice::alloc()->set(
            _t('Danh mục <a href="%s">%s</a> đã được tạo.', $this->permalink, $this->name),
            'success'
        );

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-categories.php'
            . ($category['parent'] ? '?parent=' . $category['parent'] : ''), $this->options->adminUrl));
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
        $form = new Form($this->security->getIndex('/action/metas-category-edit'), Form::POST_METHOD);

        /** Tên danh mục */
        $name = new Form\Element\Text('name', null, null, _t('Tên danh mục') . ' *');
        $form->addInput($name);

        /** Viết tắt danh mục */
        $slug = new Form\Element\Text(
            'slug',
            null,
            null,
            _t('URL danh mục'),
            _t('URL danh mục được sử dụng để tạo ra liên kết thân thiện. Nên sử dụng các chữ cái, số, dấu gạch dưới và dấu gạch ngang.')
        );
        $form->addInput($slug);
        
        
                /** Fontawesome của danh mục */
        $fontawesome = new Form\Element\Text(
            'fontawesome',
            null,
            null,
            _t('Fontawesome của danh mục'),
            _t('Chức năng Fontawesome này chỉ hoạt động khi bạn sử dụng theme Joe mà thôi!<br>Fontawesome ở đây sử dụng v6.7.1 và bạn có thể tìm thêm icon <a href="https://fontawesome.com/v6/search?o=r&m=free"><b>tại đây</b></a>.')
        );
        $form->addInput($fontawesome);

        /** Danh mục gốc */
        $options = [0 => _t('Không chọn')];
        $parents = Rows::allocWithAlias(
            'options',
            (isset($this->request->mid) ? 'ignore=' . $this->request->mid : '')
        );

        while ($parents->next()) {
            $options[$parents->mid] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $parents->levels) . $parents->name;
        }

        $parent = new Form\Element\Select(
            'parent',
            $options,
            $this->request->parent,
            _t('Danh mục mẹ'),
            _t('Thể loại này sẽ được xếp vào danh mục chính mà bạn chọn.')
        );
        $form->addInput($parent);

        /** Mô tả phân loại */
        $description = new Form\Element\Textarea(
            'description',
            null,
            null,
            _t('Mô tả danh mục'),
            _t('Văn bản này được sử dụng để mô tả danh mục và nó sẽ được hiển thị trong một số chủ đề.')
        );
        $form->addInput($description);

        /** Hành động phân loại */
        $do = new Form\Element\Hidden('do');
        $form->addInput($do);

        /** Phân loại khóa chính */
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
                ->where('type = ?', 'category')->limit(1));

            if (!$meta) {
                $this->response->redirect(Common::url('manage-categories.php', $this->options->adminUrl));
            }

            $name->value($meta['name']);
            $slug->value($meta['slug']);
            $fontawesome->value($meta['fontawesome']);
            $parent->value($meta['parent']);
            $description->value($meta['description']);
            $do->value('update');
            $mid->value($meta['mid']);
            $submit->value(_t('Sửa danh mục'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('Thêm danh mục'));
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** Thêm quy tắc vào biểu mẫu */
        if ('insert' == $action || 'update' == $action) {
            $name->addRule('required', _t('Bạn phải điền tên danh mục!'));
            $name->addRule([$this, 'nameExists'], _t('Tên danh mục đã tồn tại!'));
            $name->addRule([$this, 'nameToSlug'], _t('Tên danh mục không thể được chuyển đổi thành chữ viết tắt'));
            $name->addRule('xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong tên danh mục'));
            $slug->addRule([$this, 'slugExists'], _t('Chữ viết tắt đã tồn tại'));
            $slug->addRule('xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong chữ viết tắt'));
        }

        if ('update' == $action) {
            $mid->addRule('required', _t('Khóa chính danh mục không tồn tại'));
            $mid->addRule([$this, 'categoryExists'], _t('Danh mục không tồn tại'));
        }

        return $form;
    }

    /**
     * Cập nhật danh mục
     *
     * @throws Exception
     */
    public function updateCategory()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** Nhận dữ liệu */
        $category = $this->request->from('name', 'slug', 'fontawesome', 'description', 'parent');
        $category['mid'] = $this->request->mid;
        $category['slug'] = Common::slugName(empty($category['slug']) ? $category['name'] : $category['slug']);
        $category['type'] = 'category';
        $category['fontawesome'] = '' . $category['fontawesome'] . '';
        $current = $this->db->fetchRow($this->select()->where('mid = ?', $category['mid']));

        if ($current['parent'] != $category['parent']) {
            $parent = $this->db->fetchRow($this->select()->where('mid = ?', $category['parent']));

            if ($parent['mid'] == $category['mid']) {
                $category['order'] = $parent['order'];
                $this->update([
                    'parent' => $current['parent'],
                    'order'  => $current['order']
                ], $this->db->sql()->where('mid = ?', $parent['mid']));
            } else {
                $category['order'] = $this->getMaxOrder('category', $category['parent']) + 1;
            }
        }

        /** Cập nhật dữ liệu */
        $this->update($category, $this->db->sql()->where('mid = ?', $this->request->filter('int')->mid));
        $this->push($category);

        /** Đặt điểm nổi bật */
        Notice::alloc()->highlight($this->theId);

        /** Tin nhắn nhắc nhở */
        Notice::alloc()
            ->set(_t('Danh mục <a href="%s">%s</a> đã được cập nhật', $this->permalink, $this->name), 'success');

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-categories.php'
            . ($category['parent'] ? '?parent=' . $category['parent'] : ''), $this->options->adminUrl));
    }

    /**
     * Xóa danh mục
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function deleteCategory()
    {
        $categories = $this->request->filter('int')->getArray('mid');
        $deleteCount = 0;

        foreach ($categories as $category) {
            $parent = $this->db->fetchObject($this->select()->where('mid = ?', $category))->parent;

            if ($this->delete($this->db->sql()->where('mid = ?', $category))) {
                $this->db->query($this->db->delete('table.relationships')->where('mid = ?', $category));
                $this->update(['parent' => $parent], $this->db->sql()->where('parent = ?', $category));
                $deleteCount++;
            }
        }

        /** Tin nhắn nhắc nhở */
        Notice::alloc()
            ->set($deleteCount > 0 ? _t('Danh mục đã bị xóa!') : _t('Không có danh mục nào bị xóa!'), $deleteCount > 0 ? 'success' : 'notice');

        /** Tới trang gốc */
        $this->response->goBack();
    }

    /**
     * Hợp nhất danh mục
     */
    public function mergeCategory()
    {
        /** Xác thực dữ liệu */
        $validator = new Validate();
        $validator->addRule('merge', 'required', _t('Khóa chính danh mục không tồn tại!'));
        $validator->addRule('merge', [$this, 'categoryExists'], _t('Vui lòng chọn danh mục cần gộp!'));

        if ($error = $validator->run($this->request->from('merge'))) {
            Notice::alloc()->set($error, 'error');
            $this->response->goBack();
        }

        $merge = $this->request->merge;
        $categories = $this->request->filter('int')->getArray('mid');

        if ($categories) {
            $this->merge($merge, 'category', $categories);

            /** Tin nhắn nhắc nhở */
            Notice::alloc()->set(_t('Danh mục đã được hợp nhất!'), 'success');
        } else {
            Notice::alloc()->set(_t('Không có danh mục nào được chọn!'), 'notice');
        }

        /** Tới trang gốc */
        $this->response->goBack();
    }

    /**
     * Sắp xếp theo danh mục
     */
    public function sortCategory()
    {
        $categories = $this->request->filter('int')->getArray('mid');
        if ($categories) {
            $this->sort($categories, 'category');
        }

        if (!$this->request->isAjax()) {
            /** Tới trang gốc */
            $this->response->redirect(Common::url('manage-categories.php', $this->options->adminUrl));
        } else {
            $this->response->throwJson(['success' => 1, 'message' => _t('Việc phân loại đã hoàn tất!')]);
        }
    }

    /**
     * Làm mới danh mục
     *
     * @throws Exception
     */
    public function refreshCategory()
    {
        $categories = $this->request->filter('int')->getArray('mid');
        if ($categories) {
            foreach ($categories as $category) {
                $this->refreshCountByTypeAndStatus($category, 'post', 'publish');
            }

            Notice::alloc()->set(_t('Làm mới danh mục đã hoàn tất!'), 'success');
        } else {
            Notice::alloc()->set(_t('Không có danh mục nào được chọn!'), 'notice');
        }

        /** Tới trang gốc */
        $this->response->goBack();
    }

    /**
     * Đặt danh mục mặc định
     *
     * @throws Exception
     */
    public function defaultCategory()
    {
        /** Xác thực dữ liệu */
        $validator = new Validate();
        $validator->addRule('mid', 'required', _t('Khóa chính danh mục không tồn tại!'));
        $validator->addRule('mid', [$this, 'categoryExists'], _t('Danh mục không tồn tại!'));

        if ($error = $validator->run($this->request->from('mid'))) {
            Notice::alloc()->set($error, 'error');
        } else {
            $this->db->query($this->db->update('table.options')
                ->rows(['value' => $this->request->mid])
                ->where('name = ?', 'defaultCategory'));

            $this->db->fetchRow($this->select()->where('mid = ?', $this->request->mid)
                ->where('type = ?', 'category')->limit(1), [$this, 'push']);

            /** Đặt điểm nổi bật */
            Notice::alloc()->highlight($this->theId);

            /** Tin nhắn nhắc nhở */
            Notice::alloc()->set(
                _t('<a href="%s">%s</a> đã được đặt làm danh mục mặc định!', $this->permalink, $this->name),
                'success'
            );
        }

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-categories.php', $this->options->adminUrl));
    }

    /**
     * Nhận tiêu đề thực đơn
     *
     * @return string|null
     * @throws \Typecho\Widget\Exception|Exception
     */
    public function getMenuTitle(): ?string
    {
        if (isset($this->request->mid)) {
            $category = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'category', $this->request->mid));

            if (!empty($category)) {
                return _t('Chỉnh sửa danh mục %s', $category['name']);
            }

        }
        if (isset($this->request->parent)) {
            $category = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'category', $this->request->parent));

            if (!empty($category)) {
                return _t('Thêm danh mục con của %s', $category['name']);
            }

        } else {
            return null;
        }

        throw new \Typecho\Widget\Exception(_t('Danh mục không tồn tại!'), 404);
    }

    /**
     * Chức năng nhập
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=insert'))->insertCategory();
        $this->on($this->request->is('do=update'))->updateCategory();
        $this->on($this->request->is('do=delete'))->deleteCategory();
        $this->on($this->request->is('do=merge'))->mergeCategory();
        $this->on($this->request->is('do=sort'))->sortCategory();
        $this->on($this->request->is('do=refresh'))->refreshCategory();
        $this->on($this->request->is('do=default'))->defaultCategory();
        $this->response->redirect($this->options->adminUrl);
    }
}
