<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\User\MenuController as UserMenuController;
use Illuminate\Support\Facades\Route;

// Guest routes (belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes (sudah login)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // User routes (hanya untuk role user)
    Route::middleware('user')->prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
        Route::get('/menu-items', [UserMenuController::class, 'getActiveMenus'])->name('menu-items');
        Route::post('/checkout', [UserDashboardController::class, 'checkout'])->name('checkout');
        Route::get('/orders', [UserDashboardController::class, 'orders'])->name('orders');
    });
    
    // Admin routes (hanya untuk role admin)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        
        // ========== MENU MANAGEMENT ROUTES ==========
        // PENTING: Urutan route harus CREATE dulu sebelum SHOW
        
        // Route untuk menampilkan list menu (index)
        Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
        
        // Route untuk menampilkan form create menu (harus sebelum route {menu})
        Route::get('/menus/create', [MenuController::class, 'create'])->name('menus.create');
        
        // Route untuk menyimpan menu baru
        Route::post('/menus', [MenuController::class, 'store'])->name('menus.store');
        
        // Route untuk menampilkan form edit menu
        Route::get('/menus/{menu}/edit', [MenuController::class, 'edit'])->name('menus.edit');
        
        // Route untuk update menu
        Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
        
        // Route untuk delete menu
        Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');
        
        // Route untuk menampilkan single menu (diletakkan setelah create/edit)
        Route::get('/menus/{menu}', [MenuController::class, 'show'])->name('menus.show');
        
        // Route khusus untuk toggle status (AJAX)
        Route::patch('/menus/{menu}/toggle-status', [MenuController::class, 'toggleStatus'])->name('menus.toggle-status');
        // Order Management
        Route::patch('/orders/{order}/status', [AdminDashboardController::class, 'updateStatus'])->name('orders.update-status');
        // ============================================
    });
});

// Home route
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin() 
            ? redirect()->route('admin.dashboard') 
            : redirect()->route('user.dashboard');
    }
    return redirect()->route('login');
});

// Temporary route for Railway DB seeding and debugging
Route::get('/force-migrate', function () {
    try {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true
        ]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return "<pre>Exit Code: $exitCode\n\nOutput:\n$output</pre>";
    } catch (\Exception $e) {
        return "<pre>Error: " . $e->getMessage() . "</pre>";
    }
});