<?php

namespace Typecho;

use Typecho\Db\Adapter;
use Typecho\Db\Query;
use Typecho\Db\Exception as DbException;

/**
 * Lớp chứa các phương thức hỗ trợ lấy dữ liệu.
 * phải được xác định__TYPECHO_DB_HOST__, __TYPECHO_DB_PORT__, __TYPECHO_DB_NAME__,
 * __TYPECHO_DB_USER__, __TYPECHO_DB_PASS__, __TYPECHO_DB_CHAR__
 *
 * @package Db
 */
class Db
{
    /** Đọc cơ sở dữ liệu */
    public const READ = 1;

    /** Ghi vào cơ sở dữ liệu */
    public const WRITE = 2;

    /** Thứ tự tăng dần */
    public const SORT_ASC = 'ASC';

    /** thứ tự giảm dần */
    public const SORT_DESC = 'DESC';

    /** Phương thức nối trong bảng */
    public const INNER_JOIN = 'INNER';

    /** Kết nối ngoài bảng */
    public const OUTER_JOIN = 'OUTER';

    /** Phương thức nối trái bảng */
    public const LEFT_JOIN = 'LEFT';

    /** Phương thức nối bảng bên phải */
    public const RIGHT_JOIN = 'RIGHT';

    /** Các thao tác truy vấn cơ sở dữ liệu */
    public const SELECT = 'SELECT';

    /** Thao tác cập nhật cơ sở dữ liệu */
    public const UPDATE = 'UPDATE';

    /** Thao tác chèn cơ sở dữ liệu */
    public const INSERT = 'INSERT';

    /** Thao tác xóa cơ sở dữ liệu */
    public const DELETE = 'DELETE';

    /**
     * bộ điều hợp cơ sở dữ liệu
     * @var Adapter
     */
    private $adapter;

    /**
     * Cấu hình mặc định
     *
     * @var array
     */
    private $config;

    /**
     * Đã kết nối
     *
     * @access private
     * @var array
     */
    private $connectedPool;

    /**
     * tiền tố
     *
     * @access private
     * @var string
     */
    private $prefix;

    /**
     * tên bộ chuyển đổi
     *
     * @access private
     * @var string
     */
    private $adapterName;

    /**
     * đối tượng cơ sở dữ liệu được khởi tạo
     * @var Db
     */
    private static $instance;

    /**
     * Trình xây dựng lớp cơ sở dữ liệu
     *
     * @param mixed $adapterName tên bộ chuyển đổi
     * @param string $prefix tiền tố
     *
     * @throws DbException
     */
    public function __construct($adapterName, string $prefix = 'typecho_')
    {
        /** Nhận tên bộ chuyển đổi */
        $adapterName = $adapterName == 'Mysql' ? 'Mysqli' : $adapterName;
        $this->adapterName = $adapterName;

        /** bộ điều hợp cơ sở dữ liệu */
        $adapterName = '\Typecho\Db\Adapter\\' . str_replace('_', '\\', $adapterName);

        if (!call_user_func([$adapterName, 'isAvailable'])) {
            throw new DbException("Adapter {$adapterName} is not available");
        }

        $this->prefix = $prefix;

        /** Khởi tạo các biến nội bộ */
        $this->connectedPool = [];

        $this->config = [
            self::READ => [],
            self::WRITE => []
        ];

        // Khởi tạo đối tượng bộ chuyển đổi
        $this->adapter = new $adapterName();
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * Nhận tên bộ chuyển đổi
     *
     * @access public
     * @return string
     */
    public function getAdapterName(): string
    {
        return $this->adapterName;
    }

    /**
     * Nhận tiền tố bảng
     *
     * @access public
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param Config $config
     * @param int $op
     */
    public function addConfig(Config $config, int $op)
    {
        if ($op & self::READ) {
            $this->config[self::READ][] = $config;
        }

        if ($op & self::WRITE) {
            $this->config[self::WRITE][] = $config;
        }
    }

    /**
     * getConfig
     *
     * @param int $op
     *
     * @return Config
     * @throws DbException
     */
    public function getConfig(int $op): Config
    {
        if (empty($this->config[$op])) {
            /** DbException */
            throw new DbException('Missing Database Connection');
        }

        $key = array_rand($this->config[$op]);
        return $this->config[$op][$key];
    }

    /**
     * Đặt lại nhóm kết nối
     *
     * @return void
     */
    public function flushPool()
    {
        $this->connectedPool = [];
    }

    /**
     * Chọn cơ sở dữ liệu
     *
     * @param int $op
     *
     * @return mixed
     * @throws DbException
     */
    public function selectDb(int $op)
    {
        if (!isset($this->connectedPool[$op])) {
            $selectConnectionConfig = $this->getConfig($op);
            $selectConnectionHandle = $this->adapter->connect($selectConnectionConfig);
            $this->connectedPool[$op] = $selectConnectionHandle;
        }

        return $this->connectedPool[$op];
    }

    /**
     * Lấy đối tượng khởi tạo SQL Lexical Builder
     *
     * @return Query
     */
    public function sql(): Query
    {
        return new Query($this->adapter, $this->prefix);
    }

    /**
     * Cung cấp hỗ trợ cho nhiều cơ sở dữ liệu
     *
     * @access public
     * @param array $config Phiên bản cơ sở dữ liệu
     * @param integer $op Hoạt động cơ sở dữ liệu
     * @return void
     */
    public function addServer(array $config, int $op)
    {
        $this->addConfig(Config::factory($config), $op);
        $this->flushPool();
    }

    /**
     * Nhận phiên bản
     *
     * @param int $op
     *
     * @return string
     * @throws DbException
     */
    public function getVersion(int $op = self::READ): string
    {
        return $this->adapter->getVersion($this->selectDb($op));
    }

    /**
     * Đặt đối tượng cơ sở dữ liệu mặc định
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @return void
     */
    public static function set(Db $db)
    {
        self::$instance = $db;
    }

    /**
     * Lấy đối tượng khởi tạo cơ sở dữ liệu
     * Sử dụng các biến tĩnh để lưu trữ đối tượng cơ sở dữ liệu đã được khởi tạo để đảm bảo rằng kết nối dữ liệu chỉ được thực hiện một lần
     * @return Db
     * @throws DbException
     */
    public static function get(): Db
    {
        if (empty(self::$instance)) {
            /** DbException */
            throw new DbException('Missing Database Object');
        }

        return self::$instance;
    }

    /**
     * Chọn trường truy vấn
     *
     * @param ...$ags
     *
     * @return Query
     * @throws DbException
     */
    public function select(...$ags): Query
    {
        $this->selectDb(self::READ);

        $args = func_get_args();
        return call_user_func_array([$this->sql(), 'select'], $args ?: ['*']);
    }

    /**
     * Thao tác cập nhật bản ghi (UPDATE)
     *
     * @param string $table Các bảng có bản ghi cần được cập nhật
     *
     * @return Query
     * @throws DbException
     */
    public function update(string $table): Query
    {
        $this->selectDb(self::WRITE);

        return $this->sql()->update($table);
    }

    /**
     * Thao tác xóa bản ghi (DELETE)
     *
     * @param string $table Bảng có bản ghi cần xóa
     *
     * @return Query
     * @throws DbException
     */
    public function delete(string $table): Query
    {
        $this->selectDb(self::WRITE);

        return $this->sql()->delete($table);
    }

    /**
     * Thao tác chèn bản ghi (INSERT)
     *
     * @param string $table Bảng cần chèn bản ghi vào
     *
     * @return Query
     * @throws DbException
     */
    public function insert(string $table): Query
    {
        $this->selectDb(self::WRITE);

        return $this->sql()->insert($table);
    }

    /**
     * @param $table
     * @throws DbException
     */
    public function truncate($table)
    {
        $table = preg_replace("/^table\./", $this->prefix, $table);
        $this->adapter->truncate($table, $this->selectDb(self::WRITE));
    }

    /**
     * Thực hiện câu lệnh truy vấn
     *
     * @param mixed $query Câu lệnh truy vấn hoặc đối tượng truy vấn
     * @param int $op Trạng thái đọc và ghi cơ sở dữ liệu
     * @param string $action Hành động vận hành
     *
     * @return mixed
     * @throws DbException
     */
    public function query($query, int $op = self::READ, string $action = self::SELECT)
    {
        $table = null;

        /** Thực hiện truy vấn trong bộ chuyển đổi */
        if ($query instanceof Query) {
            $action = $query->getAttribute('action');
            $table = $query->getAttribute('table');
            $op = (self::UPDATE == $action || self::DELETE == $action
                || self::INSERT == $action) ? self::WRITE : self::READ;
        } elseif (!is_string($query)) {
            /** Nếu truy vấn không phải là một đối tượng hay một chuỗi, thì truy vấn đó sẽ được đánh giá là một bộ điều khiển tài nguyên truy vấn và được trả về trực tiếp. */
            return $query;
        }

        /** Chọn nhóm kết nối */
        $handle = $this->selectDb($op);

        /** Gửi truy vấn */
        $resource = $this->adapter->query($query instanceof Query ?
            $query->prepare($query) : $query, $handle, $op, $action, $table);

        if ($action) {
            // Trả về tài nguyên tương ứng dựa trên hành động truy vấn
            switch ($action) {
                case self::UPDATE:
                case self::DELETE:
                    return $this->adapter->affectedRows($resource, $handle);
                case self::INSERT:
                    return $this->adapter->lastInsertId($resource, $handle);
                case self::SELECT:
                default:
                    return $resource;
            }
        } else {
            // 如果直接执行查询语句则返回资源
            return $resource;
        }
    }

    /**
     * tìm nạp tất cả các hàng cùng một lúc
     *
     * @param mixed $query Đối tượng truy vấn
     * @param callable|null $filter Hàm lọc hàng chuyển từng hàng của truy vấn vào bộ lọc được chỉ định làm tham số đầu tiên.
     *
     * @return array
     * @throws DbException
     */
    public function fetchAll($query, ?callable $filter = null): array
    {
        // Thực hiện truy vấn
        $resource = $this->query($query);
        $result = $this->adapter->fetchAll($resource);

        return $filter ? array_map($filter, $result) : $result;
    }

    /**
     * Lấy ra từng hàng một
     *
     * @param mixed $query Đối tượng truy vấn
     * @param callable|null $filter Hàm lọc hàng chuyển từng hàng của truy vấn vào bộ lọc được chỉ định làm tham số đầu tiên.
     * @return array|null
     * @throws DbException
     */
    public function fetchRow($query, ?callable $filter = null): ?array
    {
        $resource = $this->query($query);

        return ($rows = $this->adapter->fetch($resource)) ?
            ($filter ? call_user_func($filter, $rows) : $rows) :
            null;
    }

    /**
     * Lấy ra một đồ vật tại một thời điểm
     *
     * @param mixed $query Đối tượng truy vấn
     * @param array|null $filter Hàm lọc hàng chuyển từng hàng của truy vấn vào bộ lọc được chỉ định làm tham số đầu tiên.
     * @return object|null
     * @throws DbException
     */
    public function fetchObject($query, ?array $filter = null): ?object
    {
        $resource = $this->query($query);

        return ($rows = $this->adapter->fetchObject($resource)) ?
            ($filter ? call_user_func($filter, $rows) : $rows) :
            null;
    }
}
