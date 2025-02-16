<?php

namespace Widget;

use Typecho\Http\Client;
use Typecho\Widget\Exception;
use Widget\Base\Options as BaseOptions;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Các thành phần gọi không đồng bộ
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Ajax extends BaseOptions implements ActionInterface
{
    /**
     * Trả về các yêu cầu viết lại đã được xác minh
     *
     * @access public
     * @return void
     */
    public function remoteCallback()
    {
        if ($this->options->generator == $this->request->getAgent()) {
            echo 'OK';
        }
    }

    /**
     * Tải phiên bản mới nhất
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function checkVersion()
    {
        $this->user->pass('editor');
        $client = Client::get();
        if ($client) {
            $client->setHeader('User-Agent', $this->options->generator)
                ->setTimeout(10);
            $result = ['available' => 0];

            try {
                $client->send('https://typecho.org/version.json');

                /** Nội dung phù hợp */
                $response = $client->getResponseBody();
                $json = json_decode($response, true);

                if (!empty($json)) {
                    $version = $this->options->version;

                    if (
                        isset($json['release'])
                        && preg_match("/^[0-9\.]+$/", $json['release'])
                        && version_compare($json['release'], $version, '>')
                    ) {
                        $result = [
                            'available' => 1,
                            'latest'    => $json['release'],
                            'current'   => $version,
                            'link'      => 'https://typecho.org/download'
                        ];
                    }
                }
            } catch (\Exception $e) {
                // do nothing
            }

            $this->response->throwJson($result);
        }

        throw new Exception(_t('Quyền truy cập bị cấm!'), 403);
    }

    /**
     * Proxy yêu cầu từ xa
     *
     * @throws Exception
     * @throws Client\Exception|\Typecho\Db\Exception
     */
    public function feed()
    {
        $this->user->pass('subscriber');
        $client = Client::get();
        if ($client) {
            $client->setHeader('User-Agent', $this->options->generator)
                ->setTimeout(10)
                ->send('https://typecho.org/feed/');

            /** Nội dung phù hợp */
            $response = $client->getResponseBody();
            preg_match_all(
                "/<item>\s*<title>([^>]*)<\/title>\s*<link>([^>]*)<\/link>\s*<guid>[^>]*<\/guid>\s*<pubDate>([^>]*)<\/pubDate>/is",
                $response,
                $matches
            );

            $data = [];

            if ($matches) {
                foreach ($matches[0] as $key => $val) {
                    $data[] = [
                        'title' => $matches[1][$key],
                        'link'  => $matches[2][$key],
                        'date'  => date('n.j', strtotime($matches[3][$key]))
                    ];

                    if ($key > 8) {
                        break;
                    }
                }
            }

            $this->response->throwJson($data);
        }

        throw new Exception(_t('Quyền truy cập bị cấm!'), 403);
    }

    /**
     * Kích thước trình chỉnh sửa tùy chỉnh
     *
     * @throws \Typecho\Db\Exception|Exception
     */
    public function editorResize()
    {
        $this->user->pass('contributor');
        if (
            $this->db->fetchObject($this->db->select(['COUNT(*)' => 'num'])
                ->from('table.options')->where('name = ? AND user = ?', 'editorSize', $this->user->uid))->num > 0
        ) {
            parent::update(
                ['value' => $this->request->size],
                $this->db->sql()->where('name = ? AND user = ?', 'editorSize', $this->user->uid)
            );
        } else {
            parent::insert([
                'name'  => 'editorSize',
                'value' => $this->request->size,
                'user'  => $this->user->uid
            ]);
        }
    }

    /**
     * Mục nhập yêu cầu không đồng bộ
     *
     * @access public
     * @return void
     */
    public function action()
    {
        if (!$this->request->isAjax()) {
            $this->response->goBack();
        }

        $this->on($this->request->is('do=remoteCallback'))->remoteCallback();
        $this->on($this->request->is('do=feed'))->feed();
        $this->on($this->request->is('do=checkVersion'))->checkVersion();
        $this->on($this->request->is('do=editorResize'))->editorResize();
    }
}
