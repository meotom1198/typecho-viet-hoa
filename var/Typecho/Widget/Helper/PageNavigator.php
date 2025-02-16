<?php

namespace Typecho\Widget\Helper;

use Typecho\Widget\Exception;

/**
 * Lớp trừu tượng phân trang nội dung
 *
 * @package Widget
 */
abstract class PageNavigator
{
    /**
     * tổng số hồ sơ
     *
     * @var integer
     */
    protected $total;

    /**
     * Tổng số trang
     *
     * @var integer
     */
    protected $totalPage;

    /**
     * trang hiện tại
     *
     * @var integer
     */
    protected $currentPage;

    /**
     * Nội dung mỗi trang
     *
     * @var integer
     */
    protected $pageSize;

    /**
     * Mẫu liên kết trang
     *
     * @var string
     */
    protected $pageTemplate;

    /**
     * liên kết neo
     *
     * @var string
     */
    protected $anchor;

    /**
     * Trình giữ chỗ trang
     *
     * @var mixed
     */
    protected $pageHolder = ['{page}', '%7Bpage%7D'];

    /**
     * Trình xây dựng, khởi tạo thông tin cơ bản của trang
     *
     * @param integer $total tổng số hồ sơ
     * @param integer $currentPage trang hiện tại
     * @param integer $pageSize Số lượng bản ghi trên mỗi trang
     * @param string $pageTemplate Mẫu liên kết trang
     * @throws Exception
     */
    public function __construct(int $total, int $currentPage, int $pageSize, string $pageTemplate)
    {
        $this->total = $total;
        $this->totalPage = ceil($total / $pageSize);
        $this->currentPage = $currentPage;
        $this->pageSize = $pageSize;
        $this->pageTemplate = $pageTemplate;

        if (($currentPage > $this->totalPage || $currentPage < 1) && $total > 0) {
            throw new Exception('Page Not Exists', 404);
        }
    }

    /**
     * Đặt trình giữ chỗ trang
     *
     * @param string $holder Trình giữ chỗ trang
     */
    public function setPageHolder(string $holder)
    {
        $this->pageHolder = ['{' . $holder . '}',
            str_replace(['{', '}'], ['%7B', '%7D'], $holder)];
    }

    /**
     * Đặt điểm neo
     *
     * @param string $anchor điểm neo
     */
    public function setAnchor(string $anchor)
    {
        $this->anchor = '#' . $anchor;
    }

    /**
     * Phương thức đầu ra
     *
     * @throws Exception
     */
    public function render()
    {
        throw new Exception(get_class($this) . ':' . __METHOD__, 500);
    }
}
