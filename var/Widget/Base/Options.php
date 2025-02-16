<?php

namespace Widget\Base;

use Typecho\Db\Exception;
use Typecho\Db\Query;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần tùy chọn toàn cầu
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Options extends Base implements QueryInterface
{
    /**
     * Lấy đối tượng truy vấn ban đầu
     *
     * @access public
     * @return Query
     * @throws Exception
     */
    public function select(): Query
    {
        return $this->db->select()->from('table.options');
    }

    /**
     * chèn một bản ghi
     *
     * @param array $rows ghi lại giá trị chèn
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        return $this->db->query($this->db->insert('table.options')->rows($rows));
    }

    /**
     * Cập nhật bản ghi
     *
     * @param array $rows ghi lại giá trị cập nhật
     * @param Query $condition Cập nhật điều kiện
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        return $this->db->query($condition->update('table.options')->rows($rows));
    }

    /**
     * xóa bản ghi
     *
     * @param Query $condition xóa điều kiện
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        return $this->db->query($condition->delete('table.options'));
    }

    /**
     * Lấy tổng số bản ghi
     *
     * @param Query $condition Điều kiện tính toán
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition->select(['COUNT(name)' => 'num'])->from('table.options'))->num;
    }

    /**
     * Sử dụng tùy chọn hộp kiểm để xác định xem giá trị có được bật hay không
     *
     * @param mixed $settings tập hợp các tùy chọn
     * @param string $name tên tùy chọn
     * @return integer
     */
    protected function isEnableByCheckbox($settings, string $name): int
    {
        return is_array($settings) && in_array($name, $settings) ? 1 : 0;
    }
}
