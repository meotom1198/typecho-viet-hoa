<?php

namespace Widget\Options;

use Typecho\Db\Exception;
use Typecho\I18n\GetText;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Các thành phần thiết lập cơ bản
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class General extends Options implements ActionInterface
{
    /**
     * Kiểm tra xem nó có trong danh sách ngôn ngữ không
     *
     * @param string $lang
     * @return bool
     */
    public function checkLang(string $lang): bool
    {
        $langs = self::getLangs();
        return isset($langs[$lang]);
    }

    /**
     * Nhận danh sách ngôn ngữ
     *
     * @return array
     */
    public static function getLangs(): array
    {
        $dir = defined('__TYPECHO_LANG_DIR__') ? __TYPECHO_LANG_DIR__ : __TYPECHO_ROOT_DIR__ . '/usr/langs';
        $files = glob($dir . '/*.mo');
        $langs = ['vi_VN' => 'Tiếng Việt'];

        if (!empty($files)) {
            foreach ($files as $file) {
                $getText = new GetText($file, false);
                [$name] = explode('.', basename($file));
                $title = $getText->translate('lang', $count);
                $langs[$name] = $count > - 1 ? $title : $name;
            }

            ksort($langs);
        }

        return $langs;
    }

    /**
     * Lọc ra các hậu tố thực thi được
     *
     * @param string $ext
     * @return boolean
     */
    public function removeShell(string $ext): bool
    {
        return !preg_match("/^(php|php4|php5|sh|asp|jsp|rb|py|pl|dll|exe|bat)$/i", $ext);
    }

    /**
     * Thực hiện hành động cập nhật
     *
     * @throws Exception
     */
    public function updateGeneralSettings()
    {
        /** Xác minh định dạng */
        if ($this->form()->validate()) {
            $this->response->goBack();
        }

        $settings = $this->request->from(
            'title',
            'description',
            'keywords',
            'allowRegister',
            'allowXmlRpc',
            'lang',
            'timezone'
        );
        $settings['attachmentTypes'] = $this->request->getArray('attachmentTypes');

        if (!defined('__TYPECHO_SITE_URL__')) {
            $settings['siteUrl'] = rtrim($this->request->siteUrl, '/');
        }

        $attachmentTypes = [];
        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@image@')) {
            $attachmentTypes[] = '@image@';
        }

        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@media@')) {
            $attachmentTypes[] = '@media@';
        }

        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@doc@')) {
            $attachmentTypes[] = '@doc@';
        }

        $attachmentTypesOther = $this->request->filter('trim', 'strtolower')->attachmentTypesOther;
        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@other@') && !empty($attachmentTypesOther)) {
            $types = implode(
                ',',
                array_filter(array_map('trim', explode(',', $attachmentTypesOther)), [$this, 'removeShell'])
            );

            if (!empty($types)) {
                $attachmentTypes[] = $types;
            }
        }

        $settings['attachmentTypes'] = implode(',', $attachmentTypes);
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
        $form = new Form($this->security->getIndex('/action/options-general'), Form::POST_METHOD);

        /** Tên trang web */
        $title = new Form\Element\Text('title', null, $this->options->title, _t('Tiêu đề trang web'), _t('Tiêu đề của trang web sẽ xuất hiện trong tiêu đề của trang web.'));
        $title->input->setAttribute('class', 'w-100');
        $form->addInput($title->addRule('required', _t('Vui lòng điền tiêu đề trang web'))
            ->addRule('xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong tên trang web!')));

        /** Địa chỉ trang web */
        if (!defined('__TYPECHO_SITE_URL__')) {
            $siteUrl = new Form\Element\Text(
                'siteUrl',
                null,
                $this->options->originalSiteUrl,
                _t('Địa chỉ trang web'),
                _t('Địa chỉ trang web chủ yếu được sử dụng để tạo liên kết cố định tới nội dung.') . ($this->options->originalSiteUrl == $this->options->rootUrl ?
                    '' : '</p><p class="message notice mono">'
                    . _t('Địa chỉ hiện tại <strong>%s</strong> không phù hợp với cài đặt ở trên!', $this->options->rootUrl))
            );
            $siteUrl->input->setAttribute('class', 'w-100 mono');
            $form->addInput($siteUrl->addRule('required', _t('Vui lòng điền địa chỉ trang web!'))
                ->addRule('url', _t('Vui lòng điền địa chỉ URL hợp pháp!')));
        }

        /** mô tả trang web */
        $description = new Form\Element\Text(
            'description',
            null,
            $this->options->description,
            _t('Mô tả trang web'),
            _t('Mô tả trang web sẽ được hiển thị ở phần đầu của mã trang web.')
        );
        $form->addInput($description->addRule('xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong mô tả trang web!')));

        /** từ khóa */
        $keywords = new Form\Element\Text(
            'keywords',
            null,
            $this->options->keywords,
            _t('Từ khóa'),
            _t('Vui lòng phân tách nhiều từ khóa bằng dấu phẩy ","!')
        );
        $form->addInput($keywords->addRule('xssCheck', _t('Vui lòng không sử dụng ký tự đặc biệt trong từ khóa!')));

        /** đăng ký */
        $allowRegister = new Form\Element\Radio(
            'allowRegister',
            ['0' => _t('Tắt đăng ký'), '1' => _t('Bật đăng ký')],
            $this->options->allowRegister,
            _t('Cho đăng ký tài khoản không?'),
            _t('Cho phép khách đăng ký làm thành viên trong blog của bạn, người dùng đã đăng ký không có bất kỳ quyền đặc biệt nào.')
        );
        $form->addInput($allowRegister);

        /** XMLRPC */
        $allowXmlRpc = new Form\Element\Radio(
            'allowXmlRpc',
            ['0' => _t('Đóng'), '1' => _t('Chỉ đóng Pingback'), '2' => _t('Mở')],
            $this->options->allowXmlRpc,
            _t('Giao diện XMLRPC')
        );
        $form->addInput($allowXmlRpc);

        /** mục ngôn ngữ */
        // quét ngôn ngữ hack
        _t('lang');

        $langs = self::getLangs();

        if (count($langs) > 1) {
            $lang = new Form\Element\Select('lang', $langs, $this->options->lang, _t('Ngôn ngữ'));
            $form->addInput($lang->addRule([$this, 'checkLang'], _t('Gói ngôn ngữ đã chọn không tồn tại!')));
        }

        /** múi giờ */
        $timezoneList = [
            "0"      => _t('GMT'),
            "3600"   => _t('GMT +1'),
            "7200"   => _t('GMT +2'),
            "10800"  => _t('GMT +3'),
            "14400"  => _t('GMT +4'),
            "18000"  => _t('(GMT +5'),
            "21600"  => _t('GMT +6'),
            "25200"  => _t('GMT +7'),
            "28800"  => _t('GMT +8'),
            "32400"  => _t('GMT +9'),
            "36000"  => _t('GMT +10'),
            "39600"  => _t('GMT +11'),
            "43200"  => _t('GMT +12'),
            "-3600"  => _t('GMT -1'),
            "-7200"  => _t('GMT -2'),
            "-10800" => _t('GMT -3'),
            "-14400" => _t('GMT -4'),
            "-18000" => _t('GMT -5'),
            "-21600" => _t('GMT -6'),
            "-25200" => _t('GMT -7'),
            "-28800" => _t('GMT -8'),
            "-32400" => _t('GMT -9'),
            "-36000" => _t('GMT -10'),
            "-39600" => _t('GMT -11'),
            "-43200" => _t('GMT -12')
        ];

        $timezone = new Form\Element\Select('timezone', $timezoneList, $this->options->timezone, _t('Múi giờ'));
        $form->addInput($timezone);

        /** sự mở rộng */
        $attachmentTypesOptionsResult = (null != trim($this->options->attachmentTypes)) ?
            array_map('trim', explode(',', $this->options->attachmentTypes)) : [];
        $attachmentTypesOptionsValue = [];

        if (in_array('@image@', $attachmentTypesOptionsResult)) {
            $attachmentTypesOptionsValue[] = '@image@';
        }

        if (in_array('@media@', $attachmentTypesOptionsResult)) {
            $attachmentTypesOptionsValue[] = '@media@';
        }

        if (in_array('@doc@', $attachmentTypesOptionsResult)) {
            $attachmentTypesOptionsValue[] = '@doc@';
        }

        $attachmentTypesOther = array_diff($attachmentTypesOptionsResult, $attachmentTypesOptionsValue);
        $attachmentTypesOtherValue = '';
        if (!empty($attachmentTypesOther)) {
            $attachmentTypesOptionsValue[] = '@other@';
            $attachmentTypesOtherValue = implode(',', $attachmentTypesOther);
        }

        $attachmentTypesOptions = [
            '@image@' => _t('Tập tin hình ảnh') . ' <code>(gif jpg jpeg png tiff bmp webp avif)</code>',
            '@media@' => _t('Tập tin đa phương tiện') . ' <code>(mp3 mp4 mov wmv wma rmvb rm avi flv ogg oga ogv)</code>',
            '@doc@'   => _t('Tập tin lưu trữ') . ' <code>(txt doc docx xls xlsx ppt pptx zip rar pdf)</code>',
            '@other@' => _t(
                'Định dạng khác %s',
                ' <input type="text" class="w-50 text-s mono" name="attachmentTypesOther" value="'
                . htmlspecialchars($attachmentTypesOtherValue) . '" />'
            ),
        ];

        $attachmentTypes = new Form\Element\Checkbox(
            'attachmentTypes',
            $attachmentTypesOptions,
            $attachmentTypesOptionsValue,
            _t('Các loại tập tin được phép tải lên'),
            _t('Sử dụng dấu phẩy "," để phân tách các tên hậu tố, ví dụ: %s', '<code>cpp, h, mak</code>')
        );
        $form->addInput($attachmentTypes->multiMode());

        /** nút gửi */
        $submit = new Form\Element\Submit('submit', null, _t('Lưu cài đặt'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * Hành động ràng buộc
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost())->updateGeneralSettings();
        $this->response->redirect($this->options->adminUrl);
    }
}
