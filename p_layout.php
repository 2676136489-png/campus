<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/autoload.php';

/**
 * Shared layout helpers for the Campus Circle interface.
 */

function iconMark() {
    return '<span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><rect x="3.5" y="3.5" width="25" height="25" rx="3.5" stroke="currentColor" stroke-width="1.6" opacity="0.92"/><text x="16" y="22.8" text-anchor="middle" font-family="system-ui, sans-serif" font-size="17.5" font-weight="700" fill="currentColor">校</text></svg></span>';
}

function icon($name, $size = 18) {
    $paths = [
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'home' => '<path d="M4 11.5 12 5l8 6.5"/><path d="M6 10v9h12v-9"/>',
        'feed' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h6M8 17h4"/>',
        'users' => '<circle cx="9" cy="8.5" r="3.2"/><path d="M3.5 19c.6-3.1 2.7-4.8 5.5-4.8s4.9 1.7 5.5 4.8"/><path d="M15.5 5.6a3.2 3.2 0 0 1 0 5.8M17.6 14.4c1.8.8 2.8 2.4 3.1 4.6"/>',
        'user' => '<circle cx="12" cy="8" r="3.4"/><path d="M5 20c.8-3.4 3.4-5.2 7-5.2s6.2 1.8 7 5.2"/>',
        'heart' => '<path d="M12 20s-7-4.4-9.2-8.4C1.2 8.4 2.8 5 6 5c1.9 0 3.3 1 4 2.3C10.7 6 12.1 5 14 5c3.2 0 4.8 3.4 3.2 6.6C19 15.6 12 20 12 20z"/>',
        'chat' => '<path d="M4 6h16v11H9l-5 4V6z"/><path d="M8 10.5h8M8 13.5h5"/>',
        'trash' => '<path d="M5 7h14M10 7V5h4v2M7 7l1 13h8l1-13M10 11v5M14 11v5"/>',
        'edit' => '<path d="M5 19h4l10-10-4-4L5 15v4z"/><path d="M13 7l4 4"/>',
        'logout' => '<path d="M10 5H5v14h5"/><path d="M14 8l4 4-4 4"/><path d="M8 12h10"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'image' => '<rect x="4" y="5" width="16" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="M4 17l5-4 3 3 4-4 4 4"/>',
        'check' => '<path d="M5 12.5 10 17 19 7"/>',
        'shield' => '<path d="M12 3l7 3v5c0 4.6-3 8-7 9.5C8 19 5 15.6 5 11V6l7-3z"/><path d="M9.5 11.5l2 2 3.5-4"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
        'mail' => '<rect x="4" y="5.5" width="16" height="13" rx="2"/><path d="m4.5 7 7.5 5.5L19.5 7"/>',
        'phone' => '<path d="M6.5 4h3l1.5 4-2 1.5a12 12 0 0 0 5.5 5.5L16 13l4 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4.5 6.2 2 2 0 0 1 6.5 4z"/>',
        'tag' => '<path d="M4 4h7l9 9-7 7-9-9V4z"/><circle cx="8.5" cy="8.5" r="1.5"/>',
        'calendar' => '<rect x="4" y="5.5" width="16" height="15" rx="2"/><path d="M4 10h16M8 3.5v4M16 3.5v4"/>',
        'alert' => '<path d="M12 4 3.5 19h17L12 4z"/><path d="M12 10v4M12 16.8v.2"/>',
        'send' => '<path d="M5 12h13M13 6l6 6-6 6"/>',
        'eye' => '<path d="M3 12s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6z"/><circle cx="12" cy="12" r="2.4"/>',
        'eye-off' => '<path d="M4 4l16 16"/><path d="M9.9 5.9A8.8 8.8 0 0 1 12 5.7c5.5 0 9 6.3 9 6.3a16.5 16.5 0 0 1-3.2 3.8M6.2 6.2A15.4 15.4 0 0 0 3 12s3.5 6.3 9 6.3c1.3 0 2.5-.3 3.6-.8"/><path d="M9.5 9.6a3 3 0 0 0 4.2 4.2"/>',
        'id' => '<rect x="4" y="4.5" width="16" height="15" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="M6 16.5c.5-1.7 1.6-2.6 3-2.6s2.5.9 3 2.6M15 9.5h3M15 13h3"/>',
        'camera' => '<path d="M4 7h3l2-2.5h6L17 7h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3.4"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'school' => '<path d="M3 10 12 5l9 5-9 5-9-5z"/><path d="M6 12.5V17c0 1.1 2.7 2.5 6 2.5s6-1.4 6-2.5v-4.5M3 10v7"/>',
        'database' => '<ellipse cx="12" cy="5.5" rx="7" ry="2.8"/><path d="M5 5.5v6c0 1.5 3.1 2.8 7 2.8s7-1.3 7-2.8v-6"/><path d="M5 11.5v6c0 1.5 3.1 2.8 7 2.8s7-1.3 7-2.8v-6"/>',
        'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8v.2"/>',
        'bell' => '<path d="M6 9a6 6 0 0 1 12 0c0 4 2 5 2 5H4s2-1 2-5"/><path d="M10 18a2 2 0 0 0 4 0"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/>',
        'download' => '<path d="M12 4v10M8 10l4 4 4-4M5 19h14"/>',
        'smile' => '<circle cx="12" cy="12" r="8.5"/><path d="M8.5 14.5c.9 1.3 2.1 2 3.5 2s2.6-.7 3.5-2M9 9.5h.01M15 9.5h.01"/>',
        'pin' => '<path d="M9 4h6l-1 5 3 3v2H7v-2l3-3-1-5zM12 14v6"/>',
        'bell-off' => '<path d="M6 9a6 6 0 0 1 10.8-3.6M6 9c0 4-2 5-2 5h13M10 18a2 2 0 0 0 4 0M4 4l16 16"/>',
        'megaphone' => '<path d="M4 11v2l12 4V7L4 11z"/><path d="M16 8.5a3 3 0 0 1 0 7M7 16v3h3v-3"/>',
        'chart' => '<path d="M4 20h16M7 16v-5M12 16V7M17 16v-8"/>',
        'refresh' => '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M6.1 8.5A7 7 0 0 1 18.6 6M17.9 15.5A7 7 0 0 1 5.4 18"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M18.7 5.3l-1.4 1.4M6.7 17.3l-1.4 1.4"/>',
        'moon' => '<path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z"/>',
        'pen' => '<path d="M4 20h16"/><path d="M6.5 16.5 16 7l2.5 2.5L9 17l-4 1 1.5-4z"/><path d="m14 9 2.5 2.5"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'more' => '<circle cx="5" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1.4" fill="currentColor" stroke="none"/>',
        'panel' => '<rect x="3.5" y="4" width="17" height="16" rx="2.5"/><path d="M10 4v16"/>',
        'document' => '<path d="M7 3h7l5 5v13H7V3z"/><path d="M14 3v5h5M10 12h5M10 16h5"/>',
        'bookmark' => '<path d="M7 4h10v17l-5-3.5L7 21V4z"/>',
    ];
    if (!isset($paths[$name])) {
        return '';
    }
    return '<svg class="icon" width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

function renderHead($title, $description = '') {
    $desc = $description === ''
        ? '校园圈子：学生实名认证、校园动态分享与同学交流平台。'
        : $description;
    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="zh-CN">' . "\n";
    echo '<head>' . "\n";
    echo '<meta charset="UTF-8">' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
    echo '<meta name="description" content="' . h($desc) . '">' . "\n";
    echo '<meta name="theme-color" content="#f4f7fa">' . "\n";
echo "<script>(function(){try{var t=localStorage.getItem('campus-theme')||(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>" . "\n";
    echo '<title>' . h($title) . ' - 校园圈子</title>' . "\n";
    echo '<link rel="icon" type="image/svg+xml" href="favicon.svg?v=20260920">' . "\n";
    echo '<link rel="stylesheet" href="style.css?v=20260920a">' . "\n";
    echo '<link rel="stylesheet" href="design-system.css?v=20260920a">' . "\n";
    renderCampusStyles();
    echo '</head>' . "\n";
    echo '<body>' . "\n";
    echo '<!-- THESIS: 校园圈子是一个干净通透的实名校园社区。OWN-WORLD: 低饱和浅天蓝、8px 栅格、发丝线、单层微阴影。STORY: 学生在清爽的页面里读、写、相识。FORM: app-shell 桌面侧栏 + 移动底部标签栏。 -->' . "\n";
}
function renderSiteHeader($opts = []) {
    $nav = isset($opts['nav']) ? $opts['nav'] : [];
    $siteNav = isset($opts['siteNav']) ? $opts['siteNav'] : $nav;
    $mobileNav = isset($opts['mobileNav']) ? $opts['mobileNav'] : $nav;
    $headerClass = !empty($opts['hideSiteNavDesktop']) ? ' site-header--nav-desktop-hidden' : '';
    $actions = isset($opts['actions']) ? $opts['actions'] : '';
    $home = isset($opts['home']) ? $opts['home'] : 'p_welcomeStu.php';
    $GLOBALS['__campus_nav'] = $mobileNav;
    $GLOBALS['__campus_composer'] = !empty($opts['composer']);
    echo '<div class="app-shell">' . "\n";
    echo '<a class="skip-link" href="#main">跳到主要内容</a>' . "\n";
    echo '<aside class="side-rail" aria-label="主导航">' . "\n";
    echo '<a class="brand rail-brand" href="' . h($home) . '">' . iconMark() . '<span class="brand-name">校园圈子</span></a>' . "\n";
    echo '<nav class="rail-nav">' . "\n";
    $lastSection = '';
    foreach ($nav as $item) {
        $active = !empty($item['active']) ? ' active' : '';
        $navIcon = isset($item['icon']) ? $item['icon'] : navIconFor($item['label']);
        $section = isset($item['section']) ? $item['section'] : '';
        if ($section !== '' && $section !== $lastSection) {
            echo '<div class="rail-section-label">' . h($section) . '</div>' . "\n";
            $lastSection = $section;
        }
        echo '<a class="rail-link' . $active . '"' . ($active !== '' ? ' aria-current="page"' : '') . ' title="' . h($item['label']) . '" href="' . h($item['href']) . '">' . icon($navIcon, 19) . '<span>' . h($item['label']) . '</span></a>' . "\n";
    }
    echo '</nav>' . "\n";
    echo '<div class="rail-foot">' . "\n";
    echo renderThemeToggle();
    echo '<button type="button" class="icon-btn rail-toggle rail-foot-toggle" data-rail-toggle aria-label="收起或展开侧边栏" title="收起或展开侧边栏">' . icon('panel', 18) . '<span class="rail-toggle-label" data-rail-toggle-label>收起侧边栏</span></button>' . "\n";
    if (!empty($opts['railAction'])) {
        echo $opts['railAction'];
    }
    echo '</div>' . "\n";
    echo '</aside>' . "\n";
    echo '<div class="app-main">' . "\n";
    echo '<header class="site-header' . $headerClass . '" id="siteHeader">' . "\n";
    echo '<div class="container topbar-inner">' . "\n";
    echo '<button class="icon-btn rail-toggle" type="button" data-rail-toggle aria-label="切换侧边栏" title="切换侧边栏">' . icon('panel', 18) . '</button>' . "\n";
    echo '<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="siteNav" data-nav-toggle>' . icon('menu', 20) . '<span class="sr-only">打开导航</span></button>' . "\n";
    echo '<a class="brand topbar-brand" href="' . h($home) . '">' . iconMark() . '<span>校园圈子</span></a>' . "\n";
    echo '<nav class="site-nav" id="siteNav">' . "\n";
    foreach ($siteNav as $item) {
        $active = !empty($item['active']) ? ' active' : '';
        echo '<a class="nav-link' . $active . '"' . ($active !== '' ? ' aria-current="page"' : '') . ' href="' . h($item['href']) . '">' . h($item['label']) . '</a>' . "\n";
    }
    echo '</nav>' . "\n";
    echo '<div class="header-actions">' . renderThemeToggle() . $actions . '</div>' . "\n";
    echo '</div>' . "\n";
    echo '</header>' . "\n";
    echo '<main class="page-main" id="main">' . "\n";
    if (!empty($GLOBALS['__campus_composer'])) {
        echo '<button type="button" class="composer-fab" data-open-composer aria-label="发布动态">' . icon('pen', 24) . '</button>' . "\n";
    }
}
function renderSiteFooter($opts = []) {
    echo '</main>' . "\n";
    echo '<footer class="site-footer">' . "\n";
    echo '<div class="container footer-inner">' . "\n";
    echo '<span>校园圈子 · 让每一份校园生活被看见</span>' . "\n";
    echo '<nav class="footer-links">' . "\n";
    $links = isset($opts['links']) ? $opts['links'] : [
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
        ['label' => '学生注册', 'href' => 'p_registerStu.php'],
        ['label' => '管理员后台', 'href' => 'p_adminAuth.php?logout=1'],
    ];
    foreach ($links as $link) {
        echo '<a href="' . h($link['href']) . '">' . h($link['label']) . '</a>' . "\n";
    }
    echo '</nav>' . "\n";
    echo '</div>' . "\n";
    echo '</footer>' . "\n";
    if (!empty($GLOBALS['__campus_composer'])) {
        renderComposer();
    }
    renderMobileTabbar();
    renderCampusScripts();
    echo '<script src="app.js?v=20260816r9"></script>' . "\n";
    echo '</div>' . "\n";
    echo '</div>' . "\n";
    if (!empty($_SESSION['adminName'])) {
        echo '<script>window.difyChatbotConfig={token:"ysipenxIDyTtnfRT",baseUrl:"https://udify.app",inputs:{},systemVariables:{},userVariables:{}};</script>';
        echo '<script src="https://udify.app/embed.min.js" id="ysipenxIDyTtnfRT" defer></script>';
        echo '<style>#dify-chatbot-bubble-button{background-color:#1C64F2!important}#dify-chatbot-bubble-window{width:24rem!important;height:40rem!important}</style>';
    } elseif (!empty($_SESSION['userName'])) {
        echo '<script>window.difyChatbotConfig={token:"6evfFJQ2FdKb70eI",baseUrl:"https://udify.app",inputs:{},systemVariables:{},userVariables:{}};</script>';
        echo '<script src="https://udify.app/embed.min.js" id="6evfFJQ2FdKb70eI" defer></script>';
        echo '<style>#dify-chatbot-bubble-button{background-color:#1C64F2!important}#dify-chatbot-bubble-window{width:24rem!important;height:40rem!important}</style>';
    }
    echo '</body>' . "\n";
    echo '</html>' . "\n";
}
function adminNavItems() {
    return [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php', 'icon' => 'chart', 'section' => '总览'],
        ['label' => '学生入口', 'href' => 'p_loginStu.php', 'icon' => 'user', 'section' => '总览'],
        ['label' => '学生管理', 'href' => 'p_adminStudents.php', 'icon' => 'users', 'section' => '内容'],
        ['label' => '动态管理', 'href' => 'p_adminDynamics.php', 'icon' => 'feed', 'section' => '内容'],
        ['label' => '评论管理', 'href' => 'p_adminComments.php', 'icon' => 'chat', 'section' => '内容'],
        ['label' => '私信审计', 'href' => 'p_adminMessages.php', 'icon' => 'mail', 'section' => '安全'],
        ['label' => '举报审核', 'href' => 'p_adminReports.php', 'icon' => 'alert', 'section' => '安全'],
        ['label' => '审计日志', 'href' => 'p_adminAudit.php', 'icon' => 'database', 'section' => '安全'],
        ['label' => '管理员账号', 'href' => 'p_adminAdmins.php', 'icon' => 'shield', 'section' => '系统'],
    ];
}

function renderAdminHeader($active = '', $actions = '') {
    $isSuperAdmin = isset($_SESSION['adminName']) && getAdminRole($_SESSION['adminName']) === 'super';
    $nav = array_values(array_filter(adminNavItems(), function ($item) use ($isSuperAdmin) {
        if ($isSuperAdmin) {
            return true;
        }
        return !in_array($item['label'], ['学生管理', '审计日志', '管理员账号'], true);
    }));
    if ($active !== '') {
        foreach ($nav as &$item) {
            if ($item['label'] === $active) {
                $item['active'] = true;
            }
        }
        unset($item);
    }
    $mobileNav = array_values(array_filter([
        ['label' => '后台总览', 'href' => 'p_adminAuth.php', 'icon' => 'chart'],
        ['label' => '学生管理', 'href' => 'p_adminStudents.php', 'icon' => 'users'],
        ['label' => '举报审核', 'href' => 'p_adminReports.php', 'icon' => 'alert'],
    ], function ($item) use ($isSuperAdmin) {
        if ($isSuperAdmin) {
            return true;
        }
        return $item['label'] !== '学生管理';
    }));
    foreach ($mobileNav as &$mobileItem) {
        if ($mobileItem['label'] === $active) {
            $mobileItem['active'] = true;
        }
    }
    unset($mobileItem);
    $actions = $actions !== ''
        ? $actions
        : '<a class="btn btn-ghost btn-sm" href="p_adminAuth.php?logout=1">' . icon('logout') . '退出</a>';
    renderSiteHeader([
        'home' => 'p_adminAuth.php',
        'nav' => $nav,
        'siteNav' => $nav,
        'mobileNav' => $mobileNav,
        'hideSiteNavDesktop' => true,
        'actions' => $actions,
    ]);
}

function renderAuthShell($opts = []) {
    $quote = isset($opts['quote']) ? $opts['quote'] : '让每一份校园生活被看见。';
    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="zh-CN">' . "\n";
    echo '<head>' . "\n";
    echo '<meta charset="UTF-8">' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
    echo '<meta name="description" content="' . h(isset($opts['description']) ? $opts['description'] : '校园圈子：学生实名认证、校园动态分享与同学交流平台。') . '">' . "\n";
    echo '<meta name="theme-color" content="#f4f7fa">' . "\n";
echo "<script>(function(){try{var t=localStorage.getItem('campus-theme')||(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>" . "\n";
    echo '<title>' . h($opts['title']) . ' - 校园圈子</title>' . "\n";
    echo '<link rel="icon" type="image/svg+xml" href="favicon.svg?v=20260920">' . "\n";
    echo '<link rel="stylesheet" href="style.css?v=20260920a">' . "\n";
    echo '<link rel="stylesheet" href="design-system.css?v=20260920a">' . "\n";
    renderCampusStyles();
    echo '</head>' . "\n";
    echo '<body class="auth-body">' . "\n";
    echo '<!-- THESIS: 校园圈子是一个干净通透的实名校园社区。OWN-WORLD: 低饱和浅天蓝、8px 栅格、发丝线、单层微阴影。STORY: 学生在清爽的页面里读、写、相识。FORM: app-shell 桌面侧栏 + 移动底部标签栏。 -->' . "\n";
    echo '<a class="skip-link" href="#authMain">跳到主要内容</a>' . "\n";
    echo '<main class="auth-shell" id="authMain">' . "\n";
    echo '<section class="auth-visual">' . "\n";
    echo '<div class="auth-visual-top">' . "\n";
    echo '<a class="brand" href="p_loginStu.php">' . iconMark() . '<span>校园圈子</span></a>' . "\n";
    echo renderThemeToggle();
    echo '</div>' . "\n";
    echo '<p class="auth-quote">' . h($quote) . '</p>' . "\n";
echo '<div class="auth-index"><span>实名认证</span><span>动态广场</span><span>站内私信</span></div>' . "\n";
    echo '<p class="auth-credit">Campus Circle · 实名校园社交</p>' . "\n";
    echo '</section>' . "\n";
    echo '<section class="auth-panel">' . "\n";
    echo '<div class="auth-card">' . "\n";
}
function renderAuthShellClose() {
    echo '</div>' . "\n";
    echo '</section>' . "\n";
    echo '</main>' . "\n";
    renderCampusScripts();
    echo '<script src="app.js?v=20260816r9"></script>' . "\n";
    echo '</body>' . "\n";
    echo '</html>' . "\n";
}
function renderCampusStyles() {
    $v = '20260920d';
    echo '<link rel="stylesheet" href="assets/css/tokens.css?v=' . $v . '">' . "\n";
    echo '<link rel="stylesheet" href="assets/css/base.css?v=' . $v . '">' . "\n";
    echo '<link rel="stylesheet" href="assets/css/layout.css?v=' . $v . '">' . "\n";
    echo '<link rel="stylesheet" href="assets/css/components.css?v=' . $v . '">' . "\n";
    echo '<link rel="stylesheet" href="assets/css/pages.css?v=' . $v . '">' . "\n";
    echo '<link rel="stylesheet" href="assets/css/motion.css?v=' . $v . '">' . "\n";
}

function renderCampusScripts() {
    $v = '20260816';
    echo '<script src="assets/vendor/lenis.min.js"></script>' . "\n";
    echo '<script src="assets/js/campus.js?v=' . $v . '"></script>' . "\n";
}

function navIconFor($label) {
    $map = [
        '学生中心' => 'home',
        '动态广场' => 'feed',
        '同学' => 'users',
        '后台总览' => 'chart',
        '公告管理' => 'megaphone',
        '敏感词管理' => 'shield',
        '学生入口' => 'user',
        '学生登录' => 'user',
        '学生注册' => 'plus',
        '隐私政策' => 'lock',
        '用户协议' => 'document',
    ];
    return isset($map[$label]) ? $map[$label] : 'more';
}

function renderThemeToggle() {
    return '<button type="button" class="theme-switch" data-theme-toggle role="switch" aria-checked="false" aria-label="切换明暗主题">'
        . '<span class="theme-switch-track" aria-hidden="true">'
        . '<span class="theme-switch-sun"><span class="theme-icon" data-theme-icon-image="sun"></span></span>'
        . '<span class="theme-switch-moon"><span class="theme-icon" data-theme-icon-image="moon"></span></span>'
        . '</span>'
        . '<span class="theme-switch-thumb" aria-hidden="true"></span>'
        . '</button>';
}

function renderComposer() {
    echo '<div class="modal-backdrop composer-backdrop" data-composer hidden>' . "\n";
    echo '<section class="composer-sheet" role="dialog" aria-modal="true" aria-labelledby="composerTitle">' . "\n";
    echo '<header class="composer-head">' . "\n";
    echo '<div><h2 id="composerTitle">发布动态</h2><p>分享值得被看见的校园瞬间</p></div>' . "\n";
    echo '<button type="button" class="icon-btn" data-composer-close aria-label="关闭发布器">' . icon('close', 18) . '</button>' . "\n";
    echo '</header>' . "\n";
    echo '<form class="composer-editor" method="POST" action="p_publishDynamic.php" enctype="multipart/form-data" target="campusComposerFrame" data-composer-form>' . "\n";
    echo csrfField();
    echo '<textarea name="content" required maxlength="1000" placeholder="记录今天的校园生活..."></textarea>' . "\n";
    echo '<div class="composer-toolbar">' . "\n";
    echo '<input class="input" type="text" name="tags" maxlength="220" placeholder="标签，用逗号分隔">' . "\n";
    echo '<label class="icon-btn" for="composerPhotos" title="添加照片">' . icon('image', 18) . '</label>' . "\n";
    echo '<input type="file" name="photos[]" id="composerPhotos" accept="image/jpeg,image/png,image/gif" multiple data-composer-photo-input class="sr-only">' . "\n";
    echo '</div>' . "\n";
    echo '<div class="photo-preview-grid" data-composer-photos hidden></div>' . "\n";
    echo '<div class="composer-error" data-composer-error role="alert" hidden></div>' . "\n";
    echo '<footer class="composer-foot">' . "\n";
    echo '<span class="composer-hint">最多 3 张照片 · 内容 1000 字以内</span>' . "\n";
    echo '<button type="submit" name="publishDynamic" class="btn" data-composer-submit>' . icon('send', 16) . '发布</button>' . "\n";
    echo '</footer>' . "\n";
    echo '</form>' . "\n";
    echo '</section>' . "\n";
    echo '</div>' . "\n";
    echo '<iframe name="campusComposerFrame" class="composer-frame" data-composer-frame hidden tabindex="-1" aria-hidden="true"></iframe>' . "\n";
}

function renderMobileTabbar() {
    $nav = isset($GLOBALS['__campus_nav']) ? $GLOBALS['__campus_nav'] : [];
    if (empty($nav)) {
        return;
    }
    echo '<nav class="mobile-tabbar" aria-label="移动端导航">' . "\n";
    foreach ($nav as $item) {
        $active = !empty($item['active']) ? ' active' : '';
        $navIcon = isset($item['icon']) ? $item['icon'] : navIconFor($item['label']);
        echo '<a class="mobile-tab' . $active . '"' . ($active !== '' ? ' aria-current="page"' : '') . ' href="' . h($item['href']) . '">' . icon($navIcon, 21) . '<span>' . h($item['label']) . '</span></a>' . "\n";
    }
    echo '</nav>' . "\n";
}
/**
 * 学生端私信入口（带未读角标）
 * @param int $userPk 学生用户ID
 * @return string HTML
 */
function renderMessageBell($userPk) {
    $count = getUnreadMessageCount((int)$userPk);
    $badge = $count > 0
        ? '<span class="notify-badge" data-message-count>' . ($count > 99 ? '99+' : h($count)) . '</span>'
        : '<span class="notify-badge is-empty" data-message-count></span>';
    return '<a class="notify-link" href="p_messages.php" aria-label="私信">' . icon('chat', 20) . $badge . '</a>';
}

/**
 * 学生端通知铃铛（带未读角标）
 * @param int $userPk 学生用户ID
 * @return string HTML
 */
function renderNotificationBell($userPk) {
    $count = getUnreadNotificationCount((int)$userPk);
    $badge = $count > 0
        ? '<span class="notify-badge" data-notify-count>' . ($count > 99 ? '99+' : h($count)) . '</span>'
        : '<span class="notify-badge is-empty" data-notify-count></span>';
    return '<a class="notify-link" href="p_notifications.php" aria-label="通知中心">' . icon('bell', 20) . $badge . '</a>';
}

/**
 * 分页链接
 * @param string $base 基础地址
 * @param int $page 当前页
 * @param int $totalPages 总页数
 * @param array $filters 附加查询参数
 * @return string HTML
 */
function paginationLinks($base, $page, $totalPages, $filters = [], $pageParam = 'page') {
    if ($totalPages <= 1) {
        return '';
    }
    $page = max(1, (int)$page);
    $totalPages = max(1, (int)$totalPages);
    $build = function ($p) use ($base, $filters, $pageParam) {
        $query = array_merge($filters, [$pageParam => $p]);
        return $base . '?' . http_build_query($query);
    };
    $html = '<div class="pagination-wrap">';
    $html .= '<nav class="pagination" aria-label="分页">';
    if ($page > 1) {
        $html .= '<a class="page-link" href="' . h($build($page - 1)) . '">上一页</a>';
    }
    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
        $active = $i === $page ? ' is-active' : '';
        $html .= '<a class="page-link' . $active . '" href="' . h($build($i)) . '">' . $i . '</a>';
    }
    if ($page < $totalPages) {
        $html .= '<a class="page-link" href="' . h($build($page + 1)) . '">下一页</a>';
    }
    $html .= '</nav>';
    $html .= '<form class="page-jump" method="GET" action="' . h($base) . '">';
    foreach ($filters as $filterName => $filterValue) {
        $html .= '<input type="hidden" name="' . h($filterName) . '" value="' . h($filterValue) . '">';
    }
    $html .= '<input class="input page-jump-input" type="number" name="' . h($pageParam) . '" min="1" max="' . (int)$totalPages . '" value="' . (int)$page . '" inputmode="numeric" placeholder="页码" aria-label="跳转到第几页">';
    $html .= '<button type="submit" class="btn btn-ghost btn-sm">跳转</button>';
    $html .= '</form>';
    $html .= '</div>';
    return $html;
}