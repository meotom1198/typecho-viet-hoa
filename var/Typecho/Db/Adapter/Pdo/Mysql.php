<?php

namespace Typecho\Db\Adapter\Pdo;

use Typecho\Config;
use Typecho\Db\Adapter\MysqlTrait;
use Typecho\Db\Adapter\Pdo;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Bộ điều hợp cơ sở dữ liệu Pdo_Mysql
 *
 * @package Db
 */
class Mysql extends Pdo
{
    use MysqlTrait;

    /**
     * Xác định xem bộ chuyển đổi có sẵn không
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return parent::isAvailable() && in_array('mysql', \PDO::getAvailableDrivers());
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
     * Khởi tạo cơ sở dữ liệu
     *
     * @param Config $config Cấu hình cơ sở dữ liệu
     * @access public
     * @return \PDO
     */
    public function init(Config $config): \PDO
    {
        $options = [];
        if (!empty($config->sslCa)) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $config->sslCa;

            if (isset($config->sslVerify)) {
                // FIXME: https://github.com/php/php-src/issues/8577
                $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $config->sslVerify;
            }
        }

        $pdo = new \PDO(
            !empty($config->dsn)
                ? $config->dsn : "mysql:dbname={$config->database};host={$config->host};port={$config->port}",
            $config->user,
            $config->password,
            $options
        );
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        if ($config->charset) {
            $pdo->exec("SET NAMES '{$config->charset}'");
        }

        return $pdo;
    }

    /**
     * Chức năng thoát trích dẫn
     *
     * @param mixed $string Chuỗi cần được thoát
     * @return string
     */
    public function quoteValue($string): string
    {
        return '\'' . str_replace(['\'', '\\'], ['\'\'', '\\\\'], $string) . '\'';
    }
}
