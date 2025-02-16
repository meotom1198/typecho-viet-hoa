<?php

namespace Widget\Options;

use Typecho\Db\Exception;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần cài đặt bình luận
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Discussion extends Options implements ActionInterface
{
    /**
     * Thực hiện hành động cập nhật
     *
     * @throws Exception
     */
    public function updateDiscussionSettings()
    {
        /** Xác minh định dạng */
        if ($this->form()->validate()) {
            $this->response->goBack();
        }

        $settings = $this->request->from(
            'commentDateFormat',
            'commentsListSize',
            'commentsPageSize',
            'commentsPageDisplay',
            'commentsAvatar',
            'commentsOrder',
            'commentsMaxNestingLevels',
            'commentsUrlNofollow',
            'commentsPostTimeout',
            'commentsUniqueIpInterval',
            'commentsWhitelist',
            'commentsRequireMail',
            'commentsAvatarRating',
            'commentsPostTimeout',
            'commentsPostInterval',
            'commentsRequireModeration',
            'commentsRequireURL',
            'commentsHTMLTagAllowed',
            'commentsStopWords',
            'commentsIpBlackList'
        );
        $settings['commentsShow'] = $this->request->getArray('commentsShow');
        $settings['commentsPost'] = $this->request->getArray('commentsPost');

        $settings['commentsShowCommentOnly'] = $this->isEnableByCheckbox(
            $settings['commentsShow'],
            'commentsShowCommentOnly'
        );
        $settings['commentsMarkdown'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsMarkdown');
        $settings['commentsShowUrl'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsShowUrl');
        $settings['commentsUrlNofollow'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsUrlNofollow');
        $settings['commentsAvatar'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsAvatar');
        $settings['commentsPageBreak'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsPageBreak');
        $settings['commentsThreaded'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsThreaded');

        $settings['commentsPageSize'] = intval($settings['commentsPageSize']);
        $settings['commentsMaxNestingLevels'] = min(7, max(2, intval($settings['commentsMaxNestingLevels'])));
        $settings['commentsPageDisplay'] = ('first' == $settings['commentsPageDisplay']) ? 'first' : 'last';
        $settings['commentsOrder'] = ('DESC' == $settings['commentsOrder']) ? 'DESC' : 'ASC';
        $settings['commentsAvatarRating'] = in_array($settings['commentsAvatarRating'], ['G', 'PG', 'R', 'X'])
            ? $settings['commentsAvatarRating'] : 'G';

        $settings['commentsRequireModeration'] = $this->isEnableByCheckbox(
            $settings['commentsPost'],
            'commentsRequireModeration'
        );
        $settings['commentsWhitelist'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsWhitelist');
        $settings['commentsRequireMail'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsRequireMail');
        $settings['commentsRequireURL'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsRequireURL');
        $settings['commentsCheckReferer'] = $this->isEnableByCheckbox(
            $settings['commentsPost'],
            'commentsCheckReferer'
        );
        $settings['commentsAntiSpam'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsAntiSpam');
        $settings['commentsAutoClose'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsAutoClose');
        $settings['commentsPostIntervalEnable'] = $this->isEnableByCheckbox(
            $settings['commentsPost'],
            'commentsPostIntervalEnable'
        );

        $settings['commentsPostTimeout'] = intval($settings['commentsPostTimeout']) * 24 * 3600;
        $settings['commentsPostInterval'] = round($settings['commentsPostInterval'], 1) * 60;

        unset($settings['commentsShow']);
        unset($settings['commentsPost']);

        foreach ($settings as $name => $value) {
            $this->update(['value' => $value], $this->db->sql()->where('name = ?', $name));
        }

        Notice::alloc()->set(_t("Đã lưu cài đặt!"), 'success');
        $this->response->goBack();
    }

    /**
     * Cấu trúc biểu mẫu đầu ra
     *
     * @return Form
     */
    public function form(): Form
    {
        /** Xây dựng bảng */
        $form = new Form($this->security->getIndex('/action/options-discussion'), Form::POST_METHOD);

        /** Định dạng ngày bình luận */
        $commentDateFormat = new Form\Element\Text(
            'commentDateFormat',
            null,
            $this->options->commentDateFormat,
            _t('Định dạng ngày của bình luận'),
            _t('Đây là định dạng mặc định. Khi bạn gọi phương thức hiển thị ngày bình luận trong mẫu, nếu không có định dạng ngày nào được chỉ định thì đầu ra sẽ ở định dạng này.') . '<br />'
            . _t('Để biết các phương pháp viết cụ thể, vui lòng tham khảo <a href="https://www.php.net/manual/zh/function.date.php">Phương pháp viết định dạng ngày tháng PHP</a>.')
        );
        $commentDateFormat->input->setAttribute('class', 'w-40 mono');
        $form->addInput($commentDateFormat);

        /** Số lượng danh sách bình luận */
        $commentsListSize = new Form\Element\Text(
            'commentsListSize',
            null,
            $this->options->commentsListSize,
            _t('Số lượng bình luận'),
            _t('Con số này chỉ định số lượng bình luận sẽ hiển thị trong thanh bên.')
        );
        $commentsListSize->input->setAttribute('class', 'w-20');
        $form->addInput($commentsListSize->addRule('isInteger', _t('Nhập một số')));

        $commentsShowOptions = [
            'commentsShowCommentOnly' => _t('Chỉ hiển thị bình luận, không hiển thị Pingback và Trackback'),
            'commentsMarkdown'        => _t('Sử dụng Markdown trong bình luận!'),
            'commentsShowUrl'         => _t('Tên của người bình luận sẽ tự động được hiển thị kèm theo liên kết đến trang chủ cá nhân của người đó.'),
            'commentsUrlNofollow'     => _t('Sử dụng <a href="https://en.wikipedia.org/wiki/Nofollow">thuộc tính nofollow</a> trên các liên kết hồ sơ người bình luận'),
            'commentsAvatar'          => _t('Bật ảnh đại diện <a href="https://gradatar.com">Gravatar</a>, hiển thị hình đại diện có xếp hạng tối đa %s',
                '</label><select id="commentsShow-commentsAvatarRating" name="commentsAvatarRating">
            <option value="G"' . ('G' == $this->options->commentsAvatarRating ? ' selected="true"' : '') . '>' . _t('G - Bình thường') . '</option>
            <option value="PG"' . ('PG' == $this->options->commentsAvatarRating ? ' selected="true"' : '') . '>' . _t('PG - 13+') . '</option>
            <option value="R"' . ('R' == $this->options->commentsAvatarRating ? ' selected="true"' : '') . '>' . _t('R - Người lớn 17+') . '</option>
            <option value="X"' . ('X' == $this->options->commentsAvatarRating ? ' selected="true"' : '') . '>' . _t('X - Bị hạn chế') . '</option></select>
            <label for="commentsShow-commentsAvatarRating">'),
            'commentsPageBreak'       => _t('Bật phân trang và hiển thị %s đánh giá trên mỗi trang, hiển thị %s làm mặc định khi liệt kê!',
                '</label><input type="text" value="' . $this->options->commentsPageSize
                . '" class="text num text-s" id="commentsShow-commentsPageSize" name="commentsPageSize" /><label for="commentsShow-commentsPageSize">',
                '</label><select id="commentsShow-commentsPageDisplay" name="commentsPageDisplay">
            <option value="first"' . ('first' == $this->options->commentsPageDisplay ? ' selected="true"' : '') . '>' . _t('Trang đầu tiên') . '</option>
            <option value="last"' . ('last' == $this->options->commentsPageDisplay ? ' selected="true"' : '') . '>' . _t('Trang cuối cùng') . '</option></select>'
                . '<label for="commentsShow-commentsPageDisplay">'),
            'commentsThreaded'        => _t('Bật trả lời nhận xét, với lớp %s là số lượng lớp trả lời tối đa cho mỗi nhận xét',
                    '</label><input name="commentsMaxNestingLevels" type="text" class="text num text-s" value="' . $this->options->commentsMaxNestingLevels . '" id="commentsShow-commentsMaxNestingLevels" />
            <label for="commentsShow-commentsMaxNestingLevels">') . '</label></span><span class="multiline">'
                . _t('Đưa nhận xét của %s lên phía trước', '<select id="commentsShow-commentsOrder" name="commentsOrder">
            <option value="DESC"' . ('DESC' == $this->options->commentsOrder ? ' selected="true"' : '') . '>' . _t('Mới hơn') . '</option>
            <option value="ASC"' . ('ASC' == $this->options->commentsOrder ? ' selected="true"' : '') . '>' . _t('Cũ hơn') . '</option></select><label for="commentsShow-commentsOrder">')
        ];

        $commentsShowOptionsValue = [];
        if ($this->options->commentsShowCommentOnly) {
            $commentsShowOptionsValue[] = 'commentsShowCommentOnly';
        }

        if ($this->options->commentsMarkdown) {
            $commentsShowOptionsValue[] = 'commentsMarkdown';
        }

        if ($this->options->commentsShowUrl) {
            $commentsShowOptionsValue[] = 'commentsShowUrl';
        }

        if ($this->options->commentsUrlNofollow) {
            $commentsShowOptionsValue[] = 'commentsUrlNofollow';
        }

        if ($this->options->commentsAvatar) {
            $commentsShowOptionsValue[] = 'commentsAvatar';
        }

        if ($this->options->commentsPageBreak) {
            $commentsShowOptionsValue[] = 'commentsPageBreak';
        }

        if ($this->options->commentsThreaded) {
            $commentsShowOptionsValue[] = 'commentsThreaded';
        }

        $commentsShow = new Form\Element\Checkbox(
            'commentsShow',
            $commentsShowOptions,
            $commentsShowOptionsValue,
            _t('Bình luận hiển thị')
        );
        $form->addInput($commentsShow->multiMode());

        /** Gửi bình luận */
        $commentsPostOptions = [
            'commentsRequireModeration'  => _t('Các bình luận phải được kiểm duyệt'),
            'commentsWhitelist'          => _t('Người bình luận phải vượt qua kiểm duyệt trước khi bình luận'),
            'commentsRequireMail'        => _t('Bắt buộc phải có email'),
            'commentsRequireURL'         => _t('Bắt buộc phải điền trang web'),
            'commentsCheckReferer'       => _t('Kiểm tra xem URL trang nguồn bình luận có phù hợp với liên kết bài viết không'),
            'commentsAntiSpam'           => _t('Bật bảo vệ chống bình luận spam'),
            'commentsAutoClose'          => _t('Bình luận sẽ tự động bị đóng %s ngày sau khi bài viết được xuất bản!',
                '</label><input name="commentsPostTimeout" type="text" class="text num text-s" value="' . intval($this->options->commentsPostTimeout / (24 * 3600)) . '" id="commentsPost-commentsPostTimeout" />
            <label for="commentsPost-commentsPostTimeout">'),
            'commentsPostIntervalEnable' => _t('Khoảng thời gian giữa các lần gửi bình luận từ cùng một IP được giới hạn ở %s phút',
                '</label><input name="commentsPostInterval" type="text" class="text num text-s" value="' . round($this->options->commentsPostInterval / (60), 1) . '" id="commentsPost-commentsPostInterval" />
            <label for="commentsPost-commentsPostInterval">')
        ];

        $commentsPostOptionsValue = [];
        if ($this->options->commentsRequireModeration) {
            $commentsPostOptionsValue[] = 'commentsRequireModeration';
        }

        if ($this->options->commentsWhitelist) {
            $commentsPostOptionsValue[] = 'commentsWhitelist';
        }

        if ($this->options->commentsRequireMail) {
            $commentsPostOptionsValue[] = 'commentsRequireMail';
        }

        if ($this->options->commentsRequireURL) {
            $commentsPostOptionsValue[] = 'commentsRequireURL';
        }

        if ($this->options->commentsCheckReferer) {
            $commentsPostOptionsValue[] = 'commentsCheckReferer';
        }

        if ($this->options->commentsAntiSpam) {
            $commentsPostOptionsValue[] = 'commentsAntiSpam';
        }

        if ($this->options->commentsAutoClose) {
            $commentsPostOptionsValue[] = 'commentsAutoClose';
        }

        if ($this->options->commentsPostIntervalEnable) {
            $commentsPostOptionsValue[] = 'commentsPostIntervalEnable';
        }

        $commentsPost = new Form\Element\Checkbox(
            'commentsPost',
            $commentsPostOptions,
            $commentsPostOptionsValue,
            _t('Gửi bình luận')
        );
        $form->addInput($commentsPost->multiMode());

        /** Các thẻ và thuộc tính HTML được phép */
        $commentsHTMLTagAllowed = new Form\Element\Textarea(
            'commentsHTMLTagAllowed',
            null,
            $this->options->commentsHTMLTagAllowed,
            _t('Các thẻ và thuộc tính HTML được phép'),
            _t('Các bình luận mặc định của người dùng không cho phép điền bất kỳ thẻ HTML nào. Bạn có thể điền các thẻ HTML được phép sử dụng tại đây.') . '<br />'
            . _t('Ví dụ:%s', '<code>&lt;a href=&quot;&quot;&gt; &lt;img src=&quot;&quot;&gt; &lt;blockquote&gt;</code>')
        );
        $commentsHTMLTagAllowed->input->setAttribute('class', 'mono');
        $form->addInput($commentsHTMLTagAllowed);

        /** nút gửi */
        $submit = new Form\Element\Submit('submit', null, _t('Lưu cài đặt'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * Hành động ràng buộc
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost())->updateDiscussionSettings();
        $this->response->redirect($this->options->adminUrl);
    }
}
