<?php

namespace Widget;

use Typecho\Common;
use Typecho\Cookie;
use Typecho\Db\Exception as DbException;
use Typecho\Widget;
use Utils\PasswordHash;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Người dùng hiện đang đăng nhập
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class User extends Users
{
    /**
     * Nhóm người dùng
     *
     * @var array
     */
    public $groups = [
        'administrator' => 0,
        'editor' => 1,
        'contributor' => 2,
        'subscriber' => 3,
        'visitor' => 4
    ];

    /**
     * người dùng
     *
     * @var array
     */
    private $currentUser;

    /**
     * Bạn đã đăng nhập chưa?
     *
     * @var boolean|null
     */
    private $hasLogin = null;

    /**
     * @param int $components
     */
    protected function initComponents(int &$components)
    {
        $components = self::INIT_OPTIONS;
    }

    /**
     * Thực thi chức năng
     *
     * @throws DbException
     */
    public function execute()
    {
        if ($this->hasLogin()) {
            $this->push($this->currentUser);

            // update last activated time
            $this->db->query($this->db
                ->update('table.users')
                ->rows(['activated' => $this->options->time])
                ->where('uid = ?', $this->currentUser['uid']));

            // merge personal options
            $options = $this->personalOptions->toArray();

            foreach ($options as $key => $val) {
                $this->options->{$key} = $val;
            }
        }
    }

    /**
     * Xác định xem người dùng đã đăng nhập hay chưa
     *
     * @return boolean
     * @throws DbException
     */
    public function hasLogin(): ?bool
    {
        if (null !== $this->hasLogin) {
            return $this->hasLogin;
        } else {
            $cookieUid = Cookie::get('__typecho_uid');
            if (null !== $cookieUid) {
                /** Xác minh đăng nhập */
                $user = $this->db->fetchRow($this->db->select()->from('table.users')
                    ->where('uid = ?', intval($cookieUid))
                    ->limit(1));

                $cookieAuthCode = Cookie::get('__typecho_authCode');
                if ($user && Common::hashValidate($user['authCode'], $cookieAuthCode)) {
                    $this->currentUser = $user;
                    return ($this->hasLogin = true);
                }

                $this->logout();
            }

            return ($this->hasLogin = false);
        }
    }

    /**
     * Chức năng đăng xuất của người dùng
     *
     * @access public
     * @return void
     */
    public function logout()
    {
        self::pluginHandle()->trigger($logoutPluggable)->logout();
        if ($logoutPluggable) {
            return;
        }

        Cookie::delete('__typecho_uid');
        Cookie::delete('__typecho_authCode');
    }

    /**
     * Chức năng đăng xuất của người dùng
     *
     * @access public
     * @param string $name tên người dùng
     * @param string $password mật khẩu
     * @param boolean $temporarily Đây có phải là đăng nhập tạm thời?
     * @param integer $expire Thời gian hết hạn
     * @return boolean
     * @throws DbException
     */
    public function login(string $name, string $password, bool $temporarily = false, int $expire = 0): bool
    {
        // Giao diện trình cắm
        $result = self::pluginHandle()->trigger($loginPluggable)->login($name, $password, $temporarily, $expire);
        if ($loginPluggable) {
            return $result;
        }

        /** Bắt đầu xác minh người dùng **/
        $user = $this->db->fetchRow($this->db->select()
            ->from('table.users')
            ->where((strpos($name, '@') ? 'mail' : 'name') . ' = ?', $name)
            ->limit(1));

        if (empty($user)) {
            return false;
        }

        $hashValidate = self::pluginHandle()->trigger($hashPluggable)->hashValidate($password, $user['password']);
        if (!$hashPluggable) {
            if ('$P$' == substr($user['password'], 0, 3)) {
                $hasher = new PasswordHash(8, true);
                $hashValidate = $hasher->checkPassword($password, $user['password']);
            } else {
                $hashValidate = Common::hashValidate($password, $user['password']);
            }
        }

        if ($user && $hashValidate) {
            if (!$temporarily) {
                $this->commitLogin($user, $expire);
            }

            /** Đẩy dữ liệu */
            $this->push($user);
            $this->currentUser = $user;
            $this->hasLogin = true;
            self::pluginHandle()->loginSucceed($this, $name, $password, $temporarily, $expire);

            return true;
        }

        self::pluginHandle()->loginFail($this, $name, $password, $temporarily, $expire);
        return false;
    }

    /**
     * @param $user
     * @param int $expire
     * @throws DbException
     */
    public function commitLogin(&$user, int $expire = 0)
    {
        $authCode = function_exists('openssl_random_pseudo_bytes') ?
            bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Common::randString(20));
        $user['authCode'] = $authCode;

        Cookie::set('__typecho_uid', $user['uid'], $expire);
        Cookie::set('__typecho_authCode', Common::hash($authCode), $expire);

        // Cập nhật thời gian đăng nhập và mã xác minh lần cuối
        $this->db->query($this->db
            ->update('table.users')
            ->expression('logged', 'activated')
            ->rows(['authCode' => $authCode])
            ->where('uid = ?', $user['uid']));
    }

    /**
     * Bạn chỉ cần cung cấp uid hoặc một mảng người dùng hoàn chỉnh để đăng nhập. Nó chủ yếu được sử dụng trong những dịp đặc biệt như plug-in.
     *
     * @param int | array $uid Id người dùng hoặc mảng dữ liệu người dùng
     * @param boolean $temporarily Cho dù đó là đăng nhập tạm thời thì mặc định là đăng nhập tạm thời để tương thích với phương thức trước đó.
     * @param integer $expire Thời gian hết hạn
     * @return boolean
     * @throws DbException
     */
    public function simpleLogin($uid, bool $temporarily = true, int $expire = 0): bool
    {
        if (is_array($uid)) {
            $user = $uid;
        } else {
            $user = $this->db->fetchRow($this->db->select()
                ->from('table.users')
                ->where('uid = ?', $uid)
                ->limit(1));
        }

        if (empty($user)) {
            self::pluginHandle()->simpleLoginFail($this);
            return false;
        }

        if (!$temporarily) {
            $this->commitLogin($user, $expire);
        }

        $this->push($user);
        $this->currentUser = $user;
        $this->hasLogin = true;

        self::pluginHandle()->simpleLoginSucceed($this, $user);
        return true;
    }

    /**
     * Xác định quyền của người dùng
     *
     * @access public
     * @param string $group Nhóm người dùng
     * @param boolean $return Cho dù đó là chế độ trở lại
     * @return boolean
     * @throws DbException|Widget\Exception
     */
    public function pass(string $group, bool $return = false): bool
    {
        if ($this->hasLogin()) {
            if (array_key_exists($group, $this->groups) && $this->groups[$this->group] <= $this->groups[$group]) {
                return true;
            }
        } else {
            if ($return) {
                return false;
            } else {
                // Ngăn chặn chuyển hướng vòng tròn
                $this->response->redirect(defined('__TYPECHO_ADMIN__') ? $this->options->loginUrl .
                    (0 === strpos($this->request->getReferer() ?? '', $this->options->loginUrl) ? '' :
                        '?referer=' . urlencode($this->request->makeUriByRequest())) : $this->options->siteUrl, false);
            }
        }

        if ($return) {
            return false;
        } else {
            throw new Widget\Exception(_t('Quyền truy cập bị cấm!'), 403);
        }
    }
}
