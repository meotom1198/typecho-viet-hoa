<?php

namespace Widget;

use Typecho\Common;
use Widget\Plugins\Config;
use Widget\Themes\Files;
use Widget\Users\Edit as UsersEdit;
use Widget\Contents\Attachment\Edit as AttachmentEdit;
use Widget\Contents\Post\Edit as PostEdit;
use Widget\Contents\Page\Edit as PageEdit;
use Widget\Contents\Post\Admin as PostAdmin;
use Widget\Comments\Admin as CommentsAdmin;
use Widget\Metas\Category\Admin as CategoryAdmin;
use Widget\Metas\Category\Edit as CategoryEdit;
use Widget\Metas\Tag\Admin as TagAdmin;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Hiển thị menu phụ trợ
 *
 * @package Widget
 */
class Menu extends Base
{
    /**
     * Tiêu đề thực đơn hiện tại
     * @var string
     */
    public $title;

    /**
     * Hiện đang thêm liên kết dự án
     * @var string
     */
    public $addLink;

    /**
     * Danh sách thực đơn dành cho phụ huynh
     *
     * @var array
     */
    private $menu = [];

    /**
     * Menu cha mẹ hiện tại
     *
     * @var integer
     */
    private $currentParent = 1;

    /**
     * Menu con hiện tại
     *
     * @var integer
     */
    private $currentChild = 0;

    /**
     * trang hiện tại
     *
     * @var string
     */
    private $currentUrl;

    /**
     * Thực thi chức năng, khởi tạo menu
     */
    public function execute()
    {
        $parentNodes = [null, _t('<i class="fa-solid fa-gauge"></i> Bảng điều khiển'), _t('<i class="fa-solid fa-file-pen"></i> Viết'), _t('<i class="fa-solid fa-screwdriver-wrench"></i> Quản lý'), _t('<i class="fa-solid fa-gears"></i> Cài đặt')];

        $childNodes = [
            [
                [_t('<i class="fa-solid fa-user"></i> Đăng nhập'), _t('Đăng nhập - %s', $this->options->title), 'login.php', 'visitor'],
                [_t('<i class="fa-solid fa-user-plus"></i> Đăng ký'), _t('Đăng ký - %s', $this->options->title), 'register.php', 'visitor']
            ],
            [
                [_t('<i class="fa-solid fa-briefcase"></i> Tổng quan'), _t('Tổng quan về blog'), 'index.php', 'subscriber'],
                [_t('<i class="fa-regular fa-user"></i> Cá nhân'), _t('Cá nhân'), 'profile.php', 'subscriber'],
                [_t('<i class="fa-solid fa-puzzle-piece"></i> Plugins'), _t('Plugins'), 'plugins.php', 'administrator'],
                [[Config::class, 'getMenuTitle'], [Config::class, 'getMenuTitle'], 'options-plugin.php?config=', 'administrator', true],
                [_t('<i class="fa-solid fa-palette"></i> Giao diện'), _t('Giao diện'), 'themes.php', 'administrator'],
                [[Files::class, 'getMenuTitle'], [Files::class, 'getMenuTitle'], 'theme-editor.php', 'administrator', true],
                [_t('<i class="fa-solid fa-palette"></i> Cài đặt giao diện'), _t('Cài đặt giao diện'), 'options-theme.php', 'administrator', true],
                [_t('<i class="fa-solid fa-box"></i> Sao lưu'), _t('Sao lưu'), 'backup.php', 'administrator'],
                [_t('<i class="fa-solid fa-upload"></i> Nâng cấp'), _t('Nâng cấp'), 'upgrade.php', 'administrator', true],
                [_t('<i class="fa-solid fa-hand-scissors"></i> Chào mừng'), _t('Chào mừng'), 'welcome.php', 'subscriber', true]
            ],
            [
                [_t('<i class="fa-solid fa-file-pen"></i> Đăng bài viết'), _t('Đăng bài viết'), 'write-post.php', 'contributor'],
                [[PostEdit::class, 'getMenuTitle'], [PostEdit::class, 'getMenuTitle'], 'write-post.php?cid=', 'contributor', true],
                [_t('<i class="fa-solid fa-file-pen"></i> Tạo trang'), _t('Tạo trang'), 'write-page.php', 'editor'],
                [[PageEdit::class, 'getMenuTitle'], [PageEdit::class, 'getMenuTitle'], 'write-page.php?cid=', 'editor', true],
            ],
            [
                [_t('<i class="fa-solid fa-screwdriver-wrench"></i> Quản lý bài viết'), _t('Quản lý bài viết'), 'manage-posts.php', 'contributor', false, 'write-post.php'],
                [[PostAdmin::class, 'getMenuTitle'], [PostAdmin::class, 'getMenuTitle'], 'manage-posts.php?uid=', 'contributor', true],
                [_t('<i class="fa-solid fa-screwdriver-wrench"></i> Quản lý trang'), _t('Quản lý trang'), 'manage-pages.php', 'editor', false, 'write-page.php'],
                [_t('<i class="fa-solid fa-screwdriver-wrench"></i> Quản lý bình luận'), _t('Quản lý bình luận'), 'manage-comments.php', 'contributor'],
                [[CommentsAdmin::class, 'getMenuTitle'], [CommentsAdmin::class, 'getMenuTitle'], 'manage-comments.php?cid=', 'contributor', true],
                [_t('<i class="fa-solid fa-screwdriver-wrench"></i> Quản lý danh mục'), _t('Quản lý danh mục'), 'manage-categories.php', 'editor', false, 'category.php'],
                [_t('<i class="fa-solid fa-screwdriver-wrench"></i> Tạo danh mục'), _t('Tạo danh mục'), 'category.php', 'editor', true],
                [[CategoryAdmin::class, 'getMenuTitle'], [CategoryAdmin::class, 'getMenuTitle'], 'manage-categories.php?parent=', 'editor', true, [CategoryAdmin::class, 'getAddLink']],
                [[CategoryEdit::class, 'getMenuTitle'], [CategoryEdit::class, 'getMenuTitle'], 'category.php?mid=', 'editor', true],
                [[CategoryEdit::class, 'getMenuTitle'], [CategoryEdit::class, 'getMenuTitle'], 'category.php?parent=', 'editor', true],
                [_t('<i class="fa-solid fa-screwdriver-wrench"></i> Quản lý thẻ'), _t('Quản lý thẻ'), 'manage-tags.php', 'editor'],
                [[TagAdmin::class, 'getMenuTitle'], [TagAdmin::class, 'getMenuTitle'], 'manage-tags.php?mid=', 'editor', true],
                [_t('<i class="fa-solid fa-screwdriver-wrench"></i> Quản lý tập tin'), _t('Quản lý tập tin'), 'manage-medias.php', 'editor'],
                [[AttachmentEdit::class, 'getMenuTitle'], [AttachmentEdit::class, 'getMenuTitle'], 'media.php?cid=', 'contributor', true],
                [_t('<i class="fa-solid fa-screwdriver-wrench"></i> Quản lý tài khoản'), _t('Quản lý tài khoản'), 'manage-users.php', 'administrator', false, 'user.php'],
                [_t('<i class="fa-solid fa-screwdriver-wrench"></i> Tạo tài khoản'), _t('Tạo tài khoản'), 'user.php', 'administrator', true],
                [[UsersEdit::class, 'getMenuTitle'], [UsersEdit::class, 'getMenuTitle'], 'user.php?uid=', 'administrator', true],
            ],
            [
                [_t('<i class="fa-solid fa-gear"></i> Cài đặt cơ bản'), _t('Cài đặt cơ bản'), 'options-general.php', 'administrator'],
                [_t('<i class="fa-regular fa-comment-dots"></i> Cài đặt bình luận'), _t('Cài đặt bình luận'), 'options-discussion.php', 'administrator'],
                [_t('<i class="fa-solid fa-eye"></i> Cài đặt đọc'), _t('Cài đặt đọc'), 'options-reading.php', 'administrator'],
                [_t('<i class="fa-solid fa-link"></i> Cài đặt URL'), _t('Cài đặt URL'), 'options-permalink.php', 'administrator'],
            ]
        ];

        /** Nhận menu mở rộng */
        $panelTable = unserialize($this->options->panelTable);
        $extendingParentMenu = empty($panelTable['parent']) ? [] : $panelTable['parent'];
        $extendingChildMenu = empty($panelTable['child']) ? [] : $panelTable['child'];
        $currentUrl = $this->request->getRequestUrl();
        $adminUrl = $this->options->adminUrl;
        $menu = [];
        $defaultChildNode = [null, null, null, 'administrator', false, null];

        $currentUrlParts = parse_url($currentUrl);
        $currentUrlParams = [];
        if (!empty($currentUrlParts['query'])) {
            parse_str($currentUrlParts['query'], $currentUrlParams);
        }

        if ('/' == $currentUrlParts['path'][strlen($currentUrlParts['path']) - 1]) {
            $currentUrlParts['path'] .= 'index.php';
        }

        foreach ($extendingParentMenu as $key => $val) {
            $parentNodes[10 + $key] = $val;
        }

        foreach ($extendingChildMenu as $key => $val) {
            $childNodes[$key] = array_merge($childNodes[$key] ?? [], $val);
        }

        foreach ($parentNodes as $key => $parentNode) {
            // this is a simple struct than before
            $children = [];
            $showedChildrenCount = 0;
            $firstUrl = null;

            foreach ($childNodes[$key] as $inKey => $childNode) {
                // magic merge
                $childNode += $defaultChildNode;
                [$name, $title, $url, $access] = $childNode;

                $hidden = $childNode[4] ?? false;
                $addLink = $childNode[5] ?? null;

                // Lưu thông tin ẩn ban đầu
                $orgHidden = $hidden;

                // parse url
                $url = Common::url($url, $adminUrl);

                // compare url
                $urlParts = parse_url($url);
                $urlParams = [];
                if (!empty($urlParts['query'])) {
                    parse_str($urlParts['query'], $urlParams);
                }

                $validate = true;
                if ($urlParts['path'] != $currentUrlParts['path']) {
                    $validate = false;
                } else {
                    foreach ($urlParams as $paramName => $paramValue) {
                        if (!isset($currentUrlParams[$paramName])) {
                            $validate = false;
                            break;
                        }
                    }
                }

                if (
                    $validate
                    && basename($urlParts['path']) == 'extending.php'
                    && !empty($currentUrlParams['panel']) && !empty($urlParams['panel'])
                    && $urlParams['panel'] != $currentUrlParams['panel']
                ) {
                    $validate = false;
                }

                if ($hidden && $validate) {
                    $hidden = false;
                }

                if (!$hidden && !$this->user->pass($access, true)) {
                    $hidden = true;
                }

                if (!$hidden) {
                    $showedChildrenCount++;

                    if (empty($firstUrl)) {
                        $firstUrl = $url;
                    }

                    if (is_array($name)) {
                        [$widget, $method] = $name;
                        $name = self::widget($widget)->$method();
                    }

                    if (is_array($title)) {
                        [$widget, $method] = $title;
                        $title = self::widget($widget)->$method();
                    }

                    if (is_array($addLink)) {
                        [$widget, $method] = $addLink;
                        $addLink = self::widget($widget)->$method();
                    }
                }

                if ($validate) {
                    if ('visitor' != $access) {
                        $this->user->pass($access);
                    }

                    $this->currentParent = $key;
                    $this->currentChild = $inKey;
                    $this->title = $title;
                    $this->addLink = $addLink ? Common::url($addLink, $adminUrl) : null;
                }

                $children[$inKey] = [
                    $name,
                    $title,
                    $url,
                    $access,
                    $hidden,
                    $addLink,
                    $orgHidden
                ];
            }

            $menu[$key] = [$parentNode, $showedChildrenCount > 0, $firstUrl, $children];
        }

        $this->menu = $menu;
        $this->currentUrl = Common::safeUrl($currentUrl);
    }

    /**
     * Nhận menu hiện tại
     *
     * @return array
     */
    public function getCurrentMenu(): ?array
    {
        return $this->currentParent > 0 ? $this->menu[$this->currentParent][3][$this->currentChild] : null;
    }

    /**
     * Đầu ra menu cha
     */
    public function output($class = 'focus', $childClass = 'focus')
    {
        foreach ($this->menu as $key => $node) {
            if (!$node[1] || !$key) {
                continue;
            }

            echo "<ul class=\"root" . ($key == $this->currentParent ? ' ' . $class : null)
                . "\"><li class=\"parent\"><a href=\"{$node[2]}\">{$node[0]}</a>"
                . "</li><ul class=\"child\">";

            $last = 0;
            foreach ($node[3] as $inKey => $inNode) {
                if (!$inNode[4]) {
                    $last = $inKey;
                }
            }

            foreach ($node[3] as $inKey => $inNode) {
                if ($inNode[4]) {
                    continue;
                }

                $classes = [];
                if ($key == $this->currentParent && $inKey == $this->currentChild) {
                    $classes[] = $childClass;
                } elseif ($inNode[6]) {
                    continue;
                }

                if ($inKey == $last) {
                    $classes[] = 'last';
                }

                echo "<li" . (!empty($classes) ? ' class="' . implode(' ', $classes) . '"' : null) . "><a href=\""
                    . ($key == $this->currentParent && $inKey == $this->currentChild ? $this->currentUrl : $inNode[2])
                    . "\">{$inNode[0]}</a></li>";
            }

            echo "</ul></ul>";
        }
    }
}
