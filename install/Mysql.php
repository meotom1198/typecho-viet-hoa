<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbHost"><?php _e('<i class="fa-solid fa-database"></i> Địa chỉ database'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="localhost"/>
        <p class="description"><?php _e('ㅤ+ Nên để mặc định là: "%s"', 'localhost'); ?></p>
    </li>
</ul>

<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbUser"><?php _e('<i class="fa-solid fa-database"></i> DatabaseUser'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="" />
        <p class="description"><?php _e('ㅤ+ Hãy điền DatabaseUser, hoặc bạn có thể nhập: "%s"', 'root'); ?></p>
    </li>
</ul>

<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbPassword"><?php _e('<i class="fa-solid fa-database"></i> DatabasePassword'); ?></label>
        <input type="password" class="text" name="dbPassword" id="dbPassword" value="" />
        <p class="description"><?php _e('ㅤ+ Hãy điền DatabasePassword!', 'root'); ?></p>
    </li>
</ul>
<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbDatabase"><?php _e('<i class="fa-solid fa-database"></i> DatabaseName'); ?></label>
        <input type="text" class="text" name="dbDatabase" id="dbDatabase" value="" />
        <p class="description"><?php _e('ㅤ+ Hãy điền DatabaseName!'); ?></p>
    </li>

</ul>

<details>
    <summary>
        <strong><?php _e('<i class="fa-solid fa-plus"></i> Tùy chọn nâng cao'); ?></strong>
    </summary>
    <ul class="typecho-option">
        <li>
            <label class="typecho-label" for="dbPort"><?php _e('<i class="fa-solid fa-database"></i> Cổng database'); ?></label>
            <input type="text" class="text" name="dbPort" id="dbPort" value="3306"/>
            <p class="description"><?php _e('ㅤ+ Nếu bạn không biết cái này có là gì, thì hãy để nó yên như thế, đừng chình sửa gì cả.'); ?></p>
        </li>
    </ul>

    <ul class="typecho-option">
        <li>
            <label class="typecho-label" for="dbCharset"><?php _e('<i class="fa-solid fa-file-invoice"></i> Mã hóa database'); ?></label>
            <select name="dbCharset" id="dbCharset">
                <option value="utf8mb4">== UTF8MB4 ==</option>
                <option value="utf8">== UTF8 ==</option>
            </select>
            <p class="description"><?php _e('ㅤ+ Việc chọn mã hóa UTF8MB4 yêu cầu phiên bản MySQL thấp nhất là 5.5.3 nhé!'); ?></p>
        </li>
    </ul>

    <ul class="typecho-option">
        <li>
            <label class="typecho-label" for="dbEngine"><?php _e('<i class="fa-solid fa-wrench"></i> Công cụ database'); ?></label>
            <select name="dbEngine" id="dbEngine">
                <option value="InnoDB">== InnoDB ==</option>
                <option value="MyISAM">== MyISAM ==</option>
            </select>
        </li>
    </ul>

    <ul class="typecho-option">
        <li>
            <label class="typecho-label" for="dbSslCa"><?php _e('<i class="fa-solid fa-lock"></i> Chứng chỉ SSL database'); ?></label>
            <input type="text" class="text" name="dbSslCa" id="dbSslCa"/>
            <p class="description"><?php _e('ㅤ+ Nếu database của bạn đã bật SSL, hãy điền vào ô đường dẫn chứng chỉ CA bên trên, nếu không hãy để trống ô bên trên.'); ?></p>
        </li>
    </ul>

    <ul class="typecho-option">
        <li>
            <label class="typecho-label" for="dbSslVerify"><?php _e('<i class="fa-solid fa-lock"></i> Bật xác minh chứng chỉ máy chủ SSL database'); ?></label>
            <select name="dbSslVerify" id="dbSslVerify">
                <option value="on"><?php _e('== Bật =='); ?></option>
                <option value="off"><?php _e('== Tắt =='); ?></option>
            </select>
        </li>
    </ul>
</details>
