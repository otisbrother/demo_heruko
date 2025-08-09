<?php
// ✅ Laravel Breeze mặc định xử lý auth (đặt ở đầu để tránh xung đột)
require __DIR__.'/auth.php';

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RoleLoginController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\HealthInsuranceController;
use App\Http\Controllers\RelativeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\TypePatientController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\DetailPrescriptionController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\DetailMedicalRecordController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\HospitalizedController;
use App\Http\Controllers\DischargeController;
use App\Http\Controllers\AdminController; 
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\PatientAuthController;
use App\Http\Controllers\InsuranceApplicationController;

Route::get('/', function () {
    // return view('welcome');
    return 'Hello railway';
});

// Route test để debug admin login
Route::get('/debug-admin', function() {
    if (Auth::check()) {
        return 'Đã đăng nhập: ' . Auth::user()->name . ' - Role: ' . Auth::user()->role;
    } else {
        return 'Chưa đăng nhập';
    }
})->name('debug.admin');

// Route test để debug admin login redirect
Route::get('/debug-admin-redirect', function() {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard.simple');
        } else {
            return 'Không phải admin - Role: ' . $user->role;
        }
    } else {
        return 'Chưa đăng nhập';
    }
})->name('debug.admin.redirect');

// Route test để debug session
Route::get('/debug-session', function() {
    $sessionData = [
        'session_id' => session()->getId(),
        'auth_check' => Auth::check(),
        'user_id' => Auth::id(),
        'user_name' => Auth::user()->name ?? 'N/A',
        'user_role' => Auth::user()->role ?? 'N/A',
        'session_data' => session()->all()
    ];
    return response()->json($sessionData);
})->name('debug.session');

// ✅ Route dashboard cho admin (để tránh xung đột)
Route::get('/admin-dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'role:admin'])->name('admin.main.dashboard');

// Route test để debug redirect
Route::get('/test-redirect', function() {
    return 'Test redirect route - Current URL: ' . request()->url();
})->name('test.redirect');

// ✅ Các route liên quan đến hồ sơ cá nhân (breeze mặc định)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ✅ Route phân quyền theo vai trò

// === ADMIN ===
// Admin login routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', function () {
        return view('auth.login', ['role' => 'admin']);
    })->name('login');
    
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->role === 'admin') {
                $request->session()->regenerate();
                // Debug: Log thông tin user
                \Log::info('Admin login successful - User: ' . $user->name . ' Role: ' . $user->role);
                return redirect('/admin/dashboard-simple');
            } else {
                Auth::logout();
                \Log::warning('Admin login failed - User: ' . $user->name . ' Role: ' . $user->role);
                return back()->withErrors([
                    'email' => 'Tài khoản này không có quyền admin.',
                ]);
            }
        }

        \Log::warning('Admin login failed - Invalid credentials');
        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ]);
    })->name('login.post');
});

// Admin protected routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');
     Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
     
     // Route test để debug
     Route::get('/test', function() {
         return 'Admin test route working! User: ' . Auth::user()->name . ' Role: ' . Auth::user()->role;
     })->name('test');
     
     // Route dashboard đơn giản để test
     Route::get('/dashboard-simple', function() {
         return 'Admin dashboard simple - User: ' . Auth::user()->name . ' Role: ' . Auth::user()->role;
     })->name('dashboard.simple');

    // ✅ Route logout cho admin
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    })->name('logout');

    // ✅ Route profile cho admin
    Route::get('/profile', function () {
        return view('admin.profile', ['user' => Auth::user()]);
    })->name('profile.edit');

    // ✅ Route quản lý bệnh nhân (CRUD + search)
    Route::resource('patients', PatientController::class);  

      // ✅ Route quản lý thẻ bảo hiểm y tế
    Route::resource('insurances', HealthInsuranceController::class);
    
    // ✅ Route duyệt hồ sơ BHYT
    Route::prefix('insurances')->name('insurances.')->group(function () {
        Route::get('/application/{id}', [HealthInsuranceController::class, 'showApplication'])->name('application.show');
        Route::post('/application/{id}/approve', [HealthInsuranceController::class, 'approveApplication'])->name('application.approve');
        Route::post('/application/{id}/reject', [HealthInsuranceController::class, 'rejectApplication'])->name('application.reject');
    });
    
    // ✅ Route quản lý hồ sơ đăng ký BHYT
    Route::prefix('insurance-applications')->name('insurance-applications.')->group(function () {
        Route::get('/', [InsuranceApplicationController::class, 'adminIndex'])->name('index');
        Route::get('/{id}', [InsuranceApplicationController::class, 'adminShow'])->name('show');
        Route::post('/{id}/approve', [InsuranceApplicationController::class, 'adminApprove'])->name('approve');
        Route::post('/{id}/reject', [InsuranceApplicationController::class, 'adminReject'])->name('reject');
    });

    // 🔔 API thông báo cho admin dashboard
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [AdminController::class, 'getNewAppointmentNotifications'])->name('appointments');
        Route::get('/insurance', [AdminController::class, 'getInsuranceNotifications'])->name('insurance');
        Route::get('/count', [AdminController::class, 'getNotificationCount'])->name('count');
        Route::post('/mark-read', [AdminController::class, 'markNotificationAsRead'])->name('mark-read');
        Route::post('/insurance/mark-read', [AdminController::class, 'markInsuranceNotificationAsRead'])->name('insurance.mark-read');
    });

     // ✅ Route quản lý thân nhân
    Route::prefix('relatives')->name('relatives.')->group(function () {
    Route::get('/', [RelativeController::class, 'index'])->name('index');
    Route::get('/create', [RelativeController::class, 'create'])->name('create');
    Route::post('/', [RelativeController::class, 'store'])->name('store');

    // ⚠️ Các route dưới cần truyền đủ 2 tham số: patient_id + name
    Route::get('/{patient_id}/{name}', [RelativeController::class, 'show'])->name('show');
    Route::get('/{patient_id}/{name}/edit', [RelativeController::class, 'edit'])->name('edit');
    Route::put('/{patient_id}/{name}', [RelativeController::class, 'update'])->name('update');
    Route::delete('/{patient_id}/{name}', [RelativeController::class, 'destroy'])->name('destroy');
});
    
     // ✅ Route quản lý khoa
    Route::resource('departments',DepartmentController::class);

     // ✅ Route quản lý loại bệnh nhân
    Route::resource('type_patients', TypePatientController::class);

    // ✅ Route quản lý bác sĩ
    Route::resource('doctors', DoctorController::class);

     // ✅ Route quản lý thuốc
    Route::resource('medicines', MedicineController::class);
    
       // ✅ Route quản lý đơn thuốc
    Route::resource('prescriptions', PrescriptionController::class);


        // ✅ Route quản lý chi tiết đơn thuốc (khóa chính kép)
    Route::prefix('detail-prescriptions')->name('detail-prescriptions.')->group(function () {
        Route::get('/', [DetailPrescriptionController::class, 'index'])->name('index');
        Route::get('/create', [DetailPrescriptionController::class, 'create'])->name('create');
        Route::post('/', [DetailPrescriptionController::class, 'store'])->name('store');
        Route::get('/{prescription_id}/{medicine_id}/edit', [DetailPrescriptionController::class, 'edit'])->name('edit');
        Route::put('/{prescription_id}/{medicine_id}', [DetailPrescriptionController::class, 'update'])->name('update');
        Route::delete('/{prescription_id}/{medicine_id}', [DetailPrescriptionController::class, 'destroy'])->name('destroy');
    });
        // ✅ Route quản lý sổ khám bệnh (khóa chính kép)
        Route::prefix('medical-records')->name('medical-records.')->group(function () {
            Route::get('/', [MedicalRecordController::class, 'index'])->name('index');
            Route::get('/create', [MedicalRecordController::class, 'create'])->name('create');
            Route::post('/', [MedicalRecordController::class, 'store'])->name('store');
            Route::get('/{id}/{order}', [MedicalRecordController::class, 'show'])->name('show');
            Route::get('/{id}/{order}/edit', [MedicalRecordController::class, 'edit'])->name('edit');
            Route::put('/{id}/{order}', [MedicalRecordController::class, 'update'])->name('update');
            Route::delete('/{id}/{order}', [MedicalRecordController::class, 'destroy'])->name('destroy');
        });
      // ✅ Route quản lý chi tiết sổ khám bệnh

    Route::prefix('detail-medicalrecords')->name('detail-medicalrecords.')->group(function () {
    Route::get('/', [DetailMedicalRecordController::class, 'index'])->name('index');
    Route::get('/create', [DetailMedicalRecordController::class, 'create'])->name('create');
    Route::post('/', [DetailMedicalRecordController::class, 'store'])->name('store');
    Route::get('/{medical_record_id}/{patient_id}/{exam_date}/edit', [DetailMedicalRecordController::class, 'edit'])->name('edit');
    Route::put('/{medical_record_id}/{patient_id}/{exam_date}', [DetailMedicalRecordController::class, 'update'])->name('update');
    Route::delete('/{medical_record_id}/{patient_id}/{exam_date}', [DetailMedicalRecordController::class, 'destroy'])->name('destroy');
    

     

});
    // ✅ Route quản lý hóa đơn viện phí

   Route::resource('bills', BillController::class);


// ✅ Route quản lý nhập viện
   
Route::resource('hospitalized', HospitalizedController::class)
    ->parameters(['hospitalized' => 'patient_id'])
    ->except(['show']);

Route::get('hospitalized/{patient_id}/{room}/{bed}/edit', [HospitalizedController::class, 'edit'])->name('hospitalized.edit');
Route::put('hospitalized/{patient_id}/{room}/{bed}', [HospitalizedController::class, 'update'])->name('hospitalized.update');
Route::delete('hospitalized/{patient_id}/{room}/{bed}', [HospitalizedController::class, 'destroy'])->name('hospitalized.destroy');


// ✅ Route quản lý xuất viện
Route::prefix('discharges')->name('discharges.')->group(function () {
    Route::get('/', [DischargeController::class, 'index'])->name('index');
    Route::get('/create', [DischargeController::class, 'create'])->name('create');
    Route::post('/', [DischargeController::class, 'store'])->name('store');

    // ✅ Route sửa xuất viện
    Route::get('/{patient_id}/{discharge_date}/edit', [DischargeController::class, 'edit'])->name('edit');
    Route::put('/{patient_id}/{discharge_date}', [DischargeController::class, 'update'])->name('update');

    // ✅ Route xóa xuất viện
    Route::delete('/{patient_id}/{discharge_date}', [DischargeController::class, 'destroy'])->name('destroy');
});

    // ✅ Route quản lý lịch hẹn
    Route::resource('appointments', AppointmentController::class);

    // ✅ Route cập nhật trạng thái lịch hẹn
    Route::post('appointments/{id}/update-status', [AppointmentController::class, 'updateStatus'])
        ->name('appointments.updateStatus');

    // 🔔 Routes cho thông báo lịch hẹn mới
    Route::get('/notifications', [AdminController::class, 'getNewAppointmentNotifications'])
        ->name('notifications.get');
    Route::get('/notifications/count', [AdminController::class, 'getNotificationCount'])
        ->name('notifications.count');
    Route::post('/notifications/mark-read', [AdminController::class, 'markNotificationAsRead'])
        ->name('notifications.markRead');

});

// === DOCTOR ===
// Route này đã được định nghĩa trong group doctors/ ở dưới


// === PATIENT ===
// ===== PATIENT ROUTES (Protected with session check) =====
Route::group([], function () {
    Route::get('/patient/home', function () {
        // Kiểm tra session timeout
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút = 600 giây
            
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            
            // Cập nhật thời gian hoạt động cuối
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return view('patients.home');
    })->name('patient.home');

    Route::get('/patient/doctor', function () {
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return view('patients.doctor');
    })->name('patients.doctor');

    Route::get('/department', function () {
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return view('patients.department');
    })->name('patients.department');

    // ✅ Route đặt lịch hẹn cho bệnh nhân
    Route::get('/patient/appointment/create', function (Request $request) {
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(AppointmentController::class)->patientCreate($request);
    })->name('patient.appointment.create');
    
    Route::post('/patient/appointment/store', function (Request $request) {
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(AppointmentController::class)->patientStore($request);
    })->name('patient.appointment.store');

    // ✅ Routes mới cho các chức năng bệnh nhân
    Route::get('/patient/appointments', function() {
        // Debug: Kiểm tra session
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        
        // Debug: Kiểm tra session timeout
        $lastActivity = session('patient_last_activity');
        $timeout = 10 * 60; // 10 phút
        
        if (!$lastActivity || (time() - $lastActivity) > $timeout) {
            session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
            return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
        }
        
        // Cập nhật thời gian hoạt động
        session(['patient_last_activity' => time()]);
        
        return app(\App\Http\Controllers\PatientViewController::class)->appointments();
    })->name('patients.appointments');
    
    Route::get('/patient/medical-records', function() {
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->medicalRecords();
    })->name('patients.medical-records');
    
    Route::get('/patient/prescriptions', function() {
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->prescriptions();
    })->name('patients.prescriptions');
    
    // Route đặt thuốc
    Route::post('/patient/order-medicine', function(Request $request) {
        Log::info('Route đặt thuốc được gọi với data: ' . json_encode($request->all()));
        
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->orderMedicine($request);
    })->name('patient.order.medicine');
    
    // Route thanh toán hóa đơn
    Route::post('/patient/pay-bill', function(Request $request) {
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->payBill($request);
    })->name('patient.pay.bill');
    
    // Route xóa hóa đơn
    Route::delete('/patient/delete-bill', function(Request $request) {
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->deleteBill($request);
    })->name('patient.delete.bill');
    
    Route::get('/patient/hospitalization', function() {
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->hospitalization();
    })->name('patients.hospitalization');
    
    Route::get('/patient/relatives', function() {
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->relatives();
    })->name('patients.relatives');
    
     Route::get('/patient/relatives/create', [App\Http\Controllers\PatientViewController::class, 'createRelative'])->name('patients.relatives.create');
    Route::post('/patient/relatives/store', [App\Http\Controllers\PatientViewController::class, 'storeRelative'])->name('patients.relatives.store');

    Route::get('/patient/bills', function() {
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->bills();
    })->name('patients.bills');
    
    Route::get('/patient/account', function() {
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->account();
    })->name('patients.account');
    
    Route::put('/patient/account', function(Request $request) {
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        
        return app(\App\Http\Controllers\PatientViewController::class)->updateAccount($request);
    })->name('patients.account.update');
    
    // ✅ Routes cho đăng ký BHYT
    Route::get('/patient/insurance/test', function() {
        return app(\App\Http\Controllers\InsuranceApplicationController::class)->test();
    })->name('insurance-applications.test');
    
    Route::get('/patient/insurance/create', function() {
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\InsuranceApplicationController::class)->create();
    })->name('insurance-applications.create');
    
    Route::post('/patient/insurance/store', function(Request $request) {
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\InsuranceApplicationController::class)->store($request);
    })->name('insurance-applications.store');
    
    Route::get('/patient/insurance/status', function() {
        if (!session('patient_id')) {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\InsuranceApplicationController::class)->status();
    })->name('insurance-applications.status');
    
    Route::post('/patient/account/change-password', function(Request $request) {
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->changePassword($request);
    })->name('patients.account.change-password');
    
    Route::get('/patient/machines', function() {
        if (session('patient_id')) {
            $lastActivity = session('patient_last_activity');
            $timeout = 10 * 60; // 10 phút
            if (!$lastActivity || (time() - $lastActivity) > $timeout) {
                session()->forget(['patient_id', 'patient_name', 'patient_email', 'patient_last_activity']);
                return redirect()->route('patient.login')->with('error', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            }
            session(['patient_last_activity' => time()]);
        } else {
            return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
        }
        return app(\App\Http\Controllers\PatientViewController::class)->machines();
    })->name('patients.machines');
});


// ===== PATIENT HOME (Protected with session check) =====
Route::get('/patients/home', function () {
    if (!session('patient_id')) {
        return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
    }
    return view('patients.home');
})->name('patients.home');

// Route patients.room trỏ vào trang thông tin phòng
Route::get('/patients/room', function () {
    if (!session('patient_id')) {
        return redirect()->route('patient.login')->with('error', 'Vui lòng đăng nhập để truy cập.');
    }
    
    return view('patients.room');
})->name('patients.room');

// Route xử lý bệnh nhân chọn phòng và tạo nhập viện
Route::post('/patients/room/select', function (Request $request) {
    if (!session('patient_id')) {
        return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để truy cập.']);
    }

    $request->validate([
        'room_code' => 'required|string|max:10',
        'bed_number' => 'required|integer|min:1',
    ]);

    try {
        // Kiểm tra xem bệnh nhân đã nhập viện chưa
        $existingHospitalization = \App\Models\Hospitalized::where('patient_id', session('patient_id'))
            ->whereNull('discharge_date')
            ->first();

        if ($existingHospitalization) {
            return response()->json([
                'success' => false, 
                'message' => 'Bạn đã nhập viện tại phòng ' . $existingHospitalization->room . ' giường ' . $existingHospitalization->bed . '.'
            ]);
        }

        // Kiểm tra xem phòng và giường đã được sử dụng chưa
        $occupiedBed = \App\Models\Hospitalized::where('room', $request->room_code)
            ->where('bed', $request->bed_number)
            ->whereNull('discharge_date')
            ->first();

        if ($occupiedBed) {
            return response()->json([
                'success' => false, 
                'message' => 'Phòng ' . $request->room_code . ' giường ' . $request->bed_number . ' đã được sử dụng.'
            ]);
        }

        // Tạo bản ghi nhập viện
        \App\Models\Hospitalized::create([
            'patient_id' => session('patient_id'),
            'admission_date' => now(),
            'room' => $request->room_code,
            'bed' => $request->bed_number,
            'reason' => 'Bệnh nhân tự chọn phòng',
            'diagnosis' => 'Chờ khám và chẩn đoán',
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Đăng ký nhập viện thành công! Bạn đã được phân phòng ' . $request->room_code . ' giường ' . $request->bed_number . '.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ]);
    }
})->name('patients.room.select');



// ✅ Giao diện chọn vai trò khi đăng nhập
Route::get('/select-role', function () {
    return view('auth.select-role'); // resources/views/auth/select-role.blade.php
})->name('select.role');

// ✅ Route login theo vai trò (ví dụ: /login/admin)
Route::get('/login/{role}', function ($role) {
    if (!in_array($role, ['admin', 'doctor', 'patient'])) {
        abort(404);
    }
    
    // Nếu là patient, chuyển hướng đến trang login riêng của patient
    if ($role === 'patient') {
        return redirect()->route('patient.login');
    }
    
    // Nếu là doctor, chuyển hướng đến trang login riêng của doctor
    if ($role === 'doctor') {
        return redirect()->route('doctor.login');
    }
    
    // Nếu là admin, redirect đến trang login admin
    if ($role === 'admin') {
        return redirect()->route('admin.login');
    }
    
    abort(404);
})->name('login.role');




// ===== PATIENT AUTHENTICATION ROUTES =====
Route::prefix('patient')->name('patient.')->group(function () {
    // Show login form
    Route::get('/login', [PatientAuthController::class, 'showLoginForm'])
        ->name('login');
    
    // Process login
    Route::post('/login', [PatientAuthController::class, 'login']);
    
    // Show register form
    Route::get('/register', [PatientAuthController::class, 'showRegisterForm'])->name('register');
    
    // Process registration
    Route::post('/register', [PatientAuthController::class, 'register']);
    
    // Logout
    Route::post('/logout', [PatientAuthController::class, 'logout'])
        ->name('logout');
});



// ✅ Route khác để chọn role
Route::get('/choose-role', fn() => view('auth.select-role'))->name('choose.role');

// ========================================
// 👨‍⚕️ DOCTOR AUTHENTICATION ROUTES
// ========================================

Route::prefix('doctor')->name('doctor.')->group(function () {
    // Doctor login routes
    Route::get('/login', [\App\Http\Controllers\Auth\DoctorAuthController::class, 'create'])
        ->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\DoctorAuthController::class, 'store']);
    
    // Doctor registration routes
    Route::get('/register', [\App\Http\Controllers\Auth\DoctorAuthController::class, 'createRegister'])
        ->name('register');
    
    Route::post('/register', [\App\Http\Controllers\Auth\DoctorAuthController::class, 'storeRegister'])
        ->name('register.store');
    
    Route::post('/logout', [\App\Http\Controllers\Auth\DoctorAuthController::class, 'destroy'])
        ->middleware('auth:doctor')->name('logout');
});

// ========================================
// 👨‍⚕️ DOCTOR ROUTES - Tất cả tính năng dành cho bác sĩ (Protected)
// ========================================

        Route::prefix('doctors')->name('doctors.')->middleware('auth:doctor')->group(function () {
    // Dashboard chính
    Route::get('/dashboard', [\App\Http\Controllers\DoctorViewController::class, 'dashboard'])->name('dashboard');
    
    // Route test đơn giản
    Route::get('/test', function() {
        return 'Doctor test route working!';
    })->name('test');
    
    // Quản lý lịch khám
    Route::get('/appointments', [\App\Http\Controllers\DoctorViewController::class, 'appointments'])->name('appointments');
    
    // Khám bệnh và ghi hồ sơ
    Route::get('/exam/create/{appointmentId?}', [\App\Http\Controllers\DoctorViewController::class, 'examCreate'])->name('exam.create');
    Route::post('/exam/store', [\App\Http\Controllers\DoctorViewController::class, 'examStore'])->name('exam.store');
    
    // Kê đơn thuốc
    Route::get('/prescription/create/{medicalRecordId?}', [\App\Http\Controllers\DoctorViewController::class, 'prescriptionCreate'])->name('prescription.create');
    Route::post('/prescription/store', [\App\Http\Controllers\DoctorViewController::class, 'prescriptionStore'])->name('prescription.store');
    Route::get('/prescription/create-from-exam/{medicalRecordId}', [\App\Http\Controllers\DoctorViewController::class, 'prescriptionCreate'])->name('exam.prescription');
    
    // Quản lý hồ sơ y tế bệnh nhân
    Route::get('/medical-records', [\App\Http\Controllers\DoctorViewController::class, 'medicalRecords'])->name('medical-records');
    Route::get('/medical-record/{medicalRecordId}', [\App\Http\Controllers\DoctorViewController::class, 'viewMedicalRecord'])->name('medical-record.view');
    Route::get('/medical-record/{medicalRecordId}/edit', [\App\Http\Controllers\DoctorViewController::class, 'editMedicalRecord'])->name('medical-record.edit');
    Route::put('/medical-record/{medicalRecordId}', [\App\Http\Controllers\DoctorViewController::class, 'updateMedicalRecord'])->name('medical-record.update');
    
    // Quản lý đơn thuốc của bác sĩ
    Route::get('/prescriptions', [\App\Http\Controllers\DoctorViewController::class, 'prescriptions'])->name('prescriptions');
    Route::get('/prescription/{prescriptionId}', [\App\Http\Controllers\DoctorViewController::class, 'viewPrescription'])->name('prescription.view');
    Route::get('/prescription/{prescriptionId}/edit', [\App\Http\Controllers\DoctorViewController::class, 'editPrescription'])->name('prescription.edit');
    Route::put('/prescription/{prescriptionId}', [\App\Http\Controllers\DoctorViewController::class, 'updatePrescription'])->name('prescription.update');
    
    // Thống kê
    Route::get('/statistics', [\App\Http\Controllers\DoctorViewController::class, 'statistics'])->name('statistics');
    
    // Quản lý nhập viện
    Route::get('/hospitalized', [\App\Http\Controllers\DoctorViewController::class, 'hospitalized'])->name('hospitalized.index');
    Route::get('/hospitalized/create', [\App\Http\Controllers\DoctorViewController::class, 'hospitalizedCreate'])->name('hospitalized.create');
    Route::post('/hospitalized', [\App\Http\Controllers\DoctorViewController::class, 'hospitalizedStore'])->name('hospitalized.store');
    Route::get('/hospitalized/{patient_id}/{room}/{bed}/edit', [\App\Http\Controllers\DoctorViewController::class, 'hospitalizedEdit'])->name('hospitalized.edit');
    Route::put('/hospitalized/{patient_id}/{room}/{bed}', [\App\Http\Controllers\DoctorViewController::class, 'hospitalizedUpdate'])->name('hospitalized.update');
    Route::delete('/hospitalized/{patient_id}/{room}/{bed}', [\App\Http\Controllers\DoctorViewController::class, 'hospitalizedDestroy'])->name('hospitalized.destroy');
    
    // Quản lý xuất viện
    Route::get('/discharges', [\App\Http\Controllers\DoctorViewController::class, 'discharges'])->name('discharges.index');
    Route::get('/discharges/create', [\App\Http\Controllers\DoctorViewController::class, 'dischargesCreate'])->name('discharges.create');
    Route::post('/discharges', [\App\Http\Controllers\DoctorViewController::class, 'dischargesStore'])->name('discharges.store');
    Route::get('/discharges/{patient_id}/{discharge_date}/edit', [\App\Http\Controllers\DoctorViewController::class, 'dischargesEdit'])->name('discharges.edit');
    Route::put('/discharges/{patient_id}/{discharge_date}', [\App\Http\Controllers\DoctorViewController::class, 'dischargesUpdate'])->name('discharges.update');
    Route::delete('/discharges/{patient_id}/{discharge_date}', [\App\Http\Controllers\DoctorViewController::class, 'dischargesDestroy'])->name('discharges.destroy');
});

// Test route để kiểm tra ảnh BHYT
Route::get('/test/insurance-images', function() {
    $applications = \App\Models\InsuranceApplication::whereNotNull('proof_images')->get();
    $result = [];
    
    foreach ($applications as $app) {
        $result[] = [
            'id' => $app->id,
            'patient_id' => $app->patient_id,
            'proof_images' => $app->proof_images,
            'storage_urls' => array_map(function($image) {
                return \Illuminate\Support\Facades\Storage::url($image);
            }, $app->proof_images ?? [])
        ];
    }
    
    return response()->json($result);
});






