<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Bộ điều hợp cơ sở dữ liệu Mysqli
 *
 * @package Db
 */
class Mysqli implements Adapter
{
    use MysqlTrait;

    /**
     * Mã định danh chuỗi kết nối cơ sở dữ liệu
     *
     * @access private
     * @var \mysqli
     */
    private $dbLink;

    /**
     * Xác định xem bộ chuyển đổi có sẵn không
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('mysqli');
    }

    /**
     * Chức năng kết nối cơ sở dữ liệu
     *
     * @param Config $config Cấu hình cơ sở dữ liệu
     * @return \mysqli
     * @throws ConnectionException
     */
    public function connect(Config $config): \mysqli
    {
        $mysqli = mysqli_init();
        if ($mysqli) {
            if (!empty($config->sslCa)) {
                $mysqli->ssl_set(null, null, $config->sslCa, null, null);

                if (isset($config->sslVerify)) {
                    $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, $config->sslVerify);
                }
            }

            $mysqli->real_connect(
                $config->host,
                $config->user,
                $config->password,
                $config->database,
                (empty($config->port) ? null : $config->port)
            );

            $this->dbLink = $mysqli;

            if ($config->charset) {
                $this->dbLink->query("SET NAMES '{$config->charset}'");
            }

            return $this->dbLink;
        }

        /** Ngoại lệ cơ sở dữ liệu */
        throw new ConnectionException("Couldn't connect to database.", mysqli_connect_errno());
    }

    /**
     * Nhận phiên bản cơ sở dữ liệu
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        return $this->dbLink->server_version;
    }

    /**
     * Thực hiện truy vấn cơ sở dữ liệu
     *
     * @param string $query Chuỗi SQL truy vấn cơ sở dữ liệu
     * @param mixed $handle đối tượng kết nối
     * @param integer $op Trạng thái đọc và ghi cơ sở dữ liệu
     * @param string|null $action Hành động cơ sở dữ liệu
     * @param string|null $table bảng dữ liệu
     * @throws SQLException
     */
    public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    ) {
        if ($resource = @$this->dbLink->query($query)) {
            return $resource;
        }

        /** Ngoại lệ cơ sở dữ liệu */
        throw new SQLException($this->dbLink->error, $this->dbLink->errno);
    }

    /**
     * Lọc trích dẫn đối tượng
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string
    {
        return '`' . $string . '`';
    }

    /**
     * Lấy ra một trong các hàng trong truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với giá trị khóa mảng
     *
     * @param \mysqli_result $resource Truy vấn trả về ID tài nguyên
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        return $resource->fetch_assoc();
    }

    /**
     * Đưa ra tất cả các kết quả của truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với các giá trị khóa của mảng
     *
     * @param \mysqli_result $resource Truy vấn trả về ID tài nguyên
     * @return array
     */
    public function fetchAll($resource): array
    {
        return $resource->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Lấy một trong các hàng trong truy vấn dữ liệu làm đối tượng, trong đó tên trường tương ứng với thuộc tính đối tượng
     *
     * @param \mysqli_result $resource Truy vấn dữ liệu tài nguyên
     * @return object|null
     */
    public function fetchObject($resource): ?object
    {
        return $resource->fetch_object();
    }

    /**
     * Chức năng thoát trích dẫn
     *
     * @param mixed $string Chuỗi cần được thoát
     * @return string
     */
    public function quoteValue($string): string
    {
        return "'" . $this->dbLink->real_escape_string($string) . "'";
    }

    /**
     * Lấy số hàng bị ảnh hưởng bởi truy vấn cuối cùng
     *
     * @param mixed $resource Truy vấn dữ liệu tài nguyên
     * @param \mysqli $handle đối tượng kết nối
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return $handle->affected_rows;
    }

    /**
     * Nhận giá trị khóa chính được trả về từ lần chèn cuối cùng
     *
     * @param mixed $resource Truy vấn dữ liệu tài nguyên
     * @param \mysqli $handle đối tượng kết nối
     * @return integer
     */
    public function lastInsertId($resource, $handle): int
    {
        return $handle->insert_id;
    }
}
