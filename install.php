<?php
// File install.php đã được Việt Hoá và sửa lại đôi chút bởi: Luyện Phạm
// Hỗ trợ: https://facebook.com/cu.ti.9212
// Zalo: 0332.585.704 - Luyện
// Home Page: https://wapvn.top


if (!file_exists(dirname(__FILE__) . '/config.inc.php')) {
    // site root path
    define('__TYPECHO_ROOT_DIR__', dirname(__FILE__));

    // plugin directory (relative path)
    define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');

    // theme directory (relative path)
    define('__TYPECHO_THEME_DIR__', '/usr/themes');

    // admin directory (relative path)
    define('__TYPECHO_ADMIN_DIR__', '/admin/');

    // register autoload
    require_once __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';

    // init
    \Typecho\Common::init();
} else {
    require_once dirname(__FILE__) . '/config.inc.php';
    $installDb = \Typecho\Db::get();
}

/**
 * get lang
 *
 * @return string
 */
function install_get_lang(): string
{
    $serverLang = \Typecho\Request::getInstance()->getServer('TYPECHO_LANG');

    if (!empty($serverLang)) {
        return $serverLang;
    } else {
        $lang = 'vi_VN';
        $request = \Typecho\Request::getInstance();

        if ($request->is('lang')) {
            $lang = $request->get('lang');
            \Typecho\Cookie::set('lang', $lang);
        }

        return \Typecho\Cookie::get('lang', $lang);
    }
}

/**
 * get site url
 *
 * @return string
 */
function install_get_site_url(): string
{
    $request = \Typecho\Request::getInstance();
    return install_is_cli() ? $request->getServer('TYPECHO_SITE_URL', 'http://localhost') : $request->getRequestRoot();
}

/**
 * detect cli mode
 *
 * @return bool
 */
function install_is_cli(): bool
{
    return \Typecho\Request::getInstance()->isCli();
}

/**
 * get default router
 *
 * @return string[][]
 */
function install_get_default_routers(): array
{
    return [
        'index'              =>
            [
                'url'    => '/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'archive'            =>
            [
                'url'    => '/blog/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'do'                 =>
            [
                'url'    => '/action/[action:alpha]',
                'widget' => '\Widget\Action',
                'action' => 'action',
            ],
        'post'               =>
            [
                'url'    => '/archives/[cid:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'attachment'         =>
            [
                'url'    => '/attachment/[cid:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'category'           =>
            [
                'url'    => '/category/[slug]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'tag'                =>
            [
                'url'    => '/tag/[slug]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'author'             =>
            [
                'url'    => '/author/[uid:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'search'             =>
            [
                'url'    => '/search/[keywords]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'index_page'         =>
            [
                'url'    => '/page/[page:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'archive_page'       =>
            [
                'url'    => '/blog/page/[page:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'category_page'      =>
            [
                'url'    => '/category/[slug]/[page:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'tag_page'           =>
            [
                'url'    => '/tag/[slug]/[page:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'author_page'        =>
            [
                'url'    => '/author/[uid:digital]/[page:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'search_page'        =>
            [
                'url'    => '/search/[keywords]/[page:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'archive_year'       =>
            [
                'url'    => '/[year:digital:4]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'archive_month'      =>
            [
                'url'    => '/[year:digital:4]/[month:digital:2]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'archive_day'        =>
            [
                'url'    => '/[year:digital:4]/[month:digital:2]/[day:digital:2]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'archive_year_page'  =>
            [
                'url'    => '/[year:digital:4]/page/[page:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'archive_month_page' =>
            [
                'url'    => '/[year:digital:4]/[month:digital:2]/page/[page:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'archive_day_page'   =>
            [
                'url'    => '/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'comment_page'       =>
            [
                'url'    => '[permalink:string]/comment-page-[commentPage:digital]',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
        'feed'               =>
            [
                'url'    => '/feed[feed:string:0]',
                'widget' => '\Widget\Archive',
                'action' => 'feed',
            ],
        'feedback'           =>
            [
                'url'    => '[permalink:string]/[type:alpha]',
                'widget' => '\Widget\Feedback',
                'action' => 'action',
            ],
        'page'               =>
            [
                'url'    => '/[slug].html',
                'widget' => '\Widget\Archive',
                'action' => 'render',
            ],
    ];
}

/**
 * list all default options
 *
 * @return array
 */
function install_get_default_options(): array
{
    static $options;

    if (empty($options)) {
        $options = [
            'theme' => 'default',
            'theme:default' => 'a:2:{s:7:"logoUrl";N;s:12:"sidebarBlock";a:5:{i:0;s:15:"ShowRecentPosts";i:1;s:18:"ShowRecentComments";i:2;s:12:"ShowCategory";i:3;s:11:"ShowArchive";i:4;s:9:"ShowOther";}}',
            'timezone' => '25200',
            'lang' => install_get_lang(),
            'charset' => 'UTF-8',
            'contentType' => 'text/html',
            'gzip' => 0,
            'generator' => 'Typecho ' . \Typecho\Common::VERSION,
            'title' => 'Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10',
            'description' => 'Đây là mô tả mặc định của mã nguồn Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10',
            'keywords' => 'typecho viet hoa,php,blog,wapvn.top, blog wapvn.top',
            'rewrite' => 0,
            'frontPage' => 'recent',
            'frontArchive' => 0,
            'commentsRequireMail' => 1,
            'commentsWhitelist' => 0,
            'commentsRequireURL' => 0,
            'commentsRequireModeration' => 0,
            'plugins' => 'a:0:{}',
            'commentDateFormat' => 'a s:i:H / d-m-Y,',
            'siteUrl' => install_get_site_url(),
            'defaultCategory' => 1,
            'allowRegister' => 0,
            'defaultAllowComment' => 1,
            'defaultAllowPing' => 1,
            'defaultAllowFeed' => 1,
            'pageSize' => 5,
            'postsListSize' => 10,
            'commentsListSize' => 10,
            'commentsHTMLTagAllowed' => null,
            'postDateFormat' => 'd-m-Y',
            'feedFullText' => 1,
            'editorSize' => 350,
            'autoSave' => 0,
            'markdown' => 1,
            'xmlrpcMarkdown' => 0,
            'commentsMaxNestingLevels' => 5,
            'commentsPostTimeout' => 24 * 3600 * 30,
            'commentsUrlNofollow' => 1,
            'commentsShowUrl' => 1,
            'commentsMarkdown' => 0,
            'commentsPageBreak' => 0,
            'commentsThreaded' => 1,
            'commentsPageSize' => 20,
            'commentsPageDisplay' => 'last',
            'commentsOrder' => 'ASC',
            'commentsCheckReferer' => 1,
            'commentsAutoClose' => 0,
            'commentsPostIntervalEnable' => 1,
            'commentsPostInterval' => 60,
            'commentsShowCommentOnly' => 0,
            'commentsAvatar' => 1,
            'commentsAvatarRating' => 'G',
            'commentsAntiSpam' => 1,
            'routingTable' => serialize(install_get_default_routers()),
            'actionTable' => 'a:0:{}',
            'panelTable' => 'a:0:{}',
            'attachmentTypes' => '@image@',
            'secret' => \Typecho\Common::randString(32, true),
            'installed' => 0,
            'allowXmlRpc' => 2
        ];
    }

    return $options;
}

/**
 * get database driver type
 *
 * @param string $driver
 * @return string
 */
function install_get_db_type(string $driver): string
{
    $parts = explode('_', $driver);
    return $driver == 'Mysqli' ? 'Mysql' : array_pop($parts);
}

/**
 * list all available database drivers
 *
 * @return array
 */
function install_get_db_drivers(): array
{
    $drivers = [];

    if (\Typecho\Db\Adapter\Pdo\Mysql::isAvailable()) {
        $drivers['Pdo_Mysql'] = _t('PDO MySQL');
    }

    if (\Typecho\Db\Adapter\Pdo\SQLite::isAvailable()) {
        $drivers['Pdo_SQLite'] = _t(' SQLite PDO');
    }

    if (\Typecho\Db\Adapter\Pdo\Pgsql::isAvailable()) {
        $drivers['Pdo_Pgsql'] = _t('PDO PostgreSQL');
    }

    if (\Typecho\Db\Adapter\Mysqli::isAvailable()) {
        $drivers['Mysqli'] = _t('MySQL Gốc');
    }

    if (\Typecho\Db\Adapter\SQLite::isAvailable()) {
        $drivers['SQLite'] = _t('SQLite Gốc');
    }

    if (\Typecho\Db\Adapter\Pgsql::isAvailable()) {
        $drivers['Pgsql'] = _t('PsSQL Gốc');
    }

    return $drivers;
}

/**
 * get current db driver
 *
 * @return string
 */
function install_get_current_db_driver(): string
{
    global $installDb;

    if (empty($installDb)) {
        $driver = \Typecho\Request::getInstance()->get('driver');
        $drivers = install_get_db_drivers();

        if (empty($driver) || !isset($drivers[$driver])) {
            return key($drivers);
        }

        return $driver;
    } else {
        return $installDb->getAdapterName();
    }
}

/**
 * generate config file
 *
 * @param string $adapter
 * @param string $dbPrefix
 * @param array $dbConfig
 * @param bool $return
 * @return string
 */
function install_config_file(string $adapter, string $dbPrefix, array $dbConfig, bool $return = false): string
{
    global $configWritten;

    $code = "<" . "?php
// site root path
define('__TYPECHO_ROOT_DIR__', dirname(__FILE__));

// plugin directory (relative path)
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');

// theme directory (relative path)
define('__TYPECHO_THEME_DIR__', '/usr/themes');

// admin directory (relative path)
define('__TYPECHO_ADMIN_DIR__', '/admin/');

// register autoload
require_once __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';

// init
\Typecho\Common::init();

// config db
\$db = new \Typecho\Db('{$adapter}', '{$dbPrefix}');
\$db->addServer(" . (var_export($dbConfig, true)) . ", \Typecho\Db::READ | \Typecho\Db::WRITE);
\Typecho\Db::set(\$db);
";

    $configWritten = false;

    if (!$return) {
        $configWritten = @file_put_contents(__TYPECHO_ROOT_DIR__ . '/config.inc.php', $code) !== false;
    }

    return $code;
}

/**
 * remove config file if written
 */
function install_remove_config_file()
{
    global $configWritten;

    if ($configWritten) {
        unlink(__TYPECHO_ROOT_DIR__ . '/config.inc.php');
    }
}

/**
 * check install
 *
 * @param string $type
 * @return bool
 */
function install_check(string $type): bool
{
    switch ($type) {
        case 'config':
            return file_exists(__TYPECHO_ROOT_DIR__ . '/config.inc.php');
        case 'db_structure':
        case 'db_data':
            global $installDb;

            if (empty($installDb)) {
                return false;
            }

            try {
                // check if table exists
                $installed = $installDb->fetchRow($installDb->select()->from('table.options')
                    ->where('user = 0 AND name = ?', 'installed'));

                if ($type == 'db_data' && empty($installed['value'])) {
                    return false;
                }
            } catch (\Typecho\Db\Adapter\ConnectionException $e) {
                return true;
            } catch (\Typecho\Db\Adapter\SQLException $e) {
                return false;
            }

            return true;
        default:
            return false;
    }
}

/**
 * raise install error
 *
 * @param mixed $error
 * @param mixed $config
 */
function install_raise_error($error, $config = null)
{
    if (install_is_cli()) {
        if (is_array($error)) {
            foreach ($error as $key => $value) {
                echo (is_int($key) ? '' : $key . ': ') . $value . "\n";
            }
        } else {
            echo $error . "\n";
        }

        exit(1);
    } else {
        install_throw_json([
            'success' => 0,
            'message' => is_string($error) ? nl2br($error) : $error,
            'config' => $config
        ]);
    }
}

/**
 * @param $step
 * @param array|null $config
 */
function install_success($step, ?array $config = null)
{
    global $installDb;

    if (install_is_cli()) {
        if ($step == 3) {
            \Typecho\Db::set($installDb);
        }

        if ($step > 0) {
            $method = 'install_step_' . $step . '_perform';
            $method();
        }

        if (!empty($config)) {
            [$userName, $userPassword] = $config;
            echo _t('Đã cài đặt thành công mã nguồn Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10!') . "\n";
            echo _t('Tài khoản: ') . " {$userName}\n";
            echo _t('Mật khẩu: ') . " {$userPassword}\n";
        }

        exit(0);
    } else {
        install_throw_json([
            'success' => 1,
            'message' => $step,
            'config'  => $config
        ]);
    }
}

/**
 * @param $data
 */
function install_throw_json($data)
{
    \Typecho\Response::getInstance()->setContentType('application/json')
        ->addResponder(function () use ($data) {
            echo json_encode($data);
        })
        ->respond();
}

/**
 * @param string $url
 */
function install_redirect(string $url)
{
    \Typecho\Response::getInstance()->setStatus(302)
        ->setHeader('Location', $url)
        ->respond();
}

/**
 * add common js support
 */
function install_js_support()
{
    ?>
    <div id="success" class="row typecho-page-main hidden">
        <div class="col-mb-12 col-tb-8 col-tb-offset-2">
            <div class="typecho-page-title">
                <h2 align="center"><?php _e('Cài Đặt Mã Nguồn Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10'); ?></h2>
            </div>
            <div id="typecho-welcome">
                <p class="keep-word">
                    <?php _e('Bạn đã chọn sử dụng dữ liệu trong database cũ, để có thể đăng nhập vào "BẢNG ĐIỀU KHIỂN" bạn phải sử dụng tài khoản và mật khẩu cũ trong database trước đó.'); ?>
                </p>
                <p class="fresh-word">
                    <?php _e('Tài khoản: '); ?>: <strong class="warning" id="success-user"></strong><br>
                    <?php _e('Mật khẩu: '); ?>: <strong class="warning" id="success-password"></strong>
                </p>
                <ul>
                    <li><a id="login-url" href=""><?php _e('=> <b style="color:orange;">VÀO BẢNG ĐIỀU KHIỂN</b>'); ?></a></li>
                    <li><a id="site-url" href=""><?php _e('=> <b style="color:blue;">XEM TRANG CHỦ</b>'); ?></a></li>
                </ul>
                <p  align="center"><?php _e('Mình hy vọng rằng, bạn sẽ thích mã nguồn <b style="color:blue;">Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10</b> và giới thiệu mã nguồn <b style="color:blue;">Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10</b> với nhiều người hơn, để cộng đồng người sử dụng mã nguồn <b style="color:blue;">Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10</b> lớn mạnh hơn! Cảm ơn bạn rất nhiều!'); ?></p>
            </div>
        </div>
    </div>
    <script>
        let form = $('form'), errorBox = $('<div></div>');

        errorBox.addClass('message error')
            .prependTo(form);

        function showError(error) {
            if (typeof error == 'string') {
                $(window).scrollTop(0);

                errorBox
                    .html(error)
                    .addClass('fade');
            } else {
                for (let k in error) {
                    let input = $('#' + k), msg = error[k], p = $('<p></p>');

                    p.addClass('message error')
                        .html(msg)
                        .insertAfter(input);

                    input.on('input', function () {
                        p.remove();
                    });
                }
            }

            return errorBox;
        }

        form.submit(function (e) {
            e.preventDefault();

            errorBox.removeClass('fade');
            $('button', form).attr('disabled', 'disabled');
            $('.typecho-option .error', form).remove();

            $.ajax({
                url: form.attr('action'),
                processData: false,
                contentType: false,
                type: 'POST',
                data: new FormData(this),
                success: function (data) {
                    $('button', form).removeAttr('disabled');

                    if (data.success) {
                        if (data.message) {
                            location.href = '?step=' + data.message;
                        } else {
                            let success = $('#success').removeClass('hidden');

                            form.addClass('hidden');

                            if (data.config) {
                                success.addClass('fresh');

                                $('.typecho-page-main:first').addClass('hidden');
                                $('#success-user').html(data.config[0]);
                                $('#success-password').html(data.config[1]);

                                $('#login-url').attr('href', data.config[2]);
                                $('#site-url').attr('href', data.config[3]);
                            } else {
                                success.addClass('keep');
                            }
                        }
                    } else {
                        let el = showError(data.message);

                        if (typeof configError == 'function' && data.config) {
                            configError(form, data.config, el);
                        }
                    }
                },
                error: function (xhr, error) {
                    showError(error)
                }
            });
        });
    </script>
    <?php
}

/**
 * @param string[] $extensions
 * @return string|null
 */
function install_check_extension(array $extensions): ?string
{
    foreach ($extensions as $extension) {
        if (extension_loaded($extension)) {
            return null;
        }
    }

    return _n('Có hàm PHP cần thiết chưa được bật.', 'Vui lòng bật các hàm sau để mã nguồn Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10 hoạt động ổn định nhất.', count($extensions))
        . ': ' . implode(', ', $extensions);
}

function install_step_1()
{
    $langs = \Widget\Options\General::getLangs();
    $lang = install_get_lang();
    ?>
    <div class="row typecho-page-main">
        <div class="col-mb-12 col-tb-8 col-tb-offset-2">
            <div class="typecho-page-title">
                <h2 align="center"><?php _e('Cài Đặt Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10'); ?></h2>
            </div>
            <div id="typecho-welcome">
                <form autocomplete="off" method="post" action="install.php">
                    <h3><?php _e('<i class="fa-solid fa-circle-info"></i> HÃY DÀNH RA 1 PHÚT ĐỂ ĐỌC "LỜI NÓI ĐẦU" ĐI ĐÃ RỒI HÃY BẮT ĐẦU CÀI ĐẶT MÃ NGUỒN "Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10"'); ?></h3>
                    <h4 style="color:violet;"><i class="fa-solid fa-list"></i> <b>Lời nói đầu:</b></h4>
                     <ol>
                        <?php _e('
                      <li>Đây là mã nguồn <b>Typecho 1.2.1 gốc</b>, mình tải tại <a href="https://typecho.org/download" title="Download Typecho Official"><u style="color:red;">Download Typecho Official</u></a>, và đã được <a href="https://facebook.com/cu.ti.9212" title="Luyện Phạm"><b style="color:blue;">Luyện Phạm</b></a> Việt Hóa full đến 99,99%. Gần như không còn 1 chữ tq rách nào trong mã nguồn! :))
                      </li>
                      <li>LƯU Ý: Mình <b style="color:black;">KHÔNG</b> chịu trách nhiệm nếu bạn download, hoặc tải về tại bất kỳ <i>blog</i> hay <i>diễn đàn</i> nào khác ngoài <a href="https://wapvn.top" title="wapvn.top"><b style="color:orange;">WAPVN.TOP</b></a>. Vì khi bạn tải <b>Typecho Việt Hóa 1.2.1</b> ở trang khác, rất có thể <b>Typecho Việt Hóa 1.2.1</b> ở đó đã bị gắn mã độc, nên mình không hỗ trợ khi bạn tải <b>Typecho Việt Hóa 1.2.1</b> từ <i>blog</i> hay <i>diễn đàn</i>khác.
                      </li>
                      <li>Hãy tải <b>Typecho Việt Hóa 1.2.1</b> tại <a href="https://WapVN.TOP" title="wapvn.top"><b style="color:orange;">WapVN.TOP</b></a> để có thể yên tâm về vấn đề bảo mật. Mã nguồn <b>Typecho Việt Hóa 1.2.1</b> tại <a href="https://WapVN.TOP" title="wapvn.top"><b style="color:orange;">WapVN.TOP</b></a> không hề có virus hay shell hoặc mã độc. Mình xin khẳng định!
                      </li>
                      <li><b>Typecho Việt Hóa 1.2.1</b> là <b><i>dự án phi lợi nhuận</b></i> được thực hiện bởi: <a href="https://facebook.com/cu.ti.9212" title="Luyện Phạm"><b style="color:blue;">Luyện Phạm</b></a>. Nên các <i>blog</i> hoặc <i>diễn đàn</i> khác, khi leech về để chia sẻ cho thành viên của mình, vui lòng <b style="color:black;">KHÔNG</b> rao bán, <b style="color:black;">KHÔNG</b> chèn ADS vào link download, <b style="color:black;">KHÔNG</b> cài mã độc vào <b>Typecho Việt Hóa 1.2.1</b>. Xin cảm ơn!
                      </li>
                      <li>
CUỐI CÙNG: Nếu bạn là người sử dụng thì hãy bấm nút "Thiết Lập Database <i class="fa-solid fa-play"></i>" để bắt đầu quá trình cài đặt mã nguồn <b>Typecho Việt Hóa 1.2.1</b> nhé!
                      </li>
                      <li>
Xin cảm ơn!
                      </li>
                     </ol>
'); ?>
                    </p>
                    <h4><?php _e('<b><i class="fa-regular fa-id-badge"></i> Giấy phép và Thỏa thuận sử dụng: </b>'); ?></h4>
                    <ul>
                        <li><?php _e('1. Đang cập nhật....'); ?></li>
                        <li><?php _e('2. Đang cập nhật....'); ?></li>
                        <li><?php _e('3. Đang cập nhật....'); ?></li>
                        <li><?php _e('4. Đang cập nhật....'); ?></li>
                        <li><?php _e('5. Đang cập nhật....'); ?></li>
                    </ul>

                    <p class="submit">
                        <button class="btn primary" type="submit"><?php _e('Thiết Lập Database <i class="fa-solid fa-play"></i>'); ?></button>
                        <input type="hidden" name="step" value="1">

                        <?php if (count($langs) > 1) : ?>
                            <select style="float: right" onchange="location.href='?lang=' + this.value">
                                <?php foreach ($langs as $key => $val) : ?>
                                    <option value="<?php echo $key; ?>"<?php if ($lang == $key) :
                                        ?> selected<?php
                                                   endif; ?>><?php echo $val; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
    install_js_support();
}

/**
 * check dependencies before install
 */
function install_step_1_perform()
{
    $errors = [];
    $checks = [
        'mbstring',
        'json',
        'Reflection',
        ['mysqli', 'sqlite3', 'pgsql', 'pdo_mysql', 'pdo_sqlite', 'pdo_pgsql']
    ];

    foreach ($checks as $check) {
        $error = install_check_extension(is_array($check) ? $check : [$check]);

        if (!empty($error)) {
            $errors[] = $error;
        }
    }

    $uploadDir = '/usr/uploads';
    $realUploadDir = \Typecho\Common::url($uploadDir, __TYPECHO_ROOT_DIR__);
    $writeable = true;
    if (is_dir($realUploadDir)) {
        if (!is_writeable($realUploadDir) || !is_readable($realUploadDir)) {
            if (!@chmod($realUploadDir, 0755)) {
                $writeable = false;
            }
        }
    } else {
        if (!@mkdir($realUploadDir, 0755)) {
            $writeable = false;
        }
    }

    if (!$writeable) {
        $errors[] = _t('Không thể ghi thư mục tải lên. Vui lòng đặt thủ công các quyền của thư mục %s trong thư mục cài đặt để có thể ghi và tiếp tục nâng cấp.', $uploadDir);
    }

    if (empty($errors)) {
        install_success(2);
    } else {
        install_raise_error(implode("\n", $errors));
    }
}

/**
 * display step 2
 */
function install_step_2()
{
    global $installDb;

    $drivers = install_get_db_drivers();
    $adapter = install_get_current_db_driver();
    $type = install_get_db_type($adapter);

    if (!empty($installDb)) {
        $config = $installDb->getConfig(\Typecho\Db::WRITE)->toArray();
        $config['prefix'] = $installDb->getPrefix();
        $config['adapter'] = $adapter;
    }
    ?>
    <div class="row typecho-page-main">
        <div class="col-mb-12 col-tb-8 col-tb-offset-2">
            <div class="typecho-page-title">
                <h2 align="center"><?php _e('Thiết Lập Thông Tin Database'); ?></h2>
            </div>
            <div id="typecho-welcome">
            <form autocomplete="off" action="install.php" method="post">
                <ul class="typecho-option">
                    <li>
                        <label for="dbAdapter" class="typecho-label"><?php _e('<b><i class="fa-solid fa-database"></i> Chọn loại database: </b>'); ?></label>
                        <select name="dbAdapter" id="dbAdapter" onchange="location.href='?step=2&driver=' + this.value">
                            <?php foreach ($drivers as $driver => $name) : ?>
                                <option value="<?php echo $driver; ?>"<?php if ($driver == $adapter) :
                                    ?> selected="selected"<?php
                                               endif; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('ㅤ+
Hãy chọn loại database phù hợp để có thể cài mã nguồn <b>Typecho Việt Hóa 1.2.1</b>  thành công.'); ?></p>
                        <input type="hidden" id="dbNext" name="dbNext" value="none">
                    </li>
                </ul>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label" for="dbPrefix"><?php _e('<b><i class="fa-solid fa-database"></i> Tiền tố database: </b>'); ?></label>
                        <input type="text" class="text" name="dbPrefix" id="dbPrefix" value="wapvntop_" />
                        <p class="description"><?php _e('ㅤ+ Nếu bạn không sửa thì tiền tố mặc định là "wapvntop_"'); ?></p>
                    </li>
                </ul>
                <?php require_once './install/' . $type . '.php'; ?>


                <ul class="typecho-option typecho-option-submit">
                    <li>
                        <button id="confirm" type="submit" class="btn primary"><?php _e('Thiết Lập Tài Khoản Admin <i class="fa-solid fa-play"></i>'); ?></button>
                        <input type="hidden" name="step" value="2">
                    </li>
                </ul>
            </form>
          </div>
        </div>
    </div>
    <script>
        function configError(form, config, errorBox) {
            let next = $('#dbNext'),
                line = $('<p></p>');

            if (config.code) {
                let text = $('<textarea></textarea>'),
                    btn = $('<button></button>');

                btn.html('<?php _e('Tiếp tục &raquo;'); ?>')
                    .attr('type', 'button')
                    .addClass('btn btn-s primary');

                btn.click(function () {
                    next.val('config');
                    form.trigger('submit');
                });

                text.val(config.code)
                    .addClass('mono')
                    .attr('readonly', 'readonly');

                errorBox.append(text)
                    .append(btn);
                return;
            }

            errorBox.append(line);

            for (let key in config) {
                let word = config[key],
                    btn = $('<button></button>');

                btn.html(word)
                    .attr('type', 'button')
                    .addClass('btn btn-s primary')
                    .click(function () {
                        next.val(key);
                        form.trigger('submit');
                    });

                line.append(btn);
            }
        }

        $('#confirm').click(function () {
            $('#dbNext').val('none');
        });

        <?php if (!empty($config)) : ?>
        function fillInput(config) {
            for (let k in config) {
                let value = config[k],
                    key = 'db' + k.charAt(0).toUpperCase() + k.slice(1),
                    input = $('#' + key)
                        .attr('readonly', 'readonly')
                        .val(value);

                $('option:not(:selected)', input).attr('disabled', 'disabled');
            }
        }

        fillInput(<?php echo json_encode($config); ?>);
        <?php endif; ?>
    </script>
    <?php
    install_js_support();
}

/**
 * perform install step 2
 */
function install_step_2_perform()
{
    global $installDb;

    $request = \Typecho\Request::getInstance();
    $drivers = install_get_db_drivers();

    $configMap = [
        'Mysql' => [
            'dbHost' => 'localhost',
            'dbPort' => 3306,
            'dbUser' => null,
            'dbPassword' => null,
            'dbCharset' => 'utf8mb4',
            'dbDatabase' => null,
            'dbEngine' => 'InnoDB',
            'dbSslCa' => null,
            'dbSslVerify' => 'on',
        ],
        'Pgsql' => [
            'dbHost' => 'localhost',
            'dbPort' => 5432,
            'dbUser' => null,
            'dbPassword' => null,
            'dbCharset' => 'utf8',
            'dbDatabase' => null,
        ],
        'SQLite' => [
            'dbFile' => __TYPECHO_ROOT_DIR__ . '/usr/' . uniqid() . '.db'
        ]
    ];

    if (install_is_cli()) {
        $config = [
            'dbHost' => $request->getServer('TYPECHO_DB_HOST'),
            'dbUser' => $request->getServer('TYPECHO_DB_USER'),
            'dbPassword' => $request->getServer('TYPECHO_DB_PASSWORD'),
            'dbCharset' => $request->getServer('TYPECHO_DB_CHARSET'),
            'dbPort' => $request->getServer('TYPECHO_DB_PORT'),
            'dbDatabase' => $request->getServer('TYPECHO_DB_DATABASE'),
            'dbFile' => $request->getServer('TYPECHO_DB_FILE'),
            'dbDsn' => $request->getServer('TYPECHO_DB_DSN'),
            'dbEngine' => $request->getServer('TYPECHO_DB_ENGINE'),
            'dbPrefix' => $request->getServer('TYPECHO_DB_PREFIX', 'typecho_'),
            'dbAdapter' => $request->getServer('TYPECHO_DB_ADAPTER', install_get_current_db_driver()),
            'dbNext' => $request->getServer('TYPECHO_DB_NEXT', 'none'),
            'dbSslCa' => $request->getServer('TYPECHO_DB_SSL_CA'),
            'dbSslVerify' => $request->getServer('TYPECHO_DB_SSL_VERIFY', 'on'),
        ];
    } else {
        $config = $request->from([
            'dbHost',
            'dbUser',
            'dbPassword',
            'dbCharset',
            'dbPort',
            'dbDatabase',
            'dbFile',
            'dbDsn',
            'dbEngine',
            'dbPrefix',
            'dbAdapter',
            'dbNext',
            'dbSslCa',
            'dbSslVerify',
        ]);
    }

    $error = (new \Typecho\Validate())
        ->addRule('dbPrefix', 'required', _t('Xác nhận cấu hình của bạn'))
        ->addRule('dbPrefix', 'minLength', _t('Xác nhận cấu hình của bạn'), 1)
        ->addRule('dbPrefix', 'maxLength', _t('Xác nhận cấu hình của bạn'), 16)
        ->addRule('dbPrefix', 'alphaDash', _t('Xác nhận cấu hình của bạn'))
        ->addRule('dbAdapter', 'required', _t('Xác nhận cấu hình của bạn'))
        ->addRule('dbAdapter', 'enum', _t('Xác nhận cấu hình của bạn'), array_keys($drivers))
        ->addRule('dbNext', 'required', _t('Xác nhận cấu hình của bạn'))
        ->addRule('dbNext', 'enum', _t('Xác nhận cấu hình của bạn'), ['none', 'delete', 'keep', 'config'])
        ->run($config);

    if (!empty($error)) {
        install_raise_error($error);
    }

    $type = install_get_db_type($config['dbAdapter']);
    $dbConfig = [];

    foreach ($configMap[$type] as $key => $value) {
        $config[$key] = !isset($config[$key]) ? (install_is_cli() ? $value : null) : $config[$key];
    }

    switch ($type) {
        case 'Mysql':
            $error = (new \Typecho\Validate())
                ->addRule('dbHost', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbPort', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbPort', 'isInteger', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbUser', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbCharset', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbCharset', 'enum', _t('Xác nhận cấu hình của bạn'), ['utf8', 'utf8mb4'])
                ->addRule('dbDatabase', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbEngine', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbEngine', 'enum', _t('Xác nhận cấu hình của bạn'), ['InnoDB', 'MyISAM'])
                ->addRule('dbSslCa', 'file_exists', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbSslVerify', 'enum', _t('Xác nhận cấu hình của bạn'), ['on', 'off'])
                ->run($config);
            break;
        case 'Pgsql':
            $error = (new \Typecho\Validate())
                ->addRule('dbHost', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbPort', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbPort', 'isInteger', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbUser', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbCharset', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbCharset', 'enum', _t('Xác nhận cấu hình của bạn'), ['utf8'])
                ->addRule('dbDatabase', 'required', _t('Xác nhận cấu hình của bạn'))
                ->run($config);
            break;
        case 'SQLite':
            $error = (new \Typecho\Validate())
                ->addRule('dbFile', 'required', _t('Xác nhận cấu hình của bạn'))
                ->addRule('dbFile', function (string $path) {
                    $pattern = "/^(\/[._a-z0-9-]+)*[a-z0-9]+\.[a-z0-9]{2,}$/i";
                    if (strstr(PHP_OS, 'WIN')) {
                        $pattern = "/(\/[._a-z0-9-]+)*[a-z0-9]+\.[a-z0-9]{2,}$/i";
                    }
                    return !!preg_match($pattern, $path);
                }, _t('Xác nhận cấu hình của bạn'))
                ->run($config);
            break;
        default:
            install_raise_error(_t('Xác nhận cấu hình của bạn'));
            break;
    }

    if (!empty($error)) {
        install_raise_error($error);
    }

    foreach ($configMap[$type] as $key => $value) {
        $dbConfig[lcfirst(substr($key, 2))] = $config[$key];
    }

    // intval port number
    if (isset($dbConfig['port'])) {
        $dbConfig['port'] = intval($dbConfig['port']);
    }

    // bool ssl verify
    if (isset($dbConfig['sslVerify'])) {
        $dbConfig['sslVerify'] = $dbConfig['sslVerify'] == 'on';
    }

    if (isset($dbConfig['file']) && preg_match("/^[a-z0-9]+\.[a-z0-9]{2,}$/i", $dbConfig['file'])) {
        $dbConfig['file'] = __DIR__ . '/usr/' . $dbConfig['file'];
    }

    // check config file
    if ($config['dbNext'] == 'config' && !install_check('config')) {
        $code = install_config_file($config['dbAdapter'], $config['dbPrefix'], $dbConfig, true);
        install_raise_error(_t('Không tìm thấy tập tin cấu hình bạn tạo thủ công, vui lòng kiểm tra và tạo lại.'), ['code' => $code]);
    } elseif (empty($installDb)) {
        // detect db config
        try {
            $installDb = new \Typecho\Db($config['dbAdapter'], $config['dbPrefix']);
            $installDb->addServer($dbConfig, \Typecho\Db::READ | \Typecho\Db::WRITE);
            $installDb->query('SELECT 1=1');
        } catch (\Typecho\Db\Adapter\ConnectionException $e) {
            $code = $e->getCode();
            if (('Mysql' == $type && 1049 == $code) || ('Pgsql' == $type && 7 == $code)) {
                install_raise_error(_t('Cơ sở dữ liệu: "%s" không tồn tại, vui lòng tạo thủ công và thử lại!', $config['dbDatabase']));
            } else {
                install_raise_error(_t('Rất tiếc, không thể kết nối với cơ sở dữ liệu, vui lòng kiểm tra cấu hình cơ sở dữ liệu trước khi tiếp tục cài đặt: "%s"', $e->getMessage()));
            }
        } catch (\Typecho\Db\Exception $e) {
            install_raise_error(_t('Quá trình cài đặt gặp lỗi sau: "%s". Quá trình cài đặt đã bị chấm dứt, vui lòng kiểm tra thông tin cấu hình của bạn.', $e->getMessage()));
        }

        $code = install_config_file($config['dbAdapter'], $config['dbPrefix'], $dbConfig);

        if (!install_check('config')) {
            install_raise_error(
                _t('Quá trình cài đặt không thể tự tạo tập tin <strong>public_html/config.inc.php</strong>!') . "\n" .
                _t('Bạn có thể fix bằng cách tạo tập tin <strong>public_html/config.inc.php</strong> trên hosting hoặc máy chủ, sau đó sao chép và dán đoạn code dưới vào: '),
                [
                'code' => $code
                ]
            );
        }
    }

    // delete exists db
    if ($config['dbNext'] == 'delete') {
        $tables = [
            $config['dbPrefix'] . 'comments',
            $config['dbPrefix'] . 'contents',
            $config['dbPrefix'] . 'fields',
            $config['dbPrefix'] . 'metas',
            $config['dbPrefix'] . 'options',
            $config['dbPrefix'] . 'relationships',
            $config['dbPrefix'] . 'users'
        ];

        try {
            foreach ($tables as $table) {
                switch ($type) {
                    case 'Mysql':
                        $installDb->query("DROP TABLE IF EXISTS `{$table}`");
                        break;
                    case 'Pgsql':
                    case 'SQLite':
                        $installDb->query("DROP TABLE {$table}");
                        break;
                }
            }
        } catch (\Typecho\Db\Exception $e) {
            install_raise_error(_t('Quá trình cài đặt gặp lỗi sau: "%s". Quá trình cài đặt đã bị chấm dứt, vui lòng kiểm tra thông tin cấu hình của bạn.', $e->getMessage()));
        }
    }

    // init db structure
    try {
        $scripts = file_get_contents(__TYPECHO_ROOT_DIR__ . '/install/' . $type . '.sql');
        $scripts = str_replace('typecho_', $config['dbPrefix'], $scripts);

        if (isset($dbConfig['charset'])) {
            $scripts = str_replace('%charset%', $dbConfig['charset'], $scripts);
        }

        if (isset($dbConfig['engine'])) {
            $scripts = str_replace('%engine%', $dbConfig['engine'], $scripts);
        }

        $scripts = explode(';', $scripts);
        foreach ($scripts as $script) {
            $script = trim($script);
            if ($script) {
                $installDb->query($script, \Typecho\Db::WRITE);
            }
        }
    } catch (\Typecho\Db\Exception $e) {
        $code = $e->getCode();

        if (
            ('Mysql' == $type && (1050 == $code || '42S01' == $code)) ||
            ('SQLite' == $type && ('HY000' == $code || 1 == $code)) ||
            ('Pgsql' == $type && '42P07' == $code)
        ) {
            if ($config['dbNext'] == 'keep') {
                if (install_check('db_data')) {
                    install_success(0);
                } else {
                    install_success(3);
                }
            } elseif ($config['dbNext'] == 'none') {
                install_remove_config_file();

                install_raise_error(_t('Quá trình cài đặt đã kiểm tra xem bảng dữ liệu gốc đã tồn tại chưa.'), [
                    'delete' => _t('Xóa dữ liệu gốc'),
                    'keep' => _t('Sử dụng dữ liệu gốc')
                ]);
            }
        } else {
            install_remove_config_file();

            install_raise_error(_t('Quá trình cài đặt gặp lỗi sau: "%s". Quá trình cài đặt đã bị chấm dứt, vui lòng kiểm tra thông tin cấu hình của bạn.', $e->getMessage()));
        }
    }

    install_success(3);
}

/**
 * display step 3
 */
function install_step_3()
{
    $options = \Widget\Options::alloc();
    ?>
    <div class="row typecho-page-main">
        <div class="col-mb-12 col-tb-8 col-tb-offset-2">
            <div class="typecho-page-title">
                <h2 align="center"><?php _e('Thiết Lập Tài Khoản Administration'); ?></h2>
            </div>
            <form autocomplete="off" action="install.php" method="post">
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label" for="userUrl"><?php _e('<i class="fa-solid fa-globe"></i> Tên miền: '); ?></label>
                        <input autocomplete="new-password" type="text" name="userUrl" id="userUrl" class="text" value="<?php $options->rootUrl(); ?>" />
                        <p class="description"><?php _e('ㅤ+ Đây là tên miền được mã nguồn <b style="color:blue;">Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10</b> tự động điền. Nếu tên miền mà mã nguồn <b style="color:blue;">Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10</b> tự động điền chưa chính xác, bạn có thể sửa lại tên miền của website trong <b>BẢNG ĐIỀU KHIỂN</b>.'); ?></p>
                    </li>
                </ul>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label" for="userName"><?php _e('<b style="color:red;"><i class="fa-solid fa-user"></i></b> Tài khoản Administration'); ?></label>
                        <input autocomplete="new-password" type="text" name="userName" id="userName" class="text" />
                        <p class="description"><?php _e('ㅤ+ Vui lòng điền tên tài khoản <b style="color:red;">Administration</b>!'); ?></p>
                    </li>
                </ul>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label" for="userPassword"><?php _e('<b style="color:red;"><i class="fa-solid fa-key"></i></b> Mật khẩu Administration'); ?></label>
                        <input type="password" name="userPassword" id="userPassword" class="text" />
                        <p class="description"><?php _e('ㅤ+ Vui lòng điền mật khẩu của tài khoản <b style="color:red;">Admin</b>, nếu để trống hệ thống sẽ tạo ngẫu nhiên mật khẩu cho tài khoản <b style="color:red;">Administration</b> của bạn!'); ?></p>
                    </li>
                </ul>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label" for="userMail"><?php _e('<b style="color:red;"><i class="fa-solid fa-envelope"></i></b> Email Administration'); ?></label>
                        <input autocomplete="new-password" type="text" name="userMail" id="userMail" class="text" />
                        <p class="description"><?php _e('ㅤ+ Hãy điền địa chỉ email để thiết lập làm email chính của tài khoản <b style="color:red;">Administration</b>!'); ?></p>
                    </li>
                </ul>
                <ul class="typecho-option typecho-option-submit">
                    <li>
                        <button type="submit" class="btn primary"><?php _e('Tiếp tục <i class="fa-solid fa-play"></i>'); ?></button>
                        <input type="hidden" name="step" value="3">
                    </li>
                </ul>
            </form>
        </div>
    </div>
    <?php
    install_js_support();
}

/**
 * perform step 3
 */
function install_step_3_perform()
{
    global $installDb;

    $request = \Typecho\Request::getInstance();
    $defaultPassword = \Typecho\Common::randString(8);
    $options = \Widget\Options::alloc();

    if (install_is_cli()) {
        $config = [
            'userUrl' => $request->getServer('TYPECHO_SITE_URL'),
            'userName' => $request->getServer('TYPECHO_USER_NAME', 'typecho'),
            'userPassword' => $request->getServer('TYPECHO_USER_PASSWORD'),
            'userMail' => $request->getServer('TYPECHO_USER_MAIL', 'admin@localhost.local')
        ];
    } else {
        $config = $request->from([
            'userUrl',
            'userName',
            'userPassword',
            'userMail',
        ]);
    }

    $error = (new \Typecho\Validate())
        ->addRule('userUrl', 'required', _t('Vui lòng nhập tên miền của website!'))
        ->addRule('userUrl', 'url', _t('Vui lòng nhập tên miền hợp lệ! VD: https://wapvn.top'))
        ->addRule('userName', 'required', _t('Vui lòng điền tên tài khoản Admin!'))
        ->addRule('userName', 'xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong tên tài khoản Admin! (nghi vấn: XSS)'))
        ->addRule('userName', 'maxLength', _t('Tên tài khoản Admin quá dài, không vượt quá 32 ký tự.'), 32)
        ->addRule('userMail', 'required', _t('Vui lòng điền email!'))
        ->addRule('userMail', 'email', _t('Email không đúng định dạng!'))
        ->addRule('userMail', 'maxLength', _t('Mail bạn vừa điền quá dài, không vượt quá 200 ký tự!'), 200)
        ->run($config);

    if (!empty($error)) {
        install_raise_error($error);
    }

    if (empty($config['userPassword'])) {
        $config['userPassword'] = $defaultPassword;
    }

    try {
        // write user
        $hasher = new \Utils\PasswordHash(8, true);
        $installDb->query(
            $installDb->insert('table.users')->rows([
                'name' => $config['userName'],
                'password' => $hasher->hashPassword($config['userPassword']),
                'mail' => $config['userMail'],
                'url' => $config['userUrl'],
                'screenName' => $config['userName'],
                'group' => 'administrator',
                'created' => \Typecho\Date::time()
            ])
        );

        // write category
        $installDb->query(
            $installDb->insert('table.metas')
                ->rows([
                    'name' => _t('Danh Mục Mẹ'),
                    'slug' => 'danh-muc-me',
                    'fontawesome' => '<i class="fa-duotone fa-solid fa-bars"></i>',
                    'type' => 'category',
                    'description' => _t('Đây là mô tả mặc định!'),
                    'count' => 1
                ])
        );

        $installDb->query($installDb->insert('table.relationships')->rows(['cid' => 1, 'mid' => 1]));

        // write first page and post
        $installDb->query(
            $installDb->insert('table.contents')->rows([
                'title' => _t('Cài Đặt Thành Công Mã Nguồn Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10'),
                'slug' => 'start', 'created' => \Typecho\Date::time(),
                'modified' => \Typecho\Date::time(),
                'text' => '<!--markdown-->' . _t('　CHÚC MỪNG, BẠN ĐÃ CÀI ĐẶT THÀNH CÔNG MÃ NGUỒN [**Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10**](https://wapvn.top)
<center>
![Logo](https://wapvn.top/images/logo.png)
</center>
# Cài đặt thành công!
- Nếu bạn nhìn thấy bài viết đầu tiên này, tức là hosting hoặc máy chủ của bạn đã được cài đặt thành công mã nguồn [**Typecho Việt Hóa 1.2.1**](https://wapvn.top)
- Do mình sử dụng *Google dịch* và *Translate.Yandex* nên có nhiều câu nhiều từ dịch chưa được sát nghĩa và còn mang ý nghĩa rất chung chung. Vấn đề này mình sẽ từ từ khắc phục lại bằng các bản cập nhật nhỏ trong tương lai!
- Thường xuyên truy cập [**WAPVN.TOP**](https://wapvn.top) để không bỏ lỡ các bản cập nhật vá lỗi cho mã nguồn [**Typecho Việt Hóa 1.2.1**](https://wapvn.top) nhé!'),
                'authorId' => 1,
                'type' => 'post',
                'status' => 'publish',
                'commentsNum' => 1,
                'allowComment' => 1,
                'allowPing' => 1,
                'allowFeed' => 1,
                'parent' => 0
            ])
        );

        $installDb->query(
            $installDb->insert('table.contents')->rows([
                'title' => _t('Trang mẫu'),
                'slug' => 'trang-mau',
                'created' => \Typecho\Date::time(),
                'modified' => \Typecho\Date::time(),
                'text' => '<!--markdown-->' . _t('- Nếu bạn nhìn thấy **Trang mẫu** này, tức là hosting hoặc máy chủ của bạn đã cài đặt thành công mã nguồn **Typecho Việt Hóa 1.2.1 **!
- Bạn có thể sửa hoặc xoá "Trang mặc định" này trong **Bảng Điều Khiển**!
- Xin cảm ơn bạn đã tin tưởng và chọn sử dụng mã nguồn **Typecho Việt Hóa 1.2.1** của mình để sử dụng!'),
                'authorId' => 1,
                'order' => 0,
                'type' => 'page',
                'status' => 'publish',
                'commentsNum' => 0,
                'allowComment' => 1,
                'allowPing' => 1,
                'allowFeed' => 1,
                'parent' => 0
            ])
        );

        // write comment
        $installDb->query(
            $installDb->insert('table.comments')->rows([
                'cid' => 1, 'created' => \Typecho\Date::time(),
                'author' => 'MeoDiLac',
                'ownerId' => 1,
                'url' => 'https://facebook.com/cu.ti.9212',
                'ip' => '127.0.0.1',
                'agent' => $options->generator,
                'text' => 'Đây là "Bình luận mặc định" được tạo tự động sau khi mã nguồn "Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10" được cài đặt thành công lên hosting hoặc máy chủ của bạn! Bạn có thể "xoá" hoặc "sửa bình luận" này trong "Bảng Điều Khiển"! Bạn có thể tìm thêm nhiều Giao Diện - Themes Đã Việt Hoá cực đẹp và Plugins Đã Việt Hoá tại WAPVN.TOP. Các nội dung được chia sẻ trên WAPVN.TOP là hoàn toàn miễn phí.',
                'type' => 'comment',
                'status' => 'approved',
                'parent' => 0
            ])
        );

        // write options
        foreach (install_get_default_options() as $key => $value) {
            // mark installing finished
            if ($key == 'installed') {
                $value = 1;
            }

            $installDb->query(
                $installDb->insert('table.options')->rows(['name' => $key, 'user' => 0, 'value' => $value])
            );
        }
    } catch (\Typecho\Db\Exception $e) {
        install_raise_error($e->getMessage());
    }

    $parts = parse_url($options->loginAction);
    $parts['query'] = http_build_query([
            'name'  => $config['userName'],
            'password' => $config['userPassword'],
            'referer' => $options->adminUrl
        ]);
    $loginUrl = \Typecho\Common::buildUrl($parts);

    install_success(0, [
        $config['userName'],
        $config['userPassword'],
        \Widget\Security::alloc()->getTokenUrl($loginUrl, $request->getReferer()),
        $config['userUrl']
    ]);
}

/**
 * dispatch install action
 *
 */
function install_dispatch()
{
    // disable root url on cli mode
    if (install_is_cli()) {
        define('__TYPECHO_ROOT_URL__', 'http://localhost');
    }

    // init default options
    $options = \Widget\Options::alloc(install_get_default_options());
    \Widget\Init::alloc();

    // display version
    if (install_is_cli()) {
        echo $options->generator . "\n";
        echo 'PHP ' . PHP_VERSION . "\n";
    }

    // install finished yet
    if (
        install_check('config')
        && install_check('db_structure')
        && install_check('db_data')
    ) {
        // redirect to siteUrl if not cli
        if (!install_is_cli()) {
            install_redirect($options->siteUrl);
        }

        exit(1);
    }

    if (install_is_cli()) {
        install_step_1_perform();
    } else {
        $request = \Typecho\Request::getInstance();
        $step = $request->get('step');

        $action = 1;

        switch (true) {
            case $step == 2:
                if (!install_check('db_structure')) {
                    $action = 2;
                } else {
                    install_redirect('install.php?step=3');
                }
                break;
            case $step == 3:
                if (install_check('db_structure')) {
                    $action = 3;
                } else {
                    install_redirect('install.php?step=2');
                }
                break;
            default:
                break;
        }

        $method = 'install_step_' . $action;

        if ($request->isPost()) {
            $method .= '_perform';
            $method();
            exit;
        }
        ?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="<?php _e('UTF-8'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php _e('Cài Đặt Mã Nguồn Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10'); ?></title>
    <link rel="shortcut icon" href="https://wapvn.top/images/favicon.png" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/js/all.js" integrity="sha512-uNrBiKhFm8UOf0IXqkeojIesJ5glWJt8+epL5xwBBe1J9tcmd54f/vwQ6+g2ahXBHuayqaQcelUK7CULdWHinQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/js/all.min.js" integrity="sha512-1JkMy1LR9bTo3psH+H4SV5bO2dFylgOy+UJhMus1zF4VEFuZVu5lsi4I6iIndE4N9p01z1554ZDcvMSjMaqCBQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" type="text/css" href="<?php $options->adminStaticUrl('css', 'normalize.css') ?>" />
    <link rel="stylesheet" type="text/css" href="<?php $options->adminStaticUrl('css', 'grid.css') ?>" />
    <link rel="stylesheet" type="text/css" href="<?php $options->adminStaticUrl('css', 'style.css') ?>" />
    <link rel="stylesheet" type="text/css" href="<?php $options->adminStaticUrl('css', 'install.css') ?>" />
    <script src="<?php $options->adminStaticUrl('js', 'jquery.js'); ?>"></script>
</head>
<body>
    <div class="body container">
        <h1 align="center"><a href="https://wapvn.top" target="_blank"><b style="color:violet;">Typecho Việt Hoá 1.2.1 - NewBuild - Alpha10</b></a><br><a href="https://wapvn.top" target="_blank"><img src="https://wapvn.top/images/logo.png" title="Logo WapVN.Top" width="390" height="auto" /></a></h1>
        <?php $method(); ?>
    </div>
</body>
</html>
        <?php
    }
}

install_dispatch();
