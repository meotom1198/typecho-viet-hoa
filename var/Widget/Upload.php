<?php

namespace Widget;

use Typecho\Common;
use Typecho\Date;
use Typecho\Db\Exception;
use Typecho\Plugin;
use Typecho\Widget;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Tải thành phần lên
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Upload extends Contents implements ActionInterface
{
    // Tải lên thư mục tập tin
    public const UPLOAD_DIR = '/usr/uploads';

    /**
     * Xóa tập tin
     *
     * @param array $content Thông tin liên quan đến tập tin
     * @return bool
     */
    public static function deleteHandle(array $content): bool
    {
        $result = Plugin::factory(Upload::class)->trigger($hasDeleted)->deleteHandle($content);
        if ($hasDeleted) {
            return $result;
        }

        return @unlink(__TYPECHO_ROOT_DIR__ . '/' . $content['attachment']->path);
    }

    /**
     * Nhận đường dẫn truy cập tuyệt đối của tệp thực tế
     *
     * @param array $content Thông tin liên quan đến tập tin
     * @return string
     */
    public static function attachmentHandle(array $content): string
    {
        $result = Plugin::factory(Upload::class)->trigger($hasPlugged)->attachmentHandle($content);
        if ($hasPlugged) {
            return $result;
        }

        $options = Options::alloc();
        return Common::url(
            $content['attachment']->path,
            defined('__TYPECHO_UPLOAD_URL__') ? __TYPECHO_UPLOAD_URL__ : $options->siteUrl
        );
    }

    /**
     * Nhận dữ liệu tệp thực tế
     *
     * @param array $content
     * @return string
     */
    public static function attachmentDataHandle(array $content): string
    {
        $result = Plugin::factory(Upload::class)->trigger($hasPlugged)->attachmentDataHandle($content);
        if ($hasPlugged) {
            return $result;
        }

        return file_get_contents(
            Common::url(
                $content['attachment']->path,
                defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__
            )
        );
    }

    /**
     * hàm khởi tạo
     */
    public function action()
    {
        if ($this->user->pass('contributor', true) && $this->request->isPost()) {
            $this->security->protect();
            if ($this->request->is('do=modify&cid')) {
                $this->modify();
            } else {
                $this->upload();
            }
        } else {
            $this->response->setStatus(403);
        }
    }

    /**
     * Thực hiện thủ tục nâng cấp
     *
     * @throws Exception
     */
    public function modify()
    {
        if (!empty($_FILES)) {
            $file = array_pop($_FILES);
            if (0 == $file['error'] && is_uploaded_file($file['tmp_name'])) {
                $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $this->request->filter('int')->cid)
                    ->where('table.contents.type = ?', 'attachment'), [$this, 'push']);

                if (!$this->have()) {
                    $this->response->setStatus(404);
                    exit;
                }

                if (!$this->allow('edit')) {
                    $this->response->setStatus(403);
                    exit;
                }

                // Gửi xhr không thể hỗ trợ utf8
                if ($this->request->isAjax()) {
                    $file['name'] = urldecode($file['name']);
                }

                $result = self::modifyHandle($this->row, $file);

                if (false !== $result) {
                    self::pluginHandle()->beforeModify($result);

                    $this->update([
                        'text' => serialize($result)
                    ], $this->db->sql()->where('cid = ?', $this->cid));

                    $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $this->cid)
                        ->where('table.contents.type = ?', 'attachment'), [$this, 'push']);

                    /** Thêm giao diện plugin */
                    self::pluginHandle()->modify($this);

                    $this->response->throwJson([$this->attachment->url, [
                        'cid' => $this->cid,
                        'title' => $this->attachment->name,
                        'type' => $this->attachment->type,
                        'size' => $this->attachment->size,
                        'bytes' => number_format(ceil($this->attachment->size / 1024)) . ' Kb',
                        'isImage' => $this->attachment->isImage,
                        'url' => $this->attachment->url,
                        'permalink' => $this->permalink
                    ]]);
                }
            }
        }

        $this->response->throwJson(false);
    }

    /**
     * Sửa đổi chức năng xử lý tệp. Nếu bạn cần triển khai hàm băm tệp của riêng mình hoặc hệ thống tệp đặc biệt, vui lòng thay đổi ModifyHandle thành chức năng của riêng bạn trong bảng tùy chọn.
     *
     * @param array $content tập tin cũ
     * @param array $file Các tập tin mới được tải lên
     * @return mixed
     */
    public static function modifyHandle(array $content, array $file)
    {
        if (empty($file['name'])) {
            return false;
        }

        $result = self::pluginHandle()->trigger($hasModified)->modifyHandle($content, $file);
        if ($hasModified) {
            return $result;
        }

        $ext = self::getSafeName($file['name']);

        if ($content['attachment']->type != $ext) {
            return false;
        }

        $path = Common::url(
            $content['attachment']->path,
            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__
        );
        $dir = dirname($path);

        // Tạo thư mục tải lên
        if (!is_dir($dir)) {
            if (!self::makeUploadDir($dir)) {
                return false;
            }
        }

        if (isset($file['tmp_name'])) {
            @unlink($path);

            // Tệp tải lên di động
            if (!@move_uploaded_file($file['tmp_name'], $path)) {
                return false;
            }
        } elseif (isset($file['bytes'])) {
            @unlink($path);

            // Viết trực tiếp vào tập tin
            if (!file_put_contents($path, $file['bytes'])) {
                return false;
            }
        } elseif (isset($file['bits'])) {
            @unlink($path);

            // Viết trực tiếp vào tập tin
            if (!file_put_contents($path, $file['bits'])) {
                return false;
            }
        } else {
            return false;
        }

        if (!isset($file['size'])) {
            $file['size'] = filesize($path);
        }

        // Viết trực tiếp vào tập tin
        return [
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        ];
    }

    /**
     * Nhận tên tập tin an toàn
     *
     * @param string $name
     * @return string
     */
    private static function getSafeName(string &$name): string
    {
        $name = str_replace(['"', '<', '>'], '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);

        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

    /**
     * Tạo đường dẫn tải lên
     *
     * @param string $path con đường
     * @return boolean
     */
    private static function makeUploadDir(string $path): bool
    {
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last, 0755)) {
            return false;
        }

        return self::makeUploadDir($path);
    }

    /**
     * Thực hiện thủ tục nâng cấp
     *
     * @throws Exception
     */
    public function upload()
    {
        if (!empty($_FILES)) {
            $file = array_pop($_FILES);
            if (0 == $file['error'] && is_uploaded_file($file['tmp_name'])) {
                // Gửi xhr không thể hỗ trợ utf8
                if ($this->request->isAjax()) {
                    $file['name'] = urldecode($file['name']);
                }
                $result = self::uploadHandle($file);

                if (false !== $result) {
                    self::pluginHandle()->beforeUpload($result);

                    $struct = [
                        'title' => $result['name'],
                        'slug' => $result['name'],
                        'type' => 'attachment',
                        'status' => 'publish',
                        'text' => serialize($result),
                        'allowComment' => 1,
                        'allowPing' => 0,
                        'allowFeed' => 1
                    ];

                    if (isset($this->request->cid)) {
                        $cid = $this->request->filter('int')->cid;

                        if ($this->isWriteable($this->db->sql()->where('cid = ?', $cid))) {
                            $struct['parent'] = $cid;
                        }
                    }

                    $insertId = $this->insert($struct);

                    $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $insertId)
                        ->where('table.contents.type = ?', 'attachment'), [$this, 'push']);

                    /** Thêm giao diện plugin */
                    self::pluginHandle()->upload($this);

                    $this->response->throwJson([$this->attachment->url, [
                        'cid' => $insertId,
                        'title' => $this->attachment->name,
                        'type' => $this->attachment->type,
                        'size' => $this->attachment->size,
                        'bytes' => number_format(ceil($this->attachment->size / 1024)) . ' Kb',
                        'isImage' => $this->attachment->isImage,
                        'url' => $this->attachment->url,
                        'permalink' => $this->permalink
                    ]]);

                }
            }
        }

        $this->response->throwJson(false);
    }

    /**
     * Chức năng xử lý tệp tải lên Nếu bạn cần triển khai hàm băm tệp của riêng mình hoặc hệ thống tệp đặc biệt, vui lòng thay đổi uploadHandle thành chức năng của riêng bạn trong bảng tùy chọn.
     *
     * @param array $file Tệp đã tải lên
     * @return mixed
     */
    public static function uploadHandle(array $file)
    {
        if (empty($file['name'])) {
            return false;
        }

        $result = self::pluginHandle()->trigger($hasUploaded)->uploadHandle($file);
        if ($hasUploaded) {
            return $result;
        }

        $ext = self::getSafeName($file['name']);

        if (!self::checkFileType($ext)) {
            return false;
        }

        $date = new Date();
        $path = Common::url(
            defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__
        ) . '/' . $date->year . '/' . $date->month;

        // Tạo thư mục tải lên
        if (!is_dir($path)) {
            if (!self::makeUploadDir($path)) {
                return false;
            }
        }

        // Lấy tên tập tin
        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $path . '/' . $fileName;

        if (isset($file['tmp_name'])) {
            // Tệp tải lên di động
            if (!@move_uploaded_file($file['tmp_name'], $path)) {
                return false;
            }
        } elseif (isset($file['bytes'])) {
            // Viết trực tiếp vào tập tin
            if (!file_put_contents($path, $file['bytes'])) {
                return false;
            }
        } elseif (isset($file['bits'])) {
            // Viết trực tiếp vào tập tin
            if (!file_put_contents($path, $file['bits'])) {
                return false;
            }
        } else {
            return false;
        }

        if (!isset($file['size'])) {
            $file['size'] = filesize($path);
        }

        // Trả về đường dẫn lưu trữ tương đối
        return [
            'name' => $file['name'],
            'path' => (defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR)
                . '/' . $date->year . '/' . $date->month . '/' . $fileName,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => Common::mimeContentType($path)
        ];
    }

    /**
     * Kiểm tra tên tập tin
     *
     * @access private
     * @param string $ext sự mở rộng
     * @return boolean
     */
    public static function checkFileType(string $ext): bool
    {
        $options = Options::alloc();
        return in_array($ext, $options->allowedAttachmentTypes);
    }
}
