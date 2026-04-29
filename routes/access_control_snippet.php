
        // Access Control Management Routes
        Route::prefix('access-control')->name('access-control.')->group(function () {
            Route::get('/', [AccessControlController::class, 'index'])->name('index')->middleware('permission:access-control.view');
            
            // User Access Configuration
            Route::post('users/{user}/access-level', [AccessControlController::class, 'setAccessLevel'])->name('users.access-level')->middleware('permission:access-control.update');
            Route::post('users/{user}/login-restriction', [AccessControlController::class, 'setLoginRestrictions'])->name('users.login-restriction')->middleware('permission:access-control.update'); // The missing route!
            
            // Feature Toggles (Single click actions)
            Route::post('users/{user}/toggle-multi-login', [AccessControlController::class, 'toggleMultiLogin'])->name('users.toggle-multi-login')->middleware('permission:access-control.update');
            Route::post('users/{user}/toggle-freeze', [AccessControlController::class, 'toggleFreeze'])->name('users.toggle-freeze')->middleware('permission:access-control.update');
            Route::post('users/{user}/toggle-screenshot', [AccessControlController::class, 'toggleScreenshot'])->name('users.toggle-screenshot')->middleware('permission:access-control.update');
            
            // Information & Checks
            Route::get('users/{user}/summary', [AccessControlController::class, 'getUserAccessSummary'])->name('users.summary')->middleware('permission:access-control.view');
            Route::post('users/check-access', [AccessControlController::class, 'checkAccess'])->name('check-access'); // Utility for frontend check
        });
