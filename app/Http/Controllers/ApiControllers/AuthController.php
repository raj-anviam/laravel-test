<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Auth;
use DB;
use App\Models\User;
use App\Models\Role;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function register(RegisterRequest $request)
    {
        try {

            $data = $request->all();

            DB::beginTransaction();
            
            $user = User::create([
                'name' => $data['name'],
                'password' => bcrypt($data['password']),
                'email' => $data['email']
            ]);

            $role = Role::whereName('customer')->first();
            $user->roles()->attach($role);

            DB::commit();

            $data = [
                'token' => $user->createToken('tokens')->plainTextToken,
                'name' => $user->name,
                'email' => $user->email,
            ];

            return $this->successResponse($data);
        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    public function login(LoginRequest $request) {
        
        try {

            if (!Auth::attempt($request->all())) {
                return $this->errorResponse('Credentials not match', 401);
            }
            
            $data = [
                'token' => Auth::user()->createToken('tokens')->plainTextToken,
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
            ];
            
            return $this->successResponse($data);
        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
