<?php

namespace Typecho\Db;

use Typecho\Config;
use Typecho\Db;

/**
 * Bộ điều hợp cơ sở dữ liệu Typecho
 * Xác định giao diện thích ứng cơ sở dữ liệu chung
 *
 * @package Db
 */
interface Adapter
{
    /**
     * Xác định xem bộ chuyển đổi có sẵn không
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool;

    /**
     * Chức năng kết nối cơ sở dữ liệu
     *
     * @param Config $config Cấu hình cơ sở dữ liệu
     * @return mixed
     */
    public function connect(Config $config);

    /**
     * Nhận phiên bản cơ sở dữ liệu
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string;

    /**
     * Nhận loại cơ sở dữ liệu
     *
     * @return string
     */
    public function getDriver(): string;

    /**
     * Xóa bảng dữ liệu
     *
     * @param string $table Tên bảng dữ liệu
     * @param mixed $handle đối tượng kết nối
     */
    public function truncate(string $table, $handle);

    /**
     * Thực hiện truy vấn cơ sở dữ liệu
     *
     * @param string $truy vấn cơ sở dữ liệu chuỗi SQL
     * @param hỗn hợp đối tượng kết nối $handle
     * @param số nguyên $op trạng thái đọc và ghi cơ sở dữ liệu
     * @param string|null $action hành động cơ sở dữ liệu
     * @param string|null bảng dữ liệu $table
     * @return resource
     */
    public function query(string $query, $handle, int $op = Db::READ, ?string $action = null, ?string $table = null);

    /**
     * Lấy ra một trong các hàng trong truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với giá trị khóa mảng
     *
     * @param resource $resource Truy vấn dữ liệu tài nguyên
     * @return array|null
     */
    public function fetch($resource): ?array;

    /**
     * Đưa ra tất cả các kết quả của truy vấn dữ liệu dưới dạng một mảng, trong đó tên trường tương ứng với các giá trị khóa của mảng
     *
     * @param resource $resource Truy vấn dữ liệu tài nguyên
     * @return array
     */
    public function fetchAll($resource): array;

    /**
     * Lấy một trong các hàng trong truy vấn dữ liệu làm đối tượng, trong đó tên trường tương ứng với thuộc tính đối tượng
     *
     * @param resource $resource Truy vấn dữ liệu tài nguyên
     * @return object|null
     */
    public function fetchObject($resource): ?object;

    /**
     * Chức năng thoát trích dẫn
     *
     * @param mixed $string Chuỗi cần được thoát
     * @return string
     */
    public function quoteValue($string): string;

    /**
     * Lọc trích dẫn đối tượng
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string;

    /**
     * Câu lệnh truy vấn tổng hợp
     *
     * @access public
     * @param array $sql Mảng từ vựng của đối tượng truy vấn
     * @return string
     */
    public function parseSelect(array $sql): string;

    /**
     * Lấy số hàng bị ảnh hưởng bởi truy vấn cuối cùng
     *
     * @param resource $resource Truy vấn dữ liệu tài nguyên
     * @param mixed $handle đối tượng kết nối
     * @return integer
     */
    public function affectedRows($resource, $handle): int;

    /**
     * Nhận giá trị khóa chính được trả về từ lần chèn cuối cùng
     *
     * @param resource $resource Truy vấn dữ liệu tài nguyên
     * @param mixed $handle đối tượng kết nối
     * @return integer
     */
    public function lastInsertId($resource, $handle): int;
}
