<?php

include 'common.php';

/** Nhận tiện ích nội dung */
\Widget\Archive::alloc('type=single&checkPermalink=0&preview=1')->to($content);

/** Kiểm tra xem có */
if (!$content->have()) {
    $response->redirect($options->adminUrl);
}

/** Kiểm tra quyền */
if (!$user->pass('editor', true) && $content->authorId != $user->uid) {
    $response->redirect($options->adminUrl);
}

/** Nội dung đầu ra */
$content->render();
?>
<script>
    window.onbeforeunload = function () {
        if (!!window.parent) {
            window.parent.postMessage('cancelPreview', '<?php $options->rootUrl(); ?>');
        }
    }
</script>
