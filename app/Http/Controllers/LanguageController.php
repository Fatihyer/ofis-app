<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Lang;

class LanguageController extends Controller
{
   public function change(Request $request) {
    $supported = config('app.supported_locales', ['tr','en','fr']);
    $data = $request->validate([
        'locale'   => 'required|in:'.implode(',', $supported),
        'redirect' => 'nullable|string',
    ]);
    session(['locale' => $data['locale']]);
    return redirect($data['redirect'] ?? url()->previous());
}
}
