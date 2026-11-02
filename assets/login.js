// Ghi chú: File script xử lý tương tác giao diện và gọi API.
const authCard = document.getElementById("authCard");
const unifiedLoginForm = document.getElementById("unifiedLoginForm");
const signupForm = document.getElementById("userSignupForm");
const messageEl = document.getElementById("loginMessage");
const loginError = document.getElementById("loginError");
const signupError = document.getElementById("signupError");
const modeButtons = document.querySelectorAll("[data-auth-mode]");
const passwordToggleButtons = document.querySelectorAll("[data-password-target]");

let activeMode = window.__INITIAL_TAB__ === "signup" ? "signup" : "login";
let isSubmitting = false;

function setMessage(text, isError = false) {
  if (!messageEl) return;
  messageEl.textContent = text;
  messageEl.classList.toggle("is-error", isError);
}
// Hàm hiển thị thông báo trạng thái chung trên trang đăng nhập.

function setFormDisabled(form, disabled) {
  if (!form) return;
  form.querySelectorAll("input, button").forEach((element) => {
    element.disabled = disabled;
  });
}
// Hàm khóa/mở form để chống submit lặp khi request đang xử lý.

function setMode(mode, shouldFocus = false) {
  activeMode = mode === "signup" ? "signup" : "login";
  const isSignup = activeMode === "signup";
  authCard?.classList.toggle("is-signup", isSignup);
  unifiedLoginForm?.toggleAttribute("inert", isSignup);
  signupForm?.toggleAttribute("inert", !isSignup);
  unifiedLoginForm?.setAttribute("aria-hidden", String(isSignup));
  signupForm?.setAttribute("aria-hidden", String(!isSignup));
  setMessage("");
  loginError?.classList.add("hidden");
  signupError?.classList.add("hidden");
  if (shouldFocus) {
    window.setTimeout(() => {
      (isSignup ? signupForm : unifiedLoginForm)?.querySelector("input")?.focus();
    }, 350);
  }
}
// Hàm đổi màn hình đăng nhập/đăng ký và dọn thông báo cũ.

function setInlineError(mode, message = "") {
  const target = mode === "signup" ? signupError : loginError;
  if (!target) return;
  target.textContent = message;
  target.classList.toggle("hidden", message === "");
}
// Hàm hiển thị lỗi ngay dưới đúng form tương ứng.

async function sendRequest(url, payload) {
  const response = await fetch(url, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  const data = await response.json().catch(() => ({ ok: false, message: "Lỗi phản hồi từ máy chủ." }));
  if (!response.ok || !data.ok) {
    throw new Error(data.message || "Yêu cầu thất bại.");
  }
  return data;
}
// Hàm gọi API và chuẩn hóa lỗi phản hồi cho giao diện.

modeButtons.forEach((button) => {
  button.addEventListener("click", () => {
    if (isSubmitting) return;
    setMode(button.dataset.authMode, true);
  });
});

passwordToggleButtons.forEach((button) => {
  button.addEventListener("click", () => {
    const input = document.getElementById(button.dataset.passwordTarget || "");
    if (!(input instanceof HTMLInputElement)) return;

    const isVisible = input.type === "password";
    input.type = isVisible ? "text" : "password";
    button.classList.toggle("is-visible", isVisible);
    button.setAttribute("aria-pressed", String(isVisible));
    button.setAttribute("aria-label", isVisible ? "Ẩn mật khẩu" : "Hiện mật khẩu");
  });
});

unifiedLoginForm?.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (isSubmitting) return;
  if (!unifiedLoginForm.reportValidity()) return;
  const formData = new FormData(unifiedLoginForm);
  const payload = {
    username: String(formData.get("username") || "").trim(),
    password: String(formData.get("password") || "")
  };
  isSubmitting = true;
  setFormDisabled(unifiedLoginForm, true);

  try {
    const data = await sendRequest("./api/auth/login.php", payload);
    setInlineError("login", "");
    const isAdmin = data.role === "admin";
    setMessage(`Đăng nhập ${isAdmin ? "quản trị" : "thành công"}, đang chuyển trang...`);
    window.location.href = isAdmin ? "./index.php?p=admin-products" : "./index.php?p=home";
  } catch (error) {
    setInlineError("login", error.message);
    setMessage("", true);
  } finally {
    isSubmitting = false;
    setFormDisabled(unifiedLoginForm, false);
  }
});

signupForm?.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (isSubmitting) return;
  if (!signupForm.reportValidity()) return;

  const formData = new FormData(signupForm);
  const username = String(formData.get("username") || "").trim();
  const password = String(formData.get("password") || "");
  const confirmPassword = String(formData.get("confirmPassword") || "");

  if (password !== confirmPassword) {
    setInlineError("signup", "Mật khẩu xác nhận không khớp.");
    return;
  }

  isSubmitting = true;
  setFormDisabled(signupForm, true);

  try {
    await sendRequest("./api/user/register.php", { username, password, confirmPassword });
    setInlineError("signup", "");
    signupForm.reset();
    setMode("login");
    setMessage("Đăng ký thành công. Bạn có thể đăng nhập ngay.", false);
  } catch (error) {
    setInlineError("signup", error.message);
    setMessage("", true);
  } finally {
    isSubmitting = false;
    setFormDisabled(signupForm, false);
  }
});

setMode(activeMode);



