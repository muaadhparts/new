<?php

namespace App\Http\Controllers\Operator;

use App\Models\HomePageTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomePageThemeController extends OperatorBaseController
{
    /**
     * Display a listing of all themes
     */
    public function index()
    {
        $themes = HomePageTheme::orderBy('is_active', 'desc')->orderBy('name')->get();
        return view('operator.homethemes.index', compact('themes'));
    }

    /**
     * Show the form for creating a new theme
     */
    public function create()
    {
        return view('operator.homethemes.create');
    }

    /**
     * Store a newly created theme
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:home_page_themes,slug',
        ]);

        $data = $request->all();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Handle checkbox fields
        $checkboxFields = [
            'show_hero_search', 'show_brands', 'show_categories',
            'show_arrival', 'show_blogs', 'show_newsletter'
        ];

        foreach ($checkboxFields as $field) {
            $data[$field] = isset($data[$field]) ? 1 : 0;
        }

        $theme = HomePageTheme::create($data);

        // If this is set as active, deactivate others
        if ($request->is_active) {
            $theme->activate();
        }

        return response()->json(__('Theme Created Successfully'));
    }

    /**
     * Show the form for editing a theme
     */
    public function edit($id)
    {
        $theme = HomePageTheme::findOrFail($id);
        return view('operator.homethemes.edit', compact('theme'));
    }

    /**
     * Update the specified theme
     */
    public function update(Request $request, $id)
    {
        $theme = HomePageTheme::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:home_page_themes,slug,' . $id,
        ]);

        $data = $request->all();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Handle checkbox fields
        $checkboxFields = [
            'show_hero_search', 'show_brands', 'show_categories',
            'show_arrival', 'show_blogs', 'show_newsletter'
        ];

        foreach ($checkboxFields as $field) {
            $data[$field] = isset($data[$field]) ? 1 : 0;
        }

        $theme->update($data);

        // If this is set as active, deactivate others
        if ($request->is_active) {
            $theme->activate();
        }

        cache()->forget('active_home_theme');

        return response()->json(__('Theme Updated Successfully'));
    }

    /**
     * Activate a theme
     */
    public function activate($id)
    {
        $theme = HomePageTheme::findOrFail($id);
        $theme->activate();

        return redirect()->back()->with('success', __('Theme Activated Successfully'));
    }

    /**
     * Remove the specified theme
     */
    public function destroy($id)
    {
        $theme = HomePageTheme::findOrFail($id);

        // Don't allow deleting the active theme
        if ($theme->is_active) {
            return response()->json(['error' => __('Cannot delete active theme')], 400);
        }

        // Don't allow deleting if it's the only theme
        if (HomePageTheme::count() <= 1) {
            return response()->json(['error' => __('Cannot delete the only theme')], 400);
        }

        $theme->delete();
        cache()->forget('active_home_theme');

        return response()->json(__('Theme Deleted Successfully'));
    }

    /**
     * Duplicate a theme
     */
    public function duplicate($id)
    {
        $theme = HomePageTheme::findOrFail($id);

        $newTheme = $theme->replicate();
        $newTheme->name = $theme->name . ' (Copy)';
        $newTheme->slug = $theme->slug . '-copy-' . time();
        $newTheme->is_active = false;
        $newTheme->save();

        return redirect()->back()->with('success', __('Theme Duplicated Successfully'));
    }
}
