<?php

namespace Utils;

use Typecho\Common;
use Typecho\Db;
use Typecho\Exception;
use Widget\Options;
use Widget\Themes\Edit;
use Widget\Upload;

/**
 * Thủ tục nâng cấp
 *
 * @category typecho
 * @package Upgrade
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Upgrade
{
    /**
     * Đã nâng cấp lên 9.1.7
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_3r9_1_7($db, $options)
    {
        /** Chuyển đổi nhận xét */
        $i = 1;

        while (true) {
            $result = $db->query($db->select('coid', 'text')->from('table.comments')
                ->order('coid', Db::SORT_ASC)->page($i, 100));
            $j = 0;

            while ($row = $db->fetchRow($result)) {
                $text = nl2br($row['text']);

                $db->query($db->update('table.comments')
                    ->rows(['text' => $text])
                    ->where('coid = ?', $row['coid']));

                $j++;
                unset($text);
                unset($row);
            }

            if ($j < 100) {
                break;
            }

            $i++;
            unset($result);
        }

        /** Chuyển đổi nội dung */
        $i = 1;

        while (true) {
            $result = $db->query($db->select('cid', 'text')->from('table.contents')
                ->order('cid', Db::SORT_ASC)->page($i, 100));
            $j = 0;

            while ($row = $db->fetchRow($result)) {
                $text = preg_replace(
                    [
                        "/\s*<p>/is", "/\s*<\/p>\s*/is", "/\s*<br\s*\/>\s*/is",
                        "/\s*<(div|blockquote|pre|table|ol|ul)>/is", "/<\/(div|blockquote|pre|table|ol|ul)>\s*/is"
                    ],
                    ['', "\n\n", "\n", "\n\n<\\1>", "</\\1>\n\n"],
                    $row['text']
                );

                $db->query($db->update('table.contents')
                    ->rows(['text' => $text])
                    ->where('cid = ?', $row['cid']));

                $j++;
                unset($text);
                unset($row);
            }

            if ($j < 100) {
                break;
            }

            $i++;
            unset($result);
        }
    }

    /**
     * Đã nâng cấp lên 9.1.14
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_4r9_1_14($db, $options)
    {
        if (is_writeable(__TYPECHO_ROOT_DIR__ . '/config.inc.php')) {
            $handle = fopen(__TYPECHO_ROOT_DIR__ . '/config.inc.php', 'ab');
            fwrite($handle, '
/** Khởi tạo múi giờ */
Typecho_Date::setTimezoneOffset($options->timezone);
');
            fclose($handle);
        } else {
            throw new Exception(_t('config.inc.php 文件无法写入, 请将它的权限设置为可写'));
        }
    }

    /**
     * Đã nâng cấp lên 9.2.3
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_5r9_2_3($db, $options)
    {
        /** Chuyển đổi nhận xét */
        $i = 1;

        while (true) {
            $result = $db->query($db->select('coid', 'text')->from('table.comments')
                ->order('coid', Db::SORT_ASC)->page($i, 100));
            $j = 0;

            while ($row = $db->fetchRow($result)) {
                $text = preg_replace("/\s*<br\s*\/>\s*/i", "\n", $row['text']);

                $db->query($db->update('table.comments')
                    ->rows(['text' => $text])
                    ->where('coid = ?', $row['coid']));

                $j++;
                unset($text);
                unset($row);
            }

            if ($j < 100) {
                break;
            }

            $i++;
            unset($result);
        }
    }

    /**
     * Đã nâng cấp lên 9.2.18
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_5r9_2_18($db, $options)
    {
        /** Nâng cấp giao diện soạn thảo */
        $db->query($db->update('table.options')
            ->rows(['value' => 350])
            ->where('name = ?', 'editorSize'));
    }

    /**
     * Đã nâng cấp lên 9.2.25
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_5r9_2_25($db, $options)
    {
        /** Nâng cấp giao diện soạn thảo */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'useRichEditor', 'user' => 0, 'value' => 1]));
    }

    /**
     * Đã nâng cấp lên 9.4.3
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_6r9_4_3($db, $options)
    {
        /** Sửa đổi các trường cơ sở dữ liệu */
        $adapterName = $db->getAdapterName();
        $prefix = $db->getPrefix();

        // Xóa dữ liệu cũ
        try {
            switch (true) {
                case false !== strpos($adapterName, 'Mysql'):
                    $db->query('ALTER TABLE  `' . $prefix . 'users` DROP  `meta`', Db::WRITE);
                    break;

                case false !== strpos($adapterName, 'Pgsql'):
                    $db->query('ALTER TABLE  "' . $prefix . 'users" DROP COLUMN  "meta"', Db::WRITE);
                    break;

                case false !== strpos($adapterName, 'SQLite'):
                    $uuid = uniqid();
                    $db->query('CREATE TABLE ' . $prefix . 'users_' . $uuid . ' ( "uid" INTEGER NOT NULL PRIMARY KEY,
            "name" varchar(32) default NULL ,
            "password" varchar(64) default NULL ,
            "mail" varchar(150) default NULL ,
            "url" varchar(150) default NULL ,
            "screenName" varchar(32) default NULL ,
            "created" int(10) default \'0\' ,
            "activated" int(10) default \'0\' ,
            "logged" int(10) default \'0\' ,
            "group" varchar(16) default \'visitor\' ,
            "authCode" varchar(64) default NULL)', Db::WRITE);
                    $db->query('INSERT INTO ' . $prefix . 'users_' . $uuid . ' ("uid", "name", "password", "mail", "url"
                    , "screenName", "created", "activated", "logged", "group", "authCode") SELECT "uid", "name", "password", "mail", "url"
                    , "screenName", "created", "activated", "logged", "group", "authCode" FROM ' . $prefix . 'users', Db::WRITE);
                    $db->query('DROP TABLE  ' . $prefix . 'users', Db::WRITE);
                    $db->query('CREATE TABLE ' . $prefix . 'users ( "uid" INTEGER NOT NULL PRIMARY KEY,
            "name" varchar(32) default NULL ,
            "password" varchar(64) default NULL ,
            "mail" varchar(150) default NULL ,
            "url" varchar(150) default NULL ,
            "screenName" varchar(32) default NULL ,
            "created" int(10) default \'0\' ,
            "activated" int(10) default \'0\' ,
            "logged" int(10) default \'0\' ,
            "group" varchar(16) default \'visitor\' ,
            "authCode" varchar(64) default NULL)', Db::WRITE);
                    $db->query('INSERT INTO ' . $prefix . 'users SELECT * FROM ' . $prefix . 'users_' . $uuid, Db::WRITE);
                    $db->query('DROP TABLE  ' . $prefix . 'users_' . $uuid, Db::WRITE);
                    $db->query('CREATE UNIQUE INDEX ' . $prefix . 'users_name ON ' . $prefix . 'users ("name")', Db::WRITE);
                    $db->query('CREATE UNIQUE INDEX ' . $prefix . 'users_mail ON ' . $prefix . 'users ("mail")', Db::WRITE);
                    $db->flushPool();

                    break;

                default:
                    break;
            }
        } catch (Db_Exception $e) {
            //do nothing
        }

        // Tăng chiều dài trường sên lên 150
        try {
            switch (true) {
                case false !== strpos($adapterName, 'Mysql'):
                    $db->query("ALTER TABLE  `" . $prefix . "contents` MODIFY COLUMN `slug` varchar(150)", Db::WRITE);
                    $db->query("ALTER TABLE  `" . $prefix . "metas` MODIFY COLUMN `slug` varchar(150)", Db::WRITE);
                    break;

                case false !== strpos($adapterName, 'Pgsql'):
                    $db->query('ALTER TABLE  "' . $prefix . 'contents" ALTER COLUMN  "slug" TYPE varchar(150)', Db::WRITE);
                    $db->query('ALTER TABLE  "' . $prefix . 'metas" ALTER COLUMN  "slug" TYPE varchar(150)', Db::WRITE);
                    break;

                case false !== strpos($adapterName, 'SQLite'):
                    $uuid = uniqid();
                    $db->query('CREATE TABLE ' . $prefix . 'contents' . $uuid . ' ( "cid" INTEGER NOT NULL PRIMARY KEY,
        "title" varchar(150) default NULL ,
        "slug" varchar(150) default NULL ,
        "created" int(10) default \'0\' ,
        "modified" int(10) default \'0\' ,
        "text" text ,
        "order" int(10) default \'0\' ,
        "authorId" int(10) default \'0\' ,
        "template" varchar(32) default NULL ,
        "type" varchar(16) default \'post\' ,
        "status" varchar(16) default \'publish\' ,
        "password" varchar(32) default NULL ,
        "commentsNum" int(10) default \'0\' ,
        "allowComment" char(1) default \'0\' ,
        "allowPing" char(1) default \'0\' ,
        "allowFeed" char(1) default \'0\' )', Db::WRITE);
                    $db->query('INSERT INTO ' . $prefix . 'contents' . $uuid . ' SELECT * FROM ' . $prefix . 'contents', Db::WRITE);
                    $db->query('DROP TABLE  ' . $prefix . 'contents', Db::WRITE);
                    $db->query('CREATE TABLE ' . $prefix . 'contents ( "cid" INTEGER NOT NULL PRIMARY KEY,
        "title" varchar(150) default NULL ,
        "slug" varchar(150) default NULL ,
        "created" int(10) default \'0\' ,
        "modified" int(10) default \'0\' ,
        "text" text ,
        "order" int(10) default \'0\' ,
        "authorId" int(10) default \'0\' ,
        "template" varchar(32) default NULL ,
        "type" varchar(16) default \'post\' ,
        "status" varchar(16) default \'publish\' ,
        "password" varchar(32) default NULL ,
        "commentsNum" int(10) default \'0\' ,
        "allowComment" char(1) default \'0\' ,
        "allowPing" char(1) default \'0\' ,
        "allowFeed" char(1) default \'0\' )', Db::WRITE);
                    $db->query('INSERT INTO ' . $prefix . 'contents SELECT * FROM ' . $prefix . 'contents' . $uuid, Db::WRITE);
                    $db->query('DROP TABLE  ' . $prefix . 'contents' . $uuid, Db::WRITE);
                    $db->query('CREATE UNIQUE INDEX ' . $prefix . 'contents_slug ON ' . $prefix . 'contents ("slug")', Db::WRITE);
                    $db->query('CREATE INDEX ' . $prefix . 'contents_created ON ' . $prefix . 'contents ("created")', Db::WRITE);

                    $db->query('CREATE TABLE ' . $prefix . 'metas' . $uuid . ' ( "mid" INTEGER NOT NULL PRIMARY KEY,
        "name" varchar(150) default NULL ,
        "slug" varchar(150) default NULL ,
        "type" varchar(32) NOT NULL ,
        "description" varchar(150) default NULL ,
        "count" int(10) default \'0\' ,
        "order" int(10) default \'0\' )', Db::WRITE);
                    $db->query('INSERT INTO ' . $prefix . 'metas' . $uuid . ' SELECT * FROM ' . $prefix . 'metas', Db::WRITE);
                    $db->query('DROP TABLE  ' . $prefix . 'metas', Db::WRITE);
                    $db->query('CREATE TABLE ' . $prefix . 'metas ( "mid" INTEGER NOT NULL PRIMARY KEY,
        "name" varchar(150) default NULL ,
        "slug" varchar(150) default NULL ,
        "type" varchar(32) NOT NULL ,
        "description" varchar(150) default NULL ,
        "count" int(10) default \'0\' ,
        "order" int(10) default \'0\' )', Db::WRITE);
                    $db->query('INSERT INTO ' . $prefix . 'metas SELECT * FROM ' . $prefix . 'metas' . $uuid, Db::WRITE);
                    $db->query('DROP TABLE  ' . $prefix . 'metas' . $uuid, Db::WRITE);
                    $db->query('CREATE INDEX ' . $prefix . 'metas_slug ON ' . $prefix . 'metas ("slug")', Db::WRITE);
                    $db->flushPool();

                    break;

                default:
                    break;
            }
        } catch (Db_Exception $e) {
            //do nothing
        }
    }

    /**
     * Đã nâng cấp lên 9.4.21
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_6r9_4_21($db, $options)
    {
        // thành phần thông tin toàn cầu
        $uploadDir = Common::url(Upload::UPLOAD_DIR, __TYPECHO_ROOT_DIR__);
        if (is_dir($uploadDir)) {
            if (!is_writeable($uploadDir)) {
                if (!@chmod($uploadDir, 0755)) {
                    throw new \Typecho\Widget\Exception(_t('Không thể ghi thư mục tải lên. Vui lòng đặt thủ công các quyền của thư mục %s trong thư mục cài đặt để có thể ghi và tiếp tục nâng cấp.', Upload::UPLOAD_DIR));
                }
            }
        } else {
            if (!@mkdir($uploadDir, 0755)) {
                throw new \Typecho\Widget\Exception(_t('Không thể tạo thư mục tải lên. Vui lòng tạo thủ công thư mục %s trong thư mục cài đặt, đặt quyền của nó thành có thể ghi và tiếp tục nâng cấp.', Upload::UPLOAD_DIR));
            }
        }

        /** Thêm trang chủ tùy chỉnh */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'customHomePage', 'user' => 0, 'value' => 0]));

        /** Thêm hàm băm tải lên tập tin */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'uploadHandle', 'user' => 0, 'value' => 'a:2:{i:0;s:13:"Widget_Upload";i:1;s:12:"uploadHandle";}']));

        /** Thêm chức năng xóa file */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'deleteHandle', 'user' => 0, 'value' => 'a:2:{i:0;s:13:"Widget_Upload";i:1;s:12:"deleteHandle";}']));

        /** Thêm hàm băm hiển thị tập tin */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'attachmentHandle', 'user' => 0, 'value' => 'a:2:{i:0;s:13:"Widget_Upload";i:1;s:16:"attachmentHandle";}']));

        /** Thêm phần mở rộng tập tin */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'attachmentTypes', 'user' => 0, 'value' => '*.jpg;*.gif;*.png;*.zip;*.tar.gz']));

        /** Thêm tuyến đường */
        $routingTable = $options->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        $pre = array_slice($routingTable, 0, 2);
        $next = array_slice($routingTable, 2);

        $routingTable = array_merge($pre, [
            'attachment' =>
                [
                    'url'    => '/attachment/[cid:digital]/',
                    'widget' => 'Widget_Archive',
                    'action' => 'render',
                ]
        ], $next);

        $db->query($db->update('table.options')
            ->rows(['value' => serialize($routingTable)])
            ->where('name = ?', 'routingTable'));
    }

    /**
     * Đã nâng cấp lên 9.6.1
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_6r9_6_1($db, $options)
    {
        /** Xóa trình soạn thảo WYSIWYG */
        $db->query($db->delete('table.options')
            ->where('name = ?', 'useRichEditor'));

        /** Sửa các giá trị tự động lưu */
        $db->query($db->update('table.options')
            ->rows(['value' => 0])
            ->where('name = ?', 'autoSave'));

        /** Tăng giới hạn số tầng xếp chồng lên nhau */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsMaxNestingLevels', 'user' => 0, 'value' => 5]));
    }

    /**
     * Đã nâng cấp lên 9.6.16
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_6_16($db, $options)
    {
        /** Thêm tay cầm đính kèm */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'modifyHandle', 'value' => 'a:2:{i:0;s:13:"Widget_Upload";i:1;s:12:"modifyHandle";}']));

        /** Thêm tay cầm đính kèm */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'attachmentDataHandle', 'value' => 'a:2:{i:0;s:13:"Widget_Upload";i:1;s:20:"attachmentDataHandle";}']));

        /** Chuyển đổi tệp đính kèm */
        $i = 1;

        while (true) {
            $result = $db->query($db->select('cid', 'text')->from('table.contents')
                ->where('type = ?', 'attachment')
                ->order('cid', Db::SORT_ASC)->page($i, 100));
            $j = 0;

            while ($row = $db->fetchRow($result)) {
                $attachment = unserialize($row['text']);
                $attachment['modifyHandle'] = ['Widget_Upload', 'modifyHandle'];
                $attachment['attachmentDataHandle'] = ['Widget_Upload', 'attachmentDataHandle'];

                $db->query($db->update('table.contents')
                    ->rows(['text' => serialize($attachment)])
                    ->where('cid = ?', $row['cid']));

                $j++;
                unset($text);
                unset($row);
            }

            if ($j < 100) {
                break;
            }

            $i++;
            unset($result);
        }
    }

    /**
     * Đã nâng cấp lên 9.6.16.1
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_6_16_1($db, $options)
    {
        // Sửa đổi định tuyến hành động
        $routingTable = $options->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        $routingTable['do'] = [
            'url'    => '/action/[action:alpha]',
            'widget' => 'Widget_Do',
            'action' => 'action'
        ];

        $db->query($db->update('table.options')
            ->rows(['value' => serialize($routingTable)])
            ->where('name = ?', 'routingTable'));

        // Loại bỏ dữ liệu rác
        $db->query($db->update('table.options')
            ->rows(['value' => 'a:0:{}'])
            ->where('name = ?', 'actionTable'));
    }

    /**
     * Đã nâng cấp lên 9.7.2
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_7_2($db, $options)
    {
        /** Thêm định dạng nội dung mặc định */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'contentType', 'user' => 0, 'value' => 'text/html']));

        /** Thêm công tắc gzip */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'gzip', 'user' => 0, 'value' => 0]));


        if (is_writeable(__TYPECHO_ROOT_DIR__ . '/config.inc.php')) {
            $contents = file_get_contents(__TYPECHO_ROOT_DIR__ . '/config.inc.php');
            $contents = preg_replace("/Common::init([^;]+);/is", "Common::init(array(
    'autoLoad'          =>  true,
    'exception'         =>  'Widget_ExceptionHandle',
    'gpc'               =>  true
));", $contents);
            $contents = preg_replace("/\s*(\/[^\/]+\/)?\s*Typecho_Widget::widget([^;]+);/is", '', $contents);
            $contents = preg_replace("/\s*(\/[^\/]+\/)?\s*Typecho_Router::setRoutes([^;]+);/is", '', $contents);
            $contents = preg_replace("/\s*(\/[^\/]+\/)?\s*Typecho_Plugin::init([^;]+);/is", '', $contents);
            $contents = preg_replace("/\s*(\/[^\/]+\/)?\s*Typecho_Date::setTimezoneOffset([^;]+);/is", '', $contents);
            file_put_contents(__TYPECHO_ROOT_DIR__ . '/config.inc.php', $contents);

        } else {
            /** Mẹo nâng cấp */
            return _t('Bạn nên thực hiện ngay <a href="https://typecho.org/upgrade/9.7.2"> các bước tối ưu hóa sau</a> sau khi nâng cấp lên Typecho 0.7/9.7.2 trở lên.');
        }
    }

    /**
     * Đã nâng cấp lên 9.9.2
     * Sửa đổi loại trường văn bản của bảng nội dung thành văn bản dài (chỉ mysql, pssql và sqlite không có giới hạn độ dài)
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_9_2($db, $options)
    {
        $adapterName = $db->getAdapterName();
        $prefix = $db->getPrefix();

        if (false !== strpos($adapterName, 'Mysql')) {
            $db->query("ALTER TABLE  `{$prefix}contents` CHANGE  `text`  `text` LONGTEXT NULL DEFAULT NULL COMMENT  'Văn bản nội dung'", Db::WRITE);
        }
    }

    /**
     * Đã nâng cấp lên 9.9.15
     * Tối ưu hóa cấu trúc bảng định tuyến
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_9_15($db, $options)
    {
        /** Thêm tuyến đường */
        $routingTable = $options->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        $do = $routingTable['do'];
        unset($routingTable['do']);

        $pre = array_slice($routingTable, 0, 1);
        $next = array_slice($routingTable, 1);

        $routingTable = array_merge($pre, ['do' => $do], $next);

        $db->query($db->update('table.options')
            ->rows(['value' => serialize($routingTable)])
            ->where('name = ?', 'routingTable'));
    }

    /**
     * Đã nâng cấp lên 9.9.22
     * Bản nâng cấp này dùng để sửa các bảng định tuyến bị hỏng khi nâng cấp từ 0.6
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_9_22($db, $options)
    {
        /** Sửa đổi tuyến đường */
        $routingTable = $options->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        $routingTable['do'] = [
            'url'    => '/action/[action:alpha]',
            'widget' => 'Widget_Do',
            'action' => 'action'
        ];

        $do = $routingTable['do'];
        unset($routingTable['do']);

        $pre = array_slice($routingTable, 0, 1);
        $next = array_slice($routingTable, 1);

        $routingTable = array_merge($pre, ['do' => $do], $next);

        $db->query($db->update('table.options')
            ->rows(['value' => serialize($routingTable)])
            ->where('name = ?', 'routingTable'));
    }

    /**
     * Đã nâng cấp lên 9.9.27
     * Thêm lưu trữ theo tác giả
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_9_27($db, $options)
    {
        /** Sửa đổi tuyến đường */
        $routingTable = $options->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        $pre = array_slice($routingTable, 0, 6);
        $next = array_slice($routingTable, 6);
        $next_pre = array_slice($next, 0, 5);
        $next_next = array_slice($next, 5);

        $author = [
            'url'    => '/author/[uid:digital]/',
            'widget' => 'Widget_Archive',
            'action' => 'render',
        ];

        $author_page = [
            'url'    => '/author/[uid:digital]/[page:digital]/',
            'widget' => 'Widget_Archive',
            'action' => 'render',
        ];

        $routingTable = array_merge($pre, ['author' => $author], $next_pre,
            ['author_page' => $author_page], $next_next);

        $db->query($db->update('table.options')
            ->rows(['value' => serialize($routingTable)])
            ->where('name = ?', 'routingTable'));
    }

    /**
     * Đã nâng cấp lên 9.10.16
     * Thêm phân trang bình luận
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_10_16($db, $options)
    {
        /** Sửa đổi tuyến đường */
        $routingTable = $options->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        $pre = array_slice($routingTable, 0, 20);
        $next = array_slice($routingTable, 20);

        $commentPage = [
            'url'    => '[permalink:string]/[commentType:alpha]-page-[commentPage:digital]',
            'widget' => 'Widget_Archive',
            'action' => 'render',
        ];

        $routingTable = array_merge($pre, ['comment_page' => $commentPage], $next);

        $db->query($db->update('table.options')
            ->rows(['value' => serialize($routingTable)])
            ->where('name = ?', 'routingTable'));
    }

    /**
     * Đã nâng cấp lên 9.10.16
     * Đã nâng cấp lên 9.10.16
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_10_20($db, $options)
    {
        /** Sửa đổi các trường cơ sở dữ liệu */
        $adapterName = $db->getAdapterName();
        $prefix = $db->getPrefix();

        switch (true) {
            case false !== strpos($adapterName, 'Mysql'):
                $db->query('ALTER TABLE  `' . $prefix . 'contents` ADD  `parent` INT(10) UNSIGNED NULL DEFAULT \'0\'', Db::WRITE);
                break;

            case false !== strpos($adapterName, 'Pgsql'):
                $db->query('ALTER TABLE  "' . $prefix . 'contents" ADD COLUMN  "parent" INT NULL DEFAULT \'0\'', Db::WRITE);
                break;

            case false !== strpos($adapterName, 'SQLite'):
                $uuid = uniqid();
                $db->query('CREATE TABLE ' . $prefix . 'contents_tmp ( "cid" INTEGER NOT NULL PRIMARY KEY,
"title" varchar(150) default NULL ,
"slug" varchar(150) default NULL ,
"created" int(10) default \'0\' ,
"modified" int(10) default \'0\' ,
"text" text ,
"order" int(10) default \'0\' ,
"authorId" int(10) default \'0\' ,
"template" varchar(32) default NULL ,
"type" varchar(16) default \'post\' ,
"status" varchar(16) default \'publish\' ,
"password" varchar(32) default NULL ,
"commentsNum" int(10) default \'0\' ,
"allowComment" char(1) default \'0\' ,
"allowPing" char(1) default \'0\' ,
"allowFeed" char(1) default \'0\' ,
"parent" int(10) default \'0\' )', Db::WRITE);
                $db->query('INSERT INTO ' . $prefix . 'contents_tmp ("cid", "title", "slug", "created", "modified"
                , "text", "order", "authorId", "template", "type", "status", "password", "commentsNum", "allowComment",
                "allowPing", "allowFeed", "parent") SELECT "cid", "title", "slug", "created", "modified"
                , "text", "order", "authorId", "template", "type", "status", "password", "commentsNum", "allowComment",
                "allowPing", "allowFeed", "parent" FROM ' . $prefix . 'contents', Db::WRITE);
                $db->query('DROP TABLE  ' . $prefix . 'contents', Db::WRITE);
                $db->query('CREATE TABLE ' . $prefix . 'contents ( "cid" INTEGER NOT NULL PRIMARY KEY,
"title" varchar(150) default NULL ,
"slug" varchar(150) default NULL ,
"created" int(10) default \'0\' ,
"modified" int(10) default \'0\' ,
"text" text ,
"order" int(10) default \'0\' ,
"authorId" int(10) default \'0\' ,
"template" varchar(32) default NULL ,
"type" varchar(16) default \'post\' ,
"status" varchar(16) default \'publish\' ,
"password" varchar(32) default NULL ,
"commentsNum" int(10) default \'0\' ,
"allowComment" char(1) default \'0\' ,
"allowPing" char(1) default \'0\' ,
"allowFeed" char(1) default \'0\' ,
"parent" int(10) default \'0\' )', Db::WRITE);
                $db->query('INSERT INTO ' . $prefix . 'contents SELECT * FROM ' . $prefix . 'contents_tmp', Db::WRITE);
                $db->query('DROP TABLE  ' . $prefix . 'contents_tmp', Db::WRITE);
                $db->query('CREATE UNIQUE INDEX ' . $prefix . 'contents_slug ON ' . $prefix . 'contents ("slug")', Db::WRITE);
                $db->query('CREATE INDEX ' . $prefix . 'contents_created ON ' . $prefix . 'contents ("created")', Db::WRITE);
                $db->flushPool();

                break;

            default:
                break;
        }

        $db->query($db->update('table.contents')->expression('parent', 'order')
            ->where('type = ?', 'attachment'));

        $db->query($db->update('table.contents')->rows(['order' => 0])
            ->where('type = ?', 'attachment'));
    }

    /**
     * Đã nâng cấp lên 9.10.31
     * Sửa tệp đính kèm
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_7r9_10_31($db, $options)
    {
        $db->query($db->update('table.contents')->rows(['status' => 'publish'])
            ->where('type = ?', 'attachment'));
    }

    /**
     * Đã nâng cấp lên 9.11.25
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_8r9_11_25($db, $options)
    {
        /** Thêm một số tùy chọn */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsPageBreak', 'user' => 0, 'value' => 0]));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsThreaded', 'user' => 0, 'value' => 1]));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsPageSize', 'user' => 0, 'value' => 20]));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsPageDisplay', 'user' => 0, 'value' => 'last']));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsOrder', 'user' => 0, 'value' => 'ASC']));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsCheckReferer', 'user' => 0, 'value' => 1]));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsAutoClose', 'user' => 0, 'value' => 0]));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsPostIntervalEnable', 'user' => 0, 'value' => 1]));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsPostInterval', 'user' => 0, 'value' => 60]));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsShowCommentOnly', 'user' => 0, 'value' => 0]));

        /** Sửa đổi tuyến đường */
        $routingTable = $options->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        if (isset($routingTable['comment_page'])) {
            $routingTable['comment_page'] = [
                'url'    => '[permalink:string]/comment-page-[commentPage:digital]',
                'widget' => 'Widget_Archive',
                'action' => 'render',
            ];
        } else {
            $pre = array_slice($routingTable, 0, 20);
            $next = array_slice($routingTable, 20);

            $commentPage = [
                'url'    => '[permalink:string]/comment-page-[commentPage:digital]',
                'widget' => 'Widget_Archive',
                'action' => 'render',
            ];

            $routingTable = array_merge($pre, ['comment_page' => $commentPage], $next);
        }

        $db->query($db->update('table.options')
            ->rows(['value' => serialize($routingTable)])
            ->where('name = ?', 'routingTable'));
    }

    /**
     * Đã nâng cấp lên 9.12.11
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_8r9_12_11($db, $options)
    {
        /** Loại bỏ các tùy chọn vô dụng */
        $db->query($db->delete('table.options')
            ->where('name = ? OR name = ? OR name = ? OR name = ? OR name = ? OR name = ? OR name = ?', 'customHomePage', 'uploadHandle',
                'deleteHandle', 'modifyHandle', 'attachmentHandle', 'attachmentDataHandle', 'gzip'));

        // Thêm trang chủ tùy chỉnh
        $db->query($db->insert('table.options')
            ->rows(['name' => 'frontPage', 'user' => 0, 'value' => 'recent']));
    }

    /**
     * Đã nâng cấp lên 10.2.27
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_8r10_2_27($db, $options)
    {
        /** Thêm một số tùy chọn */
        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsAvatar', 'user' => 0, 'value' => 1]));

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsAvatarRating', 'user' => 0, 'value' => 'G']));

        // Cập nhật tiện ích mở rộng
        if (null != $options->attachmentTypes) {
            $attachmentTypes = array_map('trim', explode(';', $options->attachmentTypes));
            $attachmentTypesResult = [];

            foreach ($attachmentTypes as $type) {
                $type = trim($type, '*.');
                if (!empty($type)) {
                    $attachmentTypesResult[] = $type;
                }
            }

            if (!empty($attachmentTypesResult)) {
                $db->query($db->update('table.options')
                    ->rows(['value' => implode(',', $attachmentTypesResult)])
                    ->where('name = ?', 'attachmentTypes'));
            }
        }
    }

    /**
     * Nâng cấp lên 10.3.8
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_8r10_3_8($db, $options)
    {
        /** Loại bỏ các tùy chọn vô dụng */
        $db->query($db->delete('table.options')
            ->where('name = ?', 'commentsAvatarSize'));
    }

    /**
     * Đã nâng cấp lên 10.5.17
     *
     * @access public
     * @param Db $db đối tượng cơ sở dữ liệu
     * @param Options $options thành phần thông tin toàn cầu
     * @return void
     */
    public static function v0_8r10_5_17($db, $options)
    {
        Edit::alloc(null, 'change=' . $options->theme, function (Edit $edit) {
            $edit->action();
        });
    }


    /**
     * Đã nâng cấp lên 13.10.18
     *
     * @param mixed $db
     * @param mixed $options
     * @static
     * @access public
     * @return void
     */
    public static function v0_9r13_10_18($db, $options)
    {
        //  thêm markdown
        $db->query($db->insert('table.options')
            ->rows(['name' => 'markdown', 'user' => 0, 'value' => 0]));

        // Cập nhật bản nháp lộn xộn ban đầu
        $db->query($db->update('table.contents')
            ->rows([
                'type'   => 'post_draft',
                'status' => 'publish'
            ])
            ->where('type = ? AND status = ?', 'post', 'draft'));

        $db->query($db->update('table.contents')
            ->rows([
                'type'   => 'page_draft',
                'status' => 'publish'
            ])
            ->where('type = ? AND status = ?', 'page', 'draft'));
    }

    /**
     * v0_9r13_11_17
     *
     * @param mixed $db
     * @param mixed $options
     * @static
     * @access public
     * @return void
     */
    public static function v0_9r13_11_17($db, $options)
    {
        Helper::addRoute('archive', '/blog/', 'Widget_Archive', 'render', 'index');
        Helper::addRoute('archive_page', '/blog/[page:digital]/', 'Widget_Archive', 'render', 'index_page');
        $db->query($db->insert('table.options')
            ->rows(['name' => 'frontArchive', 'user' => 0, 'value' => 0]));
    }

    /**
     * v0_9r13_11_24
     *
     * @param mixed $db
     * @param mixed $options
     * @static
     * @access public
     * @return void
     */
    public static function v0_9r13_11_24($db, $options)
    {
        /* Thêm bảng dữ liệu */
        $adapterName = $db->getAdapterName();
        $prefix = $db->getPrefix();

        switch (true) {
            case false !== strpos($adapterName, 'Mysql'):
                $config = $db->getConfig();
                $db->query("CREATE TABLE `{$prefix}fields` (
  `cid` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(8) default 'str',
  `str_value` text,
  `int_value` int(10) default '0',
  `float_value` float default '0',
  PRIMARY KEY  (`cid`,`name`),
  KEY `int_value` (`int_value`),
  KEY `float_value` (`float_value`)
) ENGINE=MyISAM  DEFAULT CHARSET=" . $config[0]->charset, Db::WRITE);
                break;

            case false !== strpos($adapterName, 'Pgsql'):
                $db->query('CREATE TABLE "' . $prefix . 'fields" ("cid" INT NOT NULL,
  "name" VARCHAR(150) NOT NULL,
  "type" VARCHAR(8) NULL DEFAULT \'str\',
  "str_value" TEXT NULL DEFAULT NULL,
  "int_value" INT NULL DEFAULT \'0\',
  "float_value" REAL NULL DEFAULT \'0\',
  PRIMARY KEY  ("cid","name")
)', Db::WRITE);
                $db->query('CREATE INDEX "' . $prefix . 'fields_int_value" ON "' . $prefix . 'fields" ("int_value")', Db::WRITE);
                $db->query('CREATE INDEX "' . $prefix . 'fields_float_value" ON "' . $prefix . 'fields" ("float_value")', Db::WRITE);
                break;

            case false !== strpos($adapterName, 'SQLite'):
                $db->query('CREATE TABLE "' . $prefix . 'fields" ("cid" INTEGER NOT NULL,
  "name" varchar(150) NOT NULL,
  "type" varchar(8) default \'str\',
  "str_value" text,
  "int_value" int(10) default \'0\',
  "float_value" real default \'0\'
)', Db::WRITE);
                $db->query('CREATE UNIQUE INDEX ' . $prefix . 'fields_cid_name ON ' . $prefix . 'fields ("cid", "name")', Db::WRITE);
                $db->query('CREATE INDEX ' . $prefix . 'fields_int_value ON ' . $prefix . 'fields ("int_value")', Db::WRITE);
                $db->query('CREATE INDEX ' . $prefix . 'fields_float_value ON ' . $prefix . 'fields ("float_value")', Db::WRITE);

                break;

            default:
                break;
        }

        $db->query($db->insert('table.options')
            ->rows(['name' => 'commentsMarkdown', 'user' => 0, 'value' => 0]));
    }

    /**
     * Đã nâng cấp lên 17.4.24
     *
     * @param mixed $db
     * @param mixed $options
     * @static
     * @access public
     * @return void
     */
    public static function v1_1r17_4_24($db, $options)
    {
        // thêm markdown
        if (!isset($options->xmlrpcMarkdown)) {
            $db->query($db->insert('table.options')
                ->rows(['name' => 'xmlrpcMarkdown', 'user' => 0, 'value' => 0]));
        }
    }

    /**
     * Đã nâng cấp lên 17.10.24
     *
     * @param mixed $db
     * @param mixed $options
     * @static
     * @access public
     * @return void
     */
    public static function v1_1r17_10_24($db, $options)
    {
        // Thêm đã cài đặt
        if (!isset($options->installed)) {
            $db->query($db->insert('table.options')
                ->rows(['name' => 'installed', 'user' => 0, 'value' => 1]));
        }
    }

    /**
     * Đã nâng cấp lên 17.10.27
     *
     * @param mixed $db
     * @param mixed $options
     * @static
     * @access public
     * @return void
     */
    public static function v1_1r17_10_27($db, $options)
    {
        // Thêm công tắc xmlRpc
        if (!isset($options->allowXmlRpc)) {
            $db->query($db->insert('table.options')
                ->rows(['name' => 'allowXmlRpc', 'user' => 0, 'value' => 2]));
        }
    }

    /**
     * Đã nâng cấp lên 17.12.8
     *
     * @param $db
     */
    public static function v1_1r17_12_8($db)
    {
        /** Sửa đổi các trường cơ sở dữ liệu */
        $adapterName = $db->getAdapterName();
        $prefix = $db->getPrefix();

        switch (true) {
            case false !== strpos($adapterName, 'Mysql'):
                $db->query("ALTER TABLE  `" . $prefix . "comments` MODIFY COLUMN `agent` varchar(511)", Db::WRITE);
                break;

            case false !== strpos($adapterName, 'Pgsql'):
                $db->query('ALTER TABLE  "' . $prefix . 'comments" ALTER COLUMN  "agent" TYPE varchar(511)', Db::WRITE);
                break;

            case false !== strpos($adapterName, 'SQLite'):
                $uuid = uniqid();
                $db->query('CREATE TABLE ' . $prefix . 'comments' . $uuid . ' ( "coid" INTEGER NOT NULL PRIMARY KEY,
"cid" int(10) default \'0\' ,
"created" int(10) default \'0\' ,
"author" varchar(150) default NULL ,
"authorId" int(10) default \'0\' ,
"ownerId" int(10) default \'0\' ,
"mail" varchar(150) default NULL ,
"url" varchar(150) default NULL ,
"ip" varchar(64) default NULL ,
"agent" varchar(511) default NULL ,
"text" text ,
"type" varchar(16) default \'comment\' ,
"status" varchar(16) default \'approved\' ,
"parent" int(10) default \'0\')', Db::WRITE);
                $db->query('INSERT INTO ' . $prefix . 'comments' . $uuid . ' SELECT * FROM ' . $prefix . 'comments', Db::WRITE);
                $db->query('DROP TABLE  ' . $prefix . 'metas', Db::WRITE);
                $db->query('CREATE TABLE ' . $prefix . 'comments ( "coid" INTEGER NOT NULL PRIMARY KEY,
"cid" int(10) default \'0\' ,
"created" int(10) default \'0\' ,
"author" varchar(150) default NULL ,
"authorId" int(10) default \'0\' ,
"ownerId" int(10) default \'0\' ,
"mail" varchar(150) default NULL ,
"url" varchar(150) default NULL ,
"ip" varchar(64) default NULL ,
"agent" varchar(511) default NULL ,
"text" text ,
"type" varchar(16) default \'comment\' ,
"status" varchar(16) default \'approved\' ,
"parent" int(10) default \'0\')', Db::WRITE);
                $db->query('INSERT INTO ' . $prefix . 'comments SELECT * FROM ' . $prefix . 'comments' . $uuid, Db::WRITE);
                $db->query('DROP TABLE  ' . $prefix . 'comments' . $uuid, Db::WRITE);
                $db->query('CREATE INDEX ' . $prefix . 'comments_cid ON ' . $prefix . 'comments ("cid")', Db::WRITE);
                $db->query('CREATE INDEX ' . $prefix . 'comments_created ON ' . $prefix . 'comments ("created")', Db::WRITE);
                $db->flushPool();
                break;

            default:
                break;
        }
    }

    /**
     * Đã nâng cấp lên 18.1.29
     *
     * @param $db
     */
    public static function v1_2r18_1_29($db)
    {
        /** Sửa đổi các trường cơ sở dữ liệu */
        $adapterName = $db->getAdapterName();
        $prefix = $db->getPrefix();

        switch (true) {
            case false !== strpos($adapterName, 'Mysql'):
                $db->query("ALTER TABLE  `" . $prefix . "comments` MODIFY COLUMN `url` varchar(255)", Db::WRITE);
                break;

            case false !== strpos($adapterName, 'Pgsql'):
                $db->query('ALTER TABLE  "' . $prefix . 'comments" ALTER COLUMN  "url" TYPE varchar(255)', Db::WRITE);
                break;

            case false !== strpos($adapterName, 'SQLite'):
                $uuid = uniqid();
                $db->query('CREATE TABLE ' . $prefix . 'comments' . $uuid . ' ( "coid" INTEGER NOT NULL PRIMARY KEY,
"cid" int(10) default \'0\' ,
"created" int(10) default \'0\' ,
"author" varchar(150) default NULL ,
"authorId" int(10) default \'0\' ,
"ownerId" int(10) default \'0\' ,
"mail" varchar(150) default NULL ,
"url" varchar(255) default NULL ,
"ip" varchar(64) default NULL ,
"agent" varchar(511) default NULL ,
"text" text ,
"type" varchar(16) default \'comment\' ,
"status" varchar(16) default \'approved\' ,
"parent" int(10) default \'0\')', Db::WRITE);
                $db->query('INSERT INTO ' . $prefix . 'comments' . $uuid . ' SELECT * FROM ' . $prefix . 'comments', Db::WRITE);
                $db->query('DROP TABLE  ' . $prefix . 'metas', Db::WRITE);
                $db->query('CREATE TABLE ' . $prefix . 'comments ( "coid" INTEGER NOT NULL PRIMARY KEY,
"cid" int(10) default \'0\' ,
"created" int(10) default \'0\' ,
"author" varchar(150) default NULL ,
"authorId" int(10) default \'0\' ,
"ownerId" int(10) default \'0\' ,
"mail" varchar(150) default NULL ,
"url" varchar(255) default NULL ,
"ip" varchar(64) default NULL ,
"agent" varchar(511) default NULL ,
"text" text ,
"type" varchar(16) default \'comment\' ,
"status" varchar(16) default \'approved\' ,
"parent" int(10) default \'0\')', Db::WRITE);
                $db->query('INSERT INTO ' . $prefix . 'comments SELECT * FROM ' . $prefix . 'comments' . $uuid, Db::WRITE);
                $db->query('DROP TABLE  ' . $prefix . 'comments' . $uuid, Db::WRITE);
                $db->query('CREATE INDEX ' . $prefix . 'comments_cid ON ' . $prefix . 'comments ("cid")', Db::WRITE);
                $db->query('CREATE INDEX ' . $prefix . 'comments_created ON ' . $prefix . 'comments ("created")', Db::WRITE);
                $db->flushPool();
                break;

            default:
                break;
        }
    }

    /**
     * v0_9r13_11_24
     *
     * @param mixed $db
     * @param mixed $options
     * @access public
     * @return void
     */
    public function v0_9r13_12_6($db, $options)
    {
        if (!isset($options->frontArchive)) {
            $db->query($db->insert('table.options')
                ->rows(['name' => 'frontArchive', 'user' => 0, 'value' => 0]));
        }
    }

    /**
     * v0_9r13_12_20
     *
     * @param mixed $db
     * @param mixed $options
     * @access public
     * @return void
     */
    public function v0_9r13_12_20($db, $options)
    {
        if (!isset($options->commentsWhitelist)) {
            $db->query($db->insert('table.options')
                ->rows(['name' => 'commentsWhitelist', 'user' => 0, 'value' => 0]));
        }
    }

    /**
     * v0_9r14_2_24
     *
     * @param mixed $db
     * @param mixed $options
     * @access public
     * @return void
     */
    public function v0_9r14_2_24($db, $options)
    {
        /** Sửa đổi các trường cơ sở dữ liệu */
        $adapterName = $db->getAdapterName();
        $prefix = $db->getPrefix();

        switch (true) {
            case false !== strpos($adapterName, 'Mysql'):
                $db->query('ALTER TABLE  `' . $prefix . 'metas` ADD  `parent` INT(10) UNSIGNED NULL DEFAULT \'0\'', Db::WRITE);
                break;

            case false !== strpos($adapterName, 'Pgsql'):
                $db->query('ALTER TABLE  "' . $prefix . 'metas" ADD COLUMN  "parent" INT NULL DEFAULT \'0\'', Db::WRITE);
                break;

            case false !== strpos($adapterName, 'SQLite'):
                $uuid = uniqid();
                $db->query('CREATE TABLE ' . $prefix . 'metas' . $uuid . ' ( "mid" INTEGER NOT NULL PRIMARY KEY,
        "name" varchar(150) default NULL ,
        "slug" varchar(150) default NULL ,
        "type" varchar(32) NOT NULL ,
        "description" varchar(150) default NULL ,
        "count" int(10) default \'0\' ,
        "order" int(10) default \'0\' ,
        "parent" int(10) default \'0\')', Db::WRITE);
                $db->query('INSERT INTO ' . $prefix . 'metas' . $uuid . ' ("mid", "name", "slug", "type", "description", "count", "order") 
                    SELECT "mid", "name", "slug", "type", "description", "count", "order" FROM ' . $prefix . 'metas', Db::WRITE);
                $db->query('DROP TABLE  ' . $prefix . 'metas', Db::WRITE);
                $db->query('CREATE TABLE ' . $prefix . 'metas ( "mid" INTEGER NOT NULL PRIMARY KEY,
        "name" varchar(150) default NULL ,
        "slug" varchar(150) default NULL ,
        "type" varchar(32) NOT NULL ,
        "description" varchar(150) default NULL ,
        "count" int(10) default \'0\' ,
        "order" int(10) default \'0\' ,
        "parent" int(10) default \'0\')', Db::WRITE);
                $db->query('INSERT INTO ' . $prefix . 'metas SELECT * FROM ' . $prefix . 'metas' . $uuid, Db::WRITE);
                $db->query('DROP TABLE  ' . $prefix . 'metas' . $uuid, Db::WRITE);
                $db->query('CREATE INDEX ' . $prefix . 'metas_slug ON ' . $prefix . 'metas ("slug")', Db::WRITE);
                $db->flushPool();
                break;

            default:
                break;
        }
    }

    /**
     * v0_9r14_3_14
     *
     * @param mixed $db
     * @param mixed $options
     * @access public
     * @return void
     */
    public function v0_9r14_3_14($db, $options)
    {
        if (!isset($options->secret)) {
            $db->query($db->insert('table.options')
                ->rows(['name' => 'secret', 'user' => 0, 'value' => Common::randString(32, true)]));
        }
    }

    /**
     * v1_0r14_9_2
     *
     * @param mixed $db
     * @param mixed $options
     * @access public
     * @return void
     */
    public function v1_0r14_9_2($db, $options)
    {
        if (!isset($options->lang)) {
            $db->query($db->insert('table.options')
                ->rows(['name' => 'lang', 'user' => 0, 'value' => 'vi_VN']));
        }
    }

    /**
     * v1_0r14_10_10
     *
     * @param mixed $db
     * @param mixed $options
     * @access public
     * @return void
     */
    public function v1_0r14_10_10($db, $options)
    {
        if (!isset($options->commentsAntiSpam)) {
            $db->query($db->insert('table.options')
                ->rows(['name' => 'commentsAntiSpam', 'user' => 0, 'value' => 1]));
        }
    }
}

