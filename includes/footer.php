
</main><!-- end .main-content -->

<script>
// ── Sidebar toggle (mobile) ───────────────────────────────────
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}
// Close sidebar when nav item clicked on mobile
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
        if (window.innerWidth <= 768) closeSidebar();
    });
});

// ── Modal helpers ─────────────────────────────────────────────
function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}
// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
// Close modal on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => {
            closeModal(m.id);
        });
    }
});

// ── Character counter for textareas ──────────────────────────
document.querySelectorAll('textarea[data-counter]').forEach(ta => {
    const counterId = ta.getAttribute('data-counter');
    const counter   = document.getElementById(counterId);
    if (!counter) return;
    const update = () => { counter.textContent = ta.value.length + ' chars'; };
    ta.addEventListener('input', update);
    update();
});

// ── Auto-dismiss alerts ───────────────────────────────────────
setTimeout(() => {
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(a => {
        a.style.transition = 'opacity 0.5s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 500);
    });
}, 4000);

// ── Confirm dialogs ───────────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function(e) {
        if (!confirm(this.getAttribute('data-confirm'))) e.preventDefault();
    });
});

// ── Real-time notification polling ───────────────────────────
(function() {
  const API       = document.currentScript ? '' : '';
  const apiUrl    = window.location.origin + window.location.pathname.replace(/\/portals\/.*/, '/portals/notifications_api.php');
  const bell      = document.getElementById('notifBell');
  const badge     = document.getElementById('notifBadge');
  const panel     = document.getElementById('notifPanel');
  const list      = document.getElementById('notifList');
  let   lastPoll  = Math.floor(Date.now() / 1000) - 60;
  let   panelOpen = false;

  if (!bell) return; // not logged in / no bell

  // ── Toast stack ──────────────────────────────────────────────
  const toastStack = document.createElement('div');
  toastStack.className = 'notif-toast-stack';
  document.body.appendChild(toastStack);

  function showToast(n) {
    const colors = {success:'var(--success)',danger:'var(--danger)',warning:'var(--warning)',info:'var(--info)'};
    const col    = colors[n.type] || 'var(--info)';
    const t = document.createElement('div');
    t.className = 'notif-toast';
    t.innerHTML = `
      <div style="width:10px;height:10px;border-radius:50%;background:${col};flex-shrink:0;margin-top:3px;"></div>
      <div style="flex:1;">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:2px;">${escHtml(n.title)}</div>
        <div style="font-size:12px;color:var(--text-muted);">${escHtml(n.message)}</div>
      </div>
      <button class="notif-toast-close" onclick="this.closest('.notif-toast').remove()">✕</button>`;
    toastStack.appendChild(t);
    if (n.link) t.style.cursor = 'pointer';
    t.addEventListener('click', e => { if(e.target.tagName!=='BUTTON' && n.link) window.location = n.link; });
    setTimeout(() => { t.style.transition='opacity 0.4s'; t.style.opacity='0'; setTimeout(()=>t.remove(),400); }, 5000);
  }

  function escHtml(s) {
    const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML;
  }

  // ── Render panel items ────────────────────────────────────────
  function renderPanel(notifications) {
    if (!list) return;
    if (!notifications.length) {
      list.innerHTML = '<div class="notif-empty">🔔 No notifications yet</div>';
      return;
    }
    list.innerHTML = notifications.map(n => {
      const col = {success:'success',danger:'danger',warning:'warning',info:'info'}[n.type] || 'info';
      const href = n.link || '#';
      return `<a class="notif-item${n.is_read?'':' unread'}" href="${escHtml(href)}"
                 onclick="markRead(${n.id})">
        <div class="notif-dot ${col}"></div>
        <div style="flex:1;min-width:0;">
          <div class="notif-item-title">${escHtml(n.title)}</div>
          <div class="notif-item-msg">${escHtml(n.message)}</div>
          <div class="notif-item-time">${escHtml(n.time_ago)}</div>
        </div>
      </a>`;
    }).join('');
  }

  // ── Update badge ──────────────────────────────────────────────
  function updateBadge(count) {
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count > 9 ? '9+' : count;
      badge.style.display = 'flex';
      // Pulse animation on increase
      badge.style.animation = 'none';
      setTimeout(() => badge.style.animation = '', 10);
    } else {
      badge.style.display = 'none';
    }
  }

  // ── Poll API ──────────────────────────────────────────────────
  async function poll(showToasts) {
    try {
      const res  = await fetch(`${apiUrl}?action=poll&since=${lastPoll}&_=${Date.now()}`, {credentials:'same-origin'});
      if (!res.ok) return;
      const data = await res.json();
      if (!data.ok) return;
      lastPoll = data.server_time || Math.floor(Date.now()/1000);
      updateBadge(data.unread);
      renderPanel(data.notifications || []);
      if (showToasts && data.new_items && data.new_items.length) {
        data.new_items.slice(0,3).forEach(n => showToast(n));
      }
    } catch(e) { /* network error — silent fail */ }
  }

  // ── Mark single as read ───────────────────────────────────────
  window.markRead = async function(id) {
    const fd = new FormData();
    fd.append('id', id);
    await fetch(`${apiUrl}?action=mark_read`, {method:'POST',body:fd,credentials:'same-origin'});
  };

  // ── Mark all as read ──────────────────────────────────────────
  window.markAllRead = async function() {
    const fd = new FormData();
    fd.append('id', 0);
    await fetch(`${apiUrl}?action=mark_read`, {method:'POST',body:fd,credentials:'same-origin'});
    updateBadge(0);
    // Re-render as read
    if (list) list.querySelectorAll('.notif-item.unread').forEach(el=>el.classList.remove('unread'));
  };

  // ── Toggle panel ──────────────────────────────────────────────
  window.toggleNotifPanel = function(e) {
    e.stopPropagation();
    panelOpen = !panelOpen;
    if (panel) panel.style.display = panelOpen ? 'block' : 'none';
    if (panelOpen) poll(false);
  };
  document.addEventListener('click', e => {
    const wrapper = document.getElementById('notifWrapper');
    if (wrapper && !wrapper.contains(e.target)) {
      panelOpen = false;
      if (panel) panel.style.display = 'none';
    }
  });

  // ── Initial + periodic poll ───────────────────────────────────
  poll(false);                          // immediate on page load
  setInterval(() => poll(true), 30000); // every 30 seconds
})();
</script>

</body>
</html>
