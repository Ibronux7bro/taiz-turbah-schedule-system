# -*- coding: utf-8 -*-
import sys
sys.stdout.reconfigure(encoding='utf-8')

from flask import Flask, request
import mysql.connector
import threading
import queue
import os

from telethon import TelegramClient

app = Flask(__name__)

# إعدادات تيليجرام
api_id = 29723937
api_hash = "138b00a5303c9ca3e7f806467a353431"

# ====== Queue لإرسال الرسائل خارج Thread Flask ======
task_queue = queue.Queue()

# ====== Worker Thread خاص بـ Telethon فقط ======
def telegram_worker():
    client = TelegramClient('college_user_session', api_id, api_hash)
    client.start()

    while True:
        task = task_queue.get()
        if task is None:
            break

        try:
            phone, message, file_path = task
            client.send_message(phone, message)
            if file_path:
                client.send_file(phone, file_path)
            print(f"تم الإرسال إلى {phone}")
        except Exception as e:
            print(f"خطأ أثناء الإرسال: {e}")

# تشغيل Thread العامل وقت تشغيل السيرفر
worker_thread = threading.Thread(target=telegram_worker, daemon=True)
worker_thread.start()


# قاعدة البيانات
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'college_schedule_system'
}


@app.route("/send-schedule", methods=["POST"])
def send_schedule():

    phone = request.form.get("phone")

    if not phone:
        return "⚠ رقم الهاتف مفقود", 400

    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)

        cursor.execute("SELECT * FROM students WHERE phone = %s", (phone,))
        student = cursor.fetchone()

        if not student:
            return "❌ الطالب غير موجود", 404

        department_id = student["department_id"]
        name = student["student_name"]

        cursor.execute("SELECT * FROM schedules WHERE department_id = %s", (department_id,))
        schedule = cursor.fetchone()

        if not schedule:
            return "❌ لا يوجد جدول لهذا القسم", 404

        filename = schedule["file_name"].replace("uploads/", "")

        uploads_dir = os.path.join(os.path.dirname(__file__), "manger", "uploads")
        file_path = os.path.join(uploads_dir, filename)

        if not os.path.exists(file_path):
            return f"❌ الملف غير موجود: {file_path}", 404

        # إرسال المهمة إلى worker thread
        task_queue.put(
            (phone, f"مرحباً {name} 👋\nهذا جدولك الدراسي…", file_path)
        )

        return "✅ تم إضافة الإرسال إلى قائمة المهام"

    except Exception as e:
        return f"❌ خطأ أثناء تنفيذ الطلب: {e}", 500

    finally:
        try:
            cursor.close()
            conn.close()
        except:
            pass


if __name__ == "__main__":
    app.run(debug=True)
