<!DOCTYPE html>
<html lang="ar" dir="rtl" class="theme-1">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>لوحة إعدادات جانبية - RTL</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      /* تعريف افتراضي للمتغيرات */
      --primary-color: #3498db;
      --sidebar-color: #2980b9;
    }

    :root.theme-1 {
      --primary-color: #3498db;
      --sidebar-color: #2980b9;
    }

    :root.theme-2 {
      --primary-color: #e74c3c;
      --sidebar-color: #c0392b;
    }

    :root.theme-3 {
      --primary-color: #2ecc71;
      --sidebar-color: #27ae60;
    }

    :root.theme-4 {
      --primary-color: #f39c12;
      --sidebar-color: #d35400;
    }

    :root.theme-5 {
      --primary-color: #9b59b6;
      --sidebar-color: #8e44ad;
    }

    :root.theme-6 {
      --primary-color: #1abc9c;
      --sidebar-color: #16a085;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Tajawal', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
      min-height: 100vh;
      padding: 20px;
      color: #333;
    }

    .content {
      max-width: 800px;
      margin: 0 auto;
      padding: 60px 20px;
      text-align: center;
    }

    .content h1 {
      font-size: 2.5rem;
      margin-bottom: 20px;
      color: #2c3e50;
    }

    .content p {
      font-size: 1.1rem;
      line-height: 1.8;
      margin-bottom: 30px;
      color: #555;
    }

    .features {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 25px;
      margin-top: 50px;
    }

    .feature {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 15px;
      padding: 25px;
      width: 250px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      backdrop-filter: blur(5px);
      border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .feature:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }

    .feature i {
      font-size: 3rem;
      color: var(--primary-color);
      margin-bottom: 20px;
    }

    .feature h3 {
      font-size: 1.4rem;
      margin-bottom: 15px;
      color: #2c3e50;
    }

    .feature p {
      font-size: 1rem;
      color: #555;
      margin-bottom: 0;
    }

    #toggle-settings {
      position: fixed;
      top: 10px;
      right: 107px;
      z-index: 1300;
      background: var(--primary-color);
      border: none;
      border-radius: 50%;
      width: 45px;
      height: 45px;
      cursor: pointer;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
    }

    #toggle-settings:hover {
      background-color: var(--sidebar-color);
      transform: rotate(90deg);
    }

    #toggle-settings i {
      font-size: 1.5rem;
      color: #fff;
      transition: all 0.3s ease;
    }

    .settings {
      position: fixed;
      top: 0;
      right: 0;
      width: 320px;
      height: 100vh;
      background: #fff;
      box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
      padding: 25px;
      box-sizing: border-box;
      transform: translateX(100%);
      transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      z-index: 1299;
      overflow-y: auto;
    }

    .settings.active {
      transform: translateX(0);
    }

    .panel-heading {
      font-weight: 700;
      font-size: 1.6rem;
      margin-bottom: 25px;
      padding-bottom: 15px;
      text-align: center;
      border-bottom: 3px solid var(--primary-color);
      color: #2c3e50;
      position: relative;
    }

    .panel-heading::after {
      content: "";
      position: absolute;
      bottom: -3px;
      right: 0;
      width: 100px;
      height: 3px;
      background: var(--sidebar-color);
      border-radius: 3px;
    }

    .setting-box {
      margin-bottom: 20px;
      padding: 15px;
      background: #f9f9f9;
      border-radius: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 1.1rem;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    .setting-box:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .setting-title {
      font-weight: 600;
      color: #2c3e50;
      flex: 1;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .setting-title i {
      width: 35px;
      height: 35px;
      background: #e8f4fc;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-color);
      font-size: 1.1rem;
    }

    .setting-switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 30px;
    }

    .setting-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .switch-slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 34px;
    }

    .switch-slider:before {
      position: absolute;
      content: "";
      height: 22px;
      width: 22px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked+.switch-slider {
      background-color: #2ecc71;
    }

    input:checked+.switch-slider:before {
      transform: translateX(30px);
    }

    .theme-section {
      margin-top: 30px;
    }

    .theme-title {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 20px;
      color: #2c3e50;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .theme-title i {
      color: var(--primary-color);
      font-size: 1.4rem;
    }

    .colors-row {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-top: 15px;
    }

    .color-theme {
      cursor: pointer;
      border: 2px solid transparent;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
    }

    .color-theme:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .color-theme.active {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
    }

    .color-layout {
      position: relative;
      padding: 15px;
    }

    .color-layout input[type="radio"] {
      display: none;
    }

    .ti-check {
      position: absolute;
      top: 10px;
      right: 10px;
      width: 24px;
      height: 24px;
      background: var(--primary-color);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 14px;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .color-layout input[type="radio"]:checked~.ti-check {
      opacity: 1;
    }

    .split {
      display: flex;
      gap: 8px;
      margin-top: 10px;
    }

    .th-header,
    .th-sidebar,
    .th-collapse,
    .th-body {
      border-radius: 4px;
    }

    .th-header {
      height: 12px;
    }

    .th-collapse {
      height: 12px;
    }

    .th-sidebar {
      height: 40px;
    }

    .th-body {
      height: 40px;
      background: #ecf0f1;
    }

    .theme-name {
      text-align: center;
      padding: 10px;
      font-weight: 600;
      background: #f9f9f9;
      color: #2c3e50;
    }

    .save-btn {
      margin-top: 30px;
      padding: 16px;
      width: 100%;
      background: var(--primary-color);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 1.2rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }

    .save-btn:hover {
      background: var(--sidebar-color);
      transform: translateY(-3px);
    }

    .save-btn:active {
      transform: translateY(0);
    }

    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1200;
      opacity: 0;
      visibility: hidden;
      transition: all 0.4s ease;
    }

    .overlay.active {
      opacity: 1;
      visibility: visible;
    }

    @media (max-width: 768px) {
      .settings {
        width: 280px;
      }

      .features {
        flex-direction: column;
        align-items: center;
      }

      .feature {
        width: 100%;
        max-width: 350px;
      }
    }
  </style>
</head>

<body>
  <!-- زر التبديل -->
  <button id="toggle-settings"><i class="fa fa-gear"></i></button>

  <!-- لوحة الإعدادات الجانبية -->
  <div class="settings" id="settings">
    <div class="panel-heading">محدد النمط</div>
    <div class="panel-body">
      <!-- الثيمات -->
      <div class="theme-section">
        <div class="theme-title"><i class="fas fa-palette"></i>اختر الثيم المفضل</div>
        <div class="colors-row">
          <div class="color-theme theme-1 active">
            <div class="color-layout">
              <input type="radio" name="setting-theme" value="theme-1" id="theme-1" checked />
              <span class="ti-check">✓</span>
              <div class="split header">
                <span class="th-header" style="background:#3498db;"></span>
                <span class="th-collapse" style="background:#2980b9;"></span>
              </div>
              <div class="split">
                <span class="th-sidebar" style="background:#3498db;"></span>
                <span class="th-body"></span>
              </div>
            </div>
            <div class="theme-name">أزرق</div>
          </div>
          <div class="color-theme theme-2">
            <div class="color-layout">
              <input type="radio" name="setting-theme" value="theme-2" id="theme-2" />
              <span class="ti-check">✓</span>
              <div class="split header">
                <span class="th-header" style="background:#e74c3c;"></span>
                <span class="th-collapse" style="background:#c0392b;"></span>
              </div>
              <div class="split">
                <span class="th-sidebar" style="background:#e74c3c;"></span>
                <span class="th-body"></span>
              </div>
            </div>
            <div class="theme-name">أحمر</div>
          </div>
          <div class="color-theme theme-3">
            <div class="color-layout">
              <input type="radio" name="setting-theme" value="theme-3" id="theme-3" />
              <span class="ti-check">✓</span>
              <div class="split header">
                <span class="th-header" style="background:#2ecc71;"></span>
                <span class="th-collapse" style="background:#27ae60;"></span>
              </div>
              <div class="split">
                <span class="th-sidebar" style="background:#2ecc71;"></span>
                <span class="th-body"></span>
              </div>
            </div>
            <div class="theme-name">أخضر</div>
          </div>
          <div class="color-theme theme-4">
            <div class="color-layout">
              <input type="radio" name="setting-theme" value="theme-4" id="theme-4" />
              <span class="ti-check">✓</span>
              <div class="split header">
                <span class="th-header" style="background:#f39c12;"></span>
                <span class="th-collapse" style="background:#d35400;"></span>
              </div>
              <div class="split">
                <span class="th-sidebar" style="background:#f39c12;"></span>
                <span class="th-body"></span>
              </div>
            </div>
            <div class="theme-name">برتقالي</div>
          </div>
          <div class="color-theme theme-5">
            <div class="color-layout">
              <input type="radio" name="setting-theme" value="theme-5" id="theme-5" />
              <span class="ti-check">✓</span>
              <div class="split header">
                <span class="th-header" style="background:#9b59b6;"></span>
                <span class="th-collapse" style="background:#8e44ad;"></span>
              </div>
              <div class="split">
                <span class="th-sidebar" style="background:#9b59b6;"></span>
                <span class="th-body"></span>
              </div>
            </div>
            <div class="theme-name">بنفسجي</div>
          </div>
          <div class="color-theme theme-6">
            <div class="color-layout">
              <input type="radio" name="setting-theme" value="theme-6" id="theme-6" />
              <span class="ti-check">✓</span>
              <div class="split header">
                <span class="th-header" style="background:#1abc9c;"></span>
                <span class="th-collapse" style="background:#16a085;"></span>
              </div>
              <div class="split">
                <span class="th-sidebar" style="background:#1abc9c;"></span>
                <span class="th-body"></span>
              </div>
            </div>
            <div class="theme-name">تركوازي</div>
          </div>
        </div>
      </div>

      <button class="save-btn"><i class="fas fa-save"></i> حفظ الإعدادات</button>
    </div>
  </div>



  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const toggleBtn = document.getElementById('toggle-settings');
      const settingsPanel = document.getElementById('settings');
      const overlay = document.getElementById('settings-overlay');
      const saveBtn = document.querySelector('.save-btn');
      const themeRadios = document.querySelectorAll('input[name="setting-theme"]');
      const themeItems = document.querySelectorAll('.color-theme');
      const htmlElement = document.documentElement;

      // استرجاع الثيم من التخزين المحلي وتطبيقه
      const savedTheme = localStorage.getItem('theme') || 'theme-1';

      // إزالة جميع كلاسات الثيمات الحالية
      Array.from(htmlElement.classList).forEach(className => {
        if (className.startsWith('theme-')) {
          htmlElement.classList.remove(className);
        }
      });

      // إضافة الثيم المحفوظ أو الافتراضي
      htmlElement.classList.add(savedTheme);

      // تحديد الثيم النشط في الواجهة
      document.querySelectorAll('input[name="setting-theme"]').forEach(radio => {
        if (radio.value === savedTheme) {
          radio.checked = true;
          radio.closest('.color-theme').classList.add('active');
        } else {
          radio.closest('.color-theme').classList.remove('active');
        }
      });

      // تطبيق اللون الأولي على زر التبديل
      updateThemeColors();

      toggleBtn.addEventListener('click', () => {
        settingsPanel.classList.toggle('active');
        overlay.classList.toggle('active');
      });

      overlay.addEventListener('click', () => {
        settingsPanel.classList.remove('active');
        overlay.classList.remove('active');
      });

      themeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
          // إزالة جميع كلاسات الثيمات
          Array.from(htmlElement.classList).forEach(className => {
            if (className.startsWith('theme-')) {
              htmlElement.classList.remove(className);
            }
          });

          // إضافة الثيم الجديد
          htmlElement.classList.add(this.value);
          localStorage.setItem('theme', this.value);

          // تحديث الواجهة
          themeItems.forEach(item => item.classList.remove('active'));
          this.closest('.color-theme').classList.add('active');

          // تحديث الألوان الديناميكية
          updateThemeColors();
        });
      });

      saveBtn.addEventListener('click', function() {
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-check"></i> تم الحفظ بنجاح!';
        setTimeout(() => this.innerHTML = originalText, 2000);
        setTimeout(() => {
          settingsPanel.classList.remove('active');
          overlay.classList.remove('active');
        }, 1500);
      });

      function updateThemeColors() {
        // تحديث ألوان العناصر الديناميكية
        const primaryColor = getComputedStyle(htmlElement).getPropertyValue('--primary-color').trim();
        const sidebarColor = getComputedStyle(htmlElement).getPropertyValue('--sidebar-color').trim();

        toggleBtn.style.backgroundColor = primaryColor;
        toggleBtn.style.setProperty('--primary-color', primaryColor, 'important');
        toggleBtn.style.setProperty('--sidebar-color', sidebarColor, 'important');

        saveBtn.style.backgroundColor = primaryColor;
        saveBtn.style.setProperty('--primary-color', primaryColor, 'important');
        saveBtn.style.setProperty('--sidebar-color', sidebarColor, 'important');
      }
    });
  </script>
</body>

</html>