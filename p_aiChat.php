<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

// 必须先显式启动会话，才能在分流前读到会话类型（页面头部没有统一的 session 启动）
secureSessionStart();

// 学生与管理员共用同一个聊天界面：管理员会话走后台导航与管理员版 AI 应用，
// 学生会话走学生导航与学生版应用。后端 ai_chat_api.php 按会话类型自动分流。
$isAdminChat = session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['adminName']);
if ($isAdminChat) {
    $adminName = requireAdminLogin();
    // 与后台其他页一致：首次登录尚未改密时先引导到改密页
    if ($_SESSION['adminMustChangePassword'] ?? isInitialAdminPassword($adminName)) {
        header('Location: ' . tabUrl('p_adminAuth.php'));
        exit;
    }
    $actorName = $adminName;
    $actorAvatar = '';
} else {
    $student = requireStudentLogin();
    $actorName = $student['userName'];
    $actorAvatar = !empty($student['avatar']) && uploadFileExists($student['avatar']) ? thumbnailUrl($student['avatar'], 'avatar') : '';
}
// 本页是自建 AI 界面，隐藏右下角官方 Dify 气泡，避免双入口
$GLOBALS['__campus_hide_dify_bubble'] = true;

renderHead('AI 助手');
if ($isAdminChat) {
    // 管理员版头部：后台导航 + AI 助手高亮（与 renderAdminHeader 同一套角色过滤）
    $isSuperAdmin = getAdminRole($adminName) === 'super';
    $nav = array_values(array_filter(adminNavItems(), function ($item) use ($isSuperAdmin) {
        return $isSuperAdmin || !in_array($item['label'], ['学生管理', '审计日志', '管理员账号'], true);
    }));
    $nav[] = ['label' => 'AI 助手', 'href' => 'p_aiChat.php', 'active' => true];
    renderSiteHeader([
        'home' => 'p_adminAuth.php',
        'nav' => $nav,
        'siteNav' => $nav,
        'mobileNav' => $nav,
        'hideSiteNavDesktop' => true,
        'actions' => '<a class="btn btn-ghost btn-sm" href="p_adminAuth.php?logout=1">' . icon('logout') . '退出登录</a>',
    ]);
} else {
    renderSiteHeader([
        'nav' => [
            ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
            ['label' => '动态广场', 'href' => 'p_dynamics.php'],
            ['label' => '同学', 'href' => 'p_allStudents.php'],
            ['label' => 'AI 助手', 'href' => 'p_aiChat.php', 'active' => true],
        ],
        'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
        'composer' => ($student['status'] === 'V'),
    ]);
}
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <div class="hero-kicker">Campus AI</div>
            <h1>AI 助手</h1>
            <?php if ($isAdminChat): ?>
            <p class="muted">管理后台的智能助手：查站内数据、找内容，回答里可以直接点进对应页面。</p>
            <?php else: ?>
            <p class="muted">校园圈的智能向导：帮你找人、找动态、找公告，回答里可以直接点进对应页面。</p>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-soft btn-sm" data-ai-new-chat><?=icon('refresh')?>新会话</button>
    </div>

    <div class="chat-card card ai-chat-card">
        <div class="chat-head">
            <span class="avatar ai-avatar"><?=aiAvatarMark(40)?></span>
            <div>
                <div class="chat-name">校园圈子 AI 助手</div>
                <div class="muted small-text">回答附带同学 / 动态卡片，点击直达</div>
            </div>
        </div>

        <div class="chat-body ai-chat-body" data-ai-chat
             data-csrf="<?=h(csrfToken())?>"
             data-user-name="<?=h($actorName)?>"
             data-user-avatar="<?=h($actorAvatar)?>">
            <div class="ai-chat-empty" data-ai-empty>
                <span class="empty-icon"><?=icon('smile', 26)?></span>
                <div class="empty-title">问问看：「最近有什么校园活动？」</div>
                <div class="muted small-text">AI 的回答会引用站内的同学与动态，点击卡片即可跳转。</div>
            </div>
        </div>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-error"><?=icon('alert')?><div><?=h($_SESSION['flash_error'])?></div></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form class="ai-chat-input-row" data-ai-form data-api-action="1">
            <input class="input" type="text" name="question" data-ai-question
                   maxlength="500" autocomplete="off"
                   placeholder="输入问题，回车发送…" aria-label="输入问题">
            <button type="submit" class="btn" data-ai-send <?=icon('send', 16)?>发送</button>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';
    var root = document.querySelector('[data-ai-chat]');
    var form = document.querySelector('[data-ai-form]');
    var input = document.querySelector('[data-ai-question]');
    if (!root || !form || !input) return;
    var csrf = root.getAttribute('data-csrf');
    var userName = root.getAttribute('data-user-name');
    var userAvatar = root.getAttribute('data-user-avatar') || '';
    var AI_AVATAR = '<?=aiAvatarDataUri()?>';
    var lsKey = 'campus_ai_conversation_' + userName;
    var sending = false;

    // ---- 工具 ----
    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text !== undefined && text !== '') node.textContent = text;
        return node;
    }

    // 【同学|pk|姓名】/【动态|pk|摘要】→ 可点击卡片；其余文本安全渲染（轻量 markdown：粗体/列表符/换行）
    function escapeHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function mdText(s) {
        var esc = escapeHtml(s);
        esc = esc.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
        esc = esc.replace(/^[ \t]*[-*][ \t]+/gm, '');
        esc = esc.replace(/\n/g, '<br>');
        return esc;
    }
    function renderAnswer(target, text) {
        var pattern = /【(同学|动态)\|(\d+)\|([^\】]{1,40})】/g;
        var last = 0, match;
        while ((match = pattern.exec(text)) !== null) {
            if (match.index > last) {
                var pre = el('div', 'ai-chat-text');
                pre.innerHTML = mdText(text.slice(last, match.index));
                target.appendChild(pre);
            }
            var kind = match[1], pk = match[2], label = match[3];
            var a = document.createElement('a');
            a.className = 'ai-ref-card' + (kind === '同学' ? ' ai-ref-student' : ' ai-ref-dynamic');
            a.href = kind === '同学' ? 'p_profile.php?user=' + encodeURIComponent(pk) : 'p_dynamics.php?focus=' + encodeURIComponent(pk);
            var iconSpan = el('span', 'ai-ref-icon', kind === '同学' ? '👤' : '📝');
            var body = el('span', 'ai-ref-body');
            body.appendChild(el('span', 'ai-ref-kind', kind));
            body.appendChild(el('span', 'ai-ref-label', label));
            var arrow = el('span', 'ai-ref-arrow', '→');
            a.appendChild(iconSpan); a.appendChild(body); a.appendChild(arrow);
            target.appendChild(a);
            last = pattern.lastIndex;
        }
        if (last < text.length) {
            var tail = el('div', 'ai-chat-text');
            tail.innerHTML = mdText(text.slice(last));
            target.appendChild(tail);
        }
    }

    // 头像节点：AI 消息左侧 / 用户消息右侧（参考私信对话框的形态）
    function avatarNode(role) {
        if (role === 'user') {
            if (userAvatar !== '') {
                var img = document.createElement('img');
                img.className = 'ai-msg-avatar';
                img.src = userAvatar;
                img.alt = '';
                return img;
            }
            var ph = el('span', 'ai-msg-avatar ai-msg-avatar-ph', '👤');
            return ph;
        }
        var ai = document.createElement('img');
        ai.className = 'ai-msg-avatar';
        ai.src = AI_AVATAR;
        ai.alt = 'AI';
        return ai;
    }

    function addMessage(role, text) {
        var empty = root.querySelector('[data-ai-empty]');
        if (empty) empty.remove();
        var wrap = el('div', 'ai-msg' + (role === 'user' ? ' is-mine' : ''));
        var bubble = el('div', 'chat-bubble');
        if (role === 'user') {
            bubble.appendChild(el('div', 'chat-text', text));
        } else {
            renderAnswer(bubble, text);
        }
        wrap.appendChild(avatarNode(role));
        wrap.appendChild(bubble);
        root.appendChild(wrap);
        root.scrollTop = root.scrollHeight;
        return wrap;
    }

    function addThinking() {
        var wrap = addMessage('assistant', '');
        var bubble = wrap.querySelector('.chat-bubble');
        bubble.classList.add('ai-thinking');
        bubble.appendChild(el('span', 'ai-chat-text', '思考中…'));
        return wrap;
    }

    function addNotice(text) {
        var wrap = el('div', 'ai-chat-notice');
        wrap.appendChild(el('span', '', text));
        root.appendChild(wrap);
        root.scrollTop = root.scrollHeight;
    }

    function removeIfEmpty() {
        var empty = root.querySelector('[data-ai-empty]');
        if (empty) empty.remove();
    }

    // ---- 历史 ----
    function loadHistory() {
        var conversationId = localStorage.getItem(lsKey) || '';
        if (!conversationId) return;
        fetch('ai_chat_api.php?action=history&conversationId=' + encodeURIComponent(conversationId))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok || !data.messages || !data.messages.length) {
                    localStorage.removeItem(lsKey);
                    return;
                }
                data.messages.forEach(function (m) { addMessage(m.role, m.text); });
            })
            .catch(function () { /* 历史拉取失败不影响使用 */ });
    }

    // ---- 发送 ----
    function send(question) {
        if (sending) return;
        sending = true;
        removeIfEmpty();
        addMessage('user', question);
        input.value = '';
        var thinking = addThinking();

        var body = new URLSearchParams({ question: question, csrf_token: csrf });
        var conversationId = localStorage.getItem(lsKey) || '';
        if (conversationId) body.append('conversationId', conversationId);

        fetch('ai_chat_api.php?action=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        })
        .then(function (r) { return r.json().then(function (d) { return { code: r.status, data: d }; }); })
        .then(function (res) {
            thinking.remove();
            if (res.data && res.data.ok) {
                if (res.data.conversationId) localStorage.setItem(lsKey, res.data.conversationId);
                addMessage('assistant', res.data.answer);
            } else {
                var reason = { ai_not_configured: 'AI 服务尚未配置密钥，请联系管理员在 .env 填入 DIFY_API_KEY。',
                    ai_unreachable: 'AI 服务暂时连不上，请稍后再试。',
                    bad_question: '问题过长或为空。', bad_csrf: '页面令牌过期，请刷新页面后重试。' };
                addNotice((res.data && reason[res.data.error]) || '回答失败，请稍后再试。');
            }
        })
        .catch(function () {
            thinking.remove();
            addNotice('网络异常，请稍后再试。');
        })
        .finally(function () { sending = false; input.focus(); });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var q = input.value.trim();
        if (q !== '') send(q);
    });
    document.querySelector('[data-ai-new-chat]').addEventListener('click', function () {
        localStorage.removeItem(lsKey);
        while (root.firstChild) root.removeChild(root.firstChild);
        var empty = el('div', 'ai-chat-empty');
        empty.setAttribute('data-ai-empty', '');
        empty.innerHTML = '<span class="empty-icon"></span><div class="empty-title">新会话已开始</div>';
        root.appendChild(empty);
        addNotice('已开始新会话，之前的对话不会带入。');
        input.focus();
    });

    loadHistory();
    input.focus();
})();
</script>
<?php renderSiteFooter(); ?>

