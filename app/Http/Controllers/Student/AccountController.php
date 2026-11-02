<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user();

        return view('student.account', [
            'student' => $student,
            'subscriptions' => $student->subscriptions()->currentlyActive()->with(['stage', 'subject'])->latest('expires_at')->get(),
            'attempts' => $student->attempts()->with(['booklet', 'subject'])->latest('id')->limit(30)->get(),
        ]);
    }
}
