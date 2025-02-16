<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<div class="col-mb-12 col-tb-8 col-tb-offset-2">

    <div class="error-page">
        <h2 class="post-title">404 - <?php _e('Không tìm thấy trang'); ?></h2>
        <p><?php _e('Trang bạn muốn xem đã bị di chuyển hoặc bị xóa. Bạn muốn tìm kiếm nó:'); ?></p>
        <form method="post">
            <p><input type="text" name="s" class="text" autofocus/></p>
            <p>
                <button type="submit" class="submit"><?php _e('Tìm kiếm'); ?></button>
            </p>
        </form>
    </div>

</div><!-- end #content-->
<?php $this->need('footer.php'); ?>
