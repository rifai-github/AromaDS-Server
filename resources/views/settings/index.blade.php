@extends('layouts.app')

@section('title', 'Settings')
@section('breadcrumb', 'Home / Settings')

@section('content')
<style>
    .settings-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
    }

    .settings-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .settings-card-header {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 10px 10px 0 0;
        border-bottom: none;
    }

    .settings-item {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s ease;
        text-decoration: none;
        color: #374151;
    }

    .settings-item:hover {
        background-color: #f9fafb;
        color: #214589;
    }

    .settings-item:last-child {
        border-bottom: none;
    }

    .settings-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px;
        font-size: 18px;
    }

    .settings-content {
        flex: 1;
    }

    .settings-title {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 4px;
        color: inherit;
    }

    .settings-description {
        font-size: 12px;
        color: #6b7280;
        margin: 0;
    }

    .settings-badge {
        background-color: #e5e7eb;
        color: #374151;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 500;
    }
</style>

<div class="flex flex-col   w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">

        <!-- Settings Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
            <div class="flex flex-row justify-start items-center w-full">
                <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Settings</p>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="w-full bg-white rounded-b-[10px] p-[16px] md:p-[20px] lg:p-[24px]">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-[16px] md:gap-[20px] lg:gap-[24px]">

                <!-- Theme Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h4 class="text-[14px] md:text-[16px] font-inter font-semibold m-0">
                            <i class="fas fa-palette mr-[8px]"></i>Theme Settings
                        </h4>
                    </div>
                    <div class="p-0">
                        <a href="{{ route('settings.theme') }}" class="settings-item">
                            <div class="settings-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-palette"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Theme Customization</div>
                                <p class="settings-description">Kustomisasi tema dan tampilan aplikasi</p>
                            </div>
                            <span class="settings-badge">Appearance</span>
                        </a>
                        <a href="{{ route('settings.layout') }}" class="settings-item">
                            <div class="settings-icon bg-green-100 text-green-600">
                                <i class="fas fa-th-large"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Layout Settings</div>
                                <p class="settings-description">Pengaturan layout dan navigasi</p>
                            </div>
                            <span class="settings-badge">Layout</span>
                        </a>
                        <a href="{{ route('settings.colors') }}" class="settings-item">
                            <div class="settings-icon bg-purple-100 text-purple-600">
                                <i class="fas fa-paint-brush"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Color Schemes</div>
                                <p class="settings-description">Skema warna dan branding</p>
                            </div>
                            <span class="settings-badge">Colors</span>
                        </a>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h4 class="text-[14px] md:text-[16px] font-inter font-semibold m-0">
                            <i class="fas fa-cogs mr-[8px]"></i>System Settings
                        </h4>
                    </div>
                    <div class="p-0">
                        <a href="{{ route('settings.general') }}" class="settings-item">
                            <div class="settings-icon bg-gray-100 text-gray-600">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">General Settings</div>
                                <p class="settings-description">Pengaturan umum sistem</p>
                            </div>
                            <span class="settings-badge">General</span>
                        </a>
                        <a href="{{ route('settings.email') }}" class="settings-item">
                            <div class="settings-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Email Settings</div>
                                <p class="settings-description">Konfigurasi email dan notifikasi</p>
                            </div>
                            <span class="settings-badge">Email</span>
                        </a>
                        <a href="{{ route('settings.backup') }}" class="settings-item">
                            <div class="settings-icon bg-yellow-100 text-yellow-600">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Backup & Restore</div>
                                <p class="settings-description">Backup dan restore data</p>
                            </div>
                            <span class="settings-badge">Data</span>
                        </a>
                        <a href="{{ route('settings.security') }}" class="settings-item">
                            <div class="settings-icon bg-red-100 text-red-600">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Security Settings</div>
                                <p class="settings-description">Pengaturan keamanan sistem</p>
                            </div>
                            <span class="settings-badge">Security</span>
                        </a>
                    </div>
                </div>

                <!-- User Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h4 class="text-[14px] md:text-[16px] font-inter font-semibold m-0">
                            <i class="fas fa-user mr-[8px]"></i>User Settings
                        </h4>
                    </div>
                    <div class="p-0">
                        <a href="{{ route('settings.profile') }}" class="settings-item">
                            <div class="settings-icon bg-indigo-100 text-indigo-600">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Profile Settings</div>
                                <p class="settings-description">Pengaturan profil pengguna</p>
                            </div>
                            <span class="settings-badge">Profile</span>
                        </a>
                        <a href="{{ route('settings.password') }}" class="settings-item">
                            <div class="settings-icon bg-orange-100 text-orange-600">
                                <i class="fas fa-key"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Password Settings</div>
                                <p class="settings-description">Ubah password akun</p>
                            </div>
                            <span class="settings-badge">Security</span>
                        </a>
                        <a href="{{ route('settings.preferences') }}" class="settings-item">
                            <div class="settings-icon bg-teal-100 text-teal-600">
                                <i class="fas fa-sliders-h"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">User Preferences</div>
                                <p class="settings-description">Preferensi pengguna</p>
                            </div>
                            <span class="settings-badge">Preferences</span>
                        </a>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h4 class="text-[14px] md:text-[16px] font-inter font-semibold m-0">
                            <i class="fas fa-bell mr-[8px]"></i>Notification Settings
                        </h4>
                    </div>
                    <div class="p-0">
                        <a href="{{ route('settings.notifications') }}" class="settings-item">
                            <div class="settings-icon bg-pink-100 text-pink-600">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Notification Preferences</div>
                                <p class="settings-description">Pengaturan notifikasi</p>
                            </div>
                            <span class="settings-badge">Alerts</span>
                        </a>
                        <a href="{{ route('settings.sounds') }}" class="settings-item">
                            <div class="settings-icon bg-purple-100 text-purple-600">
                                <i class="fas fa-volume-up"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Sound Settings</div>
                                <p class="settings-description">Pengaturan suara notifikasi</p>
                            </div>
                            <span class="settings-badge">Audio</span>
                        </a>
                    </div>
                </div>

                <!-- Integration Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h4 class="text-[14px] md:text-[16px] font-inter font-semibold m-0">
                            <i class="fas fa-plug mr-[8px]"></i>Integration Settings
                        </h4>
                    </div>
                    <div class="p-0">
                        <a href="{{ route('settings.api') }}" class="settings-item">
                            <div class="settings-icon bg-green-100 text-green-600">
                                <i class="fas fa-code"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">API Settings</div>
                                <p class="settings-description">Konfigurasi API dan integrasi</p>
                            </div>
                            <span class="settings-badge">API</span>
                        </a>
                        <a href="{{ route('settings.webhooks') }}" class="settings-item">
                            <div class="settings-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-link"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Webhook Settings</div>
                                <p class="settings-description">Pengaturan webhook</p>
                            </div>
                            <span class="settings-badge">Webhooks</span>
                        </a>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h4 class="text-[14px] md:text-[16px] font-inter font-semibold m-0">
                            <i class="fas fa-tools mr-[8px]"></i>Advanced Settings
                        </h4>
                    </div>
                    <div class="p-0">
                        <a href="{{ route('settings.logs') }}" class="settings-item">
                            <div class="settings-icon bg-gray-100 text-gray-600">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">System Logs</div>
                                <p class="settings-description">Log sistem dan debugging</p>
                            </div>
                            <span class="settings-badge">Logs</span>
                        </a>
                        <a href="{{ route('settings.maintenance') }}" class="settings-item">
                            <div class="settings-icon bg-yellow-100 text-yellow-600">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <div class="settings-content">
                                <div class="settings-title">Maintenance Mode</div>
                                <p class="settings-description">Mode maintenance sistem</p>
                            </div>
                            <span class="settings-badge">System</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
