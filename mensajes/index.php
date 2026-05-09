<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = getDB();
$pageTitle = 'Mensajes';

// Marcar como leído
if (isset($_GET['leer'])) {
    $mid = (int)$_GET['leer'];
    $db->query("UPDATE mensajes SET leido=1 WHERE id=$mid");
    header('Location: index.php');
    exit();
}

// Eliminar
if (isset($_GET['eliminar'])) {
    $mid = (int)$_GET['eliminar'];
    $db->query("DELETE FROM mensajes WHERE id=$mid");
    header('Location: index.php');
    exit();
}

$mensajes = $db->query("SELECT * FROM mensajes ORDER BY fecha DESC");
$no_leidos = $db->query("SELECT COUNT(*) AS total FROM mensajes WHERE leido=0")->fetch_assoc()['total'];

include '../views/layouts/header.php';
?>

<style>
.msg-card { border-radius: 12px; transition: all .2s; }
.msg-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }
.msg-unread { border-left: 4px solid #2E75B6 !important; background: #F0F4F8; }
.msg-read { border-left: 4px solid #dee2e6 !important; }
.notification-dot { width: 10px; height: 10px; border-radius: 50%; background: #2E75B6; display: inline-block; animation: blink 1.5s ease-in-out infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }
.badge-unread { background: #2E75B6; color: #fff; border-radius: 100px; padding: 2px 10px; font-size: 11px; }
</style>

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Mensajes</h5>
        <?php if ($no_leidos > 0): ?>
        <span class="badge-unread"><?= $no_leidos ?> nuevo<?= $no_leidos > 1 ? 's' : '' ?></span>
        <span class="notification-dot"></span>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="small text-muted" id="ultimo-actualizado">Actualizando...</span>
        <button onclick="marcarTodosLeidos()" class="btn btn-outline-primary btn-sm"><i class="bi bi-check-all me-1"></i>Marcar todos leídos</button>
    </div>
</div>

<!-- MENSAJES -->
<div id="mensajes-container">
<?php if ($mensajes->num_rows === 0): ?>
    <div class="text-center py-5 text-muted" id="empty-state">
        <i class="bi bi-chat-dots fs-1 d-block mb-3"></i>
        <h5>No hay mensajes aún</h5>
        <p class="small">Los mensajes del formulario de contacto aparecerán aquí.</p>
    </div>
<?php else: ?>
    <?php while ($m = $mensajes->fetch_assoc()): ?>
    <div class="card mb-2 msg-card <?= $m['leido'] ? 'msg-read' : 'msg-unread' ?>" id="msg-<?= $m['id'] ?>">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#1F4E79,#2E75B6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;flex-shrink:0;">
                        <?= strtoupper(substr($m['nombre'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:14px">
                            <?= htmlspecialchars($m['nombre']) ?>
                            <?php if (!$m['leido']): ?>
                                <span class="notification-dot ms-1"></span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted"><?= htmlspecialchars($m['correo'] ?? 'Sin correo') ?></div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></div>
                    <span class="badge bg-<?= $m['leido'] ? 'secondary' : 'primary' ?> mt-1"><?= $m['leido'] ? 'Leído' : 'Nuevo' ?></span>
                </div>
            </div>
            <div class="mt-2">
                <div class="small fw-semibold text-primary mb-1"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($m['asunto'] ?? 'Sin asunto') ?></div>
                <div style="font-size:14px;color:#333;line-height:1.6"><?= nl2br(htmlspecialchars($m['mensaje'])) ?></div>
            </div>
            <div class="mt-2 d-flex gap-2">
                <?php if (!$m['leido']): ?>
                <a href="index.php?leer=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-check me-1"></i>Marcar leído</a>
                <?php endif; ?>
                <?php if ($m['correo']): ?>
                <a href="mailto:<?= htmlspecialchars($m['correo']) ?>" class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-reply me-1"></i>Responder</a>
                <?php endif; ?>
                <a href="index.php?eliminar=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger py-0" onclick="return confirm('¿Eliminar este mensaje?')"><i class="bi bi-trash me-1"></i>Eliminar</a>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
<?php endif; ?>
</div>

<script>
// Notificaciones en tiempo real con polling cada 5 segundos
let ultimoId = <?= $db->query("SELECT MAX(id) AS id FROM mensajes")->fetch_assoc()['id'] ?? 0 ?>;
let audioCtx = null;

function playNotification() {
    try {
        audioCtx = audioCtx || new AudioContext();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
        osc.start(audioCtx.currentTime);
        osc.stop(audioCtx.currentTime + 0.3);
    } catch(e) {}
}

function checkNuevosMensajes() {
    fetch('check_mensajes.php?ultimo_id=' + ultimoId)
        .then(r => r.json())
        .then(data => {
            if (data.nuevos && data.nuevos.length > 0) {
                playNotification();
                // Mostrar notificación del navegador
                if (Notification.permission === 'granted') {
                    data.nuevos.forEach(m => {
                        new Notification('💬 Nuevo mensaje - MiniMarket G2', {
                            body: m.nombre + ': ' + m.mensaje.substring(0, 80),
                            icon: '/favicon.ico'
                        });
                    });
                }
                // Recargar la página para mostrar nuevos mensajes
                location.reload();
            }
            ultimoId = data.ultimo_id || ultimoId;
            document.getElementById('ultimo-actualizado').textContent = 'Actualizado: ' + new Date().toLocaleTimeString('es-DO');
        })
        .catch(() => {});
}

// Pedir permiso para notificaciones
if (Notification.permission === 'default') {
    Notification.requestPermission();
}

// Polling cada 5 segundos
setInterval(checkNuevosMensajes, 5000);
checkNuevosMensajes();

function marcarTodosLeidos() {
    fetch('marcar_todos.php')
        .then(() => location.reload());
}
</script>

<?php include '../views/layouts/footer.php'; ?>
