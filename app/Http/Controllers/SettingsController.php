<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Display the settings dashboard.
     */
    public function index()
    {
        return view('settings.index');
    }

    /**
     * Display general settings.
     */
    public function general()
    {
        return view('settings.general.index');
    }

    /**
     * Display theme settings.
     */
    public function theme()
    {
        return view('settings.theme.index');
    }

    /**
     * Display layout settings.
     */
    public function layout()
    {
        return view('settings.layout.index');
    }

    /**
     * Display color schemes.
     */
    public function colors()
    {
        return view('settings.colors.index');
    }

    /**
     * Display email settings.
     */
    public function email()
    {
        return view('settings.email.index');
    }

    /**
     * Display backup settings.
     */
    public function backup()
    {
        return view('settings.backup.index');
    }

    /**
     * Display security settings.
     */
    public function security()
    {
        return view('settings.security.index');
    }

    /**
     * Display profile settings.
     */
    public function profile()
    {
        return view('settings.profile.index');
    }

    /**
     * Display password settings.
     */
    public function password()
    {
        return view('settings.password.index');
    }

    /**
     * Display user preferences.
     */
    public function preferences()
    {
        return view('settings.preferences.index');
    }

    /**
     * Display notification settings.
     */
    public function notifications()
    {
        return view('settings.notifications.index');
    }

    /**
     * Display sound settings.
     */
    public function sounds()
    {
        return view('settings.sounds.index');
    }

    /**
     * Display API settings.
     */
    public function api()
    {
        return view('settings.api.index');
    }

    /**
     * Display webhook settings.
     */
    public function webhooks()
    {
        return view('settings.webhooks.index');
    }

    /**
     * Display system logs.
     */
    public function logs()
    {
        return view('settings.logs.index');
    }

    /**
     * Display maintenance mode.
     */
    public function maintenance()
    {
        return view('settings.maintenance.index');
    }
}
