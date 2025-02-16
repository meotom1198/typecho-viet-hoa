<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Bộ điều hợp SQLite cơ sở dữ liệu
 *
 * @package Db
 */
class SQLite implements Adapter
{
    use SQLiteTrait;

    /**
     * Xác định xem bộ chuyển đổi có sẵn không
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('sqlite3');
    }

    /**
     * Chức năng kết nối cơ sở dữ liệu
     *
     * @param Config $config Cấu hình cơ sở dữ liệu
     * @return \SQLite3
     * @throws ConnectionException
     */
    public function connect(Config $config): \SQLite3
    {
        try {
            $dbHandle = new \SQLite3($config->file);
            $this->isSQLite2 = version_compare(\SQLite3::version()['versionString'], '3.0.0', '<');
        } catch (\Exception $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }

        return $dbHandle;
    }

    /**
     * Nhận phiên bản cơ sở dữ liệu
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        return \SQLite3::version()['versionString'];
    }

    /**
     * Thực hiện truy vấn cơ sở dữ liệu
     *
     * @param string $query Chuỗi SQL truy vấn cơ sở dữ liệu
     * @param \SQLite3 $handle đối tượng kết nối
     * @param integer $op Trạng thái đọc và ghi cơ sở dữ liệu
     * @param string|null $action Hành động cơ sở dữ liệu
     * @param string|null $table bảng dữ liệu
     * @return \SQLite3Result
     * @throws SQLException
     */
    public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    ): \SQLite3Result {
        if ($stm = $handle->prepare($query)) {
            if ($resource = $stm->execute()) {
                return $resource;
            }
        }

        /** Ngoại lệ cơ sở dữ liệu */
        throw new SQLException($handle->lastErrorMsg(), $handle->lastErrorCode());
    }

    /**
     * Lấy một trong các hàng trong truy vấn dữ liệu làm đối tượng, trong đó tên trường tương ứng với thuộc tính đối tượng
     *
     * @param \SQLite3Result $resource Truy vấn dữ liệu tài nguyên
     * @return object|null
     */
    public function fetchObject($resource): ?object
    {
        $result = $this->fetch($resource);
        return $result ? (object) $result : null;
    }

    /**
     * Lấy ra một trong các hàng trong truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với giá trị khóa mảng
     *
     * @param \SQLite3Result $resource Truy vấn trả về ID tài nguyên
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        $result = $resource->fetchArray(SQLITE3_ASSOC);
        return $result ? $this->filterColumnName($result) : null;
    }

    /**
     * Đưa ra tất cả các kết quả của truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với các giá trị khóa của mảng
     *
     * @param \SQLite3Result $resource Truy vấn dữ liệu tài nguyên
     * @return array
     */
    public function fetchAll($resource): array
    {
        $result = [];

        while ($row = $this->fetch($resource)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Chức năng thoát trích dẫn
     *
     * @param mixed $string Chuỗi cần được thoát
     * @return string
     */
    public function quoteValue($string): string
    {
        return '\'' . str_replace('\'', '\'\'', $string) . '\'';
    }

    /**
     * Lấy số hàng bị ảnh hưởng bởi truy vấn cuối cùng
     *
     * @param \SQLite3Result $resource Truy vấn dữ liệu tài nguyên
     * @param \SQLite3 $handle đối tượng kết nối
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return $handle->changes();
    }

    /**
     * Nhận giá trị khóa chính được trả về từ lần chèn cuối cùng
     *
     * @param \SQLite3Result $resource Truy vấn dữ liệu tài nguyên
     * @param \SQLite3 $handle đối tượng kết nối
     * @return integer
     */
    public function lastInsertId($resource, $handle): int
    {
        return $handle->lastInsertRowID();
    }
}
