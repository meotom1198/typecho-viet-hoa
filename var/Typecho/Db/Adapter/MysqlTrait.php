<?php

namespace Typecho\Db\Adapter;

trait MysqlTrait
{
    use QueryTrait;

    /**
     * Xóa bảng dữ liệu
     *
     * @param string $table
     * @param mixed $handle đối tượng kết nối
     * @throws SQLException
     */
    public function truncate(string $table, $handle)
    {
        $this->query('TRUNCATE TABLE ' . $this->quoteColumn($table), $handle);
    }

    /**
     * Câu lệnh truy vấn tổng hợp
     *
     * @access public
     * @param array $sql Mảng từ vựng của đối tượng truy vấn
     * @return string
     */
    public function parseSelect(array $sql): string
    {
        return $this->buildQuery($sql);
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return 'mysql';
    }
}
