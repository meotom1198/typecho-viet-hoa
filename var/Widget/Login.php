<?php

namespace Widget;

use Typecho\Cookie;
use Typecho\Validate;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần đăng nhập
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Login extends Users implements ActionInterface
{
    /**
     * Thành phần đăng nhập
     *
     * @access public
     * @return void
     */
    public function action()
    {
        // protect
        $this->security->protect();

        /** Nếu đã đăng nhập */
        if ($this->user->hasLogin()) {
            /** Trả lại trực tiếp */
            $this->response->redirect($this->options->index);
        }

        /** Khởi tạo lớp xác nhận */
        $validator = new Validate();
        $validator->addRule('name', 'required', _t('Vui lòng nhập tên tài khoản'));
        $validator->addRule('password', 'required', _t('Vui lòng nhập mật khẩu'));
        $expire = 30 * 24 * 3600;

        /** Ghi nhớ trạng thái mật khẩu */
        if ($this->request->remember) {
            Cookie::set('__typecho_remember_remember', 1, $expire);
        } elseif (Cookie::get('__typecho_remember_remember')) {
            Cookie::delete('__typecho_remember_remember');
        }

        /** Chặn các ngoại lệ xác minh */
        if ($error = $validator->run($this->request->from('name', 'password'))) {
            Cookie::set('__typecho_remember_name', $this->request->name);

            /** Đặt thông tin nhắc nhở */
            Notice::alloc()->set($error);
            $this->response->goBack();
        }

        /** Bắt đầu xác minh người dùng **/
        $valid = $this->user->login(
            $this->request->name,
            $this->request->password,
            false,
            1 == $this->request->remember ? $expire : 0
        );

        /** So sánh mật khẩu */
        if (!$valid) {
            /** Ngăn ngừa kiệt sức và ngủ trong 3 giây */
            sleep(3);

            self::pluginHandle()->loginFail(
                $this->user,
                $this->request->name,
                $this->request->password,
                1 == $this->request->remember
            );

            Cookie::set('__typecho_remember_name', $this->request->name);
            Notice::alloc()->set(_t('Tên người dùng hoặc mật khẩu không chính xác!'), 'error');
            $this->response->goBack('?referer=' . urlencode($this->request->referer));
        }

        self::pluginHandle()->loginSucceed(
            $this->user,
            $this->request->name,
            $this->request->password,
            1 == $this->request->remember
        );

        /** Chuyển đến địa chỉ sau xác minh */
        if (!empty($this->request->referer)) {
            /** fix #952 & validate redirect url */
            if (
                0 === strpos($this->request->referer, $this->options->adminUrl)
                || 0 === strpos($this->request->referer, $this->options->siteUrl)
            ) {
                $this->response->redirect($this->request->referer);
            }
        } elseif (!$this->user->pass('contributor', true)) {
            /** Người dùng thông thường không được phép nhảy trực tiếp xuống nền */
            $this->response->redirect($this->options->profileUrl);
        }

        $this->response->redirect($this->options->adminUrl);
    }
}
