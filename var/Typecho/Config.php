<?php

namespace Typecho;

/**
 * Lớp quản lý cấu hình
 *
 * @category typecho
 * @package Config
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Config implements \Iterator, \ArrayAccess
{
    /**
     * Cấu hình hiện tại
     *
     * @access private
     * @var array
     */
    private $currentConfig = [];

    /**
     * Khởi tạo cấu hình hiện tại
     *
     * @access public
     * @param array|string|null $config Danh sách cấu hình
     */
    public function __construct($config = [])
    {
        /** Thông số khởi tạo */
        $this->setDefault($config);
    }

    /**
     * Mẫu nhà máy khởi tạo cấu hình hiện tại
     *
     * @access public
     *
     * @param array|string|null $config Danh sách cấu hình
     *
     * @return Config
     */
    public static function factory($config = []): Config
    {
        return new self($config);
    }

    /**
     * Đặt cấu hình mặc định
     *
     * @access public
     *
     * @param mixed $config Thông tin cấu hình
     * @param boolean $replace Có nên thay thế thông tin hiện có hay không
     *
     * @return void
     */
    public function setDefault($config, bool $replace = false)
    {
        if (empty($config)) {
            return;
        }

        /** Thông số khởi tạo */
        if (is_string($config)) {
            parse_str($config, $params);
        } else {
            $params = $config;
        }

        /** Đặt thông số mặc định */
        foreach ($params as $name => $value) {
            if ($replace || !array_key_exists($name, $this->currentConfig)) {
                $this->currentConfig[$name] = $value;
            }
        }
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->currentConfig);
    }

    /**
     * đặt lại con trỏ
     *
     * @access public
     * @return void
     */
    public function rewind(): void
    {
        reset($this->currentConfig);
    }

    /**
     * Trả về giá trị hiện tại
     *
     * @access public
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->currentConfig);
    }

    /**
     * Di chuyển con trỏ lùi lại một vị trí
     *
     * @access public
     * @return void
     */
    public function next(): void
    {
        next($this->currentConfig);
    }

    /**
     * Lấy con trỏ hiện tại
     *
     * @access public
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->currentConfig);
    }

    /**
     * Xác minh xem giá trị hiện tại có đạt đến cuối không
     *
     * @access public
     * @return boolean
     */
    public function valid(): bool
    {
        return false !== $this->current();
    }

    /**
     * Hàm ma thuật nhận giá trị cấu hình
     *
     * @access public
     * @param string $name Tên cấu hình
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->offsetGet($name);
    }

    /**
     * Hàm ma thuật đặt giá trị cấu hình
     *
     * @access public
     * @param string $name Tên cấu hình
     * @param mixed $value giá trị cấu hình
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * Xuất trực tiếp giá trị cấu hình mặc định
     *
     * @access public
     * @param string $name Tên cấu hình
     * @param array|null $args tham số
     * @return void
     */
    public function __call(string $name, ?array $args)
    {
        echo $this->currentConfig[$name];
    }

    /**
     * Xác định xem giá trị cấu hình hiện tại có tồn tại không
     *
     * @access public
     * @param string $name Tên cấu hình
     * @return boolean
     */
    public function __isSet(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * Phương pháp kỳ diệu, in mảng cấu hình hiện tại
     *
     * @access public
     * @return string
     */
    public function __toString(): string
    {
        return serialize($this->currentConfig);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->currentConfig;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->currentConfig[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->currentConfig[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->currentConfig[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->currentConfig[$offset]);
    }
}
