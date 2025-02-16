<?php

namespace Typecho\Db\Adapter\Pdo;

use Typecho\Config;
use Typecho\Db\Adapter\Pdo;
use Typecho\Db\Adapter\SQLiteTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Bộ điều hợp cơ sở dữ liệu Pdo_SQLite
 *
 * @package Db
 */
class SQLite extends Pdo
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
        return parent::isAvailable() && in_array('sqlite', \PDO::getAvailableDrivers());
    }

    /**
     * 初Khởi tạo cơ sở dữ liệu
     *
     * @param Config $config Cấu hình cơ sở dữ liệu
     * @access public
     * @return \PDO
     */
    public function init(Config $config): \PDO
    {
        $pdo = new \PDO("sqlite:{$config->file}");
        $this->isSQLite2 = version_compare($pdo->getAttribute(\PDO::ATTR_SERVER_VERSION), '3.0.0', '<');
        return $pdo;
    }

    /**
     * Lấy một trong các hàng trong truy vấn dữ liệu làm đối tượng, trong đó tên trường tương ứng với thuộc tính đối tượng
     *
     * @param \PDOStatement $resource Truy vấn dữ liệu tài nguyên
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
     * @param \PDOStatement $resource Truy vấn trả về ID tài nguyên
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        $result = parent::fetch($resource);
        return $result ? $this->filterColumnName($result) : null;
    }

    /**
     * Đưa ra tất cả các kết quả của truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với các giá trị khóa của mảng
     *
     * @param \PDOStatement $resource Truy vấn dữ liệu tài nguyên
     * @return array
     */
    public function fetchAll($resource): array
    {
        return array_map([$this, 'filterColumnName'], parent::fetchAll($resource));
    }
}
