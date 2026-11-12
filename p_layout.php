<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/autoload.php';

/**
 * Shared layout helpers for the Campus Circle interface.
 */

/**
 * 站点标志：光圈（六片叶片收拢成一个开口，相机里的光圈本来就叫「圈」）。
 * 内联而不是走 <img>，避免首屏闪一下空位；id 统一加 bmk 前缀防止和其它 svg 抢。
 */
function iconMark() {
    return '<span class="brand-mark" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><defs><radialGradient id="bmkm1" cx="0.28" cy="0.2" r="0.62"><stop offset="0" stop-color="#A6F7D6" stop-opacity="1"/><stop offset="1" stop-color="#A6F7D6" stop-opacity="0"/></radialGradient><radialGradient id="bmkm2" cx="0.78" cy="0.16" r="0.6"><stop offset="0" stop-color="#45D9C4" stop-opacity="1"/><stop offset="1" stop-color="#45D9C4" stop-opacity="0"/></radialGradient><radialGradient id="bmkm3" cx="0.86" cy="0.76" r="0.62"><stop offset="0" stop-color="#3E9BE8" stop-opacity="1"/><stop offset="1" stop-color="#3E9BE8" stop-opacity="0"/></radialGradient><radialGradient id="bmkm4" cx="0.18" cy="0.82" r="0.62"><stop offset="0" stop-color="#6D6DF0" stop-opacity="1"/><stop offset="1" stop-color="#6D6DF0" stop-opacity="0"/></radialGradient><radialGradient id="bmkvDepth" cx="0.5" cy="0.5" r="0.5"><stop offset="0" stop-color="#06382F" stop-opacity="0.34"/><stop offset="0.34" stop-color="#06382F" stop-opacity="0.12"/><stop offset="0.62" stop-color="#06382F" stop-opacity="0"/></radialGradient><mask id="bmkvMask"><circle cx="256" cy="256" r="206" fill="#fff"/><path d="M 335.67 302.00 L 256.00 348.00 L 176.33 302.00 L 176.33 210.00 L 256.00 164.00 L 335.67 210.00 Z" fill="#000"/><path d="M 257.50 182.00 L 326.70 53.84 L 310.44 48.87 L 254.50 182.00 Z" fill="#000"/><path d="M 320.84 220.30 L 466.43 216.14 L 462.60 199.58 L 319.34 217.70 Z" fill="#000"/><path d="M 319.34 294.30 L 395.73 418.31 L 408.16 406.71 L 320.84 291.70 Z" fill="#000"/><path d="M 254.50 330.00 L 185.30 458.16 L 201.56 463.13 L 257.50 330.00 Z" fill="#000"/><path d="M 191.16 291.70 L 45.57 295.86 L 49.40 312.42 L 192.66 294.30 Z" fill="#000"/><path d="M 192.66 217.70 L 116.27 93.69 L 103.84 105.29 L 191.16 220.30 Z" fill="#000"/></mask></defs><g mask="url(#bmkvMask)"><circle cx="256" cy="256" r="206" fill="#4FD3AE"/><circle cx="256" cy="256" r="206" fill="url(#bmkm1)"/><circle cx="256" cy="256" r="206" fill="url(#bmkm2)"/><circle cx="256" cy="256" r="206" fill="url(#bmkm3)"/><circle cx="256" cy="256" r="206" fill="url(#bmkm4)"/><circle cx="256" cy="256" r="206" fill="url(#bmkvDepth)"/></g></svg></span>';
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
        'hash' => '<path d="M9 4 7 20M17 4l-2 16M4.5 9h16M3.5 15h16"/>',
        'music' => '<path d="M9 18V6l10-2v12"/><circle cx="6.8" cy="18" r="2.4"/><circle cx="16.8" cy="16" r="2.4"/>',
        'ball' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5c2.8 2.3 4.2 5.2 4.2 8.5s-1.4 6.2-4.2 8.5"/><path d="M12 3.5c-2.8 2.3-4.2 5.2-4.2 8.5s1.4 6.2 4.2 8.5"/>',
        'gamepad' => '<path d="M7.5 7h9a5.5 5.5 0 0 1 5.4 6.5l-.7 4a2.6 2.6 0 0 1-4.5 1.2L14.6 16H9.4l-2.1 2.7a2.6 2.6 0 0 1-4.5-1.2l-.7-4A5.5 5.5 0 0 1 7.5 7z"/><path d="M8.5 10.5v3M7 12h3"/><path d="M16 11h.01M18 13h.01"/>',
        'book' => '<path d="M12 7c-1.5-1.7-3.8-2.5-7-2.5v13c3.2 0 5.5.8 7 2.5 1.5-1.7 3.8-2.5 7-2.5v-13c-3.2 0-5.5.8-7 2.5z"/><path d="M12 7v13"/>',
        'dumbbell' => '<path d="M6.5 6.5v11M17.5 6.5v11M3.5 9v6M20.5 9v6M6.5 12h11"/>',
        'film' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 4v16M16 4v16M4 9h4M4 15h4M16 9h4M16 15h4"/>',
        'coffee' => '<path d="M5 9h11v6a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5V9z"/><path d="M16 10h2.5a2.5 2.5 0 0 1 0 5H16"/><path d="M8 5.5V4M11.5 5.5V4.5M15 5.5V4"/>',
    ];
    if (!isset($paths[$name])) {
        return '';
    }
    return '<svg class="icon" width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

function circleIconFor($tag) {
    static $map = [
        '摄影' => 'camera', '拍照' => 'camera', '电影' => 'film', '动漫' => 'film', '二次元' => 'film',
        '音乐' => 'music', '乐器' => 'music', '舞蹈' => 'music', '吉他' => 'music', '唱歌' => 'music', '乐队' => 'music',
        '篮球' => 'ball', '羽毛球' => 'ball', '乒乓球' => 'ball', '足球' => 'ball', '操场' => 'ball', '球场' => 'ball',
        '阅读' => 'book', '写作' => 'book', '外语' => 'book', '心理学' => 'book',
        '学习' => 'book', '作业' => 'book', '复习' => 'book', '考试' => 'book', '简历' => 'book',
        '编程' => 'gamepad', '代码' => 'gamepad', '游戏' => 'gamepad', '电竞' => 'gamepad', '桌游' => 'gamepad',
        '网站' => 'feed', '技术' => 'feed',
        '跑步' => 'dumbbell', '健身' => 'dumbbell', '骑行' => 'dumbbell', '滑板' => 'dumbbell', '减肥' => 'dumbbell',
        '美食' => 'coffee', '咖啡' => 'coffee', '食堂' => 'coffee', '干饭' => 'coffee', '夜宵' => 'coffee',
        '绘画' => 'pen', '设计' => 'pen', '手作' => 'pen',
        '汉服' => 'tag', '旅行' => 'pin', '长春' => 'pin', '出游' => 'pin', '周边游' => 'pin',
        '志愿' => 'heart', '公益' => 'heart',
        '辩论' => 'chat', '面试' => 'chat', '聊天' => 'chat',
        '天文' => 'eye', '展览' => 'eye', '看展' => 'eye', '演出' => 'eye',
        '社团' => 'users', '聚会' => 'users', '组织' => 'users',
        '宿舍' => 'home', '寝室' => 'home',
        '周末' => 'calendar', '放假' => 'calendar', '假期' => 'calendar', '节假日' => 'calendar',
        '秋招' => 'id', '求职' => 'id', '实习' => 'id',
        '实验室' => 'school', '科研' => 'school',
    ];
    return $map[$tag] ?? 'hash';
}

/**
 * 「猜你喜欢」标签池：聚合已有动态（status=normal）用过的标签。
 * 过滤规则：2-8 个汉字或字母，纯数字开头/带标点/过长的碎片标签一律不要，
 * 否则会推荐出「91卢先生」这类只能命中一条动态的脏数据。
 */
function dynamicTagPool() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $cached = [];
    try {
        $conn = dbConnect();
        $res = $conn->query("SELECT `tags` FROM `dynamic` WHERE `status` = 'normal' AND `tags` <> '' LIMIT 5000");
        if ($res) {
            $counter = [];
            while ($row = $res->fetch_assoc()) {
                foreach (explode(',', (string)$row['tags']) as $piece) {
                    $piece = trim($piece);
                    if ($piece === '' || !preg_match('/^[\p{Han}A-Za-z]{2,8}$/u', $piece)) {
                        continue;
                    }
                    $counter[$piece] = ($counter[$piece] ?? 0) + 1;
                }
            }
            $res->free();
            arsort($counter);
            $cached = $counter;
        }
        $conn->close();
    } catch (Throwable $e) {
        // 侧栏推荐属增强内容，数据源异常时降级为不展示。
    }
    return $cached;
}

/**
 * 从动态标签池里随机挑 $limit 个。
 * 同一 session 内保持稳定（翻页不会跳），只有带 $refresh=true 的「换一批」才重抽。
 *
 * @param int   $limit   要几个
 * @param array $exclude 尽量避开的标签（一般是当前展示中的那几个）
 * @param bool  $refresh 是否强制重抽
 */
function railPickTags($limit = 3, array $exclude = [], $refresh = false) {
    static $memo = null;
    if (!$refresh && $memo !== null) {
        return $memo;
    }
    $pool = dynamicTagPool();
    if (empty($pool)) {
        return [];
    }
    $hasSession = session_status() === PHP_SESSION_ACTIVE;
    $sessionKey = 'rail_pick_tags';

    if (!$refresh && $hasSession && !empty($_SESSION[$sessionKey])) {
        $saved = array_values(array_filter((array)$_SESSION[$sessionKey], function ($tag) use ($pool) {
            return isset($pool[$tag]);
        }));
        if (!empty($saved)) {
            $memo = $saved;
            return $memo;
        }
    }

    $candidates = $pool;
    $exclude = array_values(array_intersect($exclude, array_keys($candidates)));
    // 池子太小就别硬排除，否则会一直只能抽到同一批。
    if (count($candidates) - count($exclude) < $limit) {
        $exclude = [];
    }
    foreach ($exclude as $excludedTag) {
        unset($candidates[$excludedTag]);
    }

    // 按标签被用过多少次做加权不放回抽样：用得多的更容易被推荐到，
    // 但冷门标签仍有机会，不至于来来回回就那几个。
    $picked = [];
    while (count($picked) < $limit && !empty($candidates)) {
        $before = count($picked);
        $total = (int)array_sum($candidates);
        $roll = mt_rand(1, max(1, $total));
        $acc = 0;
        foreach ($candidates as $tag => $weight) {
            $acc += (int)$weight;
            if ($roll <= $acc) {
                $picked[] = (string)$tag;
                unset($candidates[$tag]);
                break;
            }
        }
        if (count($picked) === $before) {
            break; // 兜底：极端权重下没抽中，避免死循环
        }
    }

    if ($hasSession) {
        $_SESSION[$sessionKey] = $picked;
    }
    $memo = $picked;
    return $picked;
}

/**
 * 渲染侧栏推荐链接。首屏和「换一批」接口共用同一份 HTML，样式不漂移。
 */
function railPickTagHtml(array $tags) {
    $currentTag = isset($_GET['tag']) ? trim((string)$_GET['tag']) : '';
    $html = '';
    foreach ($tags as $tag) {
        $tag = (string)$tag;
        if ($tag === '') {
            continue;
        }
        $active = ($currentTag !== '' && $currentTag === $tag) ? ' active' : '';
        $html .= '<a class="rail-link rail-circle' . $active . '" data-tag="' . h($tag) . '" title="' . h($tag) . '相关动态" href="p_dynamics.php?tag=' . h(urlencode($tag)) . '">' . icon(circleIconFor($tag), 19) . '<span>' . h($tag) . '</span></a>';
    }
    return $html;
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
    echo '<meta name="theme-color" content="#f2f4f7">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="校园圈子">' . "\n";
    echo '<meta property="og:title" content="' . h($title) . ' - 校园圈子">' . "\n";
    echo '<meta property="og:description" content="' . h($desc) . '">' . "\n";
    echo '<meta property="og:image" content="https://www.lucky-campus.top/uploads/og-cover.jpg">' . "\n";
    echo '<meta property="og:url" content="https://www.lucky-campus.top/">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
echo "<script>(function(){try{var t=localStorage.getItem('campus-theme')||(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>" . "\n";
    echo '<title>' . h($title) . ' - 校园圈子</title>' . "\n";
    echo '<link rel="icon" href="favicon.ico?v=20261111r13" sizes="any">' . "\n";
    echo '<link rel="icon" type="image/svg+xml" href="favicon.svg?v=20261111r13">' . "\n";
    echo '<link rel="apple-touch-icon" href="apple-touch-icon.png?v=20261111r13">' . "\n";
    echo '<link rel="manifest" href="site.webmanifest?v=20261111r13">' . "\n";
    echo '<link rel="stylesheet" href="style.css?v=20261111r13">' . "\n";
    echo '<link rel="stylesheet" href="design-system.css?v=20261111r13">' . "\n";
    renderCampusStyles();
    echo '</head>' . "\n";
    echo '<body>' . "\n";
    echo '<!-- THESIS: Campus life as a quiet instrument panel, not a decorated bulletin board. OWN-WORLD: gray paper, hairline rules, one blue accent, matte cards, liquid glass only on overlays. STORY: students read, publish and connect without noise. FORM: app-shell with desktop rail and mobile tab bar. -->' . "\n";
}
/**
 * 全站背景动效（浪涌气泡）：五层浪 + 两团极光 + 八颗上浮光泡。
 * 只输出一次、fixed 垫底（z-index:-1），卡片白底自然盖住，不挡点击。
 * 样式在 base.css 的 .page-fx 区块；手机端 CSS 会自动减到 4 颗气泡。
 */
function renderPageFx() {
    $orbs = [
        ['6%', '170px', '--c:rgba(0,82,217,.5);--d:62s;--dl:-8s;--o:.48'],
        ['19%', '100px', '--c:rgba(122,92,255,.45);--d:76s;--dl:-34s;--o:.4'],
        ['31%', '210px', '--c:rgba(0,164,255,.45);--d:88s;--dl:-20s;--o:.42'],
        ['45%', '110px', '--c:rgba(0,194,168,.5);--d:66s;--dl:-48s;--o:.42'],
        ['56%', '150px', '--c:rgba(0,82,217,.42);--d:72s;--dl:-14s;--o:.4'],
        ['67%', '92px', '--c:rgba(255,77,141,.4);--d:82s;--dl:-62s;--o:.34'],
        ['77%', '190px', '--c:rgba(122,92,255,.42);--d:94s;--dl:-30s;--o:.38'],
        ['89%', '120px', '--c:rgba(0,164,255,.46);--d:58s;--dl:-42s;--o:.44'],
    ];
    $html = '<div class="page-fx" aria-hidden="true">' . "\n";
    $html .= '<i class="pf-b1"></i>' . "\n" . '<i class="pf-b2"></i>' . "\n";
    // 气泡夹在中层浪之间，看起来是从水里冒出来的
    $html .= '<i class="pf-w pf-w5"></i>' . "\n" . '<i class="pf-w pf-w4"></i>' . "\n";
    foreach ($orbs as $n => $orb) {
        list($left, $size, $vars) = $orb;
        $html .= '<i class="pf-orb pf-o' . ($n + 1) . '" style="left:' . $left . ';width:' . $size . ';height:' . $size . ';' . $vars . '"></i>\n';
    }
    $html .= '<i class="pf-w pf-w3"></i>' . "\n" . '<i class="pf-w pf-w2"></i>' . "\n" . '<i class="pf-w pf-w1"></i>' . "\n";
    $html .= '</div>' . "\n";
    echo $html;
}

function renderSiteHeader($opts = []) {
    $nav = isset($opts['nav']) ? $opts['nav'] : [];
    $siteNav = isset($opts['siteNav']) ? $opts['siteNav'] : $nav;
    $mobileNav = isset($opts['mobileNav']) ? $opts['mobileNav'] : $nav;
    // 全站统一追加 AI 助手入口（页面自己声明过则不重复）
    $hasAiEntry = false;
    foreach ($nav as $item) {
        if (strpos((string)$item['href'], 'p_aiChat.php') !== false) {
            $hasAiEntry = true;
            break;
        }
    }
    if (!$hasAiEntry) {
        $nav[] = ['label' => 'AI 助手', 'href' => 'p_aiChat.php'];
        $siteNav[] = ['label' => 'AI 助手', 'href' => 'p_aiChat.php'];
        $mobileNav[] = ['label' => 'AI 助手', 'href' => 'p_aiChat.php'];
    }
    $headerClass = !empty($opts['hideSiteNavDesktop']) ? ' site-header--nav-desktop-hidden' : '';
    $actions = isset($opts['actions']) ? $opts['actions'] : '';
    $home = isset($opts['home']) ? $opts['home'] : 'p_welcomeStu.php';
    $GLOBALS['__campus_nav'] = $mobileNav;
    $GLOBALS['__campus_composer'] = !empty($opts['composer']);
    renderPageFx();
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
        // 四个主入口给彩色插画牌，其余入口退回「淡色底 + 线条」的次级样式
        $tile = navTile($item['label'], $active !== '');
        $linkClass = 'rail-link' . $active . ($tile !== '' ? '' : ' rail-link--soft');
        echo '<a class="' . $linkClass . '"' . ($active !== '' ? ' aria-current="page"' : '') . ' title="' . h($item['label']) . '" href="' . h($item['href']) . '">' . ($tile !== '' ? $tile : icon($navIcon, 19)) . '<span>' . h($item['label']) . '</span></a>' . "\n";
    }
    // 「猜你喜欢」只属于学生端；管理员后台侧栏不放标签推荐
    $isAdminRail = session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['adminName']);
    $pickTags = $isAdminRail ? [] : railPickTags(3);
    if (!empty($pickTags)) {
        echo '<div class="rail-picks" data-rail-picks>' . "\n";
        echo '<div class="rail-section-label">猜你喜欢</div>' . "\n";
        echo '<div class="rail-picks-list" data-rail-picks-list aria-live="polite">' . "\n";
        echo railPickTagHtml($pickTags);
        echo '</div>' . "\n";
        echo '<button type="button" class="rail-refresh" data-rail-refresh aria-label="换一批推荐标签">' . icon('refresh', 15) . '<span>换一批</span></button>' . "\n";
        echo '</div>' . "\n";
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
        ['label' => '隐私政策', 'href' => 'p_privacy.php'],
        ['label' => '用户协议', 'href' => 'p_terms.php'],
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
    echo '<script src="app.js?v=20261111r13"></script>' . "\n";
    echo '</div>' . "\n";
    echo '</div>' . "\n";
    if (!empty($GLOBALS['__campus_hide_dify_bubble'])) {
        // 自建 AI 界面的页面不注入官方气泡
    } elseif (!empty($_SESSION['adminName'])) {
        echo '<script>window.difyChatbotConfig={token:"ysipenxIDyTtnfRT",baseUrl:"https://udify.app",inputs:{},systemVariables:{},userVariables:{}};</script>';
        echo '<script src="https://udify.app/embed.min.js" id="ysipenxIDyTtnfRT" defer></script>';
        echo '<style>#dify-chatbot-bubble-button{background-image:linear-gradient(135deg,#0052d9,#00a4ff)!important;background-color:#0052d9!important;box-shadow:0 10px 24px -10px rgba(0,82,217,.6)!important;bottom:88px!important;width:48px!important;height:48px!important}#dify-chatbot-bubble-window{width:24rem!important;height:40rem!important}</style>';
    } elseif (!empty($_SESSION['userName'])) {
        echo '<script>window.difyChatbotConfig={token:"6evfFJQ2FdKb70eI",baseUrl:"https://udify.app",inputs:{},systemVariables:{},userVariables:{}};</script>';
        echo '<script src="https://udify.app/embed.min.js" id="6evfFJQ2FdKb70eI" defer></script>';
        echo '<style>#dify-chatbot-bubble-button{background-image:linear-gradient(135deg,#0052d9,#00a4ff)!important;background-color:#0052d9!important;box-shadow:0 10px 24px -10px rgba(0,82,217,.6)!important;bottom:88px!important;width:48px!important;height:48px!important}#dify-chatbot-bubble-window{width:24rem!important;height:40rem!important}</style>';
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
    echo '<meta name="theme-color" content="#f2f4f7">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="校园圈子">' . "\n";
    echo '<meta property="og:title" content="' . h($opts['title']) . ' - 校园圈子">' . "\n";
    echo '<meta property="og:description" content="校园圈子：学生实名认证、校园动态分享与同学交流平台。">' . "\n";
    echo '<meta property="og:image" content="https://www.lucky-campus.top/uploads/og-cover.jpg">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
echo "<script>(function(){try{var t=localStorage.getItem('campus-theme')||(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>" . "\n";
    echo '<title>' . h($opts['title']) . ' - 校园圈子</title>' . "\n";
    echo '<link rel="icon" href="favicon.ico?v=20261111r13" sizes="any">' . "\n";
    echo '<link rel="icon" type="image/svg+xml" href="favicon.svg?v=20261111r13">' . "\n";
    echo '<link rel="apple-touch-icon" href="apple-touch-icon.png?v=20261111r13">' . "\n";
    echo '<link rel="manifest" href="site.webmanifest?v=20261111r13">' . "\n";
    echo '<link rel="stylesheet" href="style.css?v=20261111r13">' . "\n";
    echo '<link rel="stylesheet" href="design-system.css?v=20261111r13">' . "\n";
    renderCampusStyles();
    echo '</head>' . "\n";
    echo '<body class="auth-body">' . "\n";
    echo '<!-- THESIS: Campus life as a quiet instrument panel, not a decorated bulletin board. OWN-WORLD: gray paper, hairline rules, one blue accent, matte cards, liquid glass only on overlays. STORY: students read, publish and connect without noise. FORM: app-shell with desktop rail and mobile tab bar. -->' . "\n";
    echo '<a class="skip-link" href="#authMain">跳到主要内容</a>' . "\n";
    echo '<main class="auth-shell" id="authMain">' . "\n";
    echo '<section class="auth-visual">' . "\n";
    echo '<div class="auth-visual-top">' . "\n";
    echo '<a class="brand" href="p_loginStu.php">' . iconMark() . '<span>校园圈子</span></a>' . "\n";
    echo renderThemeToggle();
    echo '</div>' . "\n";
    echo '<p class="auth-quote">' . h($quote) . '</p>' . "\n";
echo '<div class="auth-index"><span>实名认证</span><span>动态广场</span><span>站内私信</span></div>' . "\n";
    echo '<div class="auth-radar" aria-hidden="true"><span class="auth-radar-ring"></span><span class="auth-radar-ring is-outer"></span></div>' . "\n";
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
    echo '<script src="app.js?v=20261111r13"></script>' . "\n";
    echo '</body>' . "\n";
    echo '</html>' . "\n";
}
function renderCampusStyles() {
    $v = '20261111r13';
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

/**
 * 侧栏主入口的彩色插画牌：48 网格圆角方牌 + 白色剪影。
 * 为什么不用 CSS 画：方牌是渐变，选中项还要换成「白牌 + 本色图案」，
 * 两种状态各出一份 SVG 比在 CSS 里反推渐变最省事，也不受旧层样式干扰。
 * 颜色走 --cue-* 变量（style 属性里带 hex 兜底），深色模式下会自动变亮一档。
 */
function navTileGlyph($name) {
    static $g = [
        'home' => '<path d="M24 11.5 37 22.4v11.4a3.1 3.1 0 0 1-3.1 3.1H14.1A3.1 3.1 0 0 1 11 33.8V22.4Z" fill="#fff"/>'
            . '<rect x="20.6" y="27.5" width="6.8" height="9.4" rx="1.8" fill="#000" fill-opacity=".16"/>',
        'feed' => '<rect x="11" y="12" width="26" height="19" rx="6" fill="#fff"/>'
            . '<path d="M17.5 31 17.5 37.5 25 31Z" fill="#fff"/>'
            . '<rect x="16" y="18" width="16" height="2.6" rx="1.3" fill="#000" fill-opacity=".15"/>'
            . '<rect x="16" y="23" width="10" height="2.6" rx="1.3" fill="#000" fill-opacity=".15"/>',
        'people' => '<circle cx="19" cy="19" r="5.2" fill="#fff"/>'
            . '<path d="M9 34.5c0-5.3 4.4-8.6 10-8.6s10 3.3 10 8.6z" fill="#fff"/>'
            . '<circle cx="31.5" cy="20.5" r="4.2" fill="#fff" fill-opacity=".92"/>'
            . '<path d="M25 34.5c0-4.6 3.2-7.6 7.6-7.6 3.6 0 6.4 2.2 6.4 7.6z" fill="#fff" fill-opacity=".92"/>',
        'spark' => '<path d="M24 10.5c1.3 7.2 3.8 9.7 11 11-7.2 1.3-9.7 3.8-11 11-1.3-7.2-3.8-9.7-11-11 7.2-1.3 9.7-3.8 11-11z" fill="#fff"/>'
            . '<circle cx="33.5" cy="31.5" r="3.2" fill="#fff" fill-opacity=".9"/>',
        'bot' => '<circle cx="24" cy="10.6" r="2.5" fill="#fff"/>'
            . '<rect x="23.1" y="12.6" width="1.8" height="4.8" rx=".9" fill="#fff"/>'
            . '<rect x="13.2" y="16.8" width="21.6" height="15.4" rx="6.8" fill="#fff"/>'
            . '<rect x="10" y="21.6" width="3" height="5.8" rx="1.5" fill="#fff" fill-opacity=".92"/>'
            . '<rect x="35" y="21.6" width="3" height="5.8" rx="1.5" fill="#fff" fill-opacity=".92"/>'
            . '<circle cx="19.6" cy="23.6" r="2.2" fill="#000" fill-opacity=".32"/>'
            . '<circle cx="28.4" cy="23.6" r="2.2" fill="#000" fill-opacity=".32"/>'
            . '<path d="M20.6 27.8c1.1 1.6 2.4 2.4 3.4 2.4s2.3-.8 3.4-2.4" fill="none" stroke="#000" stroke-opacity=".3" stroke-width="1.7" stroke-linecap="round"/>'
            . '<circle cx="38.6" cy="8.8" r="1.8" fill="#fff" fill-opacity=".6"/>',
    ];
    return isset($g[$name]) ? $g[$name] : '';
}

function navTile($label, $active = false, $size = 24) {
    // 四个主入口各一对颜色，和 tokens.css 里的 --cue-1..4 同源
    static $map = [
        '学生中心' => ['home', '--cue-1', '#0052d9'],
        '动态广场' => ['feed', '--cue-2', '#00a4ff'],
        '同学' => ['people', '--cue-3', '#7a5cff'],
        'AI 助手' => ['bot', '--cue-4', '#00c2a8'],
    ];
    static $seq = 0;
    if (!isset($map[$label])) {
        return '';
    }
    list($glyph, $cue, $hex) = $map[$label];
    $body = navTileGlyph($glyph);
    $seq++;
    $head = '<svg class="icon" width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 48 48" aria-hidden="true">';
    if ($active) {
        // 选中项：白牌 + 本色图案。本色用 hex 而不是变量——牌底是白的，
        // 深色模式下也不需要换色。
        return $head . '<rect x="1" y="1" width="46" height="46" rx="14" fill="#ffffff"/>'
            . str_replace('fill="#fff"', 'fill="' . $hex . '"', $body) . '</svg>';
    }
    $id = 'nt' . $seq . '-' . substr(md5($glyph . $cue), 0, 5);
    return $head
        . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" style="stop-color:var(' . $cue . ',#0052d9)"/>'
        . '<stop offset="1" style="stop-color:var(' . $cue . '-2,#3d84ff)"/>'
        . '</linearGradient></defs>'
        . '<rect x="1" y="1" width="46" height="46" rx="14" fill="url(#' . $id . ')"/>'
        . $body . '</svg>';
}

/**
 * AI 助手头像：和侧栏 AI 入口同一个机器人方牌，替换掉原来那张糊的 jpg。
 * 渐变用 var(--cue-4)，深色模式自动提亮，和 tokens 同源。
 */
function aiAvatarMark($size = 40) {
    static $n = 0;
    $n++;
    $id = 'ai-mark-' . $n;
    return '<svg width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 48 48" role="img" aria-label="AI 助手">'
        . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" style="stop-color:var(--cue-4,#00c2a8)"/>'
        . '<stop offset="1" style="stop-color:var(--cue-4-2,#4ff0c8)"/>'
        . '</linearGradient></defs>'
        . '<rect x="1" y="1" width="46" height="46" rx="14" fill="url(#' . $id . ')"/>'
        . navTileGlyph('bot') . '</svg>';
}

/**
 * 聊天消息里的 AI 头像走 <img>，只能用 data URI（独立文档取不到页面 CSS 变量），
 * 所以这里硬编码 hex，和 tokens.css 里的 --cue-4 / --cue-4-2 保持同源，改色要两边一起改。
 */
function aiAvatarDataUri() {
    static $uri = null;
    if ($uri === null) {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48">'
            . '<defs><linearGradient id="am" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0" stop-color="#00c2a8"/><stop offset="1" stop-color="#4ff0c8"/>'
            . '</linearGradient></defs>'
            . '<rect x="1" y="1" width="46" height="46" rx="14" fill="url(#am)"/>'
            . navTileGlyph('bot') . '</svg>';
        $uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    return $uri;
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