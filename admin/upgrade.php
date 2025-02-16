<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <div id="typecho-welcome">
                    <form action="<?php echo $security->getTokenUrl(
                        \Typecho\Router::url('do', ['action' => 'upgrade', 'widget' => 'Upgrade'],
                            \Typecho\Common::url('index.php', $options->rootUrl))); ?>" method="post">
                        <h3><?php _e('Đã phát hiện phiên bản mới!'); ?></h3>
                        <ul>
                            <li><?php _e('Bạn đã cập nhật hệ thống, chúng tôi vẫn cần thực hiện một số bước tiếp theo để hoàn tất nâng cấp!'); ?></li>
                            <li><?php _e('Quá trình này sẽ nâng cấp hệ thống của bạn từ <strong>%s</strong> lên <strong>%s</strong>!', $options->version, \Typecho\Common::VERSION); ?></li>
                            <li><strong
                                    class="warning"><?php _e('Bạn nên <a href="%s">sao lưu dữ liệu</a> trước khi nâng cấp!', \Typecho\Common::url('backup.php', $options->adminUrl)); ?></strong>
                            </li>
                        </ul>
                        <p>
                            <button class="btn primary" type="submit"><?php _e('Hoàn tất nâng cấp &raquo;'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>
<script>
    (function () {
        if (window.sessionStorage) {
            sessionStorage.removeItem('update');
        }
    })();
</script>
<?php include 'footer.php'; ?>
