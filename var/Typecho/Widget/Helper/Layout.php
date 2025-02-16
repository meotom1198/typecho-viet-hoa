<?php

namespace Typecho\Widget\Helper;

/**
 * Lớp trợ giúp bố cục HTML
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Layout
{
    /**
     * danh sách phần tử
     *
     * @access private
     * @var array
     */
    private $items = [];

    /**
     * Danh sách thuộc tính biểu mẫu
     *
     * @access private
     * @var array
     */
    private $attributes = [];

    /**
     * Tên thẻ
     *
     * @access private
     * @var string
     */
    private $tagName = 'div';

    /**
     * Nó có tự đóng không?
     *
     * @access private
     * @var boolean
     */
    private $close = false;

    /**
     * Có buộc phải tự đóng không
     *
     * @access private
     * @var boolean
     */
    private $forceClose = null;

    /**
     * dữ liệu nội bộ
     *
     * @access private
     * @var string
     */
    private $html;

    /**
     * nút cha
     *
     * @access private
     * @var Layout
     */
    private $parent;

    /**
     * Hàm tạo, đặt tên nhãn
     *
     * @param string $tagName Tên thẻ
     * @param array|null $attributes Danh sách tài sản
     *
     */
    public function __construct(string $tagName = 'div', ?array $attributes = null)
    {
        $this->setTagName($tagName);

        if (!empty($attributes)) {
            foreach ($attributes as $attributeName => $attributeValue) {
                $this->setAttribute($attributeName, (string)$attributeValue);
            }
        }
    }

    /**
     * Đặt thuộc tính biểu mẫu
     *
     * @param string $attributeName Tên tài sản
     * @param mixed $attributeValue giá trị thuộc tính
     * @return $this
     */
    public function setAttribute(string $attributeName, $attributeValue): Layout
    {
        $this->attributes[$attributeName] = (string) $attributeValue;
        return $this;
    }

    /**
     * Xóa phần tử
     *
     * @param Layout $item yếu tố
     * @return $this
     */
    public function removeItem(Layout $item): Layout
    {
        unset($this->items[array_search($item, $this->items)]);
        return $this;
    }

    /**
     * getItems
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * getTagName
     *
     * @return string
     */
    public function getTagName(): string
    {
        return $this->tagName;
    }

    /**
     * Đặt tên thẻ
     *
     * @param string $tagName tên thẻ
     */
    public function setTagName(string $tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * Xóa một thuộc tính
     *
     * @param string $attributeName Tên tài sản
     * @return $this
     */
    public function removeAttribute(string $attributeName): Layout
    {
        if (isset($this->attributes[$attributeName])) {
            unset($this->attributes[$attributeName]);
        }

        return $this;
    }

    /**
     * Nhận thuộc tính
     *
     * @access public
     *
     * @param string $attributeName tên thuộc tính
     * @return string|null
     */
    public function getAttribute(string $attributeName): ?string
    {
        return $this->attributes[$attributeName] ?? null;
    }

    /**
     * Đặt có tự đóng hay không
     *
     * @param boolean $close Nó có tự đóng không?
     * @return $this
     */
    public function setClose(bool $close): Layout
    {
        $this->forceClose = $close;
        return $this;
    }

    /**
     * Nhận nút cha
     *
     * @return Layout
     */
    public function getParent(): Layout
    {
        return $this->parent;
    }

    /**
     * Đặt nút cha
     *
     * @param Layout $parent nút cha
     * @return $this
     */
    public function setParent(Layout $parent): Layout
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Thêm vào bộ sưu tập các thành phần bố cục
     *
     * @param Layout $parent đối tượng bố trí
     * @return $this
     */
    public function appendTo(Layout $parent): Layout
    {
        $parent->addItem($this);
        return $this;
    }

    /**
     * Thêm phần tử
     *
     * @param Layout $item yếu tố
     * @return $this
     */
    public function addItem(Layout $item): Layout
    {
        $item->setParent($this);
        $this->items[] = $item;
        return $this;
    }

    /**
     * Nhận thuộc tính
     *
     * @param string $name Tên tài sản
     * @return string|null
     */
    public function __get(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Đặt thuộc tính
     *
     * @param string $name Tên tài sản
     * @param string $value giá trị thuộc tính
     */
    public function __set(string $name, string $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Xuất tất cả các phần tử
     */
    public function render()
    {
        if (empty($this->items) && empty($this->html)) {
            $this->close = true;
        }

        if (null !== $this->forceClose) {
            $this->close = $this->forceClose;
        }

        $this->start();
        $this->html();
        $this->end();
    }

    /**
     * thẻ bắt đầu
     */
    public function start()
    {
        /** Nhãn đầu ra */
        echo $this->tagName ? "<{$this->tagName}" : null;

        /** Thuộc tính đầu ra */
        foreach ($this->attributes as $attributeName => $attributeValue) {
            echo " {$attributeName}=\"{$attributeValue}\"";
        }

        /** Hỗ trợ tự đóng */
        if (!$this->close && $this->tagName) {
            echo ">\n";
        }
    }

    /**
     * Đặt dữ liệu nội bộ
     *
     * @param string|null $html dữ liệu nội bộ
     * @return void|$this
     */
    public function html(?string $html = null)
    {
        if (null === $html) {
            if (empty($this->html)) {
                foreach ($this->items as $item) {
                    $item->render();
                }
            } else {
                echo $this->html;
            }
        } else {
            $this->html = $html;
            return $this;
        }
    }

    /**
     * thẻ kết thúc
     *
     * @return void
     */
    public function end()
    {
        if ($this->tagName) {
            echo $this->close ? " />\n" : "</{$this->tagName}>\n";
        }
    }
}
