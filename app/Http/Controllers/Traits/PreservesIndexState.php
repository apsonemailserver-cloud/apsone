<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;

trait PreservesIndexState
{
    /**
     * Preserves and redirects to the last visited index page state (pagination, search, filters).
     *
     * @param Request $request
     * @param string $routeName The base route/session identifier (e.g. 'roles', 'shifts').
     * @param string $basePathPattern Regex pattern to match related sub-paths.
     * @return \Illuminate\Http\RedirectResponse|null
     */
    protected function checkIndexState(Request $request, string $routeName, string $basePathPattern)
    {
        $sessionKey = $routeName . '_index_url';

        // 1. If the request has active query parameters, save the current state URL
        if ($request->anyFilled(['page', 'search', 'per_page', 'q', 'filter', 'station'])) {
            session()->put($sessionKey, $request->fullUrl());
            return null;
        }

        // 2. If it's a plain index request, check referrer to determine context
        $prevUrl = url()->previous();
        $prevPath = parse_url($prevUrl, PHP_URL_PATH);

        if ($prevPath) {
            $isRelatedAction = preg_match($basePathPattern, $prevPath);

            if ($isRelatedAction && session()->has($sessionKey)) {
                session()->reflash();
                return redirect(session()->get($sessionKey));
            }
        }

        // Navigating from other modules/menus: reset state to page 1
        session()->forget($sessionKey);
        return null;
    }
}
