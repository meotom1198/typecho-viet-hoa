<?php

namespace Typecho\Widget\Helper\Form;

use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Lớp trừu tượng phần tử biểu mẫu
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
abstract class Element extends Layout
{
    /**
     * Id duy nhất của Singleton
     *
     * @access protected
     * @var integer
     */
    protected static $uniqueId = 0;

    /**
     * thùng chứa phần tử biểu mẫu
     *
     * @access public
     * @var Layout
     */
    public $container;

    /**
     * Trường nhập liệu
     *
     * @access public
     * @var Layout
     */
    public $input;

    /**
     * inputs
     *
     * @var array
     * @access public
     */
    public $inputs = [];

    /**
     * tiêu đề biểu mẫu
     *
     * @access public
     * @var Layout
     */
    public $label;

    /**
     * trình xác nhận mẫu
     *
     * @access public
     * @var array
     */
    public $rules = [];

    /**
     * tên mẫu
     *
     * @access public
     * @var string
     */
    public $name;

    /**
     * giá trị biểu mẫu
     *
     * @access public
     * @var mixed
     */
    public $value;

    /**
     * Mô tả biểu mẫu
     *
     * @access private
     * @var string
     */
    protected $description;

    /**
     * tin nhắn mẫu
     *
     * @access protected
     * @var string
     */
    protected $message;

    /**
     * Nhiều dòng đầu vào
     *
     * @access public
     * @var array()
     */
    protected $multiline = [];

    /**
     * Người xây dựng
     *
     * @param string|null $name Tên đầu vào của biểu mẫu
     * @param array|null $options Tùy chọn
     * @param mixed $value giá trị mặc định của biểu mẫu
     * @param string|null $label tiêu đề biểu mẫu
     * @param string|null $description Mô tả biểu mẫu
     * @return void
     */
    public function __construct(
        ?string $name = null,
        ?array $options = null,
        $value = null,
        ?string $label = null,
        ?string $description = null
    ) {
        /** Tạo phần tử html và đặt class */
        parent::__construct(
            'ul',
            ['class' => 'typecho-option', 'id' => 'typecho-option-item-' . $name . '-' . self::$uniqueId]
        );

        $this->name = $name;
        self::$uniqueId++;

        /** Chạy chức năng ban đầu tùy chỉnh */
        $this->init();

        /** Khởi tạo tiêu đề biểu mẫu */
        if (null !== $label) {
            $this->label($label);
        }

        /** Khởi tạo các mục biểu mẫu */
        $this->input = $this->input($name, $options);

        /** Khởi tạo giá trị biểu mẫu */
        if (null !== $value) {
            $this->value($value);
        }

        /** Mô tả biểu mẫu khởi tạo */
        if (null !== $description) {
            $this->description($description);
        }
    }

    /**
     * Chức năng ban đầu tùy chỉnh
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Tạo tiêu đề biểu mẫu
     *
     * @param string $value chuỗi tiêu đề
     * @return $this
     */
    public function label(string $value): Element
    {
        /** Tạo phần tử tiêu đề */
        if (empty($this->label)) {
            $this->label = new Layout('label', ['class' => 'typecho-label']);
            $this->container($this->label);
        }

        $this->label->html($value);
        return $this;
    }

    /**
     * Thêm phần tử vào vùng chứa
     *
     * @param Layout $item phần tử biểu mẫu
     * @return $this
     */
    public function container(Layout $item): Element
    {
        /** Tạo vùng chứa biểu mẫu */
        if (empty($this->container)) {
            $this->container = new Layout('li');
            $this->addItem($this->container);
        }

        $this->container->addItem($item);
        return $this;
    }

    /**
     * Khởi tạo mục đầu vào hiện tại
     *
     * @param string|null $name Tên phần tử biểu mẫu
     * @param array|null $options Tùy chọn
     * @return Layout|null
     */
    abstract public function input(?string $name = null, ?array $options = null): ?Layout;

    /**
     * Đặt giá trị phần tử biểu mẫu
     *
     * @param mixed $value giá trị phần tử biểu mẫu
     * @return Element
     */
    public function value($value): Element
    {
        $this->value = $value;
        $this->inputValue($value ?? '');
        return $this;
    }

    /**
     * Đặt thông tin mô tả
     *
     * @param string $description Thông tin mô tả
     * @return Element
     */
    public function description(string $description): Element
    {
        /** Tạo phần tử mô tả */
        if (empty($this->description)) {
            $this->description = new Layout('p', ['class' => 'description']);
            $this->container($this->description);
        }

        $this->description->html($description);
        return $this;
    }

    /**
     * Đặt thông tin nhắc nhở
     *
     * @param string $message Tin nhắn nhắc nhở
     * @return Element
     */
    public function message(string $message): Element
    {
        if (empty($this->message)) {
            $this->message = new Layout('p', ['class' => 'message error']);
            $this->container($this->message);
        }

        $this->message->html($message);
        return $this;
    }

    /**
     * chế độ đầu ra đa dòng
     *
     * @return Layout
     */
    public function multiline(): Layout
    {
        $item = new Layout('span');
        $this->multiline[] = $item;
        return $item;
    }

    /**
     * chế độ đầu ra đa dòng
     *
     * @return Element
     */
    public function multiMode(): Element
    {
        foreach ($this->multiline as $item) {
            $item->setAttribute('class', 'multiline');
        }
        return $this;
    }

    /**
     * Thêm trình xác thực
     *
     * @param mixed ...$rules
     * @return $this
     */
    public function addRule(...$rules): Element
    {
        $this->rules[] = $rules;
        return $this;
    }

    /**
     * Đặt giá trị thuộc tính cho tất cả các mục đầu vào một cách thống nhất
     *
     * @param string $attributeName
     * @param mixed $attributeValue
     */
    public function setInputsAttribute(string $attributeName, $attributeValue)
    {
        foreach ($this->inputs as $input) {
            $input->setAttribute($attributeName, $attributeValue);
        }
    }

    /**
     * Đặt giá trị phần tử biểu mẫu
     *
     * @param mixed $value giá trị phần tử biểu mẫu
     */
    abstract protected function inputValue($value);

    /**
     * filterValue
     *
     * @param string $value
     * @return string
     */
    protected function filterValue(string $value): string
    {
        if (preg_match_all('/[_0-9a-z-]+/i', $value, $matches)) {
            return implode('-', $matches[0]);
        }

        return '';
    }
}
