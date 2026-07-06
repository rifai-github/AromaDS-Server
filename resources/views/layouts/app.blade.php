@php
    use Illuminate\Support\Facades\Storage;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Aroma ERP System</title>

    {{-- Include Favicon --}}
    @include('layouts.favicon')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('theme/css/main.css') }}" rel="stylesheet" />
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- jQuery MUST be loaded first (before any page content that might use it) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #dbecfd;
        }

        /* Bootstrap 5 no longer styles legacy .badge-* classes used by older views. */
        .badge.badge-primary {
            background-color: #0d6efd !important;
            color: #fff !important;
        }

        .badge.badge-secondary {
            background-color: #6c757d !important;
            color: #fff !important;
        }

        .badge.badge-success {
            background-color: #198754 !important;
            color: #fff !important;
        }

        .badge.badge-danger {
            background-color: #dc3545 !important;
            color: #fff !important;
        }

        .badge.badge-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        .badge.badge-info {
            background-color: #0dcaf0 !important;
            color: #000 !important;
        }

        .badge.badge-light {
            background-color: #f8f9fa !important;
            color: #000 !important;
        }

        .badge.badge-dark {
            background-color: #212529 !important;
            color: #fff !important;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 220px;
            background: linear-gradient(354.28deg, #14ADD6 0%, #384295 100%);
            color: #fff;
            border-radius: 0 10px 10px 0;
            display: flex;
            flex-direction: column;
            transition: width 0.5s ease-in-out;
            overflow: hidden;
            z-index: 100;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px;
            transition: all 0.5s ease-in-out;
        }

        .sidebar .logo img {
            max-width: 120px;
            transition: all 0.5s ease-in-out;
        }

        .sidebar.collapsed .logo img {
            max-width: 40px;
        }

        .collapse-btn {
            background: #fff;
            border: none;
            color: #384295;
            font-size: 18px;
            cursor: pointer;
            align-self: flex-end; 
            margin: 0 10px 15px 0;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            flex-shrink: 0;
            min-width: 35px;
            min-height: 35px;
        }

        .collapse-btn:hover {
            background: #e9e9e9;
            transform: scale(1.05);
        }

        .collapse-btn i {
            transition: transform 0.5s ease-in-out;
        }

        .menu {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 5px;
            transition: all 0.5s ease-in-out;
            max-height: calc(100vh - 150px);
            min-height: 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.3) transparent;
        }

        /* Ensure smooth scrolling for the main menu */
        .menu {
            scroll-behavior: smooth;
        }

        .menu::-webkit-scrollbar {
            width: 6px;
        }

        .menu::-webkit-scrollbar-track {
            background: transparent;
        }

        .menu::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .menu::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        .menu::-webkit-scrollbar {
            width: 4px;
        }

        .menu::-webkit-scrollbar-track {
            background: transparent;
        }

        .menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }

        .menu::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 2px 8px;
            position: relative;
            flex-shrink: 0;
        }

        .menu-item.has-submenu {
            justify-content: space-between;
        }

        .menu-item:hover {
            background: #C1E0FC;
            color: #225FD3;
        }

        .menu-item.active {
            background: #C1E0FC;
            color: #225FD3;
            font-weight: 600;
        }

        .menu-item .left {
            display: flex;
            align-items: center;
        }

        .menu-item i {
            font-size: 18px;
            min-width: 24px;
            text-align: center;
            flex-shrink: 0;
            margin: 0;
            transition: all 0.3s ease;
        }

        .menu-item span {
            margin-left: 10px;
            transition: all 0.5s ease-in-out;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .menu-item span {
            opacity: 0;
            width: 0;
            pointer-events: none;
        }

        .menu-item .arrow {
            margin-left: auto;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .menu-item.open .arrow {
            transform: rotate(180deg);
        }

        .sidebar.collapsed .menu-item .arrow {
            opacity: 0;
        }

        .sidebar.collapsed .menu-item {
            justify-content: center;
            padding: 12px;
            margin: 2px 8px;
            width: 48px;   
            height: 48px;  
            border-radius: 50%;
            flex-shrink: 0;
            min-width: 48px;
            min-height: 48px;
            transition: all 0.5s ease-in-out;
        }

        .sidebar.collapsed .menu-item:hover,
        .sidebar.collapsed .menu-item.active {
            background: #C1E0FC;
            color: #225FD3;
        }

        .sidebar.collapsed .menu-item .left {
            justify-content: center;
            width: 100%;
            display: flex;
            align-items: center;
        }

        .sidebar.collapsed .menu-item .left span {
            display: none;
        }

        .sidebar.collapsed .menu-item.has-submenu {
            justify-content: center;
        }

        .sidebar.collapsed .menu-item.has-submenu .arrow {
            display: none;
        }

        .sidebar.collapsed .menu-item .left i {
            margin: 0;
            text-align: center;
        }

        .sidebar.collapsed .menu-item .left {
            justify-content: center;
            width: 100%;
            display: flex;
            align-items: center;
            text-align: center;
        }

        .sidebar.collapsed .bottom {
            padding: 10px 0;
            background: rgba(0, 0, 0, 0.15);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar.collapsed .bottom .menu-item {
            flex-shrink: 0;
        }

        /* Ensure bottom section is always visible */
        .sidebar .bottom {
            min-height: 80px;
        }

        .sidebar.collapsed .bottom {
            min-height: 60px;
        }

        /* Ensure smooth transitions for all sidebar elements */
        .sidebar * {
            transition: all 0.3s ease;
        }

        /* Ensure submenu doesn't overlap with bottom section */
        .submenu.show {
            margin-bottom: 10px;
        }

        /* Ensure submenu stays within sidebar bounds */
        .submenu {
            position: relative;
            z-index: 5;
        }

        /* Ensure submenu doesn't extend beyond sidebar */
        .submenu.show {
            position: relative;
            z-index: 5;
        }

        /* Ensure submenu items are properly contained */
        .submenu li {
            max-width: 100%;
            box-sizing: border-box;
            min-height: 44px;
            display: flex;
            align-items: center;
        }

        /* Ensure menu items have proper spacing */
        .menu-item + .submenu {
            margin-top: 5px;
        }

        .submenu {
            list-style: none;
            margin: 0 8px;
            padding: 0;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            background: #C1E0FC;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            transition: all 0.4s ease;
            position: relative;
            z-index: 5;
            width: calc(100% - 16px);
            box-sizing: border-box;
        }

        .submenu.show {
            max-height: none;
            opacity: 1;
            padding: 10px 0;
            overflow: visible;
            background: #C1E0FC;
            width: calc(100% - 16px);
            box-sizing: border-box;
        }

        /* Ensure submenu items use wrap text and are fully visible */
        .submenu li {
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            word-wrap: break-word;
            line-height: 1.4;
            padding: 12px 20px;
        }

        .sidebar.collapsed .submenu {
            background: transparent;
            box-shadow: none;
            margin: 0;
            padding: 0;
            transition: all 0.5s ease-in-out;
            max-height: 0;
            overflow: hidden;
        }

        .sidebar.collapsed .submenu.show {
            max-height: 200px;
            overflow: visible;
            background: transparent;
        }

        .sidebar.collapsed .submenu li {
            background: transparent;
            padding: 8px 12px;
            margin: 2px 8px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all 0.3s ease;
            color: transparent;
            font-size: 0;
            cursor: pointer;
            flex-shrink: 0;
        }

        .sidebar.collapsed .submenu li:hover {
            background-color: rgba(34,95,211,0.15);
        }

        .sidebar.collapsed .submenu li.active {
            background-color: rgba(34,95,211,0.25);
        }

        .sidebar.collapsed .submenu li::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.6);
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .submenu li:hover::before {
            background-color: rgba(255,255,255,0.9);
        }

        .sidebar.collapsed .submenu li.active::before {
            background-color: rgba(255,255,255,1);
        }

        /* Ensure submenu doesn't overflow sidebar when collapsed */
        .sidebar.collapsed .submenu {
            position: relative;
            z-index: 1;
        }

        /* Ensure submenu items are properly contained */
        .sidebar.collapsed .submenu li {
            max-width: 48px;
            max-height: 48px;
        }

        /* Ensure submenu doesn't extend beyond sidebar width */
        .sidebar.collapsed .submenu {
            width: 100%;
            box-sizing: border-box;
        }

        /* Ensure submenu items don't overflow */
        .sidebar.collapsed .submenu li {
            overflow: hidden;
        }

        /* Ensure submenu doesn't extend beyond sidebar */
        .sidebar.collapsed .submenu {
            left: 0;
            right: 0;
        }

        .submenu li {
            padding: 12px 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 6px;
            color: #214589;
            position: relative;
            flex-shrink: 0;
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            word-wrap: break-word;
            line-height: 1.4;
        }

        .submenu li:hover {
            background: rgba(34,95,211,0.15);
            color: #225FD3;
        }

        .submenu li.active {
            background: rgba(34,95,211,0.25);
            color: #225FD3;
            font-weight: 600;
        }

        /* Menu Divider */
        .menu-divider {
            height: 1px;
            background: #214589;
            margin: 4px 16px;
            border: none;
            list-style: none;
            padding: 0;
            opacity: 0.6;
            min-height: 1px;
            max-height: 1px;
            display: block;
            content: '';
        }

        .sidebar.collapsed .menu-divider {
            margin: 2px 8px;
            opacity: 0.4;
        }

        /* Override submenu li styling for menu-divider */
        .submenu .menu-divider {
            padding: 0 !important;
            margin: 4px 16px !important;
            height: 1px !important;
            min-height: 1px !important;
            max-height: 1px !important;
            background: #214589 !important;
            border-radius: 0 !important;
            display: block !important;
            cursor: default !important;
            color: transparent !important;
            font-size: 0 !important;
            line-height: 0 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: unset !important;
            word-wrap: normal !important;
        }

        /* Override for collapsed sidebar */
        .sidebar.collapsed .submenu .menu-divider {
            margin: 2px 8px !important;
            opacity: 0.4 !important;
        }

        /* Custom Scrollbar for Submenu - Removed since submenu no longer scrolls */

        .bottom {
            margin-top: auto;
            padding: 15px 0;
            flex-shrink: 0;
            transition: all 0.5s ease-in-out;
            background: rgba(0, 0, 0, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 10;
        }

        .bottom .menu-item {
            flex-shrink: 0;
        }

        /* Tooltip styles */
        .tooltip {
            position: fixed;
            left: 80px;
            background: #225FD3;
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .sidebar.collapsed .menu-item:hover .tooltip,
        .sidebar.collapsed .submenu li:hover .tooltip {
            opacity: 1;
        }

        .main-content {
            margin-left: 220px;
            transition: margin-left 0.5s ease-in-out;
            min-height: 100vh;
            padding: 20px;
        }

        .main-content.sidebar-collapsed {
            margin-left: 70px;
        }

        /* Header styling to match marketing.html */
        .content-header {
            background: white;
            border-radius: 10px;
            padding: 6px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-inner {
            display: flex;
            flex-direction: column;
            margin: 6px 12px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .header-title {
            font-size: 22px;
            font-weight: bold;
            color: #214589;
            margin: 0;
            line-height: 27px;
        }

        .header-breadcrumb {
            font-size: 12px;
            color: #214589;
            margin: 0;
            line-height: 15px;
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #214589;
        }

        .user-info {
            margin-left: 14px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .user-name {
            font-size: 14px;
            font-weight: bold;
            color: #3d3d3d;
            line-height: 17px;
        }

        .user-role {
            font-size: 12px;
            color: #3d3d3d;
            line-height: 15px;
        }

        .content-body {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Button styling to match marketing.html */
        .btn {
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            line-height: 17px;
        }

        .btn-primary {
            background: #214589;
            color: white;
        }

        .btn-primary:hover {
            background: #1a3a6e;
        }



        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: #eff6ff; /* Light Blue hover */
            transition: background-color 0.2s ease;
        }

        /* Global Zebra Striping (User Request) */
        .table tbody tr:nth-child(even),
        .responsive-table tbody tr:nth-child(even),
        table tbody tr:nth-child(even) {
            background-color: #f9fbff; /* Very light blue/gray for even rows */
        }
        
        .responsive-table tbody tr:hover,
        table tbody tr:hover {
            background-color: #eff6ff; /* Light Blue hover */
            transition: background-color 0.2s ease;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #384295;
            box-shadow: 0 0 0 2px rgba(56, 66, 149, 0.1);
        }

        /* Validation Styles */
        .form-control.is-invalid,
        .was-validated .form-control:invalid {
            border-color: #dc3545 !important;
            background-image: none;
        }

        .form-control.is-invalid:focus,
        .was-validated .form-control:invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1) !important;
        }

        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 80%;
            color: #dc3545;
        }

        .form-control.is-invalid ~ .invalid-feedback,
        .is-invalid ~ .invalid-feedback,
        .was-validated :invalid ~ .invalid-feedback {
            display: block;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }


        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
            color: #384295;
        }

        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card .stat-label {
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 1024px) {
            /* Hide collapse button on mobile */
            .collapse-btn {
                display: none !important;
            }
            
            /* Mobile sidebar behavior */
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px !important; /* Fixed width for mobile */
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            /* Disable collapse functionality on mobile */
            .sidebar.collapsed {
                width: 280px !important;
                transform: translateX(-100%);
            }
            
            .sidebar.collapsed.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
                padding-top: 80px;
            }

            /* Mobile hamburger menu */
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1000;
                background: #14ADD6;
                border: none;
                color: white;
                padding: 12px;
                border-radius: 8px;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                font-size: 16px;
            }

            .mobile-menu-toggle:hover {
                background: #384295;
            }

            /* Overlay for mobile */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 99;
            }

            .sidebar-overlay.show {
                display: block;
            }

            /* Ensure menu items are visible on mobile */
            .sidebar .menu-item span {
                opacity: 1 !important;
                width: auto !important;
                pointer-events: auto !important;
            }

            .sidebar .menu-item {
                justify-content: flex-start !important;
                padding: 12px 18px !important;
                margin: 2px 8px !important;
                width: auto !important;
                height: auto !important;
                border-radius: 6px !important;
            }

            .sidebar .menu-item .arrow {
                opacity: 1 !important;
            }
        }

        @media (min-width: 1025px) {
            .mobile-menu-toggle {
                display: none;
            }
            
            .sidebar-overlay {
                display: none !important;
            }
        }

        /* Context Menu Styles */
        .context-menu {
            position: fixed;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 8px 0;
            z-index: 10000;
            min-width: 180px;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.2s ease;
            pointer-events: none;
        }

        .context-menu.show {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
        }

        .context-menu-item {
            padding: 10px 16px;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s ease;
        }

        .context-menu-item:hover {
            background-color: #f5f5f5;
        }

        .context-menu-item i {
            width: 16px;
            text-align: center;
            color: #666;
        }

        .context-menu-separator {
            height: 1px;
            background-color: #eee;
            margin: 4px 0;
        }

        .context-menu-item.danger {
            color: #dc3545;
        }

        .context-menu-item.danger:hover {
            background-color: #f8d7da;
        }

        .context-menu-item.danger i {
            color: #dc3545;
        }

        /* Sticky Horizontal Scrollbar for Tables */
        .table-responsive,
        .table-container,
        [style*="overflow-x"],
        .overflow-x-auto {
            position: relative;
        }

        /* Fake scrollbar container that sticks to viewport bottom */
        .sticky-scroll-container {
            position: fixed;
            bottom: 0;
            height: 20px;
            background: linear-gradient(to bottom, #e8e8e8, #d0d0d0);
            border-top: 1px solid #bbb;
            z-index: 9999;
            overflow-x: scroll !important;
            overflow-y: hidden;
            display: none;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
        }

        .sticky-scroll-container.visible {
            display: block !important;
        }

        .sticky-scroll-inner {
            height: 20px;
            min-height: 20px;
            pointer-events: none;
        }

        /* Ensure main content has padding at bottom when sticky scroll is visible */
        body.has-sticky-scroll .main-content {
            padding-bottom: 30px;
        }

        /* Style the sticky scrollbar - make it more visible */
        .sticky-scroll-container::-webkit-scrollbar {
            height: 16px;
        }

        .sticky-scroll-container::-webkit-scrollbar-track {
            background: #e0e0e0;
            border-radius: 8px;
            border: 2px solid #d0d0d0;
        }

        .sticky-scroll-container::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #888, #666);
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            min-width: 50px;
        }

        .sticky-scroll-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #666, #444);
        }

        .sticky-scroll-container::-webkit-scrollbar-thumb:active {
            background: linear-gradient(to bottom, #555, #333);
        }

        /* ============================================
           GLOBAL STICKY TABLE HEADER STYLES
           ============================================ */
        
        /* Make table container scrollable with sticky header support */
        .table-container {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            position: relative;
        }

        /* Sticky table header - first row (column names) */
        .responsive-table thead tr:first-child th,
        .table thead tr:first-child th {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #214589 !important;
            color: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Sticky table header - second row (filter inputs) */
        .responsive-table thead tr:nth-child(2) th,
        .responsive-table thead tr:nth-child(2) td,
        .table thead tr:nth-child(2) th,
        .table thead tr:nth-child(2) td {
            position: sticky;
            top: 40px; /* Adjust based on header row height */
            z-index: 19;
            background-color: #f8fafc !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }


        /* Ensure filter inputs have proper background */
        .responsive-table thead tr:nth-child(2) input,
        .responsive-table thead tr:nth-child(2) select,
        .table thead tr:nth-child(2) input,
        .table thead tr:nth-child(2) select {
            background-color: white;
        }

        /* Alternative: Sticky for tables with filter row inside thead */
        .responsive-table thead,
        .table thead {
            position: sticky;
            top: 0;
            z-index: 20;
        }
    </style>
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Flatpickr (standardized datepicker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">

    @stack('styles')
</head>
<body class="bg-[#dbecfd] min-h-screen">
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileMenu()"></div>

    <!-- Context Menu -->
    <div class="context-menu" id="contextMenu">
        <div class="context-menu-item" onclick="contextMenuAction('refresh')">
            <i class="fas fa-sync-alt"></i>
            Refresh Page
        </div>
        <div class="context-menu-item" onclick="contextMenuAction('newTab')">
            <i class="fas fa-external-link-alt"></i>
            Open in New Tab
        </div>
        <div class="context-menu-separator"></div>
        <div class="context-menu-item" onclick="contextMenuAction('bookmark')">
            <i class="fas fa-bookmark"></i>
            Bookmark
        </div>
        <div class="context-menu-item" onclick="contextMenuAction('copy')">
            <i class="fas fa-copy"></i>
            Copy Link
        </div>
        <div class="context-menu-separator"></div>
        <div class="context-menu-item" onclick="contextMenuAction('inspect')">
            <i class="fas fa-code"></i>
            Inspect Element
        </div>
        <div class="context-menu-item danger" onclick="contextMenuAction('logout')">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </div>
    </div>


    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img id="logoImg" src="{{ asset('theme/assets/images/img_logo.svg') }}" alt="Aroma Logo" onerror="this.style.display='none'">
        </div>
        
        <button class="collapse-btn" id="collapseBtn" onclick="toggleSidebar()">
            <i class="fas fa-angle-left"></i>
        </button>

        <div class="menu">
            <!-- Dashboard -->
            @php
                $user = auth()->user();
                $roleName = $user->getRoleName(); // MOM10: Use Individual Role only
                $roleLower = strtolower($roleName ?? '');
                $isMarketing = $user->hasPermission('marketing.dashboard');
                $dashboardRoute = $isMarketing ? route('marketing.dashboard') : route('dashboard');
            @endphp
            <div class="menu-item {{ request()->routeIs('dashboard') || request()->routeIs('marketing.dashboard') ? 'active' : '' }}" onclick="navigateTo('{{ $dashboardRoute }}', event)">
                <div class="left">
                    <i class="fas fa-table-columns"></i>
                    <span>Dashboard</span>
                </div>
                <div class="tooltip">Dashboard</div>
            </div>

            <!-- Marketing - Check by permission -->
            @if(auth()->user()->canAccessModule('marketing'))
            @php
                // Routes that are in Marketing menu but use different route prefixes
                $marketingMenuRoutes = [
                    'marketing.*',
                    'company.customers.*',
                    'company.customer-contacts.*',
                    'company.customer-taxes.*',
                    'system.customer-types.*',
                    'company.company-virtual-accounts.*',
                    'system.salutations.*',
                    'operational.buildings.*', // Buildings moved to Marketing menu
                    'marketing.master-corporates.*' // Added Master Corporate
                ];
                $isMarketingMenuActive = false;
                foreach ($marketingMenuRoutes as $routePattern) {
                    if (request()->routeIs($routePattern)) {
                        $isMarketingMenuActive = true;
                        break;
                    }
                }
            @endphp
            <div class="menu-item has-submenu {{ $isMarketingMenuActive ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                <div class="left">
                    <i class="fas fa-bullhorn"></i>
                    <span>Marketing</span>
                </div>
                <i class="fas fa-chevron-down arrow"></i>
                <div class="tooltip">Marketing</div>
            </div>
            <ul class="submenu {{ $isMarketingMenuActive ? 'show' : '' }}">
                @if(auth()->user()->canAccessMenuItem('marketing.dashboard'))
                <li data-tooltip="Dashboard" class="{{ request()->routeIs('marketing.dashboard') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.dashboard') }}')">Dashboard</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.commissions.dashboard'))
                <li data-tooltip="Commission Dashboard" class="{{ request()->routeIs('marketing.commissions.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.commissions.dashboard') }}')">Commission Dashboard</li>
                @endif
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('marketing.pipeline'))
                <li data-tooltip="Pipeline" class="{{ request()->routeIs('marketing.pipeline.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.pipeline.index') }}')">Pipeline</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.surveys'))
                <li data-tooltip="Survey" class="{{ request()->routeIs('marketing.surveys.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.surveys.index') }}')">Survey</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.quotations'))
                <li data-tooltip="Quotation" class="{{ request()->routeIs('marketing.quotations.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.quotations.index') }}')">Quotation</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.contracts'))
                <li data-tooltip="Contract" class="{{ request()->routeIs('marketing.contracts.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.contracts.index') }}')">Contract</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.contract-terminations'))
                <li data-tooltip="Contract Termination" class="{{ request()->routeIs('marketing.contract-terminations.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.contract-terminations.index') }}')">Contract Termination</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.contract-assigned'))
                <li data-tooltip="Contract Assigned" class="{{ request()->routeIs('marketing.contract-assigned.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.contract-assigned.index') }}')">Contract Assigned</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.contract-switchings'))
                <li data-tooltip="Contract Switching" class="{{ request()->routeIs('marketing.contract-switchings.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.contract-switchings.index') }}')">Contract Switching</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.aroma-changes'))
                <li data-tooltip="Aroma Switching" class="{{ request()->routeIs('marketing.aroma-changes.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.aroma-changes.index') }}')">Aroma Switching</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.stock-view'))
                <li data-tooltip="Stock View" class="{{ request()->routeIs('marketing.stock-view.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.stock-view.index') }}')">Stock View</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.job-advices'))
                <li data-tooltip="Job Advice" class="{{ request()->routeIs('marketing.job-advices.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.job-advices.index') }}')">Job Advice</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.lost-unit-reports'))
                <li data-tooltip="Lost Unit Report" class="{{ request()->routeIs('marketing.lost-unit-reports.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.lost-unit-reports.index') }}')">Lost Unit Report</li>
                @endif
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('marketing.customers'))
                <li data-tooltip="Master Customer" class="{{ request()->routeIs('company.customers.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('company.customers.index') }}')">Master Customer</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.master-corporates'))
                <li data-tooltip="Master Corporate" class="{{ request()->routeIs('marketing.master-corporates.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('marketing.master-corporates.index') }}')">Master Corporate</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.customer-contacts'))
                <li data-tooltip="Customer Contacts" class="{{ request()->routeIs('company.customer-contacts.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('company.customer-contacts.index') }}')">Customer Contacts</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.customer-taxes'))
                <li data-tooltip="Master Customer Tax" class="{{ request()->routeIs('company.customer-taxes.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('company.customer-taxes.index') }}')">Master Customer Tax</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('marketing.customer-types'))
                <li data-tooltip="Master Customer Category" class="{{ request()->routeIs('system.customer-types.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.customer-types.index') }}')">Master Customer Category</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('company.company-virtual-accounts') || auth()->user()->canAccessMenuItem('marketing.company-virtual-accounts'))
                <li data-tooltip="Company Virtual Account" class="{{ request()->routeIs('company.company-virtual-accounts.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('company.company-virtual-accounts.index') }}')">Company Virtual Account</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('system.salutations'))
                <li data-tooltip="Master Salutation" class="{{ request()->routeIs('system.salutations.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.salutations.index') }}')">Master Salutation</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('operational.master-buildings') || auth()->user()->canAccessMenuItem('marketing.master-buildings'))
                <li data-tooltip="Master Building" class="{{ request()->routeIs('operational.buildings.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.buildings.index') }}')">Master Building</li>
                @endif
            </ul>
            @endif

            <!-- Operational - Check by permission -->
            @if(auth()->user()->canAccessModule('operational'))
            @php
                // Routes that are actually in Operational menu (exclude buildings which moved to Marketing)
                $operationalMenuRoutes = [
                    'operational.job-schedules.*',
                    'operational.job-assign-material-issues.*',
                    'operational.teams.*',
                    'operational.master-rooms.*',
                    'operational.room-rental-units.*'
                ];
                $isOperationalMenuActive = false;
                foreach ($operationalMenuRoutes as $routePattern) {
                    if (request()->routeIs($routePattern)) {
                        $isOperationalMenuActive = true;
                        break;
                    }
                }
            @endphp
            <div class="menu-item has-submenu {{ $isOperationalMenuActive ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                <div class="left">
                    <i class="fas fa-file-signature"></i>
                    <span>Operational</span>
                </div>
                <i class="fas fa-chevron-down arrow"></i>
                <div class="tooltip">Operational</div>
            </div>
            <ul class="submenu {{ $isOperationalMenuActive ? 'show' : '' }}">
                @if(auth()->user()->canAccessMenuItem('operational.job-schedules'))
                <li data-tooltip="Job Schedule List" class="{{ request()->routeIs('operational.job-schedules.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.job-schedules.index') }}')">Job Schedule</li>
                @endif
               <!-- <li data-tooltip="Job Reports" class="{{ request()->routeIs('operational.job-reports.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.job-reports.index') }}')">Job Reports</li>
                <li data-tooltip="Technician Locations" class="{{ request()->routeIs('operational.technician-locations.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.technician-locations.index') }}')">Technician Locations</li>
                <li data-tooltip="Force Majeure Dashboard" class="{{ request()->routeIs('operational.force-majeure-dashboard') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.force-majeure-dashboard') }}')">Force Majeure Dashboard</li> -->
                @if(auth()->user()->canAccessMenuItem('operational.job-assign'))
               <!-- <li data-tooltip="Job Assign - Schedule" class="{{ request()->routeIs('operational.job-assign-schedules.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.job-assign-schedules.index') }}')">Job Assign - Schedule</li> -->
                @endif
                @if(auth()->user()->canAccessMenuItem('operational.job-assign-material-issues'))
                <li data-tooltip="Material Assign" class="{{ request()->routeIs('operational.job-assign-material-issues.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.job-assign-material-issues.index') }}')">Material Assign</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('operational.master-team'))
                <li data-tooltip="Master Team" class="{{ request()->routeIs('operational.teams.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.teams.index') }}')">Master Team</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('operational.master-rooms'))
                <li data-tooltip="Master Room" class="{{ request()->routeIs('operational.master-rooms.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.master-rooms.index') }}')">Master Room</li>
                <li data-tooltip="Jenis Ruangan" class="{{ request()->routeIs('operational.master-rooms.room-types') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.master-rooms.room-types') }}')">Jenis Ruangan</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('operational.room-rental-units'))
               <!-- <li data-tooltip="Room Rental Unit" class="{{ request()->routeIs('operational.room-rental-units.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('operational.room-rental-units.index') }}')">Room Rental Unit</li> -->
                @endif
            </ul>
            @endif

            <!-- Finance - Check by permission -->
            @if(auth()->user()->canAccessModule('finance'))
            <div class="menu-item has-submenu {{ request()->routeIs('finance.*') ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                <div class="left">
                    <i class="fas fa-calculator"></i>
                    <span>Finance</span>
                </div>
                <i class="fas fa-chevron-down arrow"></i>
                <div class="tooltip">Finance</div>
            </div>
            <ul class="submenu {{ request()->routeIs('finance.*') ? 'show' : '' }}">
                @if(auth()->user()->canAccessMenuItem('finance.invoices'))
                <li data-tooltip="Invoice" class="{{ request()->routeIs('finance.invoices.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.invoices.index') }}')">Invoice</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.invoice-follow-ups'))
                <li data-tooltip="Invoice - Follow Up" class="{{ request()->routeIs('finance.invoice-follow-ups.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.invoice-follow-ups.index') }}')">Invoice - Follow Up</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.bank-receipts'))
              <!--  <li data-tooltip="Bank Receipt" class="{{ request()->routeIs('finance.bank-receipts.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.bank-receipts.index') }}')">Bank Receipt</li> -->
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.virtual-account-imports'))
               <!-- <li data-tooltip="Virtual Account - Import" class="{{ request()->routeIs('finance.virtual-account-imports.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.virtual-account-imports.index') }}')">Virtual Account - Import</li> -->
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.virtual-account-exports'))
               <!-- <li data-tooltip="Virtual Account - Export" class="{{ request()->routeIs('finance.virtual-account-exports.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.virtual-account-exports.index') }}')">Virtual Account - Export</li> -->
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.tax-file-imports'))
                <li data-tooltip="Tax File - Import" class="{{ request()->routeIs('finance.tax-file-imports.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.tax-file-imports.index') }}')">Tax File - Import</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.tax-file-exports'))
                <li data-tooltip="Tax File - Export" class="{{ request()->routeIs('finance.tax-file-exports.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.tax-file-exports.index') }}')">Tax File - Export</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.faktur-pajak'))
               <!-- <li data-tooltip="Faktur Pajak" class="{{ request()->routeIs('finance.faktur-pajak.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.faktur-pajak.index') }}')">Faktur Pajak</li> -->
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.tax-settings'))
                <li data-tooltip="Tax Setting" class="{{ request()->routeIs('finance.tax-settings.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.tax-settings.index') }}')">Tax Setting</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.tax-codes'))
                <li data-tooltip="Kode Pajak" class="{{ request()->routeIs('finance.tax-codes.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.tax-codes.index') }}')">Kode Pajak</li>
                @endif
                {{-- <li data-tooltip="Billing Groups" class="{{ request()->routeIs('finance.billing-groups.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.billing-groups.index') }}')">Billing Groups</li> --}}
                
                <!-- Commission System Section -->
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('finance.commission-levels'))
                <li data-tooltip="Commission Levels" class="{{ request()->routeIs('finance.commission-levels.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.commission-levels.index') }}')">Commission Levels</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.marketing-levels'))
                <li data-tooltip="Marketing Levels" class="{{ request()->routeIs('finance.marketing-levels.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.marketing-levels.index') }}')">Marketing Levels</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.cr-variables'))
                <li data-tooltip="CR Variables" class="{{ request()->routeIs('finance.cr-variables.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.cr-variables.index') }}')">CR Variables</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.marketing-targets'))
                <li data-tooltip="Marketing Targets" class="{{ request()->routeIs('finance.marketing-targets.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.marketing-targets.index') }}')">Marketing Targets</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.renewal-contract-assignments'))
                <li data-tooltip="Renewal Contract Assignments" class="{{ request()->routeIs('finance.renewal-contract-assignments.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.renewal-contract-assignments.index') }}')">Renewal Contract Assignments</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.commission-transfers'))
                <li data-tooltip="Commission Transfers" class="{{ request()->routeIs('finance.commission-transfers.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.commission-transfers.index') }}')">Commission Transfers</li>
                @endif
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('finance.commissions'))
                <li data-tooltip="Commission System" class="{{ request()->routeIs('finance.commissions.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.commissions.index') }}')">Commission System</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.achievements'))
                <li data-tooltip="Achievements" class="{{ request()->routeIs('finance.achievements.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.achievements.index') }}')">Achievements</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.achievement-periods'))
                <li data-tooltip="Achievement Periods" class="{{ request()->routeIs('finance.achievement-periods.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.achievement-periods.index') }}')">Achievement Periods</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('finance.commission-payments'))
                <li data-tooltip="Commission Payments" class="{{ request()->routeIs('finance.commission-payments.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('finance.commission-payments.index') }}')">Commission Payments</li>
                @endif
            </ul>
            @endif

            <!-- Warehouse - Check by permission -->
            @if(auth()->user()->canAccessModule('warehouse'))
            <div class="menu-item has-submenu {{ request()->routeIs('warehouse.*') ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                <div class="left">
                    <i class="fas fa-cube"></i>
                    <span>Warehouse</span>
                </div>
                <i class="fas fa-chevron-down arrow"></i>
                <div class="tooltip">Warehouse</div>
            </div>
            <ul class="submenu {{ request()->routeIs('warehouse.*') ? 'show' : '' }}">
                <!-- 1. MASTER DATA SETUP -->
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('warehouse.warehouses'))
                <li data-tooltip="Warehouses" class="{{ request()->routeIs('warehouse.warehouses.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.warehouses.index') }}')">Warehouses</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('warehouse.product-structure'))
                <li data-tooltip="Product Structure" class="{{ request()->routeIs('warehouse.product-structure.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.product-structure.categories') }}')">Product Structure</li>
                @endif
                {{-- Product Type menu hidden - merged into Product Structure --}}
                {{-- @if(auth()->user()->canAccessMenuItem('warehouse.product-types'))
                <li data-tooltip="Product Type" class="{{ request()->routeIs('warehouse.product-types.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.product-types.index') }}')">Product Type</li>
                @endif --}}
                <li data-tooltip="Brand Variant" class="{{ request()->routeIs('warehouse.brand-variants.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.brand-variants.index') }}')">Brand Variant</li>
                
                <!-- 2. PRODUCT MANAGEMENT -->
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('warehouse.master-products'))
                <li data-tooltip="Master Product" class="{{ request()->routeIs('warehouse.master-products.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.master-products.index') }}')">Master Product</li>
                @endif
                
                <!-- 3. RENTAL MANAGEMENT -->
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('warehouse.master-rentals'))
                <li data-tooltip="Master Rental" class="{{ request()->routeIs('warehouse.master-rentals.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.master-rentals.index') }}')">Master Rental</li>
                @endif
            
                
                <!-- 4. INVENTORY OPERATIONS -->
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('warehouse.inventory-issuings'))
                <li data-tooltip="Inventory Issuing" class="{{ request()->routeIs('warehouse.inventory-issuings.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.inventory-issuings.index') }}')">Inventory Issuing</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('warehouse.inventory-receivings'))
                <li data-tooltip="Inventory Receiving" class="{{ request()->routeIs('warehouse.inventory-receivings.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.inventory-receivings.index') }}')">Inventory Receiving</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('warehouse.inventory-requests'))
                <li data-tooltip="Inventory Request" class="{{ request()->routeIs('warehouse.inventory-requests.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.inventory-requests.index') }}')">Inventory Request</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('warehouse.inventory-transfers'))
                <li data-tooltip="Inventory Transfer" class="{{ request()->routeIs('warehouse.inventory-transfers.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.inventory-transfers.index') }}')">Inventory Transfer</li>
                @endif
                
                <!-- 5. STOCK MANAGEMENT -->
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('warehouse.stock-opnames'))
                <li data-tooltip="Stock Opname" class="{{ request()->routeIs('warehouse.stock-opnames.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.stock-opnames.index') }}')">Stock Opname</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('warehouse.stock-adjustments'))
                <li data-tooltip="Stock Adjustment" class="{{ request()->routeIs('warehouse.stock-adjustments.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.stock-adjustments.index') }}')">Stock Adjustment</li>
                @endif
                
                <!-- 6. ADDITIONAL TOOLS -->
                <li class="menu-divider"></li>
                @if(auth()->user()->canAccessMenuItem('warehouse.serial-numbers'))
                <li data-tooltip="Check Serial Number" class="{{ request()->routeIs('warehouse.serial-numbers.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.serial-numbers.index') }}')">Check Serial Number</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('warehouse.unit-on-walls'))
                <li data-tooltip="Unit On Wall" class="{{ request()->routeIs('warehouse.unit-on-walls.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.unit-on-walls.index') }}')">Unit On Wall</li>
                @endif
                
                <!-- 7. LOGISTICS -->
              <!--  <li class="menu-divider"></li>
                <li data-tooltip="Logistics Tracking" class="{{ request()->routeIs('warehouse.inventory-logistics.tracking*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.inventory-logistics.tracking') }}')">Logistics Tracking</li>
                <li data-tooltip="Berita Acara" class="{{ request()->routeIs('warehouse.inventory-logistics.berita-acara*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.inventory-logistics.berita-acara') }}')">Berita Acara</li>
                <li data-tooltip="Purchasing Requests" class="{{ request()->routeIs('warehouse.inventory-logistics.purchasing-requests*') ? 'active' : '' }}" onclick="navigateTo('{{ route('warehouse.inventory-logistics.purchasing-requests') }}')">Purchasing Requests</li> -->
            </ul>
            @endif
            <!-- Updated: Unit On Wall menu added -->

            <!-- Company - Check by permission -->
            <!-- Modified to use explicit permission check to avoid phantom empty module -->
            @php

                // Routes that are in Company menu but use different route prefixes
                $companyMenuRoutes = [
                    'company.branches.*',
                    'company.master-banks.*',
                    'company.bank-payments.*',
                    'company.master-price-slabs.*',
                    'company.companies.*',
                    'system.positions.*', // Positions moved to Company menu
                    'other.master-options.*' // Master Options moved to Company menu
                ];
                // Check if user has ANY of the specific Company menu items permissions
                // Use strict check to prevent empty module if user only has "company.customers.*" (which is in Marketing)
                $hasCompanyAccess = auth()->user()->canAccessMenuItem('company.branches') ||
                                   auth()->user()->canAccessMenuItem('company.master-banks') ||
                                   auth()->user()->canAccessMenuItem('company.bank-payments') ||
                                   auth()->user()->canAccessMenuItem('company.master-price-slabs') ||
                                   auth()->user()->canAccessMenuItem('company.companies') ||
                                   auth()->user()->canAccessMenuItem('company.positions') ||
                                   auth()->user()->canAccessMenuItem('company.master-options');
                                   
                $isCompanyMenuActive = false;
                foreach ($companyMenuRoutes as $routePattern) {
                    if (request()->routeIs($routePattern)) {
                        $isCompanyMenuActive = true;
                        break;
                    }
                }
            @endphp
            @if($hasCompanyAccess)
            <div class="menu-item has-submenu {{ $isCompanyMenuActive ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                <div class="left">
                    <i class="fas fa-building"></i>
                    <span>Company</span>
                </div>
                <i class="fas fa-chevron-down arrow"></i>
                <div class="tooltip">Company</div>
            </div>
            <ul class="submenu {{ $isCompanyMenuActive ? 'show' : '' }}">
                @if(auth()->user()->canAccessMenuItem('company.branches'))
                <li data-tooltip="Master Branch" class="{{ request()->routeIs('company.branches.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('company.branches.index') }}')">Master Branch</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('company.positions'))
                <li data-tooltip="Master Position" class="{{ request()->routeIs('system.positions.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.positions.index') }}')">Master Position</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('company.master-options'))
                <li data-tooltip="Master Options" class="{{ request()->routeIs('other.master-options.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('other.master-options.index') }}')">Master Options</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('company.master-banks'))
                <li data-tooltip="Master Bank" class="{{ request()->routeIs('company.master-banks.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('company.master-banks.index') }}')">Master Bank</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('company.bank-payments'))
                    @unless(auth()->user()->hasRole('Marketing') && auth()->user()->position_name === 'Staff')
                    <li data-tooltip="Bank Payment" class="{{ request()->routeIs('company.bank-payments.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('company.bank-payments.index') }}')">Bank Payment</li>
                    @endunless
                @endif
                @if(auth()->user()->canAccessMenuItem('company.master-price-slabs'))
                <li data-tooltip="Master Price Slab" class="{{ request()->routeIs('company.master-price-slabs.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('company.master-price-slabs.index') }}')">Master Price Slab</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('company.companies'))
                <li data-tooltip="Master Company" class="{{ request()->routeIs('company.companies.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('company.companies.index') }}')">Master Company</li>
                @endif
            </ul>
            @endif

            <!-- System - Check by permission -->
            @if(auth()->user()->canAccessModule('system'))
            <div class="menu-item has-submenu {{ request()->routeIs('system.*', 'audit-trails.*', 'access-control.*', 'settings.system.*') ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                <div class="left">
                    <i class="fas fa-gear"></i>
                    <span>System</span>
                </div>
                <i class="fas fa-chevron-down arrow"></i>
                <div class="tooltip">System</div>
            </div>
            <ul class="submenu {{ request()->routeIs('system.*', 'audit-trails.*', 'access-control.*', 'emergency-contacts.*', 'settings.system.*') ? 'show' : '' }}">
                @if(auth()->user()->canAccessMenuItem('system.departments'))
                <li data-tooltip="Master Department" class="{{ request()->routeIs('system.departments.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.departments.index') }}')">Master Department</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('system.users'))
                <li data-tooltip="Master Users" class="{{ request()->routeIs('system.users.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.users.index') }}')">Master Users</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('system.roles'))
                <li data-tooltip="Master Roles" class="{{ request()->routeIs('system.roles.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.roles.index') }}')">Master Roles</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('system.access-control'))
                <li data-tooltip="Hirarki Data" class="{{ request()->routeIs('access-control.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('access-control.index') }}')">Hirarki Data</li>
                @endif
                {{-- MOM10: Department Roles and Position Roles hidden - using Individual Role only --}}
                {{-- <li data-tooltip="Department Roles" class="{{ request()->routeIs('system.department-roles.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.department-roles.index') }}')">Department Roles</li> --}}
                {{-- <li data-tooltip="Position Roles" class="{{ request()->routeIs('system.position-roles.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.position-roles.index') }}')">Position Roles</li> --}}
                @if(auth()->user()->canAccessMenuItem('system.master-term-of-payments'))
                <li data-tooltip="Master Term of Payment" class="{{ request()->routeIs('system.master-term-of-payments.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.master-term-of-payments.index') }}')">Master Term of Payment</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('system.provinces'))
                <li data-tooltip="Master Location" class="{{ request()->routeIs('system.provinces.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.provinces.index') }}')">Master Location</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('system.audit-trails'))
                <li data-tooltip="Audit Trails" class="{{ request()->routeIs('audit-trails.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('audit-trails.index') }}')">Audit Trails</li>
                @endif
                @if(auth()->user()->canAccessMenuItem('system.backup-restore'))
                <li data-tooltip="Backup (Import/Export)" class="{{ request()->routeIs('system.backup-restore.*') || request()->routeIs('system.catalyst-import.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('system.backup-restore.index') }}')">Backup (Import/Export)</li>
                @endif
                <li data-tooltip="System Settings" class="{{ request()->routeIs('settings.system.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('settings.system.index') }}')">System Settings</li>
            </ul>
            @endif

            <!-- Report - Available for all roles -->
            <div class="menu-item has-submenu {{ request()->routeIs('report.*') ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                <div class="left">
                    <i class="fas fa-chart-line"></i>
                    <span>Report</span>
                </div>
                <i class="fas fa-chevron-down arrow"></i>
                <div class="tooltip">Report</div>
            </div>
            <ul class="submenu {{ request()->routeIs('report.*') ? 'show' : '' }}">
                @if(auth()->user()->hasPermission('reports.warehouse'))
                <li data-tooltip="Warehouse Report" class="{{ request()->routeIs('report.warehouse.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('report.warehouse.index') }}')">Warehouse Report</li>
                @endif
                @if(auth()->user()->hasPermission('reports.operational'))
                <li data-tooltip="Operational Report" class="{{ request()->routeIs('report.operational.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('report.operational.index') }}')">Operational Report</li>
                @endif
                @if(auth()->user()->hasPermission('reports.finance'))
                <li data-tooltip="Finance Report" class="{{ request()->routeIs('report.finance.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('report.finance.index') }}')">Finance Report</li>
                @endif
                @if(auth()->user()->hasPermission('reports.marketing'))
                <li data-tooltip="Marketing Report" class="{{ request()->routeIs('report.marketing.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('report.marketing.index') }}')">Marketing Report</li>
                @endif
            </ul>

            <!-- Settings -->
           <!-- <div class="menu-item has-submenu {{ request()->routeIs('setting.*') ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                <div class="left">
                    <i class="fas fa-sliders-h"></i>
                    <span>Settings</span>
                </div>
                <i class="fas fa-chevron-down arrow"></i>
                <div class="tooltip">Settings</div>
            </div>
            <ul class="submenu {{ request()->routeIs('setting.*') ? 'show' : '' }}">
                <li data-tooltip="Theme Settings" class="{{ request()->routeIs('setting.theme-settings.*') ? 'active' : '' }}" onclick="navigateTo('{{ route('setting.theme-settings.index') }}')">Theme Settings</li>
            </ul> -->
        </div>

        <div class="bottom">
            <div class="menu-item" onclick="navigateTo('{{ route('profile.index') }}')">
                <div class="left">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </div>
                <div class="tooltip">My Profile</div>
            </div>
            <div class="menu-item" onclick="logout()">
                <div class="left">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </div>
                <div class="tooltip">Logout</div>
            </div>
            
            <!-- Fallback logout link for users with JavaScript disabled -->
            <a href="{{ route('logout.get') }}" class="menu-item" style="display: none;" id="fallbackLogout">
                <div class="left">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout (Direct)</span>
                </div>
                <div class="tooltip">Logout</div>
            </a>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <!-- Header matching marketing.html design -->
        <div class="content-header">
            <div class="header-inner">
                <div class="header-top">
                    <div class="header-left">
                        <h1 class="header-title">@yield('title', 'Dashboard')</h1>
                        <p class="header-breadcrumb">@yield('breadcrumb', 'Home')</p>
                    </div>
                    
                    <div class="header-right">
                        <div class="user-profile">
                            <div class="user-avatar">
                                @if(auth()->user()->photo_file_path)
                                    <img src="{{ asset('uploads/' . auth()->user()->photo_file_path) }}" alt="{{ auth()->user()->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                @else
                                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                                @endif
                            </div>
                            <div class="user-info">
                                <div class="user-name">{{ auth()->user()->name ?? 'User' }}</div>
                                <div class="user-role">{{ auth()->user()->roles->pluck('name')->implode(', ') ?? 'Staff' }} | {{ auth()->user()->department_name ?? 'Department' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- jQuery already loaded once in <head>; do not reload here (avoids resetting plugins) -->

    <!-- Removed DataTables CSS/JS to preserve existing UI theme -->

    <!-- Select2 CDN (single standardized stable version) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    
    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Axios CDN -->
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function normalizeNotificationArgs(firstArg, secondArg) {
            const validIcons = ['success', 'error', 'warning', 'info', 'question'];
            let type = 'info';
            let message = '';

            if (validIcons.includes(firstArg)) {
                type = firstArg;
                message = secondArg || '';
            } else if (validIcons.includes(secondArg)) {
                type = secondArg;
                message = firstArg || '';
            } else {
                message = firstArg || secondArg || '';
            }

            return { type, message };
        }

        // Global Notification Function
        function showNotification(firstArg, secondArg) {
            const { type, message } = normalizeNotificationArgs(firstArg, secondArg);
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                Toast.fire({
                    icon: type,
                    title: message
                });
            } else {
                console.log(type + ': ' + message);
            }
        }

        function showAlertDialog({
            title = 'Informasi',
            text = '',
            icon = 'info',
            confirmButtonText = 'Tutup',
            confirmButtonColor = '#214589',
            allowOutsideClick = true,
            allowEscapeKey = true
        } = {}) {
            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    title,
                    text,
                    icon,
                    confirmButtonText,
                    confirmButtonColor,
                    allowOutsideClick,
                    allowEscapeKey
                });
            }

            alert(text || title);
            return Promise.resolve();
        }

        function showSuccessDialog(message, title = 'Berhasil') {
            return showAlertDialog({ title, text: message, icon: 'success' });
        }

        function showErrorDialog(message, title = 'Terjadi Kesalahan') {
            return showAlertDialog({ title, text: message, icon: 'error' });
        }

        function showWarningDialog(message, title = 'Peringatan') {
            return showAlertDialog({ title, text: message, icon: 'warning' });
        }

        function showInfoDialog(message, title = 'Informasi') {
            return showAlertDialog({ title, text: message, icon: 'info' });
        }

        function showConfirmDialog(options = {}, legacyText, legacyConfirmButtonText, legacyCancelButtonText) {
            const isLegacySignature = typeof options !== 'object' || options === null || Array.isArray(options);
            const config = isLegacySignature ? {
                title: options || 'Apakah Anda yakin?',
                text: legacyText || '',
                icon: 'question',
                confirmButtonText: legacyConfirmButtonText || 'Ya, lanjutkan',
                cancelButtonText: legacyCancelButtonText || 'Batal',
                confirmButtonColor: '#214589',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: true,
                allowEscapeKey: true
            } : {
                title: 'Apakah Anda yakin?',
                text: '',
                icon: 'question',
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#214589',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: true,
                allowEscapeKey: true,
                ...options
            };

            if (typeof Swal !== 'undefined') {
                const confirmation = Swal.fire({
                    title: config.title,
                    text: config.text,
                    icon: config.icon,
                    showCancelButton: true,
                    confirmButtonText: config.confirmButtonText,
                    cancelButtonText: config.cancelButtonText,
                    confirmButtonColor: config.confirmButtonColor,
                    cancelButtonColor: config.cancelButtonColor,
                    allowOutsideClick: config.allowOutsideClick,
                    allowEscapeKey: config.allowEscapeKey
                });

                return isLegacySignature ? confirmation.then((result) => result.isConfirmed === true) : confirmation;
            }

            const confirmed = confirm(config.text || config.title);
            return Promise.resolve(isLegacySignature ? confirmed : { isConfirmed: confirmed });
        }

        window.nativeAlert = window.alert.bind(window);
        window.alert = function(message) {
            return showAlertDialog({
                title: 'Informasi',
                text: message ?? '',
                icon: 'info'
            });
        };


        // Set up CSRF token for AJAX requests
        window.axios = axios;
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        
        // Function to get CSRF token
        function getCsrfToken() {
            const tokenMeta = document.head.querySelector('meta[name="csrf-token"]');
            return tokenMeta ? tokenMeta.getAttribute('content') : null;
        }
        
        // Function to update CSRF token
        function updateCsrfToken(newToken) {
            const tokenMeta = document.head.querySelector('meta[name="csrf-token"]');
            if (tokenMeta && newToken) {
                tokenMeta.setAttribute('content', newToken);
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
                document.querySelectorAll('input[name="_token"]').forEach(input => {
                    input.value = newToken;
                });
            }
        }
        
        // Initialize CSRF token
        let token = getCsrfToken();
        if (token) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
        
        // Interceptor untuk auto-refresh CSRF token dan handle 419 error
        window.axios.interceptors.response.use(
            function (response) {
                // Update CSRF token dari response header jika ada
                const newToken = response.headers['x-csrf-token'] || response.headers['X-CSRF-TOKEN'];
                if (newToken) {
                    updateCsrfToken(newToken);
                }
                return response;
            },
            function (error) {
                // Handle 419 (CSRF Token Mismatch / Page Expired)
                if (error.response && error.response.status === 419) {
                    // Coba refresh token dari response
                    const newToken = error.response.headers['x-csrf-token'] || error.response.headers['X-CSRF-TOKEN'];
                    if (newToken) {
                        updateCsrfToken(newToken);
                        // Retry request dengan token baru
                        const config = error.config;
                        config.headers['X-CSRF-TOKEN'] = newToken;
                        return window.axios.request(config);
                    } else {
                        // Jika tidak ada token baru, reload halaman untuk mendapatkan token baru
                        showConfirmDialog({
                            title: 'Sesi Berakhir',
                            text: 'Sesi Anda sudah berakhir. Muat ulang halaman untuk melanjutkan?',
                            icon: 'warning',
                            confirmButtonText: 'Muat Ulang',
                            cancelButtonText: 'Nanti'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                    }
                }
                return Promise.reject(error);
            }
        );
        
        function applyCsrfTokenToFetchOptions(options, token, force = false) {
            if (!options.headers) {
                options.headers = {};
            }

            const headers = new Headers(options.headers);
            if (force || (!headers.has('X-CSRF-TOKEN') && !headers.has('x-csrf-token'))) {
                headers.set('X-CSRF-TOKEN', token);
            }
            options.headers = headers;

            if (options.body instanceof FormData) {
                options.body.set('_token', token);
                return options;
            }

            if (typeof options.body === 'string' && headers.get('Content-Type')?.includes('application/json')) {
                try {
                    const payload = JSON.parse(options.body);
                    payload._token = token;
                    options.body = JSON.stringify(payload);
                } catch (error) {
                    // Keep original body when it is not valid JSON.
                }
            }

            return options;
        }

        let csrfRefreshPromise = null;
        function refreshCsrfToken() {
            if (!csrfRefreshPromise) {
                csrfRefreshPromise = originalFetch('/csrf-token', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Unable to refresh CSRF token');
                        }

                        const headerToken = response.headers.get('x-csrf-token') || response.headers.get('X-CSRF-TOKEN');
                        return response.json()
                            .catch(() => ({}))
                            .then(data => data.token || headerToken);
                    })
                    .then(newToken => {
                        if (!newToken) {
                            throw new Error('CSRF token refresh returned empty token');
                        }

                        updateCsrfToken(newToken);
                        return newToken;
                    })
                    .finally(() => {
                        csrfRefreshPromise = null;
                    });
            }

            return csrfRefreshPromise;
        }

        // Global fetch wrapper untuk handle CSRF token refresh
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const url = args[0];
            const options = args[1] || {};
            args[1] = options;
            
            // Ensure CSRF token is included
            const token = getCsrfToken();
            if (token) {
                applyCsrfTokenToFetchOptions(options, token);
            }
            
            return originalFetch.apply(this, args)
                .then(response => {
                    // Update CSRF token dari response header jika ada
                    const newToken = response.headers.get('x-csrf-token') || response.headers.get('X-CSRF-TOKEN');
                    if (newToken) {
                        updateCsrfToken(newToken);
                    }
                    
                    // Handle 419 error
                    if (response.status === 419) {
                        return refreshCsrfToken()
                            .then(refreshToken => {
                                applyCsrfTokenToFetchOptions(options, refreshToken, true);
                                return originalFetch.apply(this, args);
                            })
                            .catch(() => {
                                // Reload halaman jika token tidak bisa diperbarui
                                showConfirmDialog({
                                    title: 'Sesi Berakhir',
                                    text: 'Sesi Anda sudah berakhir. Muat ulang halaman untuk melanjutkan?',
                                    icon: 'warning',
                                    confirmButtonText: 'Muat Ulang',
                                    cancelButtonText: 'Nanti'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                                return Promise.reject(new Error('CSRF token expired'));
                            });
                    }
                    
                    return response;
                });
        };

        function toggleSidebar() {
            // Only work on desktop (screen width > 1024px)
            if (window.innerWidth <= 1024) {
                return; // Don't allow collapse on mobile
            }
            
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const collapseBtn = document.getElementById('collapseBtn');
            const collapseIcon = collapseBtn.querySelector('i');
            const logoImg = document.querySelector('.logo img');
            
            // Simple toggle without complex timing
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
            
            // Simple icon rotation
            if (sidebar.classList.contains('collapsed')) {
                collapseIcon.style.transform = 'rotate(180deg)';
                if (logoImg) {
                    logoImg.src = "{{ asset('theme/assets/images/logo-b.svg') }}";
                }
            } else {
                collapseIcon.style.transform = 'rotate(0deg)';
                if (logoImg) {
                    logoImg.src = "{{ asset('theme/assets/images/img_logo.svg') }}";
                }
            }
        }

        function toggleSubmenu(element) {
            // Close all other submenus first (accordion behavior)
            const allSubmenus = document.querySelectorAll('.submenu');
            const allMenuItems = document.querySelectorAll('.menu-item.has-submenu');
            
            allSubmenus.forEach(submenu => {
                if (submenu !== element.nextElementSibling) {
                    submenu.classList.remove('show');
                }
            });
            
            allMenuItems.forEach(menuItem => {
                if (menuItem !== element) {
                    menuItem.classList.remove('open');
                }
            });
            
            // Toggle current submenu
            const submenu = element.nextElementSibling;
            const arrow = element.querySelector('.arrow');
            
            element.classList.toggle('open');
            submenu.classList.toggle('show');
        }

        function navigateTo(url, event) {
            // Close mobile menu if open
            closeMobileMenu();
            
            // Check if Ctrl key is pressed (or Cmd on Mac)
            if (event && (event.ctrlKey || event.metaKey)) {
                // Create a temporary link element to open in new tab
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                return false; // Prevent default behavior
            } else {
                // Normal navigation
                window.location.href = url;
            }
        }

        // Add event listeners for Ctrl+click support on all menu items
        document.addEventListener('DOMContentLoaded', function() {
            // Handle menu items with onclick="navigateTo()"
            document.addEventListener('click', function(event) {
                const target = event.target.closest('[onclick*="navigateTo"]');
                if (target) {
                    const onclickAttr = target.getAttribute('onclick');
                    if (onclickAttr && onclickAttr.includes('navigateTo')) {
                        // Check if Ctrl key is pressed (or Cmd on Mac)
                        if (event.ctrlKey || event.metaKey) {
                            event.preventDefault();
                            event.stopPropagation();
                            
                            // Extract URL from onclick attribute
                            const urlMatch = onclickAttr.match(/navigateTo\('([^']+)'\)/);
                            if (urlMatch) {
                                const url = urlMatch[1];
                                
                                // Create a temporary link element to open in new tab
                                const link = document.createElement('a');
                                link.href = url;
                                link.target = '_blank';
                                link.rel = 'noopener noreferrer';
                                link.style.display = 'none';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                            }
                            return false;
                        }
                    }
                }
            }, true); // Use capture phase to intercept before onclick
        });

        // Mobile menu functions
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const menuToggle = document.getElementById('mobileMenuToggle');
            
            // Toggle mobile menu
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            
            // Change icon
            if (sidebar.classList.contains('show')) {
                menuToggle.innerHTML = '<i class="fas fa-times"></i>';
                menuToggle.title = 'Close Menu';
            } else {
                menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                menuToggle.title = 'Open Menu';
            }
        }

        function closeMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const menuToggle = document.getElementById('mobileMenuToggle');
            
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            menuToggle.title = 'Open Menu';
        }

        // Close mobile menu when window is resized to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                closeMobileMenu();
            }
        });

        function logout() {
            showLogoutModal();
        }

        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Add animation
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }

        function hideLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
            
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function confirmLogout() {
            try {
                // Create a form and submit it via POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('logout') }}';
                
                // Add CSRF token
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                document.body.appendChild(form);
                form.submit();
            } catch (error) {
                console.error('Logout form submission failed:', error);
                // Fallback: redirect to GET logout route
                window.location.href = '{{ route('logout.get') }}';
            }
        }

        // Add event listener for backdrop click and ESC key
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('logoutModal');
            
            // Check if CSRF token is available
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.warn('CSRF token not found, logout may not work properly');
                // Show fallback logout option
                const fallbackLogout = document.getElementById('fallbackLogout');
                if (fallbackLogout) {
                    fallbackLogout.style.display = 'block';
                }
            }
            
            // Close modal when clicking backdrop
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    hideLogoutModal();
                }
            });
            
            // Close modal with ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'flex') {
                    hideLogoutModal();
                }
            });
        });

        // Simple tooltip initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Add tooltips to menu items
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach(item => {
                const span = item.querySelector('span');
                if (span && span.textContent.trim()) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip';
                    tooltip.textContent = span.textContent.trim();
                    item.appendChild(tooltip);
                }
            });
            
            // Add tooltips to submenu items
            const submenuItems = document.querySelectorAll('.submenu li');
            submenuItems.forEach(item => {
                const tooltipText = item.getAttribute('data-tooltip') || item.textContent.trim();
                if (tooltipText) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip';
                    tooltip.textContent = tooltipText;
                    item.appendChild(tooltip);
                }
            });
        });



        // Global AJAX error handler
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });

        // Show loading on form submissions
        document.addEventListener('DOMContentLoaded', function() {
            // Global Integer Input Formatting
            // Helper function to format input value
            function formatIntegerInput(input) {
                if (input.type === 'number' && input.value && input.value.includes('.00')) {
                    input.value = input.value.replace('.00', '');
                }
            }

            // 1. Format existing inputs
            document.querySelectorAll('input[type="number"]').forEach(formatIntegerInput);

            // 2. Watch for new inputs (dynamic content)
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Check if node itself is an input
                            if (node.matches && node.matches('input[type="number"]')) {
                                formatIntegerInput(node);
                            }
                            // Check children of the node
                            if (node.querySelectorAll) {
                                node.querySelectorAll('input[type="number"]').forEach(formatIntegerInput);
                            }
                        }
                    });
                });
            });

            // Start observing the body
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });

        // Context Menu Functionality
        let contextMenuTarget = null;
        let contextMenuUrl = null;

        function showContextMenu(event, target, url = null) {
            event.preventDefault();
            event.stopPropagation();
            
            const contextMenu = document.getElementById('contextMenu');
            const x = event.clientX;
            const y = event.clientY;
            
            // Position the context menu
            contextMenu.style.left = x + 'px';
            contextMenu.style.top = y + 'px';
            
            // Store target and URL for context menu actions
            contextMenuTarget = target;
            contextMenuUrl = url || window.location.href;
            
            // Check if this is a parent menu item (no direct URL)
            const hasSubmenu = target.classList.contains('has-submenu') || target.nextElementSibling?.classList.contains('submenu');
            const hasDirectUrl = target.getAttribute('onclick') && target.getAttribute('onclick').includes('navigateTo');
            
            // Show/hide context menu items based on menu type
            const newTabItem = contextMenu.querySelector('[onclick="contextMenuAction(\'newTab\')"]');
            const bookmarkItem = contextMenu.querySelector('[onclick="contextMenuAction(\'bookmark\')"]');
            const copyItem = contextMenu.querySelector('[onclick="contextMenuAction(\'copy\')"]');
            
            if (hasDirectUrl) {
                // Show all options for items with direct URLs
                newTabItem.style.display = 'flex';
                newTabItem.innerHTML = '<i class="fas fa-external-link-alt"></i> Open in New Tab';
                bookmarkItem.style.display = 'flex';
                copyItem.style.display = 'flex';
            } else if (hasSubmenu) {
                // For parent menu items, check if we found a submenu URL
                newTabItem.style.display = 'flex';
                if (contextMenuUrl && contextMenuUrl !== window.location.href) {
                    newTabItem.innerHTML = '<i class="fas fa-external-link-alt"></i> Open First Submenu in New Tab';
                } else {
                    newTabItem.innerHTML = '<i class="fas fa-external-link-alt"></i> Open Current Page in New Tab';
                }
                bookmarkItem.style.display = 'flex';
                copyItem.style.display = 'flex';
            } else {
                // For other items, show all options
                newTabItem.style.display = 'flex';
                newTabItem.innerHTML = '<i class="fas fa-external-link-alt"></i> Open in New Tab';
                bookmarkItem.style.display = 'flex';
                copyItem.style.display = 'flex';
            }
            
            // Debug logging
            console.log('Context menu triggered:', {
                target: target,
                url: contextMenuUrl,
                onclick: target.getAttribute('onclick'),
                hasSubmenu: hasSubmenu,
                hasDirectUrl: hasDirectUrl
            });
            
            // Show context menu
            contextMenu.classList.add('show');
            
            // Adjust position if menu goes off screen
            setTimeout(() => {
                const rect = contextMenu.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;
                
                if (rect.right > viewportWidth) {
                    contextMenu.style.left = (x - rect.width) + 'px';
                }
                
                if (rect.bottom > viewportHeight) {
                    contextMenu.style.top = (y - rect.height) + 'px';
                }
            }, 0);
        }

        function hideContextMenu() {
            const contextMenu = document.getElementById('contextMenu');
            contextMenu.classList.remove('show');
            
            // Reset context menu items to default state
            const newTabItem = contextMenu.querySelector('[onclick="contextMenuAction(\'newTab\')"]');
            if (newTabItem) {
                newTabItem.innerHTML = '<i class="fas fa-external-link-alt"></i> Open in New Tab';
                newTabItem.style.display = 'flex';
            }
            
            // Show tooltips again
            const tooltips = document.querySelectorAll('.tooltip');
            tooltips.forEach(tooltip => tooltip.style.display = '');
            
            contextMenuTarget = null;
            contextMenuUrl = null;
        }

        function contextMenuAction(action) {
            // Store URL before hiding menu
            const urlToUse = contextMenuUrl;
            console.log('contextMenuAction called with action:', action, 'URL to use:', urlToUse);
            
            hideContextMenu();
            
            switch(action) {
                case 'refresh':
                    window.location.reload();
                    break;
                    
                case 'newTab':
                    console.log('newTab action triggered, urlToUse:', urlToUse);
                    if (urlToUse && urlToUse.trim() !== '') {
                        console.log('Opening URL in new tab:', urlToUse);
                        const newWindow = window.open(urlToUse, '_blank');
                        if (!newWindow) {
                            // Fallback if popup blocked
                            showWarningDialog('Popup diblokir. Izinkan popup untuk situs ini atau salin URL secara manual.');
                        }
                    } else {
                        console.log('No URL available for new tab, using current page URL');
                        // Instead of showing error, use current page URL
                        const currentUrl = window.location.href;
                        console.log('Using current page URL:', currentUrl);
                        const newWindow = window.open(currentUrl, '_blank');
                        if (!newWindow) {
                            showWarningDialog('Popup diblokir. Izinkan popup untuk situs ini atau salin URL secara manual.');
                        }
                    }
                    break;
                    
                case 'bookmark':
                    if (urlToUse) {
                        // Try to add bookmark (works in some browsers)
                        try {
                            window.external.AddFavorite(urlToUse, document.title);
                        } catch(e) {
                            // Fallback: copy URL to clipboard
                            navigator.clipboard.writeText(urlToUse).then(() => {
                                showSuccessDialog('URL berhasil disalin ke clipboard. Silakan simpan bookmark secara manual.', 'Berhasil Disalin');
                            });
                        }
                    }
                    break;
                    
                case 'copy':
                    if (urlToUse) {
                        navigator.clipboard.writeText(urlToUse).then(() => {
                            // Show brief success message
                            const notification = document.createElement('div');
                            notification.style.cssText = `
                                position: fixed;
                                top: 20px;
                                right: 20px;
                                background: #28a745;
                                color: white;
                                padding: 10px 15px;
                                border-radius: 5px;
                                z-index: 10001;
                                font-size: 14px;
                            `;
                            notification.textContent = 'Link copied to clipboard!';
                            document.body.appendChild(notification);
                            
                            setTimeout(() => {
                                notification.remove();
                            }, 2000);
                        });
                    }
                    break;
                    
                case 'inspect':
                    if (contextMenuTarget) {
                        // Highlight the element briefly
                        const originalStyle = contextMenuTarget.style.cssText;
                        contextMenuTarget.style.cssText += 'outline: 2px solid #007bff !important; background-color: rgba(0,123,255,0.1) !important;';
                        
                        setTimeout(() => {
                            contextMenuTarget.style.cssText = originalStyle;
                        }, 2000);
                        
                        // Open browser dev tools (if possible)
                        console.log('Element:', contextMenuTarget);
                        console.log('URL:', contextMenuUrl);
                    }
                    break;
                    
                case 'logout':
                    logout();
                    break;
            }
        }

        // Add right-click event listeners to sidebar items
        document.addEventListener('DOMContentLoaded', function() {
            // Add context menu to all menu items
            const menuItems = document.querySelectorAll('.menu-item, .submenu li');
            menuItems.forEach(function(item) {
                item.addEventListener('contextmenu', function(event) {
                    // Prevent default context menu and tooltip
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Hide any existing tooltips
                    const tooltips = document.querySelectorAll('.tooltip');
                    tooltips.forEach(tooltip => tooltip.style.display = 'none');
                    
                    // Get the URL from onclick attribute
                    let url = null;
                    const onclick = item.getAttribute('onclick');
                    
                    if (onclick && onclick.includes('navigateTo')) {
                        // Extract URL from navigateTo function call - handle both single and double quotes
                        const urlMatch = onclick.match(/navigateTo\(['"]([^'"]+)['"]\)/);
                        if (urlMatch) {
                            url = urlMatch[1];
                            console.log('Extracted URL from onclick:', url);
                        } else {
                            console.log('Failed to extract URL from onclick:', onclick);
                        }
                    }
                    
                    // If no URL found, check if it's a parent menu item
                    if (!url) {
                        // Check if this is a parent menu item (has submenu)
                        const hasSubmenu = item.classList.contains('has-submenu') || item.nextElementSibling?.classList.contains('submenu');
                        
                        if (hasSubmenu) {
                            // For parent menu items, try to find the first submenu item with a URL
                            const submenu = item.nextElementSibling;
                            if (submenu && submenu.classList.contains('submenu')) {
                                const firstSubmenuItem = submenu.querySelector('li[onclick]');
                                if (firstSubmenuItem) {
                                    const submenuOnclick = firstSubmenuItem.getAttribute('onclick');
                                    const submenuUrlMatch = submenuOnclick.match(/navigateTo\(['"]([^'"]+)['"]\)/);
                                    if (submenuUrlMatch) {
                                        url = submenuUrlMatch[1];
                                        console.log('Using first submenu URL:', url);
                                    }
                                }
                            }
                            
                            // If still no URL, use current page URL
                            if (!url) {
                                url = window.location.href;
                                console.log('Using current page URL for parent menu');
                            }
                        } else {
                            // For regular menu items without URL, use current page
                            url = window.location.href;
                            console.log('Using current page URL for regular menu');
                        }
                    }
                    
                    showContextMenu(event, item, url);
                });
            });
            
            // Hide context menu when clicking elsewhere
            document.addEventListener('click', function(event) {
                if (!event.target.closest('#contextMenu')) {
                    hideContextMenu();
                }
            });
            
            // Hide context menu on escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    hideContextMenu();
                }
            });
            
            // Prevent context menu on context menu itself
            const contextMenu = document.getElementById('contextMenu');
            contextMenu.addEventListener('contextmenu', function(event) {
                event.preventDefault();
                event.stopPropagation();
            });
        });
    </script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('scripts')

    <script>
    (function() {
        'use strict';

        window.AromaTableState = window.AromaTableState || {
            sortKeys: ['sort', 'direction'],
            paramsWithCurrentSort: function() {
                var currentParams = new URLSearchParams(window.location.search);
                var nextParams = new URLSearchParams();

                this.sortKeys.forEach(function(key) {
                    if (currentParams.has(key)) {
                        nextParams.set(key, currentParams.get(key));
                    }
                });

                return nextParams;
            },
            applyCurrentSort: function(params) {
                var nextParams = params || new URLSearchParams();
                var currentParams = new URLSearchParams(window.location.search);

                this.sortKeys.forEach(function(key) {
                    if (!nextParams.has(key) && currentParams.has(key)) {
                        nextParams.set(key, currentParams.get(key));
                    }
                });

                return nextParams;
            }
        };

        if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
            jQuery.extend(true, jQuery.fn.dataTable.defaults, {
                stateSave: true,
                stateDuration: 0
            });
        }
    })();
    </script>

    <!-- Global Modal Protection: Prevent closing modal when clicking outside -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============================================
        // 1. CUSTOM MODALS (modal-overlay with onclick)
        // ============================================
        const modalBackdropSelector = [
            '.modal-overlay',
            '[class*="modal-overlay"]',
            '[class*="ModalOverlay"]',
            '[id*="modalOverlay"]',
            '[id*="ModalOverlay"]',
            '.h-modal'
        ].join(', ');

        // Capture backdrop clicks before page-level handlers can close custom modals.
        document.addEventListener('click', function(event) {
            const target = event.target;

            if (target instanceof Element && target.matches(modalBackdropSelector)) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);

        // Remove onclick handlers from modal overlays
        function removeModalOverlayClickHandlers() {
            document.querySelectorAll('.modal-overlay, [class*="ModalOverlay"], [id*="modalOverlay"], [id*="ModalOverlay"]').forEach(function(overlay) {
                // Remove inline onclick
                overlay.removeAttribute('onclick');
                
                // Clone and replace to remove all event listeners
                const newOverlay = overlay.cloneNode(true);
                newOverlay.removeAttribute('onclick');
                
                // Re-add stopPropagation to inner container
                const container = newOverlay.querySelector('.modal-container, .modal-content, [class*="modal-container"], [class*="ModalContainer"]');
                if (container) {
                    container.onclick = function(e) { e.stopPropagation(); };
                }
                
                if (overlay.parentNode) {
                    overlay.parentNode.replaceChild(newOverlay, overlay);
                }
            });
        }
        
        // Run on page load
        removeModalOverlayClickHandlers();
        
        // Watch for dynamically added modals
        const observer = new MutationObserver(function(mutations) {
            let hasNewOverlay = false;
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        // Safely check node.id (it might be SVGAnimatedString for SVG elements)
                        const nodeId = typeof node.id === 'string' ? node.id : '';
                        if (node.classList && (node.classList.contains('modal-overlay') || nodeId.includes('modalOverlay') || nodeId.includes('ModalOverlay'))) {
                            hasNewOverlay = true;
                        }
                        if (node.querySelectorAll) {
                            try {
                                const overlays = node.querySelectorAll('.modal-overlay, [id*="modalOverlay"], [id*="ModalOverlay"]');
                                if (overlays.length > 0) hasNewOverlay = true;
                            } catch (e) {
                                // Ignore selector errors
                            }
                        }
                    }
                });
            });
            if (hasNewOverlay) {
                setTimeout(removeModalOverlayClickHandlers, 10);
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
        
        // ============================================
        // 2. BOOTSTRAP 5 MODALS
        // ============================================
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const OriginalModal = bootstrap.Modal;
            bootstrap.Modal = function(element, config) {
                config = config || {};
                config.backdrop = 'static';
                config.keyboard = false;
                return new OriginalModal(element, config);
            };
            bootstrap.Modal.getInstance = OriginalModal.getInstance;
            bootstrap.Modal.getOrCreateInstance = function(element, config) {
                config = config || {};
                config.backdrop = 'static';
                config.keyboard = false;
                return OriginalModal.getOrCreateInstance(element, config);
            };
            Object.keys(OriginalModal).forEach(function(key) {
                if (!(key in bootstrap.Modal)) {
                    bootstrap.Modal[key] = OriginalModal[key];
                }
            });
        }
        
        // Set static backdrop on Bootstrap modals
        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.setAttribute('data-bs-backdrop', 'static');
            modal.setAttribute('data-bs-keyboard', 'false');
        });
    });
    </script>
    
    <!-- Global Select2 Initialization for All Modals -->
    <style>
        /* Select2 Custom Styling to match form inputs */
        .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            padding: 6px 12px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            background-color: #fff !important;
            font-size: 14px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            padding-left: 0 !important;
            color: #374151 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #214589 !important;
            box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1) !important;
            outline: none !important;
        }
        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            z-index: 10060 !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            padding: 8px 12px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 4px !important;
            font-size: 14px !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #214589 !important;
            outline: none !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #214589 !important;
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #e0e7ff !important;
            color: #1e40af !important;
        }
        .select2-results__option {
            padding: 8px 12px !important;
            font-size: 14px !important;
        }
        /* Fix Select2 in modals - ensure dropdown appears above modal */
        .modal-body .select2-container--open .select2-dropdown {
            z-index: 10060 !important;
        }
        .select2-container--open {
            z-index: 10050 !important;
        }
        
        /* Select2 Multiple Selection Styling - Fix tag spacing */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            padding: 4px 8px !important;
            min-height: 44px !important;
            background-color: white !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #e0e7ff !important;
            border: 1px solid #6366f1 !important;
            border-radius: 4px !important;
            padding: 2px 8px !important;
            margin: 2px 4px 2px 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            font-size: 13px !important;
            color: #1e40af !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #4f46e5 !important;
            margin-right: 4px !important;
            font-size: 14px !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #dc2626 !important;
        }
        .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
            margin-top: 4px !important;
            font-size: 14px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border: 2px solid #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
        }
    </style>
    <script>
    $(document).ready(function() {
        // Function to initialize Select2 on select elements
        function initializeSelect2(container) {
            const $container = $(container || document);
            
            // Find ALL select elements (standardized: every dropdown is searchable)
            $container.find('select').each(function() {
                const $select = $(this);

                // Skip if already initialized or explicitly opted out
                if ($select.hasClass('select2-hidden-accessible')
                    || $select.hasClass('no-select2')
                    || this.hasAttribute('data-no-select2')) {
                    return;
                }

                // Skip flatpickr's internal month dropdown / any select inside a flatpickr calendar
                if ($select.hasClass('flatpickr-monthDropdown-months')
                    || $select.closest('.flatpickr-calendar').length) {
                    return;
                }

                // Skip master_building_status (should not use Select2)
                if ($select.attr('id') === 'master_building_status') {
                    return;
                }

                // Skip very small selects (like pagination) outside modals.
                // data-force-select2 opts a specific dropdown back in regardless of
                // option count - used by editable table cells (e.g. Material Assign's
                // unit-variant dropdown) so every row gets the same select2 styling
                // even when a row only has 2-3 variant options.
                if ($select.find('option').length <= 3
                    && !$select.closest('.modal-body, .modal-container').length
                    && !this.hasAttribute('data-force-select2')) {
                    return;
                }
                
                // Determine dropdown parent for proper z-index in modals
                let dropdownParent = $('body');
                const $modal = $select.closest('.modal, .modal-overlay, .modal-container');
                if ($modal.length) {
                    dropdownParent = $modal;
                }
                
                // Initialize Select2
                $select.select2({
                    placeholder: $select.find('option:first').text() || 'Select an option',
                    allowClear: !$select.prop('required'),
                    dropdownParent: dropdownParent,
                    width: '100%',
                    minimumResultsForSearch: 10 // Show search box only when there are >= 10 options
                });
            });
        }
        
        // Initialize on page load
        initializeSelect2();
        
        // Re-initialize when modal is shown (for Bootstrap modals)
        $(document).on('shown.bs.modal', '.modal', function() {
            initializeSelect2(this);
        });
        
        // Watch for dynamically added content (for custom modals)
        const select2Observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        // Check if the node itself is a select or contains selects
                        if (node.tagName === 'SELECT') {
                            setTimeout(function() { initializeSelect2(node.parentNode); }, 100);
                        } else if (node.querySelectorAll) {
                            const selects = node.querySelectorAll('select');
                            if (selects.length > 0) {
                                setTimeout(function() { initializeSelect2(node); }, 100);
                            }
                        }
                    }
                });
            });
        });
        
        // Observe modal containers for dynamic content
        select2Observer.observe(document.body, { childList: true, subtree: true });
        
        // Also watch for modal-body innerHTML changes
        $(document).on('DOMNodeInserted', '.modal-body, .modal-container', function() {
            setTimeout(function() { initializeSelect2(); }, 100);
        });
    });
    </script>

    <!-- Custom Logout Modal -->
    <div id="logoutModal" class="logout-modal-overlay">
        <div class="logout-modal-container">
            <div class="logout-modal-header">
                <div class="logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3 class="logout-title">Confirm Logout</h3>
                <p class="logout-subtitle">Are you sure you want to sign out?</p>
            </div>
            
            <div class="logout-modal-body">
                <div class="logout-user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name">{{ Auth::user()->name ?? 'User' }}</span>
                        <span class="user-email">{{ Auth::user()->email ?? '' }}</span>
                    </div>
                </div>
            </div>
            
            <div class="logout-modal-footer">
                <button type="button" class="logout-btn logout-btn-cancel" onclick="hideLogoutModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button type="button" class="logout-btn logout-btn-confirm" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </div>
    </div>

    <style>
        /* Custom Logout Modal Styles */
        .logout-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .logout-modal-overlay.show {
            opacity: 1;
        }

        .logout-modal-container {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            max-width: 420px;
            width: 90%;
            transform: scale(0.7) translateY(50px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-modal-overlay.show .logout-modal-container {
            transform: scale(1) translateY(0);
        }

        .logout-modal-header {
            text-align: center;
            padding: 40px 30px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
        }

        .logout-modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .logout-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            z-index: 1;
        }

        .logout-icon i {
            font-size: 32px;
            color: white;
        }

        .logout-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px;
            position: relative;
            z-index: 1;
        }

        .logout-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        .logout-modal-body {
            padding: 30px;
        }

        .logout-user-info {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .user-avatar i {
            font-size: 20px;
            color: white;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .user-name {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .user-email {
            font-size: 14px;
            color: #718096;
        }

        .logout-modal-footer {
            padding: 20px 30px 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .logout-btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .logout-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .logout-btn:hover::before {
            left: 100%;
        }

        .logout-btn-cancel {
            background: #e2e8f0;
            color: #4a5568;
            border: 2px solid transparent;
        }

        .logout-btn-cancel:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .logout-btn-confirm {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
            border: 2px solid transparent;
        }

        .logout-btn-confirm:hover {
            background: linear-gradient(135deg, #c53030, #9c2626);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
        }

        .logout-btn:active {
            transform: translateY(0);
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .logout-modal-container {
                margin: 20px;
                width: calc(100% - 40px);
            }
            
            .logout-modal-header {
                padding: 30px 20px 15px;
            }
            
            .logout-icon {
                width: 60px;
                height: 60px;
            }
            
            .logout-icon i {
                font-size: 24px;
            }
            
            .logout-title {
                font-size: 20px;
            }
            
            .logout-subtitle {
                font-size: 14px;
            }
            
            .logout-modal-body {
                padding: 20px;
            }
            
            .logout-modal-footer {
                padding: 15px 20px 25px;
                flex-direction: column;
            }
            
            .logout-btn {
                padding: 12px 16px;
                font-size: 14px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .logout-modal-container {
                background: linear-gradient(145deg, #2d3748, #1a202c);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .logout-user-info {
                background: #4a5568;
                border: 1px solid #2d3748;
            }
            
            .user-name {
                color: #3d3d3d;
            }
            
            .user-email {
                color: #a0aec0;
            }
        }
    </style>

    <script>
    // Open native date picker when any part of a date input is clicked.
    (function() {
        function openDateInputPicker(input) {
            if (!input || input.type !== 'date' || input.disabled || input.readOnly) return;

            if (typeof input.showPicker === 'function') {
                try {
                    input.showPicker();
                } catch (error) {
                    input.focus();
                }
            } else {
                input.focus();
            }
        }

        function markDateInputs(scope) {
            (scope || document).querySelectorAll('input[type="date"]').forEach(function(input) {
                input.style.cursor = 'pointer';
            });
        }

        document.addEventListener('click', function(event) {
            const input = event.target.closest('input[type="date"]');
            openDateInputPicker(input);
        });

        document.addEventListener('DOMContentLoaded', function() {
            markDateInputs(document);
        });

        document.addEventListener('table:enhance', function(event) {
            markDateInputs(event.detail && event.detail.scope ? event.detail.scope : document);
        });
    })();

    // Global Table Filter Injector with auto column detection
    (function() {
        let __autoTableId = 0;
        function initTableFilters(scope) {
            const tables = (scope || document).querySelectorAll('table');
            tables.forEach(function(table) {
                if (table.dataset.filterEnhanced === '1') return;
                const thead = table.tHead;
                if (!thead || thead.rows.length === 0) return;
                const headerRow = thead.rows[0];
                // Check if already has a second row with filter inputs
                if (thead.rows.length > 1 && thead.rows[1].classList.contains('filter-row')) return;
                const filterRow = thead.insertRow(1);
                filterRow.classList.add('filter-row');
                if (!table.id) {
                    __autoTableId += 1;
                    table.id = 'tableFilter' + __autoTableId;
                }
                const tableId = table.id;
                const url = new URL(window.location.href);
                for (let i = 0; i < headerRow.cells.length; i++) {
                    const th = headerRow.cells[i];
                    const cell = document.createElement('th');
                    cell.style.padding = '4px';
                    cell.style.backgroundColor = '#214589';
                    
                    // Match width from header cell if specified
                    if (th.style.width) {
                        cell.style.width = th.style.width;
                    }
                    if (th.width) {
                        cell.width = th.width;
                    }
                    // Copy class for width constraints
                    if (th.className) {
                        cell.className = th.className;
                    }
                    
                    // Fix Sticky Header Overlap
                    // Ensure filter row sticks BELOW the main header row
                    // Default fallback height is 45px (typical header height)
                    let headerHeight = 45;
                    if (headerRow.offsetHeight > 0) {
                        headerHeight = headerRow.offsetHeight;
                    }
                    
                    cell.style.position = 'sticky';
                    cell.style.top = headerHeight + 'px';
                    cell.style.zIndex = '9'; // Slightly below main header (z-index 10)

                    const computedStyle = window.getComputedStyle(th);
                    const isHiddenHeader = th.hidden || computedStyle.display === 'none' || computedStyle.visibility === 'hidden';
                    if (isHiddenHeader) {
                        cell.style.display = 'none';
                        filterRow.appendChild(cell);
                        continue;
                    }
                    
                    // Skip filter for columns with data-no-filter
                    const hasNoFilter = th.hasAttribute('data-no-filter');
                    const hasDataColumn = th.hasAttribute('data-column');
                    
                    if (!hasNoFilter && hasDataColumn) {
                        const columnName = th.getAttribute('data-column');
                        const columnType = th.getAttribute('data-type') || 'text';
                        
                        const input = document.createElement('input');
                        input.type = columnType === 'date' ? 'date' : 'text';
                        input.placeholder = columnType === 'date' ? 'Select date...' : 'Filter...';
                        input.style.width = '100%';
                        input.style.padding = '4px 6px';
                        input.style.fontSize = '12px';
                        input.style.border = '1px solid #d1d5db';
                        input.style.borderRadius = '4px';
                        input.style.backgroundColor = '#ffffff';
                        input.style.color = '#111827';
                        input.style.caretColor = '#111827';
                        input.style.boxSizing = 'border-box';
                        input.dataset.columnName = columnName;
                        input.dataset.columnType = columnType;
                        
                        // Convert dots to double underscores for PHP compatibility
                        // PHP converts dots in parameter names to underscores
                        const safeColumnName = columnName.replace(/\./g, '__');
                        
                        // Prefill from URL using safe column name
                        const key = 'filter[' + safeColumnName + ']';
                        const fromUrl = url.searchParams.get(key);
                        if (fromUrl) input.value = fromUrl;
                        
                        // Trigger filter on Enter key only
                        input.addEventListener('keydown', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                applyFilter(table, input);
                            }
                        });
                        if (columnType === 'date') {
                            input.style.cursor = 'pointer';
                            input.addEventListener('change', function() {
                                applyFilter(table, input);
                            });
                        }
                        cell.appendChild(input);
                    }
                    filterRow.appendChild(cell);
                }
                table.dataset.filterEnhanced = '1';
            });
        }

        function applyFilter(table, input) {
            const url = new URL(window.location.href);
            const tableId = table.id || 'table';
            const columnName = input.dataset.columnName;
            const term = input.value.trim();
            
            // Collect all active filters from this table
            const allInputs = table.querySelectorAll('.filter-row input[data-column-name]');
            let hasAnyFilter = false;
            
            allInputs.forEach(function(inp) {
                const col = inp.dataset.columnName;
                const val = inp.value.trim();
                // Convert dots to double underscores for PHP compatibility
                const safeCol = col.replace(/\./g, '__');
                const key = 'filter[' + safeCol + ']';
                
                if (val) {
                    url.searchParams.set(key, val);
                    hasAnyFilter = true;
                } else {
                    url.searchParams.delete(key);
                }
            });
            
            // Reset pagination on filter
            url.searchParams.set('page', '1');
            
            window.location.assign(url.toString());
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function(){
            initTableFilters(document);
        });

        // Re-init on AJAX content loads if your app injects HTML dynamically
        document.addEventListener('table:enhance', function(e){
            initTableFilters(e.detail && e.detail.scope ? e.detail.scope : document);
        });
    })();
    </script>

    <!-- ========================================
         GLOBAL LIST FILTER PERSISTENCE
         Mengingat filter + pagination halaman list (mis. ?filter[rental_code]=IIA&page=1)
         supaya saat user buka salah satu item lalu klik "Back to List" (atau Back),
         state filter tidak hilang. Berlaku global untuk semua halaman list+detail.
         ======================================== -->
    <script>
    (function() {
        'use strict';

        var STORAGE_PREFIX = 'listReturnUrl:';

        function storageKeyFor(path) {
            // Normalisasi: buang trailing slash supaya '/x' dan '/x/' sama.
            var clean = (path || '').replace(/\/+$/, '');
            return STORAGE_PREFIX + (clean || '/');
        }

        // Sebuah halaman dianggap "list" jika punya filter aktif / pagination di URL,
        // atau punya tabel yang di-enhance dengan kolom filter (data-column).
        function isListPage() {
            try {
                var params = new URL(window.location.href).searchParams;
                var hasFilterParam = false;
                params.forEach(function(_, key) {
                    if (key === 'page' || key === 'search' || key.indexOf('filter[') === 0) {
                        hasFilterParam = true;
                    }
                });
                if (hasFilterParam) return true;
            } catch (e) { /* ignore */ }
            return !!document.querySelector('table th[data-column]');
        }

        // Simpan URL list saat ini, dikunci oleh pathname-nya.
        function rememberCurrentListUrl() {
            if (!isListPage()) return;
            try {
                sessionStorage.setItem(
                    storageKeyFor(window.location.pathname),
                    window.location.href
                );
            } catch (e) { /* sessionStorage tidak tersedia */ }
        }

        // Untuk halaman detail: cari link "kembali ke list" lalu ganti href-nya dengan
        // URL list yang tersimpan (berisi filter). Deteksi TIDAK bergantung pada teks
        // tombol (yang bervariasi: "Back", "Back to List", "Kembali", dst.) melainkan
        // pada href yang mengarah ke path list yang URL-nya pernah kita simpan.
        function restoreBackToListLinks() {
            // Jangan rewrite link di halaman yang merupakan list itu sendiri.
            var currentPath = window.location.pathname.replace(/\/+$/, '');

            var links = document.querySelectorAll('a[href]');
            links.forEach(function(link) {
                var targetPath;
                try {
                    targetPath = new URL(link.href, window.location.origin).pathname;
                } catch (e) { return; }

                var cleanTarget = targetPath.replace(/\/+$/, '');

                // Lewati link yang menunjuk ke halaman saat ini sendiri.
                if (cleanTarget === currentPath) return;

                // Hanya proses jika link belum membawa query string (href polos ke index).
                var hasQuery;
                try {
                    hasQuery = new URL(link.href, window.location.origin).search.length > 0;
                } catch (e) { hasQuery = false; }
                if (hasQuery) return;

                var saved = null;
                try {
                    saved = sessionStorage.getItem(storageKeyFor(targetPath));
                } catch (e) { /* ignore */ }

                if (!saved) return;

                // Pastikan path tersimpan benar-benar untuk list ini (bukan kebetulan),
                // dan halaman saat ini adalah turunan dari list tsb (mis. detail item).
                try {
                    var savedPath = new URL(saved).pathname.replace(/\/+$/, '');
                    var isDescendant = currentPath === cleanTarget ||
                        currentPath.indexOf(cleanTarget + '/') === 0;
                    if (savedPath === cleanTarget && isDescendant) {
                        link.href = saved;
                    }
                } catch (e) { /* ignore */ }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            rememberCurrentListUrl();
            restoreBackToListLinks();
        });
    })();
    </script>

    <!-- ========================================
         GLOBAL DOUBLE-CLICK PREVENTION
         Mencegah user klik berkali-kali pada tombol submit/save/create
         Click pertama tetap jalan, click kedua dst di-block
         ======================================== -->
    <script>
    (function() {
        'use strict';
        
        // Keywords untuk mendeteksi tombol proses
        const SUBMIT_KEYWORDS = [
            'simpan', 'save', 'submit', 'create', 'buat', 'tambah', 'add',
            'update', 'edit', 'ubah', 'kirim', 'send', 'proses', 'process',
            'approve', 'reject', 'confirm', 'delete', 'hapus', 'apply',
            'generate', 'export', 'import', 'upload', 'download'
        ];
        
        // Classes yang menandakan tombol submit
        const SUBMIT_CLASSES = [
            'btn-primary', 'btn-success', 'btn-danger', 'btn-submit',
            'btn-save', 'btn-create', 'submit-btn', 'save-btn'
        ];
        
        // Durasi lock click ganda dan visual loading dipisah supaya UI tidak terlihat "muter" terlalu lama.
        const CLICK_LOCK_DURATION = 3000; // fallback 3 detik jika tidak ada redirect/AJAX completion
        const VISUAL_FEEDBACK_DURATION = 1200; // spinner singkat, lock tetap jalan di background
        
        // Storage untuk button yang sedang diproses
        const processingButtons = new WeakSet();
        
        /**
         * Check apakah button adalah tombol proses/submit
         */
        function isSubmitButton(button) {
            if (!button || button.tagName !== 'BUTTON') return false;
            
            // Skip jika sudah ada class khusus untuk exclude
            if (button.classList.contains('no-double-click-prevention')) return false;
            
            // Skip access-control toggle buttons (they have their own state management)
            if (button.classList.contains('toggle-multi-login') ||
                button.classList.contains('toggle-freeze') ||
                button.classList.contains('toggle-screenshot')) return false;
            
            // Skip wizard navigation buttons (Next/Previous) - support multiple ID formats
            if (button.id === 'nextBtn' || button.id === 'prevBtn' || 
                button.id === 'next-btn' || button.id === 'prev-btn') return false;
            
            // Skip modal trigger buttons (buttons that open modals)
            if (button.id === 'add_master_building_btn' || 
                button.id === 'add-room-btn' || 
                button.id === 'addRoomBtn' ||
                button.classList.contains('modal-trigger') ||
                button.classList.contains('btn-success') && button.querySelector('i.fa-plus')) return false;
            
            // Skip all btn-wizard buttons (wizard navigation buttons)
            if (button.classList.contains('btn-wizard')) {
                // Check if it's a navigation button (not submit/finalize)
                const text = (button.textContent || button.innerText || '').toLowerCase().trim();
                if (text === 'next' || text.includes('next') || 
                    text === 'previous' || text.includes('previous') ||
                    text === 'prev' || text.includes('prev') ||
                    text.includes('arrow-left') || text.includes('arrow-right')) {
                    return false;
                }
            }
            
            // Check type="submit"
            if (button.type === 'submit') return true;
            
            // Check text content
            const text = (button.textContent || button.innerText || '').toLowerCase().trim();
            if (SUBMIT_KEYWORDS.some(keyword => text.includes(keyword))) return true;
            
            // Check class names (but exclude btn-wizard-primary if it's just navigation)
            if (button.classList.contains('btn-wizard-primary') || button.classList.contains('btn-wizard-secondary') ||
                button.classList.contains('btn-prev') || button.classList.contains('btn-next')) {
                // Only treat as submit if it's not a simple navigation button
                const buttonText = (button.textContent || button.innerText || '').toLowerCase().trim();
                if (buttonText === 'next' || buttonText.includes('next') || 
                    buttonText === 'previous' || buttonText.includes('previous') ||
                    buttonText === 'prev' || buttonText.includes('prev') ||
                    buttonText.includes('arrow-left') || buttonText.includes('arrow-right')) {
                    return false;
                }
            }
            if (SUBMIT_CLASSES.some(cls => button.classList.contains(cls))) return true;
            
            // Check data attribute
            if (button.dataset.submit === 'true' || button.dataset.process === 'true') return true;
            
            return false;
        }
        
        /**
         * Disable button dan tampilkan loading state
         * SETELAH click pertama diproses
         */
        function disableButton(button) {
            if (processingButtons.has(button)) return false;
            
            processingButtons.add(button);
            
            // Store original state
            button.dataset.originalText = button.innerHTML;
            button.dataset.originalDisabled = button.disabled ? 'true' : 'false';
            
            // Visual feedback - tapi JANGAN disable dulu, biarkan action jalan
            button.style.opacity = '0.6';
            button.style.cursor = 'wait';
            button.classList.add('btn-click-locked');
            button.classList.add('btn-processing');
            
            // Add loading indicator ke text
            const hasIcon = button.querySelector('i.fa-spinner');
            if (!hasIcon) {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 5px;"></i>' + originalHTML;
            }
            
            // Disable button setelah sedikit delay (biar click pertama sempat jalan)
            setTimeout(() => {
                button.disabled = true;
            }, 100);

            // Spinner/opacity tidak perlu bertahan sepanjang lock. Click berikutnya tetap diblok oleh processingButtons.
            button._visualTimeout = setTimeout(() => {
                restoreButtonVisualState(button);
            }, VISUAL_FEEDBACK_DURATION);
            
            // Auto re-enable after timeout (fallback jika tidak ada redirect)
            button._reenableTimeout = setTimeout(() => {
                enableButton(button);
            }, CLICK_LOCK_DURATION);
            
            return true;
        }

        /**
         * Restore tampilan tombol tanpa melepas lock double-click.
         */
        function restoreButtonVisualState(button) {
            if (!button) return;

            if (button._visualTimeout) {
                clearTimeout(button._visualTimeout);
                delete button._visualTimeout;
            }

            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }

            button.disabled = button.dataset.originalDisabled === 'true';
            button.style.opacity = '';
            button.style.cursor = '';
            button.classList.remove('btn-processing');
        }
        
        /**
         * Re-enable button
         */
        function enableButton(button) {
            if (!button) return;
            
            processingButtons.delete(button);
            
            // Clear timeout if exists
            if (button._reenableTimeout) {
                clearTimeout(button._reenableTimeout);
                delete button._reenableTimeout;
            }
            
            restoreButtonVisualState(button);
            button.classList.remove('btn-click-locked');
            
            // Clean up data attributes
            delete button.dataset.originalText;
            delete button.dataset.originalDisabled;
        }
        
        /**
         * Global click handler - BLOCKING phase untuk click kedua dst
         */
        document.addEventListener('click', function(e) {
            const button = e.target.closest('button');
            
            if (button && isSubmitButton(button)) {
                // Check if already processing - BLOCK click kedua dst
                if (processingButtons.has(button)) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    console.log('🚫 Double-click prevented:', button.dataset.originalText || button.textContent);
                    return false;
                }
            }
        }, true); // Capture phase - intercept sebelum sampai ke handler
        
        /**
         * Global click handler - SETELAH click pertama berhasil, baru disable
         */
        document.addEventListener('click', function(e) {
            const button = e.target.closest('button');
            
            if (button && isSubmitButton(button) && !processingButtons.has(button)) {
                // Click pertama - disable button SETELAH click diproses
                disableButton(button);
                console.log('🔒 Button locked after first click:', button.textContent?.trim());
            }
        }, false); // Bubble phase - setelah handler asli jalan
        
        /**
         * Handle form submit events
         */
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const submitButtons = form.querySelectorAll('button[type="submit"], button:not([type])');
            
            submitButtons.forEach(button => {
                if (!processingButtons.has(button)) {
                    disableButton(button);
                }
            });
        }, false);
        
        /**
         * Re-enable buttons on AJAX error
         */
        window.addEventListener('unhandledrejection', function(e) {
            document.querySelectorAll('.btn-click-locked, .btn-processing').forEach(button => {
                enableButton(button);
            });
        });
        
        /**
         * Re-enable buttons when navigating back (bfcache)
         */
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                document.querySelectorAll('.btn-click-locked, .btn-processing').forEach(button => {
                    enableButton(button);
                });
            }
        });
        
        /**
         * Re-enable on page visibility change (tab switch back)
         */
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                document.querySelectorAll('.btn-click-locked, .btn-processing').forEach(button => {
                    enableButton(button);
                });
            }
        });

        /**
         * Jika flow memakai AJAX, buka lock begitu request selesai agar user tidak menunggu fallback timeout.
         */
        function resetLockedButtons() {
            document.querySelectorAll('.btn-click-locked, .btn-processing').forEach(button => {
                enableButton(button);
            });
        }

        function bindAjaxButtonReset() {
            if (window.jQuery && !window.__buttonAjaxResetBound) {
                window.__buttonAjaxResetBound = true;
                window.jQuery(document).ajaxStop(function() {
                    resetLockedButtons();
                });
            }
        }

        bindAjaxButtonReset();
        document.addEventListener('DOMContentLoaded', bindAjaxButtonReset);
        window.addEventListener('load', bindAjaxButtonReset);
        
        // Expose functions untuk manual control
        window.enableSubmitButton = enableButton;
        window.disableSubmitButton = disableButton;
        window.resetAllSubmitButtons = function() {
            resetLockedButtons();
        };
        
        console.log('✅ Global double-click prevention initialized');
    })();
    </script>
    
    <style>
    /* Processing button style */
    .btn-processing {
        position: relative;
    }
    
    /* Spinner animation fallback if FontAwesome not loaded */
    @keyframes btn-spinner {
        to { transform: rotate(360deg); }
    }
    
    .btn-processing .fa-spinner {
        animation: btn-spinner 1s linear infinite;
    }
    </style>
    
    {{-- Modals Stack --}}
    @stack('modals')

    {{-- Screenshot Protection Script --}}
    @php
        $canTakeScreenshot = auth()->check() && auth()->user()->canTakeScreenshot();
    @endphp
    @if(!$canTakeScreenshot)
    <style>
    /* Always-active blur protection - content is always blurred */
    body.screenshot-protected {
        position: relative;
    }
    
    body.screenshot-protected::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: transparent;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        z-index: 9999998;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.1s;
    }
    
    /* When screenshot is attempted, make blur visible */
    body.screenshot-protected.screenshot-attempt::before {
        opacity: 1;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    
    /* Additional overlay for extra protection - always visible but subtle */
    #screenshot-protection-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
        z-index: 9999997;
        pointer-events: none;
        opacity: 0.2;
        transition: opacity 0.1s, backdrop-filter 0.1s;
    }
    
    /* Make content slightly blurred by default - minimal blur for UX */
    body.screenshot-protected > *:not(#screenshot-watermark):not(#screenshot-indicator):not(#screenshot-protection-overlay):not(script):not(style) {
        /* filter: blur(0.3px); REMOVED as per user request */
        transition: filter 0.05s;
        /* -webkit-filter: blur(0.3px); REMOVED as per user request */
    }
    
    /* When screenshot attempt detected, blur much more aggressively */
    body.screenshot-protected.screenshot-attempt > *:not(#screenshot-watermark):not(#screenshot-indicator):not(#screenshot-protection-overlay):not(script):not(style) {
        filter: blur(10px) !important;
        -webkit-filter: blur(10px) !important;
    }
    
    /* Additional protection: Make overlay more visible when Windows key pressed */
    body.screenshot-protected #screenshot-protection-overlay {
        opacity: 0.2;
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
        transition: opacity 0.1s, backdrop-filter 0.1s;
    }
    
    body.screenshot-protected.screenshot-attempt #screenshot-protection-overlay {
        opacity: 0.9 !important;
        backdrop-filter: blur(15px) !important;
        -webkit-backdrop-filter: blur(15px) !important;
    }
    </style>
    <script>
    (function() {
        'use strict';
        
        // Enhanced screenshot protection - more aggressive detection
        // Note: Complete prevention is impossible due to OS-level access, but we make it very difficult
        
        // Add class to body immediately
        document.body.classList.add('screenshot-protected');
        
        // Create always-active overlay
        var protectionOverlay = document.createElement('div');
        protectionOverlay.id = 'screenshot-protection-overlay';
        document.body.appendChild(protectionOverlay);
        
        var screenshotAttempts = 0;
        var maxAttempts = 3;
        var blurOverlay = null;
        var isScreenshotAttempt = false;
        
        // Activate blur protection immediately
        function activateBlurProtection() {
            isScreenshotAttempt = true;
            document.body.classList.add('screenshot-attempt');
            
            // Also create visible overlay
            if (!blurOverlay) {
                blurOverlay = document.createElement('div');
                blurOverlay.id = 'screenshot-protection-blur';
                blurOverlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(15px);-webkit-backdrop-filter:blur(15px);';
                blurOverlay.innerHTML = '<div style="background:white;padding:30px;border-radius:10px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.3);"><h2 style="color:#dc2626;margin:0 0 10px 0;">⚠️ Screenshot Tidak Diizinkan!</h2><p style="margin:0;color:#666;">Akses screenshot/print screen tidak diizinkan untuk akun Anda.</p></div>';
                document.body.appendChild(blurOverlay);
            }
            
            // Keep blur active for longer
            setTimeout(function() {
                document.body.classList.remove('screenshot-attempt');
                if (blurOverlay) {
                    blurOverlay.remove();
                    blurOverlay = null;
                }
                isScreenshotAttempt = false;
            }, 5000);
        }
        
        // Pre-emptive blur activation on Windows key press
        var windowsKeyPressed = false;
        var shiftKeyPressed = false;
        
        // Track Windows and Shift keys for early detection - HIGHEST PRIORITY
        document.addEventListener('keydown', function(e) {
            var key = e.key || e.keyCode;
            var isWindowsKey = e.metaKey || e.key === 'Meta' || e.keyCode === 91 || e.keyCode === 92 || e.keyCode === 93;
            
            // Track Windows key - ACTIVATE BLUR IMMEDIATELY
            if (isWindowsKey) {
                windowsKeyPressed = true;
                // CRITICAL: Activate blur IMMEDIATELY when Windows key is pressed
                // This happens BEFORE Windows+S combination completes
                activateBlurProtection();
                // Also add class immediately for CSS blur
                document.body.classList.add('screenshot-attempt');
            }
            
            // Track Shift key
            if (e.shiftKey) {
                shiftKeyPressed = true;
                // If Windows key is already pressed, activate blur immediately
                if (windowsKeyPressed) {
                    activateBlurProtection();
                    document.body.classList.add('screenshot-attempt');
                }
            }
            
            // If S key is pressed while Windows+Shift are active
            if ((key === 'S' || e.keyCode === 83) && windowsKeyPressed && shiftKeyPressed) {
                // Triple protection - blur is already active, but ensure it stays
                activateBlurProtection();
                document.body.classList.add('screenshot-attempt');
            }
        }, true);
        
        // Reset key tracking on keyup
        document.addEventListener('keyup', function(e) {
            var isWindowsKey = e.metaKey || e.key === 'Meta' || e.keyCode === 91 || e.keyCode === 92 || e.keyCode === 93;
            if (isWindowsKey) {
                windowsKeyPressed = false;
            }
            if (!e.shiftKey) {
                shiftKeyPressed = false;
            }
        }, true);
        
        // 1. Enhanced Print Screen and Windows+S detection
        document.addEventListener('keydown', function(e) {
            var key = e.key || e.keyCode;
            var isPrintScreen = key === 'PrintScreen' || key === 'Print' || e.keyCode === 44 || e.keyCode === 124;
            var isWindowsKey = e.metaKey || e.key === 'Meta' || e.keyCode === 91 || e.keyCode === 92 || e.keyCode === 93;
            var isShift = e.shiftKey;
            var isAlt = e.altKey;
            
            // Print Screen key
            if (isPrintScreen) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                screenshotAttempts++;
                activateBlurProtection();
                if (screenshotAttempts >= maxAttempts) {
                    showWarningDialog('Screenshot tidak diizinkan. Percobaan berulang dapat menyebabkan akun Anda dibekukan.');
                } else {
                    showWarningDialog('Screenshot atau Print Screen tidak diizinkan untuk akun Anda.');
                }
                return false;
            }
            
            // Windows + Shift + S (Windows Snipping Tool) - activate blur BEFORE tool opens
            if (isWindowsKey && isShift && (key === 'S' || e.keyCode === 83)) {
                // Activate blur IMMEDIATELY before preventing default
                activateBlurProtection();
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                screenshotAttempts++;
                showWarningDialog('Windows Snipping Tool tidak diizinkan. Screenshot tidak diizinkan untuk akun Anda.');
                return false;
            }
            
            // Pre-emptive: If Windows key is pressed, activate blur immediately
            if (isWindowsKey) {
                activateBlurProtection();
            }
            
            // Alt + Print Screen
            if (isAlt && isPrintScreen) {
                activateBlurProtection();
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                screenshotAttempts++;
                showWarningDialog('Screenshot tidak diizinkan untuk akun Anda.');
                return false;
            }
            
            // Windows + Print Screen
            if (isWindowsKey && isPrintScreen) {
                activateBlurProtection();
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                screenshotAttempts++;
                showWarningDialog('Screenshot tidak diizinkan untuk akun Anda.');
                return false;
            }
            
            // Ctrl + Shift + S (some screenshot tools)
            if (e.ctrlKey && isShift && (key === 'S' || e.keyCode === 83)) {
                activateBlurProtection();
                e.preventDefault();
                e.stopPropagation();
                showWarningDialog('Screenshot tidak diizinkan untuk akun Anda.');
                return false;
            }
        }, true);
        
        // 2. Detect visibility change (user switching tabs or minimizing - might be taking screenshot)
        // 2. Detect visibility change (user switching tabs or minimizing - might be taking screenshot)
        // DISABLED as per user request - no blur on tab switch
        /*
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Tab is hidden - might be taking screenshot with external tool
                // Activate blur immediately when tab becomes hidden
                activateBlurProtection();
                console.warn('Tab visibility changed - potential screenshot attempt');
            } else {
                // Tab is visible again - activate blur and check clipboard
                activateBlurProtection();
                setTimeout(function() {
                    if (navigator.clipboard && navigator.clipboard.readText) {
                        navigator.clipboard.readText().then(function(text) {
                            if (text && text.length > 100) {
                                console.warn('Large clipboard content detected after visibility change');
                                activateBlurProtection();
                            }
                        }).catch(function() {
                            // Clipboard access denied or empty
                        });
                    }
                }, 500);
            }
        });
        */
        
        // 3. Monitor clipboard for screenshot data (images) - more frequent check
        var clipboardCheckInterval = setInterval(function() {
            if (navigator.clipboard && navigator.clipboard.read) {
                navigator.clipboard.read().then(function(clipboardItems) {
                    clipboardItems.forEach(function(item) {
                        if (item.types.includes('image/png') || item.types.includes('image/jpeg')) {
                            console.warn('⚠️ Screenshot image detected in clipboard!');
                            activateBlurProtection();
                            showWarningDialog('Screenshot terdeteksi. Screenshot tidak diizinkan untuk akun Anda.');
                            // Clear clipboard
                            navigator.clipboard.writeText('').catch(function() {});
                        }
                    });
                }).catch(function() {
                    // Clipboard access denied - that's fine
                });
            }
        }, 1000); // Check every 1 second (more frequent)
        
        // 4. Disable right-click context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }, true);
        
        // 5. Disable Developer Tools and inspection
        document.addEventListener('keydown', function(e) {
            // F12
            if (e.keyCode === 123) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                showWarningDialog('Developer Tools tidak diizinkan untuk akun Anda.');
                return false;
            }
            
            // Ctrl+Shift+I (DevTools)
            if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                showWarningDialog('Developer Tools tidak diizinkan untuk akun Anda.');
                return false;
            }
            
            // Ctrl+Shift+J (Console)
            if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }
            
            // Ctrl+Shift+C (Inspect Element)
            if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                showWarningDialog('Inspect Element tidak diizinkan untuk akun Anda.');
                return false;
            }
            
            // Ctrl+U (View Source)
            if (e.ctrlKey && e.keyCode === 85) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }
            
            // Ctrl+S (Save Page - can be used to extract content)
            if (e.ctrlKey && e.keyCode === 83 && !e.shiftKey) {
                e.preventDefault();
                e.stopPropagation();
                showWarningDialog('Simpan halaman tidak diizinkan untuk akun Anda.');
                return false;
            }
        }, true);
        
        // 6. Watermark in top-right corner only
        var watermarkContainer = document.createElement('div');
        watermarkContainer.id = 'screenshot-watermark';
        watermarkContainer.style.cssText = 'position:fixed;top:20px;right:20px;pointer-events:none;z-index:999999;background:transparent;text-align:right;';
        
        // Main watermark text
        var watermark = document.createElement('div');
        watermark.style.cssText = 'font-size:14px;color:rgba(220,38,38,0.6);font-weight:600;white-space:nowrap;user-select:none;pointer-events:none;margin-bottom:4px;text-shadow:1px 1px 2px rgba(255,255,255,0.8);';
        watermark.innerHTML = '{{ auth()->user()->name ?? "User" }} - ' + (new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString());
        watermarkContainer.appendChild(watermark);
        
        // Protected by System text
        var protectedText = document.createElement('div');
        protectedText.style.cssText = 'font-size:11px;color:rgba(220,38,38,0.5);font-weight:500;white-space:nowrap;user-select:none;pointer-events:none;text-shadow:1px 1px 2px rgba(255,255,255,0.8);';
        protectedText.innerHTML = 'Protected by System';
        watermarkContainer.appendChild(protectedText);
        
        document.body.appendChild(watermarkContainer);
        
        // 7. Disable text selection (make it harder to copy)
        document.addEventListener('selectstart', function(e) {
            // Allow some selection for UX, but make it harder
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return true; // Allow in form fields
            }
            // Block selection in other areas
            e.preventDefault();
            return false;
        }, true);
        
        // 8. Disable copy operation (except in input fields)
        document.addEventListener('copy', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return true; // Allow copy in form fields
            }
            e.preventDefault();
            e.clipboardData.setData('text/plain', '⚠️ Copy tidak diizinkan untuk konten ini.');
            console.warn('Copy operation blocked');
            return false;
        }, true);
        
        // 9. Monitor canvas operations (screenshot tools sometimes use canvas)
        var originalToDataURL = HTMLCanvasElement.prototype.toDataURL;
        var originalToBlob = HTMLCanvasElement.prototype.toBlob;
        HTMLCanvasElement.prototype.toDataURL = function() {
            console.warn('⚠️ Canvas toDataURL called - potential screenshot attempt');
            activateBlurProtection();
            return originalToDataURL.apply(this, arguments);
        };
        HTMLCanvasElement.prototype.toBlob = function() {
            console.warn('⚠️ Canvas toBlob called - potential screenshot attempt');
            activateBlurProtection();
            return originalToBlob.apply(this, arguments);
        };
        
        // 10. Disable drag and drop
        document.addEventListener('dragstart', function(e) {
            if (e.target.tagName !== 'IMG' && e.target.tagName !== 'A') {
                e.preventDefault();
                return false;
            }
        }, true);
        
        // 11. Visual indicator
        var indicator = document.createElement('div');
        indicator.id = 'screenshot-indicator';
        indicator.style.cssText = 'position:fixed;bottom:10px;right:10px;background:rgba(220,38,38,0.95);color:white;padding:10px 15px;border-radius:8px;font-size:13px;z-index:999998;box-shadow:0 4px 12px rgba(0,0,0,0.4);font-weight:600;';
        indicator.innerHTML = '<i class="fas fa-ban"></i> Screenshot Disabled';
        document.body.appendChild(indicator);
        
        // 12. Console warning
        console.warn('%c⚠️ SCREENSHOT PROTECTION ACTIVE', 'color: red; font-size: 20px; font-weight: bold;');
        console.warn('Screenshot/Print Screen tidak diizinkan untuk akun ini.');
        console.warn('Semua percobaan screenshot akan dideteksi dan diblokir.');
        
        // 13. Monitor for screenshot tools injection
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        // Check for suspicious screenshot-related classes or IDs
                        var className = node.className || '';
                        var id = node.id || '';
                        if (typeof className === 'string' && (
                            className.toLowerCase().includes('screenshot') ||
                            className.toLowerCase().includes('snip') ||
                            id.toLowerCase().includes('screenshot') ||
                            id.toLowerCase().includes('snip')
                        )) {
                            console.warn('⚠️ Suspicious screenshot-related element detected');
                            if (node.parentNode) {
                                node.parentNode.removeChild(node);
                            }
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: false
        });
        
        // 14. Detect if user tries to open new window/tab for screenshot
        window.addEventListener('beforeunload', function(e) {
            // This might indicate user is trying to screenshot in new window
            console.warn('Window unload detected');
        });
        
        // 15. Periodic check for screenshot tools
        setInterval(function() {
            // Check for common screenshot tool processes (limited - browser can't access OS processes)
            // But we can check for suspicious browser extensions
            if (window.chrome && window.chrome.runtime) {
                // Chrome extension detection (limited)
                console.log('Browser extension check');
            }
        }, 5000);
        
    })();
    </script>
    @endif

    <!-- Sticky Horizontal Scrollbar Script -->
    <script>
    (function() {
        'use strict';
        
        // Create sticky scroll wrapper
        var stickyWrapper = document.createElement('div');
        stickyWrapper.id = 'stickyScrollWrapper';
        stickyWrapper.style.cssText = 'position:fixed;bottom:0;height:20px;background:linear-gradient(#e8e8e8,#d0d0d0);border-top:1px solid #bbb;z-index:9999;overflow-x:scroll;overflow-y:hidden;display:none;box-shadow:0 -2px 5px rgba(0,0,0,0.1);';
        
        var stickyInner = document.createElement('div');
        stickyInner.id = 'stickyScrollInner';
        stickyInner.style.cssText = 'height:1px;';
        stickyWrapper.appendChild(stickyInner);
        document.body.appendChild(stickyWrapper);
        
        var currentTarget = null;
        var syncing = false;
        
        function findScrollableContainer() {
            var tables = document.querySelectorAll('table');
            for (var i = 0; i < tables.length; i++) {
                var table = tables[i];
                var parent = table.parentElement;
                if (!parent) continue;
                
                // Check if parent has horizontal scroll
                if (parent.scrollWidth > parent.clientWidth + 10) {
                    var rect = parent.getBoundingClientRect();
                    // Check if table is visible and scrollbar is below viewport
                    if (rect.top < window.innerHeight && rect.bottom > window.innerHeight) {
                        return parent;
                    }
                }
            }
            return null;
        }
        
        function update() {
            var container = findScrollableContainer();
            
            if (container) {
                currentTarget = container;
                var rect = container.getBoundingClientRect();
                
                stickyWrapper.style.left = rect.left + 'px';
                stickyWrapper.style.width = rect.width + 'px';
                stickyWrapper.style.display = 'block';
                stickyInner.style.width = container.scrollWidth + 'px';
                
                if (!syncing) {
                    stickyWrapper.scrollLeft = container.scrollLeft;
                }
            } else {
                currentTarget = null;
                stickyWrapper.style.display = 'none';
            }
        }
        
        // Sync: sticky -> table
        stickyWrapper.addEventListener('scroll', function() {
            if (currentTarget && !syncing) {
                syncing = true;
                currentTarget.scrollLeft = stickyWrapper.scrollLeft;
                setTimeout(function() { syncing = false; }, 20);
            }
        });
        
        // Sync: table -> sticky (using event capture)
        document.addEventListener('scroll', function(e) {
            if (currentTarget && e.target === currentTarget && !syncing) {
                syncing = true;
                stickyWrapper.scrollLeft = currentTarget.scrollLeft;
                setTimeout(function() { syncing = false; }, 20);
            }
        }, true);
        
        // Also listen for wheel events on sticky bar
        stickyWrapper.addEventListener('wheel', function(e) {
            if (currentTarget && e.deltaX !== 0) {
                e.preventDefault();
                currentTarget.scrollLeft += e.deltaX;
                stickyWrapper.scrollLeft = currentTarget.scrollLeft;
            }
        }, { passive: false });
        
        window.addEventListener('scroll', update);
        window.addEventListener('resize', update);
        setInterval(update, 300);
        setTimeout(update, 200);
        
        console.log('Sticky horizontal scrollbar initialized');
    })();
    </script>
    <script>
    (function() {
        'use strict';
        
        // --- GLOBAL TABLE SORTING ---
        document.addEventListener('DOMContentLoaded', function() {
            // Get current URL params
            var urlParams = new URLSearchParams(window.location.search);
            var currentSort = urlParams.get('sort');
            var currentDirection = urlParams.get('direction');
            
            // Find all sortable headers
            var headers = document.querySelectorAll('th[data-column]');
            
            headers.forEach(function(th) {
                var column = th.getAttribute('data-column');
                var computedStyle = window.getComputedStyle(th);
                
                // Skip if data-no-sort is present
                if (th.hasAttribute('data-no-sort')) return;
                if (th.hidden || computedStyle.display === 'none' || computedStyle.visibility === 'hidden') return;
                
                // Add cursor pointer style
                th.style.cursor = 'pointer';
                th.style.position = 'relative'; 
                th.title = 'Click to sort';
                
                // Create container for text and icon if not exists
                if (!th.querySelector('.sort-wrapper')) {
                    // Wrap existing content
                    var content = th.innerHTML;
                    th.innerHTML = '<div class="sort-wrapper flex items-center justify-between gap-2"><span>' + content + '</span><span class="sort-icon text-gray-300 text-[10px]">↕</span></div>';
                }
                
                var icon = th.querySelector('.sort-icon');
                
                // Update icon if active
                if (currentSort === column && currentDirection) {
                    icon.innerHTML = currentDirection === 'asc' ? '▲' : '▼'; // Up/Down filled arrows
                    icon.classList.remove('text-gray-300');
                    icon.classList.add('text-gray-800'); // Active color
                    
                    // Highlight active column header background
                    th.classList.add('bg-gray-50'); 
                } else {
                    // Default state
                    icon.innerHTML = '↕';
                    icon.classList.add('text-gray-300');
                    icon.classList.remove('text-gray-800');
                    th.classList.remove('bg-gray-50');
                }
                
                // Attach click handler (ensure we don't add duplicates if script runs twice)
                if (!th.dataset.sortAttached) {
                    th.dataset.sortAttached = "true";
                    th.addEventListener('click', function(e) {
                        // Prevent default if it's inside a label or input
                        if (e.target.tagName === 'INPUT' || e.target.tagName === 'LABEL') return;
                        
                        var newDirection = 'asc';
                        
                        // Toggle direction if clicking same column
                        if (currentSort === column) {
                            newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                        }
                        
                        // Update URL and reload
                        urlParams.set('sort', column);
                        urlParams.set('direction', newDirection);
                        
                        // Reset pagination to page 1 when sorting changes
                        urlParams.set('page', '1');
                        
                        window.location.search = urlParams.toString();
                    });
                }
            });
        });

        // --- GLOBAL N/A PLACEHOLDER REPLACEMENT ---
        document.addEventListener('DOMContentLoaded', function() {
            function replaceNAPlaceholders() {
                document.querySelectorAll('td').forEach(function(td) {
                    if (td.children.length === 0) {
                        const text = td.textContent.trim();
                        if (text === 'N/A' || text === 'n/a') {
                            td.textContent = '-';
                        }
                    } else {
                        for (let child of td.childNodes) {
                            if (child.nodeType === Node.TEXT_NODE) {
                                const text = child.textContent.trim();
                                if (text === 'N/A' || text === 'n/a') {
                                    child.textContent = '-';
                                }
                            }
                        }
                    }
                });
            }
            // Run replacement
            replaceNAPlaceholders();

            // --- GLOBAL PHONE NUMBER VALIDATION ---
            document.addEventListener('input', function(e) {
                // Target elements specifically related to phone numbers
                // We use specific keywords to avoid affecting fields like 'email' or 'name'
                const selector = 'input[type="tel"], ' +
                               'input[name*="phone"], ' +
                               'input[name*="whatsapp"], ' +
                               'input[name*="mobile"], ' +
                               'input[name*="contact_no"], ' +
                               'input[id*="phone"], ' +
                               'input[id*="whatsapp"]';
                
                if (e.target.matches(selector)) {
                    // Skip if it's an email field just to be extra safe
                    if (e.target.type === 'email' || e.target.name.includes('email')) return;
    
                    // Allow digits, +, -, (, ), and space
                    const originalValue = e.target.value;
                    const validatedValue = originalValue.replace(/[^0-9\+\-\(\)\s]/g, '');
                    
                    if (originalValue !== validatedValue) {
                        e.target.value = validatedValue;
                        
                        // Show subtle visual feedback
                        e.target.style.borderColor = '#ef4444';
                        setTimeout(() => {
                            e.target.style.borderColor = '';
                        }, 500);
                    }
                }
            });
        });
    })();
    </script>
    <!-- Global DataTables Instant Filter -->
    <script>
    (function() {
        'use strict';
        // Listen for standard DataTable initialization
        $(document).on('init.dt', function(e, settings) {
            var api = new $.fn.dataTable.Api(settings);
            var $table = $(api.table().node());

            // Check if this table has a cloned header row for filters
            // (Standard pattern used in this app: class="filters")
            var $filtersRow = $table.find('thead tr.filters');
            
            if ($filtersRow.length > 0) {
                console.log('Global Filter: Attaching instant search to table', $table.attr('id'));
                
                // Find all inputs in this filter row
                $filtersRow.find('input, select').each(function() {
                    var $input = $(this);
                    
                    // We need to resolve which column this input belongs to.
                    // The robust way is to find the index of the th/td in the row.
                    var colIndex = $input.closest('th, td').index();
                    
                    // UNBIND existing events to prevent duplication/conflict
                    $input.off('keyup change keydown keypress');
                    $input.off('input'); 
                    
                    // Bind 'input' event for instant reaction (catches paste, cut, etc)
                    var searchTimeout = null;
                    $input.on('input', function(e) {
                         e.stopPropagation(); // Stop bubbling
                         
                         var val = this.value;
                         
                         // Debounce for performance (300ms)
                         clearTimeout(searchTimeout);
                         searchTimeout = setTimeout(function() {
                             if (api.column(colIndex).search() !== val) {
                                 api.column(colIndex).search(val).draw();
                             }
                         }, 300);
                    });
                });
            }
        });
    })();
    </script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Flatpickr (standardized datepicker) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
    <script>
    (function () {
        // Standardized datepicker auto-init.
        // Upgrades native date / datetime-local inputs to flatpickr with a
        // consistent d/M/Y display while still submitting Y-m-d (Y-m-d H:i) to the server.
        if (typeof flatpickr === 'undefined') { return; }

        try { flatpickr.localize(flatpickr.l10ns.id); } catch (e) {}

        window.initFlatpickr = function (root) {
            root = root || document;
            var inputs = root.querySelectorAll('input[type="date"], input[type="datetime-local"]');
            inputs.forEach(function (el) {
                // Skip if already initialized or explicitly opted out
                if (el._flatpickr || el.hasAttribute('data-no-flatpickr')) { return; }

                var isDateTime = (el.getAttribute('type') === 'datetime-local')
                    || el.hasAttribute('data-enable-time');

                // Switch type to text so the native picker doesn't double up with flatpickr
                el.setAttribute('type', 'text');

                flatpickr(el, {
                    altInput: true,
                    altFormat: isDateTime ? 'd/M/Y H:i' : 'd/M/Y',
                    dateFormat: isDateTime ? 'Y-m-d H:i' : 'Y-m-d',
                    enableTime: isDateTime,
                    time_24hr: true,
                    allowInput: true,
                });
            });
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.initFlatpickr(document);

            // Catch date inputs injected dynamically (AJAX-loaded modals, cloned rows, etc.)
            // The altInput flatpickr creates is type=text, so it never re-matches the
            // date/datetime selector -> no init loop.
            if (typeof MutationObserver !== 'undefined') {
                var scheduled = false;
                var observer = new MutationObserver(function (mutations) {
                    var hasNewDate = false;
                    for (var i = 0; i < mutations.length; i++) {
                        var added = mutations[i].addedNodes;
                        for (var j = 0; j < added.length; j++) {
                            var node = added[j];
                            if (node.nodeType !== 1) { continue; } // elements only
                            if ((node.matches && node.matches('input[type="date"], input[type="datetime-local"]')) ||
                                (node.querySelector && node.querySelector('input[type="date"], input[type="datetime-local"]'))) {
                                hasNewDate = true;
                                break;
                            }
                        }
                        if (hasNewDate) { break; }
                    }
                    if (hasNewDate && !scheduled) {
                        scheduled = true;
                        // Defer so a whole modal subtree finishes inserting before we init
                        setTimeout(function () {
                            scheduled = false;
                            window.initFlatpickr(document);
                        }, 50);
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }
        });
    })();
    </script>

    <!-- Standardized currency input (thousand separator on display, raw number on submit) -->
    <script>
    (function () {
        // Format an integer-ish string with Indonesian thousand separators (dots).
        function formatThousands(raw) {
            raw = (raw || '').toString().replace(/\D/g, '').replace(/^0+(?=\d)/, '');
            if (raw === '') return '';
            return raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        // Raw digits only (what we submit to the server).
        function rawDigits(value) {
            return (value || '').toString().replace(/\D/g, '');
        }

        window.initCurrencyInputs = function (root) {
            root = root || document;
            var inputs = root.querySelectorAll('input.currency-input, input[data-currency]');
            inputs.forEach(function (el) {
                if (el.dataset.currencyBound === '1') return;
                el.dataset.currencyBound = '1';
                el.setAttribute('inputmode', 'numeric');

                // Format any value already present (edit forms).
                if (el.value) el.value = formatThousands(el.value);

                el.addEventListener('input', function () {
                    var start = el.selectionStart;
                    var before = el.value;
                    el.value = formatThousands(el.value);
                    // keep caret roughly in place when separators shift
                    var diff = el.value.length - before.length;
                    try { el.setSelectionRange(start + diff, start + diff); } catch (e) {}
                });

                // On submit, replace the displayed value with the raw number so the
                // server receives a plain integer (no controller/validation changes needed).
                if (el.form && el.form.dataset.currencyHooked !== '1') {
                    el.form.dataset.currencyHooked = '1';
                    el.form.addEventListener('submit', function () {
                        el.form.querySelectorAll('input.currency-input, input[data-currency]').forEach(function (ci) {
                            ci.value = rawDigits(ci.value);
                        });
                    });
                }
            });
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.initCurrencyInputs(document);

            if (typeof MutationObserver !== 'undefined') {
                var scheduled = false;
                var obs = new MutationObserver(function (mutations) {
                    var found = false;
                    for (var i = 0; i < mutations.length; i++) {
                        var added = mutations[i].addedNodes;
                        for (var j = 0; j < added.length; j++) {
                            var n = added[j];
                            if (n.nodeType !== 1) continue;
                            if ((n.matches && n.matches('input.currency-input, input[data-currency]')) ||
                                (n.querySelector && n.querySelector('input.currency-input, input[data-currency]'))) {
                                found = true; break;
                            }
                        }
                        if (found) break;
                    }
                    if (found && !scheduled) {
                        scheduled = true;
                        setTimeout(function () { scheduled = false; window.initCurrencyInputs(document); }, 50);
                    }
                });
                obs.observe(document.body, { childList: true, subtree: true });
            }
        });
    })();
    </script>
</body>
</html>
