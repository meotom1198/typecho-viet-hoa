<?php

namespace Widget\Themes;

use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Submit;
use Widget\Base\Options as BaseOptions;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Thành phần cấu hình da
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Config extends BaseOptions
{
    /**
     * Hành động ràng buộc
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        $this->user->pass('administrator');

        if (!self::isExists()) {
            throw new Exception(_t('Chức năng cài đạt ngoại hình không tồn tại!'), 404);
        }
    }

    /**
     * Chức năng cấu hình có tồn tại không?
     *
     * @return boolean
     */
    public static function isExists(): bool
    {
        $options = Options::alloc();
        $configFile = $options->themeFile($options->theme, 'functions.php');

        if (!$options->missingTheme && file_exists($configFile)) {
            require_once $configFile;

            if (function_exists('themeConfig')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cấu hình giao diện
     *
     * @return Form
     */
    public function config(): Form
    {
        $form = new Form($this->security->getIndex('/action/themes-edit?config'), Form::POST_METHOD);
        themeConfig($form);
        $inputs = $form->getInputs();

        if (!empty($inputs)) {
            foreach ($inputs as $key => $val) {
                $form->getInput($key)->value($this->options->{$key});
            }
        }

        $submit = new Submit(null, null, _t('Lưu cài đặt'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        return $form;
    }
}
