// ✅ تعريف العناصر بأمان
const navbar = document.querySelector('nav');
const navLinks = navbar.querySelector('ul');

// ✅ تفعيل تأثير التمرير
window.addEventListener('scroll', () => {
  if (window.scrollY > 50) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
});

// ✅ عرض نوع الجهاز
function checkDeviceType() {
  const width = window.innerWidth;

  if (width <= 600) {
    console.log("📱 أنت تستخدم جهاز جوال");
  } else if (width <= 1024) {
    console.log("📲 أنت تستخدم جهاز تابلت");
  } else {
    console.log("💻 أنت تستخدم جهاز كمبيوتر");
  }
}

checkDeviceType();
window.addEventListener('resize', checkDeviceType);

// ✅ إنشاء قائمة الجوال بشكل ديناميكي
function createMobileMenu() {
  // تحقق إن الزر غير موجود مسبقًا
  if (window.innerWidth <= 768 && !document.querySelector('.menu-btn')) {
    const menuButton = document.createElement('button');
    menuButton.innerHTML = '<i class="fas fa-bars"></i>';
    menuButton.classList.add('menu-btn');

    // أضف الزر قبل قائمة الروابط
    navbar.insertBefore(menuButton, navLinks);

    // عند الضغط، أظهر أو أخفِ القائمة
    menuButton.addEventListener('click', () => {
      navLinks.classList.toggle('active');
    });
  }
}

// شغّله عند بداية الصفحة
createMobileMenu();

// ✅ فقط استدعِ إنشاء القائمة بدون إعادة تحميل
window.addEventListener('resize', () => {
  createMobileMenu();
});
