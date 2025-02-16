<?php

namespace Typecho;

/**
 * Feed
 *
 * @package Feed
 */
class Feed
{
    /** Xác định loại RSS 1.0 */
    public const RSS1 = 'RSS 1.0';

    /** Xác định loại RSS 2.0 */
    public const RSS2 = 'RSS 2.0';

    /** Xác định các loại ATOM 1.0 */
    public const ATOM1 = 'ATOM 1.0';

    /** Xác định định dạng thời gian RSS */
    public const DATE_RFC822 = 'r';

    /** Xác định định dạng thời gian ATOM */
    public const DATE_W3CDTF = 'c';

    /** Xác định các đầu cuối dòng */
    public const EOL = "\n";

    /**
     * trạng thái nguồn cấp dữ liệu
     *
     * @access private
     * @var string
     */
    private $type;

    /**
     * Mã hóa bộ ký tự
     *
     * @access private
     * @var string
     */
    private $charset;

    /**
     * trạng thái ngôn ngữ
     *
     * @access private
     * @var string
     */
    private $lang;

    /**
     * địa chỉ tổng hợp
     *
     * @access private
     * @var string
     */
    private $feedUrl;

    /**
     * địa chỉ cơ sở
     *
     * @access private
     * @var string
     */
    private $baseUrl;

    /**
     * tiêu đề tổng hợp
     *
     * @access private
     * @var string
     */
    private $title;

    /**
     * Tổng hợp phụ đề
     *
     * @access private
     * @var string
     */
    private $subTitle;

    /**
     * Thông tin phiên bản
     *
     * @access private
     * @var string
     */
    private $version;

    /**
     * Tất cả các mục
     *
     * @access private
     * @var array
     */
    private $items = [];

    /**
     * Tạo đối tượng nguồn cấp dữ liệu
     *
     * @param $version
     * @param string $type
     * @param string $charset
     * @param string $lang
     */
    public function __construct($version, string $type = self::RSS2, string $charset = 'UTF-8', string $lang = 'vi')
    {
        $this->version = $version;
        $this->type = $type;
        $this->charset = $charset;
        $this->lang = $lang;
    }

    /**
     * Đặt tiêu đề
     *
     * @param string $title tiêu đề
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Đặt phụ đề
     *
     * @param string|null $subTitle phụ đề
     */
    public function setSubTitle(?string $subTitle)
    {
        $this->subTitle = $subTitle;
    }

    /**
     * Đặt địa chỉ tổng hợp
     *
     * @param string $feedUrl địa chỉ tổng hợp
     */
    public function setFeedUrl(string $feedUrl)
    {
        $this->feedUrl = $feedUrl;
    }

    /**
     * Đặt trang chủ
     *
     * @param string $baseUrl Địa chỉ trang chủ
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Định dạng của $item là
     * <code>
     * array (
     *     'title'      =>  'xxx',
     *     'content'    =>  'xxx',
     *     'excerpt'    =>  'xxx',
     *     'date'       =>  'xxx',
     *     'link'       =>  'xxx',
     *     'author'     =>  'xxx',
     *     'comments'   =>  'xxx',
     *     'commentsUrl'=>  'xxx',
     *     'commentsFeedUrl' => 'xxx',
     * )
     * </code>
     *
     * @param array $item
     */
    public function addItem(array $item)
    {
        $this->items[] = $item;
    }

    /**
     * Chuỗi đầu ra
     *
     * @return string
     */
    public function __toString(): string
    {
        $result = '<?xml version="1.0" encoding="' . $this->charset . '"?>' . self::EOL;

        if (self::RSS1 == $this->type) {
            $result .= '<rdf:RDF
xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
xmlns="http://purl.org/rss/1.0/"
xmlns:dc="http://purl.org/dc/elements/1.1/">' . self::EOL;

            $content = '';
            $links = [];
            $lastUpdate = 0;

            foreach ($this->items as $item) {
                $content .= '<item rdf:about="' . $item['link'] . '">' . self::EOL;
                $content .= '<title>' . htmlspecialchars($item['title']) . '</title>' . self::EOL;
                $content .= '<link>' . $item['link'] . '</link>' . self::EOL;
                $content .= '<dc:date>' . $this->dateFormat($item['date']) . '</dc:date>' . self::EOL;
                $content .= '<description>' . strip_tags($item['content']) . '</description>' . self::EOL;
                if (!empty($item['suffix'])) {
                    $content .= $item['suffix'];
                }
                $content .= '</item>' . self::EOL;

                $links[] = $item['link'];

                if ($item['date'] > $lastUpdate) {
                    $lastUpdate = $item['date'];
                }
            }

            $result .= '<channel rdf:about="' . $this->feedUrl . '">
<title>' . htmlspecialchars($this->title) . '</title>
<link>' . $this->baseUrl . '</link>
<description>' . htmlspecialchars($this->subTitle ?? '') . '</description>
<items>
<rdf:Seq>' . self::EOL;

            foreach ($links as $link) {
                $result .= '<rdf:li resource="' . $link . '"/>' . self::EOL;
            }

            $result .= '</rdf:Seq>
</items>
</channel>' . self::EOL;

            $result .= $content . '</rdf:RDF>';
        } elseif (self::RSS2 == $this->type) {
            $result .= '<rss version="2.0"
xmlns:content="http://purl.org/rss/1.0/modules/content/"
xmlns:dc="http://purl.org/dc/elements/1.1/"
xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
xmlns:atom="http://www.w3.org/2005/Atom"
xmlns:wfw="http://wellformedweb.org/CommentAPI/">
<channel>' . self::EOL;

            $content = '';
            $lastUpdate = 0;

            foreach ($this->items as $item) {
                $content .= '<item>' . self::EOL;
                $content .= '<title>' . htmlspecialchars($item['title']) . '</title>' . self::EOL;
                $content .= '<link>' . $item['link'] . '</link>' . self::EOL;
                $content .= '<guid>' . $item['link'] . '</guid>' . self::EOL;
                $content .= '<pubDate>' . $this->dateFormat($item['date']) . '</pubDate>' . self::EOL;
                $content .= '<dc:creator>' . htmlspecialchars($item['author']->screenName)
                    . '</dc:creator>' . self::EOL;

                if (!empty($item['category']) && is_array($item['category'])) {
                    foreach ($item['category'] as $category) {
                        $content .= '<category><![CDATA[' . $category['name'] . ']]></category>' . self::EOL;
                    }
                }

                if (!empty($item['excerpt'])) {
                    $content .= '<description><![CDATA[' . strip_tags($item['excerpt'])
                        . ']]></description>' . self::EOL;
                }

                if (!empty($item['content'])) {
                    $content .= '<content:encoded xml:lang="' . $this->lang . '"><![CDATA['
                        . self::EOL .
                        $item['content'] . self::EOL .
                        ']]></content:encoded>' . self::EOL;
                }

                if (isset($item['comments']) && strlen($item['comments']) > 0) {
                    $content .= '<slash:comments>' . $item['comments'] . '</slash:comments>' . self::EOL;
                }

                $content .= '<comments>' . $item['link'] . '#comments</comments>' . self::EOL;
                if (!empty($item['commentsFeedUrl'])) {
                    $content .= '<wfw:commentRss>' . $item['commentsFeedUrl'] . '</wfw:commentRss>' . self::EOL;
                }

                if (!empty($item['suffix'])) {
                    $content .= $item['suffix'];
                }

                $content .= '</item>' . self::EOL;

                if ($item['date'] > $lastUpdate) {
                    $lastUpdate = $item['date'];
                }
            }

            $result .= '<title>' . htmlspecialchars($this->title) . '</title>
<link>' . $this->baseUrl . '</link>
<atom:link href="' . $this->feedUrl . '" rel="self" type="application/rss+xml" />
<language>' . $this->lang . '</language>
<description>' . htmlspecialchars($this->subTitle ?? '') . '</description>
<lastBuildDate>' . $this->dateFormat($lastUpdate) . '</lastBuildDate>
<pubDate>' . $this->dateFormat($lastUpdate) . '</pubDate>' . self::EOL;

            $result .= $content . '</channel>
</rss>';
        } elseif (self::ATOM1 == $this->type) {
            $result .= '<feed xmlns="http://www.w3.org/2005/Atom"
xmlns:thr="http://purl.org/syndication/thread/1.0"
xml:lang="' . $this->lang . '"
xml:base="' . $this->baseUrl . '"
>' . self::EOL;

            $content = '';
            $lastUpdate = 0;

            foreach ($this->items as $item) {
                $content .= '<entry>' . self::EOL;
                $content .= '<title type="html"><![CDATA[' . $item['title'] . ']]></title>' . self::EOL;
                $content .= '<link rel="alternate" type="text/html" href="' . $item['link'] . '" />' . self::EOL;
                $content .= '<id>' . $item['link'] . '</id>' . self::EOL;
                $content .= '<updated>' . $this->dateFormat($item['date']) . '</updated>' . self::EOL;
                $content .= '<published>' . $this->dateFormat($item['date']) . '</published>' . self::EOL;
                $content .= '<author>
    <name>' . $item['author']->screenName . '</name>
    <uri>' . $item['author']->url . '</uri>
</author>' . self::EOL;

                if (!empty($item['category']) && is_array($item['category'])) {
                    foreach ($item['category'] as $category) {
                        $content .= '<category scheme="' . $category['permalink'] . '" term="'
                            . $category['name'] . '" />' . self::EOL;
                    }
                }

                if (!empty($item['excerpt'])) {
                    $content .= '<summary type="html"><![CDATA[' . htmlspecialchars($item['excerpt'])
                        . ']]></summary>' . self::EOL;
                }

                if (!empty($item['content'])) {
                    $content .= '<content type="html" xml:base="' . $item['link']
                        . '" xml:lang="' . $this->lang . '"><![CDATA['
                        . self::EOL .
                        $item['content'] . self::EOL .
                        ']]></content>' . self::EOL;
                }

                if (isset($item['comments']) && strlen($item['comments']) > 0) {
                    $content .= '<link rel="replies" type="text/html" href="' . $item['link']
                        . '#comments" thr:count="' . $item['comments'] . '" />' . self::EOL;

                    if (!empty($item['commentsFeedUrl'])) {
                        $content .= '<link rel="replies" type="application/atom+xml" href="'
                            . $item['commentsFeedUrl'] . '" thr:count="' . $item['comments'] . '"/>' . self::EOL;
                    }
                }

                if (!empty($item['suffix'])) {
                    $content .= $item['suffix'];
                }

                $content .= '</entry>' . self::EOL;

                if ($item['date'] > $lastUpdate) {
                    $lastUpdate = $item['date'];
                }
            }

            $result .= '<title type="text">' . htmlspecialchars($this->title) . '</title>
<subtitle type="text">' . htmlspecialchars($this->subTitle ?? '') . '</subtitle>
<updated>' . $this->dateFormat($lastUpdate) . '</updated>
<generator uri="https://wapvn.top/" version="' . $this->version . '">Typecho</generator>
<link rel="alternate" type="text/html" href="' . $this->baseUrl . '" />
<id>' . $this->feedUrl . '</id>
<link rel="self" type="application/atom+xml" href="' . $this->feedUrl . '" />
';
            $result .= $content . '</feed>';
        }

        return $result;
    }

    /**
     * Nhận định dạng thời gian cấp dữ liệu
     *
     * @param integer $stamp Dấu thời gian
     * @return string
     */
    public function dateFormat(int $stamp): string
    {
        if (self::RSS2 == $this->type) {
            return date(self::DATE_RFC822, $stamp);
        } elseif (self::RSS1 == $this->type || self::ATOM1 == $this->type) {
            return date(self::DATE_W3CDTF, $stamp);
        }

        return '';
    }
}
