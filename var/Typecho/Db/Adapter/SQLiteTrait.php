<?php

namespace Typecho\Db\Adapter;

/**
 * SQLite Special Util
 */
trait SQLiteTrait
{
    use QueryTrait;

    private $isSQLite2 = false;

    /**
     * Xóa bảng dữ liệu
     *
     * @param string $table
     * @param mixed $handle đối tượng kết nối
     * @throws SQLException
     */
    public function truncate(string $table, $handle)
    {
        $this->query('DELETE FROM ' . $this->quoteColumn($table), $handle);
    }

    /**
     * Lọc trích dẫn đối tượng
     *
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string
    {
        return '"' . $string . '"';
    }

    /**
     * Tên trường lọc
     *
     * @access private
     *
     * @param array $result
     *
     * @return array
     */
    private function filterColumnName(array $result): array
    {
        /** Nếu kết quả trống, hãy quay lại trực tiếp */
        if (empty($result)) {
            return $result;
        }

        $tResult = [];

        /** Mảng di chuyển ngang */
        foreach ($result as $key => $val) {
            /** cách nhau bằng dấu chấm */
            if (false !== ($pos = strpos($key, '.'))) {
                $key = substr($key, $pos + 1);
            }

            $tResult[trim($key, '"')] = $val;
        }

        return $tResult;
    }

    /**
     * Xử lý sqlite2 distinct count
     *
     * @param string $sql
     *
     * @return string
     */
    private function filterCountQuery(string $sql): string
    {
        if (preg_match("/SELECT\s+COUNT\(DISTINCT\s+([^\)]+)\)\s+(AS\s+[^\s]+)?\s*FROM\s+(.+)/is", $sql, $matches)) {
            return 'SELECT COUNT(' . $matches[1] . ') ' . $matches[2] . ' FROM SELECT DISTINCT '
                . $matches[1] . ' FROM ' . $matches[3];
        }

        return $sql;
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
        $query = $this->buildQuery($sql);

        if ($this->isSQLite2) {
            $query = $this->filterCountQuery($query);
        }

        return $query;
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return 'sqlite';
    }
}
