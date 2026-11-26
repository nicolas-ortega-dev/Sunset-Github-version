// Botón "Ver más" del catálogo: pide la siguiente tanda de libros y la agrega a la cuadrícula.
(() => {
  const panel = document.getElementById('catalog');
  const grid = document.getElementById('book-grid');
  const button = document.getElementById('load-more');
  if (!panel || !grid || !button) return;

  let offset = parseInt(panel.dataset.offset, 10) || 0;
  const label = button.innerHTML;

  button.addEventListener('click', async () => {
    button.disabled = true;
    button.textContent = 'Cargando…';
    try {
      const response = await fetch(`${panel.dataset.moreUrl}&offset=${offset}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      grid.insertAdjacentHTML('beforeend', data.html);
      offset = data.next;
      if (data.hasMore) {
        button.disabled = false;
        button.innerHTML = label;
      } else {
        button.remove();
      }
    } catch (error) {
      button.disabled = false;
      button.textContent = 'Reintentar';
    }
  });
})();
