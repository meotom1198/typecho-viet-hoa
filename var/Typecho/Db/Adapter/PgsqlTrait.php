<?php

namespace Typecho\Db\Adapter;

use Typecho\Db;

trait PgsqlTrait
{
    use QueryTrait;

    /**
     * @var array
     */
    private $pk = [];

    /**
     * @var bool
     */
    private $compatibleInsert = false;

    /**
     * @var string|null
     */
    private $lastInsertTable = null;

    /**
     * Xóa bảng dữ liệu
     *
     * @param string $table
     * @param resource $handle đối tượng kết nối
     * @throws SQLException
     */
    public function truncate(string $table, $handle)
    {
        $this->query('TRUNCATE TABLE ' . $this->quoteColumn($table) . ' RESTART IDENTITY', $handle);
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
     * 对thích bộ lọc trích dẫn
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string
    {
        return '"' . $string . '"';
    }

    /**
     * @param string $query
     * @param $handle
     * @param string|null $action
     * @param string|null $table
     * @throws SQLException
     */
    protected function prepareQuery(string &$query, $handle, ?string $action = null, ?string $table = null)
    {
        if (Db::INSERT == $action && !empty($table)) {
            $this->compatibleInsert = false;

            if (!isset($this->pk[$table])) {
                $resource = $this->query("SELECT               
  pg_attribute.attname, 
  format_type(pg_attribute.atttypid, pg_attribute.atttypmod) 
FROM pg_index, pg_class, pg_attribute, pg_namespace 
WHERE 
  pg_class.oid = " . $this->quoteValue($table) . "::regclass AND 
  indrelid = pg_class.oid AND 
  nspname = 'public' AND 
  pg_class.relnamespace = pg_namespace.oid AND 
  pg_attribute.attrelid = pg_class.oid AND 
  pg_attribute.attnum = any(pg_index.indkey)
 AND indisprimary", $handle, Db::READ, Db::SELECT, $table);

                $result = $this->fetch($resource);

                if (!empty($result)) {
                    $this->pk[$table] = $result['attname'];
                }
            }

            // Sử dụng chế độ tương thích để theo dõi kết quả chèn
            if (isset($this->pk[$table])) {
                $this->compatibleInsert = true;
                $this->lastInsertTable = $table;
                $query .= ' RETURNING ' . $this->quoteColumn($this->pk[$table]);
            }
        } else {
            $this->lastInsertTable = null;
        }
    }

    /**
     * Nhận giá trị khóa chính được trả về từ lần chèn cuối cùng
     *
     * @param resource $resource Truy vấn dữ liệu tài nguyên
     * @param resource $handle đối tượng kết nối
     * @return integer
     * @throws SQLException
     */
    public function lastInsertId($resource, $handle): int
    {
        $lastTable = $this->lastInsertTable;

        if ($this->compatibleInsert) {
            $result = $this->fetch($resource);
            $pk = $this->pk[$lastTable];

            if (!empty($result) && isset($result[$pk])) {
                return (int) $result[$pk];
            }
        } else {
            $resource = $this->query(
                'SELECT oid FROM pg_class WHERE relname = '
                    . $this->quoteValue($lastTable . '_seq'),
                $handle,
                Db::READ,
                Db::SELECT,
                $lastTable
            );

            $result = $this->fetch($resource);

            if (!empty($result)) {
                $resource = $this->query(
                    'SELECT CURRVAL(' . $this->quoteValue($lastTable . '_seq') . ') AS last_insert_id',
                    $handle,
                    Db::READ,
                    Db::SELECT,
                    $lastTable
                );

                $result = $this->fetch($resource);
                if (!empty($result)) {
                    return (int) $result['last_insert_id'];
                }
            }
        }

        return 0;
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return 'pgsql';
    }

    abstract public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    );

    abstract public function quoteValue(string $string): string;

    abstract public function fetch($resource): ?array;
}
