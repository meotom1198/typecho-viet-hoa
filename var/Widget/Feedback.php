<?php

namespace Widget;

use Typecho\Common;
use Typecho\Cookie;
use Typecho\Db;
use Typecho\Router;
use Typecho\Validate;
use Typecho\Widget\Exception;
use Widget\Base\Comments;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * thành phần gửi phản hồi
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Feedback extends Comments implements ActionInterface
{
    /**
     * đối tượng nội dung
     *
     * @access private
     * @var Archive
     */
    private $content;

    /**
     * Phát hiện bảo vệ người dùng đã đăng ký
     *
     * @param string $userName tên người dùng
     * @return bool
     * @throws \Typecho\Db\Exception
     */
    public function requireUserLogin(string $userName): bool
    {
        if ($this->user->hasLogin() && $this->user->screenName != $userName) {
            /** Tên người dùng hiện tại không khớp với người gửi */
            return false;
        } elseif (
            !$this->user->hasLogin() && $this->db->fetchRow($this->db->select('uid')
                ->from('table.users')->where('screenName = ? OR name = ?', $userName, $userName)->limit(1))
        ) {
            /** Tên người dùng này đã được đăng ký */
            return false;
        }

        return true;
    }

    /**
     * hàm khởi tạo
     *
     * @throws \Exception
     */
    public function action()
    {
        /** phương pháp gọi lại */
        $callback = $this->request->type;
        $this->content = Router::match($this->request->permalink);

        /** Xác định xem nội dung có tồn tại hay không */
        if (
            $this->content instanceof Archive &&
            $this->content->have() && $this->content->is('single') &&
            in_array($callback, ['comment', 'trackback'])
        ) {

            /** Nếu bài viết không cho phép phản hồi */
            if ('comment' == $callback) {
                /** Bình luận đã đóng */
                if (!$this->content->allow('comment')) {
                    throw new Exception(_t('Xin lỗi, phản hồi cho nội dung này bị cấm!'), 403);
                }

                /** Kiểm tra nguồn */
                if ($this->options->commentsCheckReferer && 'false' != $this->parameter->checkReferer) {
                    $referer = $this->request->getReferer();

                    if (empty($referer)) {
                        throw new Exception(_t('Lỗi trang nguồn bình luận!'), 403);
                    }

                    $refererPart = parse_url($referer);
                    $currentPart = parse_url($this->content->permalink);

                    if (
                        $refererPart['host'] != $currentPart['host'] ||
                        0 !== strpos($refererPart['path'], $currentPart['path'])
                    ) {
                        // Hỗ trợ trang chủ tùy chỉnh
                        if ('page:' . $this->content->cid == $this->options->frontPage) {
                            $currentPart = parse_url(rtrim($this->options->siteUrl, '/') . '/');

                            if (
                                $refererPart['host'] != $currentPart['host'] ||
                                0 !== strpos($refererPart['path'], $currentPart['path'])
                            ) {
                                throw new Exception(_t('Lỗi trang nguồn bình luận!'), 403);
                            }
                        } else {
                            throw new Exception(_t('Lỗi trang nguồn bình luận!'), 403);
                        }
                    }
                }

                /** Kiểm tra khoảng thời gian nhận xét IP */
                if (
                    !$this->user->pass('editor', true) && $this->content->authorId != $this->user->uid &&
                    $this->options->commentsPostIntervalEnable
                ) {
                    $latestComment = $this->db->fetchRow($this->db->select('created')->from('table.comments')
                        ->where('cid = ? AND ip = ?', $this->content->cid, $this->request->getIp())
                        ->order('created', Db::SORT_DESC)
                        ->limit(1));

                    if (
                        $latestComment && ($this->options->time - $latestComment['created'] > 0 &&
                            $this->options->time - $latestComment['created'] < $this->options->commentsPostInterval)
                    ) {
                        throw new Exception(_t('Xin lỗi, bạn bình luận quá nhanh, vui lòng thử lại sau..'), 403);
                    }
                }
            }

            /** Nếu bài viết không cho phép trích dẫn */
            if ('trackback' == $callback && !$this->content->allow('ping')) {
                throw new Exception(_t('Xin lỗi, trích dẫn nội dung này bị cấm!'), 403);
            }

            /** chức năng gọi */
            $this->$callback();
        } else {
            throw new Exception(_t('Không tìm thấy nội dung!'), 404);
        }
    }

    /**
     * Chức năng xử lý bình luận
     *
     * @throws \Exception
     */
    private function comment()
    {
        // Bảo vệ bằng mô-đun bảo mật
        $this->security->enable($this->options->commentsAntiSpam);
        $this->security->protect();

        $comment = [
            'cid' => $this->content->cid,
            'created' => $this->options->time,
            'agent' => $this->request->getAgent(),
            'ip' => $this->request->getIp(),
            'ownerId' => $this->content->author->uid,
            'type' => 'comment',
            'status' => !$this->content->allow('edit')
                && $this->options->commentsRequireModeration ? 'waiting' : 'approved'
        ];

        /** Xác định nút cha */
        if ($parentId = $this->request->filter('int')->get('parent')) {
            if (
                $this->options->commentsThreaded
                && ($parent = $this->db->fetchRow($this->db->select('coid', 'cid')->from('table.comments')
                    ->where('coid = ?', $parentId))) && $this->content->cid == $parent['cid']
            ) {
                $comment['parent'] = $parentId;
            } else {
                throw new Exception(_t('Bình luận không tồn tại!'));
            }
        }

        // Kiểm tra định dạng
        $validator = new Validate();
        $validator->addRule('author', 'required', _t('Bản phải điền tên'));
        $validator->addRule('author', 'xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong tên tài khoản của bạn!'));
        $validator->addRule('author', [$this, 'requireUserLogin'], _t('Tên tài khoản bạn đang sử dụng đã được đăng ký, vui lòng đăng nhập hoặc thử lại!'));
        $validator->addRule('author', 'maxLength', _t('Tên người dùng có thể chứa tối đa 150 ký tự!'), 150);

        if ($this->options->commentsRequireMail && !$this->user->hasLogin()) {
            $validator->addRule('mail', 'required', _t('Bạn phải điền địa chỉ Email!'));
        }

        $validator->addRule('mail', 'email', _t('Địa chỉ email không hợp lệ!'));
        $validator->addRule('mail', 'maxLength', _t('Địa chỉ email có thể chứa tối đa 150 ký tự!'), 150);

        if ($this->options->commentsRequireUrl && !$this->user->hasLogin()) {
            $validator->addRule('url', 'required', _t('Bạn phải điền trang Facebook của bạn'));
        }
        $validator->addRule('url', 'url', _t('Địa chỉ trang Facebook bạn vừa điền không đúng định dạng!'));
        $validator->addRule('url', 'maxLength', _t('Địa chỉ trang Facebook có thể chứa tối đa 255 ký tự!'), 255);

        $validator->addRule('text', 'required', _t('Nội dung bình luận là bắt buộc!'));

        $comment['text'] = $this->request->text;

        /** Đối với khách truy cập ẩn danh nói chung, dữ liệu người dùng sẽ được lưu giữ trong một tháng */
        if (!$this->user->hasLogin()) {
            /** Anti-XSS */
            $comment['author'] = $this->request->filter('trim')->author;
            $comment['mail'] = $this->request->filter('trim')->mail;
            $comment['url'] = $this->request->filter('trim', 'url')->url;

            /** Sửa url do người dùng gửi */
            if (!empty($comment['url'])) {
                $urlParams = parse_url($comment['url']);
                if (!isset($urlParams['scheme'])) {
                    $comment['url'] = 'http://' . $comment['url'];
                }
            }

            $expire = 30 * 24 * 3600;
            Cookie::set('__typecho_remember_author', $comment['author'], $expire);
            Cookie::set('__typecho_remember_mail', $comment['mail'], $expire);
            Cookie::set('__typecho_remember_url', $comment['url'], $expire);
        } else {
            $comment['author'] = $this->user->screenName;
            $comment['mail'] = $this->user->mail;
            $comment['url'] = $this->user->url;

            /** Ghi lại ID của người dùng đã đăng nhập */
            $comment['authorId'] = $this->user->uid;
        }

        /** Người bình luận phải có những bình luận trước đó đã vượt qua quá trình xem xét */
        if (!$this->options->commentsRequireModeration && $this->options->commentsWhitelist) {
            if (
                $this->size(
                    $this->select()->where(
                        'author = ? AND mail = ? AND status = ?',
                        $comment['author'],
                        $comment['mail'],
                        'approved'
                    )
                )
            ) {
                $comment['status'] = 'approved';
            } else {
                $comment['status'] = 'waiting';
            }
        }

        if ($error = $validator->run($comment)) {
            /** ghi lại văn bản */
            Cookie::set('__typecho_remember_text', $comment['text']);
            throw new Exception(implode("\n", $error));
        }

        /** Tạo bộ lọc */
        try {
            $comment = self::pluginHandle()->comment($comment, $this->content);
        } catch (\Typecho\Exception $e) {
            Cookie::set('__typecho_remember_text', $comment['text']);
            throw $e;
        }

        /** Thêm nhận xét */
        $commentId = $this->insert($comment);
        Cookie::delete('__typecho_remember_text');
        $this->db->fetchRow($this->select()->where('coid = ?', $commentId)
            ->limit(1), [$this, 'push']);

        /** Giao diện hoàn thành bình luận */
        self::pluginHandle()->finishComment($this);

        $this->response->goBack('#' . $this->theId);
    }

    /**
     * Hàm xử lý tham chiếu
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    private function trackback()
    {
        /** Nếu không phải là phương thức POST */
        if (!$this->request->isPost() || $this->request->getReferer()) {
            $this->response->redirect($this->content->permalink);
        }

        /** Nếu trackback với địa chỉ IP hiện tại của thư rác đã tồn tại trong thư viện, nó sẽ bị từ chối trực tiếp. */
        if (
            $this->size($this->select()
                ->where('status = ? AND ip = ?', 'spam', $this->request->getIp())) > 0
        ) {
            /** Sử dụng 404 để báo cho bot */
            throw new Exception(_t('Không tìm thấy nội dung!'), 404);
        }

        $trackback = [
            'cid' => $this->content->cid,
            'created' => $this->options->time,
            'agent' => $this->request->getAgent(),
            'ip' => $this->request->getIp(),
            'ownerId' => $this->content->author->uid,
            'type' => 'trackback',
            'status' => $this->options->commentsRequireModeration ? 'waiting' : 'approved'
        ];

        $trackback['author'] = $this->request->filter('trim')->blog_name;
        $trackback['url'] = $this->request->filter('trim', 'url')->url;
        $trackback['text'] = $this->request->excerpt;

        // Kiểm tra định dạng
        $validator = new Validate();
        $validator->addRule('url', 'required', 'We require all Trackbacks to provide an url.')
            ->addRule('url', 'url', 'Your url is not valid.')
            ->addRule('url', 'maxLength', 'Your url is not valid.', 255)
            ->addRule('text', 'required', 'We require all Trackbacks to provide an excerption.')
            ->addRule('author', 'required', 'We require all Trackbacks to provide an blog name.')
            ->addRule('author', 'xssCheck', 'Your blog name is not valid.')
            ->addRule('author', 'maxLength', 'Your blog name is not valid.', 150);

        $validator->setBreak();
        if ($error = $validator->run($trackback)) {
            $message = ['success' => 1, 'message' => current($error)];
            $this->response->throwXml($message);
        }

        /** cắt ngắn chiều dài */
        $trackback['text'] = Common::subStr($trackback['text'], 0, 100, '[...]');

        /** Nếu một URL trùng lặp đã tồn tại trong thư viện thì nó sẽ bị từ chối trực tiếp. */
        if (
            $this->size($this->select()
                ->where('cid = ? AND url = ? AND type <> ?', $this->content->cid, $trackback['url'], 'comment')) > 0
        ) {
            /** Sử dụng 403 để báo cho bot */
            throw new Exception(_t('Nghiêm cấm gửi bài trùng lặp!'), 403);
        }

        /** Tạo bộ lọc */
        $trackback = self::pluginHandle()->trackback($trackback, $this->content);

        /** Thêm trích dẫn */
        $this->insert($trackback);

        /** Giao diện hoàn thành bình luận */
        self::pluginHandle()->finishTrackback($this);

        /** Trả về đúng */
        $this->response->throwXml(['success' => 0, 'message' => 'Trackback has registered.']);
    }
}
