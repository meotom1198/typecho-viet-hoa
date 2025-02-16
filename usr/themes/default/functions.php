<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('Địa chỉ LOGO'),
        _t('Điền địa chỉ URL hình ảnh vào đây để thêm LOG trước tiêu đề trang webO')
    );

    $form->addInput($logoUrl);

    $sidebarBlock = new \Typecho\Widget\Helper\Form\Element\Checkbox(
        'sidebarBlock',
        [
            'ShowRecentPosts'    => _t('Hiển thị các bài viết mới nhất'),
            'ShowRecentComments' => _t('Hiển thị các bình luận gần đây'),
            'ShowCategory'       => _t('Hiển thị danh mục'),
            'ShowArchive'        => _t('Hiển thị kho lưu trữ'),
            'ShowOther'          => _t('Hiển thị linh tinh khác')
        ],
        ['ShowRecentPosts', 'ShowRecentComments', 'ShowCategory', 'ShowArchive', 'ShowOther'],
        _t('Hiển thị thanh bên')
    );

    $form->addInput($sidebarBlock->multiMode());
}

/*
function themeFields($layout)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('Địa chỉ LOGO'),
        _t('Điền địa chỉ URL hình ảnh vào đây để thêm LOGO trước tiêu đề trang web')
    );
    $layout->addItem($logoUrl);
}
*/
