<?php
/*
Plugin Name: Gaoice / Argon Friendlink 友链自助申请与审核插件
Description: 专为 Gaoice Field Notes 主题定制（向下兼容 Argon）：提供切角风格友链自助申请表单（含实时名片预览）、可视化后台审核中心、通过/拒绝、下架/重新上架、同风格 HTML 邮件通知，支持自动建表与自定义邮件跳转页面。
Version: 2.0
Author: Timozaici & Gaoice
Author URI: https://blog.gaoice.run
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 5.5
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page(
        '友链审核中心',
        '友链审核',
        'manage_options',
        'argon_friendlink_admin',
        'argon_friendlink_admin_page',
        'dashicons-admin-links',
        30
    );
});

/**
 * 获取友链展示页默认跳转 URL（优先读取插件设置页面 -> 主题自动识别友人帐页 -> /link/）
 */
function argon_friendlink_get_target_page_url(): string {
    $selected_page_id = intval(get_option('argon_friendlink_links_page_id', 0));
    if (
        $selected_page_id > 0
        && get_post_type($selected_page_id) === 'page'
        && get_post_status($selected_page_id) === 'publish'
    ) {
        $selected_page_url = get_permalink($selected_page_id);
        if ($selected_page_url) {
            return esc_url(set_url_scheme($selected_page_url, 'https'));
        }
    }
    if (function_exists('gaoice_get_links_page_url')) {
        return esc_url(set_url_scheme(gaoice_get_links_page_url(), 'https'));
    }
    return esc_url(home_url('/link/', 'https'));
}

/**
 * 生成“审核通过”邮件 HTML。
 * 采用 Gaoice Field Notes 主题设计语言（深空蓝底 #0C1433、天穹主色 #2B57E8、青色副色 #27BBD8、明黄菱形撞色 #FFD24A），
 * 使用表格布局 + 内联样式 + 渐进式切角（clip-path）保证各主流邮件客户端与手机端完美兼容。
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
    $link_page = argon_friendlink_get_target_page_url();

    $safe_name = esc_html($link_name);
    $safe_time = esc_html($time);

    return <<<EMAILHTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<title>友人帐收录通知 — 友情链接申请已通过</title>
<style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; background-color: #0C1433; }
    .email-container { width: 600px; }
    @media only screen and (max-width: 620px) {
        .email-outer { padding: 18px 10px !important; }
        .email-container { width: 100% !important; max-width: 100% !important; }
        .email-hero { padding: 24px 20px !important; }
        .email-card { padding: 24px 20px 22px !important; }
        .email-title { font-size: 21px !important; line-height: 1.35 !important; }
        .email-detail { padding: 14px 16px !important; font-size: 14px !important; }
        .email-btn { display: block !important; width: 100% !important; padding: 14px 0 !important; text-align: center !important; box-sizing: border-box !important; }
        .email-footer { padding: 16px 12px 0 !important; }
    }
</style>
</head>
<body style="margin:0; padding:0; background-color:#0C1433;">
<div style="display:none; font-size:1px; color:#0C1433; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    您提交的站点 {$safe_name} 已通过友情链接审核，正式收录至 {$site_name} 友人帐。
</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; background-color:#0C1433; border-collapse:collapse;">
    <tr>
        <td class="email-outer" align="center" style="padding:36px 12px;">
            <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; border-collapse:collapse;">
                <!-- 顶部天穹刀锋横幅 -->
                <tr>
                    <td class="email-hero" style="background:linear-gradient(135deg,#16255C 0%,#2B57E8 62%,#27BBD8 100%); background-color:#2B57E8; padding:28px 34px; border-bottom:4px solid #FFD24A; font-family:'Noto Sans SC',-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td>
                                    <span style="display:inline-block; padding:3px 10px; background-color:#FFD24A; color:#141000; font-size:11px; font-weight:900; letter-spacing:1.5px;">&#9670; FRIEND LINK APPROVED</span>
                                    <div class="email-title" style="margin-top:10px; font-size:25px; line-height:1.3; font-weight:900; color:#FFFFFF; letter-spacing:0.5px;">友人帐收录通知 · 申请已通过</div>
                                    <div style="margin-top:4px; font-size:13px; color:#DCE7FF; font-weight:600;">{$site_name} · 我知我新，未知的世界那么惊艳</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- 主体纸感内容面板 -->
                <tr>
                    <td style="background-color:#FAFAFD; border:1px solid #283666;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td class="email-card" style="padding:34px 36px 30px 36px; font-family:'Noto Sans SC',-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif; font-size:15.5px; line-height:1.85; color:#1E294B;">
                                    <p style="margin:0; font-size:15.5px; line-height:1.85; color:#1E294B; font-weight:700;">您好，</p>
                                    <p style="margin:8px 0 0 0; font-size:15.5px; line-height:1.85; color:#334168;">
                                        很高兴与你相遇！您提交的独立站点 <strong style="color:#0C1433; background-color:#FFF3C4; padding:2px 7px;">{$safe_name}</strong> 已通过审核，现已正式收录并展示在本站的「友人帐」页面。
                                    </p>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; margin-top:22px; border-collapse:separate; border-spacing:0;">
                                        <tr>
                                            <td class="email-detail" style="padding:16px 20px; background-color:#EEF2FF; border-left:5px solid #2B57E8; font-size:14.5px; line-height:1.85; color:#1E294B;">
                                                <div style="padding:2px 0;"><span style="color:#5B6B94; font-weight:700;">收录站点：</span><strong style="color:#0C1433;">{$safe_name}</strong></div>
                                                <div style="padding:2px 0;"><span style="color:#5B6B94; font-weight:700;">审核状态：</span><strong style="color:#0E9F6E;">&#10003; 已正式上架展示</strong></div>
                                                <div style="padding:2px 0;"><span style="color:#5B6B94; font-weight:700;">通过时间：</span><span style="color:#0C1433; font-family:Consolas,monospace;">{$safe_time}</span></div>
                                            </td>
                                        </tr>
                                    </table>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; margin-top:28px; border-collapse:collapse;">
                                        <tr>
                                            <td align="center">
                                                <a href="{$link_page}" class="email-btn" style="display:inline-block; padding:14px 36px; background-color:#2B57E8; border-right:4px solid #FFD24A; color:#FFFFFF; font-size:15px; font-weight:900; letter-spacing:0.5px; line-height:1.2; text-decoration:none; clip-path:polygon(0 0,calc(100% - 10px) 0,100% 10px,100% 100%,10px 100%,0 calc(100% - 10px));">前往查看友人帐 &#8594;</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style="margin:24px 0 0 0; font-size:13px; line-height:1.7; color:#6E7C9F; text-align:center;">感谢您与 {$site_name} 互换友情链接，祝贵站常更常新！</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-footer" style="padding:18px 12px 0 12px; font-family:'Noto Sans SC',-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif; font-size:12px; line-height:1.8; color:#8A99AD; text-align:center;">
                        本邮件由 {$site_name} 系统自动发送，请勿直接回复。<br>
                        <a href="{$site_url}" style="color:#FFD24A; text-decoration:none; font-weight:700;">{$site_url}</a>
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
 * 数据库结构升级
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

/**
 * 提取站点名称的首字作为切角头像兜底徽章
 */
function argon_friendlink_initial($name): string {
    $clean = preg_replace('/^[^\p{L}\p{N}]+/u', '', trim((string) $name));
    if ($clean === '') $clean = trim((string) $name) ?: '友';
    return mb_strtoupper(mb_substr($clean, 0, 1, 'UTF-8'), 'UTF-8');
}

/**
 * 渲染后台「友链审核中心」（Gaoice Field Notes 切角视觉风格）
 */
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

    $notices = [];

    // 保存“审核通过邮件跳转页面”设置
    if (isset($_POST['argon_friendlink_save_settings'])) {
        $settings_nonce = isset($_POST['argon_friendlink_settings_nonce']) && is_string($_POST['argon_friendlink_settings_nonce'])
            ? wp_unslash($_POST['argon_friendlink_settings_nonce'])
            : '';
        if (!wp_verify_nonce($settings_nonce, 'argon_friendlink_save_settings')) {
            $notices[] = ['type' => 'error', 'text' => '设置校验失败，请刷新页面后重试。'];
        } else {
            $page_id = isset($_POST['argon_friendlink_links_page_id']) && is_scalar($_POST['argon_friendlink_links_page_id'])
                ? intval($_POST['argon_friendlink_links_page_id'])
                : 0;
            if ($page_id > 0 && (get_post_type($page_id) !== 'page' || get_post_status($page_id) !== 'publish')) {
                $page_id = 0;
            }
            update_option('argon_friendlink_links_page_id', $page_id);
            $notices[] = ['type' => 'ok', 'text' => '审核通过邮件的跳转页面已保存生效。'];
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
        $notices[] = ['type' => 'error', 'text' => '操作安全校验失败，请刷新页面后重试。'];
    }

    // 处理审核通过
    if ($admin_nonce_valid && isset($_POST['approve']) && is_array($_POST['approve'])) {
        $approved_cnt = 0;
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
                $approved_cnt++;

                // 发审核通过邮件
                if (is_email($email)) {
                    $time = current_time('Y-m-d H:i:s');
                    $html = argon_friendlink_approval_email_html($link_name, $time);
                    wp_mail($email, '您的友情链接申请已通过', $html, ['Content-Type: text/html; charset=UTF-8']);
                }
            }
        }
        if ($approved_cnt) {
            $notices[] = ['type' => 'ok', 'text' => '已审核通过 ' . intval($approved_cnt) . ' 条友链申请，并同步写入 WordPress 友人帐与发送邮件通知。'];
        }
    }

    // 处理拒绝操作（仅待审核申请可拒绝）
    if ($admin_nonce_valid && isset($_POST['reject']) && is_array($_POST['reject'])) {
        $rejected_cnt = 0;
        foreach ($_POST['reject'] as $id) {
            $updated = $wpdb->update(
                $table,
                ['status' => 'rejected'],
                ['link_id' => intval($id), 'status' => 'pending']
            );
            if ($updated) $rejected_cnt++;
        }
        if ($rejected_cnt) {
            $notices[] = ['type' => 'warn', 'text' => '已拒绝 ' . intval($rejected_cnt) . ' 条待审核申请。'];
        }
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
            $wpdb->update($links_table, ['link_visible' => 'N'], ['link_id' => $wp_link_id]);
            $wpdb->update(
                $table,
                ['status' => 'offline', 'wp_link_id' => $wp_link_id],
                ['link_id' => $id]
            );
            $offline_count++;
        }
        if ($offline_count) {
            $notices[] = ['type' => 'warn', 'text' => '已下架 ' . intval($offline_count) . ' 条友链（WordPress 链接表已同步设为隐藏）。'];
        }
        if ($offline_skipped) {
            $notices[] = ['type' => 'error', 'text' => intval($offline_skipped) . ' 条未处理：仅“已审核通过”的友链可下架，或未找到对应的 WordPress 链接记录。'];
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
            $wpdb->update($links_table, ['link_visible' => 'Y'], ['link_id' => $wp_link_id]);
            $wpdb->update(
                $table,
                ['status' => 'approved', 'wp_link_id' => $wp_link_id],
                ['link_id' => $id]
            );
            $relist_count++;
        }
        if ($relist_count) {
            $notices[] = ['type' => 'ok', 'text' => '已重新上架 ' . intval($relist_count) . ' 条友链（WordPress 链接表已恢复公开展示）。'];
        }
        if ($relist_skipped) {
            $notices[] = ['type' => 'error', 'text' => intval($relist_skipped) . ' 条未处理：仅“已下架”的友链可重新上架，或未找到对应的 WordPress 链接记录。'];
        }
    }

    // 查询不同状态的数据
    $pending_links  = $wpdb->get_results("SELECT * FROM $table WHERE status = 'pending' ORDER BY link_id DESC");
    $approved_links = $wpdb->get_results("SELECT * FROM $table WHERE status = 'approved' ORDER BY link_id DESC");
    $rejected_links = $wpdb->get_results("SELECT * FROM $table WHERE status = 'rejected' ORDER BY link_id DESC");
    $offline_links  = $wpdb->get_results("SELECT * FROM $table WHERE status = 'offline' ORDER BY link_id DESC");
    $selected_page_id = intval(get_option('argon_friendlink_links_page_id', 0));
    $target_url = argon_friendlink_get_target_page_url();

    ?>
    <style>
      .gfl-adm-wrap {
        --a1: #2B57E8;
        --a2: #27BBD8;
        --a3: #E884B6;
        --warm: #FFD24A;
        --ink: #0C1433;
        max-width: 1280px;
        margin: 18px 20px 48px 0;
        font-family: "Noto Sans SC", -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
        color: var(--ink);
      }
      .gfl-cut {
        clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 14px, 100% 100%, 14px 100%, 0 calc(100% - 14px));
      }
      .gfl-cut-sm {
        clip-path: polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px));
      }
      .gfl-hero {
        background: linear-gradient(135deg, #0C1433 0%, #192B68 58%, #2B57E8 100%);
        color: #fff;
        padding: 24px 28px;
        border-bottom: 4px solid var(--warm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
      }
      .gfl-hero__tag {
        display: inline-block;
        padding: 3px 9px;
        background: var(--warm);
        color: #161100;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 1.5px;
      }
      .gfl-hero h1 {
        color: #fff !important;
        font-size: 24px !important;
        font-weight: 900 !important;
        margin: 6px 0 4px !important;
        padding: 0 !important;
      }
      .gfl-hero p {
        margin: 0;
        font-size: 13px;
        color: rgb(255 255 255 / .82);
      }
      .gfl-hero__btns {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }
      .gfl-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        background: var(--a1);
        color: #fff !important;
        text-decoration: none !important;
        border: 1px solid rgb(255 255 255 / .24);
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        transition: transform .2s, background .2s;
      }
      .gfl-btn:hover { transform: translateY(-2px); background: #1E44C8; }
      .gfl-btn--warm { background: var(--warm); color: #161100 !important; border-color: transparent; }
      .gfl-btn--warm:hover { background: #ffe175; }
      .gfl-btn--ghost { background: rgb(255 255 255 / .12); }
      .gfl-btn--danger { background: #E11D48; }
      .gfl-btn--ok { background: #0D9488; }
      .gfl-btn--sm { padding: 5px 11px; font-size: 12px; }

      .gfl-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-top: 18px;
      }
      .gfl-stat {
        background: #fff;
        border: 1px solid #DCE3F4;
        border-left: 5px solid var(--a1);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      .gfl-stat--pending { border-left-color: var(--warm); }
      .gfl-stat--approved { border-left-color: #10B981; }
      .gfl-stat--offline { border-left-color: var(--a2); }
      .gfl-stat--rejected { border-left-color: #F43F5E; }
      .gfl-stat span { font-size: 13px; font-weight: 800; color: #546285; }
      .gfl-stat b { font-size: 30px; font-weight: 900; color: var(--ink); font-family: "Bebas Neue", "Manrope", sans-serif; line-height: 1; }

      .gfl-notice {
        margin-top: 16px;
        padding: 13px 18px;
        background: #ECFDF5;
        border-left: 5px solid #10B981;
        font-size: 13.5px;
        font-weight: 700;
        color: #065F46;
      }
      .gfl-notice--warn { background: #FFFBEB; border-left-color: #F59E0B; color: #92400E; }
      .gfl-notice--error { background: #FEF2F2; border-left-color: #EF4444; color: #991B1B; }

      .gfl-panel {
        margin-top: 20px;
        background: #fff;
        border: 1px solid #DCE3F4;
        padding: 20px 24px;
      }
      .gfl-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding-bottom: 12px;
        margin-bottom: 14px;
        border-bottom: 1px solid #E9EEF8;
      }
      .gfl-panel__title {
        font-size: 17px;
        font-weight: 900;
        color: var(--ink);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .gfl-panel__title::before {
        content: "";
        width: 9px;
        height: 9px;
        background: var(--a1);
        transform: rotate(45deg);
        display: inline-block;
      }
      .gfl-badge-cnt {
        padding: 2px 9px;
        background: #EEF2FF;
        color: var(--a1);
        font-size: 12px;
        font-weight: 900;
      }

      .gfl-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
      }
      .gfl-table th {
        text-align: left;
        padding: 11px 12px;
        background: #F4F7FF;
        color: #3B4A72;
        font-weight: 900;
        font-size: 12.5px;
        border-bottom: 2px solid #DCE3F4;
      }
      .gfl-table td {
        padding: 13px 12px;
        border-bottom: 1px solid #EEF2FA;
        vertical-align: middle;
      }
      .gfl-table tr:hover td {
        background: #F9FBFF;
      }
      .gfl-site-cell {
        display: flex;
        align-items: center;
        gap: 12px;
      }
      .gfl-ava {
        width: 40px;
        height: 40px;
        flex: none;
        background: var(--a1);
        color: #fff;
        font-weight: 900;
        font-size: 17px;
        display: grid;
        place-items: center;
        position: relative;
        overflow: hidden;
      }
      .gfl-ava img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        background: var(--a1);
      }
      .gfl-site-meta b {
        display: block;
        font-size: 14px;
        color: var(--ink);
      }
      .gfl-site-meta a {
        font-size: 12px;
        color: var(--a1);
        text-decoration: none;
        font-family: Consolas, monospace;
        word-break: break-all;
      }
      .gfl-site-meta a:hover { text-decoration: underline; }
      .gfl-actions-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
      }
      .gfl-check-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        background: #F4F7FF;
        border: 1px solid #D5E0F8;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
      }
      .gfl-check-pill--reject {
        background: #FEF2F2;
        border-color: #FECACA;
        color: #B91C1C;
      }
      .gfl-empty {
        padding: 22px;
        text-align: center;
        color: #6E7C9F;
        font-weight: 700;
        background: #F9FBFF;
      }
      @media (max-width: 900px) {
        .gfl-stats { grid-template-columns: repeat(2, 1fr); }
        .gfl-table { display: block; overflow-x: auto; }
      }
    </style>

    <div class="wrap gfl-adm-wrap">
      <!-- 顶部切角英雄头 -->
      <header class="gfl-hero gfl-cut">
        <div>
          <span class="gfl-hero__tag gfl-cut-sm">&#9670; GAOICE FIELD NOTES · FRIENDLINK CENTER</span>
          <h1>友人帐自助申请与审核中心</h1>
          <p>前台使用短代码 <code>[friendlink_apply]</code> 即可渲染带实时名片预览的切角申请表单；通过后自动写入 WordPress 链接库并发送天穹风格通知邮件。</p>
        </div>
        <div class="gfl-hero__btns">
          <a class="gfl-btn gfl-btn--warm gfl-cut-sm" href="<?php echo esc_url($target_url); ?>" target="_blank">👀 查看前台友人帐 ↗</a>
          <a class="gfl-btn gfl-btn--ghost gfl-cut-sm" href="<?php echo esc_url(admin_url('link-manager.php')); ?>">🔗 WordPress 链接管理器</a>
        </div>
      </header>

      <!-- 状态提示条 -->
      <?php foreach ($notices as $nt): ?>
        <div class="gfl-notice gfl-cut-sm <?php echo $nt['type'] === 'error' ? 'gfl-notice--error' : ($nt['type'] === 'warn' ? 'gfl-notice--warn' : ''); ?>">
          <?php echo esc_html($nt['text']); ?>
        </div>
      <?php endforeach; ?>

      <!-- 4 大状态统计矩阵 -->
      <div class="gfl-stats">
        <div class="gfl-stat gfl-stat--pending gfl-cut-sm">
          <span>⏳ 待审核申请 (Pending)</span>
          <b><?php echo count($pending_links); ?></b>
        </div>
        <div class="gfl-stat gfl-stat--approved gfl-cut-sm">
          <span>✓ 已通过展示 (Approved)</span>
          <b><?php echo count($approved_links); ?></b>
        </div>
        <div class="gfl-stat gfl-stat--offline gfl-cut-sm">
          <span>⬇ 暂时下架 (Offline)</span>
          <b><?php echo count($offline_links); ?></b>
        </div>
        <div class="gfl-stat gfl-stat--rejected gfl-cut-sm">
          <span>✕ 已拒绝 (Rejected)</span>
          <b><?php echo count($rejected_links); ?></b>
        </div>
      </div>

      <!-- 邮件跳转页面配置面板 -->
      <section class="gfl-panel gfl-cut">
        <div class="gfl-panel__head">
          <h2 class="gfl-panel__title">审核通过邮件按钮跳转页面</h2>
          <span style="font-size:12.5px;color:#5B6B94">当前跳转地址：<code><?php echo esc_html($target_url); ?></code></span>
        </div>
        <form method="post" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
          <?php wp_nonce_field('argon_friendlink_save_settings', 'argon_friendlink_settings_nonce'); ?>
          <input type="hidden" name="argon_friendlink_save_settings" value="1">
          <span style="font-size:13.5px;font-weight:700">邮件中「前往查看友人帐 →」按钮指向：</span>
          <?php
          wp_dropdown_pages([
              'name'              => 'argon_friendlink_links_page_id',
              'id'                => 'argon_friendlink_links_page_id',
              'selected'          => $selected_page_id,
              'show_option_none'  => '自动识别主题「友人帐」页面（默认 /link/）',
              'option_none_value' => '0',
              'post_status'       => 'publish',
          ]);
          ?>
          <button type="submit" class="gfl-btn gfl-cut-sm">💾 保存跳转设置</button>
        </form>
      </section>

      <!-- 审核操作主表单 -->
      <form method="post" id="gflAdminForm">
        <?php wp_nonce_field($admin_nonce, 'argon_friendlink_admin_nonce'); ?>

        <!-- 1. 待审核申请 -->
        <section class="gfl-panel gfl-cut">
          <div class="gfl-panel__head">
            <h2 class="gfl-panel__title">⏳ 待审核申请 <span class="gfl-badge-cnt gfl-cut-sm"><?php echo count($pending_links); ?></span></h2>
            <?php if (count($pending_links)): ?>
              <button type="submit" class="gfl-btn gfl-cut-sm">✓ 提交勾选操作</button>
            <?php endif; ?>
          </div>

          <?php if (count($pending_links)): ?>
            <table class="gfl-table">
              <thead>
                <tr>
                  <th style="width:24%">申请站点 & 图标</th>
                  <th style="width:24%">网站简介</th>
                  <th style="width:16%">联系邮箱</th>
                  <th style="width:16%">附加备注</th>
                  <th style="width:20%">审核操作</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pending_links as $row):
                    $init = argon_friendlink_initial($row->link_name);
                    $id   = intval($row->link_id);
                ?>
                  <tr>
                    <td>
                      <div class="gfl-site-cell">
                        <span class="gfl-ava gfl-cut-sm">
                          <span><?php echo esc_html($init); ?></span>
                          <?php if (!empty($row->link_image)): ?>
                            <img src="<?php echo esc_url($row->link_image); ?>" alt="" loading="lazy" onerror="this.remove()">
                          <?php endif; ?>
                        </span>
                        <div class="gfl-site-meta">
                          <b><?php echo esc_html($row->link_name); ?></b>
                          <a href="<?php echo esc_url($row->link_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($row->link_url); ?> ↗</a>
                        </div>
                      </div>
                    </td>
                    <td><?php echo esc_html($row->link_description); ?></td>
                    <td><code><?php echo esc_html($row->link_email); ?></code></td>
                    <td><?php echo $row->link_notes !== '' ? nl2br(esc_html($row->link_notes)) : '<span style="color:#9AA6C4">—</span>'; ?></td>
                    <td>
                      <div class="gfl-actions-cell">
                        <label class="gfl-check-pill gfl-cut-sm">
                          <input type="checkbox" name="approve[]" value="<?php echo $id; ?>"> ✓ 通过
                        </label>
                        <label class="gfl-check-pill gfl-check-pill--reject gfl-cut-sm">
                          <input type="checkbox" name="reject[]" value="<?php echo $id; ?>"> ✕ 拒绝
                        </label>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <div style="margin-top:14px;display:flex;justify-content:flex-end">
              <button type="submit" class="gfl-btn gfl-cut-sm">✓ 执行选中项审核操作</button>
            </div>
          <?php else: ?>
            <div class="gfl-empty gfl-cut-sm">🎉 当前没有待审核的友链申请。</div>
          <?php endif; ?>
        </section>

        <!-- 2. 已审核通过 -->
        <section class="gfl-panel gfl-cut">
          <div class="gfl-panel__head">
            <h2 class="gfl-panel__title">✓ 已审核通过 <span class="gfl-badge-cnt gfl-cut-sm"><?php echo count($approved_links); ?></span></h2>
            <?php if (count($approved_links)): ?>
              <button type="submit" class="gfl-btn gfl-cut-sm">提交下架操作</button>
            <?php endif; ?>
          </div>

          <?php if (count($approved_links)): ?>
            <table class="gfl-table">
              <thead>
                <tr>
                  <th style="width:26%">站点 & 图标</th>
                  <th style="width:26%">网站简介</th>
                  <th style="width:18%">联系邮箱</th>
                  <th style="width:16%">备注</th>
                  <th style="width:14%">操作</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($approved_links as $row):
                    $init = argon_friendlink_initial($row->link_name);
                    $id   = intval($row->link_id);
                ?>
                  <tr>
                    <td>
                      <div class="gfl-site-cell">
                        <span class="gfl-ava gfl-cut-sm">
                          <span><?php echo esc_html($init); ?></span>
                          <?php if (!empty($row->link_image)): ?>
                            <img src="<?php echo esc_url($row->link_image); ?>" alt="" loading="lazy" onerror="this.remove()">
                          <?php endif; ?>
                        </span>
                        <div class="gfl-site-meta">
                          <b><?php echo esc_html($row->link_name); ?></b>
                          <a href="<?php echo esc_url($row->link_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($row->link_url); ?> ↗</a>
                        </div>
                      </div>
                    </td>
                    <td><?php echo esc_html($row->link_description); ?></td>
                    <td><code><?php echo esc_html($row->link_email); ?></code></td>
                    <td><?php echo $row->link_notes !== '' ? esc_html($row->link_notes) : '<span style="color:#9AA6C4">—</span>'; ?></td>
                    <td>
                      <label class="gfl-check-pill gfl-cut-sm">
                        <input type="checkbox" name="offline[]" value="<?php echo $id; ?>"> ⬇ 下架
                      </label>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="gfl-empty gfl-cut-sm">暂无通过此插件审核上架的记录。</div>
          <?php endif; ?>
        </section>

        <!-- 3. 已下架 -->
        <section class="gfl-panel gfl-cut">
          <div class="gfl-panel__head">
            <h2 class="gfl-panel__title">⬇ 已下架友链 <span class="gfl-badge-cnt gfl-cut-sm"><?php echo count($offline_links); ?></span></h2>
            <?php if (count($offline_links)): ?>
              <button type="submit" class="gfl-btn gfl-cut-sm">⬆ 提交重新上架</button>
            <?php endif; ?>
          </div>

          <?php if (count($offline_links)): ?>
            <table class="gfl-table">
              <thead>
                <tr>
                  <th style="width:26%">站点 & 图标</th>
                  <th style="width:26%">网站简介</th>
                  <th style="width:18%">联系邮箱</th>
                  <th style="width:16%">备注</th>
                  <th style="width:14%">操作</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($offline_links as $row):
                    $init = argon_friendlink_initial($row->link_name);
                    $id   = intval($row->link_id);
                ?>
                  <tr>
                    <td>
                      <div class="gfl-site-cell">
                        <span class="gfl-ava gfl-cut-sm" style="background:#64748B">
                          <span><?php echo esc_html($init); ?></span>
                          <?php if (!empty($row->link_image)): ?>
                            <img src="<?php echo esc_url($row->link_image); ?>" alt="" loading="lazy" onerror="this.remove()">
                          <?php endif; ?>
                        </span>
                        <div class="gfl-site-meta">
                          <b><?php echo esc_html($row->link_name); ?></b>
                          <a href="<?php echo esc_url($row->link_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($row->link_url); ?> ↗</a>
                        </div>
                      </div>
                    </td>
                    <td><?php echo esc_html($row->link_description); ?></td>
                    <td><code><?php echo esc_html($row->link_email); ?></code></td>
                    <td><?php echo $row->link_notes !== '' ? esc_html($row->link_notes) : '<span style="color:#9AA6C4">—</span>'; ?></td>
                    <td>
                      <label class="gfl-check-pill gfl-cut-sm">
                        <input type="checkbox" name="relist[]" value="<?php echo $id; ?>"> ⬆ 重新上架
                      </label>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="gfl-empty gfl-cut-sm">暂无已下架的友链。</div>
          <?php endif; ?>
        </section>

        <!-- 4. 已拒绝申请 -->
        <?php if (count($rejected_links)): ?>
          <section class="gfl-panel gfl-cut">
            <div class="gfl-panel__head">
              <h2 class="gfl-panel__title">✕ 已拒绝申请 <span class="gfl-badge-cnt gfl-cut-sm"><?php echo count($rejected_links); ?></span></h2>
            </div>
            <table class="gfl-table">
              <thead>
                <tr>
                  <th style="width:28%">站点 & 图标</th>
                  <th style="width:30%">网站简介</th>
                  <th style="width:20%">联系邮箱</th>
                  <th style="width:22%">备注</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rejected_links as $row):
                    $init = argon_friendlink_initial($row->link_name);
                ?>
                  <tr>
                    <td>
                      <div class="gfl-site-cell">
                        <span class="gfl-ava gfl-cut-sm" style="background:#94A3B8">
                          <span><?php echo esc_html($init); ?></span>
                        </span>
                        <div class="gfl-site-meta">
                          <b><?php echo esc_html($row->link_name); ?></b>
                          <a href="<?php echo esc_url($row->link_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($row->link_url); ?> ↗</a>
                        </div>
                      </div>
                    </td>
                    <td><?php echo esc_html($row->link_description); ?></td>
                    <td><code><?php echo esc_html($row->link_email); ?></code></td>
                    <td><?php echo $row->link_notes !== '' ? esc_html($row->link_notes) : '<span style="color:#9AA6C4">—</span>'; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </section>
        <?php endif; ?>
      </form>
    </div>
    <?php
}

/* ==========================================================================
 * 前台友链自助申请（Gaoice Field Notes 硬朗切角 + 实时友链名片预览）
 * ========================================================================== */

/**
 * 规范化站点地址：缺少协议时补全 https://，http:// 统一升级为 https://。
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

    foreach ($defaults as $key => $unused) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $state['form'][$key] = trim(wp_unslash($_POST[$key]));
        }
    }

    $nonce = isset($_POST['argon_friendlink_nonce']) && is_string($_POST['argon_friendlink_nonce'])
        ? wp_unslash($_POST['argon_friendlink_nonce'])
        : '';
    if (!wp_verify_nonce($nonce, 'argon_friendlink_apply')) {
        $state['errors'][] = '页面安全令牌已过期，请刷新页面后重新提交。';
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
        if (
            !wp_parse_url($icon, PHP_URL_HOST)
            || !filter_var($icon, FILTER_VALIDATE_URL)
            || preg_match('/[\x00-\x20\x7F<>"\']/', $icon)
        ) {
            $state['errors'][] = '网站图标地址格式不正确。';
        }
    }

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

    // 新申请通知管理员
    $admin_email = get_option('admin_email');
    $subject = '新友情链接申请待审核 — ' . $data['link_name'];
    $review_url = esc_url(admin_url('admin.php?page=argon_friendlink_admin', 'https'));
    $body = "<div style='font-family:sans-serif;max-width:560px;padding:22px;background:#0C1433;color:#FAFAFD;border-bottom:4px solid #FFD24A'>"
          . "<div style='display:inline-block;padding:2px 8px;background:#FFD24A;color:#141000;font-size:11px;font-weight:900'>NEW FRIEND LINK</div>"
          . "<h2 style='margin:10px 0 14px;color:#fff'>收到新的友人帐自助申请</h2>"
          . "<div style='padding:14px 18px;background:#16224C;border-left:4px solid #2B57E8;line-height:1.8;font-size:14px'>"
          . "<div><strong>网站名称：</strong>" . esc_html($data['link_name']) . "</div>"
          . "<div><strong>网站地址：</strong><a href='" . esc_url($data['link_url']) . "' style='color:#27BBD8'>" . esc_html($data['link_url']) . "</a></div>"
          . "<div><strong>网站图标：</strong>" . esc_html($data['link_image']) . "</div>"
          . "<div><strong>网站简介：</strong>" . esc_html($data['link_description']) . "</div>"
          . "<div><strong>联系邮箱：</strong>" . esc_html($data['link_email']) . "</div>"
          . "<div><strong>附加备注：</strong>" . nl2br(esc_html($data['link_notes'] ?: '无')) . "</div>"
          . "</div>"
          . "<p style='margin:20px 0 0'><a href='{$review_url}' style='display:inline-block;padding:11px 24px;background:#2B57E8;color:#fff;text-decoration:none;font-weight:800'>前往后台审核 &#8594;</a></p>"
          . "</div>";
    wp_mail($admin_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);

    $state['success'] = true;
    $state['form'] = $defaults;
    return $state;
}

/**
 * 每个请求只处理一次提交。
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
 * 采用 Gaoice Field Notes 主题标志性的硬朗切角（.cut）、实时名片预览与响应式双栏布局渲染。
 */
function argon_friendlink_apply_shortcode($atts = [], $content = '') {
    $state = argon_friendlink_apply_state();
    $form  = $state['form'];
    $links_url = argon_friendlink_get_target_page_url();

    $init_preview = argon_friendlink_initial($form['link_name'] ?: 'Gaoice');
    $host_preview = $form['link_url'] ? (wp_parse_url(argon_friendlink_normalize_site_url($form['link_url']), PHP_URL_HOST) ?: $form['link_url']) : 'your-blog.example.com';

    ob_start();
    ?>
    <style>
      .gfl-front {
        --g-a1: var(--a1, #2B57E8);
        --g-a2: var(--a2, #27BBD8);
        --g-a3: var(--a3, #E884B6);
        --g-warm: var(--warm, #FFD24A);
        --g-ink: var(--ink, #0C1433);
        --g-ink-rgb: var(--ink-rgb, 12 20 51);
        --g-panel: var(--panel-solid, #FAFAFD);
        --g-c: var(--c-size, 16px);
        font-family: var(--f-cn, "Noto Sans SC", -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif);
        color: var(--g-ink);
        margin: 10px 0 24px;
      }
      .gfl-f-cut {
        --c: var(--g-c);
        clip-path: polygon(0 0, calc(100% - var(--c)) 0, 100% var(--c), 100% 100%, var(--c) 100%, 0 calc(100% - var(--c)));
      }
      html[data-shape="round"] .gfl-f-cut {
        clip-path: none;
        border-radius: var(--g-c);
      }
      html[data-shape="sharp"] .gfl-f-cut {
        clip-path: none;
        border-radius: 0;
      }

      /* 提示横幅（对齐主题 .admonition 切角提示框） */
      .gfl-alert {
        --c: 12px;
        margin-bottom: 22px;
        padding: 16px 20px;
        border-left: 5px solid var(--g-a1);
        background: rgb(var(--g-ink-rgb) / .06);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
      }
      .gfl-alert--ok {
        border-left-color: #10B981;
        background: rgb(16 185 129 / .12);
      }
      .gfl-alert--err {
        border-left-color: #F43F5E;
        background: rgb(244 63 94 / .12);
      }
      .gfl-alert__body strong {
        display: block;
        font-size: 15px;
        font-weight: 900;
        margin-bottom: 3px;
      }
      .gfl-alert__body div {
        font-size: 13.5px;
        line-height: 1.65;
      }

      /* 实时友链名片预览台 */
      .gfl-preview-stage {
        --c: 14px;
        margin-bottom: 24px;
        padding: 18px 20px;
        background: rgb(12 20 51 / .94);
        color: #FAFAFD;
        border: 1px solid rgb(255 255 255 / .16);
        display: grid;
        grid-template-columns: 1fr minmax(240px, 320px);
        gap: 20px;
        align-items: center;
      }
      .gfl-preview-copy span {
        display: inline-block;
        padding: 2px 9px;
        background: var(--g-warm);
        color: #181300;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 1.5px;
      }
      .gfl-preview-copy h4 {
        margin: 8px 0 4px !important;
        font-size: 18px !important;
        font-weight: 900 !important;
        color: #fff !important;
      }
      .gfl-preview-copy p {
        margin: 0 !important;
        font-size: 13px !important;
        line-height: 1.65 !important;
        color: rgb(250 250 253 / .76) !important;
      }
      .gfl-live-card {
        --c: 12px;
        padding: 16px 18px;
        background: var(--g-panel);
        color: var(--g-ink);
        display: flex;
        flex-direction: column;
        gap: 10px;
        box-shadow: 0 14px 36px rgb(0 0 0 / .28);
      }
      .gfl-live-card__top {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
      }
      .gfl-live-card__av {
        --c: 8px;
        width: 44px;
        height: 44px;
        flex: none;
        background: var(--g-a1);
        color: #fff;
        font-weight: 900;
        font-size: 20px;
        display: grid;
        place-items: center;
        position: relative;
        overflow: hidden;
      }
      .gfl-live-card__av img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        background: var(--g-a1);
      }
      .gfl-live-card__nm {
        min-width: 0;
        flex: 1;
      }
      .gfl-live-card__nm b {
        display: block;
        font-size: 15.5px;
        font-weight: 900;
        line-height: 1.35;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .gfl-live-card__nm small {
        display: block;
        font-size: 11px;
        font-weight: 700;
        opacity: .56;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .gfl-live-card__desc {
        margin: 0 !important;
        font-size: 12.5px !important;
        line-height: 1.65 !important;
        opacity: .78;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }

      /* 表单双栏网格与切角输入框 */
      .gfl-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px 18px;
      }
      .gfl-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
      }
      .gfl-field--full {
        grid-column: 1 / -1;
      }
      .gfl-label {
        font-size: 12.5px;
        font-weight: 900;
        color: var(--g-ink);
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      .gfl-label em {
        font-style: normal;
        font-size: 11px;
        font-weight: 700;
        color: var(--g-a1);
      }
      .gfl-input-box {
        --c: 8px;
        display: flex;
        align-items: stretch;
        background: rgb(var(--g-ink-rgb) / .045);
        border: 1px solid rgb(var(--g-ink-rgb) / .18);
        border-left: 4px solid var(--g-a1);
        transition: border-color .2s, background .2s, transform .2s;
      }
      .gfl-input-box:focus-within {
        border-color: var(--g-a1);
        border-left-color: var(--g-warm);
        background: rgb(var(--g-ink-rgb) / .02);
        transform: translateY(-1px);
      }
      .gfl-input-ico {
        width: 40px;
        flex: none;
        display: grid;
        place-items: center;
        font-size: 14px;
        font-weight: 900;
        color: var(--g-a1);
        border-right: 1px solid rgb(var(--g-ink-rgb) / .1);
      }
      .gfl-input-box input,
      .gfl-input-box textarea {
        flex: 1;
        min-width: 0;
        border: 0 !important;
        outline: 0 !important;
        background: transparent !important;
        color: var(--g-ink) !important;
        padding: 11px 14px !important;
        font: 600 14px/1.5 var(--f-cn, sans-serif) !important;
        box-shadow: none !important;
      }
      .gfl-input-box textarea {
        resize: vertical;
        min-height: 82px;
      }
      .gfl-foot {
        margin-top: 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
      }
      .gfl-foot__hint {
        font-size: 12.5px;
        opacity: .72;
      }
      .gfl-submit-btn {
        --c: 10px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 13px 26px;
        background: var(--g-a1);
        color: var(--on-a1, #fff);
        border: 0;
        font: 900 14px/1 var(--f-cn, sans-serif);
        cursor: pointer;
        transition: transform .32s cubic-bezier(.2,1.18,.3,1), background .25s;
      }
      .gfl-submit-btn:hover {
        transform: translateY(-4px);
      }
      @media (max-width: 720px) {
        .gfl-preview-stage,
        .gfl-form-grid {
          grid-template-columns: 1fr;
        }
        .gfl-submit-btn {
          width: 100%;
          justify-content: center;
        }
      }
    </style>

    <div class="gfl-front">
      <?php if ($state['success']): ?>
        <div class="gfl-alert gfl-alert--ok gfl-f-cut" role="alert">
          <div class="gfl-alert__body">
            <strong>&#10003; 友链申请已成功提交！</strong>
            <div>您的站点信息已进入后台审核队列，审核通过后系统会自动发送邮件至您的联系邮箱，并在友人帐页面公开展示。</div>
          </div>
          <a class="btn btn--sm" href="<?php echo esc_url($links_url); ?>"><span class="cut">返回友人帐 &#8594;</span></a>
        </div>
      <?php elseif (!empty($state['errors'])): ?>
        <div class="gfl-alert gfl-alert--err gfl-f-cut" role="alert">
          <div class="gfl-alert__body">
            <strong>&#9888; 提交未通过校验，请检查以下内容：</strong>
            <div><?php echo implode('<br>', array_map('esc_html', $state['errors'])); ?></div>
          </div>
        </div>
      <?php endif; ?>

      <!-- 实时友链名片预览台（与友人帐 .linkcard 1:1 同构） -->
      <div class="gfl-preview-stage gfl-f-cut">
        <div class="gfl-preview-copy">
          <span class="gfl-f-cut">&#9670; LIVE CARD PREVIEW</span>
          <h4>友人帐名片实时预览</h4>
          <p>在下方填写你的站点信息，右侧名片会实时同步渲染出收录至本站「友人帐」后的真实切角卡片效果。</p>
        </div>

        <div class="gfl-live-card gfl-f-cut" aria-label="友链卡片实时预览">
          <div class="gfl-live-card__top">
            <span class="gfl-live-card__av gfl-f-cut" id="gflPvAv">
              <span id="gflPvInit"><?php echo esc_html($init_preview); ?></span>
              <?php if (!empty($form['link_image'])): ?>
                <img id="gflPvImg" src="<?php echo esc_url($form['link_image']); ?>" alt="" onerror="this.hidden=true">
              <?php else: ?>
                <img id="gflPvImg" src="" alt="" hidden onerror="this.hidden=true">
              <?php endif; ?>
            </span>
            <span class="gfl-live-card__nm">
              <b id="gflPvName"><?php echo esc_html($form['link_name'] ?: '你的博客名称'); ?></b>
              <small id="gflPvHost"><?php echo esc_html($host_preview); ?></small>
            </span>
          </div>
          <p class="gfl-live-card__desc" id="gflPvDesc"><?php echo esc_html($form['link_description'] ?: '常去串门的独立博客伙伴。一句话介绍你的站点定位与内容风格…'); ?></p>
        </div>
      </div>

      <!-- 友链自助申请表单 -->
      <form method="post" class="gfl-form" data-no-pjax>
        <?php wp_nonce_field('argon_friendlink_apply', 'argon_friendlink_nonce'); ?>
        <input type="hidden" name="fl_submit" value="1">

        <div class="gfl-form-grid">
          <div class="gfl-field">
            <label class="gfl-label" for="link_name"><span>网站名称</span><em>* 必填</em></label>
            <div class="gfl-input-box gfl-f-cut">
              <span class="gfl-input-ico" aria-hidden="true">✦</span>
              <input id="link_name" type="text" name="link_name"
                     placeholder="如：Gaoice" value="<?php echo esc_attr($form['link_name']); ?>" required>
            </div>
          </div>

          <div class="gfl-field">
            <label class="gfl-label" for="link_url"><span>网站地址</span><em>* 必填，可省 https://</em></label>
            <div class="gfl-input-box gfl-f-cut">
              <span class="gfl-input-ico" aria-hidden="true">🔗</span>
              <input id="link_url" type="text" name="link_url"
                     placeholder="如：blog.gaoice.run" value="<?php echo esc_attr($form['link_url']); ?>" required>
            </div>
          </div>

          <div class="gfl-field">
            <label class="gfl-label" for="link_image"><span>网站图标 / 头像 URL</span><em>* 必填</em></label>
            <div class="gfl-input-box gfl-f-cut">
              <span class="gfl-input-ico" aria-hidden="true">🖼️</span>
              <input id="link_image" type="text" name="link_image"
                     placeholder="https://example.com/avatar.png" value="<?php echo esc_attr($form['link_image']); ?>" required>
            </div>
          </div>

          <div class="gfl-field">
            <label class="gfl-label" for="link_email"><span>联系邮箱（用于接收审核通过通知）</span><em>* 必填</em></label>
            <div class="gfl-input-box gfl-f-cut">
              <span class="gfl-input-ico" aria-hidden="true">✉</span>
              <input id="link_email" type="email" name="link_email"
                     placeholder="you@example.com" value="<?php echo esc_attr($form['link_email']); ?>" required>
            </div>
          </div>

          <div class="gfl-field gfl-field--full">
            <label class="gfl-label" for="link_description"><span>网站简介 / 签名</span><em>* 必填</em></label>
            <div class="gfl-input-box gfl-f-cut">
              <span class="gfl-input-ico" aria-hidden="true">💬</span>
              <input id="link_description" type="text" name="link_description"
                     placeholder="一句话介绍你的站点，如：我知我新，未知的世界那么惊艳" value="<?php echo esc_attr($form['link_description']); ?>" required>
            </div>
          </div>

          <div class="gfl-field gfl-field--full">
            <label class="gfl-label" for="link_notes"><span>附加备注（可选，仅站长后台审核可见）</span><em>选填</em></label>
            <div class="gfl-input-box gfl-f-cut">
              <textarea id="link_notes" name="link_notes" rows="3"
                        placeholder="可在此附上贵站的友链页地址或留言说明…"><?php echo esc_textarea($form['link_notes']); ?></textarea>
            </div>
          </div>
        </div>

        <div class="gfl-foot">
          <span class="gfl-foot__hint">💡 提示：申请前请先将本站添加至贵站友链，提交后管理员将尽快审核。</span>
          <button type="submit" class="gfl-submit-btn gfl-f-cut">
            <span>提交友链申请</span>
            <i class="arr" aria-hidden="true"></i>
          </button>
        </div>
      </form>
    </div>

    <script>
    (() => {
      const nmIn = document.getElementById('link_name');
      const urlIn = document.getElementById('link_url');
      const imgIn = document.getElementById('link_image');
      const descIn = document.getElementById('link_description');
      if (!nmIn || !urlIn || !imgIn || !descIn) return;

      const pvName = document.getElementById('gflPvName');
      const pvHost = document.getElementById('gflPvHost');
      const pvInit = document.getElementById('gflPvInit');
      const pvImg  = document.getElementById('gflPvImg');
      const pvDesc = document.getElementById('gflPvDesc');

      const syncPreview = () => {
        const nm = nmIn.value.trim() || '你的博客名称';
        pvName.textContent = nm;
        const clean = nm.replace(/^[^\p{L}\p{N}]+/u, '') || nm;
        pvInit.textContent = (clean[0] || '友').toUpperCase();

        const rawUrl = urlIn.value.trim();
        if (rawUrl) {
          try {
            const full = /^https?:\/\//i.test(rawUrl) ? rawUrl : 'https://' + rawUrl.replace(/^\/+/, '');
            pvHost.textContent = new URL(full).hostname || rawUrl;
          } catch {
            pvHost.textContent = rawUrl;
          }
        } else {
          pvHost.textContent = 'your-blog.example.com';
        }

        pvDesc.textContent = descIn.value.trim() || '常去串门的独立博客伙伴。一句话介绍你的站点定位与内容风格…';
      };

      let imgTimer = 0;
      imgIn.addEventListener('input', () => {
        clearTimeout(imgTimer);
        imgTimer = setTimeout(() => {
          const raw = imgIn.value.trim();
          if (!raw) {
            pvImg.hidden = true;
            pvImg.removeAttribute('src');
            return;
          }
          const full = /^https?:\/\//i.test(raw) ? raw : 'https://' + raw.replace(/^\/+/, '');
          pvImg.hidden = false;
          pvImg.src = full;
        }, 180);
      });

      [nmIn, urlIn, descIn].forEach(el => el.addEventListener('input', syncPreview));
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('friendlink_apply', 'argon_friendlink_apply_shortcode');
