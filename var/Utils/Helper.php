<?php

namespace Utils;

use Typecho\Common;
use Typecho\Db;
use Typecho\I18n;
use Typecho\Plugin;
use Typecho\Widget;
use Widget\Base\Options as BaseOptions;
use Widget\Options;
use Widget\Plugins\Edit;
use Widget\Security;
use Widget\Service;

/**
 * Trình trợ giúp plugin sẽ xuất hiện theo mặc định trong tất cả các bản phân phối typecho.
 * Vì vậy, bạn có thể tự tin sử dụng các chức năng của nó để tạo điều kiện thuận lợi cho việc cài đặt plug-in của bạn trong hệ thống của người dùng.
 * @package Helper
 * @author qining
 * @version 1.0.0
 * @link http://wapvn.top
 */
class Helper
{
    /**
     * Lấy đối tượng Bảo mật
     *
     * @return Security
     */
    public static function security(): Security
    {
        return Security::alloc();
    }

    /**
     * Nhận một đối tượng Widget dựa trên ID
     *
     * @param string $table tên bảng, hỗ trợ contents, comments, metas, users
     * @param int $pkId
     * @return Widget|null
     */
    public static function widgetById(string $table, int $pkId): ?Widget
    {
        $table = ucfirst($table);
        if (!in_array($table, ['Contents', 'Comments', 'Metas', 'Users'])) {
            return null;
        }

        $keys = [
            'Contents' => 'cid',
            'Comments' => 'coid',
            'Metas'    => 'mid',
            'Users'    => 'uid'
        ];

        $className = '\Widget\Base\\' . $table;

        $key = $keys[$table];
        $db = Db::get();
        $widget = Widget::widget($className . '@' . $pkId);

        $db->fetchRow(
            $widget->select()->where("{$key} = ?", $pkId)->limit(1),
            [$widget, 'push']
        );

        return $widget;
    }

    /**
     * Yêu cầu dịch vụ không đồng bộ
     *
     * @param $method
     * @param $params
     */
    public static function requestService($method, $params)
    {
        Service::alloc()->requestService($method, $params);
    }

    /**
     * Buộc xóa một plugin
     *
     * @param string $pluginName Tên plugin
     */
    public static function removePlugin(string $pluginName)
    {
        try {
            /** Nhận mục nhập plugin */
            [$pluginFileName, $className] = Plugin::portal(
                $pluginName,
                __TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__
            );

            /** Nhận các plugin được kích hoạt */
            $plugins = Plugin::export();
            $activatedPlugins = $plugins['activated'];

            /** Tải plugin */
            require_once $pluginFileName;

            /** Xác định xem việc khởi tạo có thành công hay không */
            if (
                !isset($activatedPlugins[$pluginName]) || !class_exists($className)
                || !method_exists($className, 'deactivate')
            ) {
                throw new Widget\Exception(_t('Không thể tắt plugin'), 500);
            }

            call_user_func([$className, 'deactivate']);
        } catch (\Exception $e) {
            //nothing to do
        }

        $db = Db::get();

        try {
            Plugin::deactivate($pluginName);
            $db->query($db->update('table.options')
                ->rows(['value' => serialize(Plugin::export())])
                ->where('name = ?', 'plugins'));
        } catch (Plugin\Exception $e) {
            //nothing to do
        }

        $db->query($db->delete('table.options')->where('name = ?', 'plugin:' . $pluginName));
    }

    /**
     * Nhập các mục ngôn ngữ
     *
     * @param string $domain
     */
    public static function lang(string $domain)
    {
        $currentLang = I18n::getLang();
        if ($currentLang) {
            $currentLang = basename($currentLang);
            $fileName = dirname(__FILE__) . '/' . $domain . '/lang/' . $currentLang;
            if (file_exists($fileName)) {
                I18n::addLang($fileName);
            }
        }
    }

    /**
     * Thêm tuyến đường
     *
     * @param string $name Tên tuyến đường
     * @param string $url đường dẫn định tuyến
     * @param string $widget Tên thành phần
     * @param string|null $action Hành động thành phần
     * @param string|null $after đằng sau một tuyến đường
     * @return integer
     */
    public static function addRoute(
        string $name,
        string $url,
        string $widget,
        ?string $action = null,
        ?string $after = null
    ): int {
        $routingTable = self::options()->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        $pos = 0;
        foreach ($routingTable as $key => $val) {
            $pos++;

            if ($key == $after) {
                break;
            }
        }

        $pre = array_slice($routingTable, 0, $pos);
        $next = array_slice($routingTable, $pos);

        $routingTable = array_merge($pre, [
            $name => [
                'url'    => $url,
                'widget' => $widget,
                'action' => $action
            ]
        ], $next);
        self::options()->routingTable = $routingTable;

        return BaseOptions::alloc()->update(
            ['value' => serialize($routingTable)],
            Db::get()->sql()->where('name = ?', 'routingTable')
        );
    }

    /**
     * Nhận đối tượng Tùy chọn
     *
     * @return Options
     */
    public static function options(): Options
    {
        return Options::alloc();
    }

    /**
     * Xóa tuyến đường
     *
     * @param string $name Tên tuyến đường
     * @return integer
     */
    public static function removeRoute(string $name): int
    {
        $routingTable = self::options()->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        unset($routingTable[$name]);
        self::options()->routingTable = $routingTable;

        $db = Db::get();
        return BaseOptions::alloc()->update(
            ['value' => serialize($routingTable)],
            $db->sql()->where('name = ?', 'routingTable')
        );
    }

    /**
     * Thêm tiện ích mở rộng hành động
     *
     * @param string $actionName Tên hành động cần được mở rộng
     * @param string $widgetName Tên của widget cần được mở rộng
     * @return integer
     */
    public static function addAction(string $actionName, string $widgetName): int
    {
        $actionTable = unserialize(self::options()->actionTable);
        $actionTable = empty($actionTable) ? [] : $actionTable;
        $actionTable[$actionName] = $widgetName;

        return BaseOptions::alloc()->update(
            ['value' => (self::options()->actionTable = serialize($actionTable))],
            Db::get()->sql()->where('name = ?', 'actionTable')
        );
    }

    /**
     * Xóa tiện ích mở rộng hành động
     *
     * @param string $actionName
     * @return int
     */
    public static function removeAction(string $actionName): int
    {
        $actionTable = unserialize(self::options()->actionTable);
        $actionTable = empty($actionTable) ? [] : $actionTable;

        if (isset($actionTable[$actionName])) {
            unset($actionTable[$actionName]);
            reset($actionTable);
        }

        return BaseOptions::alloc()->update(
            ['value' => (self::options()->actionTable = serialize($actionTable))],
            Db::get()->sql()->where('name = ?', 'actionTable')
        );
    }

    /**
     * Thêm thực đơn
     *
     * @param string $menuName Tên thực đơn
     * @return integer
     */
    public static function addMenu(string $menuName): int
    {
        $panelTable = unserialize(self::options()->panelTable);
        $panelTable['parent'] = empty($panelTable['parent']) ? [] : $panelTable['parent'];
        $panelTable['parent'][] = $menuName;

        BaseOptions::alloc()->update(
            ['value' => (self::options()->panelTable = serialize($panelTable))],
            Db::get()->sql()->where('name = ?', 'panelTable')
        );

        end($panelTable['parent']);
        return key($panelTable['parent']) + 10;
    }

    /**
     * Xóa thực đơn
     *
     * @param string $menuName Tên thực đơn
     * @return integer
     */
    public static function removeMenu(string $menuName): int
    {
        $panelTable = unserialize(self::options()->panelTable);
        $panelTable['parent'] = empty($panelTable['parent']) ? [] : $panelTable['parent'];

        if (false !== ($index = array_search($menuName, $panelTable['parent']))) {
            unset($panelTable['parent'][$index]);
        }

        BaseOptions::alloc()->update(
            ['value' => (self::options()->panelTable = serialize($panelTable))],
            Db::get()->sql()->where('name = ?', 'panelTable')
        );

        return $index + 10;
    }

    /**
     * Thêm một bảng điều khiển
     *
     * @param integer $index Chỉ mục thực đơn
     * @param string $fileName Tên tập tin
     * @param string $title Tiêu đề bảng điều khiển
     * @param string $subTitle Phụ đề bảng điều khiển
     * @param string $level Quyền truy cập
     * @param boolean $hidden Có nên giấu không
     * @param string $addLink Liên kết dự án mới sẽ được hiển thị sau tiêu đề trang
     * @return integer
     */
    public static function addPanel(
        int $index,
        string $fileName,
        string $title,
        string $subTitle,
        string $level,
        bool $hidden = false,
        string $addLink = ''
    ): int {
        $panelTable = unserialize(self::options()->panelTable);
        $panelTable['child'] = empty($panelTable['child']) ? [] : $panelTable['child'];
        $panelTable['child'][$index] = empty($panelTable['child'][$index]) ? [] : $panelTable['child'][$index];
        $fileName = urlencode(trim($fileName, '/'));
        $panelTable['child'][$index][]
            = [$title, $subTitle, 'extending.php?panel=' . $fileName, $level, $hidden, $addLink];

        $panelTable['file'] = empty($panelTable['file']) ? [] : $panelTable['file'];
        $panelTable['file'][] = $fileName;
        $panelTable['file'] = array_unique($panelTable['file']);

        BaseOptions::alloc()->update(
            ['value' => (self::options()->panelTable = serialize($panelTable))],
            Db::get()->sql()->where('name = ?', 'panelTable')
        );

        end($panelTable['child'][$index]);
        return key($panelTable['child'][$index]);
    }

    /**
     * Xóa một bảng điều khiển
     *
     * @param integer $index Chỉ mục thực đơn
     * @param string $fileName Tên tập tin
     * @return integer
     */
    public static function removePanel(int $index, string $fileName): int
    {
        $panelTable = unserialize(self::options()->panelTable);
        $panelTable['child'] = empty($panelTable['child']) ? [] : $panelTable['child'];
        $panelTable['child'][$index] = empty($panelTable['child'][$index]) ? [] : $panelTable['child'][$index];
        $panelTable['file'] = empty($panelTable['file']) ? [] : $panelTable['file'];
        $fileName = urlencode(trim($fileName, '/'));

        if (false !== ($key = array_search($fileName, $panelTable['file']))) {
            unset($panelTable['file'][$key]);
        }

        $return = 0;
        foreach ($panelTable['child'][$index] as $key => $val) {
            if ($val[2] == 'extending.php?panel=' . $fileName) {
                unset($panelTable['child'][$index][$key]);
                $return = $key;
            }
        }

        BaseOptions::alloc()->update(
            ['value' => (self::options()->panelTable = serialize($panelTable))],
            Db::get()->sql()->where('name = ?', 'panelTable')
        );
        return $return;
    }

    /**
     * Nhận url bảng điều khiển
     *
     * @param string $fileName
     * @return string
     */
    public static function url(string $fileName): string
    {
        return Common::url('extending.php?panel=' . (trim($fileName, '/')), self::options()->adminUrl);
    }

    /**
     * Định cấu hình các biến plugin theo cách thủ công
     *
     * @param mixed $pluginName Tên plugin
     * @param array $settings cặp giá trị khóa biến
     * @param bool $isPersonal . (default: false) Cho dù đó là một biến riêng tư
     */
    public static function configPlugin($pluginName, array $settings, bool $isPersonal = false)
    {
        if (empty($settings)) {
            return;
        }

        Edit::configPlugin($pluginName, $settings, $isPersonal);
    }

    /**
     * Nút trả lời bình luận
     *
     * @access public
     * @param string $theId id thành phần bình luận
     * @param integer $coid id nhận xét
     * @param string $word văn bản nút
     * @param string $formId mã mẫu
     * @param integer $style kiểu phong cách
     * @return void
     */
    public static function replyLink(
        string $theId,
        int $coid,
        string $word = 'Reply',
        string $formId = 'respond',
        int $style = 2
    ) {
        if (self::options()->commentsThreaded) {
            echo '<a href="#' . $formId . '" rel="nofollow" onclick="return typechoAddCommentReply(\'' .
                $theId . '\', ' . $coid . ', \'' . $formId . '\', ' . $style . ');">' . $word . '</a>';
        }
    }

    /**
     * Nút hủy bình luận
     *
     * @param string $word văn bản nút
     * @param string $formId mã mẫu
     */
    public static function cancelCommentReplyLink(string $word = 'Cancel', string $formId = 'respond')
    {
        if (self::options()->commentsThreaded) {
            echo '<a href="#' . $formId . '" rel="nofollow" onclick="return typechoCancelCommentReply(\'' .
                $formId . '\');">' . $word . '</a>';
        }
    }

    /**
     * Tập lệnh js trả lời bình luận
     */
    public static function threadedCommentsScript()
    {
        if (self::options()->commentsThreaded) {
            echo
            <<<EOF
<script type="text/javascript">
var typechoAddCommentReply = function (cid, coid, cfid, style) {
    var _ce = document.getElementById(cid), _cp = _ce.parentNode;
    var _cf = document.getElementById(cfid);

    var _pi = document.getElementById('comment-parent');
    if (null == _pi) {
        _pi = document.createElement('input');
        _pi.setAttribute('type', 'hidden');
        _pi.setAttribute('name', 'parent');
        _pi.setAttribute('id', 'comment-parent');

        var _form = 'form' == _cf.tagName ? _cf : _cf.getElementsByTagName('form')[0];

        _form.appendChild(_pi);
    }
    _pi.setAttribute('value', coid);

    if (null == document.getElementById('comment-form-place-holder')) {
        var _cfh = document.createElement('div');
        _cfh.setAttribute('id', 'comment-form-place-holder');
        _cf.parentNode.insertBefore(_cfh, _cf);
    }

    1 == style ? (null == _ce.nextSibling ? _cp.appendChild(_cf)
    : _cp.insertBefore(_cf, _ce.nextSibling)) : _ce.appendChild(_cf);

    return false;
};

var typechoCancelCommentReply = function (cfid) {
    var _cf = document.getElementById(cfid),
    _cfh = document.getElementById('comment-form-place-holder');

    var _pi = document.getElementById('comment-parent');
    if (null != _pi) {
        _pi.parentNode.removeChild(_pi);
    }

    if (null == _cfh) {
        return true;
    }

    _cfh.parentNode.insertBefore(_cf, _cfh);
    return false;
};
</script>
EOF;
        }
    }
}
