<?php

namespace Typecho\Widget\Helper;

use Typecho\Cookie;
use Typecho\Request;
use Typecho\Validate;
use Typecho\Widget\Helper\Form\Element;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Trình trợ giúp xử lý biểu mẫu
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Form extends Layout
{
    /** phương pháp đăng bài mẫu */
    public const POST_METHOD = 'post';

    /** phương thức lấy mẫu */
    public const GET_METHOD = 'get';

    /** phương pháp mã hóa tiêu chuẩn */
    public const STANDARD_ENCODE = 'application/x-www-form-urlencoded';

    /** mã hóa hỗn hợp */
    public const MULTIPART_ENCODE = 'multipart/form-data';

    /** mã hóa văn bản */
    public const TEXT_ENCODE = 'text/plain';

    /**
     * Danh sách các yếu tố đầu vào
     *
     * @access private
     * @var array
     */
    private $inputs = [];

    /**
     * Hàm tạo, thiết lập các thuộc tính cơ bản
     *
     * @access public
     */
    public function __construct($action = null, $method = self::GET_METHOD, $enctype = self::STANDARD_ENCODE)
    {
        /** Đặt nhãn biểu mẫu */
        parent::__construct('form');

        /** Đóng tự đóng */
        $this->setClose(false);

        /** Đặt thuộc tính biểu mẫu */
        $this->setAction($action);
        $this->setMethod($method);
        $this->setEncodeType($enctype);
    }

    /**
     * Đặt mục đích gửi biểu mẫu
     *
     * @param string|null $action Mục đích gửi biểu mẫu
     * @return $this
     */
    public function setAction(?string $action): Form
    {
        $this->setAttribute('action', $action);
        return $this;
    }

    /**
     * Đặt phương thức gửi biểu mẫu
     *
     * @param string $method Phương thức gửi biểu mẫu
     * @return $this
     */
    public function setMethod(string $method): Form
    {
        $this->setAttribute('method', $method);
        return $this;
    }

    /**
     * Đặt sơ đồ mã hóa biểu mẫu
     *
     * @param string $enctype Phương pháp mã hóa
     * @return $this
     */
    public function setEncodeType(string $enctype): Form
    {
        $this->setAttribute('enctype', $enctype);
        return $this;
    }

    /**
     * Thêm phần tử đầu vào
     *
     * @access public
     * @param Element $input yếu tố đầu vào
     * @return $this
     */
    public function addInput(Element $input): Form
    {
        $this->inputs[$input->name] = $input;
        $this->addItem($input);
        return $this;
    }

    /**
     * Nhận đầu vào
     *
     * @param string $name Tên đầu vào
     * @return mixed
     */
    public function getInput(string $name)
    {
        return $this->inputs[$name];
    }

    /**
     * Nhận các giá trị đã gửi của tất cả các mục đầu vào
     *
     * @return array
     */
    public function getAllRequest(): array
    {
        return $this->getParams(array_keys($this->inputs));
    }

    /**
     * Lấy giá trị vốn có của tất cả các mục đầu vào ở dạng này
     *
     * @return array
     */
    public function getValues(): array
    {
        $values = [];

        foreach ($this->inputs as $name => $input) {
            $values[$name] = $input->value;
        }
        return $values;
    }

    /**
     * Nhận tất cả thông tin đầu vào của biểu mẫu này
     *
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Mẫu xác nhận
     *
     * @return array
     */
    public function validate(): array
    {
        $validator = new Validate();
        $rules = [];

        foreach ($this->inputs as $name => $input) {
            $rules[$name] = $input->rules;
        }

        $id = md5(implode('"', array_keys($this->inputs)));

        /** giá trị biểu mẫu */
        $formData = $this->getParams(array_keys($rules));
        $error = $validator->run($formData, $rules);

        if ($error) {
            /** Sử dụng session để ghi lại lỗi */
            Cookie::set('__typecho_form_message_' . $id, json_encode($error));

            /** Sử dụng session để ghi lại giá trị biểu mẫu */
            Cookie::set('__typecho_form_record_' . $id, json_encode($formData));
        }

        return $error;
    }

    /**
     * Nhận nguồn dữ liệu gửi
     *
     * @param array $params Bộ tham số dữ liệu
     * @return array
     */
    public function getParams(array $params): array
    {
        $result = [];
        $request = Request::getInstance();

        foreach ($params as $param) {
            $result[$param] = $request->get($param, is_array($this->getInput($param)->value) ? [] : null);
        }

        return $result;
    }

    /**
     * hiển thị hình thức
     *
     * @return void
     */
    public function render()
    {
        $id = md5(implode('"', array_keys($this->inputs)));
        $record = Cookie::get('__typecho_form_record_' . $id);
        $message = Cookie::get('__typecho_form_message_' . $id);

        /** Khôi phục giá trị biểu mẫu */
        if (!empty($record)) {
            $record = json_decode($record, true);
            $message = json_decode($message, true);
            foreach ($this->inputs as $name => $input) {
                $input->value($record[$name] ?? $input->value);

                /** Hiển thị thông báo lỗi */
                if (isset($message[$name])) {
                    $input->message($message[$name]);
                }
            }

            Cookie::delete('__typecho_form_record_' . $id);
        }

        parent::render();
        Cookie::delete('__typecho_form_message_' . $id);
    }
}
