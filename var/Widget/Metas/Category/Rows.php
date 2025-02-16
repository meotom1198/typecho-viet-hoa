<?php

namespace Widget\Metas\Category;

use Typecho\Config;
use Typecho\Db;
use Widget\Base\Metas;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần đầu ra phân loại
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Rows extends Metas
{
    /**
     * cấu trúc phân loại cây
     *
     * @var array
     * @access private
     */
    private $treeViewCategories = [];

    /**
     * _categoryOptions
     *
     * @var mixed
     * @access private
     */
    private $categoryOptions = null;

    /**
     * Danh mục cấp cao nhất
     *
     * @var array
     * @access private
     */
    private $top = [];

    /**
     * Tất cả các bảng băm danh mục
     *
     * @var array
     * @access private
     */
    private $map = [];

    /**
     * dòng chảy tuần tự
     *
     * @var array
     * @access private
     */
    private $orders = [];

    /**
     * Danh sách tất cả các nút con
     *
     * @var array
     * @access private
     */
    private $childNodes = [];

    /**
     * Danh sách tất cả các nút cha
     *
     * @var array
     * @access private
     */
    private $parents = [];

    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault('ignore=0&current=');

        $select = $this->select()->where('type = ?', 'category');

        $categories = $this->db->fetchAll($select->order('table.metas.order', Db::SORT_ASC));
        foreach ($categories as $category) {
            $category['levels'] = 0;
            $this->map[$category['mid']] = $category;
        }

        // Đọc dữ liệu
        foreach ($this->map as $mid => $category) {
            $parent = $category['parent'];

            if (0 != $parent && isset($this->map[$parent])) {
                $this->treeViewCategories[$parent][] = $mid;
            } else {
                $this->top[] = $mid;
            }
        }

        // Độ sâu tiền xử lý
        $this->levelWalkCallback($this->top);
        $this->map = array_map([$this, 'filter'], $this->map);
    }

    /**
     * Lặp lại phân loại tiền xử lý
     *
     * @param array $categories
     * @param array $parents
     */
    private function levelWalkCallback(array $categories, array $parents = [])
    {
        foreach ($parents as $parent) {
            if (!isset($this->childNodes[$parent])) {
                $this->childNodes[$parent] = [];
            }

            $this->childNodes[$parent] = array_merge($this->childNodes[$parent], $categories);
        }

        foreach ($categories as $mid) {
            $this->orders[] = $mid;
            $parent = $this->map[$mid]['parent'];

            if (0 != $parent && isset($this->map[$parent])) {
                $levels = $this->map[$parent]['levels'] + 1;
                $this->map[$mid]['levels'] = $levels;
            }

            $this->parents[$mid] = $parents;

            if (!empty($this->treeViewCategories[$mid])) {
                $new = $parents;
                $new[] = $mid;
                $this->levelWalkCallback($this->treeViewCategories[$mid], $new);
            }
        }
    }

    /**
     * Thực thi chức năng
     *
     * @return void
     */
    public function execute()
    {
        $this->stack = $this->getCategories($this->orders);
    }

    /**
     * treeViewCategories
     *
     * @param mixed $categoryOptions Tùy chọn đầu ra
     */
    public function listCategories($categoryOptions = null)
    {
        // khởi tạo một số biến
        $this->categoryOptions = Config::factory($categoryOptions);
        $this->categoryOptions->setDefault([
            'wrapTag'       => 'ul',
            'wrapClass'     => '',
            'itemTag'       => 'li',
            'itemClass'     => '',
            'showCount'     => false,
            'showFeed'      => false,
            'countTemplate' => '(%d)',
            'feedTemplate'  => '<a href="%s">RSS</a>'
        ]);

        // Giao diện trình cắm
        self::pluginHandle()->trigger($plugged)->listCategories($this->categoryOptions, $this);

        if (!$plugged) {
            $this->stack = $this->getCategories($this->top);

            if ($this->have()) {
                echo '<' . $this->categoryOptions->wrapTag . (empty($this->categoryOptions->wrapClass)
                        ? '' : ' class="' . $this->categoryOptions->wrapClass . '"') . '>';
                while ($this->next()) {
                    $this->treeViewCategoriesCallback();
                }
                echo '</' . $this->categoryOptions->wrapTag . '>';
            }

            $this->stack = $this->map;
        }
    }

    /**
     * Liệt kê các cuộc gọi lại danh mục
     */
    private function treeViewCategoriesCallback(): void
    {
        $categoryOptions = $this->categoryOptions;
        if (function_exists('treeViewCategories')) {
            treeViewCategories($this, $categoryOptions);
            return;
        }

        $classes = [];

        if ($categoryOptions->itemClass) {
            $classes[] = $categoryOptions->itemClass;
        }

        $classes[] = 'category-level-' . $this->levels;

        echo '<' . $categoryOptions->itemTag . ' class="'
            . implode(' ', $classes);

        if ($this->levels > 0) {
            echo ' category-child';
            $this->levelsAlt(' category-level-odd', ' category-level-even');
        } else {
            echo ' category-parent';
        }

        if ($this->mid == $this->parameter->current) {
            echo ' category-active';
        } elseif (
            isset($this->childNodes[$this->mid]) && in_array($this->parameter->current, $this->childNodes[$this->mid])
        ) {
            echo ' category-parent-active';
        }

        echo '"><a href="' . $this->permalink . '">' . $this->name . '</a>';

        if ($categoryOptions->showCount) {
            printf($categoryOptions->countTemplate, intval($this->count));
        }

        if ($categoryOptions->showFeed) {
            printf($categoryOptions->feedTemplate, $this->feedUrl);
        }

        if ($this->children) {
            $this->treeViewCategories();
        }

        echo '</' . $categoryOptions->itemTag . '>';
    }

    /**
     * Đầu ra theo độ sâu còn lại
     *
     * @param ...$args
     */
    public function levelsAlt(...$args)
    {
        $num = count($args);
        $split = $this->levels % $num;
        echo $args[(0 == $split ? $num : $split) - 1];
    }

    /**
     * treeViewCategories
     *
     * @access public
     * @return void
     */
    public function treeViewCategories()
    {
        $children = $this->children;
        if ($children) {
            // Biến bộ nhớ đệm để khôi phục dễ dàng
            $tmp = $this->row;
            $this->sequence++;

            // Đầu ra trước bình luận phụ
            echo '<' . $this->categoryOptions->wrapTag . (empty($this->categoryOptions->wrapClass)
                    ? '' : ' class="' . $this->categoryOptions->wrapClass . '"') . '>';

            foreach ($children as $child) {
                $this->row = $child;
                $this->treeViewCategoriesCallback();
                $this->row = $tmp;
            }

            // Đầu ra sau bình luận phụ
            echo '</' . $this->categoryOptions->wrapTag . '>';

            $this->sequence--;
        }
    }

    /**
     * Đẩy giá trị của mỗi hàng vào ngăn xếp
     *
     * @access public
     * @param array $value giá trị của mỗi hàng
     * @return array
     */
    public function filter(array $value): array
    {
        $value['directory'] = $this->getAllParentsSlug($value['mid']);
        $value['directory'][] = $value['slug'];

        $tmpCategoryTree = $value['directory'];
        $value['directory'] = implode('/', array_map('urlencode', $value['directory']));

        $value = parent::filter($value);
        $value['directory'] = $tmpCategoryTree;

        return $value;
    }

    /**
     * Lấy tên viết tắt của tất cả các nút cha của một danh mục
     *
     * @param mixed $mid
     * @access public
     * @return array
     */
    public function getAllParentsSlug($mid): array
    {
        $parents = [];

        if (isset($this->parents[$mid])) {
            foreach ($this->parents[$mid] as $parent) {
                $parents[] = $this->map[$parent]['slug'];
            }
        }

        return $parents;
    }

    /**
     * Nhận tất cả các nút con theo một danh mục nhất định
     *
     * @param mixed $mid
     * @access public
     * @return array
     */
    public function getAllChildren($mid): array
    {
        return $this->childNodes[$mid] ?? [];
    }

    /**
     * Nhận tất cả các nút cha của một danh mục
     *
     * @param mixed $mid
     * @access public
     * @return array
     */
    public function getAllParents($mid): array
    {
        $parents = [];

        if (isset($this->parents[$mid])) {
            foreach ($this->parents[$mid] as $parent) {
                $parents[] = $this->map[$parent];
            }
        }

        return $parents;
    }

    /**
     * Nhận một danh mục duy nhất
     *
     * @param integer $mid
     * @return mixed
     */
    public function getCategory(int $mid)
    {
        return $this->map[$mid] ?? null;
    }

    /**
     * bình luận phụ
     *
     * @return array
     */
    protected function ___children(): array
    {
        return isset($this->treeViewCategories[$this->mid]) ?
            $this->getCategories($this->treeViewCategories[$this->mid]) : [];
    }

    /**
     * Nhận nhiều danh mục
     *
     * @param mixed $mids
     * @return array
     */
    public function getCategories($mids): array
    {
        $result = [];

        if (!empty($mids)) {
            foreach ($mids as $mid) {
                if (
                    !$this->parameter->ignore
                    || ($this->parameter->ignore != $mid
                        && !$this->hasParent($mid, $this->parameter->ignore))
                ) {
                    $result[] = $this->map[$mid];
                }
            }
        }

        return $result;
    }

    /**
     * Liệu nó có danh mục chính hay không
     *
     * @param mixed $mid
     * @param mixed $parentId
     * @return bool
     */
    public function hasParent($mid, $parentId): bool
    {
        if (isset($this->parents[$mid])) {
            foreach ($this->parents[$mid] as $parent) {
                if ($parent == $parentId) {
                    return true;
                }
            }
        }

        return false;
    }
}
