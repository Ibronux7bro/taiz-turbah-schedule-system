from telethon.sync import TelegramClient

api_id = 29723937
api_hash = '138b00a5303c9ca3e7f806467a353431'

# اسم الجلسة (سيتم إنشاء ملف college_user_session.session تلقائيًا)
client = TelegramClient("college_user_session", api_id, api_hash)

print("📌 سيتم الآن تسجيل الدخول إلى Telegram عبر Telethon...")
print("⚠️ أدخل رقم هاتفك مع رمز الدولة مثال: +9677XXXXXXXX")

client.start()  # Telethon سيطلب رقم الهاتف ثم رمز الكود تلقائيًا

print("✅ تم تسجيل الدخول بنجاح!")
print("📦 تم إنشاء ملف الجلسة: college_user_session.session")
print("🚀 الآن يمكنك تشغيل Flask ولن يطلب تسجيل الدخول مرة أخرى.")
