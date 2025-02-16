<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
?>
<div class="main">
    <div class="container typecho-dashboard">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="col-mb-12 col-tb-6" role="main">
                    <?php _e('<h3><i class="fa fa-bar-chart" aria-hidden="true"></i> Thông số cơ bản:</h3>
                    <ul>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> Tổng %s bài viết.</li>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> Tổng %s bình luận.</li>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> Tổng %s danh mục.</li>
                    </ul>',
                        $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?>
            </div>

            <div class="col-mb-12 col-tb-6" role="complementary">
                    <?php if ($user->pass('contributor', true)): ?>
                    <h3><i class="fa fa-fighter-jet" aria-hidden="true"></i> Truy cập nhanh:</h3>
                    <ul>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> <a href="<?php $options->adminUrl('write-post.php'); ?>"><?php _e('Đăng bài viết'); ?></a></li>
                        <?php if ($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->waitingCommentsNum > 0): ?>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> <a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('Bình luận chờ duyệt'); ?></a><span class="balloon"><?php $stat->waitingCommentsNum(); ?></span></li>
                        <?php elseif ($stat->myWaitingCommentsNum > 0): ?>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> <a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('Bình luận chờ duyệt'); ?></a><span class="balloon"><?php $stat->myWaitingCommentsNum(); ?></span></li>
                        <?php endif; ?>
                        <?php if ($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->spamCommentsNum > 0): ?>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> <a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('Bình luận spam'); ?></a>
                                <span class="balloon"><?php $stat->spamCommentsNum(); ?></span></li>
                        <?php elseif ($stat->mySpamCommentsNum > 0): ?>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> <a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('Bình luận spam'); ?></a><span class="balloon"><?php $stat->mySpamCommentsNum(); ?></span></li>
                        <?php endif; ?>
                        <?php if ($user->pass('administrator', true)): ?>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> <a href="<?php $options->adminUrl('themes.php'); ?>"><?php _e('Quản lý giao diện'); ?></a></li>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> <a href="<?php $options->adminUrl('plugins.php'); ?>"><?php _e('Quản lý plugins'); ?></a></li>
                        <li><i class="fa fa-arrow-right" aria-hidden="true"></i> <a href="<?php $options->adminUrl('options-general.php'); ?>"><?php _e('Cài đặt chung'); ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    </ul>



            </div>

            <div class="col-mb-12 col-tb-6" role="complementary">
                <section class="latest-link">
                    <h3><i class="fa fa-book" aria-hidden="true"></i> <?php _e('Bài viết gần đây'); ?></h3>
                    <?php \Widget\Contents\Post\Recent::alloc('pageSize=10')->to($posts); ?>
                    <ul>
                        <?php if ($posts->have()): ?>
                            <?php while ($posts->next()): ?>
                                <li><i class="fa-regular fa-clock"></i> 
                                    <span><?php $posts->date('n.j'); ?></span>
                                    <a href="<?php $posts->permalink(); ?>" class="title"><?php $posts->title(); ?></a>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li><em><?php _e('Chưa có bài viết nào! :))'); ?></em></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>

            <div class="col-mb-12 col-tb-6" role="complementary">
                <section class="latest-link">
                    <h3><i class="fa fa-commenting" aria-hidden="true"></i> <?php _e('Bình luận gần đây'); ?></h3>
                    <ul>
                        <?php \Widget\Comments\Recent::alloc('pageSize=10')->to($comments); ?>
                        <?php if ($comments->have()): ?>
                            <?php while ($comments->next()): ?>
                                <li><i class="fa-regular fa-clock"></i>
                                    <span><?php $comments->date('n.j'); ?></span>
                                    <a href="<?php $comments->permalink(); ?>"
                                       class="title"><?php $comments->author(false); ?></a>:
                                    <?php $comments->excerpt(35, '...'); ?>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li><?php _e('Chưa có bình luận nào! :))'); ?></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>

            <div class="col-mb-12 col-tb-12" role="complementary">
                <section class="latest-link">
                    <h3><i class="fa fa-address-book" aria-hidden="true"></i> <?php _e('Nhật ký'); ?></h3>
                    <div id="typecho-message">
                        <ul>
                            <li><?php _e('Đọc...'); ?></li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script>
    $(document).ready(function () {
        var ul = $('#typecho-message ul'), cache = window.sessionStorage,
            html = cache ? cache.getItem('feed') : '',
            update = cache ? cache.getItem('update') : '';

        if (!!html) {
            ul.html(html);
        } else {
            html = '';
            $.get('<?php $options->index('/action/ajax?do=feed'); ?>', function (o) {
                for (var i = 0; i < o.length; i++) {
                    var item = o[i];
                    html += '<li><i class="fa-regular fa-clock"></i> <span>' + item.date + '</span> <a href="' + item.link + '" target="_blank">' + item.title
                        + '</a></li>';
                }

                ul.html(html);
                cache.setItem('feed', html);
            }, 'json');
        }

        function applyUpdate(update) {
            if (update.available) {
                $('<div class="update-check message error"><p>'
                    + '<?php _e('Phiên bản Typecho hiện tại là: %s'); ?>'.replace('%s', update.current) + '<br />'
                    + '<strong><a href="' + update.link + '" target="_blank">'
                    + '<?php _e('Phiên bản Typecho mới nhất là: %s'); ?>'.replace('%s', update.latest) + '</a></strong></p></div>')
                    .insertAfter('.typecho-page-title').effect('highlight');
            }
        }

        if (!!update) {
            applyUpdate($.parseJSON(update));
        } else {
            $.get('<?php $options->index('/action/ajax?do=checkVersion'); ?>', function (o, status, resp) {
                applyUpdate(o);
                cache.setItem('update', resp.responseText);
            }, 'json');
        }
    });

</script>
<?php include 'footer.php'; ?>
