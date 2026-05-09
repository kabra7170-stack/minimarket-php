<?php if (!function_exists('setToast')): ?>
<?php
function setToast($tipo, $mensaje) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['toast'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}
function getToast() {
    if (isset($_SESSION['toast'])) {
        $t = $_SESSION['toast'];
        unset($_SESSION['toast']);
        return $t;
    }
    return null;
}
?>
<?php endif; ?>

<!-- TOAST CONTAINER -->
<div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;min-width:300px;max-width:400px;"></div>

<?php $toast = getToast(); ?>
<?php if ($toast): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    showToast('<?= addslashes($toast['tipo']) ?>', '<?= addslashes($toast['mensaje']) ?>');
});
</script>
<?php endif; ?>

<style>
.toast-item { display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-radius:12px;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.18);animation:slideIn .3s ease;font-size:14px;position:relative;overflow:hidden; }
.toast-item.success { background:linear-gradient(135deg,#0F6E56,#1aad87); }
.toast-item.error   { background:linear-gradient(135deg,#C00000,#e03131); }
.toast-item.warning { background:linear-gradient(135deg,#BF5800,#e67700); }
.toast-item.info    { background:linear-gradient(135deg,#1F4E79,#2E75B6); }
.toast-icon  { font-size:20px;flex-shrink:0;margin-top:1px; }
.toast-body  { flex:1; }
.toast-title { font-weight:700;font-size:13px;margin-bottom:2px; }
.toast-msg   { font-size:13px;opacity:.92; }
.toast-close { background:none;border:none;color:rgba(255,255,255,.7);font-size:18px;cursor:pointer;padding:0;line-height:1;margin-top:-2px; }
.toast-close:hover { color:#fff; }
.toast-progress { position:absolute;bottom:0;left:0;height:3px;background:rgba(255,255,255,.4);animation:progress 4s linear forwards; }
@keyframes slideIn  { from{transform:translateX(120%);opacity:0} to{transform:translateX(0);opacity:1} }
@keyframes slideOut { from{transform:translateX(0);opacity:1} to{transform:translateX(120%);opacity:0} }
@keyframes progress { from{width:100%} to{width:0%} }
</style>

<script>
const toastIcons  = {success:'bi-check-circle-fill',error:'bi-x-circle-fill',warning:'bi-exclamation-triangle-fill',info:'bi-info-circle-fill'};
const toastTitles = {success:'Éxito',error:'Error',warning:'Advertencia',info:'Información'};

function showToast(tipo, mensaje, duracion=4000) {
    const container = document.getElementById('toastContainer');
    const id = 'toast_' + Date.now();
    const icon  = toastIcons[tipo]  || toastIcons.info;
    const title = toastTitles[tipo] || 'Aviso';
    const el = document.createElement('div');
    el.id = id;
    el.className = `toast-item ${tipo}`;
    el.innerHTML = `
        <i class="bi ${icon} toast-icon"></i>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-msg">${mensaje}</div>
        </div>
        <button class="toast-close" onclick="closeToast('${id}')">&times;</button>
        <div class="toast-progress"></div>`;
    container.appendChild(el);
    setTimeout(() => closeToast(id), duracion);
}
function closeToast(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.animation = 'slideOut .3s ease forwards';
    setTimeout(() => el.remove(), 300);
}
</script>
