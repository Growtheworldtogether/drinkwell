<?php

namespace App\Http\Controllers;

use App\Support\SiteSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function show(Request $request, SiteSearch $search): View
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        return view('marketing.search', [
            'q' => $q,
            'results' => $q === '' ? [] : $search->search($q),
            'searched' => $q !== '' && $search->terms($q) !== [],
        ]);
    }
}
