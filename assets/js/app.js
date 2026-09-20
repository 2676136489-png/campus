if (window.NodeList && !NodeList.prototype.forEach) {
    NodeList.prototype.forEach = Array.prototype.forEach;
}

(function () {
    'use strict';

    function generateTabToken() {
        var bytes = new Uint8Array(16);
        if (window.crypto && typeof crypto.getRandomValues === 'function') {
            crypto.getRandomValues(bytes);
        } else {
            for (var i = 0; i < bytes.length; i += 1) {
                bytes[i] = Math.floor(Math.random() * 256);
            }
        }
        var token = 'tab';
        for (var j = 0; j < bytes.length; j += 1) {
            var hex = bytes[j].toString(16);
            token += hex.length === 1 ? '0' + hex : hex;
        }
        return token;
    }

    function getTabToken() {
        if (!window.sessionStorage) {
            return '';
        }
        var token = sessionStorage.getItem('campus_tab_token');
        if (!token) {
            token = generateTabToken();
            sessionStorage.setItem('campus_tab_token', token);
        }
        return token;
    }
    window.getTabToken = getTabToken;

    var currentUrl = new URL(window.location.href);
    var urlTab = currentUrl.searchParams.get('tab');
    if (urlTab && !sessionStorage.getItem('campus_tab_token')) {
        sessionStorage.setItem('campus_tab_token', urlTab);
    }
    if (!currentUrl.searchParams.has('tab') && getTabToken()) {
        currentUrl.searchParams.set('tab', getTabToken());
        window.location.replace(currentUrl.toString());
        return;
    }

    var tab = getTabToken();
    if (currentUrl.searchParams.get('rotate') === '1') {
        sessionStorage.setItem('campus_tab_token', tab);
        currentUrl.searchParams.delete('rotate');
        history.replaceState({}, '', currentUrl.toString());
    }
    if (tab) {
        document.querySelectorAll('a[href]').forEach(function (a) {
            try {
                var url = new URL(a.getAttribute('href'), window.location.href);
                if (url.origin === window.location.origin && !url.searchParams.has('tab')) {
                    url.searchParams.set('tab', tab);
                    a.setAttribute('href', url.toString());
                }
            } catch (e) {}
        });
        document.querySelectorAll('form').forEach(function (form) {
            if (!form.querySelector('input[name="tab"]')) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tab';
                input.value = tab;
                form.appendChild(input);
            }
        });
        document.querySelectorAll('[data-captcha-image]').forEach(function (image) {
            image.setAttribute('src', 'p_captcha.php?tab=' + encodeURIComponent(tab) + '&t=' + Date.now());
        });
    }
})();

(function () {
    'use strict';

    var prefersReduced = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)').matches : false;

    document.querySelectorAll('img').forEach(function (img) {
        if (!img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }
        if (!img.hasAttribute('decoding')) {
            img.setAttribute('decoding', 'async');
        }
    });

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function formatSize(bytes) {
        if (!bytes) {
            return '0 KB';
        }
        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(0) + ' KB';
        }
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function captchaUrl() {
        var tab = getTabToken();
        return 'p_captcha.php' + (tab ? '?tab=' + encodeURIComponent(tab) + '&' : '?') + 't=' + Date.now();
    }

    function refreshCaptchaImages() {
        document.querySelectorAll('[data-captcha-image]').forEach(function (image) {
            image.src = captchaUrl();
        });
    }
    document.querySelectorAll('[data-captcha-refresh]').forEach(function (button) {
        button.addEventListener('click', refreshCaptchaImages);
    });
    document.querySelectorAll('[data-captcha-image]').forEach(function (image) {
        image.addEventListener('click', function () {
            image.src = captchaUrl();
        });
    });
    var loginForm = document.querySelector('[data-login-form]');
    if (loginForm) {
        var agreeTerms = loginForm.querySelector('#agreeTerms');
        var loginSubmit = loginForm.querySelector('[data-login-submit]');
        if (agreeTerms && loginSubmit) {
            var syncLoginSubmit = function () {
                loginSubmit.disabled = !agreeTerms.checked;
            };
            agreeTerms.addEventListener('change', syncLoginSubmit);
            syncLoginSubmit();
        }
    }

    var header = document.getElementById('siteHeader');
    if (header) {
        var updateHeader = function () {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        window.addEventListener('scroll', updateHeader, { passive: true });
        updateHeader();
    }


    function initCardSpotlight() {
        var cards = document.querySelectorAll('.card');
        if (!cards.length || !window.matchMedia || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            return;
        }
        var rafId = 0;
        cards.forEach(function (card) {
            card.addEventListener('pointermove', function (event) {
                if (rafId) {
                    return;
                }
                rafId = requestAnimationFrame(function () {
                    rafId = 0;
                    var rect = card.getBoundingClientRect();
                    card.style.setProperty('--spot-x', (event.clientX - rect.left).toFixed(1) + 'px');
                    card.style.setProperty('--spot-y', (event.clientY - rect.top).toFixed(1) + 'px');
                });
            });
            card.addEventListener('pointerleave', function () {
                card.style.removeProperty('--spot-x');
                card.style.removeProperty('--spot-y');
            });
        });
    }
    initCardSpotlight();

    function initCounters() {
        var items = document.querySelectorAll('[data-count], [data-follower-count], [data-following-count]');
        if (!items.length || !('IntersectionObserver' in window) || prefersReduced) {
            return;
        }
        function animate(item) {
            var raw = (item.textContent || '').replace(/,/g, '');
            var target = parseInt(raw, 10);
            if (isNaN(target)) {
                return;
            }
            var start = 0;
            var duration = 720;
            var startTime = null;
            function step(ts) {
                if (startTime === null) {
                    startTime = ts;
                }
                var progress = Math.min(1, (ts - startTime) / duration);
                var eased = 1 - Math.pow(1 - progress, 3);
                item.textContent = String(Math.round(start + (target - start) * eased));
                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            }
            requestAnimationFrame(step);
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });
        items.forEach(function (item) {
            observer.observe(item);
        });
    }
    initCounters();

    function ensureConfirm() {
        var box = document.getElementById('confirmModal');
        if (box) {
            return box;
        }
        box = document.createElement('div');
        box.className = 'modal-backdrop';
        box.id = 'confirmModal';
        box.innerHTML =
            '<div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmCopy">' +
            '<h3 class="modal-title" id="confirmTitle">确认操作</h3>' +
            '<p class="modal-copy" id="confirmCopy"></p>' +
            '<div class="modal-actions">' +
            '<button type="button" class="btn btn-ghost" data-confirm-cancel>取消</button>' +
            '<button type="button" class="btn btn-danger" data-confirm-ok>确认</button>' +
            '</div>' +
            '</div>';
        document.body.appendChild(box);
        return box;
    }

    function openConfirm(message, onConfirm) {
        var box = ensureConfirm();
        var copy = box.querySelector('#confirmCopy');
        copy.textContent = message;
        box.classList.add('is-open');
        var ok = box.querySelector('[data-confirm-ok]');
        var cancel = box.querySelector('[data-confirm-cancel]');
        ok.focus();

        var close = function () {
            box.classList.remove('is-open');
            ok.removeEventListener('click', okHandler);
            cancel.removeEventListener('click', cancelHandler);
            document.removeEventListener('keydown', keyHandler);
        };
        var okHandler = function () {
            close();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        };
        var cancelHandler = function () {
            close();
        };
        var keyHandler = function (event) {
            if (event.key === 'Escape') {
                close();
            }
        };
        ok.addEventListener('click', okHandler);
        cancel.addEventListener('click', cancelHandler);
        document.addEventListener('keydown', keyHandler);
    }

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm') || '确认执行此操作吗？';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    var lightbox = null;
    function ensureLightbox() {
        if (lightbox) {
            return lightbox;
        }
        lightbox = document.createElement('div');
        lightbox.className = 'lightbox-backdrop';
        lightbox.innerHTML =
            '<button type="button" class="lightbox-close" aria-label="关闭图片预览">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>' +
            '</button>' +
            '<img src="" alt="">';
        document.body.appendChild(lightbox);
        lightbox.addEventListener('click', function (event) {
            if (event.target === lightbox || event.target.closest('.lightbox-close')) {
                lightbox.classList.remove('is-open');
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
                lightbox.classList.remove('is-open');
            }
        });
        return lightbox;
    }

    document.querySelectorAll('.js-lightbox').forEach(function (image) {
        image.addEventListener('click', function () {
            var box = ensureLightbox();
            var preview = box.querySelector('img');
            preview.src = image.currentSrc || image.src;
            preview.alt = image.alt || '图片预览';
            box.classList.add('is-open');
        });
    });

    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        var preview = document.querySelector(input.getAttribute('data-preview'));
        var meta = input.closest('.dropzone') ? input.closest('.dropzone').querySelector('.file-meta') : null;
        var zone = input.closest('.dropzone');

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) {
                return;
            }
            if (meta) {
                meta.textContent = file.name + ' · ' + formatSize(file.size);
            }
            var image = preview && preview.tagName === 'IMG'
                ? preview
                : (preview ? preview.querySelector('img') : null);
            if (image) {
                var reader = new FileReader();
                reader.onload = function () {
                    image.src = reader.result;
                    preview.classList.remove('is-empty');
                };
                reader.readAsDataURL(file);
            }
        });

        if (zone) {
            ['dragenter', 'dragover'].forEach(function (name) {
                zone.addEventListener(name, function (event) {
                    event.preventDefault();
                    zone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (name) {
                zone.addEventListener(name, function (event) {
                    event.preventDefault();
                    zone.classList.remove('is-dragover');
                });
            });
        }
    });

    document.querySelectorAll('[data-count-for]').forEach(function (counter) {
        var input = document.getElementById(counter.getAttribute('data-count-for'));
        if (!input) {
            return;
        }
        var update = function () {
            var max = input.maxLength || 1000;
            counter.textContent = input.value.length + ' / ' + max;
        };
        input.addEventListener('input', update);
        update();
    });

    document.querySelectorAll('[data-tags-preview]').forEach(function (input) {
        var target = document.querySelector(input.getAttribute('data-tags-preview'));
        if (!target) {
            return;
        }
        var render = function () {
            var parts = input.value.split(/[,，、;；\s]+/).filter(Boolean).slice(0, 10);
            if (parts.length === 0) {
                target.innerHTML = '<span class="muted">标签会自动显示在这里</span>';
                return;
            }
            target.innerHTML = parts.map(function (tag) {
                return '<span class="tag">' + escapeHtml(tag) + '</span>';
            }).join('');
        };
        input.addEventListener('input', render);
        render();
    });

    document.querySelectorAll('.js-toggle-pwd').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-target'));
            if (!input) {
                return;
            }
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? '隐藏密码' : '显示密码');
            button.classList.toggle('is-active', show);
        });
    });

    function validateImageFile(file, maxSize) {
        if (!file) {
            return '';
        }
        if (!/^image\//i.test(file.type || '')) {
            return '仅支持图片文件';
        }
        if (maxSize > 0 && file.size > maxSize && !window.DataTransfer) {
            return '图片大小超过限制';
        }
        return '';
    }

    function compressImageFile(file, maxDim, maxSize) {
        return new Promise(function (resolve, reject) {
            if (!/^image\//i.test(file.type || '')) {
                reject('仅支持图片文件');
                return;
            }
            if (file.type === 'image/gif' || file.size <= 200 * 1024) {
                if (maxSize > 0 && file.size > maxSize) {
                    reject('图片大小超过限制');
                } else {
                    resolve(file);
                }
                return;
            }
            var reader = new FileReader();
            reader.onerror = function () {
                reject('图片读取失败');
            };
            reader.onload = function () {
                var image = new Image();
                image.onerror = function () {
                    if (maxSize > 0 && file.size > maxSize) {
                        reject('图片大小超过限制');
                    } else {
                        resolve(file);
                    }
                };
                image.onload = function () {
                    var scale = Math.min(1, maxDim / Math.max(image.width, image.height));
                    var width = Math.max(1, Math.round(image.width * scale));
                    var height = Math.max(1, Math.round(image.height * scale));
                    var canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(image, 0, 0, width, height);
                    var outputType = 'image/jpeg';
                    canvas.toBlob(function (blob) {
                        if (blob && blob.size < file.size) {
                            if (maxSize > 0 && blob.size > maxSize) {
                                reject('图片大小超过限制');
                            } else {
                                resolve(blob);
                            }
                        } else if (maxSize > 0 && file.size > maxSize) {
                            reject('图片大小超过限制');
                        } else {
                            resolve(file);
                        }
                    }, outputType, 0.8);
                };
                image.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }
    window.compressImageFile = compressImageFile;

    function replaceInputFiles(input, files) {
        if (!window.DataTransfer) {
            return false;
        }
        try {
            var transfer = new DataTransfer();
            files.forEach(function (file) {
                transfer.items.add(file);
            });
            input.files = transfer.files;
            return true;
        } catch (e) {
            return false;
        }
    }

    function renderPreviewGrid(grid, files) {
        if (!grid) {
            return;
        }
        grid.innerHTML = '';
        files.forEach(function (file) {
            var item = document.createElement('span');
            item.className = 'photo-preview-item';
            var img = document.createElement('img');
            img.alt = '';
            var reader = new FileReader();
            reader.onload = function () {
                img.src = reader.result;
            };
            reader.readAsDataURL(file);
            item.appendChild(img);
            grid.appendChild(item);
        });
        grid.hidden = files.length === 0;
    }

    function resetSinglePreview(input) {
        var previewSelector = input.getAttribute('data-preview');
        var preview = previewSelector ? document.querySelector(previewSelector) : null;
        if (!preview) {
            return;
        }
        preview.classList.add('is-empty');
        var img = preview.tagName === 'IMG' ? preview : preview.querySelector('img');
        if (img) {
            img.removeAttribute('src');
        }
    }

    document.querySelectorAll('input[type="file"][data-max-size]').forEach(function (input) {
        var maxSize = parseInt(input.getAttribute('data-max-size') || '0', 10) || 0;
        var maxCount = parseInt(input.getAttribute('data-max-count') || '0', 10) || 0;
        var maxDim = parseInt(input.getAttribute('data-max-dim') || '1600', 10) || 1600;
        var zone = input.closest('.dropzone');
        var meta = zone ? zone.querySelector('.file-meta') : null;
        var gridSelector = input.getAttribute('data-preview-grid');
        var grid = gridSelector ? document.querySelector(gridSelector) : null;
        var clearBtn = document.querySelector('button[data-clear-input="' + input.id + '"]');

        input.addEventListener('change', function () {
            var originals = Array.prototype.slice.call(input.files || []);
            var error = '';
            if (maxCount > 0 && originals.length > maxCount) {
                error = '最多选择 ' + maxCount + ' 张图片';
                input.value = '';
                originals = [];
            }
            if (meta) {
                meta.classList.remove('is-error');
                meta.textContent = originals.length ? '正在压缩图片...' : '';
            }
            input.setAttribute('data-compressing', '1');
            var compressionTasks = originals.map(function (file) {
                return Promise.race([
                    compressImageFile(file, maxDim, maxSize),
                    new Promise(function (resolve) {
                        setTimeout(function () { resolve(file); }, 10000);
                    })
                ]).catch(function (message) {
                    error = message;
                    return file;
                });
            });
            input._compressionPromise = Promise.all(compressionTasks).then(function (files) {
                if (error) {
                    input.value = '';
                    files = [];
                } else {
                    replaceInputFiles(input, files);
                }
                if (meta) {
                    meta.classList.toggle('is-error', error !== '');
                    meta.textContent = error || '';
                    if (!error && files.length) {
                        if (input.multiple) {
                            var total = files.reduce(function (sum, file) {
                                return sum + file.size;
                            }, 0);
                            meta.textContent = files.length + ' 张 · ' + formatSize(total);
                        } else {
                            meta.textContent = files[0].name + ' · ' + formatSize(files[0].size);
                        }
                    }
                }
                if (error) {
                    resetSinglePreview(input);
                }
                if (grid) {
                    renderPreviewGrid(grid, files);
                }
                if (clearBtn) {
                    clearBtn.hidden = files.length === 0;
                }
                input._compressedFiles = files;
                input.removeAttribute('data-compressing');
                });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                input.value = '';
                if (meta) {
                    meta.textContent = '';
                    meta.classList.remove('is-error');
                }
                if (grid) {
                    renderPreviewGrid(grid, []);
                }
                clearBtn.hidden = true;
                resetSinglePreview(input);
            });
        }
    });
    function buildCompressedFormData(form) {
        var data = new FormData(form);
        form.querySelectorAll('input[type="file"][data-max-size]').forEach(function (input) {
            var name = input.getAttribute('name');
            if (!name) {
                return;
            }
            data.delete(name);
            var files = input._compressedFiles || [];
            files.forEach(function (file) {
                data.append(name, file, file.name || 'image.jpg');
            });
        });
        return data;
    }

    function submitFileForm(form) {
        var submitButtons = form.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(function (button) {
            button.disabled = true;
            button.classList.add('is-loading');
            var label = button.querySelector('span[data-submit-label]');
            if (label) {
                label.textContent = '正在提交...';
            } else {
                button.textContent = '正在提交，请稍候...';
            }
        });
        var action = form.action || window.location.href;
        var data = buildCompressedFormData(form);
        var submitter = form.querySelector('button[type="submit"][name]');
        if (submitter) {
            data.append(submitter.getAttribute('name'), submitter.value || '');
        }
        fetch(action, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            redirect: 'follow'
        }).then(function (response) {
            return response.text().then(function (html) {
                var actionUrl = new URL(action, window.location.href);
                var finalUrl = new URL(response.url, window.location.href);
                if (actionUrl.pathname === finalUrl.pathname) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    document.title = doc.title;
                    document.body.innerHTML = doc.body.innerHTML;
                    if (window.bindAjaxContent) {
                        window.bindAjaxContent(document.body);
                    }
                } else {
                    window.location.href = response.url;
                }
            });
        }).catch(function () {
            submitButtons.forEach(function (button) {
                button.disabled = false;
                button.classList.remove('is-loading');
                var label = button.querySelector('span[data-submit-label]');
                if (label) {
                    label.textContent = '提交';
                }
            });
            if (window.showToast) {
                window.showToast('提交失败，请稍后再试。');
            } else {
                alert('提交失败，请稍后再试。');
            }
        });
    }

    document.querySelectorAll('form:not([data-api-action])').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var fileInputs = form.querySelectorAll('input[type="file"][data-max-size]');
            if (fileInputs.length) {
                event.preventDefault();
                var pendingInputs = form.querySelectorAll('input[data-compressing="1"]');
                if (pendingInputs.length) {
                    var submitButtons = form.querySelectorAll('button[type="submit"]');
                    submitButtons.forEach(function (button) {
                        button.disabled = true;
                        var label = button.querySelector('span[data-submit-label]');
                        if (label) {
                            label.textContent = '正在压缩图片...';
                        } else {
                            button.textContent = '正在压缩图片，请稍候...';
                        }
                    });
                    Promise.all(Array.from(pendingInputs).map(function (input) {
                        return input._compressionPromise || Promise.resolve();
                    })).then(function () {
                        submitFileForm(form);
                    });
                } else {
                    submitFileForm(form);
                }
                return;
            }
            if (form.getAttribute('data-submitting') === '1') {
                return;
            }
            form.setAttribute('data-submitting', '1');
            var submitButtons = form.querySelectorAll('button[type="submit"]');
            setTimeout(function () {
                submitButtons.forEach(function (button) {
                    button.disabled = true;
                    button.classList.add('is-loading');
                    var label = button.querySelector('span[data-submit-label]');
                    if (label) {
                        label.textContent = '正在提交...';
                    } else {
                        button.textContent = '正在提交，请稍候...';
                    }
                });
            }, 0);
        });
    });

    window.ensureLightbox = ensureLightbox;
    window.openConfirm = openConfirm;

    var revealItems = document.querySelectorAll('.reveal');
    if (!prefersReduced && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        revealItems.forEach(function (item) {
            observer.observe(item);
        });
    } else {
        revealItems.forEach(function (item) {
            item.classList.add('is-visible');
        });
    }
})();

(function () {
    'use strict';

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    var toast = null;
    function showToast(message) {
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'ajax-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.add('is-visible');
        clearTimeout(toast._timer);
        toast._timer = setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2600);
    }

    function updateNotifyBadge() {
        var badge = document.querySelector('[data-notify-count]');
        if (!badge || !window.fetch) {
            return;
        }
        fetch('api.php?action=unread&tab=' + encodeURIComponent(window.getTabToken ? window.getTabToken() : ''), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (response) {
            return response.json();
        }).then(function (json) {
            if (!json || !json.ok) {
                return;
            }
            var count = parseInt(json.unread || 0, 10);
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.classList.remove('is-empty');
            } else {
                badge.textContent = '';
                badge.classList.add('is-empty');
            }
        }).catch(function () {});
    }

    function buildChatMessage(message, mine) {
        var recalled = message.status === 'recalled';
        var text = recalled ? (mine ? '你撤回了一条消息' : '对方撤回了一条消息') : (message.content || '');
        var node = document.createElement('div');
        node.className = 'chat-message ' + (mine ? 'is-mine' : '') + (recalled ? ' is-recalled' : '');
        node.setAttribute('data-message-pk', message.pk);
        node.setAttribute('data-time', message.createTime || '');
        var meta = '<span class="chat-time">' + escapeHtml(message.createTime || '刚刚') + '</span>';
        if (mine && !recalled) {
            meta += '<span class="chat-read">' + (parseInt(message.isRead || 0, 10) === 1 ? '已读' : '未读') + '</span>';
        }
        var bubbleHtml = '';
        if (!recalled && message.image) {
            bubbleHtml += '<img class="chat-image" src="' + escapeHtml(message.image) + '" alt="图片消息" loading="lazy">';
        }
        if (text) {
            bubbleHtml += '<div class="chat-text">' + escapeHtml(text).replace(/\n/g, '<br>') + '</div>';
        }
        node.innerHTML = '<div class="chat-bubble">' + bubbleHtml + '</div><div class="chat-meta">' + meta + '</div>';
        return node;
    }

    function appendChatMessage(list, message, mine) {
        list.appendChild(buildChatMessage(message, mine));
        list.scrollTop = list.scrollHeight;
    }

    window.showToast = showToast;

    function sendApiForm(form, action, dynamicPk) {
        var data = new FormData(form);
        data.set('action', action);
        data.set('dynamicPk', dynamicPk);
        if (action === 'follow' || action === 'sendMessage' || action === 'block') {
            data.set('targetUser', form.getAttribute('data-target-user'));
        }
        return fetch('api.php', {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        });
    }

    function handleApiFormSubmit(form, event) {
            event.preventDefault();
            var action = form.getAttribute('data-api-action');
            if (action === 'deleteComment' && form.getAttribute('data-confirmed') !== '1') {
                var doDelete = function () {
                    form.setAttribute('data-confirmed', '1');
                    handleApiFormSubmit(form, event);
                };
                if (window.openConfirm) {
                    window.openConfirm('确定删除这条评论吗？删除后不可恢复。', doDelete);
                } else if (window.confirm('确定删除这条评论吗？删除后不可恢复。')) {
                    doDelete();
                }
                return;
            }

            var pk = form.getAttribute('data-dynamic-pk');
            var button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
            }
            sendApiForm(form, action, pk).then(function (json) {
                if (!json.ok) {
                    showToast(json.message || '操作失败，请重试。');
                    return;
                }
                if (action === 'like') {
                    var count = form.querySelector('[data-like-count]');
                    if (count) {
                        count.textContent = json.likeCount;
                    }
                    if (button) {
                        button.classList.toggle('is-liked', !!json.liked);
                    }
                }
if (action === 'favorite') {
                    var favCount = form.querySelector('[data-favorite-count]');
                    if (favCount) {
                        favCount.textContent = json.favoriteCount;
                    }
                    if (button) {
                        button.classList.toggle('is-favorited', !!json.favorited);
                    }
                    var favLabel = form.querySelector('[data-favorite-label]');
                    if (favLabel) {
                        favLabel.textContent = json.favorited ? '已收藏' : '收藏';
                    }
                    if (!json.favorited && form.getAttribute('data-remove-on-unfavorite') === '1') {
                        var favoriteItem = form.closest('.favorite-item');
                        if (favoriteItem) {
                            favoriteItem.remove();
                        }
                    }
                    showToast(json.favorited ? '已收藏。' : '已取消收藏。');
                }
                if (action === 'comment') {
                    var list = form.parentElement.querySelector('[data-comment-list]');
                    var countNode = form.parentElement.querySelector('[data-comment-count]');
                    if (list) {
                        var author = (json.comment && (json.comment.name || json.comment.userName)) || '同学';
                        var time = (json.comment && json.comment.createTime) || '刚刚';
                        var body = json.comment ? escapeHtml(json.comment.content).replace(/@([A-Za-z0-9_]{3,30})/g, '<span class="comment-mention">@$1</span>').replace(/\n/g, '<br>') : '';
                        var item = document.createElement('div');
                        item.className = 'comment-item' + (json.comment && json.comment.parentPk ? ' is-reply' : '');
                        var replyPrefix = json.comment && json.comment.parentName ? '<span class="comment-reply-to">回复 ' + escapeHtml(json.comment.parentName) + '：</span>' : '';
                        item.innerHTML = '<div class="comment-head"><span class="comment-author">' + escapeHtml(author) + '</span><span class="comment-time">' + escapeHtml(time) + '</span></div><div class="comment-body">' + replyPrefix + body + '</div>';
                        list.appendChild(item);
                        var replyInput = form.querySelector('[data-reply-input]');
                        var replyChip = form.querySelector('[data-reply-chip]');
                        if (replyInput) {
                            replyInput.value = '0';
                        }
                        if (replyChip) {
                            replyChip.hidden = true;
                        }
                    }
                    if (countNode) {
                        countNode.textContent = parseInt(countNode.textContent || '0', 10) + 1;
                    }
                    var input = form.querySelector('input[name="comment"]');
                    if (input) {
                        input.value = '';
                        input.focus();
                    }
                }
                if (action === 'block') {
                    var blockLabel = form.querySelector('[data-block-label]');
                    var blockBtn = form.querySelector('button[type="submit"]');
                    if (blockLabel) {
                        blockLabel.textContent = json.blocked ? '解除拉黑' : '拉黑';
                    }
                    if (blockBtn) {
                        blockBtn.classList.toggle('btn-danger-soft', !json.blocked);
                        blockBtn.classList.toggle('btn-ghost', !!json.blocked);
                    }
                    showToast(json.blocked ? '已拉黑该用户。' : '已解除拉黑。');
                }
                if (action === 'sendMessage') {
                    var chatList = document.querySelector('[data-message-list]');

                    if (chatList && json.message) {
                        var mine = String(json.message.senderPk) === String(chatList.getAttribute('data-current-user'));
                        appendChatMessage(chatList, json.message, mine);
                        var chatInput = form.querySelector('textarea[name="content"], input[name="content"]');
                        if (chatInput) {
                            chatInput.value = '';
                            if (window.localStorage) {
                                var other = form.getAttribute('data-target-user');
                                if (other) {
                                    localStorage.removeItem('chat_draft_' + other);
                                }
                            }
                        }
                        var imageInput = document.querySelector('[data-chat-image]');
                        var imagePreview = document.querySelector('[data-chat-image-preview]');
                        if (imageInput) {
                            imageInput.value = '';
                        }
                        if (imagePreview) {
                            imagePreview.hidden = true;
                            imagePreview.src = '';
                        }
                    }
                }
                if (action === 'follow') {
                    var followLabel = form.querySelector('[data-follow-label]');
                    var followBtn = form.querySelector('button[type="submit"]');
                    if (followLabel) {
                        followLabel.textContent = json.following ? '已关注' : '关注';
                    }
                    if (followBtn) {
                        followBtn.classList.toggle('btn-ghost', !!json.following);
                    }
                    var followerCount = document.querySelector('[data-follower-count]');
                    var followingCount = document.querySelector('[data-following-count]');
                    if (followerCount) {
                        followerCount.textContent = json.followerCount;
                    }
                    if (followingCount) {
                        followingCount.textContent = json.followingCount;
                    }
                    showToast(json.following ? '关注成功。' : '已取消关注。');
                }
                if (action === 'deleteComment') {
                    var item = form.closest('.comment-item');
                    var commentBox = form.closest('.comments');
                    if (item) {
                        item.remove();
                    }
                    if (commentBox) {
                        var countNode = commentBox.querySelector('[data-comment-count]');
                        if (countNode) {
                            countNode.textContent = Math.max(0, parseInt(countNode.textContent || '0', 10) - 1);
                        }
                    }
                    showToast('评论已删除。');
                }
                updateNotifyBadge();
            }).catch(function () {
                showToast('网络异常，请稍后重试。');
            }).then(function () {
                if (button) {
                    button.disabled = false;
                }
            });
    }

    document.querySelectorAll('form[data-api-action]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            handleApiFormSubmit(form, event);
        });
    });

    function initMentionInput(input) {
        var raw = input.getAttribute('data-mentions');
        if (!raw) {
            return;
        }
        var candidates = [];
        try { candidates = JSON.parse(raw); } catch (e) { return; }
        var form = input.closest('.comment-form');
        if (!form) {
            return;
        }
        var box = document.createElement('div');
        box.className = 'mention-dropdown';
        box.hidden = true;
        form.appendChild(box);
        var activeIndex = -1;
        var close = function () {
            box.hidden = true;
            box.innerHTML = '';
            activeIndex = -1;
        };
        var render = function (query) {
            var q = query.toLowerCase();
            var matches = candidates.filter(function (c) {
                return (c.userName || '').toLowerCase().indexOf(q) !== -1 || (c.name || '').toLowerCase().indexOf(q) !== -1;
            }).slice(0, 8);
            box.innerHTML = '';
            if (!matches.length) {
                box.hidden = true;
                return;
            }
            matches.forEach(function (c, i) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'mention-option' + (i === activeIndex ? ' is-active' : '');
                item.innerHTML = '<span class="mention-name">' + escapeHtml(c.name || '') + '</span><span class="mention-user">@' + escapeHtml(c.userName || '') + '</span>';
                item.addEventListener('click', function () {
                    insertMention(c.userName);
                });
                box.appendChild(item);
            });
            box.hidden = false;
        };
        var getTrigger = function () {
            var pos = input.selectionStart || input.value.length;
            var before = input.value.slice(0, pos);
            var match = before.match(/(?:^|\s)@([A-Za-z0-9_]*)$/);
            if (!match) {
                return null;
            }
            return { start: before.lastIndexOf('@'), query: match[1] };
        };
        var insertMention = function (username) {
            var trigger = getTrigger();
            if (!trigger) {
                return;
            }
            var pos = input.selectionStart || input.value.length;
            var value = input.value;
            input.value = value.slice(0, trigger.start) + '@' + username + ' ' + value.slice(pos);
            input.focus();
            var newPos = trigger.start + username.length + 2;
            input.setSelectionRange(newPos, newPos);
            close();
        };
        input.addEventListener('input', function () {
            var t = getTrigger();
            if (t) {
                activeIndex = -1;
                render(t.query);
            } else {
                close();
            }
        });
        input.addEventListener('keydown', function (e) {
            if (box.hidden) {
                return;
            }
            var options = box.querySelectorAll('.mention-option');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = (activeIndex + 1) % options.length;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = (activeIndex - 1 + options.length) % options.length;
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                if (options[activeIndex]) {
                    options[activeIndex].click();
                }
            } else if (e.key === 'Escape') {
                close();
            } else {
                return;
            }
            var t = getTrigger();
            render(t ? t.query : '');
        });
        document.addEventListener('click', function (e) {
            if (!form.contains(e.target)) {
                close();
            }
        });
    }
    document.querySelectorAll('input[name="comment"][data-mentions]').forEach(initMentionInput);

    function bindAjaxContent(container) {
        container.querySelectorAll('.js-lightbox').forEach(function (image) {
            image.addEventListener('click', function () {
                var box = window.ensureLightbox ? window.ensureLightbox() : null;
                if (!box) {
                    return;
                }
                var preview = box.querySelector('img');
                preview.src = image.currentSrc || image.src;
                preview.alt = image.alt || '图片预览';
                box.classList.add('is-open');
            });
        });
               container.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var message = form.getAttribute('data-confirm') || '确认执行此操作吗？';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
        container.querySelectorAll('form[data-api-action]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                handleApiFormSubmit(form, event);
            });
        });
        container.querySelectorAll('input[name="comment"][data-mentions]').forEach(initMentionInput);
        container.querySelectorAll('.reveal').forEach(function (item) {
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!reduce && 'IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12 });
                observer.observe(item);
            } else {
                item.classList.add('is-visible');
            }
        });
        container.querySelectorAll('.post-photo img').forEach(function (img) {
            var reveal = function () {
                img.classList.add('is-loaded');
            };
            if (img.complete && img.naturalWidth > 0) {
                reveal();
            } else {
                img.addEventListener('load', reveal);
            }
        });
    }

    window.bindAjaxContent = bindAjaxContent;

    document.addEventListener('click', function (event) {
        var link = event.target && event.target.closest ? event.target.closest('a.page-link') : null;
        if (!link) {
            return;
        }
        var target = link.closest('[data-paginate]');
        if (!target) {
            return;
        }
        event.preventDefault();
        if (target.getAttribute('data-paginating') === '1') {
            return;
        }
        var url = link.getAttribute('href');
        var key = target.getAttribute('data-paginate');
        target.setAttribute('data-paginating', '1');
        target.classList.add('is-paginating');
        var retried = false;
        var load = function () {
            return fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'text/html' },
                cache: 'no-store'
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.text();
            }).then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var next = doc.querySelector('[data-paginate="' + key + '"]');
                if (!next) {
                    throw new Error('section missing');
                }
                target.replaceWith(next);
                history.pushState({}, '', url);
                bindAjaxContent(next);
                next.classList.remove('is-paginating');
                next.removeAttribute('data-paginating');
                next.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }).catch(function () {
                if (!retried) {
                    retried = true;
                    return load();
                }
                target.classList.remove('is-paginating');
                target.removeAttribute('data-paginating');
                showToast('加载失败，请稍后再试。');
            });
        };
        load();
    });

    window.addEventListener('popstate', function () {
        var target = document.querySelector('[data-paginate]');
        if (!target) {
            return;
        }
        var key = target.getAttribute('data-paginate');
        fetch(window.location.href, {
            credentials: 'same-origin',
            headers: { 'Accept': 'text/html' },
            cache: 'no-store'
        }).then(function (response) {
            return response.text();
        }).then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var next = doc.querySelector('[data-paginate="' + key + '"]');
            if (next) {
                target.replaceWith(next);
                bindAjaxContent(next);
            }
        }).catch(function () {});
    });

    function buildRecommendCard(person) {
        var card = document.createElement('a');
        card.className = 'card recommend-card';
        card.href = 'p_profile.php?user=' + encodeURIComponent(person.pk);
        card.setAttribute('data-person-card', '');
        var avatar;
        if (person.avatar) {
            avatar = document.createElement('img');
            avatar.className = 'avatar';
            avatar.alt = '';
            avatar.src = person.avatar;
            avatar.addEventListener('error', function () {
                var placeholder = document.createElement('span');
                placeholder.className = 'avatar avatar-placeholder';
                avatar.replaceWith(placeholder);
            });
        } else {
            avatar = document.createElement('span');
            avatar.className = 'avatar avatar-placeholder';
        }
        card.appendChild(avatar);

        var main = document.createElement('div');
        main.className = 'recommend-main';
        var name = document.createElement('div');
        name.className = 'recommend-name';
        name.textContent = person.name || '';
        var college = document.createElement('div');
        college.className = 'muted small-text';
        college.textContent = person.college || '';
        main.appendChild(name);
        main.appendChild(college);
        if (person.sharedTags && person.sharedTags.length) {
            var tags = document.createElement('div');
            tags.className = 'tag-list recommend-tags';
            person.sharedTags.slice(0, 2).forEach(function (tag) {
                var span = document.createElement('span');
                span.className = 'tag';
                span.textContent = tag;
                tags.appendChild(span);
            });
            main.appendChild(tags);
        }
        card.appendChild(main);
        return card;
    }

    document.querySelectorAll('[data-recommend-refresh]').forEach(function (button) {
        button.addEventListener('click', function () {
            fetch('api.php?action=recommendations&tab=' + encodeURIComponent(window.getTabToken ? window.getTabToken() : ''), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }).then(function (response) {
                return response.json();
            }).then(function (json) {
                if (!json.ok) {
                    return;
                }
                var list = document.querySelector('[data-recommend-list]');
                if (!list) {
                    return;
                }
                list.innerHTML = '';
                if (!json.people.length) {
                    var empty = document.createElement('p');
                    empty.className = 'muted';
                    empty.textContent = '暂无推荐，去同学列表看看吧。';
                    list.appendChild(empty);
                    return;
                }
                json.people.forEach(function (person) {
                    list.appendChild(buildRecommendCard(person));
                });
            }).catch(function () {
                showToast('刷新失败，请稍后重试。');
            });
        });
    });

    var emojiToggle = document.querySelector('[data-emoji-toggle]');
    var emojiPanel = document.querySelector('[data-emoji-panel]');
    var chatInput = document.querySelector('[data-chat-input]');
    if (emojiToggle && emojiPanel && chatInput) {
        emojiToggle.addEventListener('click', function () {
            emojiPanel.hidden = !emojiPanel.hidden;
        });
        emojiPanel.querySelectorAll('[data-emoji]').forEach(function (button) {
            button.addEventListener('click', function () {
                chatInput.value += button.getAttribute('data-emoji');
                chatInput.focus();
            });
        });
    }

    var chatOtherUser = document.querySelector('[data-other-user]');
    if (chatInput && chatOtherUser && window.localStorage) {
        var draftKey = 'chat_draft_' + chatOtherUser.getAttribute('data-other-user');
        if (localStorage.getItem(draftKey)) {
            chatInput.value = localStorage.getItem(draftKey);
        }
        chatInput.addEventListener('input', function () {
            localStorage.setItem(draftKey, chatInput.value);
        });
    }

    var chatImageToggle = document.querySelector('[data-chat-image-toggle]');
    var chatImageInput = document.querySelector('[data-chat-image]');
    var chatImagePreview = document.querySelector('[data-chat-image-preview]');
    if (chatImageToggle && chatImageInput) {
        chatImageToggle.addEventListener('click', function () {
            chatImageInput.click();
        });
            chatImageInput.addEventListener('change', function () {
        var file = chatImageInput.files && chatImageInput.files[0];
        if (!file) {
            if (chatImagePreview) {
                chatImagePreview.hidden = true;
                chatImagePreview.removeAttribute('src');
            }
            return;
        }
        var original = file;
        var showPreview = function (target) {
            var reader = new FileReader();
            reader.onload = function () {
                if (chatImagePreview) {
                    chatImagePreview.src = reader.result;
                    chatImagePreview.hidden = false;
                }
            };
            reader.onerror = function () {
                showToast('图片预览失败，请重新选择。');
                if (chatImagePreview) {
                    chatImagePreview.hidden = true;
                    chatImagePreview.removeAttribute('src');
                }
            };
            reader.readAsDataURL(target);
        };
        if (window.compressImageFile) {
            window.compressImageFile(file, 1600, 20 * 1024 * 1024).then(function (compressed) {
                if (compressed && compressed !== original && window.DataTransfer) {
                    try {
                        var transfer = new DataTransfer();
                        transfer.items.add(compressed);
                        chatImageInput.files = transfer.files;
                    } catch (e) {}
                }
                showPreview(compressed || file);
            }).catch(function () {
                showPreview(file);
            });
        } else {
            showPreview(file);
        }
    });
    }

    document.querySelectorAll('[data-recall-pk]').forEach(function (button) {
        button.addEventListener('click', function () {
            var pk = button.getAttribute('data-recall-pk');
            var list = document.querySelector('[data-message-list]');
            var csrf = list ? list.getAttribute('data-csrf') : '';
            var data = new FormData();
            data.set('action', 'recallMessage');
            data.set('messagePk', pk);
            data.set('csrf_token', csrf);
            data.set('tab', window.getTabToken ? window.getTabToken() : '');
            fetch('api.php', {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (json) {
                if (!json.ok) {
                    showToast(json.message || '撤回失败。');
                    return;
                }
                var item = list.querySelector('[data-message-pk="' + pk + '"]');
                if (item) {
                    item.classList.add('is-recalled');
                    var bubble = item.querySelector('.chat-bubble');
                    if (bubble) {
                        bubble.textContent = '你撤回了一条消息';
                    }
                    var meta = item.querySelector('.chat-meta');
                    if (meta) {
                        meta.innerHTML = '<span class="chat-time">' + escapeHtml((item.getAttribute('data-time') || '刚刚')) + '</span>';
                    }
                }
                showToast('消息已撤回。');
            }).catch(function () {
                showToast('网络异常，请稍后重试。');
            });
        });
    });

    document.querySelectorAll('[data-conversation-setting]').forEach(function (button) {
        button.addEventListener('click', function () {
            var list = document.querySelector('[data-message-list]');
            var csrf = list ? list.getAttribute('data-csrf') : '';
            var field = button.getAttribute('data-field');
            var target = button.getAttribute('data-target-user');
            var value = button.getAttribute('data-value') !== '1';
            var data = new FormData();
            data.set('action', 'conversationSetting');
            data.set('targetUser', target);
            data.set('field', field);
            data.set('value', value ? '1' : '0');
            data.set('csrf_token', csrf);
            data.set('tab', window.getTabToken ? window.getTabToken() : '');
            fetch('api.php', {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (json) {
                if (!json.ok) {
                    showToast(json.message || '设置失败。');
                    return;
                }
                button.setAttribute('data-value', value ? '1' : '0');
                button.classList.toggle('is-active', value);
                var label = button.querySelector('[data-setting-label]');
                if (label) {
                    if (field === 'isPinned') {
                        label.textContent = value ? '已置顶' : '置顶';
                    } else {
                        label.textContent = value ? '已免打扰' : '免打扰';
                    }
                }
                showToast(value ? '已开启。' : '已关闭。');
            }).catch(function () {
                showToast('网络异常，请稍后重试。');
            });
        });
    });

          var chatList = document.querySelector('[data-message-list]');
    if (chatList) {
        var scrollChatToBottom = function () {
            var last = chatList.querySelector('.chat-message:last-child');
            if (last) {
                last.scrollIntoView({ block: 'end', behavior: 'auto' });
            }
            chatList.scrollTop = chatList.scrollHeight;
            window.scrollTo(0, document.documentElement.scrollHeight);
        };
        scrollChatToBottom();
        window.addEventListener('load', function () {
            setTimeout(scrollChatToBottom, 0);
            setTimeout(scrollChatToBottom, 300);
            setTimeout(scrollChatToBottom, 800);
            setTimeout(scrollChatToBottom, 1500);
        });
        chatList.querySelectorAll('img').forEach(function (img) {
            img.addEventListener('load', function () {
                setTimeout(scrollChatToBottom, 0);
            });
            img.addEventListener('error', function () {
                setTimeout(scrollChatToBottom, 0);
            });
        });
        if (window.MutationObserver) {
            var chatObserver = new MutationObserver(function () {
                scrollChatToBottom();
            });
            chatObserver.observe(chatList, { childList: true, subtree: true });
        }
    }
    var chatForm = document.querySelector('form[data-api-action="sendMessage"]');
    var loadMore = document.querySelector('[data-chat-load-more]');
    if (loadMore && chatList) {
        loadMore.addEventListener('click', function () {
            var first = chatList.querySelector('.chat-message');
            var before = first ? first.getAttribute('data-message-pk') : '0';
            var other = chatList.getAttribute('data-other-user');
            var heightBefore = chatList.scrollHeight;
            fetch('api.php?action=messagesBefore&tab=' + encodeURIComponent(window.getTabToken ? window.getTabToken() : '') + '&user=' + encodeURIComponent(other) + '&before=' + encodeURIComponent(before), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }).then(function (response) {
                return response.json();
            }).then(function (json) {
                if (!json.ok) {
                    return;
                }
                var mine = String(chatList.getAttribute('data-current-user'));
                json.messages.forEach(function (message) {
                    chatList.insertBefore(buildChatMessage(message, String(message.senderPk) === mine), chatList.firstChild);
                });
                chatList.scrollTop = chatList.scrollHeight - heightBefore;
                if (json.messages.length < 30) {
                    loadMore.remove();
                }
            }).catch(function () {});
        });
    }
    if (chatList && chatForm && window.fetch) {
        var otherUser = chatForm.getAttribute('data-target-user');
        var pollMessages = function () {
            var last = chatList.querySelector('.chat-message:last-child');
            var after = last ? last.getAttribute('data-message-pk') : '0';
            fetch('api.php?action=messages&tab=' + encodeURIComponent(window.getTabToken ? window.getTabToken() : '') + '&user=' + encodeURIComponent(otherUser) + '&after=' + encodeURIComponent(after), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }).then(function (response) {
                return response.json();
            }).then(function (json) {
                if (!json.ok) {
                    return;
                }
                var mine = String(chatList.getAttribute('data-current-user'));
                json.messages.forEach(function (message) {
                    appendChatMessage(chatList, message, String(message.senderPk) === mine);
                });
                var badge = document.querySelector('[data-message-count]');
                if (badge) {
                    var count = parseInt(json.unread || 0, 10);
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.classList.toggle('is-empty', count === 0);
                }
            }).catch(function () {});
        };
        setInterval(pollMessages, 8000);
    }

    if (window.fetch && !document.body.classList.contains('auth-body')) {
        updateNotifyBadge();
        var sse = null;
        var startSseFallback = function () {
            if (!window.__campusSseFallback) {
                window.__campusSseFallback = true;
                setInterval(updateNotifyBadge, 60000);
            }
        };
        window.__campusSse = null;
        window.closeCampusSse = function () {
            if (window.__campusSse) {
                window.__campusSse.close();
                window.__campusSse = null;
            }
        };
        window.addEventListener('pagehide', function () {
            window.closeCampusSse();
        });
        if (window.EventSource) {
            try {
                sse = new EventSource('p_sse.php');
                window.__campusSse = sse;
                sse.addEventListener('unread', function () {
                    updateNotifyBadge();
                });
                sse.onerror = function () {
                    if (sse) {
                        sse.close();
                        sse = null;
                        window.__campusSse = null;
                    }
                    startSseFallback();
                };
            } catch (e) {
                startSseFallback();
            }
        } else {
            startSseFallback();
        }
    }
})();
(function () {
    'use strict';

    var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)').matches : false;
    var params = new URLSearchParams(window.location.search);
    var focusId = params.get('focus');
    if (focusId) {
        var target = document.getElementById('dynamic-' + focusId);
        if (target) {
            target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
            target.classList.add('is-focused');
            setTimeout(function () {
                target.classList.remove('is-focused');
            }, 2600);
        }
    }
})();

(function () {
    'use strict';

    function initFeedInfinite() {
        var feed = document.querySelector('[data-feed-infinite]');
        if (!feed) {
            return;
        }
        var page = parseInt(feed.getAttribute('data-feed-page') || '1', 10);
        var totalPages = parseInt(feed.getAttribute('data-feed-total-pages') || '1', 10);
        var sentinel = feed.querySelector('[data-feed-sentinel]');
        if (!sentinel) {
            return;
        }
        var status = sentinel.querySelector('[data-feed-status]');
        var loading = false;
        var finished = page >= totalPages;

        var finish = function (text) {
            finished = true;
            sentinel.classList.remove('is-loading');
            if (status) {
                status.textContent = text || '已经到底了';
            }
        };

        var loadNext = function () {
            if (loading || finished) {
                return;
            }
            loading = true;
            sentinel.classList.add('is-loading');
            if (status) {
                status.textContent = '加载中...';
            }
            var url = new URL(window.location.href);
            url.searchParams.set('page', String(page + 1));
            fetch(url.toString(), {
                credentials: 'same-origin',
                headers: { 'Accept': 'text/html' },
                cache: 'no-store'
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.text();
            }).then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var nextFeed = doc.querySelector('[data-feed-infinite]');
                var cards = nextFeed ? Array.prototype.slice.call(nextFeed.querySelectorAll('.post-card')) : [];
                if (!cards.length) {
                    finish();
                    return;
                }
                var frag = document.createDocumentFragment();
                cards.forEach(function (card) {
                    frag.appendChild(card);
                });
                feed.insertBefore(frag, sentinel);
                cards.forEach(function (card) {
                    if (window.bindAjaxContent) {
                        window.bindAjaxContent(card);
                    }
                    card.classList.add('is-visible');
                });
                page += 1;
                feed.setAttribute('data-feed-page', String(page));
                loading = false;
                if (page >= totalPages) {
                    finish();
                    return;
                }
                sentinel.classList.remove('is-loading');
                if (status) {
                    status.textContent = '继续下滑加载更多';
                }
            }).catch(function () {
                loading = false;
                sentinel.classList.remove('is-loading');
                if (status) {
                    status.textContent = '加载失败，继续下滑重试';
                }
            });
        };

        if (finished) {
            finish();
            return;
        }
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        loadNext();
                    }
                });
            }, { rootMargin: '400px 0px' });
            observer.observe(sentinel);
        } else {
            window.addEventListener('scroll', function () {
                var rect = sentinel.getBoundingClientRect();
                if (rect.top < window.innerHeight + 400) {
                    loadNext();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFeedInfinite);
    } else {
        initFeedInfinite();
    }
})();
(function () {
    'use strict';

    function initDraftAutosave() {
        var form = document.querySelector('[data-draft-form]');
        if (!form || !window.fetch) {
            return;
        }
        var content = form.querySelector('textarea[name="content"]');
        var tags = form.querySelector('input[name="tags"]');
        var status = document.querySelector('[data-draft-status]');
        var banner = document.querySelector('[data-draft-banner]');
        var token = '';
        var tokenInput = form.querySelector('input[name="csrf_token"]');
        if (tokenInput) {
            token = tokenInput.value;
        }
        var timer = null;
        var saving = false;
        var showStatus = function (message, isError) {
            if (!status) {
                return;
            }
            status.textContent = message;
            status.classList.toggle('is-error', !!isError);
            status.hidden = false;
            clearTimeout(showStatus._timer);
            showStatus._timer = setTimeout(function () {
                status.hidden = true;
            }, 2400);
        };
        var save = function () {
            if (saving) {
                return;
            }
            saving = true;
            var data = new FormData();
            data.set('action', 'saveDraft');
            data.set('content', content ? content.value : '');
            data.set('tags', tags ? tags.value : '');
            data.set('csrf_token', token);
            fetch('api.php', {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (json) {
                saving = false;
                if (json && json.ok) {
                    showStatus('草稿已自动保存');
                } else {
                    showStatus('草稿保存失败，请稍后再试', true);
                }
            }).catch(function () {
                saving = false;
                showStatus('草稿保存失败，请检查网络', true);
            });
        };
        var schedule = function () {
            clearTimeout(timer);
            timer = setTimeout(save, 800);
        };
        if (content) {
            content.addEventListener('input', schedule);
        }
        if (tags) {
            tags.addEventListener('input', schedule);
        }
        var clearBtn = document.querySelector('[data-clear-draft]');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (content) {
                    content.value = '';
                }
                if (tags) {
                    tags.value = '';
                }
                if (banner) {
                    banner.remove();
                }
                save();
                showStatus('草稿已清空');
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDraftAutosave);
    } else {
        initDraftAutosave();
    }
})();
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var replyBtn = event.target && event.target.closest ? event.target.closest('.comment-reply-btn') : null;
        if (replyBtn) {
            var comments = replyBtn.closest('.comments');
            if (!comments) {
                return;
            }
            var input = comments.querySelector('[data-reply-input]');
            var chip = comments.querySelector('[data-reply-chip]');
            var name = comments.querySelector('[data-reply-name]');
            var commentInput = comments.querySelector('input[name="comment"]');
            if (input) {
                input.value = replyBtn.getAttribute('data-reply-pk') || '0';
            }
            if (name) {
                name.textContent = replyBtn.getAttribute('data-reply-name') || '';
            }
            if (chip) {
                chip.hidden = false;
            }
            if (commentInput) {
                commentInput.focus();
            }
            return;
        }
        var cancelBtn = event.target && event.target.closest ? event.target.closest('[data-cancel-reply]') : null;
        if (cancelBtn) {
            var box = cancelBtn.closest('.comments');
            var input = box && box.querySelector('[data-reply-input]');
            var chip = box && box.querySelector('[data-reply-chip]');
            if (input) {
                input.value = '0';
            }
            if (chip) {
                chip.hidden = true;
            }
        }
    });
})();