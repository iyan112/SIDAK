<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\CategoryContentsController;
use App\Http\Controllers\ContentsController;

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return response()->json(['user' => Auth::user()]);
    }

    throw ValidationException::withMessages([
        'email' => ['Email atau kata sandi yang Anda masukkan salah.'],
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());

    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Logout berhasil']);
    });
});

Route::apiResource('categories', CategoryContentsController::class);
Route::apiResource('contents', ContentsController::class);