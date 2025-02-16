<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Config;
use Typecho\Cookie;
use Typecho\Date;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Plugin;
use Typecho\Router;
use Typecho\Widget;
use Utils\AutoP;
use Utils\Markdown;
use Widget\Base;
use Widget\Metas\Category\Rows;
use Widget\Upload;
use Widget\Users\Author;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Lớp cơ sở nội dung
 *
 * @property int $cid
 * @property string $title
 * @property string $slug
 * @property int $created
 * @property int $modified
 * @property string $text
 * @property int $order
 * @property int $authorId
 * @property string $template
 * @property string $type
 * @property string $status
 * @property string|null $password
 * @property int $commentsNum
 * @property bool $allowComment
 * @property bool $allowPing
 * @property bool $allowFeed
 * @property int $parent
 * @property int $parentId
 * @property-read Users $author
 * @property-read string $permalink
 * @property-read string $url
 * @property-read string $feedUrl
 * @property-read string $feedRssUrl
 * @property-read string $feedAtomUrl
 * @property-read bool $isMarkdown
 * @property-read bool $hidden
 * @property-read string $category
 * @property-read Date $date
 * @property-read string $dateWord
 * @property-read string[] $directory
 * @property-read array $tags
 * @property-read array $categories
 * @property-read string $description
 * @property-read string $excerpt
 * @property-read string $summary
 * @property-read string $content
 * @property-read Config $fields
 * @property-read Config $attachment
 * @property-read string $theId
 * @property-read string $respondId
 * @property-read string $commentUrl
 * @property-read string $trackbackUrl
 * @property-read string $responseUrl
 */
class Contents extends Base implements QueryInterface
{
    /**
     * Nhận đối tượng truy vấn
     *
     * @return Query
     * @throws Exception
     */
    public function select(): Query
    {
        return $this->db->select(
            'table.contents.cid',
            'table.contents.title',
            'table.contents.slug',
            'table.contents.created',
            'table.contents.authorId',
            'table.contents.modified',
            'table.contents.type',
            'table.contents.status',
            'table.contents.text',
            'table.contents.commentsNum',
            'table.contents.order',
            'table.contents.template',
            'table.contents.password',
            'table.contents.allowComment',
            'table.contents.allowPing',
            'table.contents.allowFeed',
            'table.contents.parent'
        )->from('table.contents');
    }

    /**
     * Chèn nội dung
     *
     * @param array $rows mảng nội dung
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        /** Xây dựng cấu trúc chèn */
        $insertStruct = [
            'title'        => !isset($rows['title']) || strlen($rows['title']) === 0
                ? null : htmlspecialchars($rows['title']),
            'created'      => !isset($rows['created']) ? $this->options->time : $rows['created'],
            'modified'     => $this->options->time,
            'text'         => !isset($rows['text']) || strlen($rows['text']) === 0 ? null : $rows['text'],
            'order'        => empty($rows['order']) ? 0 : intval($rows['order']),
            'authorId'     => $rows['authorId'] ?? $this->user->uid,
            'template'     => empty($rows['template']) ? null : $rows['template'],
            'type'         => empty($rows['type']) ? 'post' : $rows['type'],
            'status'       => empty($rows['status']) ? 'publish' : $rows['status'],
            'password'     => !isset($rows['password']) || strlen($rows['password']) === 0 ? null : $rows['password'],
            'commentsNum'  => empty($rows['commentsNum']) ? 0 : $rows['commentsNum'],
            'allowComment' => !empty($rows['allowComment']) && 1 == $rows['allowComment'] ? 1 : 0,
            'allowPing'    => !empty($rows['allowPing']) && 1 == $rows['allowPing'] ? 1 : 0,
            'allowFeed'    => !empty($rows['allowFeed']) && 1 == $rows['allowFeed'] ? 1 : 0,
            'parent'       => empty($rows['parent']) ? 0 : intval($rows['parent'])
        ];

        if (!empty($rows['cid'])) {
            $insertStruct['cid'] = $rows['cid'];
        }

        /** Đầu tiên chèn một số dữ liệu */
        $insertId = $this->db->query($this->db->insert('table.contents')->rows($insertStruct));

        /** Cập nhật chữ viết tắt */
        if ($insertId > 0) {
            $this->applySlug(!isset($rows['slug']) || strlen($rows['slug']) === 0 ? null : $rows['slug'], $insertId);
        }

        return $insertId;
    }

    /**
     * Áp dụng chữ viết tắt cho nội dung
     *
     * @param string|null $slug viết tắt
     * @param mixed $cid Id nội dung
     * @return string
     * @throws Exception
     */
    public function applySlug(?string $slug, $cid): string
    {
        if ($cid instanceof Query) {
            $cid = $this->db->fetchObject($cid->select('cid')
                ->from('table.contents')->limit(1))->cid;
        }

        /** Tạo một từ viết tắt không trống */
        $slug = Common::slugName($slug, $cid);
        $result = $slug;

        /** Xử lý đặc biệt cho sên nháp */
        $draft = $this->db->fetchObject($this->db->select('type', 'parent')
            ->from('table.contents')->where('cid = ?', $cid));

        if ('_draft' == substr($draft->type, - 6) && $draft->parent) {
            $result = '@' . $result;
        }


        /** Xác định xem nó đã tồn tại trong cơ sở dữ liệu chưa */
        $count = 1;
        while (
            $this->db->fetchObject($this->db->select(['COUNT(cid)' => 'num'])
                ->from('table.contents')->where('slug = ? AND cid <> ?', $result, $cid))->num > 0
        ) {
            $result = $slug . '-' . $count;
            $count++;
        }

        $this->db->query($this->db->update('table.contents')->rows(['slug' => $result])
            ->where('cid = ?', $cid));

        return $result;
    }

    /**
     * Cập nhật nội dung
     *
     * @param array $rows mảng nội dung
     * @param Query $condition Cập nhật điều kiện
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        /** Đầu tiên xác minh quyền ghi */
        if (!$this->isWriteable(clone $condition)) {
            return 0;
        }

        /** Xây dựng cấu trúc cập nhật */
        $preUpdateStruct = [
            'title'        => !isset($rows['title']) || strlen($rows['title']) === 0
                ? null : htmlspecialchars($rows['title']),
            'order'        => empty($rows['order']) ? 0 : intval($rows['order']),
            'text'         => !isset($rows['text']) || strlen($rows['text']) === 0 ? null : $rows['text'],
            'template'     => empty($rows['template']) ? null : $rows['template'],
            'type'         => empty($rows['type']) ? 'post' : $rows['type'],
            'status'       => empty($rows['status']) ? 'publish' : $rows['status'],
            'password'     => empty($rows['password']) ? null : $rows['password'],
            'allowComment' => !empty($rows['allowComment']) && 1 == $rows['allowComment'] ? 1 : 0,
            'allowPing'    => !empty($rows['allowPing']) && 1 == $rows['allowPing'] ? 1 : 0,
            'allowFeed'    => !empty($rows['allowFeed']) && 1 == $rows['allowFeed'] ? 1 : 0,
            'parent'       => empty($rows['parent']) ? 0 : intval($rows['parent'])
        ];

        $updateStruct = [];
        foreach ($rows as $key => $val) {
            if (array_key_exists($key, $preUpdateStruct)) {
                $updateStruct[$key] = $preUpdateStruct[$key];
            }
        }

        /** Cập nhật thời gian tạo */
        if (isset($rows['created'])) {
            $updateStruct['created'] = $rows['created'];
        }

        $updateStruct['modified'] = $this->options->time;

        /** Đầu tiên chèn một số dữ liệu */
        $updateCondition = clone $condition;
        $updateRows = $this->db->query($condition->update('table.contents')->rows($updateStruct));

        /** Cập nhật chữ viết tắt */
        if ($updateRows > 0 && isset($rows['slug'])) {
            $this->applySlug(!isset($rows['slug']) || strlen($rows['slug']) === 0
                ? null : $rows['slug'], $updateCondition);
        }

        return $updateRows;
    }

    /**
     * Liệu nội dung có thể được sửa đổi
     *
     * @param Query $condition tình trạng
     * @return bool
     * @throws Exception
     */
    public function isWriteable(Query $condition): bool
    {
        $post = $this->db->fetchRow($condition->select('authorId')->from('table.contents')->limit(1));
        return $post && ($this->user->pass('editor', true) || $post['authorId'] == $this->user->uid);
    }

    /**
     * Xóa nội dung
     *
     * @param Query $condition Đối tượng truy vấn
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        return $this->db->query($condition->delete('table.contents'));
    }

    /**
     * Xóa trường tùy chỉnh
     *
     * @param integer $cid
     * @return integer
     * @throws Exception
     */
    public function deleteFields(int $cid): int
    {
        return $this->db->query($this->db->delete('table.fields')
            ->where('cid = ?', $cid));
    }

    /**
     * Lưu các trường tùy chỉnh
     *
     * @param array $fields
     * @param mixed $cid
     * @return void
     * @throws Exception
     */
    public function applyFields(array $fields, $cid)
    {
        $exists = array_flip(array_column($this->db->fetchAll($this->db->select('name')
            ->from('table.fields')->where('cid = ?', $cid)), 'name'));

        foreach ($fields as $name => $value) {
            $type = 'str';

            if (is_array($value) && 2 == count($value)) {
                $type = $value[0];
                $value = $value[1];
            } elseif (strpos($name, ':') > 0) {
                [$type, $name] = explode(':', $name, 2);
            }

            if (!$this->checkFieldName($name)) {
                continue;
            }

            $isFieldReadOnly = Contents::pluginHandle()->trigger($plugged)->isFieldReadOnly($name);
            if ($plugged && $isFieldReadOnly) {
                continue;
            }

            if (isset($exists[$name])) {
                unset($exists[$name]);
            }

            $this->setField($name, $type, $value, $cid);
        }

        foreach ($exists as $name => $value) {
            $this->db->query($this->db->delete('table.fields')
                ->where('cid = ? AND name = ?', $cid, $name));
        }
    }

    /**
     * Kiểm tra xem tên trường có đáp ứng yêu cầu không
     *
     * @param string $name
     * @return boolean
     */
    public function checkFieldName(string $name): bool
    {
        return preg_match("/^[_a-z][_a-z0-9]*$/i", $name);
    }

    /**
     * Đặt một trường duy nhất
     *
     * @param string $name
     * @param string $type
     * @param mixed $value
     * @param integer $cid
     * @return integer|bool
     * @throws Exception
     */
    public function setField(string $name, string $type, $value, int $cid)
    {
        if (
            empty($name) || !$this->checkFieldName($name)
            || !in_array($type, ['str', 'int', 'float', 'json'])
        ) {
            return false;
        }

        if ($type === 'json') {
            $value = json_encode($value);
        }

        $exist = $this->db->fetchRow($this->db->select('cid')->from('table.fields')
            ->where('cid = ? AND name = ?', $cid, $name));

        $rows = [
            'type'        => $type,
            'str_value'   => 'str' == $type || 'json' == $type ? $value : null,
            'int_value'   => 'int' == $type ? intval($value) : 0,
            'float_value' => 'float' == $type ? floatval($value) : 0
        ];

        if (empty($exist)) {
            $rows['cid'] = $cid;
            $rows['name'] = $name;

            return $this->db->query($this->db->insert('table.fields')->rows($rows));
        } else {
            return $this->db->query($this->db->update('table.fields')
                ->rows($rows)
                ->where('cid = ? AND name = ?', $cid, $name));
        }
    }

    /**
     * Thêm một trường số nguyên
     *
     * @param string $name
     * @param integer $value
     * @param integer $cid
     * @return integer
     * @throws Exception
     */
    public function incrIntField(string $name, int $value, int $cid)
    {
        if (!$this->checkFieldName($name)) {
            return false;
        }

        $exist = $this->db->fetchRow($this->db->select('type')->from('table.fields')
            ->where('cid = ? AND name = ?', $cid, $name));
        $value = intval($value);

        if (empty($exist)) {
            return $this->db->query($this->db->insert('table.fields')
                ->rows([
                    'cid'         => $cid,
                    'name'        => $name,
                    'type'        => 'int',
                    'str_value'   => null,
                    'int_value'   => $value,
                    'float_value' => 0
                ]));
        } else {
            $struct = [
                'str_value'   => null,
                'float_value' => null
            ];

            if ('int' != $exist['type']) {
                $struct['type'] = 'int';
            }

            return $this->db->query($this->db->update('table.fields')
                ->rows($struct)
                ->expression('int_value', 'int_value ' . ($value >= 0 ? '+' : '') . $value)
                ->where('cid = ? AND name = ?', $cid, $name));
        }
    }

    /**
     * Tính toán hàm lượng theo điều kiện
     *
     * @param Query $condition Đối tượng truy vấn
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition
            ->select(['COUNT(DISTINCT table.contents.cid)' => 'num'])
            ->from('table.contents')
            ->cleanAttribute('group'))->num;
    }

    /**
     * Nhận tất cả các mẫu tùy chỉnh hiện tại
     *
     * @return array
     */
    public function getTemplates(): array
    {
        $files = glob($this->options->themeFile($this->options->theme, '*.php'));
        $result = [];

        foreach ($files as $file) {
            $info = Plugin::parseInfo($file);
            $file = basename($file);

            if ('index.php' != $file && 'custom' == $info['title']) {
                $result[$file] = $info['description'];
            }
        }

        return $result;
    }

    /**
     * Đẩy giá trị của mỗi hàng vào ngăn xếp
     *
     * @param array $value giá trị của mỗi hàng
     * @return array
     */
    public function push(array $value): array
    {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * Bộ lọc phổ quát
     *
     * @param array $value Dữ liệu hàng cần được lọc
     * @return array
     */
    public function filter(array $value): array
    {
        /** Xử lý các giá trị null mặc định */
        $value['title'] = $value['title'] ?? '';
        $value['text'] = $value['text'] ?? '';
        $value['slug'] = $value['slug'] ?? '';

        /** Xóa tất cả danh mục */
        $value['categories'] = $this->db->fetchAll($this->db
            ->select()->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $value['cid'])
            ->where('table.metas.type = ?', 'category'), [Rows::alloc(), 'filter']);

        $value['category'] = '';
        $value['directory'] = [];

        /** Lấy danh mục đầu tiên làm điều kiện sên */
        if (!empty($value['categories'])) {
            /** 使用自定义排序 */
            usort($value['categories'], function ($a, $b) {
                $field = 'order';
                if ($a['order'] == $b['order']) {
                    $field = 'mid';
                }

                return $a[$field] < $b[$field] ? - 1 : 1;
            });

            $value['category'] = $value['categories'][0]['slug'];

            $value['directory'] = Rows::alloc()
                ->getAllParentsSlug($value['categories'][0]['mid']);
            $value['directory'][] = $value['category'];
        }

        $value['date'] = new Date($value['created']);

        /** Ngày thế hệ */
        $value['year'] = $value['date']->year;
        $value['month'] = $value['date']->month;
        $value['day'] = $value['date']->day;

        /** Tạo quyền truy cập */
        $value['hidden'] = false;

        /** Lấy loại tuyến đường và xác định xem loại tuyến đường này có tồn tại trong bảng định tuyến hay không */
        $type = $value['type'];
        $routeExists = (null != Router::get($type));

        $tmpSlug = $value['slug'];
        $tmpCategory = $value['category'];
        $tmpDirectory = $value['directory'];
        $value['slug'] = urlencode($value['slug']);
        $value['category'] = urlencode($value['category']);
        $value['directory'] = implode('/', array_map('urlencode', $value['directory']));

        /** Tạo đường dẫn tĩnh */
        $value['pathinfo'] = $routeExists ? Router::url($type, $value) : '#';

        /** Tạo liên kết tĩnh */
        $value['url'] = $value['permalink'] = Common::url($value['pathinfo'], $this->options->index);

        /** Xử lý tệp đính kèm */
        if ('attachment' == $type) {
            $content = @unserialize($value['text']);

            // Thêm thông tin dữ liệu
            $value['attachment'] = new Config($content);
            $value['attachment']->isImage = in_array($content['type'], ['jpg', 'jpeg', 'gif', 'png', 'tiff', 'bmp', 'webp', 'avif']);
            $value['attachment']->url = Upload::attachmentHandle($value);

            if ($value['attachment']->isImage) {
                $value['text'] = '<img src="' . $value['attachment']->url . '" alt="' .
                    $value['title'] . '" />';
            } else {
                $value['text'] = '<a href="' . $value['attachment']->url . '" title="' .
                    $value['title'] . '">' . $value['title'] . '</a>';
            }
        }

        /** xử lý Markdown **/
        if (isset($value['text'])) {
            $value['isMarkdown'] = (0 === strpos($value['text'], '<!--markdown-->'));
            if ($value['isMarkdown']) {
                $value['text'] = substr($value['text'], 15);
            }
        }

        /** Tạo liên kết tổng hợp */
        /** RSS 2.0 */
        $value['feedUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedUrl) : '#';

        /** RSS 1.0 */
        $value['feedRssUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedRssUrl) : '#';

        /** ATOM 1.0 */
        $value['feedAtomUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedAtomUrl) : '#';

        $value['slug'] = $tmpSlug;
        $value['category'] = $tmpCategory;
        $value['directory'] = $tmpDirectory;

        /** Xử lý quá trình bảo vệ mật khẩu */
        if (
            strlen($value['password'] ?? '') > 0 &&
            $value['password'] !== Cookie::get('protectPassword_' . $value['cid']) &&
            $value['authorId'] != $this->user->uid &&
            !$this->user->pass('editor', true)
        ) {
            $value['hidden'] = true;
        }

        $value = Contents::pluginHandle()->filter($value, $this);

        /** Nếu quyền truy cập bị từ chối */
        if ($value['hidden']) {
            $value['text'] = '<form class="protected" action="' . $this->security->getTokenUrl($value['permalink'])
                . '" method="post">' .
                '<p class="word">' . _t('Vui lòng nhập mật khẩu để truy cập') . '</p>' .
                '<p><input type="password" class="text" name="protectPassword" />
            <input type="hidden" name="protectCID" value="' . $value['cid'] . '" />
            <input type="submit" class="submit" value="' . _t('Gửi') . '" /></p>' .
                '</form>';

            $value['title'] = _t('Nội dung được bảo vệ bằng mật khẩu!');
            $value['tags'] = [];
            $value['commentsNum'] = 0;
        }

        return $value;
    }

    /**
     * Đầu ra ngày xuất bản bài viết
     *
     * @param string|null $format định dạng ngày
     */
    public function date(?string $format = null)
    {
        echo $this->date->format(empty($format) ? $this->options->postDateFormat : $format);
    }

    /**
     * Xuất nội dung bài viết
     *
     * @param mixed $more Hậu tố chặn bài viết
     */
    public function content($more = false)
    {
        echo false !== $more && false !== strpos($this->text, '<!--more-->') ?
            $this->excerpt
                . "<p class=\"more\"><a href=\"{$this->permalink}\" title=\"{$this->title}\">{$more}</a></p>"
            : $this->content;
    }

    /**
     * Tóm tắt bài viết đầu ra
     *
     * @param integer $length Độ dài cắt ngắn tóm tắt
     * @param string $trim hậu tố trừu tượng
     */
    public function excerpt(int $length = 100, string $trim = '...')
    {
        echo Common::subStr(strip_tags($this->excerpt), 0, $length, $trim);
    }

    /**
     * Tiêu đề đầu ra
     *
     * @param integer $length Độ dài cắt ngắn tiêu đề
     * @param string $trim hậu tố chặn
     */
    public function title(int $length = 0, string $trim = '...')
    {
        $title = Contents::pluginHandle()->trigger($plugged)->title($this->title, $this);
        if (!$plugged) {
            echo $length > 0 ? Common::subStr($this->title, 0, $length, $trim) : $this->title;
        } else {
            echo $title;
        }
    }

    /**
     * Xuất ra số lượng bình luận bài viết
     *
     * @param ...$args
     */
    public function commentsNum(...$args)
    {
        if (empty($args)) {
            $args[] = '%d';
        }

        $num = intval($this->commentsNum);
        echo sprintf($args[$num] ?? array_pop($args), $num);
    }

    /**
     * Nhận quyền bài viết
     *
     * @param ...$permissions
     */
    public function allow(...$permissions): bool
    {
        $allow = true;

        foreach ($permissions as $permission) {
            $permission = strtolower($permission);

            if ('edit' == $permission) {
                $allow &= ($this->user->pass('editor', true) || $this->authorId == $this->user->uid);
            } else {
                /** Hỗ trợ tự động đóng chức năng phản hồi */
                if (
                    ('ping' == $permission || 'comment' == $permission) && $this->options->commentsPostTimeout > 0 &&
                    $this->options->commentsAutoClose
                ) {
                    if ($this->options->time - $this->created > $this->options->commentsPostTimeout) {
                        return false;
                    }
                }

                $allow &= ($this->row['allow' . ucfirst($permission)] == 1) and !$this->hidden;
            }
        }

        return $allow;
    }

    /**
     * Phân loại bài viết đầu ra
     *
     * @param string $split Dấu phân cách giữa nhiều danh mục
     * @param boolean $link Có xuất liên kết hay không
     * @param string|null $default Nếu không thì xuất ra
     */
    public function category(string $split = ',', bool $link = true, ?string $default = null)
    {
        $categories = $this->categories;
        if ($categories) {
            $result = [];

            foreach ($categories as $category) {
                $result[] = $link ? '<a href="' . $category['permalink'] . '">'
                    . $category['name'] . '</a>' : $category['name'];
            }

            echo implode($split, $result);
        } else {
            echo $default;
        }
    }

    /**
     * Đầu ra bài viết phân loại đa cấp
     *
     * @param string $split Dấu phân cách giữa nhiều danh mục
     * @param boolean $link Có xuất liên kết hay không
     * @param string|null $default Nếu không thì xuất ra
     * @throws \Typecho\Widget\Exception
     */
    public function directory(string $split = '/', bool $link = true, ?string $default = null)
    {
        $category = $this->categories[0];
        $directory = Rows::alloc()->getAllParents($category['mid']);
        $directory[] = $category;

        if ($directory) {
            $result = [];

            foreach ($directory as $category) {
                $result[] = $link ? '<a href="' . $category['permalink'] . '">'
                    . $category['name'] . '</a>' : $category['name'];
            }

            echo implode($split, $result);
        } else {
            echo $default;
        }
    }

    /**
     * Thẻ bài viết đầu ra
     *
     * @param string $split Dấu phân cách giữa nhiều thẻ
     * @param boolean $link Có xuất liên kết hay không
     * @param string|null $default Nếu không thì xuất ra
     */
    public function tags(string $split = ',', bool $link = true, ?string $default = null)
    {
        /** Xóa thẻ */
        if ($this->tags) {
            $result = [];
            foreach ($this->tags as $tag) {
                $result[] = $link ? '<a href="' . $tag['permalink'] . '">'
                    . $tag['name'] . '</a>' : $tag['name'];
            }

            echo implode($split, $result);
        } else {
            echo $default;
        }
    }

    /**
     * Xuất tác giả hiện tại
     *
     * @param string $item Các mục cần xuất ra
     */
    public function author(string $item = 'screenName')
    {
        if ($this->have()) {
            echo $this->author->{$item};
        }
    }

    /**
     * Xóa thẻ
     *
     * @return array
     * @throws Exception
     */
    protected function ___tags(): array
    {
        return $this->db->fetchAll($this->db
            ->select()->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $this->cid)
            ->where('table.metas.type = ?', 'tag'), [Metas::alloc(), 'filter']);
    }

    /**
     * tác giả bài viết
     *
     * @return Users
     */
    protected function ___author(): Users
    {
        return Author::allocWithAlias($this->cid, ['uid' => $this->authorId]);
    }

    /**
     * Lấy ngày từ vựng hóa
     *
     * @return string
     */
    protected function ___dateWord(): string
    {
        return $this->date->word();
    }

    /**
     * Nhận id cha
     *
     * @return int|null
     */
    protected function ___parentId(): ?int
    {
        return $this->row['parent'];
    }

    /**
     * Một mô tả ngắn gọn chỉ bằng văn bản của bài viết
     *
     * @return string|null
     */
    protected function ___description(): ?string
    {
        $plainTxt = str_replace("\n", '', trim(strip_tags($this->excerpt)));
        $plainTxt = $plainTxt ? $plainTxt : $this->title;
        return Common::subStr($plainTxt, 0, 100, '...');
    }

    /**
     * ___fields
     *
     * @return Config
     * @throws Exception
     */
    protected function ___fields(): Config
    {
        $fields = [];
        $rows = $this->db->fetchAll($this->db->select()->from('table.fields')
            ->where('cid = ?', $this->cid));

        foreach ($rows as $row) {
            $value = 'json' == $row['type'] ? json_decode($row['str_value'], true) : $row[$row['type'] . '_value'];
            $fields[$row['name']] = $value;
        }

        return new Config($fields);
    }

    /**
     * Nhận tóm tắt nội dung bài viết
     *
     * @return string|null
     */
    protected function ___excerpt(): ?string
    {
        if ($this->hidden) {
            return $this->text;
        }

        $content = Contents::pluginHandle()->trigger($plugged)->excerpt($this->text, $this);
        if (!$plugged) {
            $content = $this->isMarkdown ? $this->markdown($content)
                : $this->autoP($content);
        }

        $contents = explode('<!--more-->', $content);
        [$excerpt] = $contents;

        return Common::fixHtml(Contents::pluginHandle()->excerptEx($excerpt, $this));
    }

    /**
     * markdown
     *
     * @param string|null $text
     * @return string|null
     */
    public function markdown(?string $text): ?string
    {
        $html = Contents::pluginHandle()->trigger($parsed)->markdown($text);

        if (!$parsed) {
            $html = Markdown::convert($text);
        }

        return $html;
    }

    /**
     * autoP
     *
     * @param string|null $text
     * @return string|null
     */
    public function autoP(?string $text): ?string
    {
        $html = Contents::pluginHandle()->trigger($parsed)->autoP($text);

        if (!$parsed && $text) {
            static $parser;

            if (empty($parser)) {
                $parser = new AutoP();
            }

            $html = $parser->parse($text);
        }

        return $html;
    }

    /**
     * Lấy nội dung bài viết
     *
     * @return string|null
     */
    protected function ___content(): ?string
    {
        if ($this->hidden) {
            return $this->text;
        }

        $content = Contents::pluginHandle()->trigger($plugged)->content($this->text, $this);

        if (!$plugged) {
            $content = $this->isMarkdown ? $this->markdown($content)
                : $this->autoP($content);
        }

        return Contents::pluginHandle()->contentEx($content, $this);
    }

    /**
     * Xuất dòng đầu tiên của bài viết dưới dạng tóm tắt
     *
     * @return string|null
     */
    protected function ___summary(): ?string
    {
        $content = $this->content;
        $parts = preg_split("/(<\/\s*(?:p|blockquote|q|pre|table)\s*>)/i", $content, 2, PREG_SPLIT_DELIM_CAPTURE);
        if (!empty($parts)) {
            $content = $parts[0] . $parts[1];
        }

        return $content;
    }

    /**
     * id neo
     *
     * @return string
     */
    protected function ___theId(): string
    {
        return $this->type . '-' . $this->cid;
    }

    /**
     * Id hộp trả lời
     *
     * @return string
     */
    protected function ___respondId(): string
    {
        return 'respond-' . $this->theId;
    }

    /**
     * địa chỉ bình luận
     *
     * @return string
     */
    protected function ___commentUrl(): string
    {
        /** Tạo địa chỉ phản hồi */
        /** Bình luận */
        return Router::url(
            'feedback',
            ['type' => 'comment', 'permalink' => $this->pathinfo],
            $this->options->index
        );
    }

    /**
     * địa chỉ theo dõi
     *
     * @return string
     */
    protected function ___trackbackUrl(): string
    {
        return Router::url(
            'feedback',
            ['type' => 'trackback', 'permalink' => $this->pathinfo],
            $this->options->index
        );
    }

    /**
     * địa chỉ trả lời
     *
     * @return string
     */
    protected function ___responseUrl(): string
    {
        return $this->permalink . '#' . $this->respondId;
    }

    /**
     * Nhận phần bù trang
     *
     * @param string $column Tên trường
     * @param integer $offset giá trị bù đắp
     * @param string $type kiểu
     * @param string|null $status giá trị trạng thái
     * @param integer $authorId tác giả
     * @param integer $pageSize giá trị phân trang
     * @return integer
     * @throws Exception
     */
    protected function getPageOffset(
        string $column,
        int $offset,
        string $type,
        ?string $status = null,
        int $authorId = 0,
        int $pageSize = 20
    ): int {
        $select = $this->db->select(['COUNT(table.contents.cid)' => 'num'])->from('table.contents')
            ->where("table.contents.{$column} > {$offset}")
            ->where(
                "table.contents.type = ? OR (table.contents.type = ? AND table.contents.parent = ?)",
                $type,
                $type . '_draft',
                0
            );

        if (!empty($status)) {
            $select->where("table.contents.status = ?", $status);
        }

        if ($authorId > 0) {
            $select->where('table.contents.authorId = ?', $authorId);
        }

        $count = $this->db->fetchObject($select)->num + 1;
        return ceil($count / $pageSize);
    }
}
