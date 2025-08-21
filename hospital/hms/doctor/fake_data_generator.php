<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مولد البيانات الوهمية - نظام إدارة المستشفى</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            max-width: 800px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .card-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            text-align: center;
            padding: 20px;
        }

        .btn-generate {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .feature-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
        }

        .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        #output {
            max-height: 500px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .loading {
            text-align: center;
            padding: 20px;
        }

        .spinner-border {
            color: #667eea;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="mb-0">🏥 مولد البيانات الوهمية</h1>
                <p class="mb-0 mt-2">نظام إدارة المستشفى</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="feature-box">
                            <div class="icon">👨‍⚕️</div>
                            <h5>أطباء يمنيون متخصصون</h5>
                            <p>25 طبيب جديد بأسماء يمنية أصيلة وتخصصات متنوعة</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-box">
                            <div class="icon">👥</div>
                            <h5>مرضى وهميون</h5>
                            <p>إنشاء ملفات مرضى كاملة مع التاريخ المرضي</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-box">
                            <div class="icon">📅</div>
                            <h5>مواعيد متنوعة</h5>
                            <p>جدولة مواعيد مستقبلية بأوقات مختلفة</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-box">
                            <div class="icon">🩺</div>
                            <h5>سجلات طبية</h5>
                            <p>إنشاء فحوصات ووصفات طبية واقعية</p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button class="btn btn-primary btn-generate" onclick="generateData()">
                        🚀 توليد البيانات الوهمية
                    </button>
                    <button class="btn btn-danger ms-3" onclick="clearData()">
                        🗑️ حذف البيانات الوهمية
                    </button>
                </div>

                <div id="output"></div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h5>📋 معلومات مهمة (البيانات اليمنية):</h5>
                <ul>
                    <li><strong>الأطباء:</strong> سيتم إضافة 25 طبيب جديد بأسماء يمنية أصيلة</li>
                    <li><strong>المرضى:</strong> سيتم إضافة 30 مريض جديد مع ملفاتهم الطبية</li>
                    <li><strong>المواعيد:</strong> سيتم إنشاء 40 موعد جديد</li>
                    <li><strong>السجلات الطبية:</strong> سيتم إضافة 50 سجل طبي</li>
                    <li><strong>المدن:</strong> مدن يمنية (صنعاء، عدن، تعز، حضرموت، إلخ)</li>
                    <li><strong>أرقام الهواتف:</strong> أرقام يمنية (77، 73، 70، 71، 78)</li>
                    <li><strong>الرسوم:</strong> بالريال اليمني (5,000 - 25,000 ريال)</li>
                    <li><strong>الأمراض:</strong> تشمل أمراض شائعة في اليمن (الملاريا، حمى الضنك)</li>
                    <li><strong>كلمة المرور الافتراضية:</strong> 123456 لجميع الحسابات</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function generateData() {
            const outputDiv = document.getElementById('output');
            const button = document.querySelector('.btn-generate');

            // إظهار حالة التحميل
            outputDiv.style.display = 'block';
            outputDiv.innerHTML = `
                <div class="loading">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-3">جاري توليد البيانات الوهمية... يرجى الانتظار</p>
                </div>
            `;

            button.disabled = true;
            button.innerHTML = '⏳ جاري التوليد...';

            // استدعاء ملف توليد البيانات
            fetch('test_faker.php')
                .then(response => response.text())
                .then(data => {
                    outputDiv.innerHTML = data;
                    button.disabled = false;
                    button.innerHTML = '🚀 توليد البيانات الوهمية';
                })
                .catch(error => {
                    outputDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5>❌ حدث خطأ!</h5>
                            <p>تعذر توليد البيانات. يرجى التحقق من:</p>
                            <ul>
                                <li>اتصال قاعدة البيانات</li>
                                <li>صلاحيات الملفات</li>
                                <li>تثبيت مكتبة Faker</li>
                            </ul>
                            <p><strong>تفاصيل الخطأ:</strong> ${error}</p>
                        </div>
                    `;
                    button.disabled = false;
                    button.innerHTML = '🚀 توليد البيانات الوهمية';
                });
        }

        function clearData() {
            if (confirm('هل أنت متأكد من حذف جميع البيانات الوهمية؟ هذا الإجراء لا يمكن التراجع عنه.')) {
                const outputDiv = document.getElementById('output');
                const button = document.querySelector('.btn-danger');

                outputDiv.style.display = 'block';
                outputDiv.innerHTML = `
                    <div class="loading">
                        <div class="spinner-border text-danger" role="status">
                            <span class="visually-hidden">جاري الحذف...</span>
                        </div>
                        <p class="mt-3">جاري حذف البيانات الوهمية... يرجى الانتظار</p>
                    </div>
                `;

                button.disabled = true;
                button.innerHTML = '⏳ جاري الحذف...';

                fetch('test_faker.php?clear=true')
                    .then(response => response.text())
                    .then(data => {
                        outputDiv.innerHTML = data;
                        button.disabled = false;
                        button.innerHTML = '🗑️ حذف البيانات الوهمية';
                    })
                    .catch(error => {
                        outputDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <h5>❌ حدث خطأ أثناء الحذف!</h5>
                                <p><strong>تفاصيل الخطأ:</strong> ${error}</p>
                            </div>
                        `;
                        button.disabled = false;
                        button.innerHTML = '🗑️ حذف البيانات الوهمية';
                    });
            }
        }
    </script>
</body>

</html>