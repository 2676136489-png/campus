# 校园圈子 · 前端设计系统

版本：2026-08-09  
适用范围：学生端全部页面、认证页面、后台页面  
原则：业务逻辑零改动，视觉、动效、性能、可访问性在前端层完整重建。

## 1. 视觉升级方案

### 设计语言

理性有机极简 + 数字化静谧美学。界面以无色系灰阶为主体，单一蓝色辅助色承担全部交互能量，不出现高饱和撞色、霓虹光、粗描边、卡通贴纸或装饰性纹理。

设计气质参考：

- Linear：克制的信息密度、精确的层级与键盘友好。
- Discord 网页端：稳定的应用外壳与模块化内容区。
- Are.na：留白叙事、内容本身成为视觉主角。
- Obys / Locomotive：电影级滚动节奏与分层景深。

### 三层视觉秩序

1. 背景层：`--bg` 灰纸色 + 极低噪点基底 + 弥散网格（仅认证页）。
2. 中层：卡片、输入区、侧栏，使用 1px 发丝线、哑光表面、软阴影。
3. 前景：导航、弹窗、浮动发布器，使用 Liquid Glass 与 Z 轴抬升。

### 主题

亮色为默认场景，暗色为低光阅读场景。所有颜色、阴影、玻璃参数写入 Design Token，通过 `data-theme` 切换；首屏内联脚本在绘制前写入主题，避免闪烁。

## 2. 全局 Design Token 规范

文件：`assets/css/tokens.css`

### 颜色

- 表面：`--bg` / `--surface` / `--surface-2` / `--surface-3`
- 文字：`--ink` / `--ink-2` / `--muted` / `--faint`
- 边界：`--line` / `--line-2` / `--line-strong`
- 唯一辅助色：`--accent` 系列（含 hover、active、ink、soft、glow）
- 语义色：`--danger` / `--success` / `--warning` / `--info` 及对应浅底

### 圆角

`--r-xs 4px`、`--r-sm 6px`、`--r-md 8px`、`--r-lg 12px`、`--r-xl 16px`、`--r-2xl 20px`、`--r-pill 999px`。卡片上限 20px，按钮/输入 8-12px。

### 阴影

只使用带偏移与柔化层的软阴影，禁止零偏移硬阴影：

- `--shadow-xs`：1px 偏移，低对比
- `--shadow-sm`：2px 偏移，悬浮
- `--shadow-md`：12px 偏移，卡片抬升
- `--shadow-lg`：28px 偏移，弹窗
- `--shadow-focus`：3px accent glow，聚焦

### 间距与栅格

间距刻度：4 / 8 / 12 / 16 / 20 / 24 / 28 / 32 / 40 / 48 / 64 / 80。  
页面宽度：桌面 1180px，窄页 760px；移动端内容边距 16-40px 随视口缩放。

### 字体

- 中文与西文：MiSans / HarmonyOS Sans SC / PingFang SC / Noto Sans SC / system-ui
- 数据与时间：等宽栈 `ui-monospace, Cascadia Code, Consolas`
- 字号阶梯：12 / 13.5 / 15 / 17 / 20 / 26 / 34 / 46px

## 3. 统一动效时序标准

文件：`assets/css/motion.css`、`assets/js/campus.js`

### 缓动

- `--ease-out: cubic-bezier(0.16, 1, 0.3, 1)`：页面进入、卡片浮现
- `--ease-out-2: cubic-bezier(0.22, 1, 0.36, 1)`：滚动揭示
- `--ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1)`：弹窗、点赞
- `--ease-in-out: cubic-bezier(0.77, 0, 0.175, 1)`：骨架屏

### 时长

90ms 按压反馈 / 160ms 微交互 / 260ms 状态切换 / 420ms 页面进入 / 640ms 图片与弹窗。

### 硬性规则

- 所有交互动效只使用 `transform` 与 `opacity`。
- 全局接入 Lenis 平滑滚动，降低滚动中断与跳变。
- 滚动驱动渐进显现使用 IntersectionObserver，阈值 0.12。
- 弹窗通过景深分层弹出：背景压暗 + 毛玻璃，内容 spring 进入。
- `prefers-reduced-motion: reduce` 下关闭全部动画与毛玻璃。
- 最小有效动效：同一时刻只编排一个主动效，不为装饰而堆叠。

## 4. 核心页面布局重构思路

### 全局应用壳

桌面端：固定左侧导航栏（品牌 + 导航 + 主题切换）与右侧流体主区；主区顶部为玻璃顶栏。  
移动端：顶栏 + 底部 Tab 栏 + 浮动发布按钮；导航抽屉在顶栏展开。

### 首页信息流

桌面三栏节奏：主信息流（虚拟化卡片）+ 右侧粘性快捷面板；移动端单列。帖子卡片使用 `content-visibility: auto` 与 `contain-intrinsic-size` 实现浏览器级虚拟滚动，图片懒加载并在加载完成前显示柔和骨架底。

### 帖子详情

新增 `p_post.php`：主栏展示完整内容、图片、点赞与评论；右侧作者卡与广场公约。点赞/评论/删除沿用原 API 与表单逻辑。

### 发布弹窗

全局浮动发布器改为底部弹层：内容、标签、照片在弹窗内完成，通过隐藏 iframe 提交到原有 `p_publishDynamic.php`，成功/失败解析后回写界面，后端逻辑零改动。

### 个人主页

顶部长卡展示头像、姓名、标签、关注统计与操作；下方为动态时间线。移动端改为居中式单列。

## 5. 可复用 UI 组件规范

### 按钮

状态：default / hover / active / focus-visible / disabled / loading。  
类型：`btn` 主按钮、`btn-soft` 浅底、`btn-ghost` 描边、`btn-danger`、`btn-dark`、`btn-sm`、`btn-block`。

### 输入

高度 42px，圆角 12px，聚焦时 3px accent glow；密码框内置可见性切换；文件上传使用 Dropzone 虚线框与拖拽反馈。

### 卡片

表面卡片、侧栏卡片、帖子卡片、同学卡片、通知卡片。卡片之间禁止嵌套；页区块使用全宽带，不使用浮空卡片包裹整页。

### 徽标 / 标签 / 头像

`badge` 状态徽标、`tag` 兴趣标签（可点击）、`avatar` 圆形头像，均提供中性与语义色变体。

### 弹窗 / 轻提示

`modal-backdrop` + `modal` 提供焦点、Escape、背景点击关闭；`toast` 从右下进入，2.6s 后淡出。

### 骨架屏 / 分页

`skeleton` 扫描线动画；AJAX 分页时旧卡片降透明并覆盖扫描高光，避免内容跳动。

### 无障碍

语义化标签、`aria-current="page"`、skip-link、键盘焦点环、`prefers-reduced-motion`、表单 label、图片 alt、`role="dialog"`。

## 6. 性能策略

- 帖子列表：`content-visibility: auto` + `contain-intrinsic-size`
- 图片：`loading="lazy"`、`decoding="async"`、上传端压缩
- 动效隔离：transform/opacity，避免重排重绘
- 分页：AJAX 局部替换 + 骨架占位
- 字体：系统字体栈，零额外字体请求
- CSS：六层语义化文件，按需加载，无构建依赖

## 7. 演示数据

- 运行 `php bin/seed.php` 创建 6 个演示学生账号（密码统一 `Demo@123456`）。
- 运行 `php bin/seed_media.php` 为演示角色补齐头像、校园场景配图与新增动态。
- 演示场景图位于 `assets/media/demo/scenes/`，会复制到 `uploads/` 并在数据库建立关联。