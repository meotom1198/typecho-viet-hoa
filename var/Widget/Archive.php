<?php

namespace Widget;

use Typecho\Common;
use Typecho\Config;
use Typecho\Cookie;
use Typecho\Db;
use Typecho\Db\Query;
use Typecho\Feed;
use Typecho\Router;
use Typecho\Widget\Exception as WidgetException;
use Typecho\Widget\Helper\PageNavigator;
use Typecho\Widget\Helper\PageNavigator\Classic;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Widget\Comments\Ping;
use Widget\Comments\Recent;
use Widget\Contents\Attachment\Related;
use Widget\Contents\Related\Author;
use Widget\Metas\Category\Rows;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Lớp cơ sở nội dung bài viết
 * Đã xác định lớp css
 * p.more: Đọc đoạn văn chứa liên kết toàn văn
 *
 * @package Widget
 */
class Archive extends Contents
{
    /**
     * Tệp kiểu được gọi
     *
     * @var string
     */
    private $themeFile;

    /**
     * thư mục phong cách
     *
     * @var string
     */
    private $themeDir;

    /**
     * Đối tượng tính toán phân trang
     *
     * @var Query
     */
    private $countSql;

    /**
     * Số lượng tất cả các bài viết
     *
     * @var integer
     */
    private $total = false;

    /**
     * Cờ có được gọi từ bên ngoài không
     *
     * @var boolean
     */
    private $invokeFromOutside = false;

    /**
     * Cho dù được gọi bởi một tổng hợp
     *
     * @var boolean
     */
    private $invokeByFeed = false;

    /**
     * Trang hiện tại
     *
     * @var integer
     */
    private $currentPage;

    /**
     * Tạo nội dung được phân trang
     *
     * @var array
     */
    private $pageRow = [];

    /**
     * đối tượng tổng hợp
     *
     * @var Feed
     */
    private $feed;

    /**
     * RSS 2.0 địa chỉ tổng hợp
     *
     * @var string
     */
    private $feedUrl;

    /**
     * RSS 1.0 địa chỉ tổng hợp
     *
     * @var string
     */
    private $feedRssUrl;

    /**
     * ATOM địa chỉ tổng hợp
     *
     * @var string
     */
    private $feedAtomUrl;

    /**
     * Từ khóa cho trang này
     *
     * @var string
     */
    private $keywords;

    /**
     * Mô tả của trang này
     *
     * @var string
     */
    private $description;

    /**
     * Kiểu tổng hợp
     *
     * @var string
     */
    private $feedType;

    /**
     * Kiểu tổng hợp
     *
     * @var string
     */
    private $feedContentType;

    /**
     * Địa chỉ nguồn cấp dữ liệu hiện tại
     *
     * @var string
     */
    private $currentFeedUrl;

    /**
     * Tiêu đề lưu trữ
     *
     * @var string
     */
    private $archiveTitle = null;

    /**
     * Địa chỉ lưu trữ
     *
     * @var string|null
     */
    private $archiveUrl = null;

    /**
     * Loại lưu trữ
     *
     * @var string
     */
    private $archiveType = 'index';

    /**
     * Đây có phải là một kho lưu trữ duy nhất?
     *
     * @var string
     */
    private $archiveSingle = false;

    /**
     * Cho dù đó là trang chủ tùy chỉnh, chủ yếu để đánh dấu tình trạng trang chủ tùy chỉnh
     *
     * (default value: false)
     *
     * @var boolean
     * @access private
     */
    private $makeSinglePageAsFrontPage = false;

    /**
     * Lưu trữ viết tắt
     *
     * @access private
     * @var string
     */
    private $archiveSlug;

    /**
     * Đặt đối tượng phân trang
     *
     * @access private
     * @var PageNavigator
     */
    private $pageNav;

    /**
     * @param Config $parameter
     * @throws \Exception
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault([
            'pageSize'       => $this->options->pageSize,
            'type'           => null,
            'checkPermalink' => true,
            'preview'        => false
        ]);

        /** Được sử dụng để xác định xem đó là cuộc gọi định tuyến hay cuộc gọi bên ngoài */
        if (null == $parameter->type) {
            $parameter->type = Router::$current;
        } else {
            $this->invokeFromOutside = true;
        }

        /** Dùng để xác định xem đó có phải là cuộc gọi nguồn cấp dữ liệu hay không */
        if ($parameter->isFeed) {
            $this->invokeByFeed = true;
        }

        /** Khởi tạo đường dẫn da */
        $this->themeDir = rtrim($this->options->themeFile($this->options->theme), '/') . '/';

        /** Xử lý chế độ nạp **/
        if ('feed' == $parameter->type) {
            $this->currentFeedUrl = '';

            /** Xác định kiểu tổng hợp */
            switch (true) {
                case 0 === strpos($this->request->feed, '/rss/') || '/rss' == $this->request->feed:
                    /** Nếu là chuẩn RSS1 */
                    $this->request->feed = substr($this->request->feed, 4);
                    $this->feedType = Feed::RSS1;
                    $this->currentFeedUrl = $this->options->feedRssUrl;
                    $this->feedContentType = 'application/rdf+xml';
                    break;
                case 0 === strpos($this->request->feed, '/atom/') || '/atom' == $this->request->feed:
                    /** Nếu là tiêu chuẩn ATOM */
                    $this->request->feed = substr($this->request->feed, 5);
                    $this->feedType = Feed::ATOM1;
                    $this->currentFeedUrl = $this->options->feedAtomUrl;
                    $this->feedContentType = 'application/atom+xml';
                    break;
                default:
                    $this->feedType = Feed::RSS2;
                    $this->currentFeedUrl = $this->options->feedUrl;
                    $this->feedContentType = 'application/rss+xml';
                    break;
            }

            $feedQuery = $this->request->feed;
            //$parameter->type = Router::$current;
            //$this->request->setParams($params);

            if ('/comments/' == $feedQuery || '/comments' == $feedQuery) {
                /** Hack đặc biệt cho nguồn cấp dữ liệu */
                $parameter->type = 'comments';
                $this->options->feedUrl = $this->options->commentsFeedUrl;
                $this->options->feedRssUrl = $this->options->commentsFeedRssUrl;
                $this->options->feedAtomUrl = $this->options->commentsFeedAtomUrl;
            } else {
                $matched = Router::match($this->request->feed, 'pageSize=10&isFeed=1');
                if ($matched instanceof Archive) {
                    $this->import($matched);
                } else {
                    throw new WidgetException(_t('Trang tổng hợp không tồn tại!'), 404);
                }
            }

            /** Khởi tạo trình tổng hợp */
            $this->setFeed(new Feed(Common::VERSION, $this->feedType, $this->options->charset, _t('zh-CN')));

            /** 10 bài viết được xuất theo mặc định **/
            $parameter->pageSize = 10;
        }
    }

    /**
     * Thêm tiêu đề
     * @param string $archiveTitle tiêu đề
     */
    public function addArchiveTitle(string $archiveTitle)
    {
        $current = $this->getArchiveTitle();
        $current[] = $archiveTitle;
        $this->setArchiveTitle($current);
    }

    /**
     * @return string
     */
    public function getArchiveTitle(): ?string
    {
        return $this->archiveTitle;
    }

    /**
     * @param string $archiveTitle the $archiveTitle to set
     */
    public function setArchiveTitle(string $archiveTitle)
    {
        $this->archiveTitle = $archiveTitle;
    }

    /**
     * Lấy đối tượng phân trang
     * @return array
     */
    public function getPageRow(): array
    {
        return $this->pageRow;
    }

    /**
     * Đặt đối tượng phân trang
     * @param array $pageRow
     */
    public function setPageRow(array $pageRow)
    {
        $this->pageRow = $pageRow;
    }

    /**
     * @return string|null
     */
    public function getArchiveSlug(): ?string
    {
        return $this->archiveSlug;
    }

    /**
     * @param string $archiveSlug the $archiveSlug to set
     */
    public function setArchiveSlug(string $archiveSlug)
    {
        $this->archiveSlug = $archiveSlug;
    }

    /**
     * @return string|null
     */
    public function getArchiveSingle(): ?string
    {
        return $this->archiveSingle;
    }

    /**
     * @param string $archiveSingle the $archiveSingle to set
     */
    public function setArchiveSingle(string $archiveSingle)
    {
        $this->archiveSingle = $archiveSingle;
    }

    /**
     * @return string|null
     */
    public function getArchiveType(): ?string
    {
        return $this->archiveType;
    }

    /**
     * @param string $archiveType the $archiveType to set
     */
    public function setArchiveType(string $archiveType)
    {
        $this->archiveType = $archiveType;
    }

    /**
     * @return string|null
     */
    public function getArchiveUrl(): ?string
    {
        return $this->archiveUrl;
    }

    /**
     * @param string|null $archiveUrl
     */
    public function setArchiveUrl(?string $archiveUrl): void
    {
        $this->archiveUrl = $archiveUrl;
    }

    /**
     * @return string|null
     */
    public function getFeedType(): ?string
    {
        return $this->feedType;
    }

    /**
     * @param string $feedType the $feedType to set
     */
    public function setFeedType(string $feedType)
    {
        $this->feedType = $feedType;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description the $description to set
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords the $keywords to set
     */
    public function setKeywords(string $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return string
     */
    public function getFeedAtomUrl(): string
    {
        return $this->feedAtomUrl;
    }

    /**
     * @param string $feedAtomUrl the $feedAtomUrl to set
     */
    public function setFeedAtomUrl(string $feedAtomUrl)
    {
        $this->feedAtomUrl = $feedAtomUrl;
    }

    /**
     * @return string
     */
    public function getFeedRssUrl(): string
    {
        return $this->feedRssUrl;
    }

    /**
     * @param string $feedRssUrl the $feedRssUrl to set
     */
    public function setFeedRssUrl(string $feedRssUrl)
    {
        $this->feedRssUrl = $feedRssUrl;
    }

    /**
     * @return string
     */
    public function getFeedUrl(): string
    {
        return $this->feedUrl;
    }

    /**
     * @param string $feedUrl the $feedUrl to set
     */
    public function setFeedUrl(string $feedUrl)
    {
        $this->feedUrl = $feedUrl;
    }

    /**
     * @return Feed
     */
    public function getFeed(): Feed
    {
        return $this->feed;
    }

    /**
     * @param Feed $feed the $feed to set
     */
    public function setFeed(Feed $feed)
    {
        $this->feed = $feed;
    }

    /**
     * @return Query|null
     */
    public function getCountSql(): ?Query
    {
        return $this->countSql;
    }

    /**
     * @param Query $countSql the $countSql to set
     */
    public function setCountSql($countSql)
    {
        $this->countSql = $countSql;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * _currentPage
     *
     * @return int
     */
    public function ____currentPage(): int
    {
        return $this->getCurrentPage();
    }

    /**
     * Lấy số trang
     *
     * @return integer
     */
    public function getTotalPage(): int
    {
        return ceil($this->getTotal() / $this->parameter->pageSize);
    }

    /**
     * @return int
     * @throws Db\Exception
     */
    public function getTotal(): int
    {
        if (false === $this->total) {
            $this->total = $this->size($this->countSql);
        }

        return $this->total;
    }

    /**
     * @param int $total the $total to set
     */
    public function setTotal(int $total)
    {
        $this->total = $total;
    }

    /**
     * @return string|null
     */
    public function getThemeFile(): ?string
    {
        return $this->themeFile;
    }

    /**
     * @param string $themeFile the $themeFile to set
     */
    public function setThemeFile(string $themeFile)
    {
        $this->themeFile = $themeFile;
    }

    /**
     * @return string|null
     */
    public function getThemeDir(): ?string
    {
        return $this->themeDir;
    }

    /**
     * @param string $themeDir the $themeDir to set
     */
    public function setThemeDir(string $themeDir)
    {
        $this->themeDir = $themeDir;
    }

    /**
     * Thực thi chức năng
     */
    public function execute()
    {
        /** Tránh việc truy xuất dữ liệu lặp đi lặp lại */
        if ($this->have()) {
            return;
        }

        $handles = [
            'index'              => 'indexHandle',
            'index_page'         => 'indexHandle',
            'archive'            => 'archiveEmptyHandle',
            'archive_page'       => 'archiveEmptyHandle',
            404                  => 'error404Handle',
            'single'             => 'singleHandle',
            'page'               => 'singleHandle',
            'post'               => 'singleHandle',
            'attachment'         => 'singleHandle',
            'comment_page'       => 'singleHandle',
            'category'           => 'categoryHandle',
            'category_page'      => 'categoryHandle',
            'tag'                => 'tagHandle',
            'tag_page'           => 'tagHandle',
            'author'             => 'authorHandle',
            'author_page'        => 'authorHandle',
            'archive_year'       => 'dateHandle',
            'archive_year_page'  => 'dateHandle',
            'archive_month'      => 'dateHandle',
            'archive_month_page' => 'dateHandle',
            'archive_day'        => 'dateHandle',
            'archive_day_page'   => 'dateHandle',
            'search'             => 'searchHandle',
            'search_page'        => 'searchHandle'
        ];

        /** Xử lý các bước nhảy kết quả tìm kiếm */
        if (isset($this->request->s)) {
            $filterKeywords = $this->request->filter('search')->get('s');

            /** Chuyển đến trang tìm kiếm */
            if (null != $filterKeywords) {
                $this->response->redirect(
                    Router::url('search', ['keywords' => urlencode($filterKeywords)], $this->options->index)
                );
            }
        }

        /** Tính năng trang chủ tùy chỉnh */
        $frontPage = $this->options->frontPage;
        if (!$this->invokeByFeed && ('index' == $this->parameter->type || 'index_page' == $this->parameter->type)) {
            // Hiển thị một trang
            if (0 === strpos($frontPage, 'page:')) {
                // Hack một số biến
                $this->request->setParam('cid', intval(substr($frontPage, 5)));
                $this->parameter->type = 'page';
                $this->makeSinglePageAsFrontPage = true;
            } elseif (0 === strpos($frontPage, 'file:')) {
                // hiển thị một tập tin
                $this->setThemeFile(substr($frontPage, 5));
                return;
            }
        }

        if ('recent' != $frontPage && $this->options->frontArchive) {
            $handles['archive'] = 'indexHandle';
            $handles['archive_page'] = 'indexHandle';
            $this->archiveType = 'front';
        }

        /** Khởi tạo các biến phân trang */
        $this->currentPage = $this->request->filter('int')->page ?? 1;
        $hasPushed = false;

        /** chọn khởi tạo */
        $select = self::pluginHandle()->trigger($selectPlugged)->select($this);

        /** Chức năng phát hành theo lịch trình */
        if (!$selectPlugged) {
            if ($this->parameter->preview) {
                $select = $this->select();
            } else {
                if ('post' == $this->parameter->type || 'page' == $this->parameter->type) {
                    if ($this->user->hasLogin()) {
                        $select = $this->select()->where(
                            'table.contents.status = ? OR table.contents.status = ? 
                                OR (table.contents.status = ? AND table.contents.authorId = ?)',
                            'publish',
                            'hidden',
                            'private',
                            $this->user->uid
                        );
                    } else {
                        $select = $this->select()->where(
                            'table.contents.status = ? OR table.contents.status = ?',
                            'publish',
                            'hidden'
                        );
                    }
                } else {
                    if ($this->user->hasLogin()) {
                        $select = $this->select()->where(
                            'table.contents.status = ? OR (table.contents.status = ? AND table.contents.authorId = ?)',
                            'publish',
                            'private',
                            $this->user->uid
                        );
                    } else {
                        $select = $this->select()->where('table.contents.status = ?', 'publish');
                    }
                }
                $select->where('table.contents.created < ?', $this->options->time);
            }
        }

        /** xử lý việc khởi tạo */
        self::pluginHandle()->handleInit($this, $select);

        /** Khởi tạo các biến khác */
        $this->feedUrl = $this->options->feedUrl;
        $this->feedRssUrl = $this->options->feedRssUrl;
        $this->feedAtomUrl = $this->options->feedAtomUrl;
        $this->keywords = $this->options->keywords;
        $this->description = $this->options->description;
        $this->archiveUrl = $this->options->siteUrl;

        if (isset($handles[$this->parameter->type])) {
            $handle = $handles[$this->parameter->type];
            $this->{$handle}($select, $hasPushed);
        } else {
            $hasPushed = self::pluginHandle()->handle($this->parameter->type, $this, $select);
        }

        /** Khởi tạo chức năng da */
        $functionsFile = $this->themeDir . 'functions.php';
        if (
            (!$this->invokeFromOutside || $this->parameter->type == 404 || $this->parameter->preview)
            && file_exists($functionsFile)
        ) {
            require_once $functionsFile;
            if (function_exists('themeInit')) {
                themeInit($this);
            }
        }

        /** Nếu nó đã được đẩy vào sớm, hãy quay lại trực tiếp. */
        if ($hasPushed) {
            return;
        }

        /** Chỉ xuất bài viết */
        $this->countSql = clone $select;

        $select->order('table.contents.created', Db::SORT_DESC)
            ->page($this->currentPage, $this->parameter->pageSize);
        $this->query($select);

        /** Xử lý tình huống out-of-page */
        if ($this->currentPage > 1 && !$this->have()) {
            throw new WidgetException(_t('请求的地址不存在'), 404);
        }
    }

    /**
     * chọn quá tải
     *
     * @return Query
     * @throws Db\Exception
     */
    public function select(): Query
    {
        if ($this->invokeByFeed) {
            // Thêm các hạn chế vào đầu ra của nguồn cấp dữ liệu
            return parent::select()->where('table.contents.allowFeed = ?', 1)
                ->where("table.contents.password IS NULL OR table.contents.password = ''");
        } else {
            return parent::select();
        }
    }

    /**
     * Xuất nội dung bài viết
     *
     * @param string $more Hậu tố chặn bài viết
     */
    public function content($more = null)
    {
        parent::content($this->is('single') ? false : $more);
    }

    /**
     * Phân trang đầu ra
     *
     * @param string $prev Văn bản trang trước
     * @param string $next Văn bản trang sau
     * @param int $splitPage Phạm vi phân chia
     * @param string $splitWord ký tự phân chia
     * @param string|array $template Hiển thị thông tin cấu hình
     * @throws Db\Exception|WidgetException
     */
    public function pageNav(
        string $prev = '&laquo;',
        string $next = '&raquo;',
        int $splitPage = 3,
        string $splitWord = '...',
        $template = ''
    ) {
        if ($this->have()) {
            $hasNav = false;
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
            $total = $this->getTotal();
            $query = Router::url(
                $this->parameter->type .
                (false === strpos($this->parameter->type, '_page') ? '_page' : null),
                $this->pageRow,
                $this->options->index
            );

            self::pluginHandle()->trigger($hasNav)->pageNav(
                $this->currentPage,
                $total,
                $this->parameter->pageSize,
                $prev,
                $next,
                $splitPage,
                $splitWord,
                $template,
                $query
            );

            if (!$hasNav && $total > $this->parameter->pageSize) {
                /** Sử dụng phân trang hộp */
                $nav = new Box(
                    $total,
                    $this->currentPage,
                    $this->parameter->pageSize,
                    $query
                );

                echo '<' . $template['wrapTag'] . (empty($template['wrapClass'])
                        ? '' : ' class="' . $template['wrapClass'] . '"') . '>';
                $nav->render($prev, $next, $splitPage, $splitWord, $template);
                echo '</' . $template['wrapTag'] . '>';
            }
        }
    }

    /**
     * Trang trước
     *
     * @param string $word tiêu đề liên kết
     * @param string $page Liên kết trang
     * @throws Db\Exception|WidgetException
     */
    public function pageLink(string $word = '&laquo; Previous Entries', string $page = 'prev')
    {
        if ($this->have()) {
            if (empty($this->pageNav)) {
                $query = Router::url(
                    $this->parameter->type .
                    (false === strpos($this->parameter->type, '_page') ? '_page' : null),
                    $this->pageRow,
                    $this->options->index
                );

                /** Sử dụng phân trang hộp */
                $this->pageNav = new Classic(
                    $this->getTotal(),
                    $this->currentPage,
                    $this->parameter->pageSize,
                    $query
                );
            }

            $this->pageNav->{$page}($word);
        }
    }

    /**
     * Lấy đối tượng lưu trữ bình luận
     *
     * @access public
     * @return \Widget\Comments\Archive
     */
    public function comments(): \Widget\Comments\Archive
    {
        $parameter = [
            'parentId'      => $this->hidden ? 0 : $this->cid,
            'parentContent' => $this->row,
            'respondId'     => $this->respondId,
            'commentPage'   => $this->request->filter('int')->commentPage,
            'allowComment'  => $this->allow('comment')
        ];

        return \Widget\Comments\Archive::alloc($parameter);
    }

    /**
     * Nhận đối tượng lưu trữ echo
     *
     * @return Ping
     */
    public function pings(): Ping
    {
        return Ping::alloc([
            'parentId'      => $this->hidden ? 0 : $this->cid,
            'parentContent' => $this->row,
            'allowPing'     => $this->allow('ping')
        ]);
    }

    /**
     * Nhận đối tượng đính kèm
     *
     * @param integer $limit Số lượng tối đa
     * @param integer $offset lại
     * @return Related
     */
    public function attachments(int $limit = 0, int $offset = 0): Related
    {
        return Related::allocWithAlias($this->cid . '-' . uniqid(), [
            'parentId' => $this->cid,
            'limit'    => $limit,
            'offset'   => $offset
        ]);
    }

    /**
     * Hiển thị liên kết tiêu đề cho nội dung tiếp theo
     *
     * @param string $format Định dạng
     * @param string|null $default Nếu không có bài viết tiếp theo, văn bản mặc định được hiển thị
     * @param array $custom Phong cách tùy chỉnh
     */
    public function theNext(string $format = '%s', ?string $default = null, array $custom = [])
    {
        $content = $this->db->fetchRow($this->select()->where(
            'table.contents.created > ? AND table.contents.created < ?',
            $this->created,
            $this->options->time
        )
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', $this->type)
            ->where("table.contents.password IS NULL OR table.contents.password = ''")
            ->order('table.contents.created', Db::SORT_ASC)
            ->limit(1));

        if ($content) {
            $content = $this->filter($content);
            $default = [
                'title'    => null,
                'tagClass' => null
            ];
            $custom = array_merge($default, $custom);
            extract($custom);

            $linkText = empty($title) ? $content['title'] : $title;
            $linkClass = empty($tagClass) ? '' : 'class="' . $tagClass . '" ';
            $link = '<a ' . $linkClass . 'href="' . $content['permalink']
                . '" title="' . $content['title'] . '">' . $linkText . '</a>';

            printf($format, $link);
        } else {
            echo $default;
        }
    }

    /**
     * Hiển thị link tiêu đề của nội dung trước đó
     *
     * @access public
     * @param string $format Định dạng
     * @param string $default Nếu không có bài viết trước đó, văn bản mặc định được hiển thị
     * @param array $custom Phong cách tùy chỉnh
     * @return void
     */
    public function thePrev($format = '%s', $default = null, $custom = [])
    {
        $content = $this->db->fetchRow($this->select()->where('table.contents.created < ?', $this->created)
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', $this->type)
            ->where("table.contents.password IS NULL OR table.contents.password = ''")
            ->order('table.contents.created', Db::SORT_DESC)
            ->limit(1));

        if ($content) {
            $content = $this->filter($content);
            $default = [
                'title'    => null,
                'tagClass' => null
            ];
            $custom = array_merge($default, $custom);
            extract($custom);

            $linkText = empty($title) ? $content['title'] : $title;
            $linkClass = empty($tagClass) ? '' : 'class="' . $tagClass . '" ';
            $link = '<a ' . $linkClass . 'href="' . $content['permalink'] . '" title="' . $content['title'] . '">' . $linkText . '</a>';

            printf($format, $link);
        } else {
            echo $default;
        }
    }

    /**
     * Nhận các thành phần nội dung liên quan
     *
     * @param integer $limit Số lượng đầu ra
     * @param string|null $type loại hiệp hội
     * @return Contents
     */
    public function related(int $limit = 5, ?string $type = null): Contents
    {
        $type = strtolower($type ?? '');

        switch ($type) {
            case 'author':
                /** Nếu quyền truy cập được đặt thành bị cấm, thẻ sẽ được đặt thành trống */
                return Author::alloc(
                    ['cid' => $this->cid, 'type' => $this->type, 'author' => $this->author->uid, 'limit' => $limit]
                );
            default:
                /** Nếu quyền truy cập được đặt thành bị cấm, thẻ sẽ được đặt thành trống */
                return \Widget\Contents\Related::alloc(
                    ['cid' => $this->cid, 'type' => $this->type, 'tags' => $this->tags, 'limit' => $limit]
                );
        }
    }

    /**
     * Siêu dữ liệu tiêu đề đầu ra
     *
     * @param string|null $rule 规则
     */
    public function header(?string $rule = null)
    {
        $rules = [];
        $allows = [
            'description'  => htmlspecialchars($this->description ?? ''),
            'keywords'     => htmlspecialchars($this->keywords ?? ''),
            'generator'    => $this->options->generator,
            'template'     => $this->options->theme,
            'pingback'     => $this->options->xmlRpcUrl,
            'xmlrpc'       => $this->options->xmlRpcUrl . '?rsd',
            'wlw'          => $this->options->xmlRpcUrl . '?wlw',
            'rss2'         => $this->feedUrl,
            'rss1'         => $this->feedRssUrl,
            'commentReply' => 1,
            'antiSpam'     => 1,
            'atom'         => $this->feedAtomUrl
        ];

        /** Liệu tiêu đề có xuất ra tổng hợp hay không */
        $allowFeed = !$this->is('single') || $this->allow('feed') || $this->makeSinglePageAsFrontPage;

        if (!empty($rule)) {
            parse_str($rule, $rules);
            $allows = array_merge($allows, $rules);
        }

        $allows = self::pluginHandle()->headerOptions($allows, $this);
        $title = (empty($this->archiveTitle) ? '' : $this->archiveTitle . ' &raquo; ') . $this->options->title;

        $header = '';
        if (!empty($allows['description'])) {
            $header .= '<meta name="description" content="' . $allows['description'] . '" />' . "\n";
        }

        if (!empty($allows['keywords'])) {
            $header .= '<meta name="keywords" content="' . $allows['keywords'] . '" />' . "\n";
        }

        if (!empty($allows['generator'])) {
            $header .= '<meta name="generator" content="' . $allows['generator'] . '" />' . "\n";
        }

        if (!empty($allows['template'])) {
            $header .= '<meta name="template" content="' . $allows['template'] . '" />' . "\n";
        }

        if (!empty($allows['pingback']) && 2 == $this->options->allowXmlRpc) {
            $header .= '<link rel="pingback" href="' . $allows['pingback'] . '" />' . "\n";
        }

        if (!empty($allows['xmlrpc']) && 0 < $this->options->allowXmlRpc) {
            $header .= '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'
                . $allows['xmlrpc'] . '" />' . "\n";
        }

        if (!empty($allows['wlw']) && 0 < $this->options->allowXmlRpc) {
            $header .= '<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="'
                . $allows['wlw'] . '" />' . "\n";
        }

        if (!empty($allows['rss2']) && $allowFeed) {
            $header .= '<link rel="alternate" type="application/rss+xml" title="'
                . $title . ' &raquo; RSS 2.0" href="' . $allows['rss2'] . '" />' . "\n";
        }

        if (!empty($allows['rss1']) && $allowFeed) {
            $header .= '<link rel="alternate" type="application/rdf+xml" title="'
                . $title . ' &raquo; RSS 1.0" href="' . $allows['rss1'] . '" />' . "\n";
        }

        if (!empty($allows['atom']) && $allowFeed) {
            $header .= '<link rel="alternate" type="application/atom+xml" title="'
                . $title . ' &raquo; ATOM 1.0" href="' . $allows['atom'] . '" />' . "\n";
        }

        if ($this->options->commentsThreaded && $this->is('single')) {
            if ('' != $allows['commentReply']) {
                if (1 == $allows['commentReply']) {
                    $header .= "<script type=\"text/javascript\">
(function () {
    window.TypechoComment = {
        dom : function (id) {
            return document.getElementById(id);
        },
    
        create : function (tag, attr) {
            var el = document.createElement(tag);
        
            for (var key in attr) {
                el.setAttribute(key, attr[key]);
            }
        
            return el;
        },

        reply : function (cid, coid) {
            var comment = this.dom(cid), parent = comment.parentNode,
                response = this.dom('" . $this->respondId . "'), input = this.dom('comment-parent'),
                form = 'form' == response.tagName ? response : response.getElementsByTagName('form')[0],
                textarea = response.getElementsByTagName('textarea')[0];

            if (null == input) {
                input = this.create('input', {
                    'type' : 'hidden',
                    'name' : 'parent',
                    'id'   : 'comment-parent'
                });

                form.appendChild(input);
            }

            input.setAttribute('value', coid);

            if (null == this.dom('comment-form-place-holder')) {
                var holder = this.create('div', {
                    'id' : 'comment-form-place-holder'
                });

                response.parentNode.insertBefore(holder, response);
            }

            comment.appendChild(response);
            this.dom('cancel-comment-reply-link').style.display = '';

            if (null != textarea && 'text' == textarea.name) {
                textarea.focus();
            }

            return false;
        },

        cancelReply : function () {
            var response = this.dom('{$this->respondId}'),
            holder = this.dom('comment-form-place-holder'), input = this.dom('comment-parent');

            if (null != input) {
                input.parentNode.removeChild(input);
            }

            if (null == holder) {
                return true;
            }

            this.dom('cancel-comment-reply-link').style.display = 'none';
            holder.parentNode.insertBefore(response, holder);
            return false;
        }
    };
})();
</script>
";
                } else {
                    $header .= '<script src="' . $allows['commentReply'] . '" type="text/javascript"></script>';
                }
            }
        }

        /** Cài đặt chống thư rác */
        if ($this->options->commentsAntiSpam && $this->is('single')) {
            if ('' != $allows['antiSpam']) {
                if (1 == $allows['antiSpam']) {
                    $header .= "<script type=\"text/javascript\">
(function () {
    var event = document.addEventListener ? {
        add: 'addEventListener',
        triggers: ['scroll', 'mousemove', 'keyup', 'touchstart'],
        load: 'DOMContentLoaded'
    } : {
        add: 'attachEvent',
        triggers: ['onfocus', 'onmousemove', 'onkeyup', 'ontouchstart'],
        load: 'onload'
    }, added = false;

    document[event.add](event.load, function () {
        var r = document.getElementById('{$this->respondId}'),
            input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_';
        input.value = " . Common::shuffleScriptVar($this->security->getToken($this->request->getRequestUrl())) . "

        if (null != r) {
            var forms = r.getElementsByTagName('form');
            if (forms.length > 0) {
                function append() {
                    if (!added) {
                        forms[0].appendChild(input);
                        added = true;
                    }
                }
            
                for (var i = 0; i < event.triggers.length; i ++) {
                    var trigger = event.triggers[i];
                    document[event.add](trigger, append);
                    window[event.add](trigger, append);
                }
            }
        }
    });
})();
</script>";
                } else {
                    $header .= '<script src="' . $allows['antiSpam'] . '" type="text/javascript"></script>';
                }
            }
        }

        /** Tiêu đề đầu ra */
        echo $header;

        /** Hỗ trợ trình cắm */
        self::pluginHandle()->header($header, $this);
    }

    /**
     * Hỗ trợ tùy chỉnh chân trang
     */
    public function footer()
    {
        self::pluginHandle()->footer($this);
    }

    /**
     * Bí danh bộ nhớ cookie đầu ra
     *
     * @param string $cookieName Tên cookie được ghi nhớ
     * @param boolean $return Có nên quay lại không
     * @return string|void
     */
    public function remember(string $cookieName, bool $return = false)
    {
        $cookieName = strtolower($cookieName);
        if (!in_array($cookieName, ['author', 'mail', 'url'])) {
            return '';
        }

        $value = Cookie::get('__typecho_remember_' . $cookieName);
        if ($return) {
            return $value;
        } else {
            echo htmlspecialchars($value ?? '');
        }
    }

    /**
     * Tiêu đề lưu trữ đầu ra
     *
     * @param mixed $defines
     * @param string $before
     * @param string $end
     */
    public function archiveTitle($defines = null, string $before = ' &raquo; ', string $end = '')
    {
        if ($this->archiveTitle) {
            $define = '%s';
            if (is_array($defines) && !empty($defines[$this->archiveType])) {
                $define = $defines[$this->archiveType];
            }

            echo $before . sprintf($define, $this->archiveTitle) . $end;
        }
    }

    /**
     * Từ khóa đầu ra
     *
     * @param string $split
     * @param string $default
     */
    public function keywords(string $split = ',', string $default = '')
    {
        echo empty($this->keywords) ? $default : str_replace(',', $split, htmlspecialchars($this->keywords ?? ''));
    }

    /**
     * Nhận tập tin chủ đề
     *
     * @param string $fileName tập tin chủ đề
     */
    public function need(string $fileName)
    {
        require $this->themeDir . $fileName;
    }

    /**
     * Chế độ xem đầu ra
     * @throws WidgetException
     */
    public function render()
    {
        /** Xử lý các bước nhảy liên kết tĩnh */
        $this->checkPermalink();

        /** Thêm Pingback */
        if (2 == $this->options->allowXmlRpc) {
            $this->response->setHeader('X-Pingback', $this->options->xmlRpcUrl);
        }
        $validated = false;

        //~ Mẫu tùy chỉnh
        if (!empty($this->themeFile)) {
            if (file_exists($this->themeDir . $this->themeFile)) {
                $validated = true;
            }
        }

        if (!$validated && !empty($this->archiveType)) {
            //~ Trước tiên hãy tìm đường dẫn cụ thể, chẳng hạn như Category/default.php
            if (!$validated && !empty($this->archiveSlug)) {
                $themeFile = $this->archiveType . '/' . $this->archiveSlug . '.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $validated = true;
                }
            }

            //~ Sau đó tìm đường dẫn loại lưu trữ, chẳng hạn như Category.php
            if (!$validated) {
                $themeFile = $this->archiveType . '.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $validated = true;
                }
            }

            // Móc để đính kèm
            if (!$validated && 'attachment' == $this->archiveType) {
                if (file_exists($this->themeDir . 'page.php')) {
                    $this->themeFile = 'page.php';
                    $validated = true;
                } elseif (file_exists($this->themeDir . 'post.php')) {
                    $this->themeFile = 'post.php';
                    $validated = true;
                }
            }

            //~ Cuối cùng, tìm đường dẫn lưu trữ, chẳng hạn như archive.php hoặc single.php
            if (!$validated && 'index' != $this->archiveType && 'front' != $this->archiveType) {
                $themeFile = $this->archiveSingle ? 'single.php' : 'archive.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $validated = true;
                }
            }

            if (!$validated) {
                $themeFile = 'index.php';
                if (file_exists($this->themeDir . $themeFile)) {
                    $this->themeFile = $themeFile;
                    $validated = true;
                }
            }
        }

        /** Tập tin không tồn tại */
        if (!$validated) {
            throw new WidgetException(_t('Tập tin không tồn tại!'), 500);
        }

        /** Plugin kết nối */
        self::pluginHandle()->beforeRender($this);

        /** Mẫu đầu ra */
        require_once $this->themeDir . $this->themeFile;

        /** Plugin kết nối */
        self::pluginHandle()->afterRender($this);
    }

    /**
     * Nguồn cấp dữ liệu đầu ra
     *
     * @throws WidgetException
     */
    public function feed()
    {
        if ($this->feedType == Feed::RSS1) {
            $feedUrl = $this->feedRssUrl;
        } elseif ($this->feedType == Feed::ATOM1) {
            $feedUrl = $this->feedAtomUrl;
        } else {
            $feedUrl = $this->feedUrl;
        }

        $this->checkPermalink($feedUrl);

        $this->feed->setSubTitle($this->description);
        $this->feed->setFeedUrl($feedUrl);
        $this->feed->setBaseUrl($this->archiveUrl);

        if ($this->is('single') || 'comments' == $this->parameter->type) {
            $this->feed->setTitle(_t(
                'Bình luận của %s',
                $this->options->title . ($this->archiveTitle ? ' - ' . $this->archiveTitle : null)
            ));

            if ('comments' == $this->parameter->type) {
                $comments = Recent::alloc('pageSize=10');
            } else {
                $comments = Recent::alloc('pageSize=10&parentId=' . $this->cid);
            }

            while ($comments->next()) {
                $suffix = self::pluginHandle()->trigger($plugged)->commentFeedItem($this->feedType, $comments);
                if (!$plugged) {
                    $suffix = null;
                }

                $this->feed->addItem([
                    'title'   => $comments->author,
                    'content' => $comments->content,
                    'date'    => $comments->created,
                    'link'    => $comments->permalink,
                    'author'  => (object)[
                        'screenName' => $comments->author,
                        'url'        => $comments->url,
                        'mail'       => $comments->mail
                    ],
                    'excerpt' => strip_tags($comments->content),
                    'suffix'  => $suffix
                ]);
            }
        } else {
            $this->feed->setTitle($this->options->title . ($this->archiveTitle ? ' - ' . $this->archiveTitle : null));

            while ($this->next()) {
                $suffix = self::pluginHandle()->trigger($plugged)->feedItem($this->feedType, $this);
                if (!$plugged) {
                    $suffix = null;
                }

                $feedUrl = '';
                if (Feed::RSS2 == $this->feedType) {
                    $feedUrl = $this->feedUrl;
                } elseif (Feed::RSS1 == $this->feedType) {
                    $feedUrl = $this->feedRssUrl;
                } elseif (Feed::ATOM1 == $this->feedType) {
                    $feedUrl = $this->feedAtomUrl;
                }

                $this->feed->addItem([
                    'title'           => $this->title,
                    'content'         => $this->options->feedFullText ? $this->content
                        : (false !== strpos($this->text, '<!--more-->') ? $this->excerpt .
                            "<p class=\"more\"><a href=\"{$this->permalink}\" title=\"{$this->title}\">[...]</a></p>"
                            : $this->content),
                    'date'            => $this->created,
                    'link'            => $this->permalink,
                    'author'          => $this->author,
                    'excerpt'         => $this->___description(),
                    'comments'        => $this->commentsNum,
                    'commentsFeedUrl' => $feedUrl,
                    'suffix'          => $suffix
                ]);
            }
        }

        $this->response->setContentType($this->feedContentType);
        echo (string) $this->feed;
    }

    /**
     * bình luận của %s
     *
     * @access public
     * @param string $archiveType Loại lưu trữ
     * @param string|null $archiveSlug Tên lưu trữ
     * @return boolean
     */
    public function is(string $archiveType, ?string $archiveSlug = null)
    {
        return ($archiveType == $this->archiveType ||
                (($this->archiveSingle ? 'single' : 'archive') == $archiveType && 'index' != $this->archiveType) ||
                ('index' == $archiveType && $this->makeSinglePageAsFrontPage))
            && (empty($archiveSlug) || $archiveSlug == $this->archiveSlug);
    }

    /**
     * Gửi truy vấn
     *
     * @param mixed $select Đối tượng truy vấn
     * @throws Db\Exception
     */
    public function query($select)
    {
        self::pluginHandle()->trigger($queryPlugged)->query($this, $select);
        if (!$queryPlugged) {
            $this->db->fetchAll($select, [$this, 'push']);
        }
    }

    /**
     * địa chỉ bình luận
     *
     * @return string
     */
    protected function ___commentUrl(): string
    {
        /** Tạo địa chỉ phản hồi */
        /** Bình luận */
        $commentUrl = parent::___commentUrl();

        // Nhận xét của phụ huynh không dựa vào js
        $reply = $this->request->filter('int')->replyTo;
        if ($reply && $this->is('single')) {
            $commentUrl .= '?parent=' . $reply;
        }

        return $commentUrl;
    }

    /**
     * Nhập đối tượng
     *
     * @param Archive $widget Đối tượng cần nhập khẩu
     */
    private function import(Archive $widget)
    {
        $currentProperties = get_object_vars($this);

        foreach ($currentProperties as $name => $value) {
            if (false !== strpos('|request|response|parameter|feed|feedType|currentFeedUrl|', '|' . $name . '|')) {
                continue;
            }

            if (isset($widget->{$name})) {
                $this->{$name} = $widget->{$name};
            } else {
                $method = ucfirst($name);
                $setMethod = 'set' . $method;
                $getMethod = 'get' . $method;

                if (
                    method_exists($this, $setMethod)
                    && method_exists($widget, $getMethod)
                ) {
                    $value = $widget->{$getMethod}();

                    if ($value !== null) {
                        $this->{$setMethod}($widget->{$getMethod}());
                    }
                }
            }
        }
    }

    /**
     * Kiểm tra xem liên kết có đúng không
     *
     * @param string|null $permalink
     */
    private function checkPermalink(?string $permalink = null)
    {
        if (!isset($permalink)) {
            $type = $this->parameter->type;

            if (
                in_array($type, ['index', 'comment_page', 404])
                || $this->makeSinglePageAsFrontPage    // Trang chủ tùy chỉnh không được xử lý
                || !$this->parameter->checkPermalink
            ) { // buộc đóng
                return;
            }

            if ($this->archiveSingle) {
                $permalink = $this->permalink;
            } else {
                $value = array_merge($this->pageRow, [
                    'page' => $this->currentPage
                ]);

                $path = Router::url($type, $value);
                $permalink = Common::url($path, $this->options->index);
            }
        }

        $requestUrl = $this->request->getRequestUrl();

        $src = parse_url($permalink);
        $target = parse_url($requestUrl);

        if ($src['host'] != $target['host'] || urldecode($src['path']) != urldecode($target['path'])) {
            $this->response->redirect($permalink, true);
        }
    }

    /**
     * Xử lý chỉ mục
     *
     * @param Query $select Đối tượng truy vấn
     * @param boolean $hasPushed Cho dù nó đã được đẩy vào hàng đợi
     */
    private function indexHandle(Query $select, bool &$hasPushed)
    {
        $select->where('table.contents.type = ?', 'post');

        /** Giao diện trình cắm */
        self::pluginHandle()->indexHandle($this, $select);
    }

    /**
     * Xử lý lưu trữ ngoài trang chủ mặc định
     *
     * @param Query $select Đối tượng truy vấn
     * @param boolean $hasPushed Cho dù nó đã được đẩy vào hàng đợi
     * @throws WidgetException
     */
    private function archiveEmptyHandle(Query $select, bool &$hasPushed)
    {
        throw new WidgetException(_t('Địa chỉ được yêu cầu không tồn tại!'), 404);
    }

    /**
     * xử lý trang 404
     *
     * @param Query $select Đối tượng truy vấn
     * @param boolean $hasPushed Cho dù nó đã được đẩy vào hàng đợi
     */
    private function error404Handle(Query $select, bool &$hasPushed)
    {
        /** Đặt tiêu đề */
        $this->response->setStatus(404);

        /** Đặt tiêu đề */
        $this->archiveTitle = _t('Không tìm thấy trang!');

        /** Đặt loại lưu trữ */
        $this->archiveType = 'archive';

        /** Đặt chữ viết tắt lưu trữ */
        $this->archiveSlug = 404;

        /** Thiết lập mẫu lưu trữ */
        $this->themeFile = '404.php';

        /** Đặt một loại lưu trữ duy nhất */
        $this->archiveSingle = false;

        $hasPushed = true;

        /** Giao diện trình cắm */
        self::pluginHandle()->error404Handle($this, $select);
    }

    /**
     * xử lý trang độc lập
     *
     * @param Query $select Đối tượng truy vấn
     * @param boolean $hasPushed Cho dù nó đã được đẩy vào hàng đợi
     * @throws WidgetException|Db\Exception
     */
    private function singleHandle(Query $select, bool &$hasPushed)
    {
        if ('comment_page' == $this->parameter->type) {
            $params = [];
            $matched = Router::match($this->request->permalink);

            if ($matched && $matched instanceof Archive && $matched->is('single')) {
                $this->import($matched);
                $hasPushed = true;
                return;
            }
        }

        /** Hai cài đặt này là nâng cao để đảm bảo rằng khi gọi plugin truy vấn, plugin có thể được sử dụng để xác định loại lưu trữ sơ bộ. */
        /** Nếu bạn cần phán đoán chi tiết hơn, bạn có thể sử dụng singleHandle để đạt được điều đó. */
        $this->archiveSingle = true;

        /** Loại lưu trữ mặc định */
        $this->archiveType = 'single';

        /** kiểu khớp */

        if ('single' != $this->parameter->type) {
            $select->where('table.contents.type = ?', $this->parameter->type);
        }

        /** Nếu đó là một bài viết hoặc một trang độc lập */
        if (isset($this->request->cid)) {
            $select->where('table.contents.cid = ?', $this->request->filter('int')->cid);
        }

        /** Viết tắt trận đấu */
        if (isset($this->request->slug) && !$this->parameter->preview) {
            $select->where('table.contents.slug = ?', $this->request->slug);
        }

        /** Thời gian thi đấu */
        if (isset($this->request->year) && !$this->parameter->preview) {
            $year = $this->request->filter('int')->year;

            $fromMonth = 1;
            $toMonth = 12;

            $fromDay = 1;
            $toDay = 31;

            if (isset($this->request->month)) {
                $fromMonth = $this->request->filter('int')->month;
                $toMonth = $fromMonth;

                $fromDay = 1;
                $toDay = date('t', mktime(0, 0, 0, $toMonth, 1, $year));

                if (isset($this->request->day)) {
                    $fromDay = $this->request->filter('int')->day;
                    $toDay = $fromDay;
                }
            }

            /** Nhận dấu thời gian unix khi bắt đầu giờ GMT */
            $from = mktime(0, 0, 0, $fromMonth, $fromDay, $year)
                - $this->options->timezone + $this->options->serverTimezone;
            $to = mktime(23, 59, 59, $toMonth, $toDay, $year)
                - $this->options->timezone + $this->options->serverTimezone;
            $select->where('table.contents.created >= ? AND table.contents.created < ?', $from, $to);
        }

        /** Lưu mật khẩu vào cookie */
        $isPasswordPosted = false;

        if (
            $this->request->isPost()
            && isset($this->request->protectPassword)
            && !$this->parameter->preview
        ) {
            $this->security->protect();
            Cookie::set(
                'protectPassword_' . $this->request->filter('int')->protectCID,
                $this->request->protectPassword
            );

            $isPasswordPosted = true;
        }

        /** kiểu khớp */
        $select->limit(1);
        $this->query($select);

        if (
            !$this->have()
            || (isset($this->request->category)
                && $this->category != $this->request->category && !$this->parameter->preview)
            || (isset($this->request->directory)
                && $this->request->directory != implode('/', $this->directory) && !$this->parameter->preview)
        ) {
            if (!$this->invokeFromOutside) {
                /** Phán quyết không có chỉ số */
                throw new WidgetException(_t('Địa chỉ được yêu cầu không tồn tại!'), 404);
            } else {
                $hasPushed = true;
                return;
            }
        }

        /** Logic phán đoán dạng mật khẩu */
        if ($isPasswordPosted && $this->hidden) {
            throw new WidgetException(_t('Xin lỗi, mật khẩu bạn nhập sai!'), 403);
        }

        /** Đặt mẫu */
        if ($this->template) {
            /** Áp dụng mẫu tùy chỉnh */
            $this->themeFile = $this->template;
        }

        /** Đặt nguồn cấp dữ liệu tiêu đề */
        /** RSS 2.0 */

        // Sử dụng biến toàn cục cho trang chủ tùy chỉnh
        if (!$this->makeSinglePageAsFrontPage) {
            $this->feedUrl = $this->row['feedUrl'];

            /** RSS 1.0 */
            $this->feedRssUrl = $this->row['feedRssUrl'];

            /** ATOM 1.0 */
            $this->feedAtomUrl = $this->row['feedAtomUrl'];

            /** Đặt tiêu đề */
            $this->archiveTitle = $this->title;

            /** Đặt từ khóa */
            $this->keywords = implode(',', array_column($this->tags, 'name'));

            /** Mô tả cài đặt */
            $this->description = $this->___description();
        }

        /** Đặt loại lưu trữ */
        [$this->archiveType] = explode('_', $this->type);

        /** Đặt chữ viết tắt lưu trữ */
        $this->archiveSlug = ('post' == $this->type || 'attachment' == $this->type) ? $this->cid : $this->slug;

        /** Đặt địa chỉ lưu trữ */
        $this->archiveUrl = $this->permalink;

        /** Đặt tiêu đề 403 */
        if ($this->hidden) {
            $this->response->setStatus(403);
        }

        $hasPushed = true;

        /** Giao diện trình cắm */
        self::pluginHandle()->singleHandle($this, $select);
    }

    /**
     * Phân loại quy trình
     *
     * @param Query $select Đối tượng truy vấn
     * @param boolean $hasPushed Cho dù nó đã được đẩy vào hàng đợi
     * @throws WidgetException|Db\Exception
     */
    private function categoryHandle(Query $select, bool &$hasPushed)
    {
        /** Nếu đó là sự phân loại */
        $categorySelect = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->limit(1);

        if (isset($this->request->mid)) {
            $categorySelect->where('mid = ?', $this->request->filter('int')->mid);
        }

        if (isset($this->request->slug)) {
            $categorySelect->where('slug = ?', $this->request->slug);
        }

        if (isset($this->request->directory)) {
            $directory = explode('/', $this->request->directory);
            $categorySelect->where('slug = ?', $directory[count($directory) - 1]);
        }

        $category = $this->db->fetchRow($categorySelect);
        if (empty($category)) {
            throw new WidgetException(_t('Danh mục không tồn tại!'), 404);
        }

        $categoryListWidget = Rows::alloc('current=' . $category['mid']);
        $category = $categoryListWidget->filter($category);

        if (isset($directory) && ($this->request->directory != implode('/', $category['directory']))) {
            throw new WidgetException(_t('Danh mục mẹ không tồn tại!'), 404);
        }

        $children = $categoryListWidget->getAllChildren($category['mid']);
        $children[] = $category['mid'];

        /** fix sql92 by 70 */
        $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid IN ?', $children)
            ->where('table.contents.type = ?', 'post')
            ->group('table.contents.cid');

        /** Đặt phân trang */
        $this->pageRow = array_merge($category, [
            'slug'      => urlencode($category['slug']),
            'directory' => implode('/', array_map('urlencode', $category['directory']))
        ]);

        /** Đặt từ khóa */
        $this->keywords = $category['name'];

        /** Mô tả cài đặt */
        $this->description = $category['description'];

        /** Đặt nguồn cấp dữ liệu tiêu đề */
        /** RSS 2.0 */
        $this->feedUrl = $category['feedUrl'];

        /** RSS 1.0 */
        $this->feedRssUrl = $category['feedRssUrl'];

        /** ATOM 1.0 */
        $this->feedAtomUrl = $category['feedAtomUrl'];

        /** Đặt tiêu đề */
        $this->archiveTitle = $category['name'];

        /** Đặt loại lưu trữ */
        $this->archiveType = 'category';

        /** Đặt chữ viết tắt lưu trữ */
        $this->archiveSlug = $category['slug'];

        /** Đặt địa chỉ lưu trữ */
        $this->archiveUrl = $category['permalink'];

        /** Giao diện trình cắm */
        self::pluginHandle()->categoryHandle($this, $select);
    }

    /**
     * Xử lý thẻ
     *
     * @param Query $select Đối tượng truy vấn
     * @param boolean $hasPushed Cho dù nó đã được đẩy vào hàng đợi
     * @throws WidgetException|Db\Exception
     */
    private function tagHandle(Query $select, bool &$hasPushed)
    {
        $tagSelect = $this->db->select()->from('table.metas')
            ->where('type = ?', 'tag')->limit(1);

        if (isset($this->request->mid)) {
            $tagSelect->where('mid = ?', $this->request->filter('int')->mid);
        }

        if (isset($this->request->slug)) {
            $tagSelect->where('slug = ?', $this->request->slug);
        }

        /** Nếu là nhãn */
        $tag = $this->db->fetchRow(
            $tagSelect,
            [Metas::alloc(), 'filter']
        );

        if (!$tag) {
            throw new WidgetException(_t('Thẻ không tồn tại!'), 404);
        }

        /** fix sql92 by 70 */
        $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid = ?', $tag['mid'])
            ->where('table.contents.type = ?', 'post');

        /** Đặt phân trang */
        $this->pageRow = array_merge($tag, [
            'slug' => urlencode($tag['slug'])
        ]);

        /** Đặt từ khóa */
        $this->keywords = $tag['name'];

        /** Mô tả cài đặt */
        $this->description = $tag['description'];

        /** Đặt nguồn cấp dữ liệu tiêu đề */
        /** RSS 2.0 */
        $this->feedUrl = $tag['feedUrl'];

        /** RSS 1.0 */
        $this->feedRssUrl = $tag['feedRssUrl'];

        /** ATOM 1.0 */
        $this->feedAtomUrl = $tag['feedAtomUrl'];

        /** Đặt tiêu đề */
        $this->archiveTitle = $tag['name'];

        /** Đặt loại lưu trữ */
        $this->archiveType = 'tag';

        /** Đặt chữ viết tắt lưu trữ */
        $this->archiveSlug = $tag['slug'];

        /** Đặt địa chỉ lưu trữ */
        $this->archiveUrl = $tag['permalink'];

        /** Giao diện trình cắm */
        self::pluginHandle()->tagHandle($this, $select);
    }

    /**
     * xử lý tác giả
     *
     * @param Query $select Đối tượng truy vấn
     * @param boolean $hasPushed Cho dù nó đã được đẩy vào hàng đợi
     * @throws WidgetException|Db\Exception
     */
    private function authorHandle(Query $select, bool &$hasPushed)
    {
        $uid = $this->request->filter('int')->uid;

        $author = $this->db->fetchRow(
            $this->db->select()->from('table.users')
            ->where('uid = ?', $uid),
            [User::alloc(), 'filter']
        );

        if (!$author) {
            throw new WidgetException(_t('Tác giả không tồn tại!'), 404);
        }

        $select->where('table.contents.authorId = ?', $uid)
            ->where('table.contents.type = ?', 'post');

        /** Đặt phân trang */
        $this->pageRow = $author;

        /** Đặt từ khóa */
        $this->keywords = $author['screenName'];

        /** Mô tả cài đặt */
        $this->description = $author['screenName'];

        /** Đặt nguồn cấp dữ liệu tiêu đề */
        /** RSS 2.0 */
        $this->feedUrl = $author['feedUrl'];

        /** RSS 1.0 */
        $this->feedRssUrl = $author['feedRssUrl'];

        /** ATOM 1.0 */
        $this->feedAtomUrl = $author['feedAtomUrl'];

        /** Đặt tiêu đề */
        $this->archiveTitle = $author['screenName'];

        /** Đặt loại lưu trữ */
        $this->archiveType = 'author';

        /** Đặt chữ viết tắt lưu trữ */
        $this->archiveSlug = $author['uid'];

        /** Đặt địa chỉ lưu trữ */
        $this->archiveUrl = $author['permalink'];

        /** Giao diện trình cắm */
        self::pluginHandle()->authorHandle($this, $select);
    }

    /**
     * Ngày xử lý
     *
     * @access private
     * @param Query $select Đối tượng truy vấn
     * @param boolean $hasPushed Cho dù nó đã được đẩy vào hàng đợi
     * @return void
     */
    private function dateHandle(Query $select, &$hasPushed)
    {
        /** Nếu bạn nộp đơn theo ngày */
        $year = $this->request->filter('int')->year;
        $month = $this->request->filter('int')->month;
        $day = $this->request->filter('int')->day;

        if (!empty($year) && !empty($month) && !empty($day)) {

            /** Nếu bạn lưu trữ theo ngày */
            $from = mktime(0, 0, 0, $month, $day, $year);
            $to = mktime(23, 59, 59, $month, $day, $year);

            /** Lưu trữ viết tắt */
            $this->archiveSlug = 'day';

            /** Đặt tiêu đề */
            $this->archiveTitle = _t('%d-%d-%d', $year, $month, $day);
        } elseif (!empty($year) && !empty($month)) {

            /** Nếu nộp theo tháng */
            $from = mktime(0, 0, 0, $month, 1, $year);
            $to = mktime(23, 59, 59, $month, date('t', $from), $year);

            /** Lưu trữ viết tắt */
            $this->archiveSlug = 'month';

            /** Đặt tiêu đề */
            $this->archiveTitle = _t('%d-%d', $year, $month);
        } elseif (!empty($year)) {

            /** Nếu nộp theo năm */
            $from = mktime(0, 0, 0, 1, 1, $year);
            $to = mktime(23, 59, 59, 12, 31, $year);

            /** Lưu trữ viết tắt */
            $this->archiveSlug = 'year';

            /** Đặt tiêu đề */
            $this->archiveTitle = _t('%d năm', $year);
        }

        $select->where('table.contents.created >= ?', $from - $this->options->timezone + $this->options->serverTimezone)
            ->where('table.contents.created <= ?', $to - $this->options->timezone + $this->options->serverTimezone)
            ->where('table.contents.type = ?', 'post');

        /** Đặt loại lưu trữ */
        $this->archiveType = 'date';

        /** Đặt nguồn cấp dữ liệu tiêu đề */
        $value = [
            'year' => $year,
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'day' => str_pad($day, 2, '0', STR_PAD_LEFT)
        ];

        /** Đặt phân trang */
        $this->pageRow = $value;

        /** Nhận lộ trình hiện tại và lọc ra các tình huống chuyển trang */
        $currentRoute = str_replace('_page', '', $this->parameter->type);

        /** RSS 2.0 */
        $this->feedUrl = Router::url($currentRoute, $value, $this->options->feedUrl);

        /** RSS 1.0 */
        $this->feedRssUrl = Router::url($currentRoute, $value, $this->options->feedRssUrl);

        /** ATOM 1.0 */
        $this->feedAtomUrl = Router::url($currentRoute, $value, $this->options->feedAtomUrl);

        /** Đặt địa chỉ lưu trữ */
        $this->archiveUrl = Router::url($currentRoute, $value, $this->options->index);

        /** Giao diện trình cắm */
        self::pluginHandle()->dateHandle($this, $select);
    }

    /**
     * Xử lý tìm kiếm
     *
     * @access private
     * @param Query $select Đối tượng truy vấn
     * @param boolean $hasPushed Cho dù nó đã được đẩy vào hàng đợi
     * @return void
     */
    private function searchHandle(Query $select, &$hasPushed)
    {
        /** Thêm giao diện công cụ tìm kiếm tùy chỉnh */
        //~ fix issue 40
        $keywords = $this->request->filter('url', 'search')->keywords;
        self::pluginHandle()->trigger($hasPushed)->search($keywords, $this);

        if (!$hasPushed) {
            $searchQuery = '%' . str_replace(' ', '%', $keywords) . '%';

            /** Tìm kiếm không thể vào kho lưu trữ bảo vệ quyền riêng tư */
            if ($this->user->hasLogin()) {
                //~ fix issue 941
                $select->where("table.contents.password IS NULL
                 OR table.contents.password = '' OR table.contents.authorId = ?", $this->user->uid);
            } else {
                $select->where("table.contents.password IS NULL OR table.contents.password = ''");
            }

            $op = $this->db->getAdapter()->getDriver() == 'pgsql' ? 'ILIKE' : 'LIKE';

            $select->where("table.contents.title {$op} ? OR table.contents.text {$op} ?", $searchQuery, $searchQuery)
                ->where('table.contents.type = ?', 'post');
        }

        /** Đặt từ khóa */
        $this->keywords = $keywords;

        /** Đặt phân trang */
        $this->pageRow = ['keywords' => urlencode($keywords)];

        /** Đặt nguồn cấp dữ liệu tiêu đề */
        /** RSS 2.0 */
        $this->feedUrl = Router::url('search', ['keywords' => $keywords], $this->options->feedUrl);

        /** RSS 1.0 */
        $this->feedRssUrl = Router::url('search', ['keywords' => $keywords], $this->options->feedAtomUrl);

        /** ATOM 1.0 */
        $this->feedAtomUrl = Router::url('search', ['keywords' => $keywords], $this->options->feedAtomUrl);

        /** Đặt tiêu đề */
        $this->archiveTitle = $keywords;

        /** Đặt loại lưu trữ */
        $this->archiveType = 'search';

        /** Đặt chữ viết tắt lưu trữ */
        $this->archiveSlug = $keywords;

        /** Đặt địa chỉ lưu trữ */
        $this->archiveUrl = Router::url('search', ['keywords' => $keywords], $this->options->index);

        /** Giao diện trình cắm */
        self::pluginHandle()->searchHandle($this, $select);
    }
}
