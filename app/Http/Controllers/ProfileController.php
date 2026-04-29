<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Department;
use App\Models\Branch;
use App\Models\Bank;
use App\Models\MasterOption;
use App\Helpers\FileUploadHelper;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user->load(['department', 'branch', 'position', 'priceCategory', 'roles']);
        
        $departments = Department::all();
        $branches = Branch::all();
        $banks = Bank::where('is_active', true)->orderBy('bank_name')->get();
        $positions = MasterOption::where('name', 'Position')->first();
        $priceCategories = MasterOption::where('name', 'Price Category')->first();
        
        return view('profile.index', compact('user', 'departments', 'branches', 'banks', 'positions', 'priceCategories'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'handphone_1' => 'nullable|string|max:20',
            'handphone_2' => 'nullable|string|max:20',
            'address_1' => 'nullable|string|max:500',
            'address_2' => 'nullable|string|max:500',
            'salutation' => 'nullable|string|max:10',
            'gender' => 'nullable|string|max:10',
            'marital_status' => 'nullable|string|max:20',
            'religion' => 'nullable|string|max:50',
            'blood_type' => 'nullable|string|max:5',
            'rhesus' => 'nullable|string|max:10',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|numeric',
            'identity_type' => 'nullable|string|max:20',
            'identity_number' => 'nullable|string|max:50',
            'npwp_number' => 'nullable|string|max:50',
            'bpjs_number' => 'nullable|string|max:50',
            'bpjs_date' => 'nullable|date',
            // New fields
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:255',
            'photo_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'ktp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'npwp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Handle file uploads
            $photoFilePath = $user->photo_file_path;
            $ktpFilePath = $user->ktp_file_path;
            $npwpFilePath = $user->npwp_file_path;
            
            if ($request->hasFile('photo_file')) {
                // Delete old photo if exists
                if ($user->photo_file_path && Storage::disk('public')->exists($user->photo_file_path)) {
                    Storage::disk('public')->delete($user->photo_file_path);
                }
                $photoFile = $request->file('photo_file');
                $photoFilePath = FileUploadHelper::storeFile($photoFile, 'users/photos/photo_' . time() . '.' . $photoFile->getClientOriginalExtension(), 'public');
            }
            
            if ($request->hasFile('ktp_file')) {
                // Delete old KTP file if exists
                if ($user->ktp_file_path && Storage::disk('public')->exists($user->ktp_file_path)) {
                    Storage::disk('public')->delete($user->ktp_file_path);
                }
                $ktpFile = $request->file('ktp_file');
                $ktpFilePath = FileUploadHelper::storeFile($ktpFile, 'users/documents/ktp_' . time() . '.' . $ktpFile->getClientOriginalExtension(), 'public');
            }
            
            if ($request->hasFile('npwp_file')) {
                // Delete old NPWP file if exists
                if ($user->npwp_file_path && Storage::disk('public')->exists($user->npwp_file_path)) {
                    Storage::disk('public')->delete($user->npwp_file_path);
                }
                $npwpFile = $request->file('npwp_file');
                $npwpFilePath = FileUploadHelper::storeFile($npwpFile, 'users/documents/npwp_' . time() . '.' . $npwpFile->getClientOriginalExtension(), 'public');
            }

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'handphone_1' => $request->handphone_1,
                'handphone_2' => $request->handphone_2,
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'salutation' => $request->salutation,
                'gender' => $request->gender,
                'marital_status' => $request->marital_status,
                'religion' => $request->religion,
                'blood_type' => $request->blood_type,
                'rhesus' => $request->rhesus,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_number' => $request->emergency_contact_number,
                'identity_type' => $request->identity_type,
                'identity_number' => $request->identity_number,
                'npwp_number' => $request->npwp_number,
                'bpjs_number' => $request->bpjs_number,
                'bpjs_date' => $request->bpjs_date,
                // New fields
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_account_holder' => $request->bank_account_holder,
                'photo_file_path' => $photoFilePath,
                'ktp_file_path' => $ktpFilePath,
                'npwp_file_path' => $npwpFilePath,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating profile: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        \Log::info('Password change request started', [
            'request_data' => $request->all(),
            'user_id' => Auth::id(),
            'user_email' => Auth::user()->email ?? 'unknown'
        ]);

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            \Log::info('Password change validation failed', [
                'request_data' => $request->all(),
                'errors' => $validator->errors()->toArray()
            ]);
            
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        \Log::info('User found for password change', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'current_password_hash' => $user->password
        ]);

        // Check current password
        $passwordCheck = Hash::check($request->current_password, $user->password);
        \Log::info('Password check result', [
            'provided_password' => $request->current_password,
            'password_match' => $passwordCheck
        ]);

        if (!$passwordCheck) {
            \Log::warning('Password change failed - incorrect current password', [
                'user_id' => $user->id,
                'provided_password' => $request->current_password
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 422);
        }

        try {
            \Log::info('Attempting to update password', [
                'user_id' => $user->id,
                'new_password_length' => strlen($request->new_password)
            ]);

            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            \Log::info('Password updated successfully', [
                'user_id' => $user->id,
                'new_password_hash' => $user->fresh()->password
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Password update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error changing password: ' . $e->getMessage()
            ], 500);
        }
    }
}
