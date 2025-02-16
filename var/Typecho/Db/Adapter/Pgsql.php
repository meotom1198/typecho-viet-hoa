<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Bộ điều hợp cơ sở dữ liệu Pssql
 *
 * @package Db
 */
class Pgsql implements Adapter
{
    use PgsqlTrait;

    /**
     * Xác định xem bộ chuyển đổi có sẵn không
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('pgsql');
    }

    /**
     * Chức năng kết nối cơ sở dữ liệu
     *
     * @param Config $config Cấu hình cơ sở dữ liệu
     * @return resource
     * @throws ConnectionException
     */
    public function connect(Config $config)
    {
        $dsn = "host={$config->host} port={$config->port}"
            . " dbname={$config->database} user={$config->user} password={$config->password}";

        if ($config->charset) {
            $dsn .= " options='--client_encoding={$config->charset}'";
        }

        if ($dbLink = @pg_connect($dsn)) {
            return $dbLink;
        }

        /** Ngoại lệ cơ sở dữ liệu */
        throw new ConnectionException("Couldn't connect to database.");
    }

    /**
     * Nhận phiên bản cơ sở dữ liệu
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        $version = pg_version($handle);
        return $version['server'];
    }

    /**
     * Thực hiện truy vấn cơ sở dữ liệu
     *
     * @param string $query Chuỗi SQL truy vấn cơ sở dữ liệu
     * @param resource $handle đối tượng kết nối
     * @param integer $op Trạng thái đọc và ghi cơ sở dữ liệu
     * @param string|null $action Hành động cơ sở dữ liệu
     * @param string|null $table bảng dữ liệu
     * @return resource
     * @throws SQLException
     */
    public function query(string $query, $handle, int $op = Db::READ, ?string $action = null, ?string $table = null)
    {
        $this->prepareQuery($query, $handle, $action, $table);
        if ($resource = pg_query($handle, $query)) {
            return $resource;
        }

        /** Ngoại lệ cơ sở dữ liệu */
        throw new SQLException(
            @pg_last_error($handle),
            pg_result_error_field(pg_get_result($handle), PGSQL_DIAG_SQLSTATE)
        );
    }

    /**
     * Lấy ra một trong các hàng trong truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với giá trị khóa mảng
     *
     * @param resource $resource Truy vấn trả về ID tài nguyên
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        return pg_fetch_assoc($resource) ?: null;
    }

    /**
     * Lấy một trong các hàng trong truy vấn dữ liệu làm đối tượng, trong đó tên trường tương ứng với thuộc tính đối tượng
     *
     * @param resource $resource Truy vấn dữ liệu tài nguyên
     * @return object|null
     */
    public function fetchObject($resource): ?object
    {
        return pg_fetch_object($resource) ?: null;
    }

    /**
     * @param resource $resource
     * @return array|null
     */
    public function fetchAll($resource): array
    {
        return pg_fetch_all($resource, PGSQL_ASSOC) ?: [];
    }

    /**
     * Lấy số hàng bị ảnh hưởng bởi truy vấn cuối cùng
     *
     * @param resource $resource Truy vấn dữ liệu tài nguyên
     * @param resource $handle đối tượng kết nối
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return pg_affected_rows($resource);
    }

    /**
     * Chức năng thoát trích dẫn
     *
     * @param mixed $string Chuỗi cần được thoát
     * @return string
     */
    public function quoteValue($string): string
    {
        return '\'' . pg_escape_string($string) . '\'';
    }
}
