<?php
include('db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error'] = 'يجب تسجيل الدخول للوصول لهذه الصفحة';
    header("Location: ../login.php");
    exit();
}

// Handle department actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_department'])) {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            // Validate input
            if (empty($name)) {
                throw new Exception('اسم القسم مطلوب');
            }
            
            // Check if department name already exists
            $check = $conn->prepare("SELECT id FROM departments WHERE name = ?");
            $check->bind_param("s", $name);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                throw new Exception('اسم القسم موجود مسبقاً');
            }
            
            $stmt = $conn->prepare("INSERT INTO departments (name, description, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $name, $description);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'تمت إضافة القسم بنجاح';
            } else {
                throw new Exception('حدث خطأ أثناء إضافة القسم');
            }
            $stmt->close();
            $check->close();
            
        } elseif (isset($_POST['update_department'])) {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name)) {
                throw new Exception('اسم القسم مطلوب');
            }
            
            // Check if department name already exists (excluding current department)
            $check = $conn->prepare("SELECT id FROM departments WHERE name = ? AND id != ?");
            $check->bind_param("si", $name, $id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                throw new Exception('اسم القسم موجود مسبقاً');
            }
            
            $stmt = $conn->prepare("UPDATE departments SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'تم تحديث القسم بنجاح';
            } else {
                throw new Exception('حدث خطأ أثناء تحديث القسم');
            }
            $stmt->close();
            $check->close();
            
        } elseif (isset($_POST['delete_department'])) {
            $id = (int)($_POST['id'] ?? 0);
            
            // Check if department has associated schedules
            $check = $conn->prepare("SELECT COUNT(*) as count FROM schedules WHERE department_id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $result = $check->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                throw new Exception('لا يمكن حذف القسم لأنه مرتبط بجداول دراسية');
            }
            
            $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'تم حذف القسم بنجاح';
            } else {
                throw new Exception('حدث خطأ أثناء حذف القسم');
            }
            $stmt->close();
            $check->close();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: departments.php");
    exit();
}

// Get all departments with error handling
try {
    $departments = $conn->query("SELECT * FROM departments ORDER BY name");
    if (!$departments) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ في جلب بيانات الأقسام: ' . $e->getMessage();
    $departments = [];
}

// Include header
$page_title = 'إدارة الأقسام';
$active_page = 'departments';
include('header.php');
?>

<div class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <h1>إدارة الأقسام</h1>
        </div>
        <div class="user-menu">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                <i class="fas fa-plus"></i> إضافة قسم جديد
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>اسم القسم</th>
                            <th>الوصف</th>
                            <th>عدد الطلاب</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($departments->num_rows > 0): ?>
                            <?php $i = 1; while($dept = $departments->fetch_assoc()): 
                                $student_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE department_id = {$dept['id']}")->fetch_assoc()['count'];
                            ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($dept['name']); ?></td>
                                    <td><?php echo htmlspecialchars($dept['description'] ?? 'لا يوجد وصف'); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $student_count; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-department" 
                                                data-id="<?php echo $dept['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($dept['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($dept['description'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i> تعديل
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-department" 
                                                data-id="<?php echo $dept['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($dept['name']); ?>">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">لا توجد أقسام مضافة بعد</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> <!-- End of main-content -->

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">إضافة قسم جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addDepartmentForm" method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">اسم القسم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required maxlength="100">
                        <div class="invalid-feedback">يرجى إدخال اسم القسم</div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">الوصف (اختياري)</label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="add_department" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentModalLabel">تعديل القسم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDepartmentForm" method="POST" action="">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="update_department" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">اسم القسم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required maxlength="100">
                        <div class="invalid-feedback">يرجى إدخال اسم القسم</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">الوصف (اختياري)</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteDepartmentModalLabel">تأكيد الحذف</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteDepartmentForm" method="POST" action="">
                <input type="hidden" name="id" id="delete_id">
                <input type="hidden" name="delete_department" value="1">
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف القسم "<span id="delete_department_name"></span>"؟</p>
                    <p class="text-warning">ملاحظة: لا يمكن حذف القسم إذا كان به جداول دراسية مرتبطة.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">حذف</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    function validateForm(form) {
        const nameInput = form.querySelector('input[name="name"]');
        const name = nameInput.value.trim();
        
        if (!name) {
            nameInput.classList.add('is-invalid');
            nameInput.focus();
            return false;
        }
        
        nameInput.classList.remove('is-invalid');
        return true;
    }
    
    // Add department form validation
    document.getElementById('addDepartmentForm').addEventListener('submit', function(e) {
        if (!validateForm(this)) {
            e.preventDefault();
        }
    });
    
    // Edit department form validation
    document.getElementById('editDepartmentForm').addEventListener('submit', function(e) {
        if (!validateForm(this)) {
            e.preventDefault();
        }
    });
    
    // Clear validation on input
    document.querySelectorAll('input[name="name"]').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
    
    // Handle edit department button click
    document.querySelectorAll('.edit-department').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description;
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            
            // Clear any previous validation errors
            document.getElementById('edit_name').classList.remove('is-invalid');
            
            const editModal = new bootstrap.Modal(document.getElementById('editDepartmentModal'));
            editModal.show();
        });
    });

    // Handle delete department button click
    document.querySelectorAll('.delete-department').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_department_name').textContent = name;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteDepartmentModal'));
            deleteModal.show();
        });
    });
});
</script>
