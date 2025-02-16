<?php
/**
 * Theme Mặc Định này sẽ được kích hoạt ngay khi bạn cài đặt xong mã nguồn <a href="https://wapvn.top" title="wapvn.top"><b style="color:orange;">Typecho Việt Hoá 1.2.1</b></a>!
 * 
 * @package Theme Mặc Định
 * @author Typecho Team
 * @version 1.2
 * @link http://typecho.wapvn.top
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>

<div class="col-mb-12 col-8" id="main" role="main">
    <?php while ($this->next()): ?>
        <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
            <h2 class="post-title" itemprop="name headline">
                <a itemprop="url"
                   href="<?php $this->permalink() ?>"><?php $this->title() ?></a>
            </h2>
            <ul class="post-meta">
                <li itemprop="author" itemscope itemtype="http://schema.org/Person"><?php _e('Tác giả:'); ?><a
                        itemprop="name" href="<?php $this->author->permalink(); ?>"
                        rel="author"><?php $this->author(); ?></a></li>
                <li><?php _e('Thời gian:'); ?>
                    <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date(); ?></time>
                </li>
                <li><?php _e('Danh mục: '); ?><?php $this->category(','); ?></li>
                <li itemprop="interactionCount">
                    <a itemprop="discussionUrl"
                       href="<?php $this->permalink() ?>#comments"><?php $this->commentsNum('Bình luận', '1 bình luận', '%d bình luận'); ?></a>
                </li>
            </ul>
            <div class="post-content" itemprop="articleBody">
                <?php $this->content('- Đọc phần còn lại -'); ?>
            </div>
        </article>
    <?php endwhile; ?>

    <?php $this->pageNav('&laquo; Trang trước', 'Trang sau &raquo;'); ?>
</div><!-- end #main-->

<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
