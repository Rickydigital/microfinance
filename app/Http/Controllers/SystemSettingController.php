<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SystemSettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:admin.configuration.view', only: ['index']),
            new Middleware('can:admin.configuration.update', only: ['update']),
        ];
    }

    public function index(): View
    {
        $settings = SystemSetting::query()->orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('administration.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $values = $request->validate(['settings' => ['required', 'array'], 'settings.*' => ['nullable', 'string', 'max:2000']])['settings'];

        foreach ($values as $id => $value) {
            SystemSetting::query()->whereKey($id)->update(['value' => $value]);
        }

        return back()->with('success', 'System settings updated.');
    }
}
