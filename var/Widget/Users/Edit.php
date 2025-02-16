<?php

namespace Widget\Users;

use Typecho\Common;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Utils\PasswordHash;
use Widget\ActionInterface;
use Widget\Base\Users;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Chỉnh sửa thành phần người dùng
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Users implements ActionInterface
{
    /**
     * Thực thi chức năng
     *
     * @return void
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        /** Quản trị viên trở lên */
        $this->user->pass('administrator');

        /** chế độ cập nhật */
        if (($this->request->uid && 'delete' != $this->request->do) || 'update' == $this->request->do) {
            $this->db->fetchRow($this->select()
                ->where('uid = ?', $this->request->uid)->limit(1), [$this, 'push']);

            if (!$this->have()) {
                throw new Exception(_t('Tài khoản không tồn tại!'), 404);
            }
        }
    }

    /**
     * Nhận tiêu đề thực đơn
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('Chỉnh sửa %s', $this->name);
    }

    /**
     * Xác định xem người dùng có tồn tại hay không
     *
     * @param integer $uid Khóa chính của người dùng
     * @return boolean
     * @throws \Typecho\Db\Exception
     */
    public function userExists(int $uid): bool
    {
        $user = $this->db->fetchRow($this->db->select()
            ->from('table.users')
            ->where('uid = ?', $uid)->limit(1));

        return !empty($user);
    }

    /**
     * Thêm người dùng
     *
     * @throws \Typecho\Db\Exception
     */
    public function insertUser()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        $hasher = new PasswordHash(8, true);

        /** Nhận dữ liệu */
        $user = $this->request->from('name', 'mail', 'screenName', 'password', 'url', 'group');
        $user['screenName'] = empty($user['screenName']) ? $user['name'] : $user['screenName'];
        $user['password'] = $hasher->hashPassword($user['password']);
        $user['created'] = $this->options->time;

        /** Chèn dữ liệu */
        $user['uid'] = $this->insert($user);

        /** Đặt điểm nổi bật */
        Notice::alloc()->highlight('user-' . $user['uid']);

        /** Tin nhắn nhắc nhở */
        Notice::alloc()->set(_t('Tài khoản %s đã được tạo!', $user['screenName']), 'success');

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-users.php', $this->options->adminUrl));
    }

    /**
     * Tạo biểu mẫu
     *
     * @access public
     * @param string|null $action hình thức hành động
     * @return Form
     */
    public function form(?string $action = null): Form
    {
        /** Xây dựng bảng */
        $form = new Form($this->security->getIndex('/action/users-edit'), Form::POST_METHOD);

        /** Tên người dùng */
        $name = new Form\Element\Text('name', null, null, _t('Tên tài khoản') . ' *', _t('Tên tài khoản này sẽ là tên mà người dùng đăng nhập.')
            . '<br />' . _t('Vui lòng không sao chép tên người dùng hiện có trong hệ thống.'));
        $form->addInput($name);

        /** Địa chỉ email */
        $mail = new Form\Element\Text('mail', null, null, _t('E-mail') . ' *', _t('Địa chỉ email sẽ là thông tin liên hệ chính cho tài khoản này.')
            . '<br />' . _t('Vui lòng không sao chép địa chỉ email hiện có trong hệ thống.'));
        $form->addInput($mail);

        /** Biệt hiệu của người dùng */
        $screenName = new Form\Element\Text('screenName', null, null, _t('Biệt hiệu của tài khoản'), _t('Biệt danh người dùng có thể khác với tên người dùng và được sử dụng để hiển thị giao diện người dùng.')
            . '<br />' . _t('Nếu bạn để trống phần này, tên tài khoản sẽ được sử dụng theo mặc định.'));
        $form->addInput($screenName);

        /** Mật khẩu người dùng */
        $password = new Form\Element\Password('password', null, null, _t('Mật khẩu'), _t('Thêm mật khẩu cho tài khoản này.')
            . '<br />' . _t('Nên sử dụng mật khẩu hỗn hợp các ký tự đặc biệt, chữ cái và số để tăng tính bảo mật cho mật khẩu.'));
        $password->input->setAttribute('class', 'w-60');
        $form->addInput($password);

        /** Xác nhận mật khẩu người dùng */
        $confirm = new Form\Element\Password('confirm', null, null, _t('Xác nhận mật khẩu'), _t('Vui lòng nhập mật khẩu này giống với mật khẩu đã nhập ở trên.'));
        $confirm->input->setAttribute('class', 'w-60');
        $form->addInput($confirm);

        /** Địa chỉ trang facebook */
        $url = new Form\Element\Text('url', null, null, _t('Địa chỉ trang Facebook'), _t('Nhập địa chỉ trang Facebbok của người dùng này, vui lòng bắt đầu bằng <code>https://facebook.com/*******</code>'));
        $form->addInput($url);

        /** Chức vụ người dùng */
        $group = new Form\Element\Select(
            'group',
            [
                'subscriber'  => _t('Người theo dõi'),
                'contributor' => _t('Người đóng góp'), 'editor' => _t('Super MOD'), 'administrator' => _t('Quản Trị Viên')
            ],
            null,
            _t('Chức vụ người dùng'),
            _t('Các chức vụ khác nhau có quyền khác nhau.') . '<br />' . _t('Để biết bảng phân bổ quyền cụ thể, vui lòng tham khảo tài liệu <a href="https://docs.typecho.org/develop/acl">tại đây</a>.')
        );
        $form->addInput($group);

        /** Hành động của người dùng */
        $do = new Form\Element\Hidden('do');
        $form->addInput($do);

        /** Khóa chính của người dùng */
        $uid = new Form\Element\Hidden('uid');
        $form->addInput($uid);

        /** nút gửi */
        $submit = new Form\Element\Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (null != $this->request->uid) {
            $submit->value(_t('Chỉnh sửa tài khoản'));
            $name->value($this->name);
            $screenName->value($this->screenName);
            $url->value($this->url);
            $mail->value($this->mail);
            $group->value($this->group);
            $do->value('update');
            $uid->value($this->uid);
            $_action = 'update';
        } else {
            $submit->value(_t('Thêm tài khoản'));
            $do->value('insert');
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** Thêm quy tắc vào biểu mẫu */
        if ('insert' == $action || 'update' == $action) {
            $screenName->addRule([$this, 'screenNameExists'], _t('Biệt hiệu đã tồn tại!'));
            $screenName->addRule('xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong biệt danh'));
            $url->addRule('url', _t('Địa chỉ trang Facebook không đúng định dạng!'));
            $mail->addRule('required', _t('Bắt buộc phải điền email!'));
            $mail->addRule([$this, 'mailExists'], _t('Địa chỉ email đã tồn tại!'));
            $mail->addRule('email', _t('Email không đúng định dạng!'));
            $password->addRule('minLength', _t('Để đảm bảo an toàn cho tài khoản của bạn, vui lòng nhập mật khẩu có ít nhất 6 chữ số!'), 6);
            $confirm->addRule('confirm', _t('Hai mật khẩu bạn đã nhập không khớp!'), 'password');
        }

        if ('insert' == $action) {
            $name->addRule('required', _t('Bắt buộc phải điền tên tài khoản!'));
            $name->addRule('xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong tên tài khoản của bạn!'));
            $name->addRule([$this, 'nameExists'], _t('Tên tài khoản đã tồn tại!'));
            $password->label(_t('Mật khẩu') . ' *');
            $confirm->label(_t('Xác nhận mật khẩu') . ' *');
            $password->addRule('required', _t('Bắt buộc phải điền mật khẩu!'));
        }

        if ('update' == $action) {
            $name->input->setAttribute('disabled', 'disabled');
            $uid->addRule('required', _t('Khóa chính của tài khoản không tồn tại!'));
            $uid->addRule([$this, 'userExists'], _t('Tài khoản không tồn tại!'));
        }

        return $form;
    }

    /**
     * Cập nhật người dùng
     *
     * @throws \Typecho\Db\Exception
     */
    public function updateUser()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** Nhận dữ liệu */
        $user = $this->request->from('mail', 'screenName', 'password', 'url', 'group');
        $user['screenName'] = empty($user['screenName']) ? $user['name'] : $user['screenName'];
        if (empty($user['password'])) {
            unset($user['password']);
        } else {
            $hasher = new PasswordHash(8, true);
            $user['password'] = $hasher->hashPassword($user['password']);
        }

        /** Cập nhật dữ liệu */
        $this->update($user, $this->db->sql()->where('uid = ?', $this->request->uid));

        /** Đặt điểm nổi bật */
        Notice::alloc()->highlight('user-' . $this->request->uid);

        /** Tin nhắn nhắc nhở */
        Notice::alloc()->set(_t('Tài khoản %s đã được sửa', $user['screenName']), 'success');

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-users.php?' .
            $this->getPageOffsetQuery($this->request->uid), $this->options->adminUrl));
    }

    /**
     * Nhận truy vấn URL của trang offset
     *
     * @param integer $uid 用户id
     * @return string
     * @throws \Typecho\Db\Exception
     */
    protected function getPageOffsetQuery(int $uid): string
    {
        return 'page=' . $this->getPageOffset('uid', $uid);
    }

    /**
     * Xóa người dùng
     *
     * @throws \Typecho\Db\Exception
     */
    public function deleteUser()
    {
        $users = $this->request->filter('int')->getArray('uid');
        $masterUserId = $this->db->fetchObject($this->db->select(['MIN(uid)' => 'num'])->from('table.users'))->num;
        $deleteCount = 0;

        foreach ($users as $user) {
            if ($masterUserId == $user || $user == $this->user->uid) {
                continue;
            }

            if ($this->delete($this->db->sql()->where('uid = ?', $user))) {
                $deleteCount++;
            }
        }

        /** Tin nhắn nhắc nhở */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('Tài khoản đã bị xóa!') : _t('Không có tài khoản nào bị xóa!'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** Tới trang gốc */
        $this->response->redirect(Common::url('manage-users.php', $this->options->adminUrl));
    }

    /**
     * Chức năng nhập
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->is('do=insert'))->insertUser();
        $this->on($this->request->is('do=update'))->updateUser();
        $this->on($this->request->is('do=delete'))->deleteUser();
        $this->response->redirect($this->options->adminUrl);
    }
}
