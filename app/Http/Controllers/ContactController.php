<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Services\MailDispatcher;
use App\Support\FormGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public const TOPICS = [
        'Joining the program' => 'Joining the program',
        'My deposit' => 'My deposit',
        'My payouts or a withdrawal' => 'My payouts or a withdrawal',
        'My account' => 'My account',
        'Something else' => 'Something else',
    ];

    public function show(): View
    {
        return view('marketing.contact', ['topics' => self::TOPICS]);
    }

    public function send(Request $request, MailDispatcher $mail): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'topic' => ['required', Rule::in(array_keys(self::TOPICS))],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'name.required' => 'Please tell us your name.',
            'email.required' => 'Please enter your email so we can answer you.',
            'email.email' => 'That email address does not look right. Check it and try again.',
            'topic.required' => 'Please choose what your message is about.',
            'message.required' => 'Please write your message.',
            'message.min' => 'Your message is a little short. Please add a few more words.',
            'message.max' => 'Your message is too long. Please keep it under 2,000 characters.',
        ]);

        // A bot gets the normal answer and nothing is sent.
        if (FormGuard::request($request, 'contact')) {
            $mail->sendToAdmins(fn () => new ContactMessage($data['name'], $data['email'], $data['topic'], $data['message']), 'contact');
        }

        return redirect()->route('contact')->with('status', 'Thank you. Your message is on its way. We usually reply within 1 business day.');
    }
}
