<?php

namespace App\Modules\Docs\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Docs\Services\WorkbenchDocsService;

class WorkbenchDocsController extends Controller
{
    /**
     * Handle the incoming request to display modular documentation.
     *
     * @param string $section
     * @return \Illuminate\View\View
     */
    public function index($section = 'console_commands')
    {
        if (session()->has('docs_locale')) {
            app()->setLocale(session('docs_locale'));
        }

        $docs = WorkbenchDocsService::getDocs();

        if (! array_key_exists($section, $docs)) {
            abort(404);
        }

        return view('docs::index', [
            'docs'           => $docs,
            'currentSection' => $section,
            'activeDoc'      => $docs[$section],
        ]);
    }
}
