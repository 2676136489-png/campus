/**
 * 登录页文字地球（替换旧 CSS 雷达）。
 * 移植自 "Text Path Studies" FIG 06 Globe — ThreeUI Community
 * (threeui.com · Meng To / Design+Code), MIT License.
 * https://threeui.com/component/globe-study
 *
 * 相对原版的改动（仅工程适配，无设计改动）：
 * 1. 墨色换成吉大校徽蓝 PANTONE 7455C（#385DAE，官方后勤处通知确认）；
 *    浅色主题用加深一档的 #2C4C96，深色主题用提亮档 #7E9EE2 保可读。
 * 2. prefers-reduced-motion 时球面静止。
 * 3. 标签页不可见时暂停渲染。
 * 4. 通过 MutationObserver 跟随 campus.js 的 data-theme 切换重建墨色池。
 */
(function () {
  "use strict";

  var cv = document.querySelector('.auth-globe canvas');
  if (!cv || !cv.getContext) return;
  var REDUCED = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var FACE = '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif';

  /* 吉大校徽蓝：PANTONE 7455C → #385DAE (rgb 56,93,174)。
     浅色在淡蓝纸底上加深一档（#2C4C96），深色提亮一档（#7E9EE2）。 */
  var G_INK = { light: '44,76,150', dark: '126,158,226' };
  var theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  var GI64 = [];
  function buildInk() {
    GI64.length = 0;
    var rgb = G_INK[theme];
    for (var i = 0; i < 64; i++) GI64.push('rgba(' + rgb + ',' + (i / 63 * 0.88).toFixed(4) + ')');
  }
  buildInk();
  function ink(a) { return GI64[a <= 0 ? 0 : a >= 1 ? 63 : (a * 63) | 0]; }

  /* ---------- 主题跟随（campus.js 切主题只改 data-theme 属性） ---------- */
  if (window.MutationObserver) {
    new MutationObserver(function () {
      var t = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      if (t !== theme) { theme = t; buildInk(); }
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
  }

  /* ---------- canvas 基建（dpr 封顶 1.5，与站点性能定案一致） ---------- */
  function Surface(el) {
    this.el = el; this.ctx = el.getContext('2d');
    this.w = 0; this.h = 0; this.dpr = 1;
    this.resize();
  }
  Surface.prototype.resize = function () {
    var r = this.el.getBoundingClientRect();
    if (!r.width || !r.height) return false;
    var dpr = Math.min(1.5, window.devicePixelRatio || 1);
    var w = Math.round(r.width * dpr), h = Math.round(r.height * dpr);
    if (w === this.el.width && h === this.el.height && this.w === r.width) return false;
    this.el.width = w; this.el.height = h;
    this.w = r.width; this.h = r.height; this.dpr = dpr;
    this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    return true;
  };
  Surface.prototype.base = function () { this.ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0); };
  Object.defineProperty(Surface.prototype, 'u', {
    get: function () { return this.w < this.h ? this.w : this.h; }
  });

  function local(canvas, e) {
    var r = canvas.getBoundingClientRect();
    return { x: e.clientX - r.left, y: e.clientY - r.top };
  }

  /* Chrome 按 (font, transform) 缓存字形：角度量化到 64 步全是命中 */
  var QA = Math.PI * 2 / 64;
  function qang(a) { return Math.round(a / QA) * QA; }

  /* ---------- 大陆位图（288x144 1bit，原版数据） ---------- */
  var LAND_B64 = "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPcBAOD/HwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACA//+P//f/LwgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD4/v/4/////wcAAAAEAPABAAAAfAAAAAAAAAAAAAAAAAAAAADg9w/4/////wEAAP4AAAAAAAAA+AAAAAAAAAAAAAAAAAAAAIAG+Of//////wAAAHwGAAAAAAAAAAMAAAAAAAAAAAAAAACABwAc/4P//////wAAADAAAAAAQAAAAD4AAAAAAAAAAAAAAAAAfMbDcQAA/v///wAAAAAAAADABwAA//8HAMAPAAAAAAAAAABgAAAAAAAA/P///wAAAAAAAABwAADg//8AAAAAAAAAAAAAAADwG457dwcA8P//HwAAAAAAAAAYAAD///9/eAAAAAAAAAAAAAD4/g0H/w8A8P//PwAAAAAAAAAOgOv/////fwD/AAAAAAA/AAAA/B84/v8A8P//LwAAAAD4AAAA4PP//////////wAAAOD///H/+D/3cPgDoP//DwAAAID/BwAAx/v/////////P4AA/P///////////////////////w8AAOcBAAgAAAAAAAAAAAAAgP///////////////////////wcAACYAAAQAAAAAAAAAAAAAgf///////////////////////wMM8AAAAAAAAAAAAAAAAAAAIID/////////e+wPwH8AgA8AAP/5z/////////////////8/ANH///////9/AIALgD8AAAAAwH/+//////////////////9/APj///////8fADwAAD8AAAAA8D/+//////////////////sPAPC/+f////8fAPwAADgAAAAA8D/+////////////////D/wBAMCfAP////8PAPwYAAAAAAAA8H/4//////////////9/DgcAAAAcAOD///8/APw/AAAAAAAQAB7w//////////////8BgAMAAAACAMD/////Afh/AAAAAAA4gBz+/////////////38A4AMAAMAAAMD/////B/z/AAAAAABwwAb+/////////////x8A8AEAAAAAAAD/////P///AwAAAADmgOH//////////////z8A4AAAAAAAAAD+////P/7/BwAAAAD28P////////////////8D4AAAAAAAAAD8////f/7/BwAAAADz+f////////////////8HIAAAAAAAAAD6////////BAAAAABw/v////////////////8EAAAAAAAAAADo//////8jHgAAAACA//////////////////8MAAAAAAAAAADQ//////8OPgAAAADw//////////////////8AAAAAAAAAAADg//////+PIAAAAADA////v////////////38EAAAAAAAAAADg////////AAAAAACA//v/zD/8/////////z8AAAAAAAAAAADg//////8bAAAAAACA//N/gD///////////x8GAAAAAAAAAADw//////8AAAAAAAD+B8c/AD/+/////////wcPAAAAAAAAAADg//////8AAAAAAAD+gx4/DH74/////////wABAAAAAAAAAADg/////x8AAAAAAAD+gbCn///8////////fQABAAAAAAAAAADg/////w8AAAAAAAD/gCDn///4//////9/MgADAAAAAAAAAADA/////w8AAAAAAAD+AADm/3/4//////8/cIABAAAAAAAAAADA/////wcAAAAAAAA44AHC///5////////4+ABAAAAAAAAAACA/////wcAAAAAAACI/wEA4P//////////4OgAAAAAAAAAAAAA/////wMAAAAAAAD4/wAA4P//////////ADYAAAAAAAAAAAAA/P///wEAAAAAAAD+/wEA8P//////////AQcAAAAAAAAAAAAA+P//fwAAAAAAAAD//w8P8P//////////AQEAAAAAAAAAAAAAyP//fwAAAAAAAAD//3//////////////AQAAAAAAAAAAAAAA0P+PYQAAAAAAAAD//////z//////////AwAAAAAAAAAAAAAAoP8HwAAAAAAAAMD/////83/+////////AQAAAAAAAAAAAAAAIP8DwAAAAAAAAOD/////5//I////////AAAAAAAAAAAAAAAAQP4DgAAAAAAAAPD/////z/+A////////AAAAAAAAAAAAAAAAAPwDAAIAAAAAAPD/////z/8ZwP////9/AQAAAAAAAAAAAAAAAPgDQAAAAAAAAPj/////j/9/gP////8fAQAAAAAAAAAAAAAAAPADEAMAAAAAAPz/////v///AP9//P8DAAAAAAAAAAAAAAAAAPADAwwAAAAAAPj/////P/9/APw//B8AAAAAAAAAAAAIAAAAAPCHA8AAAAAAAPj/////P/4/APwP+J8BAAAAAAAAAAAAAAAAAMD/A0YEAAAAAPj/////f/4fAPwH+B8AAwAAAAAAAAAAAAAAAAD/AQAAAAAAAPj/////f/wHAPgD8D8AAwAAAAAAAAAAAAAAAADgHwAAAAAAAPj///////wDAPgAwH8AAQAAAAAAAAAAAAAAAADAHwAAAAAAAPz//////30AAPAAwH8AAAAAAAAAAAAAAAAAAAAAHAAAAAAAAPj//////wsAAPAAgH4AAQAAAAAAAAAAAAAAAAAAGEAAAAAAAPj//////wMBAPAAgHwAAAAAAAAAAAAAAAAAAAAAGPAhAAAAAPD///////cBAOAAgDiABAAAAAAAAAAAAAAAAAAAIPl/AAAAAOD///////8AAGABABBAFAAAAAAAAAAAAAAAAAAAgP7/AQAAAMD///////8AAAABgAAAHAAAAAAAAAAAAAAAAAAAAPz/AQAAAID///////8AAAABAAEgCAAAAAAAAAAAAAAAAAAAAPz/HwAAAAD/8P///38AAAAAAANgAAAAAAAAAAAAAAAAAAAAAPz/fwAAAAAAoP///z8AAAAAYAd4AAAAAAAAAAAAAAAAAAAAAPz/fwAAAAAAAP///x8AAAAAwAY8AAAAAAAAAAAAAAAAAAAAAP7//wAAAAAAAP///w8AAAAAgAc+AAAAAAAAAAAAAAAAAAAAAP///wAAAAAAAP///wcAAAAAgIM/TwAAAAAAAAAAAAAAAAAAAP///wEAAAAAgP///wMAAAAAAIc/QAQAAAAAAAAAAAAAAAAAgP///w8AAAAAgP///wEAAAAAAA6fAUQAAAAAAAAAAAAAAAAAAP////8AAAAAAP///wAAAAAAAB6ewuwDAgAAAAAAAAAAAAAAgP////8DAAAAAP7//wAAAAAAABwAAvAPAgAAAAAAAAAAAAAAgP////8PAAAAAPz/fwAAAAAAABAAAMCfAQAAAAAAAAAAAAAAAP////8PAAAAAPz//wAAAAAAAOADAIA/MAAAAAAAAAAAAAAAAP7///8PAAAAAPz/fwAAAAAAAAAPAMBngAAAAAAAAAAAAAAAAP7///8PAAAAAPz//wAAAAAAAAAACABAAAAAAAAAAAAAAAAAAPz///8HAAAAAPj//wAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAPz///8DAAAAAPj//wAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAPj///8BAAAAAPz//4AAAAAAAAAAAB8GAAAAAAAAAAAAAAAAAPj///8BAAAAAPz//8EAAAAAAAAAIB8OAAAAAAAAAAAAAAAAAPD///8BAAAAAP7//+AAAAAAAAAA+B8OACAAAAAAAAAAAAAAAMD///8BAAAAAP7/f/gAAAAAAAAA/H8eAAAAAAAAAAAAAAAAAID///8AAAAAAP7/H/gAAAAAAAAA/P8fAABAAAAAAAAAAAAAAAD///8AAAAAAPz/D3AAAAAAAAAA/v8/AAAAAAAAAAAAAAAAAAD///8AAAAAAPj/D3gAAAAAAADA//9/AAgAAAAAAAAAAAAAAAD//38AAAAAAPj/DzgAAAAAAADw////AAAAAAAAAAAAAAAAAAD//x8AAAAAAPD/DzgAAAAAAAD4////AQAAAAAAAAAAAAAAAAD//wMAAAAAAPD/DzgAAAAAAAD4////AwAAAAAAAAAAAAAAAID//wEAAAAAAPD/AwAAAAAAAAD4////AwAAAAAAAAAAAAAAAID//wEAAAAAAPD/AwAAAAAAAAD4////BwAAAAAAAAAAAAAAAID//wEAAAAAAOD/AwAAAAAAAAD4////BwAAAAAAAAAAAAAAAID//wAAAAAAAOD/AQAAAAAAAADw////BwAAAAAAAAAAAAAAAID//wAAAAAAAMD/AAAAAAAAAADw////AwAAAAAAAAAAAAAAAID/fwAAAAAAAIB/AAAAAAAAAADgf/z/AwAAAAAAAAAAAAAAAID/PwAAAAAAAIA/AAAAAAAAAADgB/D/AQAAAAAAAAAAAAAAAMD/HQAAAAAAAIABAAAAAAAAAADwAND/AQAAAAAAAAAAAAAAAMD/AwAAAAAAAAAAAAAAAAAAAAAAAID/AAAIAAAAAAAAAAAAAMD/BwAAAAAAAAAAAAAAAAAAAAAAAAD/AAAQAAAAAAAAAAAAAOD/AwAAAAAAAAAAAAAAAAAAAAAAAAA+AABwAAAAAAAAAAAAAOA/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA4AAAAAAAAAAAAAOA/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAAOAPAAAAAAAAAAAAAAAAAAAAAAAAAABwAAAGAAAAAAAAAAAAAMAPAAAAAAAAAAAAAAAAAAAAAAAAAAAgAAADAAAAAAAAAAAAAPAPAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMABAAAAAAAAAAAAAPADAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOABAAAAAAAAAAAAAPAHAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPAHAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPADAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAAAAAAAAAPABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPCBAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAGABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAHAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAwAAAAAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAcAAAAAAAAAAAAAAA+AABAAJ8//j8PAAAAAAAAAAAAAAAAAAAMAAAAAAAAAAAAAPD/fwD///////8/AAAAAAAAAAAAAAAAAIA+AAAAAAAAAAAAPP///8D/////////HwAAAAAAAAAAAAAAAIA9AAAAAAAA8Pz/////P/j//////////wMAAAAAAAAAAMAAAPB9AAAAAID/////////P/7///////////8BAAAAAAAAAOABAwB/AAAAAPD///////////////////////8AAAAAAFACPoD///9/AAAAAPD//////////////////////x8AAAAA+P////////8HAAAAAP///////////////////////wcAAAAA/v///////wMAAAAA/v///////////////////////wcAAAD8/////////w8AAA7w/////////////////////////w8AAMAB/////////wMAgB84/////////////////////////wEAAAAA/P///////3/w4AcA/////////////////////////wAAAADg//////////8/gM///////////////////////////wMAAADg/////////////f///////////////////////////z8A7wMA/v////////////////////////////////////////8/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==";
  var MW = 288, MH = 144, land;
  (function () {
    var bin = atob(LAND_B64);
    land = new Uint8Array(bin.length);
    for (var i = 0; i < bin.length; i++) land[i] = bin.charCodeAt(i);
  })();
  function isLand(lon, lat) {
    var gx = Math.floor((lon + 180) / 360 * MW), gy = Math.floor((90 - lat) / 180 * MH);
    if (gx < 0 || gx >= MW || gy < 0 || gy >= MH) return false;
    var b = gy * MW + gx;
    return (land[b >> 3] >> (b & 7)) & 1;
  }

  var PHRASE = "everypointonthisballisapathbacktoanotherone";
  var nodes = [];
  (function () {
    var LAT_STEP = 3.05, k = 0, run = 0, sea3 = 0;
    for (var lat = -86; lat <= 86; lat += LAT_STEP) {
      var rl = Math.cos(lat * Math.PI / 180);
      var n  = Math.max(1, Math.round(98 * rl));
      for (var i = 0; i < n; i++) {
        var lon = -180 + 360 * i / n;
        var l = isLand(lon, lat);
        if (!l && (sea3++ % 2)) continue;
        var letter = 0;
        if (l && (run++ % 2 === 0)) letter = PHRASE.charAt(k++ % PHRASE.length);
        nodes.push({
          lat: lat * Math.PI / 180, lon: lon * Math.PI / 180,
          land: l, c: letter
        });
      }
    }
  })();

  var s = new Surface(cv);
  var spin = 2.1, vel = 0.16, hover = false, drag = null, tilt = -0.36, vtilt = 0;
  var land8 = [];
  var zoom = 1, zoomT = 1, pins = [], look = null;
  var sea = [], soil = [];

  cv.addEventListener('pointerenter', function () { hover = true; });
  cv.addEventListener('pointerleave', function () { hover = false; });
  cv.addEventListener('pointerdown', function (e) {
    drag = local(cv, e); drag.moved = false;
    try { cv.setPointerCapture(e.pointerId); } catch (_) {}
  });
  cv.addEventListener('pointermove', function (e) {
    var p = local(cv, e);
    look = p;
    if (!drag) return;
    vel  = (p.x - drag.x) / s.u * 9;
    vtilt = -(p.y - drag.y) / s.u * 6;
    tilt  = Math.max(-1.15, Math.min(1.15, tilt + vtilt * 0.016));
    drag = p; drag.moved = true;
  });
  function release(e) {
    if (drag && !drag.moved) {
      var g = unproject(drag.x, drag.y);
      if (g) { pins.push({ lat: g.lat, lon: g.lon, t: performance.now() }); if (pins.length > 7) pins.shift(); }
    }
    drag = null;
  }
  cv.addEventListener('pointerup', release);
  cv.addEventListener('pointercancel', function () { drag = null; });
  cv.addEventListener('wheel', function (e) {
    e.preventDefault();
    zoomT = Math.max(0.85, Math.min(2.6, zoomT * Math.exp(-e.deltaY * 0.0016)));
  }, { passive: false });

  var view = { cx: 0, cy: 0, R: 1, cs: 1, sn: 0, ct: 1, st: 0 };
  function unproject(px, py) {
    var x1 = (px - view.cx) / view.R, y2 = (view.cy - py) / view.R;
    var q = 1 - x1 * x1 - y2 * y2;
    if (q <= 0.002) return null;
    var z2 = Math.sqrt(q);
    var y0 =  y2 * view.ct + z2 * view.st;
    var z1 = -y2 * view.st + z2 * view.ct;
    var x0 =  x1 * view.cs + z1 * view.sn;
    var z0 = -x1 * view.sn + z1 * view.cs;
    return { lat: Math.asin(Math.max(-1, Math.min(1, y0))), lon: Math.atan2(z0, x0) };
  }

  function draw(now, dt) {
    var ctx = s.ctx;
    ctx.clearRect(0, 0, s.w, s.h);

    zoom += (zoomT - zoom) * Math.min(1, dt / 180);
    if (!drag) {
      var idle = hover ? 0.045 : 0.16;
      if (REDUCED) idle = 0;
      vel += (idle - vel) * Math.min(1, dt / 900);
      vtilt *= Math.pow(0.90, dt / 16);
      tilt += vtilt * dt / 1000;
      tilt += (-0.36 - tilt) * Math.min(1, dt / 4000);
    }
    spin += vel * dt / 1000;

    var cx = s.w / 2, cy = s.h / 2 + s.u * 0.035;
    var R  = s.u * 0.318 * zoom;
    var fs = s.u * 0.0275 * Math.pow(zoom, 0.72);
    var cs = Math.cos(spin), sn = Math.sin(spin);
    var ct = Math.cos(tilt), st = Math.sin(tilt);
    view.cx = cx; view.cy = cy; view.R = R; view.cs = cs; view.sn = sn; view.ct = ct; view.st = st;

    var lx = -1e9, ly = -1e9, lr = s.u * 0.20, lr2 = lr * lr;
    if (look && !drag) { lx = look.x; ly = look.y; }

    ctx.textAlign = 'center'; ctx.textBaseline = 'middle';

    sea.length = 0; soil.length = 0;
    for (var i = 0; i < nodes.length; i++) {
      var nd = nodes[i];
      var cl = Math.cos(nd.lat);
      var x0 = cl * Math.cos(nd.lon), y0 = Math.sin(nd.lat), z0 = cl * Math.sin(nd.lon);
      var x1 =  x0 * cs - z0 * sn,  z1 = x0 * sn + z0 * cs;
      var y2 =  y0 * ct - z1 * st,  z2 = y0 * st + z1 * ct;
      if (z2 <= 0.02) continue;

      var px = cx + x1 * R, py = cy - y2 * R;
      var dx = px - lx, dy = py - ly;
      var glow = (dx * dx + dy * dy < lr2) ? (1 - Math.sqrt(dx * dx + dy * dy) / lr) : 0;
      if (!nd.land) { sea.push(px, py, Math.min(0.999, z2 + glow * 0.55)); continue; }
      if (!nd.c) { soil.push(px, py, Math.min(0.999, z2 + glow * 0.55)); continue; }

      var tx0 = -Math.sin(nd.lon), tz0 = Math.cos(nd.lon);
      var tx1 = tx0 * cs - tz0 * sn, tz1 = tx0 * sn + tz0 * cs;
      var ang = qang(Math.atan2(tz1 * st, tx1));
      var b = Math.min(7, Math.max(0, ((Math.min(0.999, z2 + glow * 0.6)) * 7.99) | 0));
      (land8[b] || (land8[b] = [])).push(px, py, ang, nd.c, 0);
    }

    var dmin = Math.max(0.7, s.u * 0.0029);
    function dots(list, base, gain, grow) {
      for (var lvl = 0; lvl < 6; lvl++) {
        var z = (lvl + 0.5) / 6, dsz = dmin * grow * (0.55 + 0.75 * z);
        ctx.fillStyle = ink(base + gain * z);
        ctx.beginPath();
        for (var q = 0; q < list.length; q += 3) {
          var lv = list[q + 2] >= 1 ? 5 : (list[q + 2] * 6) | 0;
          if (lv !== lvl) continue;
          ctx.rect(list[q] - dsz / 2, list[q + 1] - dsz / 2, dsz, dsz);
        }
        ctx.fill();
      }
    }
    dots(sea,  0.10, 0.22, 1.0);
    dots(soil, 0.34, 0.46, 1.7);

    for (var bi = 0; bi < 8; bi++) {
      var arr = land8[bi];
      if (!arr || !arr.length) continue;
      var zb = (bi + 0.5) / 8;
      ctx.font = 'bold ' + (fs * (0.42 + 0.58 * zb)).toFixed(2) + 'px ' + FACE;
      ctx.fillStyle = ink(0.28 + 0.72 * Math.pow(zb, 0.6));
      for (var t = 0; t < arr.length; t += 5) {
        ctx.save();
        ctx.translate(arr[t], arr[t + 1]);
        ctx.rotate(arr[t + 2]);
        ctx.fillText(arr[t + 3], 0, 0);
        ctx.restore();
      }
      arr.length = 0;
    }

    for (var pi = 0; pi < pins.length; pi++) {
      var pn = pins[pi];
      var pcl = Math.cos(pn.lat);
      var ax = pcl * Math.cos(pn.lon), ay = Math.sin(pn.lat), az = pcl * Math.sin(pn.lon);
      var bx1 = ax * cs - az * sn, bz1 = ax * sn + az * cs;
      var by2 = ay * ct - bz1 * st, bz2 = ay * st + bz1 * ct;
      if (bz2 <= 0.02) continue;
      var ppx = cx + bx1 * R, ppy = cy - by2 * R;
      var age = (now - pn.t) / 1000;
      var pop = Math.min(1, age / 0.22);
      var rr2 = s.u * 0.016 * (0.4 + 0.6 * pop) * (0.55 + 0.45 * bz2);
      ctx.beginPath();
      ctx.arc(ppx, ppy, rr2, 0, Math.PI * 2);
      ctx.strokeStyle = ink(0.30 + 0.55 * bz2);
      ctx.lineWidth = Math.max(0.7, s.u * 0.0022);
      ctx.stroke();
      ctx.beginPath();
      ctx.arc(ppx, ppy, Math.max(0.7, rr2 * 0.22), 0, Math.PI * 2);
      ctx.fillStyle = ink(0.45 + 0.55 * bz2);
      ctx.fill();
      if (age < 0.9) {
        var w2 = 1 - age / 0.9;
        ctx.beginPath();
        ctx.arc(ppx, ppy, rr2 + (1 - w2) * s.u * 0.05, 0, Math.PI * 2);
        ctx.strokeStyle = ink(0.55 * w2 * w2);
        ctx.lineWidth = Math.max(0.6, s.u * 0.0016);
        ctx.stroke();
      }
    }
  }

  /* ---------- 主循环：隐藏标签页暂停，resize 重建 ---------- */
  var prev = performance.now();
  var running = true;
  function frame(now) {
    if (!running) return;
    var dt = Math.min(64, now - prev); prev = now;
    if (!s.w) s.resize();
    if (s.w) { s.base(); draw(now, dt); }
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      running = false;
    } else if (!running) {
      running = true;
      prev = performance.now();
      requestAnimationFrame(frame);
    }
  });

  var resizeTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () { s.resize(); }, 120);
  });
})();
