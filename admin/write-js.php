<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php \Typecho\Plugin::factory('admin/write-js.php')->write(); ?>
<?php \Widget\Metas\Tag\Cloud::alloc('sort=count&desc=1&limit=200')->to($tags); ?>

<script src="<?php $options->adminStaticUrl('js', 'timepicker.js'); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'tokeninput.js'); ?>"></script>
<script>
$(document).ready(function() {
    // kiểm soát ngày giờ
    $('#date').mask('9999-99-99 99:99').datetimepicker({
        currentText     :   '<?php _e('Bây giờ'); ?>',
        prevText        :   '<?php _e('Tháng trước'); ?>',
        nextText        :   '<?php _e('Tháng sau'); ?>',
        monthNames      :   ['<?php _e('Tháng 1'); ?>', '<?php _e('Tháng 2'); ?>', '<?php _e('Tháng 3'); ?>', '<?php _e('Tháng 4'); ?>',
            '<?php _e('Tháng 5'); ?>', '<?php _e('Tháng 6'); ?>', '<?php _e('Tháng 7'); ?>', '<?php _e('Tháng 8'); ?>',
            '<?php _e('Tháng 9'); ?>', '<?php _e('Tháng 10'); ?>', '<?php _e('Tháng 11'); ?>', '<?php _e('Tháng 12'); ?>'],
        dayNames        :   ['<?php _e('Chủ nhật'); ?>', '<?php _e('Thứ hai'); ?>', '<?php _e('Thứ ba'); ?>',
            '<?php _e('Thứ tư'); ?>', '<?php _e('Thứ năm'); ?>', '<?php _e('Thứ sáu'); ?>', '<?php _e('Thứ bảy'); ?>'],
        dayNamesShort   :   ['<?php _e('Chủ nhật'); ?>', '<?php _e('Thứ hai'); ?>', '<?php _e('Thứ ba'); ?>', '<?php _e('Thứ tư'); ?>',
            '<?php _e('Thứ năm'); ?>', '<?php _e('Thứ sáu'); ?>', '<?php _e('Thứ bảy'); ?>'],
        dayNamesMin     :   ['<?php _e('Ngày'); ?>', '<?php _e('Một'); ?>', '<?php _e('Hai'); ?>', '<?php _e('Ba'); ?>',
            '<?php _e('Bốn'); ?>', '<?php _e('Năm'); ?>', '<?php _e('Sáu'); ?>'],
        closeText       :   '<?php _e('Hoàn thành'); ?>',
        timeOnlyTitle   :   '<?php _e('Chọn thời gian'); ?>',
        timeText        :   '<?php _e('Thời gian'); ?>',
        hourText        :   '<?php _e('Giờ'); ?>',
        amNames         :   ['<?php _e('Buổi sáng'); ?>', 'A'],
        pmNames         :   ['<?php _e('Buổi chiều'); ?>', 'P'],
        minuteText      :   '<?php _e('phút'); ?>',
        secondText      :   '<?php _e('giây'); ?>',

        dateFormat      :   'yy-mm-dd',
        timezone        :   <?php $options->timezone(); ?> / 60,
        hour            :   (new Date()).getHours(),
        minute          :   (new Date()).getMinutes()
    });

    // tập trung
    $('#title').select();

    // tự động giãn văn bản
    Typecho.editorResize('text', '<?php $security->index('/action/ajax?do=editorResize'); ?>');

    // tag autocomplete gợi ý
    var tags = $('#tags'), tagsPre = [];
    
    if (tags.length > 0) {
        var items = tags.val().split(','), result = [];
        for (var i = 0; i < items.length; i ++) {
            var tag = items[i];

            if (!tag) {
                continue;
            }

            tagsPre.push({
                id      :   tag,
                tags    :   tag
            });
        }

        tags.tokenInput(<?php 
        $data = array();
        while ($tags->next()) {
            $data[] = array(
                'id'    =>  $tags->name,
                'tags'  =>  $tags->name
            );
        }
        echo json_encode($data);
        ?>, {
            propertyToSearch:   'tags',
            tokenValue      :   'tags',
            searchDelay     :   0,
            preventDuplicates   :   true,
            animateDropdown :   false,
            hintText        :   '<?php _e('Vui lòng nhập tên thẻ'); ?>',
            noResultsText   :   '<?php _e('Thẻ này không tồn tại, nhấn Enter để tạo nó'); ?>',
            prePopulate     :   tagsPre,

            onResult        :   function (result, query, val) {
                if (!query) {
                    return result;
                }

                if (!result) {
                    result = [];
                }

                if (!result[0] || result[0]['id'] != query) {
                    result.unshift({
                        id      :   val,
                        tags    :   val
                    });
                }

                return result.slice(0, 5);
            }
        });

        // tag autocomplete Cài đặt chiều rộng nhắc nhở
        $('#token-input-tags').focus(function() {
            var t = $('.token-input-dropdown'),
                offset = t.outerWidth() - t.width();
            t.width($('.token-input-list').outerWidth() - offset);
        });
    }

    // Chiều rộng thích ứng viết tắt
    var slug = $('#slug');

    if (slug.length > 0) {
        var wrap = $('<div />').css({
            'position'  :   'relative',
            'display'   :   'inline-block'
        }),
        justifySlug = $('<pre />').css({
            'display'   :   'block',
            'visibility':   'hidden',
            'height'    :   slug.height(),
            'padding'   :   '0 2px',
            'margin'    :   0
        }).insertAfter(slug.wrap(wrap).css({
            'left'      :   0,
            'top'       :   0,
            'minWidth'  :   '5px',
            'position'  :   'absolute',
            'width'     :   '100%'
        })), originalWidth = slug.width();

        function justifySlugWidth() {
            var val = slug.val();
            justifySlug.text(val.length > 0 ? val : '     ');
        }

        slug.bind('input propertychange', justifySlugWidth);
        justifySlugWidth();
    }

    // Hình ảnh và tập tin được chèn gốc
    Typecho.insertFileToEditor = function (file, url, isImage) {
        var textarea = $('#text'), sel = textarea.getSelection(),
            html = isImage ? '<img src="' + url + '" alt="' + file + '" />'
                : '<a href="' + url + '">' + file + '</a>',
            offset = (sel ? sel.start : 0) + html.length;

        textarea.replaceSelection(html);
        textarea.setSelection(offset, offset);
    };

    var submitted = false, form = $('form[name=write_post],form[name=write_page]').submit(function () {
        submitted = true;
    }), formAction = form.attr('action'),
        idInput = $('input[name=cid]'),
        cid = idInput.val(),
        draft = $('input[name=draft]'),
        draftId = draft.length > 0 ? draft.val() : 0,
        btnSave = $('#btn-save').removeAttr('name').removeAttr('value'),
        btnSubmit = $('#btn-submit').removeAttr('name').removeAttr('value'),
        btnPreview = $('#btn-preview'),
        doAction = $('<input type="hidden" name="do" value="publish" />').appendTo(form),
        locked = false,
        changed = false,
        autoSave = $('<span id="auto-save-message" class="left"></span>').prependTo('.submit'),
        lastSaveTime = null;

    $(':input', form).bind('input change', function (e) {
        var tagName = $(this).prop('tagName');

        if (tagName.match(/(input|textarea)/i) && e.type == 'change') {
            return;
        }

        changed = true;
    });

    form.bind('field', function () {
        changed = true;
    });

    // Gửi yêu cầu lưu
    function saveData(cb) {
        function callback(o) {
            lastSaveTime = o.time;
            cid = o.cid;
            draftId = o.draftId;
            idInput.val(cid);
            autoSave.text('<?php _e('Đã lưu!'); ?>' + ' (' + o.time + ')').effect('highlight', 1000);
            locked = false;

            btnSave.removeAttr('disabled');
            btnPreview.removeAttr('disabled');

            if (!!cb) {
                cb(o)
            }
        }

        changed = false;
        btnSave.attr('disabled', 'disabled');
        btnPreview.attr('disabled', 'disabled');
        autoSave.text('<?php _e('Đang lưu...'); ?>');

        if (typeof FormData !== 'undefined') {
            var data = new FormData(form.get(0));
            data.append('do', 'save');

            $.ajax({
                url: formAction,
                processData: false,
                contentType: false,
                type: 'POST',
                data: data,
                success: callback
            });
        } else {
            var data = form.serialize() + '&do=save';
            $.post(formAction, data, callback, 'json');
        }
    }

    // Tính toán bù thời gian tiết kiệm ánh sáng ban ngày
    var dstOffset = (function () {
        var d = new Date(),
            jan = new Date(d.getFullYear(), 0, 1),
            jul = new Date(d.getFullYear(), 6, 1),
            stdOffset = Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());

        return stdOffset - d.getTimezoneOffset();
    })();
    
    if (dstOffset > 0) {
        $('<input name="dst" type="hidden" />').appendTo(form).val(dstOffset);
    }

    // múi giờ
    $('<input name="timezone" type="hidden" />').appendTo(form).val(- (new Date).getTimezoneOffset() * 60);

    // Tự động lưu
<?php if ($options->autoSave): ?>
    var autoSaveOnce = !!cid;

    function autoSaveListener () {
        setInterval(function () {
            if (changed && !locked) {
                locked = true;
                saveData();
            }
        }, 10000);
    }

    if (autoSaveOnce) {
        autoSaveListener();
    }

    $('#text').bind('input propertychange', function () {
        if (!locked) {
            autoSave.text('<?php _e('Chưa lưu'); ?>' + (lastSaveTime ? ' (<?php _e('Thời gian lưu lần cuối'); ?>: ' + lastSaveTime + ')' : ''));
        }

        if (!autoSaveOnce) {
            autoSaveOnce = true;
            autoSaveListener();
        }
    });
<?php endif; ?>

    // Tự động phát hiện các trang thoát
    $(window).bind('beforeunload', function () {
        if (changed && !submitted) {
            return '<?php _e('Nội dung đã được thay đổi và chưa được lưu. Bạn có chắc chắn muốn rời khỏi trang này không?'); ?>';
        }
    });

    // Chức năng xem trước    var isFullScreen = false;

    function previewData(cid) {
        isFullScreen = $(document.body).hasClass('fullscreen');
        $(document.body).addClass('fullscreen preview');

        var frame = $('<iframe frameborder="0" class="preview-frame preview-loading"></iframe>')
            .attr('src', './preview.php?cid=' + cid)
            .attr('sandbox', 'allow-same-origin allow-scripts')
            .appendTo(document.body);

        frame.load(function () {
            frame.removeClass('preview-loading');
        });

        frame.height($(window).height() - 53);
    }

    function cancelPreview() {
        if (submitted) {
            return;
        }

        if (!isFullScreen) {
            $(document.body).removeClass('fullscreen');
        }

        $(document.body).removeClass('preview');
        $('.preview-frame').remove();
    };

    $('#btn-cancel-preview').click(cancelPreview);

    $(window).bind('message', function (e) {
        if (e.originalEvent.data == 'cancelPreview') {
            cancelPreview();
        }
    });

    btnPreview.click(function () {
        if (changed) {
            locked = true;

            if (confirm('<?php _e('Nội dung đã sửa đổi cần được lưu trước khi có thể xem trước. Bạn có muốn lưu nội dung đó không?'); ?>')) {
                saveData(function (o) {
                    previewData(o.draftId);
                });
            } else {
                locked = false;
            }
        } else if (!!draftId) {
            previewData(draftId);
        } else if (!!cid) {
            previewData(cid);
        }
    });

    btnSave.click(function () {
        doAction.attr('value', 'save');
    });

    btnSubmit.click(function () {
        doAction.attr('value', 'publish');
    });

    // Kiểm soát các tùy chọn chuyển đổi và tệp đính kèm
    var fileUploadInit = false;
    $('#edit-secondary .typecho-option-tabs li').click(function() {
        $('#edit-secondary .typecho-option-tabs li').removeClass('active');
        $(this).addClass('active');
        $(this).parents('#edit-secondary').find('.tab-content').addClass('hidden');
        
        var selected_tab = $(this).find('a').attr('href'),
            selected_el = $(selected_tab).removeClass('hidden');

        if (!fileUploadInit) {
            selected_el.trigger('init');
            fileUploadInit = true;
        }

        return false;
    });

    // Điều khiển tùy chọn nâng cao
    $('#advance-panel-btn').click(function() {
        $('#advance-panel').toggle();
        return false;
    });

    // Tự động ẩn hộp mật khẩu
    $('#visibility').change(function () {
        var val = $(this).val(), password = $('#post-password');

        if ('password' == val) {
            password.removeClass('hidden');
        } else {
            password.addClass('hidden');
        }
    });
    
    // Xác nhận xóa bản nháp
    $('.edit-draft-notice a').click(function () {
        if (confirm('<?php _e('Bạn có chắc chắn muốn xóa bản nháp này không?'); ?>')) {
            window.location.href = $(this).attr('href');
        }

        return false;
    });
});
</script>

