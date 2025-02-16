<?php

namespace Widget\Base;

use Typecho\Db\Query;

/**
 * Base Query Interface
 */
interface QueryInterface
{
    /**
     * Phương thức truy vấn
     *
     * @return Query
     */
    public function select(): Query;

    /**
     * Lấy số lượng tất cả các bản ghi
     *
     * @access public
     * @param Query $condition Đối tượng truy vấn
     * @return integer
     */
    public function size(Query $condition): int;

    /**
     * Thêm phương pháp ghi
     *
     * @access public
     * @param array $rows Giá trị tương ứng của trường
     * @return integer
     */
    public function insert(array $rows): int;

    /**
     * Cập nhật phương pháp ghi
     *
     * @access public
     * @param array $rows Giá trị tương ứng của trường
     * @param Query $condition Đối tượng truy vấn
     * @return integer
     */
    public function update(array $rows, Query $condition): int;

    /**
     * Phương pháp xóa bản ghi
     *
     * @access public
     * @param Query $condition Đối tượng truy vấn
     * @return integer
     */
    public function delete(Query $condition): int;
}
