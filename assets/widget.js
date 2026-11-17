/* OGNIVID AI Assistant widget */
(function () {
  'use strict';
  var CFG = window.OAA_CFG || {};
  var root = document.getElementById('oaa-root');
  if (!root || !CFG.rest) return;

  var LANG = (document.documentElement.lang || '').toLowerCase().indexOf('id') === 0 ? 'id' : 'en';
  var T = {
    en: {
      launch: 'Talk to our AI', title: 'OGNIVID Assistant', sub: 'Describe your project, we prepare the brief',
      hello: 'Hi! 👋 I\'m the OGNIVID assistant. Tell me what you need help with (for example file transfer, system integration, AI agents or a Power BI dashboard) and I\'ll turn it into a project brief for our team.',
      privacy: 'This chat is processed by AI and sent to the OGNIVID team. Please don\'t share passwords or confidential data.',
      ph: 'Type your message…', send: 'Send', thinking: 'Typing…', toForm: 'Prefer a short form?', toChat: 'Back to chat',
      newChat: 'New chat', err: 'Connection problem. Please try again.', test: 'Test mode: only admins see this',
      f: { name: 'Name *', email: 'Email *', company: 'Company', phone: 'Phone / WhatsApp', title: 'Project title', problem: 'What do you need help with? *', systems: 'Current systems / tools', service: 'Service', urgency: 'Urgency', timeline: 'Desired timeline', budget: 'Budget range', submit: 'Send brief', sending: 'Sending…',
        services: ['Managed File Transfer', 'Integration & AI Agents', 'Data & Dashboards', 'Other'], urg: { low: 'Low', medium: 'Medium', high: 'High', critical: 'Critical' } },
      wa: 'Chat on WhatsApp'
    },
    id: {
      launch: 'Konsultasi dengan AI', title: 'Asisten OGNIVID', sub: 'Ceritakan proyek Anda, kami siapkan brief-nya',
      hello: 'Halo! 👋 Saya asisten OGNIVID. Ceritakan apa yang perlu dibantu (misalnya file transfer, integrasi sistem, AI agent, atau dashboard Power BI), dan saya akan menyusunnya menjadi project brief untuk tim kami.',
      privacy: 'Chat ini diproses oleh AI dan dikirim ke tim OGNIVID. Mohon jangan membagikan password atau data rahasia.',
      ph: 'Ketik pesan Anda…', send: 'Kirim', thinking: 'Mengetik…', toForm: 'Lebih suka isi formulir?', toChat: 'Kembali ke chat',
      newChat: 'Chat baru', err: 'Koneksi bermasalah. Silakan coba lagi.', test: 'Mode uji: hanya admin yang melihat ini',
      f: { name: 'Nama *', email: 'Email *', company: 'Perusahaan', phone: 'Telepon / WhatsApp', title: 'Judul proyek', problem: 'Apa yang perlu kami bantu? *', systems: 'Sistem / tools yang dipakai', service: 'Layanan', urgency: 'Urgensi', timeline: 'Target waktu', budget: 'Kisaran anggaran', submit: 'Kirim brief', sending: 'Mengirim…',
        services: ['Managed File Transfer', 'Integration & AI Agents', 'Data & Dashboards', 'Lainnya'], urg: { low: 'Rendah', medium: 'Sedang', high: 'Tinggi', critical: 'Kritis' } },
      wa: 'Chat via WhatsApp'
    }
  }[LANG];

  var store = {
    get: function (k) { try { return window.sessionStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { window.sessionStorage.setItem(k, v); } catch (e) {} },
    del: function (k) { try { window.sessionStorage.removeItem(k); } catch (e) {} }
  };

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  // Minimal, safe formatting: escape first, then **bold**, bullets and line breaks.
  function fmt(s) {
    var lines = esc(s).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').split(/\n/);
    var out = '', inList = false;
    lines.forEach(function (l) {
      var m = l.match(/^\s*(?:[-•*]|\d+\.)\s+(.*)$/);
      if (m) { if (!inList) { out += '<ul>'; inList = true; } out += '<li>' + m[1] + '</li>'; }
      else { if (inList) { out += '</ul>'; inList = false; } out += l.trim() ? '<p>' + l + '</p>' : ''; }
    });
    return out + (inList ? '</ul>' : '');
  }

  root.className = 'oaa oaa-' + (CFG.position === 'right' ? 'right' : 'left');
  var btn = el('button', 'oaa-launch');
  btn.type = 'button';
  btn.setAttribute('aria-label', T.launch);
  btn.innerHTML = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M12 3C6.5 3 2 6.6 2 11c0 2.4 1.3 4.5 3.4 6L4.5 21l4.3-2.2c1 .3 2.1.4 3.2.4 5.5 0 10-3.6 10-8s-4.5-8.2-10-8.2z"/></svg><span></span>';
  btn.querySelector('span').textContent = T.launch;

  var panel = el('div', 'oaa-panel');
  panel.setAttribute('role', 'dialog');
  panel.setAttribute('aria-label', T.title);
  panel.hidden = true;
  panel.innerHTML =
    '<div class="oaa-head"><div><div class="oaa-title"></div><div class="oaa-sub"></div></div>' +
    '<div class="oaa-actions"><button type="button" class="oaa-new" title=""></button><button type="button" class="oaa-close" aria-label="Close">×</button></div></div>' +
    '<div class="oaa-note"></div><div class="oaa-body" aria-live="polite"></div>' +
    '<form class="oaa-input"><textarea rows="1"></textarea><button type="submit"></button></form>' +
    '<div class="oaa-foot"><button type="button" class="oaa-switch"></button></div>';
  panel.querySelector('.oaa-title').textContent = T.title;
  panel.querySelector('.oaa-sub').textContent = T.sub;
  panel.querySelector('.oaa-new').textContent = T.newChat;
  panel.querySelector('.oaa-note').textContent = T.privacy + (CFG.test ? ' · ' + T.test : '');
  var body = panel.querySelector('.oaa-body');
  var form = panel.querySelector('.oaa-input');
  var ta = form.querySelector('textarea');
  var sendBtn = form.querySelector('button');
  var sw = panel.querySelector('.oaa-switch');
  ta.placeholder = T.ph;
  ta.maxLength = CFG.maxChars || 1500;
  sendBtn.textContent = T.send;
  sw.textContent = T.toForm;
  root.appendChild(panel);
  root.appendChild(btn);

  var sid = store.get('oaa_sid') || '';
  var history = [];
  try { history = JSON.parse(store.get('oaa_hist') || '[]'); } catch (e) { history = []; }
  var busy = false, mode = 'chat', done = store.get('oaa_done') === '1';

  function save() { store.set('oaa_hist', JSON.stringify(history.slice(-60))); }
  function bubble(role, text, push) {
    var b = el('div', 'oaa-msg oaa-' + role);
    if (role === 'bot') b.innerHTML = fmt(text); else b.textContent = text;
    body.appendChild(b);
    body.scrollTop = body.scrollHeight;
    if (push) { history.push([role, text]); save(); }
    return b;
  }
  function render() {
    body.innerHTML = '';
    bubble('bot', T.hello, false);
    history.forEach(function (h) { bubble(h[0], h[1], false); });
    if (done) addWaLink();
  }
  function addWaLink() {
    if (!CFG.wa) return;
    var a = el('a', 'oaa-wa', T.wa);
    a.href = 'https://wa.me/' + CFG.wa;
    a.target = '_blank';
    a.rel = 'noopener';
    body.appendChild(a);
  }
  function setBusy(v) {
    busy = v; sendBtn.disabled = v; ta.disabled = v || done;
    var t = body.querySelector('.oaa-typing');
    if (v && !t) { t = el('div', 'oaa-msg oaa-bot oaa-typing', T.thinking); body.appendChild(t); body.scrollTop = body.scrollHeight; }
    if (!v && t) t.remove();
  }
  function headers() {
    var h = { 'Content-Type': 'application/json' };
    if (CFG.nonce) h['X-WP-Nonce'] = CFG.nonce;
    return h;
  }

  function send(text) {
    if (busy || done || !text) return;
    bubble('user', text, true);
    ta.value = ''; autosize();
    setBusy(true);
    fetch(CFG.rest + '/chat', { method: 'POST', credentials: 'same-origin', headers: headers(), body: JSON.stringify({ sid: sid, message: text, lang: LANG }) })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        setBusy(false);
        if (d.sid) { sid = d.sid; store.set('oaa_sid', sid); }
        if (d.reply) bubble('bot', d.reply, true);
        if (d.done) { done = true; store.set('oaa_done', '1'); ta.disabled = true; addWaLink(); }
        if (d.mode === 'form') showForm();
        if (!d.reply && !d.done) bubble('bot', T.err, false);
      })
      .catch(function () { setBusy(false); bubble('bot', T.err, false); });
  }

  function autosize() { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 120) + 'px'; }
  ta.addEventListener('input', autosize);
  ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(ta.value.trim()); } });
  form.addEventListener('submit', function (e) { e.preventDefault(); send(ta.value.trim()); });

  /* ---------- form fallback ---------- */
  var formBox = null;
  function showForm() {
    mode = 'form';
    form.hidden = true;
    sw.textContent = T.toChat;
    sw.hidden = !CFG.aiOn;
    if (formBox) { formBox.hidden = false; formBox.scrollIntoView({ block: 'nearest' }); return; }
    var F = T.f;
    formBox = el('form', 'oaa-form');
    var ts = Math.floor(Date.now() / 1000);
    var html = '';
    function inp(name, label, type, req) { html += '<label><span>' + esc(label) + '</span><input name="' + name + '" type="' + (type || 'text') + '"' + (req ? ' required' : '') + '></label>'; }
    inp('name', F.name, 'text', true); inp('email', F.email, 'email', true); inp('company', F.company); inp('phone', F.phone, 'tel'); inp('title', F.title);
    html += '<label><span>' + esc(F.problem) + '</span><textarea name="problem" rows="4" required minlength="10"></textarea></label>';
    inp('systems', F.systems);
    html += '<label><span>' + esc(F.service) + '</span><select name="service">' + F.services.map(function (s) { return '<option>' + esc(s) + '</option>'; }).join('') + '</select></label>';
    html += '<label><span>' + esc(F.urgency) + '</span><select name="urgency">' + ['low', 'medium', 'high', 'critical'].map(function (u) { return '<option value="' + u + '"' + (u === 'medium' ? ' selected' : '') + '>' + esc(F.urg[u]) + '</option>'; }).join('') + '</select></label>';
    inp('timeline', F.timeline); inp('budget', F.budget);
    html += '<input class="oaa-hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"><button type="submit" class="oaa-submit">' + esc(F.submit) + '</button><div class="oaa-form-msg" aria-live="polite"></div>';
    formBox.innerHTML = html;
    body.appendChild(formBox);
    formBox.scrollIntoView({ block: 'nearest' });
    formBox.addEventListener('submit', function (e) {
      e.preventDefault();
      var data = { lang: LANG, ts: ts };
      Array.prototype.forEach.call(formBox.elements, function (x) { if (x.name) data[x.name] = x.value; });
      var b = formBox.querySelector('.oaa-submit'), msg = formBox.querySelector('.oaa-form-msg');
      b.disabled = true; b.textContent = F.sending;
      fetch(CFG.rest + '/form', { method: 'POST', credentials: 'same-origin', headers: headers(), body: JSON.stringify(data) })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.ok) { formBox.remove(); formBox = null; done = true; store.set('oaa_done', '1'); bubble('bot', d.reply, true); addWaLink(); sw.hidden = true; }
          else { b.disabled = false; b.textContent = F.submit; msg.textContent = d.reply || T.err; }
        })
        .catch(function () { b.disabled = false; b.textContent = F.submit; msg.textContent = T.err; });
    });
  }
  function showChat() { mode = 'chat'; if (formBox) formBox.hidden = true; form.hidden = false; sw.textContent = T.toForm; sw.hidden = false; ta.focus(); }
  sw.addEventListener('click', function () { if (mode === 'chat') showForm(); else showChat(); });

  panel.querySelector('.oaa-new').addEventListener('click', function () {
    sid = ''; history = []; done = false; store.del('oaa_sid'); store.del('oaa_hist'); store.del('oaa_done');
    if (formBox) { formBox.remove(); formBox = null; }
    showChat(); render(); ta.disabled = false;
  });
  function open() { panel.hidden = false; btn.setAttribute('aria-expanded', 'true'); root.classList.add('oaa-open'); if (!CFG.aiOn) showForm(); else ta.focus(); }
  function close() { panel.hidden = true; btn.setAttribute('aria-expanded', 'false'); root.classList.remove('oaa-open'); }
  btn.addEventListener('click', function () { if (panel.hidden) open(); else close(); });
  panel.querySelector('.oaa-close').addEventListener('click', close);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) close(); });

  render();
  if (done) ta.disabled = true;
})();
