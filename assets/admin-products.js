// Ghi chú: Script điều khiển popup xác nhận xóa cho trang admin sản phẩm.
(function () {
  const modal = document.getElementById('deleteModal');
  const closeBtn = document.getElementById('closeDeleteModalBtn');
  const hiddenId = document.getElementById('deleteProductId');
  const text = document.getElementById('deleteModalText');

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  }

  function openModal(id, name) {
    if (!modal || !hiddenId) return;
    hiddenId.value = String(id || '');
    if (text) text.textContent = `Bạn có chắc muốn xóa sản phẩm "${name}"? Hành động này không thể hoàn tác.`;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  }

  document.querySelectorAll('.js-open-delete').forEach((btn) => {
    btn.addEventListener('click', () => {
      openModal(btn.dataset.id, btn.dataset.name || '');
    });
  });

  closeBtn?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
  });
})();
