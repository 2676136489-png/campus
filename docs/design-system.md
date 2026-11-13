# 校园圈子 · 前端设计系统

版本：2026-11-13  
适用范围：学生端全部页面、认证页面、后台页面  
原则：业务逻辑零改动，视觉、动效、性能、可访问性在前端层完整重建。

## 1. 视觉升级方案

### 设计语言

CampusLink 校园社交风：明亮、清爽、低负担。界面以冷调纸色为底、腾讯蓝为主交互色、青蓝与紫色为渐变点缀，正文深靛、辅助文字灰蓝；卡片靠「边框 + 背景」两层区分，不加投影也不做悬停上浮——悬停只换背景色，按压才下沉 1px。不出现赛博光效、霓虹撞色、仪表盘式数据堆砌或装饰性纹理。

设计气质参考：

- Dribbble 校园社区类作品：卡片化信息流、圆润几何、留白呼吸感。
- Linear：克制的信息密度与精确层级。
- 腾讯官网：主色取正蓝 `#0052D9`，渐变只出现在品牌标识与侧栏入口方牌上。
- lucide 线性图标语言：1.8px 描边、圆角端点，全站图标统一。

### 三层视觉秩序

1. 背景层：`.page-fx` 固定层——极光兜底 → 视频 → 玻璃雾罩，卡片浮在雾罩之上（见 §8）。
2. 中层：卡片、输入区、侧栏，使用 1px 发丝线、哑光表面、软阴影；卡片悬停背景轻微提亮并上浮 2px。
3. 前景：导航、弹窗、浮动发布器，使用 Liquid Glass 与 Z 轴抬升。

### 主题

亮色为默认场景，暗色为低光阅读场景。两主题共用同一套色相（腾讯蓝主色 + 青蓝点缀），深浅参数写入 Design Token，通过 `data-theme` 切换；首屏内联脚本在绘制前写入主题，避免闪烁。

## 2. 全局 Design Token 规范

文件：`assets/css/tokens.css`

### 颜色

- 表面：`--bg` / `--surface` / `--surface-2` / `--surface-3`
- 文字：`--ink` / `--ink-2` / `--muted` / `--faint`（正文 `#0f1b33`、次要 `#2d3f5e`）
- 边界：`--line` / `--line-2` / `--line-strong`
- 主色：`--accent` 系列（`#0052d9` 交互深、`#0046bd` 悬停、`#003da6` 按下，含 ink / soft / glow / bright），承担链接、按钮、选中态
- 点缀强调：`--accent-warm` 系列（`#00a4ff` 青蓝 + soft 浅底 + ink），用于公告、热门提示等需要冷色引导的场景
- 渐变与图标：`--grad-1~3`（蓝 → 青蓝 → 紫）、`--cue-1~4` 四组双色渐变，供侧栏入口方牌与标签色使用
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
- 背景视频：等 `window load` 才 `play()`，不抢首屏带宽；`playbackRate` 降到 0.6 减半帧率
- 降级策略：移动端（≤640px）、`saveData`、弱设备（`deviceMemory < 4` 或 `hardwareConcurrency < 4`）、`prefers-reduced-motion` 四种情况一律不加载视频，退回极光兜底
- 毛玻璃按需开启：hero 卡 blur 18px、侧栏 12px，统计卡与切角块不开 `backdrop-filter`（半透明白 + 亮边已足够，省一层合成）

## 7. 背景层：视频 + 玻璃雾罩

全站背景是一个 `position: fixed; z-index: -1` 的 `.page-fx` 层，三段叠放：

1. **极光兜底**：两团缓慢漂移的径向渐变，视频没出来时页面照样有氛围。
2. **视频层**：`assets/media/hero-flow.mp4`，1920×1080 / 740KB / 8s / 无音轨。素材本身是运镜镜头，家具会移动，遮罩盖不住，所以靠 `filter: saturate(0.62) brightness(1.05) blur(2px)` 降饱和 + 轻模糊把陈设化成柔和色块，再用 `transform: scale(1.08) translateY(-13%)` 改取景。
3. **玻璃雾罩**：四段 `linear-gradient` 透明度递减（`--fx-veil-a~d`）叠一个中央 `radial-gradient` 柔光斑，洗掉画面正中的高光区，保证卡片上的文字始终有足够对比度。

页面卡片浮在雾罩之上，形成「透过磨砂玻璃看背景」的层次。深色主题下雾罩不透明度与视频亮度都反向调整（`--fx-video-opacity: 0.42`）。

## 8. 登录页文字地球

`assets/js/auth-globe.js` 是 ThreeUI「Text Path Studies」的 2D canvas 移植：字符沿纬线铺在大陆位图上，随球体自转、拖拽、缩放。

- **墨色**取吉大校徽蓝 PANTONE 7455C（`#385DAE`），浅色主题加深一档（`#2C4C96`）、深色主题提亮一档（`#7E9EE2`），跟随 `data-theme` 用 MutationObserver 切换。
- **字形朝向必须让字顶指向球面北向**。原版在 Three.js 里摆字，朝向天然正确；移植到 2D canvas 后只能按纬线切线的屏幕投影定基线，而正交投影下切线的屏幕法向会落到球面内侧，结果正面所有字母上下颠倒（b→q、p→d、a→ɐ、e→ǝ）。现在的做法是用切线屏幕法向与「北向的屏幕投影」做点积，为负则整体转 180°。
- **阅读顺序**：可见面上经度越大越靠左，所以纬线环内按经度递减取点，短语才能沿屏幕从左往右连读。
- 旋转角按 64 步量化，命中 Chrome 的字形缓存，避免每帧重绘字形。
- 帧率自适应：`prefers-reduced-motion` 下球面静止，标签页不可见时暂停渲染。

## 9. 演示数据

- 运行 `php bin/seed.php` 创建 6 个演示学生账号（密码统一 `Demo@123456`）。
- 运行 `php bin/seed_media.php` 为演示角色补齐头像、校园场景配图与新增动态。
- 演示场景图位于 `assets/media/demo/scenes/`，会复制到 `uploads/` 并在数据库建立关联。