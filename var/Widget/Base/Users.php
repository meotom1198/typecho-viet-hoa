<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Config;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Plugin;
use Typecho\Router;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Lớp trừu tượng người dùng
 *
 * @property int $uid
 * @property string $name
 * @property string $password
 * @property string $mail
 * @property string $url
 * @property string $screenName
 * @property int $created
 * @property int $activated
 * @property int $logged
 * @property string $group
 * @property string $authCode
 * @property-read Config $personalOptions
 * @property-read string $permalink
 * @property-read string $feedUrl
 * @property-read string $feedRssUrl
 * @property-read string $feedAtomUrl
 */
class Users extends Base implements QueryInterface
{
    /**
     * Xác định xem tên người dùng có tồn tại không
     *
     * @param string $name Tên người dùng
     * @return boolean
     * @throws Exception
     */
    public function nameExists(string $name): bool
    {
        $select = $this->db->select()
            ->from('table.users')
            ->where('name = ?', $name)
            ->limit(1);

        if ($this->request->uid) {
            $select->where('uid <> ?', $this->request->uid);
        }

        $user = $this->db->fetchRow($select);
        return !$user;
    }

    /**
     * Xác định xem email có tồn tại không
     *
     * @param string $mail e-mail
     * @return boolean
     * @throws Exception
     */
    public function mailExists(string $mail): bool
    {
        $select = $this->db->select()
            ->from('table.users')
            ->where('mail = ?', $mail)
            ->limit(1);

        if ($this->request->uid) {
            $select->where('uid <> ?', $this->request->uid);
        }

        $user = $this->db->fetchRow($select);
        return !$user;
    }

    /**
     * Xác định xem biệt danh người dùng có tồn tại hay không
     *
     * @param string $screenName biệt danh
     * @return boolean
     * @throws Exception
     */
    public function screenNameExists(string $screenName): bool
    {
        $select = $this->db->select()
            ->from('table.users')
            ->where('screenName = ?', $screenName)
            ->limit(1);

        if ($this->request->uid) {
            $select->where('uid <> ?', $this->request->uid);
        }

        $user = $this->db->fetchRow($select);
        return !$user;
    }

    /**
     * Đẩy giá trị của mỗi hàng vào ngăn xếp
     *
     * @param array $value giá trị của mỗi hàng
     * @return array
     */
    public function push(array $value): array
    {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * Bộ lọc phổ quát
     *
     * @param array $value Dữ liệu hàng cần được lọc
     * @return array
     */
    public function filter(array $value): array
    {
        // Tạo liên kết tĩnh
        $routeExists = (null != Router::get('author'));

        $value['permalink'] = $routeExists ? Router::url('author', $value, $this->options->index) : '#';

        /** Tạo liên kết tổng hợp */
        /** RSS 2.0 */
        $value['feedUrl'] = $routeExists ? Router::url('author', $value, $this->options->feedUrl) : '#';

        /** RSS 1.0 */
        $value['feedRssUrl'] = $routeExists ? Router::url('author', $value, $this->options->feedRssUrl) : '#';

        /** ATOM 1.0 */
        $value['feedAtomUrl'] = $routeExists ? Router::url('author', $value, $this->options->feedAtomUrl) : '#';

        $value = Users::pluginHandle()->filter($value, $this);
        return $value;
    }

    /**
     * Phương thức truy vấn
     *
     * @return Query
     * @throws Exception
     */
    public function select(): Query
    {
        return $this->db->select()->from('table.users');
    }

    /**
     * Lấy số lượng tất cả các bản ghi
     *
     * @param Query $condition Đối tượng truy vấn
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition->select(['COUNT(uid)' => 'num'])->from('table.users'))->num;
    }

    /**
     * Thêm phương pháp ghi
     *
     * @param array $rows Giá trị tương ứng của trường
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        return $this->db->query($this->db->insert('table.users')->rows($rows));
    }

    /**
     * Cập nhật phương pháp ghi
     *
     * @param array $rows Giá trị tương ứng của trường
     * @param Query $condition Đối tượng truy vấn
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        return $this->db->query($condition->update('table.users')->rows($rows));
    }

    /**
     * Phương pháp xóa bản ghi
     *
     * @param Query $condition Đối tượng truy vấn
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        return $this->db->query($condition->delete('table.users'));
    }

    /**
     * Gọi gravatar để xuất hình đại diện của người dùng
     *
     * @param integer $size Kích thước hình đại diện
     * @param string $rating Đánh giá hình đại diện
     * @param string|null $default Hình đại diện đầu ra mặc định
     * @param string|null $class Lớp css mặc định
     */
    public function gravatar(int $size = 40, string $rating = 'X', ?string $default = null, ?string $class = null)
    {
        $url = Common::gravatarUrl($this->mail, $size, $rating, $default, $this->request->isSecure());
        echo '<img' . (empty($class) ? '' : ' class="' . $class . '"') . ' src="' . $url . '" alt="' .
            $this->screenName . '" width="' . $size . '" height="' . $size . '" />';
    }

    /**
     * personalOptions
     *
     * @return Config
     * @throws Exception
     */
    protected function ___personalOptions(): Config
    {
        $rows = $this->db->fetchAll($this->db->select()
            ->from('table.options')->where('user = ?', $this->uid));
        $options = [];
        foreach ($rows as $row) {
            $options[$row['name']] = $row['value'];
        }

        return new Config($options);
    }

    /**
     * Nhận phần bù trang
     *
     * @param string $column Tên trường
     * @param integer $offset giá trị bù đắp
     * @param string|null $group Nhóm người dùng
     * @param integer $pageSize giá trị phân trang
     * @return integer
     * @throws Exception
     */
    protected function getPageOffset(string $column, int $offset, ?string $group = null, int $pageSize = 20): int
    {
        $select = $this->db->select(['COUNT(uid)' => 'num'])->from('table.users')
            ->where("table.users.{$column} > {$offset}");

        if (!empty($group)) {
            $select->where('table.users.group = ?', $group);
        }

        $count = $this->db->fetchObject($select)->num + 1;
        return ceil($count / $pageSize);
    }
}
