<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Show a static page rendered from its Markdown file.
     */
    public function __invoke(Page $page): View
    {
        return view('page', ['page' => $page]);
    }
}
