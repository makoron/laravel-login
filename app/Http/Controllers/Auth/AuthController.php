<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
class AuthController extends Controller
{

    public function showLogin() {
        return view('login.login_form');
    }

    /** @param App\Http\Request\LoginFormRequest
    * $request
    * @return
    */

    public function login(LoginFormRequest $request) {
        $credentials = $request->only('email', 'password');

        // アカウントがロックされていたら弾く
        $user = User::where('email', '=', $credentials['email'])->first();

        if(!is_null($user)) {
            if ($user->locked_flg === 1) {
                return back()->withErrors([
                    'danger' => 'アカウントがロックされています。',
                ]);
            }

            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();
                if ($user->error_count > 0 ) {
                    $user->error_count = 0;
                    $user->save();
                }

                return redirect()->route('home')->with(
                    'success',
                    'ログイン成功しました。'
                );
            }

            $user->error_count++;
            if ($user->error_count > 5) {
                $user->locked_flg = 1;
                $user->save();
                return back()->withErrors([
                    'danger' => 'アカウントがロックされました。解除したい場合は運営者に連絡して下さい。',
                ]);

            }

            $user->save();
        }

        return back()->withErrors([
            'danger' => 'メールアドレスかパスワードが間違っています。',
        ]);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login.show')->with('logout', 'ログアウトしました！');
    }
}
