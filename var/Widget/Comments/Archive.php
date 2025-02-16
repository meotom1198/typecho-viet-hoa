<?php

namespace Widget\Comments;

use Typecho\Config;
use Typecho\Cookie;
use Typecho\Router;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Comments;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần lưu trữ bình luận
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Archive extends Comments
{
    /**
     * Trang hiện tại
     *
     * @access private
     * @var integer
     */
    private $currentPage;

    /**
     * Số lượng tất cả các bài viết
     *
     * @access private
     * @var integer
     */
    private $total = false;

    /**
     * Mối quan hệ nhận xét giữa cha mẹ và con cái
     *
     * @access private
     * @var array
     */
    private $threadedComments = [];

    /**
     * _singleCommentOptions
     *
     * @var mixed
     * @access private
     */
    private $singleCommentOptions = null;

    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault('parentId=0&commentPage=0&commentsNum=0&allowComment=1');
    }

    /**
     * Xuất ra số lượng bình luận bài viết
     *
     * @param ...$args
     */
    public function num(...$args)
    {
        if (empty($args)) {
            $args[] = '%d';
        }

        $num = intval($this->total);

        echo sprintf($args[$num] ?? array_pop($args), $num);
    }

    /**
     * Thực thi chức năng
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        if (!$this->parameter->parentId) {
            return;
        }

        $commentsAuthor = Cookie::get('__typecho_remember_author');
        $commentsMail = Cookie::get('__typecho_remember_mail');
        $select = $this->select()->where('table.comments.cid = ?', $this->parameter->parentId)
            ->where(
                'table.comments.status = ? OR (table.comments.author = ?'
                    . ' AND table.comments.mail = ? AND table.comments.status = ?)',
                'approved',
                $commentsAuthor,
                $commentsMail,
                'waiting'
            );
        $threadedSelect = null;

        if ($this->options->commentsShowCommentOnly) {
            $select->where('table.comments.type = ?', 'comment');
        }

        $select->order('table.comments.coid', 'ASC');
        $this->db->fetchAll($select, [$this, 'push']);

        /** Danh sách các ý kiến ​​​​sẽ được xuất ra */
        $outputComments = [];

        /** Nếu bạn bật trả lời bình luận */
        if ($this->options->commentsThreaded) {
            foreach ($this->stack as $coid => &$comment) {

                /** Xóa nút cha */
                $parent = $comment['parent'];

                /** Nếu có nút cha */
                if (0 != $parent && isset($this->stack[$parent])) {

                    /** Nếu độ sâu nút hiện tại lớn hơn độ sâu tối đa, hãy gắn nó vào nút cha */
                    if ($comment['levels'] >= $this->options->commentsMaxNestingLevels) {
                        $comment['levels'] = $this->stack[$parent]['levels'];
                        $parent = $this->stack[$parent]['parent'];     // 上上层节点
                        $comment['parent'] = $parent;
                    }

                    /** Tính thứ tự các nút con */
                    $comment['order'] = isset($this->threadedComments[$parent])
                        ? count($this->threadedComments[$parent]) + 1 : 1;

                    /** Nếu đó là nút con */
                    $this->threadedComments[$parent][$coid] = $comment;
                } else {
                    $outputComments[$coid] = $comment;
                }

            }

            $this->stack = $outputComments;
        }

        /** Sắp xếp nhận xét */
        if ('DESC' == $this->options->commentsOrder) {
            $this->stack = array_reverse($this->stack, true);
            $this->threadedComments = array_map('array_reverse', $this->threadedComments);
        }

        /** Tổng số bình luận */
        $this->total = count($this->stack);

        /** Phân trang bình luận */
        if ($this->options->commentsPageBreak) {
            if ('last' == $this->options->commentsPageDisplay && !$this->parameter->commentPage) {
                $this->currentPage = ceil($this->total / $this->options->commentsPageSize);
            } else {
                $this->currentPage = $this->parameter->commentPage ? $this->parameter->commentPage : 1;
            }

            /** Chặn nhận xét */
            $this->stack = array_slice(
                $this->stack,
                ($this->currentPage - 1) * $this->options->commentsPageSize,
                $this->options->commentsPageSize
            );

            /** Vị trí bình luận */
            $this->length = count($this->stack);
            $this->row = $this->length > 0 ? current($this->stack) : [];
        }

        reset($this->stack);
    }

    /**
     * Đẩy giá trị của mỗi hàng vào ngăn xếp
     *
     * @param array $value giá trị của mỗi hàng
     * @return array
     */
    public function push(array $value): array
    {
        $value = $this->filter($value);

        /** Tính độ sâu */
        if (0 != $value['parent'] && isset($this->stack[$value['parent']]['levels'])) {
            $value['levels'] = $this->stack[$value['parent']]['levels'] + 1;
        } else {
            $value['levels'] = 0;
        }

        /** Quá tải chức năng đẩy và sử dụng coid làm giá trị khóa mảng để tạo điều kiện lập chỉ mục */
        $this->stack[$value['coid']] = $value;
        $this->length ++;

        return $value;
    }

    /**
     * Phân trang đầu ra
     *
     * @access public
     * @param string $prev Văn bản trang trước
     * @param string $next Văn bản trang sau
     * @param int $splitPage Phạm vi phân chia
     * @param string $splitWord ký tự phân chia
     * @param string|array $template Hiển thị thông tin cấu hình
     * @return void
     * @throws \Typecho\Widget\Exception
     */
    public function pageNav(
        string $prev = '&laquo;',
        string $next = '&raquo;',
        int $splitPage = 3,
        string $splitWord = '...',
        $template = ''
    ) {
        if ($this->options->commentsPageBreak) {
            $default = [
                'wrapTag'   => 'ol',
                'wrapClass' => 'page-navigator'
            ];

            if (is_string($template)) {
                parse_str($template, $config);
            } else {
                $config = $template ?: [];
            }

            $template = array_merge($default, $config);

            $pageRow = $this->parameter->parentContent;
            $pageRow['permalink'] = $pageRow['pathinfo'];
            $query = Router::url('comment_page', $pageRow, $this->options->index);

            self::pluginHandle()->trigger($hasNav)->pageNav(
                $this->currentPage,
                $this->total,
                $this->options->commentsPageSize,
                $prev,
                $next,
                $splitPage,
                $splitWord,
                $template,
                $query
            );

            if (!$hasNav && $this->total > $this->options->commentsPageSize) {
                /** Sử dụng phân trang hộp */
                $nav = new Box($this->total, $this->currentPage, $this->options->commentsPageSize, $query);
                $nav->setPageHolder('commentPage');
                $nav->setAnchor('comments');

                echo '<' . $template['wrapTag'] . (empty($template['wrapClass'])
                        ? '' : ' class="' . $template['wrapClass'] . '"') . '>';
                $nav->render($prev, $next, $splitPage, $splitWord, $template);
                echo '</' . $template['wrapTag'] . '>';
            }
        }
    }

    /**
     * Liệt kê nhận xét
     *
     * @param mixed $singleCommentOptions Tùy chọn tùy chỉnh nhận xét cá nhân
     */
    public function listComments($singleCommentOptions = null)
    {
        // khởi tạo một số biến
        $this->singleCommentOptions = Config::factory($singleCommentOptions);
        $this->singleCommentOptions->setDefault([
            'before'        => '<ol class="comment-list">',
            'after'         => '</ol>',
            'beforeAuthor'  => '',
            'afterAuthor'   => '',
            'beforeDate'    => '',
            'afterDate'     => '',
            'dateFormat'    => $this->options->commentDateFormat,
            'replyWord'     => _t('Trả lời'),
            'commentStatus' => _t('Bình luận của bạn đang chờ duyệt!'),
            'avatarSize'    => 32,
            'defaultAvatar' => null
        ]);
        self::pluginHandle()->trigger($plugged)->listComments($this->singleCommentOptions, $this);

        if (!$plugged) {
            if ($this->have()) {
                echo $this->singleCommentOptions->before;

                while ($this->next()) {
                    $this->threadedCommentsCallback();
                }

                echo $this->singleCommentOptions->after;
            }
        }
    }

    /**
     * Chức năng gọi lại bình luận
     */
    private function threadedCommentsCallback()
    {
        $singleCommentOptions = $this->singleCommentOptions;
        if (function_exists('threadedComments')) {
            return threadedComments($this, $singleCommentOptions);
        }

        $commentClass = '';
        if ($this->authorId) {
            if ($this->authorId == $this->ownerId) {
                $commentClass .= ' comment-by-author';
            } else {
                $commentClass .= ' comment-by-user';
            }
        }
        ?>
        <li itemscope itemtype="http://schema.org/UserComments" id="<?php $this->theId(); ?>" class="comment-body<?php
        if ($this->levels > 0) {
            echo ' comment-child';
            $this->levelsAlt(' comment-level-odd', ' comment-level-even');
        } else {
            echo ' comment-parent';
        }
        $this->alt(' comment-odd', ' comment-even');
        echo $commentClass;
        ?>">
            <div class="comment-author" itemprop="creator" itemscope itemtype="http://schema.org/Person">
                <span
                    itemprop="image">
                    <?php $this->gravatar($singleCommentOptions->avatarSize, $singleCommentOptions->defaultAvatar); ?>
                </span>
                <cite class="fn" itemprop="name"><?php $singleCommentOptions->beforeAuthor();
                    $this->author();
                    $singleCommentOptions->afterAuthor(); ?></cite>
            </div>
            <div class="comment-meta">
                <a href="<?php $this->permalink(); ?>">
                    <time itemprop="commentTime"
                          datetime="<?php $this->date('c'); ?>"><?php
                            $singleCommentOptions->beforeDate();
                            $this->date($singleCommentOptions->dateFormat);
                            $singleCommentOptions->afterDate();
                            ?></time>
                </a>
                <?php if ('waiting' == $this->status) { ?>
                    <em class="comment-awaiting-moderation"><?php $singleCommentOptions->commentStatus(); ?></em>
                <?php } ?>
            </div>
            <div class="comment-content" itemprop="commentText">
                <?php $this->content(); ?>
            </div>
            <div class="comment-reply">
                <?php $this->reply($singleCommentOptions->replyWord); ?>
            </div>
            <?php if ($this->children) { ?>
                <div class="comment-children" itemprop="discusses">
                    <?php $this->threadedComments(); ?>
                </div>
            <?php } ?>
        </li>
        <?php
    }

    /**
     * Đầu ra theo độ sâu còn lại
     *
     * @param mixed ...$args Giá trị cần xuất
     */
    public function levelsAlt(...$args)
    {
        $num = count($args);
        $split = $this->levels % $num;
        echo $args[(0 == $split ? $num : $split) - 1];
    }

    /**
     * Quá tải chức năng alt để chứa các bình luận đa cấp
     *
     * @param ...$args
     */
    public function alt(...$args)
    {
        $num = count($args);

        $sequence = $this->levels <= 0 ? $this->sequence : $this->order;

        $split = $sequence % $num;
        echo $args[(0 == $split ? $num : $split) - 1];
    }

    /**
     * Link trả lời bình luận
     *
     * @param string $word Trả lời văn bản liên kết
     */
    public function reply(string $word = '')
    {
        if ($this->options->commentsThreaded && !$this->isTopLevel && $this->parameter->allowComment) {
            $word = empty($word) ? _t('Trả lời') : $word;
            self::pluginHandle()->trigger($plugged)->reply($word, $this);

            if (!$plugged) {
                echo '<a href="' . substr($this->permalink, 0, - strlen($this->theId) - 1) . '?replyTo=' . $this->coid .
                    '#' . $this->parameter->respondId . '" rel="nofollow" onclick="return TypechoComment.reply(\'' .
                    $this->theId . '\', ' . $this->coid . ');">' . $word . '</a>';
            }
        }
    }

    /**
     * Nhận xét đầu ra đệ quy
     */
    public function threadedComments()
    {
        $children = $this->children;
        if ($children) {
            // Biến bộ nhớ đệm để khôi phục dễ dàng
            $tmp = $this->row;
            $this->sequence ++;

            // Đầu ra trước bình luận phụ
            echo $this->singleCommentOptions->before;

            foreach ($children as $child) {
                $this->row = $child;
                $this->threadedCommentsCallback();
                $this->row = $tmp;
            }

            // Đầu ra sau bình luận phụ
            echo $this->singleCommentOptions->after;

            $this->sequence --;
        }
    }

    /**
     * Hủy liên kết trả lời bình luận
     *
     * @param string $word Hủy văn bản liên kết trả lời
     */
    public function cancelReply(string $word = '')
    {
        if ($this->options->commentsThreaded) {
            $word = empty($word) ? _t('Hủy trả lời') : $word;
            self::pluginHandle()->trigger($plugged)->cancelReply($word, $this);

            if (!$plugged) {
                $replyId = $this->request->filter('int')->replyTo;
                echo '<a id="cancel-comment-reply-link" href="' . $this->parameter->parentContent['permalink'] . '#' . $this->parameter->respondId .
                    '" rel="nofollow"' . ($replyId ? '' : ' style="display:none"') . ' onclick="return TypechoComment.cancelReply();">' . $word . '</a>';
            }
        }
    }

    /**
     * Nhận liên kết nhận xét hiện tại
     *
     * @return string
     */
    protected function ___permalink(): string
    {

        if ($this->options->commentsPageBreak) {
            $pageRow = ['permalink' => $this->parentContent['pathinfo'], 'commentPage' => $this->currentPage];
            return Router::url('comment_page', $pageRow, $this->options->index) . '#' . $this->theId;
        }

        return $this->parentContent['permalink'] . '#' . $this->theId;
    }

    /**
     * bình luận phụ
     *
     * @return array
     */
    protected function ___children(): array
    {
        return $this->options->commentsThreaded && !$this->isTopLevel && isset($this->threadedComments[$this->coid])
            ? $this->threadedComments[$this->coid] : [];
    }

    /**
     * Có đạt được cấp độ cao nhất không
     *
     * @return boolean
     */
    protected function ___isTopLevel(): bool
    {
        return $this->levels > $this->options->commentsMaxNestingLevels - 2;
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
