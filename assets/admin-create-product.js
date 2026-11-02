// Ghi chú: File script xử lý tương tác giao diện và gọi API.
const form = document.getElementById("createProductForm");
const errorEl = document.getElementById("createProductError");
const toastEl = document.getElementById("toast");

function showToast(message) {
  if (!toastEl) return;
  toastEl.textContent = message;
  toastEl.classList.add("show");
  window.setTimeout(() => toastEl.classList.remove("show"), 1800);
}
// Hàm hiển thị thông báo ngắn khi thao tác thành công.

function setError(message = "") {
  if (!errorEl) return;
  errorEl.textContent = message;
  errorEl.classList.toggle("hidden", message === "");
}
// Hàm hiển thị lỗi inline ngay dưới form tạo sản phẩm.

async function submitForm(event) {
  event.preventDefault();
  if (!form) return;

  const formData = new FormData(form);
  const payload = {
    name: String(formData.get("name") || "").trim(),
    brand: String(formData.get("brand") || "").trim(),
    category: String(formData.get("category") || "").trim(),
    price: Number(formData.get("price") || 0),
    oldPrice: String(formData.get("oldPrice") || "").trim(),
    stock: Number(formData.get("stock") || -1),
    image: String(formData.get("image") || "").trim(),
    gallery: String(formData.get("gallery") || "").trim(),
    colors: String(formData.get("colors") || "").trim(),
    description: String(formData.get("description") || "").trim(),
    technicalSpecs: String(formData.get("technicalSpecs") || "").trim()
  };

  try {
    const response = await fetch("./api/admin/products/create.php", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const data = await response.json().catch(() => ({ ok: false, message: "Lỗi phản hồi máy chủ." }));
    if (!response.ok || !data.ok) {
      throw new Error(data.message || "Tạo sản phẩm thất bại.");
    }

    setError("");
    showToast("Tạo sản phẩm thành công.");
    form.reset();
  } catch (error) {
    setError(error.message);
  }
}
// Hàm gửi dữ liệu tạo sản phẩm tới API admin và xử lý phản hồi.

form?.addEventListener("submit", submitForm);



