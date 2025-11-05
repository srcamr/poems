# 🎭 موقع الشعر العربي - Arabic Poetry Platform

## 📝 الوصف | Description

منصة تفاعلية للعب بالشعر العربي حيث يتبادل المستخدم والنظام أبيات الشعر بناءً على القافية.

An interactive platform for playing Arabic poetry where users and the system exchange poetry verses based on rhyme.

---

## ✨ المميزات | Features

- 🎯 لعبة تبادل الأبيات الشعرية
- 📊 نظام نقاط تفاعلي
- 🔍 التحقق من صحة القافية
- 📝 إضافة أبيات جديدة
- 🛡️ لوحة تحكم إدارية
- 📱 تصميم متجاوب

---

## ⚙️ الإعداد | Setup

### 1. تثبيت المتطلبات | Install Dependencies

```bash
npm install
```

### 2. إعداد ملفات الإعدادات | Configuration Setup

#### أ. ملف إعدادات Frontend | Frontend Configuration

أنشئ ملف `peoms/src/config.js` وأضف:

Create `peoms/src/config.js` and add:

```javascript
// filepath: peoms/src/config.js
export const API_URL = 'path/to/public/connect.php/server'
```

#### ب. ملف إعدادات Backend | Backend Configuration

أنشئ ملف `public/config.php` وأضف:

Create `public/config.php` and add:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'userName');
define('DB_PASS', 'Password');
define('DB_NAME', 'DatabaseName');

// CORS Configuration
define('ALLOWED_ORIGIN', 'yourLinkToHost');
```

### 3. إعداد قاعدة البيانات | Database Setup

#### (1) استيراد قاعدة البيانات | Import Database

أضف `poemDB.sql` إلى قاعدة البيانات:

Add `poemDB.sql` to your Database

#### (2) إضافة كلمة مرور الأدمن | Add Admin Password

لا يمكن إنشاء كلمة مرور متوافقة مع `password_verify()` في PHP باستخدام دوال SQL مباشرة. يجب توليد Hash من PHP أولاً:

You cannot create a password compatible with PHP's `password_verify()` using SQL functions directly. You must generate the hash from PHP first:

**الخيار أ: استخدام مولد Hash عبر الإنترنت | Option A: Use Online Hash Generator**

1. اذهب إلى: https://bcrypt-generator.com/ أو https://phpfiddle.org/
2. أدخل كلمة المرور المطلوبة
3. انسخ الـ Hash الناتج (يبدأ بـ `$2y$10$...`)
4. استخدم هذا الأمر في SQL:

1. Go to: https://bcrypt-generator.com/ or https://phpfiddle.org/
2. Enter your desired password
3. Copy the generated hash (starts with `$2y$10$...`)
4. Use this SQL command:

```sql
INSERT INTO admin (ID, password) 
VALUES (1, '$2y$10$your_copied_hash_here');
```

**الخيار ب: توليد Hash محلياً | Option B: Generate Hash Locally**

1. أنشئ ملف `hash.php` مؤقت:
2. Create a temporary file `hash.php`:

```php
<?php
echo password_hash('YourPasswordHere', PASSWORD_DEFAULT);
?>
```

2. شغّل الملف عبر المتصفح أو عبر الـ terminal:
3. Run the file in browser or via terminal:

```bash
php hash.php
```

3. انسخ الـ Hash الناتج
4. Copy the generated hash

4. استخدم هذا الأمر في SQL:
5. Use this SQL command:

```sql
INSERT INTO admin (ID, password) 
VALUES (1, 'paste_the_generated_hash_here');
```

⚠️ **هام | Important**: 
- احذف ملف `hash.php` بعد الاستخدام لأسباب أمنية
- Delete `hash.php` file after use for security reasons
- لا تستخدم `SHA2()` أو `MD5()` - لن تعمل مع `password_verify()`
- Don't use `SHA2()` or `MD5()` - they won't work with `password_verify()`

⚠️ **هام | Important**: 
- تأكد من تغيير إعدادات قاعدة البيانات حسب بيئتك
- Make sure to change database settings according to your environment

### 4. تشغيل المشروع | Run the Project

- بداية شغل سيرفير قاعدة البيانات
- first run the sql server

```bash
npm run dev
```


---

## 🔧 التقنيات المستخدمة | Technologies Used

### Frontend
- ⚡ Vue.js 3
- 🎨 Tailwind CSS
- 📡 Axios
- 🔄 Vue Router

### Backend
- 🐘 PHP
- 🗄️ MySQL
- 🔒 PDO

## 📋 المتطلبات | Requirements

- Node.js (v14 أو أحدث | v14 or higher)
- PHP (v7.4 أو أحدث | v7.4 or higher)
- MySQL (v5.7 أو أحدث | v5.7 or higher)
- XAMPP أو بيئة خادم مشابهة | or similar server environment

---

## 📂 هيكل المشروع | Project Structure

```
poems/
├── peoms/                 # مشروع Vue.js
│   ├── src/
│   │   ├── components/
│   │   │   ├── Start.vue      # الصفحة الرئيسية
│   │   │   ├── add.vue        # إضافة أبيات
│   │   │   └── admin.vue      # لوحة التحكم
│   │   ├── config.js          # إعدادات API (مُستبعد من Git)
│   │   └── App.vue
│   ├── .gitignore
│   └── package.json
│
├── public/                # Backend PHP
│   ├── connect.php        # معالج API
│   ├── config.php         # إعدادات قاعدة البيانات (مُستبعد من Git)
│   └── index.html
│
└── README.md
```

---

## 🔐 ملاحظات الأمان | Security Notes

### ملفات مستبعدة من Git | Files Excluded from Git

- ✅ `peoms/src/config.js` - إعدادات Frontend
- ✅ `public/config.php` - إعدادات Backend وقاعدة البيانات
- ✅ `node_modules/` - مكتبات npm



---

## 📝 رخصة | License

هذا المشروع مفتوح المصدر ومتاح للاستخدام الشخصي والتعليمي.

This project is open source and available for personal and educational use.

---

## 👤 المطور | Developer

تم تطوير هذا المشروع بواسطة عمرو يوسف (srcamr)

Developed by amro yousef (srcamr)

---

## 📧 التواصل | Contact

لأي استفسارات أو مشاكل، يرجى فتح issue في المستودع.

For any inquiries or issues, please open an issue in the repository.

---
