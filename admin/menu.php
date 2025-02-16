<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<div class="typecho-head-nav clearfix" role="navigation">
    <button class="menu-bar"><?php _e('<i class="fa-regular fa-rectangle-list"></i> Menu'); ?></button>
    <nav id="typecho-nav-list">
        <?php $menu->output(); ?>
    </nav>
    <div class="operate">
        <?php \Typecho\Plugin::factory('admin/menu.php')->navBar(); ?><a title="<?php
        if ($user->logged > 0) {
            $logged = new \Typecho\Date($user->logged);
            _e('Lần đăng nhập cuối %s', $logged->word());
        }
        ?>" href="<?php $options->adminUrl('profile.php'); ?>" class="author"><i class="fa-regular fa-user"></i>  <?php $user->screenName(); ?></a><a
            class="exit" href="<?php $options->logoutUrl(); ?>"><?php _e('<i class="fa-solid fa-right-from-bracket"></i> Đăng xuất'); ?></a><a
            href="<?php $options->siteUrl(); ?>"><?php _e('<i class="fa-regular fa-eye"></i> Xem blog'); ?></a>
    </div>
</div>

