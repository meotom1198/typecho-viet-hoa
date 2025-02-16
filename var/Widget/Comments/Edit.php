<?php

namespace Widget\Comments;

use Typecho\Db\Exception;
use Widget\Base\Comments;
use Widget\ActionInterface;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần chỉnh sửa bình luận
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Comments implements ActionInterface
{
    /**
     * Đánh dấu để xem xét
     */
    public function waitingComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $updateRows = 0;

        foreach ($comments as $comment) {
            if ($this->mark($comment, 'waiting')) {
                $updateRows++;
            }
        }

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()
            ->set(
                $updateRows > 0 ? _t('Bình luận đang chờ Admin duyệt!') : _t('Không có bình luận nào cần duyệt!'),
                $updateRows > 0 ? 'success' : 'notice'
            );

        /** Quay lại trang web ban đầu */
        $this->response->goBack();
    }

    /**
     * Đánh dấu trạng thái bình luận
     *
     * @param integer $coid Nhận xét khóa chính
     * @param string $status tình trạng
     * @return boolean
     * @throws Exception
     */
    private function mark($coid, $status)
    {
        $comment = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

        if ($comment && $this->commentIsWriteable()) {
            /** Thêm giao diện plug-in chỉnh sửa bình luận */
            self::pluginHandle()->mark($comment, $this, $status);

            /** Không cần cập nhật */
            if ($status == $comment['status']) {
                return false;
            }

            /** Cập nhật nhận xét */
            $this->db->query($this->db->update('table.comments')
                ->rows(['status' => $status])->where('coid = ?', $coid));

            /** Cập nhật số lượng bình luận về nội dung liên quan */
            if ('approved' == $comment['status'] && 'approved' != $status) {
                $this->db->query($this->db->update('table.contents')
                    ->expression('commentsNum', 'commentsNum - 1')
                    ->where('cid = ? AND commentsNum > 0', $comment['cid']));
            } elseif ('approved' != $comment['status'] && 'approved' == $status) {
                $this->db->query($this->db->update('table.contents')
                    ->expression('commentsNum', 'commentsNum + 1')->where('cid = ?', $comment['cid']));
            }

            return true;
        }

        return false;
    }

    /**
     * Đánh dấu là thư rác
     *
     * @throws Exception
     */
    public function spamComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $updateRows = 0;

        foreach ($comments as $comment) {
            if ($this->mark($comment, 'spam')) {
                $updateRows++;
            }
        }

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()
            ->set(
                $updateRows > 0 ? _t('Bình luận đã bị đánh dấu là spam!') : _t('Không có bình luận nào bị đánh dấu là spam!'),
                $updateRows > 0 ? 'success' : 'notice'
            );

        /** Quay lại trang web ban đầu */
        $this->response->goBack();
    }

    /**
     * Đánh dấu để hiển thị
     *
     * @throws Exception
     */
    public function approvedComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $updateRows = 0;

        foreach ($comments as $comment) {
            if ($this->mark($comment, 'approved')) {
                $updateRows++;
            }
        }

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()
            ->set(
                $updateRows > 0 ? _t('Bình luận đã được duyệt!') : _t('Không có bình luận nào được duyệt!'),
                $updateRows > 0 ? 'success' : 'notice'
            );

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * Xóa bình luận
     *
     * @throws Exception
     */
    public function deleteComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $deleteRows = 0;

        foreach ($comments as $coid) {
            $comment = $this->db->fetchRow($this->select()
                ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

            if ($comment && $this->commentIsWriteable()) {
                self::pluginHandle()->delete($comment, $this);

                /** Xóa bình luận */
                $this->db->query($this->db->delete('table.comments')->where('coid = ?', $coid));

                /** Cập nhật số lượng bình luận về nội dung liên quan */
                if ('approved' == $comment['status']) {
                    $this->db->query($this->db->update('table.contents')
                        ->expression('commentsNum', 'commentsNum - 1')->where('cid = ?', $comment['cid']));
                }

                self::pluginHandle()->finishDelete($comment, $this);

                $deleteRows++;
            }
        }

        if ($this->request->isAjax()) {
            if ($deleteRows > 0) {
                $this->response->throwJson([
                    'success' => 1,
                    'message' => _t('Xóa bình luận thành công!')
                ]);
            } else {
                $this->response->throwJson([
                    'success' => 0,
                    'message' => _t('Không thể xóa bình luận!')
                ]);
            }
        } else {
            /** Đặt thông tin nhắc nhở */
            Notice::alloc()
                ->set(
                    $deleteRows > 0 ? _t('Bình luận đã bị xóa!') : _t('Không có bình luận nào bị xóa!'),
                    $deleteRows > 0 ? 'success' : 'notice'
                );

            /** Quay lại trang web ban đầu */
            $this->response->goBack();
        }
    }

    /**
     * Xóa tất cả bình luận spam
     *
     * @throws Exception
     */
    public function deleteSpamComment()
    {
        $deleteQuery = $this->db->delete('table.comments')->where('status = ?', 'spam');
        if (!$this->request->__typecho_all_comments || !$this->user->pass('editor', true)) {
            $deleteQuery->where('ownerId = ?', $this->user->uid);
        }

        if (isset($this->request->cid)) {
            $deleteQuery->where('cid = ?', $this->request->cid);
        }

        $deleteRows = $this->db->query($deleteQuery);

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()->set(
            $deleteRows > 0 ? _t('Tất cả các bình luận spam đã bị xóa!') : _t('Không có bình luận spam nào bị xóa!'),
            $deleteRows > 0 ? 'success' : 'notice'
        );

        /** Không có bình luận spam nào bị xóa */
        $this->response->goBack();
    }

    /**
     * Nhận nhận xét có thể chỉnh sửa
     *
     * @throws Exception
     */
    public function getComment()
    {
        $coid = $this->request->filter('int')->coid;
        $comment = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

        if ($comment && $this->commentIsWriteable()) {
            $this->response->throwJson([
                'success' => 1,
                'comment' => $comment
            ]);
        } else {
            $this->response->throwJson([
                'success' => 0,
                'message' => _t('Không thể nhận được nhận xét!')
            ]);
        }
    }

    /**
     * Bình luận biên tập
     *
     * @return bool
     * @throws Exception
     */
    public function editComment(): bool
    {
        $coid = $this->request->filter('int')->coid;
        $commentSelect = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

        if ($commentSelect && $this->commentIsWriteable()) {
            $comment['text'] = $this->request->text;
            $comment['author'] = $this->request->filter('strip_tags', 'trim', 'xss')->author;
            $comment['mail'] = $this->request->filter('strip_tags', 'trim', 'xss')->mail;
            $comment['url'] = $this->request->filter('url')->url;

            if ($this->request->is('created')) {
                $comment['created'] = $this->request->filter('int')->created;
            }

            /** Giao diện plug-in bình luận */
            $comment = self::pluginHandle()->edit($comment, $this);

            /** Cập nhật đánh giá */
            $this->update($comment, $this->db->sql()->where('coid = ?', $coid));

            $updatedComment = $this->db->fetchRow($this->select()
                ->where('coid = ?', $coid)->limit(1), [$this, 'push']);
            $updatedComment['content'] = $this->content;

            /** Giao diện plug-in bình luận */
            self::pluginHandle()->finishEdit($this);

            $this->response->throwJson([
                'success' => 1,
                'comment' => $updatedComment
            ]);
        }

        $this->response->throwJson([
            'success' => 0,
            'message' => _t('Không thể sửa bình luận!')
        ]);
    }

    /**
     * Trả lời bình luận
     *
     * @throws Exception
     */
    public function replyComment()
    {
        $coid = $this->request->filter('int')->coid;
        $commentSelect = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), [$this, 'push']);

        if ($commentSelect && $this->commentIsWriteable()) {
            $comment = [
                'cid'      => $commentSelect['cid'],
                'created'  => $this->options->time,
                'agent'    => $this->request->getAgent(),
                'ip'       => $this->request->getIp(),
                'ownerId'  => $commentSelect['ownerId'],
                'authorId' => $this->user->uid,
                'type'     => 'comment',
                'author'   => $this->user->screenName,
                'mail'     => $this->user->mail,
                'url'      => $this->user->url,
                'parent'   => $coid,
                'text'     => $this->request->text,
                'status'   => 'approved'
            ];

            /** Giao diện plug-in bình luận */
            self::pluginHandle()->comment($comment, $this);

            /** Trả lời bình luận */
            $commentId = $this->insert($comment);

            $insertComment = $this->db->fetchRow($this->select()
                ->where('coid = ?', $commentId)->limit(1), [$this, 'push']);
            $insertComment['content'] = $this->content;

            /** Giao diện hoàn thành bình luận */
            self::pluginHandle()->finishComment($this);

            $this->response->throwJson([
                'success' => 1,
                'comment' => $insertComment
            ]);
        }

        $this->response->throwJson([
            'success' => 0,
            'message' => _t('Trả lời bình luận không thành công!')
        ]);
    }

    /**
     * hàm khởi tạo
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('contributor');
        $this->security->protect();
        $this->on($this->request->is('do=waiting'))->waitingComment();
        $this->on($this->request->is('do=spam'))->spamComment();
        $this->on($this->request->is('do=approved'))->approvedComment();
        $this->on($this->request->is('do=delete'))->deleteComment();
        $this->on($this->request->is('do=delete-spam'))->deleteSpamComment();
        $this->on($this->request->is('do=get&coid'))->getComment();
        $this->on($this->request->is('do=edit&coid'))->editComment();
        $this->on($this->request->is('do=reply&coid'))->replyComment();

        $this->response->redirect($this->options->adminUrl);
    }
}
