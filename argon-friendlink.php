<?php
/*
Plugin Name: Argon Friendlink 管理插件
Description: 提供友情链接自助申请、后台审核、通过/拒绝、下架/重新上架、邮件通知，支持自动建表与自定义邮件跳转页面。
Version: 1.4
Author: Timozaici
Author URI: https://github.com/timozaici
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 5.5
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page(
        '友链审核',
        '友链审核',
        'manage_options',
        'argon_friendlink_admin',
        'argon_friendlink_admin_page',
        'dashicons-admin-links',
        30
    );
});

/**
 * 生成“审核通过”邮件 HTML。
 * 采用 Argon 主题配色（主色 #5e72e4、正文 #32325d / #525f7f、辅助文字 #8898aa），
 * 使用表格布局 + 内联样式保证邮件客户端兼容，并通过媒体查询适配手机端。
 *
 * @param string $link_name 站点名称（输出前转义，数据库仍保持原样）
 * @param string $time      审核时间
 * @return string
 */
function argon_friendlink_approval_email_html($link_name, $time) {
    $site_name = get_bloginfo('name');
    if ($site_name === '') {
        $site_name = '本站';
    }
    $site_name = esc_html($site_name);
    $site_url  = esc_url(home_url('/', 'https'));

    // 后台可选择一个已发布页面作为“审核通过”邮件按钮的跳转目标
    $selected_page_id = intval(get_option('argon_friendlink_links_page_id', 0));
    $link_page = '';
    if (
        $selected_page_id > 0
        && get_post_type($selected_page_id) === 'page'
        && get_post_status($selected_page_id) === 'publish'
    ) {
        $selected_page_url = get_permalink($selected_page_id);
        if ($selected_page_url) {
            $link_page = esc_url(set_url_scheme($selected_page_url, 'https'));
        }
    }
    if ($link_page === '') {
        $link_page = esc_url(home_url('/link.html', 'https'));
    }

    $safe_name = esc_html($link_name);
    $safe_time = esc_html($time);

    return <<<EMAILHTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<title>友情链接申请已通过</title>
<style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; background-color: #f4f5f9; }
    .email-container { width: 600px; }
    @media only screen and (max-width: 620px) {
        .email-outer { padding: 20px 10px !important; }
        .email-container { width: 100% !important; max-width: 100% !important; }
        .email-card { padding: 24px 20px 22px !important; }
        .email-title { font-size: 20px !important; line-height: 1.35 !important; }
        .email-detail { padding: 14px 16px !important; font-size: 14px !important; }
        .email-btn { display: block !important; width: 100% !important; padding: 14px 0 !important; text-align: center !important; }
        .email-footer { padding: 16px 12px 0 !important; }
    }
</style>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f9;">
<div style="display:none; font-size:1px; color:#f4f5f9; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    您提交的站点 {$safe_name} 已通过友情链接审核。
</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; background-color:#f4f5f9; border-collapse:collapse;">
    <tr>
        <td class="email-outer" align="center" style="padding:32px 12px;">
            <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; border-collapse:collapse;">
                <tr>
                    <td style="padding:0 4px 14px 4px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'PingFang SC','Hiragino Sans GB','Microsoft YaHei',sans-serif; font-size:13px; line-height:1.5; color:#8898aa;">
                        {$site_name}
                    </td>
                </tr>
                <tr>
                    <td style="background-color:#ffffff; border:1px solid #e6e9f2; border-radius:10px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td class="email-card" style="padding:36px 36px 30px 36px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'PingFang SC','Hiragino Sans GB','Microsoft YaHei',sans-serif; font-size:16px; line-height:1.8; color:#525f7f;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse;">
                                        <tr>
                                            <td width="44" valign="middle" style="width:44px;">
                                                <table role="presentation" width="44" cellpadding="0" cellspacing="0" border="0" style="width:44px; border-collapse:collapse;">
                                                    <tr>
                                                        <td width="44" height="44" align="center" valign="middle" style="width:44px; height:44px; background-color:#eaf9f1; border-radius:22px; color:#2dce89; font-size:22px; font-weight:700; line-height:44px;">&#10003;</td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign="middle" style="padding-left:14px;">
                                                <div style="font-size:11px; line-height:1.4; letter-spacing:1px; color:#8898aa;">FRIEND LINK</div>
                                                <div class="email-title" style="margin-top:3px; font-size:24px; line-height:1.3; font-weight:600; color:#32325d;">友情链接申请已通过</div>
                                            </td>
                                        </tr>
                                    </table>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; margin-top:26px; border-collapse:collapse;">
                                        <tr>
                                            <td style="height:1px; background-color:#eef0f7; font-size:0; line-height:0;">&nbsp;</td>
                                        </tr>
                                    </table>
                                    <p style="margin:24px 0 0 0; font-size:16px; line-height:1.8; color:#525f7f;">您好，</p>
                                    <p style="margin:6px 0 0 0; font-size:16px; line-height:1.8; color:#525f7f;">
                                        您提交的站点 <strong style="color:#32325d;">{$safe_name}</strong> 已通过审核，现已展示在本站的友情链接页面。
                                    </p>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; margin-top:24px; border-collapse:separate; border-spacing:0;">
                                        <tr>
                                            <td class="email-detail" style="padding:18px 22px; background-color:#f7f8fc; border-left:4px solid #5e72e4; border-radius:6px; font-size:15px; line-height:1.8; color:#525f7f;">
                                                <div style="padding:2px 0;"><span style="color:#8898aa;">站点名称：</span><strong style="color:#32325d;">{$safe_name}</strong></div>
                                                <div style="padding:2px 0;"><span style="color:#8898aa;">审核时间：</span><span style="color:#32325d;">{$safe_time}</span></div>
                                            </td>
                                        </tr>
                                    </table>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; margin-top:28px; border-collapse:collapse;">
                                        <tr>
                                            <td align="center">
                                                <a href="{$link_page}" class="email-btn" style="display:inline-block; padding:14px 36px; background-color:#5e72e4; border-radius:6px; color:#ffffff; font-size:16px; font-weight:600; line-height:1.2; text-decoration:none;">前往查看友情链接</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style="margin:26px 0 0 0; font-size:13px; line-height:1.7; color:#8898aa; text-align:center;">感谢您与本站交换友情链接。</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-footer" style="padding:18px 12px 0 12px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'PingFang SC','Hiragino Sans GB','Microsoft YaHei',sans-serif; font-size:12px; line-height:1.8; color:#a6b0c3; text-align:center;">
                        本邮件由 {$site_name} 系统自动发送，请勿直接回复。<br>
                        <a href="{$site_url}" style="color:#5e72e4; text-decoration:none;">{$site_url}</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
EMAILHTML;
}

/**
 * 找到申请对应的 wp_links 记录 ID。
 * 优先使用申请表中保存的 wp_link_id；历史数据则按 URL / 名称回退匹配。
 *
 * @param array $app 申请表记录
 * @return int
 */
function argon_friendlink_resolve_wp_link_id($app) {
    global $wpdb;
    $links_table = $wpdb->prefix . 'links';

    $stored = isset($app['wp_link_id']) ? intval($app['wp_link_id']) : 0;
    if ($stored > 0) {
        $found = $wpdb->get_var($wpdb->prepare("SELECT link_id FROM $links_table WHERE link_id = %d", $stored));
        if ($found) {
            return intval($found);
        }
    }

    $url = isset($app['link_url']) ? $app['link_url'] : '';
    if ($url !== '') {
        $found = $wpdb->get_var($wpdb->prepare("SELECT link_id FROM $links_table WHERE link_url = %s ORDER BY link_id DESC LIMIT 1", $url));
        if ($found) {
            return intval($found);
        }
    }

    $name = isset($app['link_name']) ? $app['link_name'] : '';
    if ($name !== '') {
        $found = $wpdb->get_var($wpdb->prepare("SELECT link_id FROM $links_table WHERE link_name = %s ORDER BY link_id DESC LIMIT 1", $name));
        if ($found) {
            return intval($found);
        }
    }

    return 0;
}

/**
 * 创建友链申请表（自动使用当前 WordPress 表前缀）。
 *
 * @return bool 表是否可用
 */
function argon_friendlink_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'friendlink_applications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `$table` (
        `link_id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `link_url` varchar(255) NOT NULL DEFAULT '',
        `link_name` varchar(255) NOT NULL DEFAULT '',
        `link_image` varchar(255) NOT NULL DEFAULT '',
        `link_target` varchar(25) NOT NULL DEFAULT '',
        `link_description` varchar(255) NOT NULL DEFAULT '',
        `link_visible` varchar(20) NOT NULL DEFAULT 'N',
        `link_owner` bigint unsigned NOT NULL DEFAULT '1',
        `link_rating` int NOT NULL DEFAULT '0',
        `link_updated` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        `link_rel` varchar(255) NOT NULL DEFAULT '',
        `link_notes` mediumtext NOT NULL,
        `link_rss` varchar(255) NOT NULL DEFAULT '',
        `link_email` varchar(255) NOT NULL DEFAULT '',
        `status` enum('pending','approved','rejected','offline') NOT NULL DEFAULT 'pending' COMMENT '审核状态',
        `wp_link_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '对应 wp_links.link_id',
        PRIMARY KEY (`link_id`)
    ) ENGINE=InnoDB $charset_collate COMMENT='友情链接审核表';";

    $wpdb->query($sql);

    return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
}

/**
 * 插件启用时：自动建表并升级结构。
 */
function argon_friendlink_activate() {
    argon_friendlink_create_table();
    argon_friendlink_maybe_upgrade_db(true);
}
register_activation_hook(__FILE__, 'argon_friendlink_activate');

/**
 * 数据库结构升级：
 * - 表不存在时自动创建
 * - status 枚举增加 offline（已下架）
 * - 新增 wp_link_id 字段，记录对应的 wp_links.link_id
 * - 回填历史已通过申请的映射关系
 *
 * @param bool $force 是否忽略版本号强制检查
 */
function argon_friendlink_maybe_upgrade_db($force = false) {
    $db_version = '20261117';

    if (!$force && get_option('argon_friendlink_db_version') === $db_version) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'friendlink_applications';

    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
        argon_friendlink_create_table();
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            // 建表失败：不写入版本号，后续后台请求会继续重试
            return;
        }
    }

    $has_wp_link_id = $wpdb->get_var("SHOW COLUMNS FROM `$table` LIKE 'wp_link_id'");
    if (!$has_wp_link_id) {
        $wpdb->query("ALTER TABLE `$table` ADD COLUMN `wp_link_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '对应 wp_links.link_id' AFTER `status`");
    }

    $status_column = $wpdb->get_row("SHOW FULL COLUMNS FROM `$table` LIKE 'status'");
    if ($status_column && strpos($status_column->Type, "'offline'") === false) {
        $collate = !empty($status_column->Collation) ? " COLLATE {$status_column->Collation}" : '';
        $wpdb->query("ALTER TABLE `$table` MODIFY COLUMN `status` enum('pending','approved','rejected','offline'){$collate} NOT NULL DEFAULT 'pending' COMMENT '审核状态'");
    }

    // 回填历史已通过申请的 wp_links 映射
    $rows = $wpdb->get_results("SELECT * FROM `$table` WHERE status = 'approved' AND wp_link_id = 0", ARRAY_A);
    if ($rows) {
        foreach ($rows as $row) {
            $resolved = argon_friendlink_resolve_wp_link_id($row);
            if ($resolved) {
                $wpdb->update($table, ['wp_link_id' => $resolved], ['link_id' => intval($row['link_id'])]);
            }
        }
    }

    update_option('argon_friendlink_db_version', $db_version);
}
add_action('admin_init', 'argon_friendlink_maybe_upgrade_db');

function argon_friendlink_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('您没有权限访问此页面。');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'friendlink_applications';
    $links_table = $wpdb->prefix . 'links';

    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
        echo '<div class="wrap"><h1>友情链接申请审核</h1>';
        echo '<div class="error"><p>数据表创建失败，请检查数据库权限，或手动执行插件目录下的 install.sql。</p></div>';
        echo '</div>';
        return;
    }

    // 保存“审核通过邮件跳转页面”设置
    if (isset($_POST['argon_friendlink_save_settings'])) {
        $settings_nonce = isset($_POST['argon_friendlink_settings_nonce']) && is_string($_POST['argon_friendlink_settings_nonce'])
            ? wp_unslash($_POST['argon_friendlink_settings_nonce'])
            : '';
        if (!wp_verify_nonce($settings_nonce, 'argon_friendlink_save_settings')) {
            echo '<div class="error"><p>设置校验失败，请刷新页面后重试。</p></div>';
        } else {
            $page_id = isset($_POST['argon_friendlink_links_page_id']) && is_scalar($_POST['argon_friendlink_links_page_id'])
                ? intval($_POST['argon_friendlink_links_page_id'])
                : 0;
            if ($page_id > 0 && (get_post_type($page_id) !== 'page' || get_post_status($page_id) !== 'publish')) {
                $page_id = 0;
            }
            update_option('argon_friendlink_links_page_id', $page_id);
            echo '<div class="updated"><p>邮件跳转页面已保存。</p></div>';
        }
    }

    // 后台操作 nonce 校验
    $admin_nonce = 'argon_friendlink_admin_actions';
    $admin_nonce_value = isset($_POST['argon_friendlink_admin_nonce']) && is_string($_POST['argon_friendlink_admin_nonce'])
        ? wp_unslash($_POST['argon_friendlink_admin_nonce'])
        : '';
    $admin_nonce_valid = wp_verify_nonce($admin_nonce_value, $admin_nonce);
    $has_admin_action = isset($_POST['approve']) || isset($_POST['reject']) || isset($_POST['offline']) || isset($_POST['relist']);
    if ($has_admin_action && !$admin_nonce_valid) {
        echo '<div class="error"><p>操作校验失败，请刷新页面后重试。</p></div>';
    }

    // 处理审核通过
    if ($admin_nonce_valid && isset($_POST['approve']) && is_array($_POST['approve'])) {
        foreach ($_POST['approve'] as $id) {
            $id = intval($id);
            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE link_id = %d", $id), ARRAY_A);
            if ($link && $link['status'] === 'pending') {
                $link_name = $link['link_name'];
                $email = $link['link_email'];

                // 插入到主链接表
                $insert_data = $link;
                unset($insert_data['link_id'], $insert_data['link_email'], $insert_data['status'], $insert_data['wp_link_id']);
                $insert_data['link_visible'] = 'Y';
                $inserted = $wpdb->insert($links_table, $insert_data);
                $wp_link_id = $inserted ? intval($wpdb->insert_id) : 0;

                // 更新为已审核，并记录对应的 wp_links 记录
                $update_data = ['status' => 'approved'];
                if ($wp_link_id) {
                    $update_data['wp_link_id'] = $wp_link_id;
                }
                $wpdb->update($table, $update_data, ['link_id' => $id]);

                // 发审核通过邮件（逻辑保持不变）
                if (is_email($email)) {
                    $time = current_time('Y-m-d H:i:s');
                    $html = argon_friendlink_approval_email_html($link_name, $time);

                    wp_mail($email, '您的友情链接申请已通过', $html, ['Content-Type: text/html; charset=UTF-8']);
                }
            }
        }
        echo '<div class="updated"><p>审核通过成功。</p></div>';
    }

    // 处理拒绝操作（仅待审核申请可拒绝，已拒绝/已通过/已下架均不受理）
    if ($admin_nonce_valid && isset($_POST['reject']) && is_array($_POST['reject'])) {
        foreach ($_POST['reject'] as $id) {
            $wpdb->update(
                $table,
                ['status' => 'rejected'],
                ['link_id' => intval($id), 'status' => 'pending']
            );
        }
        echo '<div class="updated"><p>已拒绝选中项。</p></div>';
    }

    // 下架：仅“已审核通过”的友链可下架
    if ($admin_nonce_valid && isset($_POST['offline']) && is_array($_POST['offline'])) {
        $offline_count = 0;
        $offline_skipped = 0;
        foreach ($_POST['offline'] as $id) {
            $id = intval($id);
            $app = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE link_id = %d", $id), ARRAY_A);
            if (!$app || $app['status'] !== 'approved') {
                $offline_skipped++;
                continue;
            }
            $wp_link_id = argon_friendlink_resolve_wp_link_id($app);
            if (!$wp_link_id) {
                $offline_skipped++;
                continue;
            }
            // WordPress 链接置为不可见
            $wpdb->update($links_table, ['link_visible' => 'N'], ['link_id' => $wp_link_id]);
            $wpdb->update(
                $table,
                ['status' => 'offline', 'wp_link_id' => $wp_link_id],
                ['link_id' => $id]
            );
            $offline_count++;
        }
        if ($offline_count) {
            echo '<div class="updated"><p>已下架 ' . intval($offline_count) . ' 条友链。</p></div>';
        }
        if ($offline_skipped) {
            echo '<div class="error"><p>' . intval($offline_skipped) . ' 条未处理：仅“已审核通过”的友链可下架，或未找到对应的 WordPress 链接记录。</p></div>';
        }
    }

    // 重新上架：仅“已下架”的友链可重新上架
    if ($admin_nonce_valid && isset($_POST['relist']) && is_array($_POST['relist'])) {
        $relist_count = 0;
        $relist_skipped = 0;
        foreach ($_POST['relist'] as $id) {
            $id = intval($id);
            $app = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE link_id = %d", $id), ARRAY_A);
            if (!$app || $app['status'] !== 'offline') {
                $relist_skipped++;
                continue;
            }
            $wp_link_id = argon_friendlink_resolve_wp_link_id($app);
            if (!$wp_link_id) {
                $relist_skipped++;
                continue;
            }
            // WordPress 链接恢复可见
            $wpdb->update($links_table, ['link_visible' => 'Y'], ['link_id' => $wp_link_id]);
            $wpdb->update(
                $table,
                ['status' => 'approved', 'wp_link_id' => $wp_link_id],
                ['link_id' => $id]
            );
            $relist_count++;
        }
        if ($relist_count) {
            echo '<div class="updated"><p>已重新上架 ' . intval($relist_count) . ' 条友链。</p></div>';
        }
        if ($relist_skipped) {
            echo '<div class="error"><p>' . intval($relist_skipped) . ' 条未处理：仅“已下架”的友链可重新上架，或未找到对应的 WordPress 链接记录。</p></div>';
        }
    }

    // 查询不同状态的数据
    $pending_links  = $wpdb->get_results("SELECT * FROM $table WHERE status = 'pending' ORDER BY link_id DESC");
    $approved_links = $wpdb->get_results("SELECT * FROM $table WHERE status = 'approved' ORDER BY link_id DESC");
    $rejected_links = $wpdb->get_results("SELECT * FROM $table WHERE status = 'rejected' ORDER BY link_id DESC");
    $offline_links  = $wpdb->get_results("SELECT * FROM $table WHERE status = 'offline' ORDER BY link_id DESC");

    echo '<div class="wrap"><h1>友情链接申请审核</h1>';

    // 设置：审核通过邮件跳转页面
    $selected_page_id = intval(get_option('argon_friendlink_links_page_id', 0));
    echo '<h2>邮件跳转页面</h2>';
    echo '<form method="post" style="margin-bottom:30px;">';
    wp_nonce_field('argon_friendlink_save_settings', 'argon_friendlink_settings_nonce');
    echo '<input type="hidden" name="argon_friendlink_save_settings" value="1">';
    echo '<p>审核通过邮件中的“前往查看友情链接”按钮将跳转到以下页面：</p>';
    wp_dropdown_pages([
        'name'              => 'argon_friendlink_links_page_id',
        'id'                => 'argon_friendlink_links_page_id',
        'selected'          => $selected_page_id,
        'show_option_none'  => '默认：友链页面 /link.html',
        'option_none_value' => '0',
        'post_status'       => 'publish',
    ]);
    if ($selected_page_id > 0) {
        $selected_page_url = get_permalink($selected_page_id);
        if ($selected_page_url) {
            echo '<p>当前跳转地址：<code>' . esc_html(set_url_scheme($selected_page_url, 'https')) . '</code></p>';
        }
    }
    echo '<p><input type="submit" class="button button-primary" value="保存设置"></p>';
    echo '</form>';

    echo '<form method="post">';
    wp_nonce_field($admin_nonce, 'argon_friendlink_admin_nonce');

    // section: pending
    echo '<h2>待审核申请</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>名称</th><th>网址</th><th>简介</th><th>邮箱</th><th>备注</th><th>操作</th></tr></thead><tbody>';
    foreach ($pending_links as $row) {
        echo "<tr>
            <td>" . esc_html($row->link_name) . "</td>
            <td><a href='" . esc_url($row->link_url) . "' target='_blank'>" . esc_html($row->link_url) . "</a></td>
            <td>" . esc_html($row->link_description) . "</td>
            <td>" . esc_html($row->link_email) . "</td>
            <td>" . esc_html($row->link_notes) . "</td>
            <td>
                <label><input type='checkbox' name='approve[]' value='" . intval($row->link_id) . "'> 通过</label><br>
                <label><input type='checkbox' name='reject[]' value='" . intval($row->link_id) . "'> 拒绝</label>
            </td>
        </tr>";
    }
    echo '</tbody></table>';

    echo '<p><input type="submit" class="button button-primary" value="提交操作"></p>';

    // section: approved
    if (count($approved_links)) {
        echo '<h2 style="margin-top:40px;">已审核通过</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>名称</th><th>网址</th><th>简介</th><th>邮箱</th><th>备注</th><th>操作</th></tr></thead><tbody>';
        foreach ($approved_links as $row) {
            echo "<tr>
                <td>" . esc_html($row->link_name) . "</td>
                <td><a href='" . esc_url($row->link_url) . "' target='_blank'>" . esc_html($row->link_url) . "</a></td>
                <td>" . esc_html($row->link_description) . "</td>
                <td>" . esc_html($row->link_email) . "</td>
                <td>" . esc_html($row->link_notes) . "</td>
                <td>
                    <label><input type='checkbox' name='offline[]' value='" . intval($row->link_id) . "'> 下架</label>
                </td>
            </tr>";
        }
        echo '</tbody></table>';
        echo '<p><input type="submit" class="button button-primary" value="提交操作"></p>';
    }

    // section: rejected
    if (count($rejected_links)) {
        echo '<h2 style="margin-top:40px;">已拒绝申请</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>名称</th><th>网址</th><th>简介</th><th>邮箱</th><th>备注</th></tr></thead><tbody>';
        foreach ($rejected_links as $row) {
            echo "<tr>
                <td>" . esc_html($row->link_name) . "</td>
                <td><a href='" . esc_url($row->link_url) . "' target='_blank'>" . esc_html($row->link_url) . "</a></td>
                <td>" . esc_html($row->link_description) . "</td>
                <td>" . esc_html($row->link_email) . "</td>
                <td>" . esc_html($row->link_notes) . "</td>
            </tr>";
        }
        echo '</tbody></table>';
    }

    // section: offline
    echo '<h2 style="margin-top:40px;">已下架</h2>';
    if (count($offline_links)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>名称</th><th>网址</th><th>简介</th><th>邮箱</th><th>备注</th><th>操作</th></tr></thead><tbody>';
        foreach ($offline_links as $row) {
            echo "<tr>
                <td>" . esc_html($row->link_name) . "</td>
                <td><a href='" . esc_url($row->link_url) . "' target='_blank'>" . esc_html($row->link_url) . "</a></td>
                <td>" . esc_html($row->link_description) . "</td>
                <td>" . esc_html($row->link_email) . "</td>
                <td>" . esc_html($row->link_notes) . "</td>
                <td>
                    <label><input type='checkbox' name='relist[]' value='" . intval($row->link_id) . "'> 重新上架</label>
                </td>
            </tr>";
        }
        echo '</tbody></table>';
        echo '<p><input type="submit" class="button button-primary" value="提交操作"></p>';
    } else {
        echo '<p>暂无已下架的友链。</p>';
    }

    echo '</form></div>';
}

/* ==========================================================================
 * 前台友链自助申请
 * --------------------------------------------------------------------------
 * 说明：
 * - 网站名称、网站简介不去除内容、不做合规校验，按用户输入原样入库；
 * - 网站地址允许省略协议，自动补全 https://；仅校验域名后缀与路径；
 * - 网站图标、网站地址、网站名称、网站简介、联系邮箱均为必填项；
 * - 联系邮箱仅校验格式；
 * - 审核通过邮件使用独立的 Argon 风格 HTML 模板。
 * ========================================================================== */

/**
 * 规范化站点地址：缺少协议时补全 https://，http:// 统一升级为 https://。
 * 除协议补全外不做其它内容过滤。
 */
function argon_friendlink_normalize_site_url($raw) {
    $url = trim((string) $raw);
    if ($url === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $url)) {
        $url = preg_replace('#^http://#i', 'https://', $url);
    } else {
        $url = 'https://' . ltrim($url, '/');
    }
    return $url;
}

/**
 * 校验“正规域名后缀”：至少包含一个点，顶级后缀为 2~63 位字母。
 * 允许域名后带路径、查询串；不拦截内网/本地地址（按需求不做额外过滤）。
 */
function argon_friendlink_is_valid_domain($host) {
    $host = trim((string) $host, " \t\n\r\0\x0B.");
    if ($host === '') {
        return false;
    }
    return (bool) preg_match(
        '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
        $host
    );
}

/**
 * 处理前台提交，返回表单状态。
 *
 * @return array{form: array, errors: string[], success: bool}
 */
function argon_friendlink_handle_apply_request() {
    $defaults = [
        'link_name'        => '',
        'link_url'         => '',
        'link_image'       => '',
        'link_description' => '',
        'link_email'       => '',
        'link_notes'       => '',
    ];
    $state = [
        'form'    => $defaults,
        'errors'  => [],
        'success' => false,
    ];

    if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST' || !isset($_POST['fl_submit'])) {
        return $state;
    }

    // 取原始输入（WordPress 会为 $_POST 添加转义斜杠，这里还原）
    foreach ($defaults as $key => $unused) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $state['form'][$key] = trim(wp_unslash($_POST[$key]));
        }
    }

    // 校验 nonce，防止跨站伪造提交
    $nonce = isset($_POST['argon_friendlink_nonce']) && is_string($_POST['argon_friendlink_nonce'])
        ? wp_unslash($_POST['argon_friendlink_nonce'])
        : '';
    if (!wp_verify_nonce($nonce, 'argon_friendlink_apply')) {
        $state['errors'][] = '页面已过期，请刷新页面后重新提交。';
        return $state;
    }

    $name    = $state['form']['link_name'];
    $desc    = $state['form']['link_description'];
    $email   = $state['form']['link_email'];
    $notes   = $state['form']['link_notes'];
    $icon    = $state['form']['link_image'];
    $raw_url = $state['form']['link_url'];

    if ($name === '') {
        $state['errors'][] = '网站名称不能为空。';
    }
    if ($desc === '') {
        $state['errors'][] = '网站简介不能为空。';
    }
    if ($email === '' || !is_email($email)) {
        $state['errors'][] = '联系邮箱格式不正确。';
    }

    // 网站图标：必填，且需要是有效的图片地址
    if ($icon === '') {
        $state['errors'][] = '网站图标不能为空。';
    } else {
        if (!preg_match('#^https?://#i', $icon)) {
            if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $icon)) {
                $state['errors'][] = '网站图标地址格式不正确。';
            } else {
                $icon = 'https://' . ltrim($icon, '/');
            }
        }
        // 图标地址会写入公开友链页的 img src 属性，禁止引号/尖括号/空白等
        // 会破坏 HTML 属性的字符（filter_var 本身允许单引号，不能作为唯一防线）。
        if (
            !wp_parse_url($icon, PHP_URL_HOST)
            || !filter_var($icon, FILTER_VALIDATE_URL)
            || preg_match('/[\x00-\x20\x7F<>"\']/', $icon)
        ) {
            $state['errors'][] = '网站图标地址格式不正确。';
        }
    }

    // 网站地址：补全协议后仅校验域名后缀（允许带路径）
    $site_url = argon_friendlink_normalize_site_url($raw_url);
    if ($raw_url === '') {
        $state['errors'][] = '网站地址不能为空。';
    } else {
        $host = wp_parse_url($site_url, PHP_URL_HOST);
        if (!$host || !argon_friendlink_is_valid_domain($host)) {
            $state['errors'][] = '网站地址格式不正确，请填写带正规域名后缀的地址（如 example.com，可带 /path）。';
        }
    }

    if (!empty($state['errors'])) {
        return $state;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'friendlink_applications';
    $data = [
        'link_url'         => $site_url,
        'link_name'        => $name,
        'link_image'       => $icon,
        'link_target'      => '_blank',
        'link_description' => $desc,
        'link_visible'     => 'N',
        'link_owner'       => get_current_user_id() ?: 1,
        'link_rating'      => 0,
        'link_updated'     => current_time('mysql'),
        'link_rel'         => '',
        'link_notes'       => sanitize_textarea_field($notes),
        'link_rss'         => '',
        'link_email'       => $email,
        'status'           => 'pending',
    ];

    $inserted = $wpdb->insert($table, $data);
    if (!$inserted) {
        $state['errors'][] = '提交失败，请稍后重试。';
        return $state;
    }

    // 新申请通知管理员（沿用原有通知内容与发送方式）
    $admin_email = get_option('admin_email');
    $subject = '新友情链接申请待审核';
    $review_url = esc_url(admin_url('admin.php?page=argon_friendlink_admin', 'https'));
    $body = "<p>有新的友情链接申请提交：</p>
             <ul>
               <li><strong>网站名：</strong>" . esc_html($data['link_name']) . "</li>
               <li><strong>网址：</strong>" . esc_url($data['link_url']) . "</li>
               <li><strong>简介：</strong>" . esc_html($data['link_description']) . "</li>
               <li><strong>备注：</strong>" . nl2br(esc_html($data['link_notes'])) . "</li>
             </ul>
             <p><a href='{$review_url}'>点击审核</a></p>";
    wp_mail($admin_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);

    $state['success'] = true;
    $state['form'] = $defaults; // 成功后清空表单，防止重复提交残留
    return $state;
}

/**
 * 每个请求只处理一次提交。
 * Argon 主题会多次应用 the_content，若在短代码回调里直接处理提交，
 * 同一次 POST 会被重复入库；因此在 template_redirect 阶段先处理并缓存结果。
 */
function argon_friendlink_apply_state() {
    static $state = null;
    if ($state === null) {
        $state = argon_friendlink_handle_apply_request();
    }
    return $state;
}
add_action('template_redirect', 'argon_friendlink_apply_state', 0);

/**
 * 前台申请表单短代码：[friendlink_apply]
 * 使用 Argon 主题原生组件（input-group-alternative / alert / btn）渲染。
 */
function argon_friendlink_apply_shortcode($atts = [], $content = '') {
    $state = argon_friendlink_apply_state();
    $form  = $state['form'];

    ob_start();

    if ($state['success']) {
        echo '<div class="alert alert-success" role="alert">'
            . '<span class="alert-inner--icon"><i class="fa fa-check-circle"></i></span>'
            . '<span class="alert-inner--text">提交成功，等待管理员审核。</span>'
            . '</div>';
    } elseif (!empty($state['errors'])) {
        echo '<div class="alert alert-danger" role="alert">'
            . '<span class="alert-inner--icon"><i class="fa fa-exclamation-circle"></i></span>'
            . '<span class="alert-inner--text">' . implode('<br>', array_map('esc_html', $state['errors'])) . '</span>'
            . '</div>';
    }
    ?>
    <form method="post">
        <?php wp_nonce_field('argon_friendlink_apply', 'argon_friendlink_nonce'); ?>
        <input type="hidden" name="fl_submit" value="1">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div class="input-group input-group-alternative mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-globe"></i></span>
                        </div>
                        <input id="link_name" class="form-control" type="text" name="link_name" aria-label="网站名称"
                               placeholder="网站名称（必填）" value="<?php echo esc_attr($form['link_name']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group input-group-alternative mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-link"></i></span>
                        </div>
                        <input id="link_url" class="form-control" type="text" name="link_url" aria-label="网站地址"
                               placeholder="网站地址（必填，可省略 https://）" value="<?php echo esc_attr($form['link_url']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group input-group-alternative mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-picture-o"></i></span>
                        </div>
                        <input id="link_image" class="form-control" type="text" name="link_image" aria-label="网站图标"
                               placeholder="网站图标地址（必填，如 https://example.com/logo.png）" value="<?php echo esc_attr($form['link_image']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group input-group-alternative mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-info-circle"></i></span>
                        </div>
                        <input id="link_description" class="form-control" type="text" name="link_description" aria-label="网站简介"
                               placeholder="网站简介（必填）" value="<?php echo esc_attr($form['link_description']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group input-group-alternative mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                        </div>
                        <input id="link_email" class="form-control" type="email" name="link_email" aria-label="联系邮箱"
                               placeholder="联系邮箱（必填）" value="<?php echo esc_attr($form['link_email']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <textarea id="link_notes" class="form-control form-control-alternative" name="link_notes"
                              aria-label="附加备注" placeholder="附加备注（可选）" rows="3"><?php echo esc_textarea($form['link_notes']); ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-icon btn-primary pull-right">
                            <span class="btn-inner--icon"><i class="fa fa-send"></i></span>
                            <span class="btn-inner--text">提交申请</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('friendlink_apply', 'argon_friendlink_apply_shortcode');
