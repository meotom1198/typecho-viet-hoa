<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Plugin;
use Typecho\Router;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * thành phần dữ liệu mô tả
 *
 * @property int $mid
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string $description
 * @property int $count
 * @property int $order
 * @property int $parent
 * @property-read string $theId
 * @property-read string $url
 * @property-read string $permalink
 * @property-read string $feedUrl
 * @property-read string $feedRssUrl
 * @property-read string $feedAtomUrl
 */
class Metas extends Base implements QueryInterface
{
    /**
     * Lấy tổng số bản ghi
     *
     * @param Query $condition Điều kiện tính toán
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition->select(['COUNT(mid)' => 'num'])->from('table.metas'))->num;
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
        // Tạo liên kết tĩnh
        $type = $value['type'];
        $routeExists = (null != Router::get($type));
        $tmpSlug = $value['slug'];
        $value['slug'] = urlencode($value['slug']);

        $value['url'] = $value['permalink'] = $routeExists ? Router::url($type, $value, $this->options->index) : '#';

        /** Tạo liên kết tổng hợp */
        /** RSS 2.0 */
        $value['feedUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedUrl) : '#';

        /** RSS 1.0 */
        $value['feedRssUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedRssUrl) : '#';

        /** ATOM 1.0 */
        $value['feedAtomUrl'] = $routeExists ? Router::url($type, $value, $this->options->feedAtomUrl) : '#';

        $value['slug'] = $tmpSlug;
        $value = Metas::pluginHandle()->filter($value, $this);
        return $value;
    }

    /**
     * Nhận sắp xếp tối đa
     *
     * @param string $type
     * @param int $parent
     * @return integer
     * @throws Exception
     */
    public function getMaxOrder(string $type, int $parent = 0): int
    {
        return $this->db->fetchObject($this->db->select(['MAX(order)' => 'maxOrder'])
            ->from('table.metas')
            ->where('type = ? AND parent = ?', $type, $parent))->maxOrder ?? 0;
    }

    /**
     * Sắp xếp dữ liệu theo trường sắp xếp
     *
     * @param array $metas
     * @param string $type
     */
    public function sort(array $metas, string $type)
    {
        foreach ($metas as $sort => $mid) {
            $this->update(
                ['order' => $sort + 1],
                $this->db->sql()->where('mid = ?', $mid)->where('type = ?', $type)
            );
        }
    }

    /**
     * Cập nhật bản ghi
     *
     * @param array $rows ghi lại giá trị cập nhật
     * @param Query $condition Cập nhật điều kiện
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        return $this->db->query($condition->update('table.metas')->rows($rows));
    }

    /**
     * Hợp nhất dữ liệu
     *
     * @param integer $mid Khóa chính dữ liệu
     * @param string $type kiểu dữ liệu
     * @param array $metas Các tập dữ liệu cần được hợp nhất
     * @throws Exception
     */
    public function merge(int $mid, string $type, array $metas)
    {
        $contents = array_column($this->db->fetchAll($this->select('cid')
            ->from('table.relationships')
            ->where('mid = ?', $mid)), 'cid');

        foreach ($metas as $meta) {
            if ($mid != $meta) {
                $existsContents = array_column($this->db->fetchAll($this->db
                    ->select('cid')->from('table.relationships')
                    ->where('mid = ?', $meta)), 'cid');

                $where = $this->db->sql()->where('mid = ? AND type = ?', $meta, $type);
                $this->delete($where);
                $diffContents = array_diff($existsContents, $contents);
                $this->db->query($this->db->delete('table.relationships')->where('mid = ?', $meta));

                foreach ($diffContents as $content) {
                    $this->db->query($this->db->insert('table.relationships')
                        ->rows(['mid' => $mid, 'cid' => $content]));
                    $contents[] = $content;
                }

                $this->update(['parent' => $mid], $this->db->sql()->where('parent = ?', $meta));
                unset($existsContents);
            }
        }

        $num = $this->db->fetchObject($this->db
            ->select(['COUNT(mid)' => 'num'])->from('table.relationships')
            ->where('table.relationships.mid = ?', $mid))->num;

        $this->update(['count' => $num], $this->db->sql()->where('mid = ?', $mid));
    }

    /**
     * Lấy đối tượng truy vấn ban đầu
     *
     * @return Query
     * @throws Exception
     */
    public function select(): Query
    {
        return $this->db->select()->from('table.metas');
    }

    /**
     * xóa bản ghi
     *
     * @param Query $condition xóa điều kiện
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        return $this->db->query($condition->delete('table.metas'));
    }

    /**
     * Nhận ID dựa trên thẻ
     *
     * @param mixed $inputTags tên thẻ
     * @return array|int
     * @throws Exception
     */
    public function scanTags($inputTags)
    {
        $tags = is_array($inputTags) ? $inputTags : [$inputTags];
        $result = [];

        foreach ($tags as $tag) {
            if (empty($tag)) {
                continue;
            }

            $row = $this->db->fetchRow($this->select()
                ->where('type = ?', 'tag')
                ->where('name = ?', $tag)->limit(1));

            if ($row) {
                $result[] = $row['mid'];
            } else {
                $slug = Common::slugName($tag);

                if ($slug) {
                    $result[] = $this->insert([
                        'name'  => $tag,
                        'slug'  => $slug,
                        'type'  => 'tag',
                        'count' => 0,
                        'order' => 0,
                    ]);
                }
            }
        }

        return is_array($inputTags) ? $result : current($result);
    }

    /**
     * chèn một bản ghi
     *
     * @param array $rows ghi lại giá trị chèn
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        return $this->db->query($this->db->insert('table.metas')->rows($rows));
    }

    /**
     * Làm sạch thẻ mà không có bất kỳ nội dung nào
     *
     * @throws Exception
     */
    public function clearTags()
    {
        // Xóa nhãn có số 0
        $tags = array_column($this->db->fetchAll($this->db->select('mid')
            ->from('table.metas')->where('type = ? AND count = ?', 'tags', 0)), 'mid');

        foreach ($tags as $tag) {
            // Xác nhận xem nó không còn được liên kết nữa
            $content = $this->db->fetchRow($this->db->select('cid')
                ->from('table.relationships')->where('mid = ?', $tag)
                ->limit(1));

            if (empty($content)) {
                $this->db->query($this->db->delete('table.metas')
                    ->where('mid = ?', $tag));
            }
        }
    }

    /**
     * Cập nhật thông tin về số lượng meta có liên quan dựa trên danh mục và trạng thái nội dung được chỉ định
     *
     * @param int $mid meta id
     * @param string $type loại
     * @param string $status tình trạng
     * @throws Exception
     */
    public function refreshCountByTypeAndStatus(int $mid, string $type, string $status = 'publish')
    {
        $num = $this->db->fetchObject($this->db->select(['COUNT(table.contents.cid)' => 'num'])->from('table.contents')
            ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid = ?', $mid)
            ->where('table.contents.type = ?', $type)
            ->where('table.contents.status = ?', $status))->num;

        $this->db->query($this->db->update('table.metas')->rows(['count' => $num])
            ->where('mid = ?', $mid));
    }

    /**
     * id neo
     *
     * @access protected
     * @return string
     */
    protected function ___theId(): string
    {
        return $this->type . '-' . $this->mid;
    }
}
