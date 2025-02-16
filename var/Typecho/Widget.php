<?php

namespace Typecho;

use Typecho\Widget\Helper\EmptyClass;
use Typecho\Widget\Request as WidgetRequest;
use Typecho\Widget\Response as WidgetResponse;
use Typecho\Widget\Terminal;

/**
 * Lớp cơ sở thành phần Typecho
 *
 * @property $sequence
 * @property $length
 * @property-read $request
 * @property-read $response
 * @property-read $parameter
 */
abstract class Widget
{
    /**
     * nhóm đối tượng widget
     *
     * @var array
     */
    private static $widgetPool = [];

    /**
     * bí danh tiện ích
     *
     * @var array
     */
    private static $widgetAlias = [];

    /**
     * yêu cầu
     *
     * @var WidgetRequest
     */
    protected $request;

    /**
     * đối tượng phản hồi
     *
     * @var WidgetResponse
     */
    protected $response;

    /**
     * ngăn xếp dữ liệu
     *
     * @var array
     */
    protected $stack = [];

    /**
     * Giá trị chuỗi con trỏ hàng đợi hiện tại, bắt đầu từ 1
     *
     * @var integer
     */
    protected $sequence = 0;

    /**
     * chiều dài hàng đợi
     *
     * @var integer
     */
    protected $length = 0;

    /**
     * đối tượng cấu hình
     *
     * @var Config
     */
    protected $parameter;

    /**
     * Mỗi hàng của ngăn xếp dữ liệu
     *
     * @var array
     */
    protected $row = [];

    /**
     * Hàm tạo, khởi tạo thành phần
     *
     * @param WidgetRequest $request yêu cầu
     * @param WidgetResponse $response đối tượng phản hồi
     * @param mixed $params Danh sách tham số
     */
    public function __construct(WidgetRequest $request, WidgetResponse $response, $params = null)
    {
        // Đặt chức năng đối tượng bên trong
        $this->request = $request;
        $this->response = $response;
        $this->parameter = Config::factory($params);

        $this->init();
    }

    /**
     * init method
     */
    protected function init()
    {
    }

    /**
     * bí danh tiện ích
     *
     * @param string $widgetClass
     * @param string $aliasClass
     */
    public static function alias(string $widgetClass, string $aliasClass)
    {
        self::$widgetAlias[$widgetClass] = $aliasClass;
    }

    /**
     * Phương thức xuất xưởng để đặt tĩnh lớp vào danh sách
     *
     * @param class-string $alias bí danh thành phần
     * @param mixed $params thông số được thông qua
     * @param mixed $request Thông số mặt trước
     * @param bool|callable $disableSandboxOrCallback gọi lại
     * @return Widget
     */
    public static function widget(
        string $alias,
        $params = null,
        $request = null,
        $disableSandboxOrCallback = true
    ): Widget {
        [$className] = explode('@', $alias);
        $key = Common::nativeClassName($alias);

        if (isset(self::$widgetAlias[$className])) {
            $className = self::$widgetAlias[$className];
        }

        $sandbox = false;

        if ($disableSandboxOrCallback === false || is_callable($disableSandboxOrCallback)) {
            $sandbox = true;
            Request::getInstance()->beginSandbox(new Config($request));
            Response::getInstance()->beginSandbox();
        }

        if ($sandbox || !isset(self::$widgetPool[$key])) {
            $requestObject = new WidgetRequest(Request::getInstance(), isset($request) ? new Config($request) : null);
            $responseObject = new WidgetResponse(Request::getInstance(), Response::getInstance());

            try {
                $widget = new $className($requestObject, $responseObject, $params);
                $widget->execute();

                if ($sandbox && is_callable($disableSandboxOrCallback)) {
                    call_user_func($disableSandboxOrCallback, $widget);
                }
            } catch (Terminal $e) {
                $widget = $widget ?? null;
            } finally {
                if ($sandbox) {
                    Response::getInstance()->endSandbox();
                    Request::getInstance()->endSandbox();

                    return $widget;
                }
            }

            self::$widgetPool[$key] = $widget;
        }

        return self::$widgetPool[$key];
    }

    /**
     * alloc widget instance
     *
     * @param mixed $params
     * @param mixed $request
     * @param bool|callable $disableSandboxOrCallback
     * @return $this
     */
    public static function alloc($params = null, $request = null, $disableSandboxOrCallback = true): Widget
    {
        return self::widget(static::class, $params, $request, $disableSandboxOrCallback);
    }

    /**
     * alloc widget instance with alias
     *
     * @param string|null $alias
     * @param mixed $params
     * @param mixed $request
     * @param bool|callable $disableSandboxOrCallback
     * @return $this
     */
    public static function allocWithAlias(
        ?string $alias,
        $params = null,
        $request = null,
        $disableSandboxOrCallback = true
    ): Widget {
        return self::widget(
            static::class . (isset($alias) ? '@' . $alias : ''),
            $params,
            $request,
            $disableSandboxOrCallback
        );
    }

    /**
     * Thành phần phát hành
     *
     * @param string $alias Tên thành phần
     * @deprecated alias for destroy
     */
    public static function destory(string $alias)
    {
        self::destroy($alias);
    }

    /**
     * Thành phần phát hành
     *
     * @param string|null $alias Tên thành phần
     */
    public static function destroy(?string $alias = null)
    {
        if (Common::nativeClassName(static::class) == 'Typecho_Widget') {
            if (isset($alias)) {
                unset(self::$widgetPool[$alias]);
            } else {
                self::$widgetPool = [];
            }
        } else {
            $alias = static::class . (isset($alias) ? '@' . $alias : '');
            unset(self::$widgetPool[$alias]);
        }
    }

    /**
     * execute function.
     */
    public function execute()
    {
    }

    /**
     * đăng sự kiện kích hoạt
     *
     * @param boolean $condition Điều kiện kích hoạt
     *
     * @return $this|EmptyClass
     */
    public function on(bool $condition)
    {
        if ($condition) {
            return $this;
        } else {
            return new EmptyClass();
        }
    }

    /**
     * Tự chỉ định lớp học
     *
     * @param mixed $variable tên biến
     * @return $this
     */
    public function to(&$variable): Widget
    {
        return $variable = $this;
    }

    /**
     * Định dạng tất cả dữ liệu trong ngăn phân tích cú pháp
     *
     * @param string $format Định dạng dữ liệu
     */
    public function parse(string $format)
    {
        while ($this->next()) {
            echo preg_replace_callback(
                "/\{([_a-z0-9]+)\}/i",
                function (array $matches) {
                    return $this->{$matches[1]};
                },
                $format
            );
        }
    }

    /**
     * Trả về giá trị từng hàng của ngăn xếp
     *
     * @return mixed
     */
    public function next()
    {
        $key = key($this->stack);

        if ($key !== null && isset($this->stack[$key])) {
            $this->row = current($this->stack);
            next($this->stack);
            $this->sequence++;
        } else {
            reset($this->stack);
            $this->sequence = 0;
            return false;
        }

        return $this->row;
    }

    /**
     * Đẩy giá trị của mỗi hàng vào ngăn xếp
     *
     * @param array $value giá trị của mỗi hàng
     * @return mixed
     */
    public function push(array $value)
    {
        // Đặt dữ liệu hàng theo thứ tự
        $this->row = $value;
        $this->length++;

        $this->stack[] = $value;
        return $value;
    }

    /**
     * Đầu ra theo phần còn lại
     *
     * @param mixed ...$args
     */
    public function alt(...$args)
    {
        $num = count($args);
        $split = $this->sequence % $num;
        echo $args[(0 == $split ? $num : $split) - 1];
    }

    /**
     * Trả về khi ngăn xếp trống
     *
     * @return boolean
     */
    public function have(): bool
    {
        return !empty($this->stack);
    }

    /**
     * Chức năng ma thuật, dùng để móc các chức năng khác
     *
     * @param string $name tên hàm
     * @param array $args Thông số chức năng
     */
    public function __call(string $name, array $args)
    {
        $method = 'call' . ucfirst($name);
        self::pluginHandle()->trigger($plugged)->{$method}($this, $args);

        if (!$plugged) {
            echo $this->{$name};
        }
    }

    /**
     * Nhận trình điều khiển trình cắm đối tượng
     *
     * @return Plugin
     */
    public static function pluginHandle(): Plugin
    {
        return Plugin::factory(static::class);
    }

    /**
     * Hàm ma thuật, được sử dụng để lấy các biến nội bộ
     *
     * @param string $name tên biến
     * @return mixed
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->row)) {
            return $this->row[$name];
        } else {
            $method = '___' . $name;

            if (method_exists($this, $method)) {
                return $this->$method();
            } else {
                $return = self::pluginHandle()->trigger($plugged)->{$method}($this);
                if ($plugged) {
                    return $return;
                }
            }
        }

        return null;
    }

    /**
     * Đặt giá trị cho mỗi hàng của ngăn xếp
     *
     * @param string $name Đặt giá trị cho mỗi hàng của ngăn xếp
     * @param mixed $value giá trị tương ứng
     */
    public function __set(string $name, $value)
    {
        $this->row[$name] = $value;
    }

    /**
     * Xác minh giá trị ngăn xếp tồn tại
     *
     * @param string $name
     * @return boolean
     */
    public function __isSet(string $name)
    {
        return isset($this->row[$name]);
    }

    /**
     * Giá trị chuỗi đầu ra
     *
     * @return int
     */
    public function ___sequence(): int
    {
        return $this->sequence;
    }

    /**
     * Độ dài dữ liệu đầu ra
     *
     * @return int
     */
    public function ___length(): int
    {
        return $this->length;
    }

    /**
     * @return WidgetRequest
     */
    public function ___request(): WidgetRequest
    {
        return $this->request;
    }

    /**
     * @return WidgetResponse
     */
    public function ___response(): WidgetResponse
    {
        return $this->response;
    }

    /**
     * @return Config
     */
    public function ___parameter(): Config
    {
        return $this->parameter;
    }
}
