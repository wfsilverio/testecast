import os
from fastapi import FastAPI
import psycopg2
from psycopg2.extras import RealDictCursor

def get_connection():
    return psycopg2.connect(
        host=os.getenv("DB_HOST", "localhost"),
        port=os.getenv("DB_PORT", "5432"),
        dbname=os.getenv("DB_NAME", "appdb"),
        user=os.getenv("DB_USER", "appuser"),
        password=os.getenv("DB_PASSWORD", "apppass"),
    )

app = FastAPI(title="Teste Cast API")

@app.on_event("startup")
def startup():
    conn = get_connection()
    cur = conn.cursor()
    cur.execute("CREATE TABLE IF NOT EXISTS items (id SERIAL PRIMARY KEY, name TEXT NOT NULL)")
    conn.commit()
    cur.close()
    conn.close()

@app.get("/health")
def health():
    conn = get_connection()
    cur = conn.cursor()
    cur.execute("SELECT 1")
    val = cur.fetchone()
    cur.close()
    conn.close()
    return {"status": "ok", "db": bool(val)}

@app.post("/items")
def create_item(name: str):
    conn = get_connection()
    cur = conn.cursor(cursor_factory=RealDictCursor)
    cur.execute("INSERT INTO items (name) VALUES (%s) RETURNING id, name", (name,))
    row = cur.fetchone()
    conn.commit()
    cur.close()
    conn.close()
    return row

@app.get("/items")
def list_items():
    conn = get_connection()
    cur = conn.cursor(cursor_factory=RealDictCursor)
    cur.execute("SELECT id, name FROM items ORDER BY id")
    rows = cur.fetchall()
    cur.close()
    conn.close()
    return rows

