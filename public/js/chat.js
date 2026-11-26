(() => {
  const csrf = document.querySelector('meta[name="csrf"]')?.content ?? '';
  const endpoint = document.querySelector('meta[name="sunset-base"]')?.content ?? '';
  const win = document.getElementById('chat-window');
  const form = document.getElementById('chat-form');
  if (!win) return;

  const forumId = win.dataset.forumId;
  let lastId = parseInt([...win.querySelectorAll('[id^="msg-"]')].pop()?.id.replace('msg-','') ?? '0');

  const scrollBottom = () => { win.scrollTop = win.scrollHeight; };
  scrollBottom();

  async function poll() {
    try {
      const r = await fetch(`${endpoint}?page=chat_messages&forum_id=${forumId}&after=${lastId}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      if (!r.ok) return;
      const d = await r.json();
      if (d.html) {
        const atBottom = win.scrollHeight - win.scrollTop - win.clientHeight < 60;
        win.insertAdjacentHTML('beforeend', d.html);
        if (atBottom) scrollBottom();
        lastId = d.last;
      }
    } catch {}
  }
  setInterval(poll, 3000);

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const ta = form.querySelector('textarea');
    const body = ta.value.trim();
    if (!body) return;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    try {
      const fd = new FormData();
      fd.append('_csrf', csrf);
      fd.append('forum_id', forumId);
      fd.append('body', body);
      const r = await fetch(`${endpoint}?page=chat_send`, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json' }, body: fd });
      const d = await r.json();
      if (d.error) { alert(d.error); return; }
      win.insertAdjacentHTML('beforeend', d.html);
      lastId = Math.max(lastId, d.id);
      ta.value = '';
      scrollBottom();
    } catch { alert('Error al enviar el mensaje.'); }
    finally { btn.disabled = false; }
  });

  win.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-del-msg]');
    if (!btn) return;
    if (!confirm('¿Eliminar este mensaje?')) return;
    const msgId = btn.dataset.delMsg;
    const fd = new FormData();
    fd.append('_csrf', csrf); fd.append('msg_id', msgId); fd.append('forum_id', forumId);
    await fetch(`${endpoint}?page=chat_delete`, { method: 'POST', credentials: 'same-origin', body: fd });
    document.getElementById('msg-' + msgId)?.remove();
  });
})();
