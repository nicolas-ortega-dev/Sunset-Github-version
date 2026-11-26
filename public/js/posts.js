// Publicaciones: "me gusta", comentarios, paginación del feed y cuadro para publicar.
(() => {
  const csrf = document.querySelector('meta[name="csrf"]')?.content ?? '';
  const endpoint = document.querySelector('meta[name="sunset-base"]')?.content ?? '';

  /** Llama a un endpoint de Sunset y devuelve el JSON (o lanza un Error con el mensaje del servidor). */
  async function request(page, { method = 'GET', params = {}, data = null } = {}) {
    const query = new URLSearchParams({ page, ...params });
    const options = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
    if (data) {
      const body = new FormData();
      body.append('_csrf', csrf);
      Object.entries(data).forEach(([key, value]) => body.append(key, value));
      options.body = body;
    }
    const response = await fetch(`${endpoint}?${query}`, options);
    if (!response.ok) {
      let message = `Error ${response.status}`;
      try { message = (await response.json()).error ?? message; } catch { /* respuesta sin JSON */ }
      throw new Error(message);
    }
    return response.json();
  }

  // ------------------------------------------------------------------ clics
  document.addEventListener('click', async (event) => {
    // Me gusta
    const likeButton = event.target.closest('[data-like]');
    if (likeButton) {
      const card = likeButton.closest('[data-post-id]');
      likeButton.disabled = true;
      try {
        const result = await request('like', { method: 'POST', data: { post_id: card.dataset.postId } });
        likeButton.classList.toggle('is-liked', result.liked);
        likeButton.setAttribute('aria-pressed', String(result.liked));
        likeButton.querySelector('i').className = result.liked ? 'bi bi-heart-fill' : 'bi bi-heart';
        likeButton.querySelector('[data-like-count]').textContent = result.count;
      } catch (error) {
        console.error(error);
      } finally {
        likeButton.disabled = false;
      }
      return;
    }

    // Mostrar/ocultar comentarios
    const commentsToggle = event.target.closest('[data-comments-toggle]');
    if (commentsToggle) {
      const card = commentsToggle.closest('[data-post-id]');
      const panel = card.querySelector('[data-comments]');
      const list = card.querySelector('[data-comment-list]');
      panel.hidden = !panel.hidden;
      commentsToggle.setAttribute('aria-expanded', String(!panel.hidden));
      if (!panel.hidden && !panel.dataset.loaded) {
        list.innerHTML = '<li class="comment-empty">Cargando…</li>';
        try {
          const result = await request('comments', { params: { post_id: card.dataset.postId } });
          list.innerHTML = result.html || '<li class="comment-empty">Aún no hay comentarios.</li>';
          panel.dataset.loaded = '1';
        } catch (error) {
          list.innerHTML = '<li class="comment-empty">No se pudieron cargar los comentarios.</li>';
        }
      }
      if (!panel.hidden) panel.querySelector('input[name="body"]')?.focus();
      return;
    }

    // Ver publicaciones anteriores
    const moreButton = event.target.closest('[data-feed-more]');
    if (moreButton) {
      const label = moreButton.innerHTML;
      moreButton.disabled = true;
      moreButton.textContent = 'Cargando…';
      try {
        const result = await request('feed_more', {
          params: { before: moreButton.dataset.before, feed: moreButton.dataset.feed },
        });
        document.getElementById('feed').insertAdjacentHTML('beforeend', result.html);
        moreButton.dataset.before = result.next;
        if (result.hasMore) {
          moreButton.disabled = false;
          moreButton.innerHTML = label;
        } else {
          moreButton.remove();
        }
      } catch (error) {
        moreButton.disabled = false;
        moreButton.textContent = 'Reintentar';
      }
    }
  });

  // ------------------------------------------------------- enviar comentario
  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-comment-form]');
    if (!form) return;
    event.preventDefault();

    const card = form.closest('[data-post-id]');
    const input = form.querySelector('input[name="body"]');
    const text = input.value.trim();
    if (!text) return;

    const button = form.querySelector('button');
    button.disabled = true;
    try {
      const result = await request('comment', {
        method: 'POST',
        data: { post_id: card.dataset.postId, body: text },
      });
      const list = card.querySelector('[data-comment-list]');
      list.querySelector('.comment-empty')?.remove();
      list.insertAdjacentHTML('beforeend', result.html);
      card.querySelector('[data-comment-count]').textContent = result.count;
      input.value = '';
    } catch (error) {
      alert(error.message);
    } finally {
      button.disabled = false;
    }
  });

  // -------------------------------------------------------------- composer
  function initComposer(form) {
    const bookInput = form.querySelector('input[name="book_id"]');
    const linked = form.querySelector('[data-linked]');
    const linkedTitle = form.querySelector('[data-linked-title]');
    const ratingWrap = form.querySelector('[data-rating-wrap]');
    const picker = form.querySelector('[data-picker]');
    const pickerInput = form.querySelector('[data-picker-input]');
    const results = form.querySelector('[data-picker-results]');

    const clearRating = () => form.querySelectorAll('input[name="rating"]').forEach((r) => { r.checked = false; });

    const setBook = (book) => {
      bookInput.value = book ? book.id : '';
      linkedTitle.textContent = book ? book.title : '';
      linked.hidden = !book;
      ratingWrap.hidden = !book;
      if (!book) clearRating();
    };

    form.querySelector('[data-picker-toggle]')?.addEventListener('click', () => {
      picker.hidden = !picker.hidden;
      if (!picker.hidden) pickerInput.focus();
    });
    form.querySelector('[data-unlink]')?.addEventListener('click', () => setBook(null));
    form.querySelector('[data-rating-clear]')?.addEventListener('click', clearRating);

    // Buscador de obras (con espera de 300 ms para no consultar en cada tecla)
    const renderBooks = (books) => {
      results.replaceChildren();
      if (!books.length) {
        const empty = document.createElement('li');
        empty.className = 'picker-empty';
        empty.textContent = 'Sin resultados en el catálogo.';
        results.append(empty);
        return;
      }
      books.forEach((book) => {
        const item = document.createElement('li');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'picker-item';
        const title = document.createElement('strong');
        title.textContent = book.title;
        const author = document.createElement('small');
        author.textContent = book.author;
        button.append(title, author);
        button.addEventListener('click', () => {
          setBook(book);
          picker.hidden = true;
          pickerInput.value = '';
          results.replaceChildren();
        });
        item.append(button);
        results.append(item);
      });
    };

    let timer;
    pickerInput?.addEventListener('input', () => {
      clearTimeout(timer);
      const q = pickerInput.value.trim();
      if (q.length < 2) {
        results.replaceChildren();
        return;
      }
      timer = setTimeout(async () => {
        try {
          const data = await request('book_lookup', { params: { q } });
          renderBooks(data.books);
        } catch (error) {
          console.error(error);
        }
      }, 300);
    });

    // Foto: vista previa y límite de tamaño
    const fileInput = form.querySelector('[data-image-input]');
    const preview = form.querySelector('[data-image-preview]');
    const previewImage = preview.querySelector('img');
    const clearImage = () => {
      if (previewImage.src.startsWith('blob:')) URL.revokeObjectURL(previewImage.src);
      previewImage.removeAttribute('src');
      preview.hidden = true;
      fileInput.value = '';
    };
    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0];
      if (!file) return clearImage();
      if (file.size > 12 * 1024 * 1024) {
        alert('La foto pesa más de 12 MB. Elige otra.');
        return clearImage();
      }
      previewImage.src = URL.createObjectURL(file);
      preview.hidden = false;
    });
    form.querySelector('[data-image-remove]')?.addEventListener('click', clearImage);

    // Evita publicar dos veces si se toca el botón rápido
    form.addEventListener('submit', () => {
      form.querySelector('button[type="submit"]').disabled = true;
    });
  }

  document.querySelectorAll('[data-composer]').forEach(initComposer);

  // -------------------------------------------------------- Panel notificaciones
  (function () {
    const btn   = document.getElementById('notif-btn');
    const panel = document.getElementById('notif-panel');
    const body  = document.getElementById('notif-panel-body');
    const badge = document.getElementById('notif-badge');
    const icon  = document.getElementById('notif-icon');
    if (!btn || !panel) return;

    let loaded = false;

    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const open = !panel.hidden;
      panel.hidden = open;
      btn.setAttribute('aria-expanded', String(!open));
      if (open) return;

      // Cargar solo la primera vez o si habia no leídas
      if (!loaded || badge.textContent.trim() !== '0') {
        body.innerHTML = '<p class="notif-panel-empty">Cargando…</p>';
        try {
          const r = await fetch(`${endpoint}?page=notif_panel`, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
          const d = await r.json();
          body.innerHTML = d.html;
          // resetear badge
          badge.textContent = '0';
          badge.classList.add('d-none');
          icon.className = 'bi bi-bell';
          loaded = true;
        } catch {
          body.innerHTML = '<p class="notif-panel-empty">No se pudieron cargar.</p>';
        }
      }
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', (e) => {
      if (!document.getElementById('notif-wrap')?.contains(e.target)) {
        panel.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
      }
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { panel.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
    });
  })();

})();
