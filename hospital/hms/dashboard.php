<?php
session_start();
error_reporting(0);
include('include/config.php');
if(strlen($_SESSION['id']==0)) {
 header('location:logout.php');
  } else{

if(isset($_POST['submit']))
{
$doctorspecilization=$_POST['doctorspecilization'];
$sql=mysqli_query($con,"insert into doctorSpecilization(specilization) values('$doctorspecilization')");
$_SESSION['msg']="تمت إضافة تخصص الطبيب بنجاح!!";
}
//Code Deletion
if(isset($_GET['del']))
{
$sid=$_GET['id'];	
mysqli_query($con,"delete from doctorSpecilization where id = '$sid'");
$_SESSION['msg']="تم حذف البيانات !!";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>المسؤول | تخصص الطبيب</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2c3e50;
            --secondary: #1a2530;
            --success: #27ae60;
            --info: #2980b9;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f0f5f9;
            color: #333;
            padding-top: 60px;
        }
        
        /* Header styling */
        .app-header {
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            color: white;
            padding: 25px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .app-header h1 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 0;
        }
        
        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: white;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.5);
            content: ">";
        }
        
        /* Main content */
        .main-content-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        /* Form styling */
        .modern-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .form-header {
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .form-header i {
            font-size: 1.8rem;
            color: var(--primary);
            margin-left: 15px;
        }
        
        .form-header h5 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            color: var(--secondary);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--secondary);
        }
        
        .form-control {
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            padding: 12px 15px;
            height: auto;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        /* Alert messages */
        .alert-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background-color: rgba(39, 174, 96, 0.15);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .alert-message i {
            margin-left: 10px;
            font-size: 1.2rem;
        }
        
        /* Table styling */
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }
        
        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h5 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            color: var(--secondary);
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            padding: 15px;
            text-align: right;
        }
        
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            text-align: right;
        }
        
        .table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .action-btns {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background-color: rgba(39, 174, 96, 0.15);
            color: var(--success);
        }
        
        .btn-edit:hover {
            background-color: var(--success);
            color: white;
        }
        
        .btn-delete {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger);
        }
        
        .btn-delete:hover {
            background-color: var(--danger);
            color: white;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .app-header h1 {
                font-size: 1.5rem;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .action-btns {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div id="app">        
        <?php include('include/sidebar.php');?>
        <div class="app-content">
            <?php include('include/header.php');?>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="wrap-content container" id="container">
                    <!-- Page Title -->
                    <section id="page-title">
                        <div class="app-header">
                            <div class="row">
                                <div class="col-sm-8">
                                    <h1 class="mainTitle">المسؤول | تخصصات الأطباء</h1>
                                </div>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="#">المسؤول</a>
                                    </li>
                                    <li class="breadcrumb-item active">
                                        <span>تخصصات الأطباء</span>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Main Content Area -->
                    <div class="main-content-container">
                        <?php if (isset($_SESSION['msg'])): ?>
                            <div class="alert-message alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlentities($_SESSION['msg - dashboard.php:330']); ?>
                            </div>
                            <?php unset($_SESSION['msg']); ?>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="modern-form">
                                    <div class="form-header">
                                        <i class="fas fa-plus-circle"></i>
                                        <h5>إضافة تخصص جديد</h5>
                                    </div>
                                    <form role="form" name="dcotorspcl" method="post">
                                        <div class="form-group">
                                            <label class="form-label">إضافة تخصص</label>
                                            <input type="text" name="doctorspecilization" class="form-control" placeholder="ادخل اسم التخصص" required>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>حفظ
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <div class="text-center">
                                        <div class="mb-4">
                                            <i class="fas fa-stethoscope text-primary" style="font-size: 5rem;"></i>
                                        </div>
                                        <h4 class="mb-3">إدارة تخصصات الأطباء</h4>
                                        <p class="text-muted">
                                            يمكنك من خلال هذه الصفحة إضافة وتعديل وحذف التخصصات الطبية المختلفة التي يقدمها الأطباء في النظام.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-container mt-5">
                            <div class="table-header">
                                <h5>قائمة التخصصات الطبية</h5>
                                <div class="d-flex align-items-center">
                                    <div class="input-group" style="max-width: 300px;">
                                        <input type="text" class="form-control" placeholder="بحث في التخصصات...">
                                        <button class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="center">#</th>
                                            <th>التخصص</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>تاريخ التعديل</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql=mysqli_query($con,"select * from doctorSpecilization");
                                        $cnt=1;
                                        while($row=mysqli_fetch_array($sql))
                                        {
                                        ?>
                                        <tr>
                                            <td class="center - dashboard.php:401"><?php echo $cnt;?>.</td>
                                            <td class="fwbold - dashboard.php:402"><?php echo $row['specilization'];?></td>
                                            <td><?php echo $row['creationDate - dashboard.php:403'];?></td>
                                            <td><?php echo $row['updationDate - dashboard.php:404'];?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="editdoctorspecialization.php?id=<?php echo $row['id'];?> - dashboard.php:407" class="btn-action btn-edit" title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="doctorspecilization.php?id=<?php echo $row['id']?>&del=delete - dashboard.php:410" onClick="return confirm('هل أنت متأكد أنك تريد الحذف؟')" class="btn-action btn-delete" title="حذف">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php 
                                        $cnt=$cnt+1;
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                                <div>عرض 1 إلى <?php echo $cnt-1; ?> من <?php echo $cnt-1; ?> إدخالات</div>
                                <nav>
                                    <ul class="pagination">
                                        <li class="page-item disabled"><a class="page-link" href="#">السابق</a></li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item"><a class="page-link" href="#">التالي</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <?php include('include/footer.php');?>
        
        <!-- Settings -->
        <?php include('include/setting.php');?>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple form validation
        $(document).ready(function() {
            $('form[name="dcotorspcl"]').submit(function(e) {
                var specialization = $('input[name="doctorspecilization"]').val();
                
                if(specialization.trim() === '') {
                    e.preventDefault();
                    alert('يرجى إدخال اسم التخصص');
                    $('input[name="doctorspecilization"]').focus();
                }
            });
            
            // Animation for form elements
            $('.modern-form .form-control').each(function(index) {
                $(this).css({
                    'opacity': '0',
                    'transform': 'translateX(20px)'
                }).delay(100 * index).animate({
                    'opacity': '1',
                    'transform': 'translateX(0)'
                }, 500);
            });
        });
    </script>
</body>
</html>
<?php } ?>