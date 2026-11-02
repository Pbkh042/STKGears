<?php
declare(strict_types=1);

// Included by authenticated user pages after config/auth.php and core/view.php.
function order_cancel_token(): string
{
    if (empty($_SESSION['order_cancel_token'])) {
        $_SESSION['order_cancel_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['order_cancel_token'];
}

function render_order_cancel_form(int $orderId): void
{
    ?>
    <form method="post" action="./index.php?p=user-order-detail&amp;id=<?= $orderId ?>"
          onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
      <input type="hidden" name="action" value="cancel_order">
      <input type="hidden" name="csrf_token" value="<?= esc(order_cancel_token()) ?>">
      <button type="submit" class="danger-btn">Hủy đơn</button>
    </form>
    <?php
}
