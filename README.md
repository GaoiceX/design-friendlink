# Gaoice / Argon Friendlink 友链自助申请与审核插件 (v2.0)

专为 **Gaoice Field Notes** 主题设计语言打造（同时向下兼容 Argon 主题）的 WordPress 友情链接自助申请与后台审核插件。

前台通过 `[friendlink_apply]` 短代码渲染带有 **「友人帐名片实时预览（Live Card Preview）」** 的硬朗切角申请表单；后台提供与主题设置中心同风格的可视化审核面板、4 大状态统计矩阵、通过/拒绝、下架/重新上架以及天穹刀锋风格 HTML 邮件通知。

**作者：** [Timozaici](https://github.com/timozaici) & [Gaoice](https://blog.gaoice.run)

---

## 核心特性 (v2.0 升级)

- **Gaoice Field Notes 切角视觉语言**：前台表单全面采用 45° 多边形硬朗切角（`.gfl-f-cut`，并自动跟随主题切换圆角/直角与日间/夜间/纯黑 OLED 模式），无任何第三方图标库依赖
- **友人帐名片实时预览**：访客在填写「网站名称、网站地址、网站图标、网站简介」时，上方实时同步渲染出收录至本站「友人帐」后的真实切角名片效果（头像加载失败自动回退为首字徽章）
- **可视化后台审核中心**：
  - 顶部天穹英雄栏 + 4 大状态统计卡片（`⏳ 待审核` / `✓ 已通过` / `⬇ 已下架` / `✕ 已拒绝`）
  - 审核列表自带申请站点切角头像预览（裂图自动降级为首字徽章）
  - 待审核申请可通过 / 拒绝；已通过友链可一键下架（同步 `wp_links.link_visible = N`）；已下架友链可重新上架（同步 `wp_links.link_visible = Y`）
- **天穹风格响应式 HTML 邮件**：
  - 新申请提交后自动发送深空蓝切角风格通知邮件至管理员邮箱
  - 审核通过后自动向申请人发送「友人帐收录通知」HTML 邮件（PC / 手机端自适应），按钮自动指向主题「友人帐」页面（或后台指定的任意已发布页面）
- **零配置自动建表与平滑升级**：启用时自动创建 `{prefix}friendlink_applications` 数据表，自动升级 `offline` 状态与 `wp_link_id` 映射字段

---

## 目录结构

```text
design-friendlink/
├── argon-friendlink.php   # 插件主文件（含前台短代码、后台审核中心与邮件模板）
├── install.sql            # 数据表安装 SQL（供无 CREATE 权限环境手动执行）
├── LICENSE                # GPL-3.0 许可证
├── README.md
└── .gitignore
```

---

## 安装与使用

1. 可直接将打包好的 `argon-friendlink.zip` 在 WordPress 后台 **插件 → 安装插件 → 上传插件** 中上传并启用，或将本目录上传至 `wp-content/plugins/argon-friendlink/`。
2. 新建或编辑友链申请页面（例如别名为 `/friend/`、标题为 `添加友链` 的页面），在正文中放入短代码：

   ```text
   [friendlink_apply]
   ```

3. 在 **Gaoice Field Notes** 主题下，`/friend/` 或包含 `[friendlink_apply]` 的页面会自动使用标准切角面板布局渲染，与 `/link/`（友人帐展示页）完美联动。
4. 在 WordPress 后台左侧点击 **「友链审核」** 即可进入可视化审核中心。

---

## 数据表结构

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

---

## 审核状态流转说明

| 当前状态 | 可用操作 | 结果 |
|---|---|---|
| `pending` | 通过 / 拒绝 | `approved`（写入 `wp_links` 并发送通过邮件） / `rejected` |
| `approved` | 下架 | `offline`，同时同步 `wp_links.link_visible = N` |
| `offline` | 重新上架 | `approved`，同时同步 `wp_links.link_visible = Y` |
| `rejected` | 无 | 保持不变 |

---

## 许可证

本项目基于 **GNU General Public License v3.0** 开源，详见 [LICENSE](LICENSE)。
