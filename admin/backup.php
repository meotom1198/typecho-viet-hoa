<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$actionUrl = $security->getTokenUrl(
    \Typecho\Router::url('do', array('action' => 'backup', 'widget' => 'Backup'),
        \Typecho\Common::url('index.php', $options->rootUrl)));

$backupFiles = \Widget\Backup::alloc()->listFiles();
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 col-tb-8">
                <div id="typecho-welcome">
                    <form action="<?php echo $actionUrl; ?>" method="post">
                    <h3><?php _e('Sao lưu dữ liệu'); ?></h3>
                    <ul>
                        <li><?php _e('Chức năng sao lưu này chỉ bao gồm <strong>dữ liệu nội dung</strong>, không liên quan đến bất kỳ <strong>thông tin cài đặt</strong> nào!'); ?></li>
                        <li><?php _e('Nếu dữ liệu của bạn quá lớn, bạn nên sử dụng sao lưu trong trang quản lý hosting để tránh gặp lỗi.'); ?></li>
                        <li><strong class="warning"><?php _e('Để giảm kích thước tệp sao lưu, bạn nên xóa dữ liệu không cần thiết trước khi sao lưu.'); ?></strong></li>
                    </ul>
                    <p><button class="btn primary" type="submit"><?php _e('Bắt đầu sao lưu &raquo;'); ?></button></p>
                        <input tabindex="1" type="hidden" name="do" value="export">
                    </form>
                </div>
            </div>

            <div id="backup-secondary" class="col-mb-12 col-tb-4" role="form">
                <h3><?php _e('Khôi phục dữ liệu'); ?></h3>
                <ul class="typecho-option-tabs clearfix">
                    <li class="active w-50"><a href="#from-upload"><?php _e('Tải lên'); ?></a></li>
                    <li class="w-50"><a href="#from-server"><?php _e('Từ máy chủ'); ?></a></li>
                </ul>

                <form action="<?php echo $actionUrl; ?>" id="from-upload" class="tab-content" method="post" enctype="multipart/form-data">
                    <ul class="typecho-option">
                        <li>
                            <input tabindex="2" id="backup-upload-file" name="file" type="file" class="file">
                        </li>
                    </ul>
                    <ul class="typecho-option typecho-option-submit">
                        <li>
                            <button tabindex="4" type="submit" class="btn primary"><?php _e('Tải lên và khôi phục &raquo;'); ?></button>
                            <input type="hidden" name="do" value="import">
                        </li>
                    </ul>
                </form>

                <form action="<?php echo $actionUrl; ?>" id="from-server" class="tab-content hidden" method="post">
                    <?php if (empty($backupFiles)): ?>
                    <ul class="typecho-option">
                        <li>
                            <p class="description"><?php _e('Sau khi tải file sao lưu lên thư mục %s của hosting theo cách thủ công, tùy chọn file sẽ xuất hiện ở đây.', __TYPECHO_BACKUP_DIR__); ?></p>
                        </li>
                    </ul>
                    <?php else: ?>
                    <ul class="typecho-option">
                        <li>
                            <label class="typecho-label" for="backup-select-file"><?php _e('Chọn tệp sao lưu để khôi phục dữ liệu'); ?></label>
                            <select tabindex="5" name="file" id="backup-select-file">
                                <?php foreach ($backupFiles as $file): ?>
                                    <option value="<?php echo $file; ?>"><?php echo $file; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </li>
                    </ul>
                    <?php endif; ?>
                    <ul class="typecho-option typecho-option-submit">
                        <li>
                            <button tabindex="7" type="submit" class="btn primary"><?php _e('Chọn và khôi phục &raquo;'); ?></button>
                            <input type="hidden" name="do" value="import">
                        </li>
                    </ul>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>
<script>
    $('#backup-secondary .typecho-option-tabs li').click(function() {
        $('#backup-secondary .typecho-option-tabs li').removeClass('active');
        $(this).addClass('active');
        $(this).parents('#backup-secondary').find('.tab-content').addClass('hidden');

        var selected_tab = $(this).find('a').attr('href');
        $(selected_tab).removeClass('hidden');

        return false;
    });

    $('#backup-secondary form').submit(function (e) {
        if (!confirm('<?php _e('Thao tác khôi phục sẽ xóa toàn bộ dữ liệu hiện có, bạn có muốn tiếp tục không?'); ?>')) {
            return false;
        }
    });
</script>
<?php include 'footer.php'; ?>
