<?php

namespace Typecho\Db;

use Typecho\Db;

/**
 * Lớp xây dựng câu lệnh truy vấn cơ sở dữ liệu Typecho
 * Cách sử dụng:
 * $query = new Query();    //Hoặc sử dụng phương thức sql được DB tích lũy để trả về đối tượng đã được khởi tạo
 * $query->select('posts', 'post_id, post_title')
 * ->where('post_id = %d', 1)
 * ->limit(1);
 * echo $query;
 * Kết quả in ra sẽ là
 * SELECT post_id, post_title FROM posts WHERE 1=1 AND post_id = 1 LIMIT 1
 *
 *
 * @package Db
 */
class Query
{
    /** khóa cơ sở dữ liệu */
    private const KEYWORDS = '*PRIMARY|AND|OR|LIKE|ILIKE|BINARY|BY|DISTINCT|AS|IN|IS|NULL';

    /**
     * Trường mặc định
     *
     * @var array
     * @access private
     */
    private static $default = [
        'action' => null,
        'table'  => null,
        'fields' => '*',
        'join'   => [],
        'where'  => null,
        'limit'  => null,
        'offset' => null,
        'order'  => null,
        'group'  => null,
        'having' => null,
        'rows'   => [],
    ];

    /**
     * bộ điều hợp cơ sở dữ liệu
     *
     * @var Adapter
     */
    private $adapter;

    /**
     * Cấu trúc tiền câu lệnh truy vấn, bao gồm các mảng, có thể dễ dàng kết hợp thành các chuỗi truy vấn SQL
     *
     * @var array
     */
    private $sqlPreBuild;

    /**
     * tiền tố
     *
     * @access private
     * @var string
     */
    private $prefix;

    /**
     * @var array
     */
    private $params = [];

    /**
     * Trình xây dựng, tham chiếu bộ điều hợp cơ sở dữ liệu dưới dạng dữ liệu nội bộ
     *
     * Bộ chuyển đổi cơ sở dữ liệu $adapter @param
     * @param chuỗi tiền tố $
     */
    public function __construct(Adapter $adapter, string $prefix)
    {
        $this->adapter = &$adapter;
        $this->prefix = $prefix;

        $this->sqlPreBuild = self::$default;
    }

    /**
     * set default params
     *
     * @param array $default
     */
    public static function setDefault(array $default)
    {
        self::$default = array_merge(self::$default, $default);
    }

    /**
     * Nhận thông số
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Nhận giá trị thuộc tính chuỗi truy vấn
     *
     * @access public
     * @param string $attributeName Tên tài sản
     * @return string
     */
    public function getAttribute(string $attributeName): ?string
    {
        return $this->sqlPreBuild[$attributeName] ?? null;
    }

    /**
     * Xóa giá trị thuộc tính chuỗi truy vấn
     *
     * @access public
     * @param string $attributeName Tên tài sản
     * @return Query
     */
    public function cleanAttribute(string $attributeName): Query
    {
        if (isset($this->sqlPreBuild[$attributeName])) {
            $this->sqlPreBuild[$attributeName] = self::$default[$attributeName];
        }
        return $this;
    }

    /**
     * tham gia bảng
     *
     * @param string $table Các bảng được nối
     * @param string $condition Điều kiện kết nối
     * @param string $op Phương thức kết nối (LEFT, RIGHT, INNER)
     * @return Query
     */
    public function join(string $table, string $condition, string $op = Db::INNER_JOIN): Query
    {
        $this->sqlPreBuild['join'][] = [$this->filterPrefix($table), $this->filterColumn($condition), $op];
        return $this;
    }

    /**
     * Tiền tố bảng lọc, bao gồm bảng.
     *
     * @param string $string Chuỗi cần được phân tích cú pháp
     * @return string
     */
    private function filterPrefix(string $string): string
    {
        return (0 === strpos($string, 'table.')) ? substr_replace($string, $this->prefix, 0, 6) : $string;
    }

    /**
     * Lọc các giá trị khóa mảng
     *
     * @access private
     * @param string $str Giá trị trường cần xử lý
     * @return string
     */
    private function filterColumn(string $str): string
    {
        $str = $str . ' 0';
        $length = strlen($str);
        $lastIsAlnum = false;
        $result = '';
        $word = '';
        $split = '';
        $quotes = 0;

        for ($i = 0; $i < $length; $i++) {
            $cha = $str[$i];

            if (ctype_alnum($cha) || false !== strpos('_*', $cha)) {
                if (!$lastIsAlnum) {
                    if (
                        $quotes > 0 && !ctype_digit($word) && '.' != $split
                        && false === strpos(self::KEYWORDS, strtoupper($word))
                    ) {
                        $word = $this->adapter->quoteColumn($word);
                    } elseif ('.' == $split && 'table' == $word) {
                        $word = $this->prefix;
                        $split = '';
                    }

                    $result .= $word . $split;
                    $word = '';
                    $quotes = 0;
                }

                $word .= $cha;
                $lastIsAlnum = true;
            } else {
                if ($lastIsAlnum) {
                    if (0 == $quotes) {
                        if (false !== strpos(' ,)=<>.+-*/', $cha)) {
                            $quotes = 1;
                        } elseif ('(' == $cha) {
                            $quotes = - 1;
                        }
                    }

                    $split = '';
                }

                $split .= $cha;
                $lastIsAlnum = false;
            }

        }

        return $result;
    }

    /**
     * VÀ câu lệnh truy vấn có điều kiện
     *
     * @param ...$args
     * @return $this
     */
    public function where(...$args): Query
    {
        [$condition] = $args;
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->sqlPreBuild['where']) ? ' WHERE ' : ' AND';

        if (count($args) <= 1) {
            $this->sqlPreBuild['where'] .= $operator . ' (' . $condition . ')';
        } else {
            array_shift($args);
            $this->sqlPreBuild['where'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * Thông số thoát
     *
     * @param array $values
     * @access protected
     * @return array
     */
    protected function quoteValues(array $values): array
    {
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = '(' . implode(',', array_map([$this, 'quoteValue'], $value)) . ')';
            } else {
                $value = $this->quoteValue($value);
            }
        }

        return $values;
    }

    /**
     * Trì hoãn việc trốn thoát
     *
     * @param $value
     * @return string
     */
    public function quoteValue($value): string
    {
        $this->params[] = $value;
        return '#param:' . (count($this->params) - 1) . '#';
    }

    /**
     * HOẶC câu lệnh truy vấn có điều kiện
     *
     * @param ...$args
     * @return Query
     */
    public function orWhere(...$args): Query
    {
        [$condition] = $args;
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->sqlPreBuild['where']) ? ' WHERE ' : ' OR';

        if (func_num_args() <= 1) {
            $this->sqlPreBuild['where'] .= $operator . ' (' . $condition . ')';
        } else {
            array_shift($args);
            $this->sqlPreBuild['where'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * Giới hạn hàng truy vấn
     *
     * @param mixed $limit Số hàng được truy vấn
     * @return Query
     */
    public function limit($limit): Query
    {
        $this->sqlPreBuild['limit'] = intval($limit);
        return $this;
    }

    /**
     * Phần bù số hàng truy vấn
     *
     * @param mixed $offset Số lượng hàng được bù đắp
     * @return Query
     */
    public function offset($offset): Query
    {
        $this->sqlPreBuild['offset'] = intval($offset);
        return $this;
    }

    /**
     * Truy vấn trang
     *
     * @param mixed $page Số trang
     * @param mixed $pageSize dòng trên mỗi trang
     * @return Query
     */
    public function page($page, $pageSize): Query
    {
        $pageSize = intval($pageSize);
        $this->sqlPreBuild['limit'] = $pageSize;
        $this->sqlPreBuild['offset'] = (max(intval($page), 1) - 1) * $pageSize;
        return $this;
    }

    /**
     * Chỉ định các cột và giá trị của chúng cần được viết
     *
     * @param array $rows
     * @return Query
     */
    public function rows(array $rows): Query
    {
        foreach ($rows as $key => $row) {
            $this->sqlPreBuild['rows'][$this->filterColumn($key)]
                = is_null($row) ? 'NULL' : $this->adapter->quoteValue($row);
        }
        return $this;
    }

    /**
     * Chỉ định các cột và giá trị của chúng cần viết
     * Dòng đơn không thoát khỏi dấu ngoặc kép
     *
     * @param string $key tên cột
     * @param hỗn hợp giá trị $value được chỉ định
     * @param bool $escape có thoát hay không
     * @return Query
     */
    public function expression(string $key, $value, bool $escape = true): Query
    {
        $this->sqlPreBuild['rows'][$this->filterColumn($key)] = $escape ? $this->filterColumn($value) : $value;
        return $this;
    }

    /**
     * sắp xếp thứ tự(ORDER BY)
     *
     * @param string $orderBy chỉ mục được sắp xếp
     * @param string $sort Sắp xếp theo (ASC, DESC)
     * @return Query
     */
    public function order(string $orderBy, string $sort = Db::SORT_ASC): Query
    {
        if (empty($this->sqlPreBuild['order'])) {
            $this->sqlPreBuild['order'] = ' ORDER BY ';
        } else {
            $this->sqlPreBuild['order'] .= ', ';
        }

        $this->sqlPreBuild['order'] .= $this->filterColumn($orderBy) . (empty($sort) ? null : ' ' . $sort);
        return $this;
    }

    /**
     * thu thập bộ sưu tập (GROUP BY)
     *
     * @param string $key Giá trị khóa tổng hợp
     * @return Query
     */
    public function group(string $key): Query
    {
        $this->sqlPreBuild['group'] = ' GROUP BY ' . $this->filterColumn($key);
        return $this;
    }

    /**
     * @param string $condition
     * @param ...$args
     * @return $this
     */
    public function having(string $condition, ...$args): Query
    {
        $condition = str_replace('?', "%s", $this->filterColumn($condition));
        $operator = empty($this->sqlPreBuild['having']) ? ' HAVING ' : ' AND';

        if (count($args) == 0) {
            $this->sqlPreBuild['having'] .= $operator . ' (' . $condition . ')';
        } else {
            $this->sqlPreBuild['having'] .= $operator . ' (' . vsprintf($condition, $this->quoteValues($args)) . ')';
        }

        return $this;
    }

    /**
     * Chọn trường truy vấn
     *
     * @param mixed ...$args Trường truy vấn
     * @return $this
     */
    public function select(...$args): Query
    {
        $this->sqlPreBuild['action'] = Db::SELECT;

        $this->sqlPreBuild['fields'] = $this->getColumnFromParameters($args);
        return $this;
    }

    /**
     * Tổng hợp các trường truy vấn từ các tham số
     *
     * @access private
     * @param array $parameters
     * @return string
     */
    private function getColumnFromParameters(array $parameters): string
    {
        $fields = [];

        foreach ($parameters as $value) {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $fields[] = $key . ' AS ' . $val;
                }
            } else {
                $fields[] = $value;
            }
        }

        return $this->filterColumn(implode(' , ', $fields));
    }

    /**
     * Thao tác ghi truy vấn (SELECT)
     *
     * @param string $table Bảng truy vấn
     * @return Query
     */
    public function from(string $table): Query
    {
        $this->sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * Thao tác cập nhật bản ghi (UPDATE)
     *
     * @param string $table Các bảng có bản ghi cần được cập nhật
     * @return Query
     */
    public function update(string $table): Query
    {
        $this->sqlPreBuild['action'] = Db::UPDATE;
        $this->sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * thao tác xóa bản ghi (DELETE)
     *
     * @param string $table Bảng có bản ghi cần xóa
     * @return Query
     */
    public function delete(string $table): Query
    {
        $this->sqlPreBuild['action'] = Db::DELETE;
        $this->sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * thao tác chèn bản ghi (INSERT)
     *
     * @param string $table Bảng cần chèn bản ghi vào
     * @return Query
     */
    public function insert(string $table): Query
    {
        $this->sqlPreBuild['action'] = Db::INSERT;
        $this->sqlPreBuild['table'] = $this->filterPrefix($table);
        return $this;
    }

    /**
     * @param string $query
     * @return string
     */
    public function prepare(string $query): string
    {
        $params = $this->params;
        $adapter = $this->adapter;

        return preg_replace_callback("/#param:([0-9]+)#/", function ($matches) use ($params, $adapter) {
            if (array_key_exists($matches[1], $params)) {
                return is_null($params[$matches[1]]) ? 'NULL' : $adapter->quoteValue($params[$matches[1]]);
            } else {
                return $matches[0];
            }
        }, $query);
    }

    /**
     * Xây dựng câu lệnh truy vấn cuối cùng
     *
     * @return string
     */
    public function __toString()
    {
        switch ($this->sqlPreBuild['action']) {
            case Db::SELECT:
                return $this->adapter->parseSelect($this->sqlPreBuild);
            case Db::INSERT:
                return 'INSERT INTO '
                    . $this->sqlPreBuild['table']
                    . '(' . implode(' , ', array_keys($this->sqlPreBuild['rows'])) . ')'
                    . ' VALUES '
                    . '(' . implode(' , ', array_values($this->sqlPreBuild['rows'])) . ')'
                    . $this->sqlPreBuild['limit'];
            case Db::DELETE:
                return 'DELETE FROM '
                    . $this->sqlPreBuild['table']
                    . $this->sqlPreBuild['where'];
            case Db::UPDATE:
                $columns = [];
                if (isset($this->sqlPreBuild['rows'])) {
                    foreach ($this->sqlPreBuild['rows'] as $key => $val) {
                        $columns[] = "$key = $val";
                    }
                }

                return 'UPDATE '
                    . $this->sqlPreBuild['table']
                    . ' SET ' . implode(' , ', $columns)
                    . $this->sqlPreBuild['where'];
            default:
                return null;
        }
    }
}
