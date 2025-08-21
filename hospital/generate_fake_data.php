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
            max-width: 900px;
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

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .feature-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="mb-0">🏥 مولد البيانات الوهمية</h1>
                <p class="mb-0 mt-2">نظام إدارة المستشفى اليمني المتكامل</p>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h5>📋 نظرة عامة</h5>
                    <p>هذا المولد سيقوم بإضافة بيانات وهمية واقعية إلى نظام إدارة المستشفى لتسهيل عملية الاختبار والعرض التوضيحي.</p>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">👨‍⚕️</div>
                        <h5>أطباء يمنيون متخصصون</h5>
                        <p>20 طبيب جديد بتخصصات مختلفة مع بيانات يمنية كاملة</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">👥</div>
                        <h5>مرضى يمنيون</h5>
                        <p>30 مريض جديد بأسماء يمنية وملفات طبية شاملة</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📅</div>
                        <h5>مواعيد مجدولة</h5>
                        <p>40 موعد مستقبلي بأوقات وحالات مختلفة</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🩺</div>
                        <h5>سجلات طبية شاملة</h5>
                        <p>50 سجل طبي مع فحوصات ووصفات يمنية</p>
                    </div>
                </div>

                <div class="text-center">
                    <a href="hms/doctor/fake_data_generator.php" class="btn btn-primary btn-lg">
                        🚀 بدء توليد البيانات
                    </a>
                </div>

                <div class="mt-4">
                    <h5>🔐 معلومات تسجيل الدخول:</h5>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">👨‍⚕️</div>
                            <div>أطباء</div>
                            <small>doctor[20-39]@yemen-hospital.ye</small>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">👥</div>
                            <div>مرضى</div>
                            <small>patient[50-74]@yemen.ye</small>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">⚙️</div>
                            <div>إدارة</div>
                            <small>admin / 123456</small>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">🔑</div>
                            <div>كلمة المرور</div>
                            <small>123456</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h5>📝 التخصصات الطبية المتاحة:</h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul>
                            <li>العظام (Orthopedics)</li>
                            <li>الباطنة (Internal Medicine)</li>
                            <li>النساء والولادة (Obstetrics and Gynecology)</li>
                            <li>الجلدية (Dermatology)</li>
                            <li>الأطفال (Pediatrics)</li>
                            <li>الأشعة (Radiology)</li>
                            <li>الجراحة العامة (General Surgery)</li>
                            <li>العيون (Ophthalmology)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul>
                            <li>التخدير (Anesthesia)</li>
                            <li>الأنف والأذن والحنجرة (ENT)</li>
                            <li>الغدد الصماء (Endocrinologists)</li>
                            <li>القلب (Cardiology)</li>
                            <li>الأعصاب (Neurology)</li>
                            <li>الطب النفسي (Psychiatry)</li>
                            <li>المسالك البولية (Urology)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h5>⚠️ ملاحظات مهمة:</h5>
                <ul>
                    <li>تأكد من تشغيل خادم قاعدة البيانات قبل البدء</li>
                    <li>سيتم إضافة البيانات إلى قاعدة البيانات الحالية دون حذف البيانات الموجودة</li>
                    <li>يمكن تشغيل المولد عدة مرات لإضافة المزيد من البيانات</li>
                    <li>جميع كلمات المرور مشفرة باستخدام MD5</li>
                    <li>البيانات المولدة باللغة العربية لتناسب النظام</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>