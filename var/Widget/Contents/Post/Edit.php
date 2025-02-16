<?php

namespace Widget\Contents\Post;

use Typecho\Common;
use Typecho\Config;
use Typecho\Validate;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Typecho\Db\Exception as DbException;
use Typecho\Date as TypechoDate;
use Widget\Notice;
use Widget\Service;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Chỉnh sửa thành phần bài viết
 *
 * @property-read array|null $draft
 */
class Edit extends Contents implements ActionInterface
{
    /**
     * Tên hook trường tùy chỉnh
     *
     * @var string
     */
    protected $themeCustomFieldsHook = 'themePostFields';

    /**
     * Thực thi chức năng
     *
     * @throws Exception|DbException
     */
    public function execute()
    {
        /** Phải là cộng tác viên trở lên */
        $this->user->pass('contributor');

        /** Lấy nội dung bài viết */
        if (!empty($this->request->cid)) {
            $this->db->fetchRow($this->select()
                ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'post_draft')
                ->where('table.contents.cid = ?', $this->request->filter('int')->cid)
                ->limit(1), [$this, 'push']);

            if ('post_draft' == $this->type && $this->parent) {
                $this->response->redirect(
                    Common::url('write-post.php?cid=' . $this->parent, $this->options->adminUrl)
                );
            }

            if (!$this->have()) {
                throw new Exception(_t('Bài viết không tồn tại hoặc đã bị xoá bởi Admin. Bạn có thể về trang chủ và sử dụng công cụ tìm kiếm ở đầu trang để tìm bài viết hoặc nội dung mà bạn cần!'), 404);
            } elseif (!$this->allow('edit')) {
                throw new Exception(_t('Bạn không có quyền chỉnh sửa!'), 403);
            }
        }
    }

    /**
     * Nhận quyền bài viết
     *
     * @param mixed ...$permissions
     * @return bool
     * @throws Exception|DbException
     */
    public function allow(...$permissions): bool
    {
        $allow = true;

        foreach ($permissions as $permission) {
            $permission = strtolower($permission);

            if ('edit' == $permission) {
                $allow &= ($this->user->pass('editor', true) || $this->authorId == $this->user->uid);
            } else {
                $permission = 'allow' . ucfirst(strtolower($permission));
                $optionPermission = 'default' . ucfirst($permission);
                $allow &= ($this->{$permission} ?? $this->options->{$optionPermission});
            }
        }

        return $allow;
    }

    /**
     * ngăn xếp bộ lọc
     *
     * @param array $value giá trị của mỗi hàng
     * @return array
     * @throws DbException
     */
    public function filter(array $value): array
    {
        if ('post' == $value['type'] || 'page' == $value['type']) {
            $draft = $this->db->fetchRow(Contents::alloc()->select()
                ->where(
                    'table.contents.parent = ? AND table.contents.type = ?',
                    $value['cid'],
                    $value['type'] . '_draft'
                )
                ->limit(1));

            if (!empty($draft)) {
                $draft['slug'] = ltrim($draft['slug'], '@');
                $draft['type'] = $value['type'];

                $draft = parent::filter($draft);

                $draft['tags'] = $this->db->fetchAll($this->db
                    ->select()->from('table.metas')
                    ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                    ->where('table.relationships.cid = ?', $draft['cid'])
                    ->where('table.metas.type = ?', 'tag'), [Metas::alloc(), 'filter']);
                $draft['cid'] = $value['cid'];

                return $draft;
            }
        }

        return parent::filter($value);
    }

    /**
     * Đầu ra ngày xuất bản bài viết
     *
     * @param string $format định dạng ngày
     * @return void
     */
    public function date($format = null)
    {
        if (isset($this->created)) {
            parent::date($format);
        } else {
            echo date($format, $this->options->time + $this->options->timezone - $this->options->serverTimezone);
        }
    }

    /**
     * Lấy tiêu đề trang web
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('Sửa %s', $this->title);
    }

    /**
     * getFieldItems
     *
     * @throws DbException
     */
    public function getFieldItems(): array
    {
        $fields = [];

        if ($this->have()) {
            $defaultFields = $this->getDefaultFieldItems();
            $rows = $this->db->fetchAll($this->db->select()->from('table.fields')
                ->where('cid = ?', $this->cid));

            foreach ($rows as $row) {
                $isFieldReadOnly = Contents::pluginHandle()
                    ->trigger($plugged)->isFieldReadOnly($row['name']);

                if ($plugged && $isFieldReadOnly) {
                    continue;
                }

                if (!isset($defaultFields[$row['name']])) {
                    $fields[] = $row;
                }
            }
        }

        return $fields;
    }

    /**
     * getDefaultFieldItems
     *
     * @return array
     */
    public function getDefaultFieldItems(): array
    {
        $defaultFields = [];
        $configFile = $this->options->themeFile($this->options->theme, 'functions.php');
        $layout = new Layout();
        $fields = new Config();

        if ($this->have()) {
            $fields = $this->fields;
        }

        self::pluginHandle()->getDefaultFieldItems($layout);

        if (file_exists($configFile)) {
            require_once $configFile;

            if (function_exists('themeFields')) {
                themeFields($layout);
            }

            if (function_exists($this->themeCustomFieldsHook)) {
                call_user_func($this->themeCustomFieldsHook, $layout);
            }
        }

        $items = $layout->getItems();
        foreach ($items as $item) {
            if ($item instanceof Element) {
                $name = $item->input->getAttribute('name');

                $isFieldReadOnly = Contents::pluginHandle()
                    ->trigger($plugged)->isFieldReadOnly($name);
                if ($plugged && $isFieldReadOnly) {
                    continue;
                }

                if (preg_match("/^fields\[(.+)\]$/", $name, $matches)) {
                    $name = $matches[1];
                } else {
                    $inputName = 'fields[' . $name . ']';
                    if (preg_match("/^(.+)\[\]$/", $name, $matches)) {
                        $name = $matches[1];
                        $inputName = 'fields[' . $name . '][]';
                    }

                    foreach ($item->inputs as $input) {
                        $input->setAttribute('name', $inputName);
                    }
                }

                if (isset($fields->{$name})) {
                    $item->value($fields->{$name});
                }

                $elements = $item->container->getItems();
                array_shift($elements);
                $div = new Layout('div');

                foreach ($elements as $el) {
                    $div->addItem($el);
                }

                $defaultFields[$name] = [$item->label, $div];
            }
        }

        return $defaultFields;
    }

    /**
     * Đăng bài viết
     */
    public function writePost()
    {
        $contents = $this->request->from(
            'password',
            'allowComment',
            'allowPing',
            'allowFeed',
            'slug',
            'tags',
            'text',
            'visibility'
        );

        $contents['category'] = $this->request->getArray('category');
        $contents['title'] = $this->request->get('title', _t('Tài liệu không tên!'));
        $contents['created'] = $this->getCreated();

        if ($this->request->markdown && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }

        $contents = self::pluginHandle()->write($contents, $this);

        if ($this->request->is('do=publish')) {
            /** Xuất bản lại một bài viết hiện có */
            $contents['type'] = 'post';
            $this->publish($contents);

            // Hoàn thiện giao diện plug-in phát hành
            self::pluginHandle()->finishPublish($contents, $this);

            /** gửi ping */
            $trackback = array_filter(array_unique(preg_split("/(\r|\n|\r\n)/", trim($this->request->trackback))));
            Service::alloc()->sendPing($this, $trackback);

            /** Đặt thông tin nhắc nhở */
            Notice::alloc()->set('post' == $this->type ?
                _t('Đã phát hành thành công bài viết: "<a href="%s">%s</a>". Bấm để xem thử!', $this->permalink, $this->title) :
                _t('Bài viết "%s" đang chờ Admin duyệt', $this->title), 'success');

            /** Đặt điểm nổi bật */
            Notice::alloc()->highlight($this->theId);

            /** Nhận phần bù trang */
            $pageQuery = $this->getPageOffsetQuery($this->cid);

            /** Nhảy trang */
            $this->response->redirect(Common::url('manage-posts.php?' . $pageQuery, $this->options->adminUrl));
        } else {
            /** Lưu bài viết làm bản nháp */
            $contents['type'] = 'post_draft';
            $this->save($contents);

            // Giao diện plug-in lưu hoàn chỉnh
            self::pluginHandle()->finishSave($contents, $this);

            /** Đặt điểm nổi bật */
            Notice::alloc()->highlight($this->cid);

            if ($this->request->isAjax()) {
                $created = new TypechoDate();
                $this->response->throwJson([
                    'success' => 1,
                    'time'    => $created->format('s:i:H'),
                    'cid'     => $this->cid,
                    'draftId' => $this->draft['cid']
                ]);
            } else {
                /** Đặt thông tin nhắc nhở */
                Notice::alloc()->set(_t('Đã lưu bài viết: "%s" làm bản nháp!', $this->title), 'success');

                /** Trở về trang gốc */
                $this->response->redirect(Common::url('write-post.php?cid=' . $this->cid, $this->options->adminUrl));
            }
        }
    }

    /**
     * Nhận giá trị trường đã tạo dựa trên giá trị đã gửi
     *
     * @return integer
     */
    protected function getCreated(): int
    {
        $created = $this->options->time;
        if (!empty($this->request->created)) {
            $created = $this->request->created;
        } elseif (!empty($this->request->date)) {
            $dstOffset = !empty($this->request->dst) ? $this->request->dst : 0;
            $timezoneSymbol = $this->options->timezone >= 0 ? '+' : '-';
            $timezoneOffset = abs($this->options->timezone);
            $timezone = $timezoneSymbol . str_pad($timezoneOffset / 3600, 2, '0', STR_PAD_LEFT) . ':00';
            [$date, $time] = explode(' ', $this->request->date);

            $created = strtotime("{$date}T{$time}{$timezone}") - $dstOffset;
        } elseif (!empty($this->request->year) && !empty($this->request->month) && !empty($this->request->day)) {
            $second = intval($this->request->get('sec', date('s')));
            $min = intval($this->request->get('min', date('i')));
            $hour = intval($this->request->get('hour', date('H')));

            $year = intval($this->request->year);
            $month = intval($this->request->month);
            $day = intval($this->request->day);

            $created = mktime($hour, $min, $second, $month, $day, $year)
                - $this->options->timezone + $this->options->serverTimezone;
        } elseif ($this->have() && $this->created > 0) {
            // Nếu bạn đang sửa bài viết
            $created = $this->created;
        } elseif ($this->request->is('do=save')) {
            // Nếu là bản nháp và không có đầu vào thì để nguyên
            $created = 0;
        }

        return $created;
    }

    /**
     * Đăng nội dung
     *
     * @param array $contents Cấu trúc nội dung
     * @throws DbException|Exception
     */
    protected function publish(array $contents)
    {
        /** Xuất bản nội dung, kiểm tra xem bạn có được phép xuất bản trực tiếp không */
        if ($this->user->pass('editor', true)) {
            if (empty($contents['visibility'])) {
                $contents['status'] = 'publish';
            } elseif (
                !in_array($contents['visibility'], ['private', 'waiting', 'publish', 'hidden'])
            ) {
                if (empty($contents['password']) || 'password' != $contents['visibility']) {
                    $contents['password'] = '';
                }
                $contents['status'] = 'publish';
            } else {
                $contents['status'] = $contents['visibility'];
                $contents['password'] = '';
            }
        } else {
            $contents['status'] = 'waiting';
            $contents['password'] = '';
        }

        /** id nội dung thực */
        $realId = 0;

        /** Có nên xuất bản từ trạng thái bản nháp hay không */
        $isDraftToPublish = ('post_draft' == $this->type || 'page_draft' == $this->type);

        $isBeforePublish = ('publish' == $this->status);
        $isAfterPublish = ('publish' == $contents['status']);

        /** Xuất bản lại nội dung hiện có */
        if ($this->have()) {

            /** Nếu bản thân nó không phải là bản nháp thì nó cần được xóa dưới dạng bản nháp */
            if (!$isDraftToPublish && $this->draft) {
                $cid = $this->draft['cid'];
                $this->deleteDraft($cid);
                $this->deleteFields($cid);
            }

            /** Thay đổi trạng thái dự thảo trực tiếp */
            if ($this->update($contents, $this->db->sql()->where('cid = ?', $this->cid))) {
                $realId = $this->cid;
            }
        } else {
            /** Đăng nội dung mới */
            $realId = $this->insert($contents);
        }

        if ($realId > 0) {
            /** Chèn danh mục */
            if (array_key_exists('category', $contents)) {
                $this->setCategories(
                    $realId,
                    !empty($contents['category']) && is_array($contents['category'])
                        ? $contents['category'] : [$this->options->defaultCategory],
                    !$isDraftToPublish && $isBeforePublish,
                    $isAfterPublish
                );
            }

            /** Chèn thẻ */
            if (array_key_exists('tags', $contents)) {
                $this->setTags($realId, $contents['tags'], !$isDraftToPublish && $isBeforePublish, $isAfterPublish);
            }

            /** Đồng bộ hóa tệp đính kèm */
            $this->attach($realId);

            /** Lưu các tùy chỉnh */
            $this->applyFields($this->getFields(), $realId);

            $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $realId)->limit(1), [$this, 'push']);
        }
    }

    /**
     * Xóa bản nháp
     *
     * @param integer $cid mã dự thảo
     * @throws DbException
     */
    protected function deleteDraft($cid)
    {
        $this->delete($this->db->sql()->where('cid = ?', $cid));

        /** Xóa danh mục dự thảo */
        $this->setCategories($cid, [], false, false);

        /** Xoá thẻ */
        $this->setTags($cid, null, false, false);
    }

    /**
     * Đặt danh mục
     *
     * @param integer $cid Id nội dung
     * @param array $categories Mảng bộ sưu tập id phân loại
     * @param boolean $beforeCount Có tham gia đếm không
     * @param boolean $afterCount Có tham gia đếm không
     * @throws DbException
     */
    public function setCategories(int $cid, array $categories, bool $beforeCount = true, bool $afterCount = true)
    {
        $categories = array_unique(array_map('trim', $categories));

        /** Xóa danh mục hiện tại */
        $existCategories = array_column(
            $this->db->fetchAll(
                $this->db->select('table.metas.mid')
                    ->from('table.metas')
                    ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                    ->where('table.relationships.cid = ?', $cid)
                    ->where('table.metas.type = ?', 'category')
            ),
            'mid'
        );

        /** Xóa danh mục hiện có */
        if ($existCategories) {
            foreach ($existCategories as $category) {
                $this->db->query($this->db->delete('table.relationships')
                    ->where('cid = ?', $cid)
                    ->where('mid = ?', $category));

                if ($beforeCount) {
                    $this->db->query($this->db->update('table.metas')
                        ->expression('count', 'count - 1')
                        ->where('mid = ?', $category));
                }
            }
        }

        /** Chèn danh mục */
        if ($categories) {
            foreach ($categories as $category) {
                /** Nếu danh mục không tồn tại */
                if (
                    !$this->db->fetchRow(
                        $this->db->select('mid')
                        ->from('table.metas')
                        ->where('mid = ?', $category)
                        ->limit(1)
                    )
                ) {
                    continue;
                }

                $this->db->query($this->db->insert('table.relationships')
                    ->rows([
                        'mid' => $category,
                        'cid' => $cid
                    ]));

                if ($afterCount) {
                    $this->db->query($this->db->update('table.metas')
                        ->expression('count', 'count + 1')
                        ->where('mid = ?', $category));
                }
            }
        }
    }

    /**
     * Đặt thẻ nội dung
     *
     * @param integer $cid
     * @param string|null $tags
     * @param boolean $beforeCount Có tham gia đếm không
     * @param boolean $afterCount Có tham gia đếm không
     * @throws DbException
     */
    public function setTags(int $cid, ?string $tags, bool $beforeCount = true, bool $afterCount = true)
    {
        $tags = str_replace('，', ',', $tags);
        $tags = array_unique(array_map('trim', explode(',', $tags)));
        $tags = array_filter($tags, [Validate::class, 'xssCheck']);

        /** Xóa các thẻ hiện có */
        $existTags = array_column(
            $this->db->fetchAll(
                $this->db->select('table.metas.mid')
                ->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $cid)
                ->where('table.metas.type = ?', 'tag')
            ),
            'mid'
        );

        /** Xóa các thẻ hiện có */
        if ($existTags) {
            foreach ($existTags as $tag) {
                if (0 == strlen($tag)) {
                    continue;
                }

                $this->db->query($this->db->delete('table.relationships')
                    ->where('cid = ?', $cid)
                    ->where('mid = ?', $tag));

                if ($beforeCount) {
                    $this->db->query($this->db->update('table.metas')
                        ->expression('count', 'count - 1')
                        ->where('mid = ?', $tag));
                }
            }
        }

        /** Xóa và chèn thẻ */
        $insertTags = Metas::alloc()->scanTags($tags);

        /** Chèn thẻ */
        if ($insertTags) {
            foreach ($insertTags as $tag) {
                if (0 == strlen($tag)) {
                    continue;
                }

                $this->db->query($this->db->insert('table.relationships')
                    ->rows([
                        'mid' => $tag,
                        'cid' => $cid
                    ]));

                if ($afterCount) {
                    $this->db->query($this->db->update('table.metas')
                        ->expression('count', 'count + 1')
                        ->where('mid = ?', $tag));
                }
            }
        }
    }

    /**
     * Đồng bộ hóa tệp đính kèm
     *
     * @param integer $cid Id nội dung
     * @throws DbException
     */
    protected function attach(int $cid)
    {
        $attachments = $this->request->getArray('attachment');
        if (!empty($attachments)) {
            foreach ($attachments as $key => $attachment) {
                $this->db->query($this->db->update('table.contents')->rows([
                    'parent' => $cid,
                    'status' => 'publish',
                    'order'  => $key + 1
                ])->where('cid = ? AND type = ?', $attachment, 'attachment'));
            }
        }
    }

    /**
     * getFields
     *
     * @return array
     */
    protected function getFields(): array
    {
        $fields = [];
        $fieldNames = $this->request->getArray('fieldNames');

        if (!empty($fieldNames)) {
            $data = [
                'fieldNames'  => $this->request->getArray('fieldNames'),
                'fieldTypes'  => $this->request->getArray('fieldTypes'),
                'fieldValues' => $this->request->getArray('fieldValues')
            ];
            foreach ($data['fieldNames'] as $key => $val) {
                $val = trim($val);

                if (0 == strlen($val)) {
                    continue;
                }

                $fields[$val] = [$data['fieldTypes'][$key], $data['fieldValues'][$key]];
            }
        }

        $customFields = $this->request->getArray('fields');
        foreach ($customFields as $key => $val) {
            $fields[$key] = [is_array($val) ? 'json' : 'str', $val];
        }

        return $fields;
    }

    /**
     * Nhận truy vấn URL của trang offset
     *
     * @param integer $cid id bài viết
     * @param string|null $status tình trạng
     * @return string
     * @throws DbException
     */
    protected function getPageOffsetQuery(int $cid, ?string $status = null): string
    {
        return 'page=' . $this->getPageOffset(
            'cid',
            $cid,
            'post',
            $status,
            'on' == $this->request->__typecho_all_posts ? 0 : $this->user->uid
        );
    }

    /**
     * Lưu nội dung
     *
     * @param array $contents Cấu trúc nội dung
     * @throws DbException|Exception
     */
    protected function save(array $contents)
    {
        /** Xuất bản nội dung, kiểm tra xem bạn có được phép xuất bản trực tiếp không */
        if ($this->user->pass('editor', true)) {
            if (empty($contents['visibility'])) {
                $contents['status'] = 'publish';
            } elseif (
                !in_array($contents['visibility'], ['private', 'waiting', 'publish', 'hidden'])
            ) {
                if (empty($contents['password']) || 'password' != $contents['visibility']) {
                    $contents['password'] = '';
                }
                $contents['status'] = 'publish';
            } else {
                $contents['status'] = $contents['visibility'];
                $contents['password'] = '';
            }
        } else {
            $contents['status'] = 'waiting';
            $contents['password'] = '';
        }

        /** id nội dung thực */
        $realId = 0;

        /** Nếu bản nháp đã tồn tại */
        if ($this->draft) {

            /** Thay đổi trạng thái dự thảo trực tiếp */
            if ($this->update($contents, $this->db->sql()->where('cid = ?', $this->draft['cid']))) {
                $realId = $this->draft['cid'];
            }
        } else {
            if ($this->have()) {
                $contents['parent'] = $this->cid;
            }

            /** Đăng nội dung mới */
            $realId = $this->insert($contents);

            if (!$this->have()) {
                $this->db->fetchRow(
                    $this->select()->where('table.contents.cid = ?', $realId)->limit(1),
                    [$this, 'push']
                );
            }
        }

        if ($realId > 0) {
            /** Chèn danh mục */
            if (array_key_exists('category', $contents)) {
                $this->setCategories($realId, !empty($contents['category']) && is_array($contents['category']) ?
                    $contents['category'] : [$this->options->defaultCategory], false, false);
            }

            /** Chèn thẻ */
            if (array_key_exists('tags', $contents)) {
                $this->setTags($realId, $contents['tags'], false, false);
            }

            /** Đồng bộ hóa tệp đính kèm */
            $this->attach($this->cid);

            /** Lưu các trường tùy chỉnh */
            $this->applyFields($this->getFields(), $realId);
        }
    }

    /**
     * Gắn thẻ bài viết
     *
     * @throws DbException
     */
    public function markPost()
    {
        $status = $this->request->get('status');
        $statusList = [
            'publish' => _t('Phát hành'),
            'private' => _t('Riêng tư'),
            'hidden'  => _t('Ẩn'),
            'waiting' => _t('Chờ duyệt')
        ];

        if (!isset($statusList[$status])) {
            $this->response->goBack();
        }

        $posts = $this->request->filter('int')->getArray('cid');
        $markCount = 0;

        foreach ($posts as $post) {
            // Đánh dấu giao diện plug-in
            self::pluginHandle()->mark($status, $post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $postObject = $this->db->fetchObject($this->db->select('status', 'type')
                ->from('table.contents')->where('cid = ? AND (type = ? OR type = ?)', $post, 'post', 'post_draft'));

            if ($this->isWriteable(clone $condition) && count((array)$postObject)) {

                /** đánh dấu trạng thái */
                $this->db->query($condition->update('table.contents')->rows(['status' => $status]));

                // Làm mới Meta
                if ($postObject->type == 'post') {
                    $op = null;

                    if ($status == 'publish' && $postObject->status != 'publish') {
                        $op = '+';
                    } elseif ($status != 'publish' && $postObject->status == 'publish') {
                        $op = '-';
                    }

                    if (!empty($op)) {
                        $metas = $this->db->fetchAll(
                            $this->db->select()->from('table.relationships')->where('cid = ?', $post)
                        );
                        foreach ($metas as $meta) {
                            $this->db->query($this->db->update('table.metas')
                                ->expression('count', 'count ' . $op . ' 1')
                                ->where('mid = ? AND (type = ? OR type = ?)', $meta['mid'], 'category', 'tag'));
                        }
                    }
                }

                // Làm việc trên bản nháp
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'post_draft')
                    ->limit(1));

                if (!empty($draft)) {
                    $this->db->query($this->db->update('table.contents')->rows(['status' => $status])
                        ->where('cid = ?', $draft['cid']));
                }

                // Hoàn thiện giao diện plugin đánh dấu
                self::pluginHandle()->finishMark($status, $post, $this);

                $markCount++;
            }

            unset($condition);
        }

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()
            ->set(
                $markCount > 0 ? _t('Bài viết đã chuyển trạng thái sang <strong>%s</strong>', $statusList[$status]) : _t('Không có bài viết nào được gắn thẻ!'),
                $markCount > 0 ? 'success' : 'notice'
            );

        /** Quay lại trang web ban đầu */
        $this->response->goBack();
    }

    /**
     * Xóa bài viết
     *
     * @throws DbException
     */
    public function deletePost()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            // Xóa giao diện plug-in
            self::pluginHandle()->delete($post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $postObject = $this->db->fetchObject($this->db->select('status', 'type')
                ->from('table.contents')->where('cid = ? AND (type = ? OR type = ?)', $post, 'post', 'post_draft'));

            if ($this->isWriteable(clone $condition) && count((array)$postObject) && $this->delete($condition)) {

                /** Xóa danh mục */
                $this->setCategories($post, [], 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** Xóa thẻ */
                $this->setTags($post, null, 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** Xóa bình luận */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $post));

                /** Tách các tệp đính kèm */
                $this->unAttach($post);

                /** Xóa bản nháp */
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'post_draft')
                    ->limit(1));

                /** Xóa trường tùy chỉnh */
                $this->deleteFields($post);

                if ($draft) {
                    $this->deleteDraft($draft['cid']);
                    $this->deleteFields($draft['cid']);
                }

                // Xóa hoàn toàn giao diện plug-in
                self::pluginHandle()->finishDelete($post, $this);

                $deleteCount++;
            }

            unset($condition);
        }

        // thẻ sạch
        if ($deleteCount > 0) {
            Metas::alloc()->clearTags();
        }

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('Bài viết đã bị xóa!') : _t('Không có bài viết nào đã bị xóa!'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** Quay lại trang web ban đầu */
        $this->response->goBack();
    }

    /**
     * Hủy liên kết tệp đính kèm
     *
     * @param integer $cid Id nội dung
     * @throws DbException
     */
    protected function unAttach($cid)
    {
        $this->db->query($this->db->update('table.contents')->rows(['parent' => 0, 'status' => 'publish'])
            ->where('parent = ? AND type = ?', $cid, 'attachment'));
    }

    /**
     * Xóa bản nháp chứa bài viết
     *
     * @throws DbException
     */
    public function deletePostDraft()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            /** Xóa bản nháp */
            $draft = $this->db->fetchRow($this->db->select('cid')
                ->from('table.contents')
                ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'post_draft')
                ->limit(1));

            if ($draft) {
                $this->deleteDraft($draft['cid']);
                $this->deleteFields($draft['cid']);
                $deleteCount++;
            }
        }

        /** Đặt thông tin nhắc nhở */
        Notice::alloc()
            ->set(
                $deleteCount > 0 ? _t('Bản nháp đã bị xóa!') : _t('Không có bản nháp nào bị xóa!'),
                $deleteCount > 0 ? 'success' : 'notice'
            );

        /** Quay lại trang web ban đầu */
        $this->response->goBack();
    }

    /**
     * Hành động ràng buộc
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))->writePost();
        $this->on($this->request->is('do=delete'))->deletePost();
        $this->on($this->request->is('do=mark'))->markPost();
        $this->on($this->request->is('do=deleteDraft'))->deletePostDraft();

        $this->response->redirect($this->options->adminUrl);
    }

    /**
     * Xóa thẻ
     *
     * @return array
     * @throws DbException
     */
    protected function ___tags(): array
    {
        if ($this->have()) {
            return $this->db->fetchAll($this->db
                ->select()->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $this->cid)
                ->where('table.metas.type = ?', 'tag'), [Metas::alloc(), 'filter']);
        }

        return [];
    }

    /**
     * Nhận thời gian hiện tại
     *
     * @return TypechoDate
     */
    protected function ___date(): TypechoDate
    {
        return new TypechoDate();
    }

    /**
     * Bản thảo của bài viết hiện tại
     *
     * @return array|null
     * @throws DbException
     */
    protected function ___draft(): ?array
    {
        if ($this->have()) {
            if ('post_draft' == $this->type || 'page_draft' == $this->type) {
                return $this->row;
            } else {
                return $this->db->fetchRow(Contents::alloc()->select()
                    ->where(
                        'table.contents.parent = ? AND (table.contents.type = ? OR table.contents.type = ?)',
                        $this->cid,
                        'post_draft',
                        'page_draft'
                    )
                    ->limit(1), [Contents::alloc(), 'filter']);
            }
        }

        return null;
    }
}
