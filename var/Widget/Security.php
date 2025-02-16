<?php

namespace Widget;

use Typecho\Common;
use Typecho\Response;
use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần tùy chọn bảo mật
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2014 Typecho team (http://typecho.org)
 * @license GNU General Public License 2.0
 */
class Security extends Base
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var boolean
     */
    private $enabled = true;

    /**
     * @param int $components
     */
    public function initComponents(int &$components)
    {
        $components = self::INIT_OPTIONS | self::INIT_USER;
    }

    /**
     * hàm khởi tạo
     */
    public function execute()
    {
        $this->token = $this->options->secret;
        if ($this->user->hasLogin()) {
            $this->token .= '&' . $this->user->authCode . '&' . $this->user->uid;
        }
    }

    /**
     * @param bool $enabled
     */
    public function enable(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * Bảo vệ dữ liệu đã gửi
     */
    public function protect()
    {
        if ($this->enabled && $this->request->get('_') != $this->getToken($this->request->getReferer())) {
            $this->response->goBack();
        }
    }

    /**
     * Bảo vệ dữ liệu đã gửi
     *
     * @param string|null $suffix hậu tố
     * @return string
     */
    public function getToken(?string $suffix): string
    {
        return md5($this->token . '&' . $suffix);
    }

    /**
     * Nhận đường dẫn định tuyến tuyệt đối
     *
     * @param string|null $path
     * @return string
     */
    public function getRootUrl(?string $path): string
    {
        return Common::url($this->getTokenUrl($path), $this->options->rootUrl);
    }

    /**
     * Tạo đường dẫn bằng mã thông báo
     *
     * @param $path
     * @param string|null $url
     * @return string
     */
    public function getTokenUrl($path, ?string $url = null): string
    {
        $parts = parse_url($path);
        $params = [];

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
        }

        $params['_'] = $this->getToken($url ?: $this->request->getRequestUrl());
        $parts['query'] = http_build_query($params);

        return Common::buildUrl($parts);
    }

    /**
     * Đường dẫn bảo mật nền đầu ra
     *
     * @param $path
     */
    public function adminUrl($path)
    {
        echo $this->getAdminUrl($path);
    }

    /**
     * Nhận đường dẫn phụ trợ an toàn
     *
     * @param string $path
     * @return string
     */
    public function getAdminUrl(string $path): string
    {
        return Common::url($this->getTokenUrl($path), $this->options->adminUrl);
    }

    /**
     * Đầu ra đường dẫn định tuyến an toàn
     *
     * @param $path
     */
    public function index($path)
    {
        echo $this->getIndex($path);
    }

    /**
     * Nhận đường dẫn định tuyến an toàn
     *
     * @param $path
     * @return string
     */
    public function getIndex($path): string
    {
        return Common::url($this->getTokenUrl($path), $this->options->index);
    }
}
 
