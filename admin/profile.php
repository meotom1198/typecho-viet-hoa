<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="col-mb-12 col-tb-3">
                <p><a href="https://gravatar.com/emails/"
                      title="<?php _e('Đổi avatar của bạn trên Gravatar'); ?>"><?php echo '<img class="profile-avatar" src="' . \Typecho\Common::gravatarUrl($user->mail, 220, 'X', 'mm', $request->isSecure()) . '" alt="' . $user->screenName . '" />'; ?></a>
                </p>
                <h2><i class="fa fa-user" aria-hidden="true"></i> Thông tin cơ bản:</h2>
                    <ul>
                        <li>Tên tài khoản: <?php $user->name(); ?></li>
                        <?php _e('<li> Tổng: %s bài viết.</li>
                        <li> Tổng: %s bình luận.</li>
                        <li> Tổng: %s danh mục.</li>',
                        $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?>
                <li><?php
                    if ($user->logged > 0) {
                        $logged = new \Typecho\Date($user->logged);
                        _e('Đăng nhập lần cuối: %s', $logged->word());
                    }
                    ?></li>
                    </ul>
            </div>

            <div class="col-mb-12 col-tb-6 col-tb-offset-1 typecho-content-panel" role="form">
                <section>
                    <h3><i class="fa fa-user" aria-hidden="true"></i> <?php _e('Thông tin cá nhân'); ?></h3>
                    <?php \Widget\Users\Profile::alloc()->profileForm()->render(); ?>
                </section>

                <?php if ($user->pass('contributor', true)): ?>
                    <br>
                    <section id="writing-option">
                        <h3><i class="fa fa-book" aria-hidden="true"></i> <?php _e('Cài đặt đăng bài viết'); ?></h3>
                        <?php \Widget\Users\Profile::alloc()->optionsForm()->render(); ?>
                    </section>
                <?php endif; ?>

                <br>

                <section id="change-password">
                    <h3><i class="fa fa-key" aria-hidden="true"></i> <?php _e('Thay đổi mật khẩu'); ?></h3>
                    <?php \Widget\Users\Profile::alloc()->passwordForm()->render(); ?>
                </section>

                <?php \Widget\Users\Profile::alloc()->personalFormList(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
\Typecho\Plugin::factory('admin/profile.php')->bottom();
include 'footer.php';
?>
