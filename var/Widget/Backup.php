<?php

namespace Widget;

use Typecho\Common;
use Typecho\Cookie;
use Typecho\Exception;
use Typecho\Plugin;
use Widget\Base\Options as BaseOptions;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Công cụ sao lưu
 *
 * @package Widget
 */
class Backup extends BaseOptions implements ActionInterface
{
    public const HEADER = '%TYPECHO_BACKUP_XXXX%';
    public const HEADER_VERSION = '0001';

    /**
     * @var array
     */
    private $types = [
        'contents'      => 1,
        'comments'      => 2,
        'metas'         => 3,
        'relationships' => 4,
        'users'         => 5,
        'fields'        => 6
    ];

    /**
     * @var array
     */
    private $fields = [
        'contents'      => [
            'cid', 'title', 'slug', 'created', 'modified', 'text', 'order', 'authorId',
            'template', 'type', 'status', 'password', 'commentsNum', 'allowComment', 'allowPing', 'allowFeed', 'parent'
        ],
        'comments'      => [
            'coid', 'cid', 'created', 'author', 'authorId', 'ownerId',
            'mail', 'url', 'ip', 'agent', 'text', 'type', 'status', 'parent'
        ],
        'metas'         => [
            'mid', 'name', 'slug', 'type', 'description', 'count', 'order', 'parent'
        ],
        'relationships' => ['cid', 'mid'],
        'users'         => [
            'uid', 'name', 'password', 'mail', 'url', 'screenName',
            'created', 'activated', 'logged', 'group', 'authCode'
        ],
        'fields'        => [
            'cid', 'name', 'type', 'str_value', 'int_value', 'float_value'
        ]
    ];

    /**
     * @var array
     */
    private $lastIds = [];

    /**
     * @var array
     */
    private $cleared = [];

    /**
     * @var bool
     */
    private $login = false;

    /**
     * Liệt kê các tập tin sao lưu hiện có
     *
     * @return array
     */
    public function listFiles(): array
    {
        return array_map('basename', glob(__TYPECHO_BACKUP_DIR__ . '/*.dat'));
    }

    /**
     * Hành động ràng buộc
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();

        $this->on($this->request->is('do=export'))->export();
        $this->on($this->request->is('do=import'))->import();
    }

    /**
     * Xuất dữ liệu
     *
     * @throws \Typecho\Db\Exception
     */
    private function export()
    {
        $backupFile = tempnam(sys_get_temp_dir(), 'backup_');
        $fp = fopen($backupFile, 'wb');
        $host = parse_url($this->options->siteUrl, PHP_URL_HOST);
        $this->response->setContentType('application/octet-stream');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="'
            . date('dmY') . '_' . $host . '_' . uniqid() . '.dat"');

        $header = str_replace('XXXX', self::HEADER_VERSION, self::HEADER);
        fwrite($fp, $header);
        $db = $this->db;

        foreach ($this->types as $type => $val) {
            $page = 1;
            do {
                $rows = $db->fetchAll($db->select()->from('table.' . $type)->page($page, 20));
                $page++;

                foreach ($rows as $row) {
                    fwrite($fp, $this->buildBuffer($val, $this->applyFields($type, $row)));
                }
            } while (count($rows) == 20);
        }

        self::pluginHandle()->export($fp);
        fwrite($fp, $header);
        fclose($fp);

        $this->response->throwFile($backupFile, 'application/octet-stream');
    }

    /**
     * @param $type
     * @param $data
     * @return string
     */
    private function buildBuffer($type, $data): string
    {
        $body = '';
        $schema = [];

        foreach ($data as $key => $val) {
            $schema[$key] = null === $val ? null : strlen($val);
            $body .= $val;
        }

        $header = json_encode($schema);
        return Common::buildBackupBuffer($type, $header, $body);
    }

    /**
     * Lọc trường
     *
     * @param $table
     * @param $data
     * @return array
     */
    private function applyFields($table, $data): array
    {
        $result = [];

        foreach ($data as $key => $val) {
            $index = array_search($key, $this->fields[$table]);

            if ($index !== false) {
                $result[$key] = $val;

                if ($index === 0 && !in_array($table, ['relationships', 'fields'])) {
                    $this->lastIds[$table] = isset($this->lastIds[$table])
                        ? max($this->lastIds[$table], $val) : $val;
                }
            }
        }

        return $result;
    }

    /**
     * Nhập dữ liệu
     */
    private function import()
    {
        $path = null;

        if (!empty($_FILES)) {
            $file = array_pop($_FILES);

            if(UPLOAD_ERR_NO_FILE == $file['error']) {
                Notice::alloc()->set(_t('Không có tập tin sao lưu nào được chọn!'), 'error');
                $this->response->goBack();
            }

            if (UPLOAD_ERR_OK == $file['error'] && is_uploaded_file($file['tmp_name'])) {
                $path = $file['tmp_name'];
            } else {
                Notice::alloc()->set(_t('Tải lên tập tin sao lưu không thành công!'), 'error');
                $this->response->goBack();
            }
        } else {
            if (!$this->request->is('file')) {
                Notice::alloc()->set(_t('Không có tập tin sao lưu nào được chọn!'), 'error');
                $this->response->goBack();
            }

            $path = __TYPECHO_BACKUP_DIR__ . '/' . $this->request->get('file');

            if (!file_exists($path)) {
                Notice::alloc()->set(_t('Tập tin sao lưu không tồn tại!'), 'error');
                $this->response->goBack();
            }
        }

        $this->extractData($path);
    }

    /**
     * Phân tích dữ liệu
     *
     * @param $file
     * @throws \Typecho\Db\Exception
     */
    private function extractData($file)
    {
        $fp = @fopen($file, 'rb');

        if (!$fp) {
            Notice::alloc()->set(_t('无法读取备份文件'), 'error');
            $this->response->goBack();
        }

        $fileSize = filesize($file);
        $headerSize = strlen(self::HEADER);

        if ($fileSize < $headerSize) {
            @fclose($fp);
            Notice::alloc()->set(_t('Lỗi định dạng tập tin sao lưu!'), 'error');
            $this->response->goBack();
        }

        $fileHeader = @fread($fp, $headerSize);

        if (!$this->parseHeader($fileHeader, $version)) {
            @fclose($fp);
            Notice::alloc()->set(_t('Lỗi định dạng tập tin sao lưu!'), 'error');
            $this->response->goBack();
        }

        fseek($fp, $fileSize - $headerSize);
        $fileFooter = @fread($fp, $headerSize);

        if (!$this->parseHeader($fileFooter, $version)) {
            @fclose($fp);
            Notice::alloc()->set(_t('Lỗi định dạng tập tin sao lưu!'), 'error');
            $this->response->goBack();
        }

        fseek($fp, $headerSize);
        $offset = $headerSize;

        while (!feof($fp) && $offset + $headerSize < $fileSize) {
            $data = Common::extractBackupBuffer($fp, $offset, $version);

            if (!$data) {
                @fclose($fp);
                Notice::alloc()->set(_t('Đã xảy ra lỗi khi khôi phục dữ liệu!'), 'error');
                $this->response->goBack();
            }

            [$type, $header, $body] = $data;
            $this->processData($type, $header, $body);
        }

        // Đặt lại số lượng cho PGSQL
        if (false !== strpos(strtolower($this->db->getAdapterName()), 'pgsql')) {
            foreach ($this->lastIds as $table => $id) {
                $seq = $this->db->getPrefix() . $table . '_seq';
                $this->db->query('ALTER SEQUENCE ' . $seq . ' RESTART WITH ' . ($id + 1));
            }
        }

        @fclose($fp);
        Notice::alloc()->set(_t('Quá trình khôi phục dữ liệu đã hoàn tất!'), 'success');
        $this->response->goBack();
    }

    /**
     * @param $str
     * @param $version
     * @return bool
     */
    private function parseHeader($str, &$version): bool
    {
        if (!$str || strlen($str) != strlen(self::HEADER)) {
            return false;
        }

        if (!preg_match("/%TYPECHO_BACKUP_[A-Z0-9]{4}%/", $str)) {
            return false;
        }

        $version = substr($str, 16, - 1);
        return true;
    }

    /**
     * @param $type
     * @param $header
     * @param $body
     */
    private function processData($type, $header, $body)
    {
        $table = array_search($type, $this->types);

        if (!empty($table)) {
            $schema = json_decode($header, true);
            $data = [];
            $offset = 0;

            foreach ($schema as $key => $val) {
                $data[$key] = null === $val ? null : substr($body, $offset, $val);
                $offset += $val;
            }

            $this->importData($table, $data);
        } else {
            self::pluginHandle()->import($type, $header, $body);
        }
    }

    /**
     * Nhập một phần dữ liệu
     *
     * @param $table
     * @param $data
     */
    private function importData($table, $data)
    {
        $db = $this->db;

        try {
            if (empty($this->cleared[$table])) {
                // xóa dữ liệu
                $db->truncate('table.' . $table);
                $this->cleared[$table] = true;
            }

            if (!$this->login && 'users' == $table && $data['group'] == 'administrator') {
                // Đăng nhập lại
                $this->reLogin($data);
            }

            $db->query($db->insert('table.' . $table)->rows($this->applyFields($table, $data)));
        } catch (Exception $e) {
            Notice::alloc()->set(_t('Đã gặp lỗi sau trong quá trình khôi phục: %s', $e->getMessage()), 'error');
            $this->response->goBack();
        }
    }

    /**
     * Quá trình sao lưu sẽ ghi đè lên dữ liệu người dùng
     * Vì vậy bạn cần đăng nhập lại với người dùng hiện tại
     *
     * @param $user
     */
    private function reLogin(&$user)
    {
        if (empty($user['authCode'])) {
            $user['authCode'] = function_exists('openssl_random_pseudo_bytes') ?
                bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Common::randString(20));
        }

        $user['activated'] = $this->options->time;
        $user['logged'] = $user['activated'];

        Cookie::set('__typecho_uid', $user['uid']);
        Cookie::set('__typecho_authCode', Common::hash($user['authCode']));
        $this->login = true;
    }
}
