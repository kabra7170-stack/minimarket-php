from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional
import pymysql
import os
from dotenv import load_dotenv
from datetime import datetime

load_dotenv()

app = FastAPI(
    title="MiniMarket G2 API",
    description="API REST para el sistema de gestión MiniMarket G2",
    version="1.0.0"
)

# CORS - permite acceso desde cualquier origen
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ─── CONEXIÓN A BASE DE DATOS ──────────────────────────
def get_db():
    return pymysql.connect(
        host="localhost",
        user="root",
        password="",
        database="minimarket_g2",
        port=3306,
        cursorclass=pymysql.cursors.DictCursor
    )

# ─── MODELOS ───────────────────────────────────────────
class Mensaje(BaseModel):
    nombre: str
    correo: Optional[str] = None
    asunto: Optional[str] = None
    mensaje: str

class PedidoWebhook(BaseModel):
    pedido_id: int

# ─── RUTAS ────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "sistema": "MiniMarket G2",
        "version": "1.0.0",
        "status": "online",
        "timestamp": datetime.now().isoformat()
    }

@app.get("/health")
def health():
    return {"status": "ok", "timestamp": datetime.now().isoformat()}

# ── PRODUCTOS ──────────────────────────────────────────
@app.get("/api/productos")
def get_productos(categoria: Optional[int] = None, buscar: Optional[str] = None):
    db = get_db()
    try:
        with db.cursor() as cursor:
            query = """
                SELECT p.id, p.nombre, p.precio_venta, p.stock_actual,
                       p.imagen, p.descripcion, c.nombre AS categoria
                FROM productos p
                JOIN categorias c ON p.categoria_id = c.id
                WHERE p.activo = 1 AND p.stock_actual > 0
            """
            params = []
            if categoria:
                query += " AND p.categoria_id = %s"
                params.append(categoria)
            if buscar:
                query += " AND p.nombre LIKE %s"
                params.append(f"%{buscar}%")
            query += " ORDER BY p.nombre"
            cursor.execute(query, params)
            return {"success": True, "data": cursor.fetchall()}
    finally:
        db.close()

@app.get("/api/productos/{id}")
def get_producto(id: int):
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("""
                SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor
                FROM productos p
                JOIN categorias c ON p.categoria_id = c.id
                JOIN proveedores pr ON p.proveedor_id = pr.id
                WHERE p.id = %s AND p.activo = 1
            """, (id,))
            producto = cursor.fetchone()
            if not producto:
                raise HTTPException(status_code=404, detail="Producto no encontrado")
            return {"success": True, "data": producto}
    finally:
        db.close()

# ── CATEGORÍAS ─────────────────────────────────────────
@app.get("/api/categorias")
def get_categorias():
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("SELECT * FROM categorias ORDER BY nombre")
            return {"success": True, "data": cursor.fetchall()}
    finally:
        db.close()

# ── VENTAS ─────────────────────────────────────────────
@app.get("/api/ventas")
def get_ventas(desde: Optional[str] = None, hasta: Optional[str] = None):
    db = get_db()
    try:
        with db.cursor() as cursor:
            query = """
                SELECT v.*, u.nombre AS cajero, c.nombre AS cliente
                FROM ventas v
                JOIN usuarios u ON v.usuario_id = u.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                WHERE v.estado = 'completada'
            """
            params = []
            if desde:
                query += " AND DATE(v.fecha) >= %s"
                params.append(desde)
            if hasta:
                query += " AND DATE(v.fecha) <= %s"
                params.append(hasta)
            query += " ORDER BY v.fecha DESC"
            cursor.execute(query, params)
            return {"success": True, "data": cursor.fetchall()}
    finally:
        db.close()

@app.get("/api/ventas/resumen")
def get_resumen_ventas():
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("""
                SELECT
                    COUNT(*) AS total_ventas,
                    IFNULL(SUM(total), 0) AS ingresos_totales,
                    IFNULL(SUM(CASE WHEN DATE(fecha) = CURDATE() THEN total ELSE 0 END), 0) AS ingresos_hoy,
                    COUNT(CASE WHEN DATE(fecha) = CURDATE() THEN 1 END) AS ventas_hoy
                FROM ventas WHERE estado = 'completada'
            """)
            return {"success": True, "data": cursor.fetchone()}
    finally:
        db.close()

# ── STOCK BAJO ─────────────────────────────────────────
@app.get("/api/stock-bajo")
def get_stock_bajo():
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("""
                SELECT p.id, p.nombre, p.stock_actual, p.stock_minimo, c.nombre AS categoria
                FROM productos p
                JOIN categorias c ON p.categoria_id = c.id
                WHERE p.stock_actual <= p.stock_minimo AND p.activo = 1
                ORDER BY p.stock_actual ASC
            """)
            data = cursor.fetchall()
            return {"success": True, "total": len(data), "data": data}
    finally:
        db.close()

# ── ALERTAS ────────────────────────────────────────────
@app.get("/api/alertas")
def get_alertas():
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("""
                SELECT a.*, p.nombre AS producto
                FROM alertas a
                JOIN productos p ON a.producto_id = p.id
                ORDER BY a.fecha DESC
            """)
            return {"success": True, "data": cursor.fetchall()}
    finally:
        db.close()

# ── MENSAJES ───────────────────────────────────────────
@app.post("/api/mensajes")
def crear_mensaje(msg: Mensaje):
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("""
                INSERT INTO mensajes (nombre, correo, asunto, mensaje)
                VALUES (%s, %s, %s, %s)
            """, (msg.nombre, msg.correo, msg.asunto, msg.mensaje))
            db.commit()
            return {"success": True, "id": cursor.lastrowid}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        db.close()

@app.get("/api/mensajes")
def get_mensajes():
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("SELECT * FROM mensajes ORDER BY fecha DESC")
            return {"success": True, "data": cursor.fetchall()}
    finally:
        db.close()

@app.get("/api/mensajes/no-leidos")
def get_mensajes_no_leidos():
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("SELECT COUNT(*) AS total FROM mensajes WHERE leido = 0")
            return {"success": True, "data": cursor.fetchone()}
    finally:
        db.close()

# ── DASHBOARD ──────────────────────────────────────────
@app.get("/api/dashboard")
def get_dashboard():
    db = get_db()
    try:
        with db.cursor() as cursor:
            # Ventas hoy
            cursor.execute("""
                SELECT IFNULL(SUM(total), 0) AS ingresos_hoy, COUNT(*) AS ventas_hoy
                FROM ventas WHERE DATE(fecha) = CURDATE() AND estado = 'completada'
            """)
            ventas_hoy = cursor.fetchone()

            # Total productos
            cursor.execute("SELECT COUNT(*) AS total FROM productos WHERE activo = 1")
            total_productos = cursor.fetchone()

            # Stock bajo
            cursor.execute("SELECT COUNT(*) AS total FROM productos WHERE stock_actual <= stock_minimo AND activo = 1")
            stock_bajo = cursor.fetchone()

            # Mensajes no leídos
            cursor.execute("SELECT COUNT(*) AS total FROM mensajes WHERE leido = 0")
            mensajes = cursor.fetchone()

            # Top 5 productos más vendidos
            cursor.execute("""
                SELECT p.nombre, SUM(dv.cantidad) AS total_vendido
                FROM detalle_ventas dv
                JOIN productos p ON dv.producto_id = p.id
                JOIN ventas v ON dv.venta_id = v.id
                WHERE v.estado = 'completada'
                GROUP BY p.id ORDER BY total_vendido DESC LIMIT 5
            """)
            top_productos = cursor.fetchall()

            return {
                "success": True,
                "data": {
                    "ventas_hoy": ventas_hoy,
                    "total_productos": total_productos['total'],
                    "stock_bajo": stock_bajo['total'],
                    "mensajes_no_leidos": mensajes['total'],
                    "top_productos": top_productos
                }
            }
    finally:
        db.close()

# ── PEDIDOS ────────────────────────────────────────────
@app.get("/api/pedidos")
def get_pedidos():
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("""
                SELECT p.*, c.nombre AS cliente, u.nombre AS cajero
                FROM pedidos p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                JOIN usuarios u ON p.usuario_id = u.id
                ORDER BY p.fecha DESC LIMIT 50
            """)
            return {"success": True, "data": cursor.fetchall()}
    finally:
        db.close()

# ── CLIENTES ───────────────────────────────────────────
@app.get("/api/clientes")
def get_clientes():
    db = get_db()
    try:
        with db.cursor() as cursor:
            cursor.execute("SELECT id, nombre, cedula, telefono, email, activo FROM clientes ORDER BY nombre")
            return {"success": True, "data": cursor.fetchall()}
    finally:
        db.close()
