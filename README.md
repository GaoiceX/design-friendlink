# Argon Friendlink 管理插件

WordPress 友情链接自助申请与审核插件。前端表单复用 [Argon](https://github.com/solstice23/argon-theme) 主题的原生页面与组件样式，后台提供审核、下架、重新上架与邮件通知。

本插件仅确保在 [Argon](https://github.com/solstice23/argon-theme) 主题中可以正常使用

**作者：** [Timozaici](https://github.com/timozaici)

> **版本说明**
>
> `1.0` – `1.3` 为内部开发版本，**从未公开发布**。
> 当前 `1.4` 是首个公开发布版本。

## 功能

- 前台友链自助提交，表单视觉与 Argon 主题原生页面保持一致
- 后台审核列表：待审核、已审核通过、已拒绝、已下架
- 待审核申请可通过 / 拒绝
- 已通过友链可下架；下架时同步 `wp_links.link_visible = N`
- 已下架友链可重新上架；上架时同步 `wp_links.link_visible = Y`
- 已拒绝友链不提供任何操作入口
- 新申请邮件通知管理员
- 审核通过邮件发送给申请人（Argon 风格，PC / 手机端自适应）
- 数据库结构自动升级：`status` 增加 `offline`、新增 `wp_link_id`、回填历史映射
- 启用时自动创建数据表，自动使用当前 WordPress 表前缀
- 后台可选择审核通过邮件按钮跳转的已发布页面

## 环境要求

- WordPress 5.5+
- PHP 7.4+
- Argon 主题（前端样式与页面布局依赖其原生组件；其他主题下表单仍可用，但外观不保证一致）
- MySQL / MariaDB，支持 `utf8mb4`

## 目录结构

```text
argon-friendlink/
├── argon-friendlink.php   # 插件主文件
├── install.sql            # 数据表安装 SQL
├── LICENSE                # GPL-3.0 许可证
├── README.md
└── .gitignore
```

## 安装

1. 将本目录放入 `wp-content/plugins/argon-friendlink/`。
2. 在 WordPress 后台启用「Argon Friendlink 管理插件」。
3. 插件启用时会自动创建数据表（自动使用当前 WordPress 表前缀），一般无需手动操作。如果数据库账号权限受限，可执行 [`install.sql`](install.sql) 作为备用方案。
4. 新建一个页面，在正文中加入短代码：

   ```text
   [friendlink_apply]
   ```

   当前站点使用 Argon 主题的页面模板 `template-parts/content-page-friendlink.php` 获得原生页面布局。该模板属于主题集成文件，不在本插件内；如果不需要 Argon 原生布局，普通页面 + 短代码也可使用。
5. 后台左侧会出现「友链审核」菜单。

> 插件启用时会自动建表；已有旧表会在后台初始化时自动升级 `status` 枚举并新增 `wp_link_id` 字段。如果数据库账号没有 `CREATE` / `ALTER` 权限，请手动执行 `install.sql` 中的建表语句后自行迁移。

## 数据表

表名：`{prefix}friendlink_applications`

```sql
CREATE TABLE IF NOT EXISTS `wp_friendlink_applications` (
  `link_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_image` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_target` varchar(25) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_description` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_visible` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'N',
  `link_owner` bigint unsigned NOT NULL DEFAULT '1',
  `link_rating` int NOT NULL DEFAULT '0',
  `link_updated` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `link_rel` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_notes` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `link_rss` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_email` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `status` enum('pending','approved','rejected','offline') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'pending' COMMENT '审核状态',
  `wp_link_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '对应 wp_links.link_id',
  PRIMARY KEY (`link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='友情链接审核表';
```

字段说明：

- `status`：`pending`（待审核）、`approved`（已通过）、`rejected`（已拒绝）、`offline`（已下架）
- `wp_link_id`：对应 `wp_links.link_id`，用于下架 / 上架时精确同步链接可见性
- `link_visible`：申请记录中的可见性副本；公开页面实际以 `wp_links.link_visible` 为准

## 使用说明

### 前台提交

- 网站名称、网站简介按设计原样入库，不做内容合规检查
- 网站地址缺少协议时自动补全 `https://`，仅校验域名后缀；允许域名后带路径
- 网站图标为必填项，必须是有效的 http(s) 图片地址
- 联系邮箱仅校验格式
- 提交成功后进入「待审核申请」

### 后台审核

| 当前状态 | 可用操作 | 结果 |
|---|---|---|
| `pending` | 通过 / 拒绝 | `approved` / `rejected` |
| `approved` | 下架 | `offline`，同时 `wp_links.link_visible = N` |
| `offline` | 重新上架 | `approved`，同时 `wp_links.link_visible = Y` |
| `rejected` | 无 | 保持不变 |

- 只有 `approved` 可以下架，`pending` 和 `rejected` 即使伪造请求也会被跳过
- 只有 `offline` 可以重新上架
- 拒绝操作只对 `pending` 生效
- 后台操作带 nonce 校验，并要求 `manage_options` 权限

### 邮件跳转页面

在「友链审核」页面顶部的“邮件跳转页面”下拉框中选择任意已发布页面并保存。审核通过邮件中的“前往查看友情链接”按钮会跳转到该页面；不选择时使用默认地址 `/link.html`。

## 邮件说明

- **新申请通知**：每次前台提交后发送到 WordPress「管理员邮箱」，主题为“新友情链接申请待审核”
- **审核通过邮件**：后台点“通过”后发送给申请人，使用独立的 Argon 风格 HTML 模板，适配 PC 与手机
- **拒绝邮件**：当前版本不发送
- 审核通过邮件中的按钮指向后台选择的“邮件跳转页面”；未选择时默认 `/link.html`
- 邮件中的站点名称、申请人名称、URL 均已转义

## 安全说明

- 网站名称、网站简介按原样入库。插件自身的后台列表、前台表单回填、邮件模板均做了输出转义；第三方主题或插件直接读取 `wp_links` 时，需要自行转义。
- 网站图标地址会拒绝引号、尖括号、空白和控制字符；公开友链页输出图标时使用 `esc_url()`。
- 后台操作使用 nonce + `manage_options` 权限检查；前台表单使用 nonce，但**匿名 nonce 不构成反垃圾措施**。
- 生产环境建议额外增加：IP / 邮箱频率限制、验证码或蜜罐、重复 URL / 邮箱去重、管理员通知邮件节流。
- 插件会存储申请人邮箱、网站 URL 与备注；部署时请根据当地隐私法规设置数据保留与删除策略。
- 请勿将 `wp-config.php`、数据库导出、备份文件或包含真实申请人信息的日志提交到公开仓库。

## 已知限制

- 后台列表没有分页
- 没有内置频率限制或验证码
- 拒绝申请不发送邮件
- 审核通过邮件按钮默认指向 `/link.html`，可在后台选择其他页面
- 前端 UI 依赖 Argon 主题样式
- 自动建表 / 升级需要数据库 `CREATE` / `ALTER` 权限；权限不足时请手动执行 `install.sql`

## 版本与变更

- `1.0` – `1.3`：内部开发版本，**从未公开发布**
- `1.4`：首个公开发布版本
  - 前台提交页面按 Argon 原生页面实现
  - 明确字段校验与入库规则
  - 审核通过邮件为 Argon 风格并适配移动端
  - 新增已通过友链下架 / 重新上架，并同步 WordPress 链接可见性
  - 新增 `wp_link_id` 映射与历史数据回填
  - 启用时自动创建数据表，自动适配当前 WordPress 表前缀
  - 后台可选择审核通过邮件按钮跳转的页面
  - 安全加固：图标 URL 注入防护、公开页图标输出转义、后台权限与 nonce、模板 `ABSPATH` 保护

## 许可证

本项目使用的可证：**GNU General Public License v3.0**。

完整许可证文本见 [LICENSE](LICENSE)，许可证说明见 <https://www.gnu.org/licenses/gpl-3.0.html>。

## 致谢

- [Argon 主题](https://github.com/solstice23/argon-theme) — solstice23
- 作者 — [Timozaici](https://github.com/timozaici)
