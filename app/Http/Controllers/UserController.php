<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller {
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:users|max:255',
            'email' => 'required|string|email|unique:users|',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Unsuccessful registration'
            ], 409);
        }

        $validatedData = $validator->validated();
 
        $user = new User([
            'name'  => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        if($user->save()) {
            return response()->json();;
        } else {
            return response()->json([
                'message' => 'Error while saving user data'
            ], 500);
        }
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email'=>'required|string|email',
            'password'=>'required|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Incorrect input data'
            ], 400);
        }

        $validatedData = $validator->validated();

        $user = User::where('email', $validatedData['email'])->first();
        if (!$user || !Hash::check($validatedData['password'], $user->password)){
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $accessToken = $user->createToken('CSVApp')->plainTextToken;

        return response()->json([
            'access_token' => $accessToken
        ]);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json();
    }
}
