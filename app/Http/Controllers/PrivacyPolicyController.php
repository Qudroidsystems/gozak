<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    public function show()
    {
        return view('privacy.privacy');
    }

    public function showUserDataSafety()
    {
        return view('privacy.user-safety');
    }
}
?>