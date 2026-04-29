<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\SingleSessionManager;

class AuthController extends Controller
{
    protected $accessControlService;
    protected $singleSessionManager;

    public function __construct(
        AccessControlService $accessControlService,
        SingleSessionManager $singleSessionManager
    )
    {
        $this->accessControlService = $accessControlService;
        $this->singleSessionManager = $singleSessionManager;
    }
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $redirectUrl = $this->getRedirectUrlByRole($user);
            return redirect($redirectUrl);
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string', // Changed from 'email' to 'string' to allow username
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $loginField = $request->input('email');
        $password = $request->input('password');

        // Try to find user by email or username
        $user = User::where('email', $loginField)
                   ->orWhere('username', $loginField)
                   ->first();

        // Check if user exists
        if (!$user) {
            // Log failed login attempt - user not found
            $this->logLoginAttempt($request, null, false, 'User tidak ditemukan', $loginField);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Email/Username atau password salah'
            ], 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            // Log failed login attempt - inactive account
            $this->logLoginAttempt($request, $user->id, false, 'Akun tidak aktif', $loginField);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak aktif. Silakan hubungi administrator.'
            ], 401);
        }

        // Check if user is frozen
        if ($user->is_frozen) {
            // Log failed login attempt - frozen account
            $this->logLoginAttempt($request, $user->id, false, 'Akun dibekukan', $loginField);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda telah dibekukan. Silakan hubungi administrator untuk membuka kembali.'
            ], 401);
        }

        // Check login time restrictions (Check for permission to bypass)
        if (!$user->hasPermission('system.admin.view')) {
            if (!$this->accessControlService->canLoginAtTime($user)) {
                // Log failed login attempt - restricted time
                $this->logLoginAttempt($request, $user->id, false, 'Waktu login tidak diizinkan', $loginField);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Waktu login Anda tidak diizinkan saat ini. Silakan hubungi administrator.'
                ], 401);
            }
        }

        // Attempt authentication
        $credentials = [
            'email' => $user->email, // Use the actual email from database
            'password' => $password
        ];

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            $this->singleSessionManager->registerCurrentSession($user, $request);
            
            // Log successful login
            $this->logLoginAttempt($request, $user->id, true, null);

            // Determine redirect URL based on user role
            $redirectUrl = $this->getRedirectUrlByRole($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'data' => [
                    'user' => $user,
                    'redirect' => $redirectUrl
                ]
            ]);
        }

        // Log failed login attempt - wrong password
        $this->logLoginAttempt($request, $user->id, false, 'Password salah', $loginField);

        return response()->json([
            'status' => 'error',
            'message' => 'Email/Username atau password salah'
        ], 401);
    }
    
    /**
     * Log login attempt with location and device info
     */
    protected function logLoginAttempt(Request $request, $userId, $isSuccessful, $failureReason = null, $attemptedIdentifier = null)
    {
        try {
            $userAgent = $request->userAgent();
            $location = $this->parseUserAgentForLocation($userAgent, $request->ip());
            
            \App\Models\LoginHistory::create([
                'user_id' => $userId,
                'attempted_identifier' => $attemptedIdentifier,
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'location' => $location,
                'login_at' => now(),
                'is_successful' => $isSuccessful,
                'failure_reason' => $failureReason
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create login history: ' . $e->getMessage());
        }
    }
    
    /**
     * Parse user agent to get browser and OS info for location display
     */
    protected function parseUserAgentForLocation($userAgent, $ipAddress)
    {
        // Check for localhost
        if ($ipAddress === '127.0.0.1' || $ipAddress === '::1') {
            $ipLocation = 'Localhost';
        } else {
            $ipLocation = $ipAddress;
        }
        
        // Parse browser
        $browser = 'Unknown Browser';
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent) && !preg_match('/Edge|Edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge|Edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        }
        
        // Parse OS
        $os = 'Unknown OS';
        if (preg_match('/Windows NT 10/i', $userAgent)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6.3/i', $userAgent)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6.2/i', $userAgent)) {
            $os = 'Windows 8';
        } elseif (preg_match('/Windows NT 6.1/i', $userAgent)) {
            $os = 'Windows 7';
        } elseif (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent) && preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $os = 'iOS';
        }
        
        return "{$browser} on {$os}";
    }

    /**
     * API Login with Sanctum Token
     */
    public function apiLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $loginField = $request->input('email');
        $password = $request->input('password');

        // Try to find user by email or username
        $user = User::where('email', $loginField)
                   ->orWhere('username', $loginField)
                   ->first();

        // Check if user exists
        if (!$user || !\Hash::check($password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email/Username atau password salah'
            ], 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak aktif. Silakan hubungi administrator.'
            ], 401);
        }

        // Check if user is frozen
        if ($user->is_frozen) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda telah dibekukan. Silakan hubungi administrator untuk membuka kembali.'
            ], 401);
        }

        // Check login time restrictions (Check for permission to bypass)
        if (!$user->hasPermission('admin.view')) {
            if (!$this->accessControlService->canLoginAtTime($user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Waktu login Anda tidak diizinkan saat ini. Silakan hubungi administrator.'
                ], 401);
            }
        }

        // Create token
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Get role as string
        $role = 'Staff';
        try {
            $userRole = $user->roles()->first();
            if ($userRole) {
                $role = $userRole->name;
            } elseif ($user->department_name) {
                $role = $user->department_name;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to get user role: ' . $e->getMessage());
        }

        // Get photo path - return relative path that mobile app can construct URL from
        $photoPath = null;
        if ($user->photo_file_path && !empty(trim($user->photo_file_path))) {
            // Return just the path without 'uploads/' prefix, mobile app will add it
            $photoPath = $user->photo_file_path;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name ?? '',
                'username' => $user->username ?? $user->email ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'role' => $role,
                'team' => $user->department_name ?? 'Teknisi',
                'area' => 'Area Jakarta',
                'photo_path' => $photoPath,
            ]
        ]);
    }

    /**
     * API Logout
     */
    public function apiLogout(Request $request)
    {
        // Check if user is authenticated
        $user = $request->user();
        
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }

    public function logout(Request $request)
    {
        try {
            // Log logout activity
            if (Auth::check()) {
                $user = Auth::user();
                
                // Update login history with logout time
                try {
                    $latestLogin = \App\Models\LoginHistory::where('user_id', $user->id)
                        ->whereNull('logout_at')
                        ->latest('login_at')
                        ->first();
                    
                    if ($latestLogin) {
                        $latestLogin->update([
                            'logout_at' => now(),
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent()
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error but don't prevent logout
                    \Log::error('Failed to update logout history: ' . $e->getMessage());
                }
            }
            
            // Perform logout
            $sessionId = $request->hasSession() ? $request->session()->getId() : null;
            $user = Auth::user();

            $this->singleSessionManager->forgetCurrentSession($user, $sessionId);
            Auth::logout();
            
            // Invalidate session (only if session exists)
            try {
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
            } catch (\Exception $e) {
                // If session is already invalid, just continue
                \Log::warning('Session already invalid during logout: ' . $e->getMessage());
            }
            
            // Return appropriate response based on request type
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Logout berhasil',
                    'redirect' => route('login')
                ]);
            }
            
            return redirect()->route('login')->with('success', 'Logout berhasil');
            
        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage());
            
            // Force logout even if there's an error
            try {
                Auth::logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                }
            } catch (\Exception $logoutError) {
                // Ignore logout errors
                \Log::warning('Error during force logout: ' . $logoutError->getMessage());
            }
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Logout berhasil',
                    'redirect' => route('login')
                ]);
            }
            
            return redirect()->route('login');
        }
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        // Redirect to role-specific dashboard
        $redirectUrl = $this->getRedirectUrlByRole($user);
        
        // If redirect URL is different from current route, redirect
        if ($redirectUrl !== route('dashboard')) {
            return redirect($redirectUrl);
        }
        
        // Add your dashboard logic here
        return view('dashboard', compact('user'));
    }

    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrlByRole($user)
    {
        // Priority Based Redirect by Permission
        if ($user->hasPermission('system.admin.view')) {
            return route('dashboard');
        }
        
        if ($user->hasPermission('marketing.dashboard.view')) {
            return route('marketing.dashboard');
        }
        
        if ($user->hasPermission('operational.dashboard.view')) {
            return route('operational.dashboard');
        }
        
        if ($user->hasPermission('finance.dashboard.view')) {
            return route('finance.dashboard');
        }
        
        if ($user->hasPermission('warehouse.dashboard.view')) {
            return route('warehouse.dashboard');
        }

        return route('dashboard'); // Default fallback
    }
}
