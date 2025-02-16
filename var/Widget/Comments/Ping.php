<?php

namespace Widget\Comments;

use Typecho\Config;
use Typecho\Db\Exception;
use Widget\Base\Comments;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * thành phần lưu trữ echo
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Ping extends Comments
{
    /**
     * _customSinglePingCallback
     *
     * @var boolean
     * @access private
     */
    private $customSinglePingCallback = false;

    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault('parentId=0');

        /** Chức năng gọi lại khởi tạo */
        if (function_exists('singlePing')) {
            $this->customSinglePingCallback = true;
        }
    }

    /**
     * Xuất ra số lượng phản hồi bài viết
     *
     * @param mixed ...$args Dữ liệu được định dạng về số lượng bình luận
     */
    public function num(...$args)
    {
        if (empty($args)) {
            $args[] = '%d';
        }

        echo sprintf($args[$this->length] ?? array_pop($args), $this->length);
    }

    /**
     * execute
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        if (!$this->parameter->parentId) {
            return;
        }

        $select = $this->select()->where('table.comments.status = ?', 'approved')
            ->where('table.comments.cid = ?', $this->parameter->parentId)
            ->where('table.comments.type <> ?', 'comment')
            ->order('table.comments.coid', 'ASC');

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * liệt kê các câu trả lời
     *
     * @param mixed $singlePingOptions Tùy chọn tùy chỉnh tiếng vang riêng lẻ
     */
    public function listPings($singlePingOptions = null)
    {
        if ($this->have()) {
            // khởi tạo một số biến
            $parsedSinglePingOptions = Config::factory($singlePingOptions);
            $parsedSinglePingOptions->setDefault([
                'before'      => '<ol class="ping-list">',
                'after'       => '</ol>',
                'beforeTitle' => '',
                'afterTitle'  => '',
                'beforeDate'  => '',
                'afterDate'   => '',
                'dateFormat'  => $this->options->commentDateFormat
            ]);

            echo $parsedSinglePingOptions->before;

            while ($this->next()) {
                $this->singlePingCallback($parsedSinglePingOptions);
            }

            echo $parsedSinglePingOptions->after;
        }
    }

    /**
     * chức năng gọi lại echo
     *
     * @param string $singlePingOptions Tùy chọn tùy chỉnh tiếng vang riêng lẻ
     */
    private function singlePingCallback(string $singlePingOptions)
    {
        if ($this->customSinglePingCallback) {
            return singlePing($this, $singlePingOptions);
        }

        ?>
        <li id="<?php $this->theId(); ?>" class="ping-body">
            <div class="ping-title">
                <cite class="fn"><?php
                    $singlePingOptions->beforeTitle();
                    $this->author(true);
                    $singlePingOptions->afterTitle();
                ?></cite>
            </div>
            <div class="ping-meta">
                <a href="<?php $this->permalink(); ?>"><?php $singlePingOptions->beforeDate();
                    $this->date($singlePingOptions->dateFormat);
                    $singlePingOptions->afterDate(); ?></a>
            </div>
            <?php $this->content(); ?>
        </li>
        <?php
    }

    /**
     * Tải lại nội dung mua lại
     *
     * @return array|null
     */
    protected function ___parentContent(): ?array
    {
        return $this->parameter->parentContent;
    }
}
