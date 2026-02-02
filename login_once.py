from telethon.sync import TelegramClient

# بيانات حساب تيليجرام الخاص بك
api_id = 29723937
api_hash = '138b00a5303c9ca3e7f806467a353431'

# اسم ملف الجلسة (سيُنشأ تلقائيًا)
with TelegramClient('college_user_session', api_id, api_hash) as client:
    print("✅ تم تسجيل الدخول بنجاح!")
