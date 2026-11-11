# 校园圈子 · 前端设计系统

版本：2026-11-11  
适用范围：学生端全部页面、认证页面、后台页面  
原则：业务逻辑零改动，视觉、动效、性能、可访问性在前端层完整重建。

## 1. 视觉升级方案

### 设计语言

CampusLink 青春校园风：明亮、轻快、低负担。界面以暖白纸色为底、薄荷绿为主交互色、浅暖橙为点缀强调色，正文深灰、辅助文字浅灰；卡片靠「边框 + 背景」两层区分，不加投影也不做悬停上浮——悬停只换背景色，按压才下沉 1px。不出现赛博光效、霓虹撞色、仪表盘式数据堆砌或装饰性纹理，也不使用背景渐变（唯一的例外是首页 hero 标题的文字渐变）。

设计气质参考：

- Dribbble 校园社区类作品：卡片化信息流、圆润几何、留白呼吸感。
- Linear：克制的信息密度与精确层级。
- lucide 线性图标语言：1.8px 描边、圆角端点，全站图标统一。

### 三层视觉秩序

1. 背景层：`--bg` 暖白纸色（深色主题为绿黑系深底）。
2. 中层：卡片、输入区、侧栏，使用 1px 发丝线、哑光表面、软阴影；卡片悬停背景轻微提亮并上浮 2px。
3. 前景：导航、弹窗、浮动发布器，使用 Liquid Glass 与 Z 轴抬升。

### 主题

亮色为默认场景，暗色为低光阅读场景。两主题共用同一套色相（薄荷绿主色 + 暖橙点缀），深浅参数写入 Design Token，通过 `data-theme` 切换；首屏内联脚本在绘制前写入主题，避免闪烁。

## 2. 全局 Design Token 规范

文件：`assets/css/tokens.css`

### 颜色

- 表面：`--bg` / `--surface` / `--surface-2` / `--surface-3`
- 文字：`--ink` / `--ink-2` / `--muted` / `--faint`（正文 `#333`、次要 `#717171`）
- 边界：`--line` / `--line-2` / `--line-strong`
- 主色：`--accent` 系列（`#5fc4a2` 交互深、`#86d3b8` 亮态、含 ink / soft / glow），承担链接、按钮、选中态
- 点缀强调：`--accent-warm` 系列（`#ffb878` 暖橙 + soft 浅底 + ink），用于公告、热门提示等需要暖色引导的场景
- 语义色：`--danger` / `--success` / `--warning` / `--info` 及对应浅底

### 圆角

圆角取 Tailwind 刻度：`--r-xs 4px`、`--r-sm 8px`、`--r-md 12px`、`--r-lg 12px`、`--r-xl 16px`、`--r-2xl 24px`、`--r-pill 999px`。嵌套元素按 `inner = outer − gap` 推导，gap 小于 32px 且结果大于 2px 时才嵌套。

### 阴影

阴影只服务于「浮起来的东西」——下拉、弹窗、提示、悬浮按钮。静态内容卡片不用阴影，靠 `--surface` 与 `--surface-2` 的明度差加一圈 `--line` 边框来分层，避免满屏卡片的灰色糊成一片。带偏移与柔化层的软阴影，禁止零偏移硬阴影：

- `--shadow-xs`：1px 偏移，低对比（输入框聚焦前的暗示层）
- `--shadow-sm`：2px 偏移，悬浮元素
- `--shadow-md`：12px 偏移，下拉与卡片弹层
- `--shadow-lg`：28px 偏移，弹窗
- `--shadow-focus`：3px accent glow，聚焦

### 间距与栅格

间距只有 13 档：0 / 4 / 8 / 12 / 16 / 24 / 32 / 40 / 48 / 64 / 80 / 96。中间值不允许手写，一律落到最近刻度。  
页面宽度：桌面 1180px，窄页 760px；移动端内容边距 16-40px 随视口缩放。

### 字体

- 西文与数字：Manrope 400/500/600/700，自托管在 `assets/fonts/`（latin 子集，每个 14KB，附 OFL 许可）
- 中文：PingFang SC / HarmonyOS Sans SC / MiSans / Noto Sans SC / 微软雅黑
- 数据与时间：等宽栈 `ui-monospace, Cascadia Code, Consolas`，数字统一 `tabular-nums`
- 字号阶梯取 Tailwind 刻度：12 / 14 / 16 / 18 / 20 / 24 / 36 / 48 / 72px
- 字重上限 700，不使用 800/900；正文不用斜体

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
- 字体：Manrope 自托管，中文回退系统黑体；西文四个字重合计 56KB
- CSS：六层语义化文件，按需加载，无构建依赖

## 7. 演示数据

- 运行 `php bin/seed.php` 创建 6 个演示学生账号（密码统一 `Demo@123456`）。
- 运行 `php bin/seed_media.php` 为演示角色补齐头像、校园场景配图与新增动态。
- 演示场景图位于 `assets/media/demo/scenes/`，会复制到 `uploads/` 并在数据库建立关联。