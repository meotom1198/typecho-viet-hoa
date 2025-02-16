<?php

namespace Typecho\Db\Adapter\Pdo;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter\SQLException;
use Typecho\Db\Adapter\Pdo;
use Typecho\Db\Adapter\PgsqlTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数Bộ điều hợp cơ sở dữ liệu Pdo_Pgsql
 *
 * @package Db
 */
class Pgsql extends Pdo
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
        return parent::isAvailable() && in_array('pgsql', \PDO::getAvailableDrivers());
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
        $this->prepareQuery($query, $handle, $action, $table);
        return parent::query($query, $handle, $op, $action, $table);
    }

    /**
     * Khởi tạo cơ sở dữ liệu
     *
     * @param Config $config Cấu hình cơ sở dữ liệu
     * @return \PDO
     */
    public function init(Config $config): \PDO
    {
        $pdo = new \PDO(
            "pgsql:dbname={$config->database};host={$config->host};port={$config->port}",
            $config->user,
            $config->password
        );

        if ($config->charset) {
            $pdo->exec("SET NAMES '{$config->charset}'");
        }

        return $pdo;
    }
}
