<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Bộ điều hợp cơ sở dữ liệu PDOMysql
 *
 * @package Db
 */
abstract class Pdo implements Adapter
{
    /**
     * đối tượng cơ sở dữ liệu
     *
     * @access protected
     * @var \PDO
     */
    protected $object;

    /**
     * Bảng dữ liệu của hoạt động cuối cùng
     *
     * @access protected
     * @var string
     */
    protected $lastTable;

    /**
     * Xác định xem bộ chuyển đổi có sẵn không
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return class_exists('PDO');
    }

    /**
     * Chức năng kết nối cơ sở dữ liệu
     *
     * @param Config $config Cấu hình cơ sở dữ liệu
     * @return \PDO
     * @throws ConnectionException
     */
    public function connect(Config $config): \PDO
    {
        try {
            $this->object = $this->init($config);
            $this->object->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $this->object;
        } catch (\PDOException $e) {
            /** Ngoại lệ cơ sở dữ liệu */
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Khởi tạo cơ sở dữ liệu
     *
     * @param Config $config Cấu hình cơ sở dữ liệu
     * @abstract
     * @access public
     * @return \PDO
     */
    abstract public function init(Config $config): \PDO;

    /**
     * Nhận phiên bản cơ sở dữ liệu
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        return $handle->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Thực hiện truy vấn cơ sở dữ liệu
     *
     * @param string $query Chuỗi SQL truy vấn cơ sở dữ liệu
     * @param \PDO $handle đối tượng kết nối
     * @param integer $op Trạng thái đọc và ghi cơ sở dữ liệu
     * @param string|null $action Hành động cơ sở dữ liệu
     * @param string|null $table bảng dữ liệu
     * @return \PDOStatement
     * @throws SQLException
     */
    public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    ): \PDOStatement {
        try {
            $this->lastTable = $table;
            $resource = $handle->prepare($query);
            $resource->execute();
        } catch (\PDOException $e) {
            /** Ngoại lệ cơ sở dữ liệu */
            throw new SQLException($e->getMessage(), $e->getCode());
        }

        return $resource;
    }

    /**
     * Đưa ra tất cả các kết quả của truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với các giá trị khóa của mảng
     *
     * @param \PDOStatement $resource Truy vấn dữ liệu tài nguyên
     * @return array
     */
    public function fetchAll($resource): array
    {
        return $resource->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy ra một trong các hàng trong truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với giá trị khóa mảng
     *
     * @param \PDOStatement $resource Truy vấn trả về ID tài nguyên
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        return $resource->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Lấy một trong các hàng trong truy vấn dữ liệu làm đối tượng, trong đó tên trường tương ứng với thuộc tính đối tượng
     *
     * @param \PDOStatement $resource Truy vấn dữ liệu tài nguyên
     * @return object|null
     */
    public function fetchObject($resource): ?object
    {
        return $resource->fetchObject() ?: null;
    }

    /**
     * Chức năng thoát trích dẫn     *
     * @param mixed $string Chuỗi cần được thoát
     * @return string
     */
    public function quoteValue($string): string
    {
        return $this->object->quote($string);
    }

    /**
     * Lấy số hàng bị ảnh hưởng bởi truy vấn cuối cùng
     *
     * @param \PDOStatement $resource Truy vấn dữ liệu tài nguyên
     * @param \PDO $handle đối tượng kết nối
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return $resource->rowCount();
    }

    /**
     * Nhận giá trị khóa chính được trả về từ lần chèn cuối cùng
     *
     * @param \PDOStatement $resource Truy vấn dữ liệu tài nguyên
     * @param \PDO $handle đối tượng kết nối
     * @return integer
     */
    public function lastInsertId($resource, $handle): int
    {
        return $handle->lastInsertId();
    }
}
