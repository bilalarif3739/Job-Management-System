<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidCredentialsException;
use App\Jobs\EmailJob;
use App\Models\Company;
use App\Models\EmailInvitation;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;


class InvitationController extends Controller
{






    // **function for email sending when user is registering 

    public static function sendemail(User $user): string
    {
        $email = $user['email'];

        $token = EmailService::create($user, 0);

        //EmailJob::dispatch($email, $token);

        return $token;

    }

    // **GET   /auth/verify/{token}

    public function accountverify(Request $request, string $token)
    {

        $record = EmailInvitation::where('token', $token)->first();
        $user = User::find($record->user_id);

        if (!is_null($user->email_verified_at)) {
            return Response::success('Your email is already verified.');
        }

        // Mark email as verified
        $user->update(['email_verified_at' => now()]);

        // delete token
        $record->delete();

        return Response::success('Your email has been verified.');


    }



    public static function forgetpassword()
    {



        $validatedData = request()->validate([
            'email' => ['required', 'email:rfc,dns', 'regex:/^\S+$/u'],
        ], [
            'email.email' => 'Please enter valid email address.',
            'email.regex' => 'The email address cannot contain any white spaces.',
        ]);
        $user = \App\Models\User::where('email', $validatedData['email'])->first();

        if (!$user) {
            throw new InvalidCredentialsException('Email Address not Found');
        }

        $email = $validatedData['email'];

        $token = EmailService::createpass($user);

        EmailJob::dispatch($email, $token, 'forgetpassword');

        return Response::success(
            'Password Reset  Link has been sent to above  email.',
            ['reset-pass-token' => $token]
        );

    }



}
