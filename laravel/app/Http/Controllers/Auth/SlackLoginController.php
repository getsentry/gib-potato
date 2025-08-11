<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Throwable;

class SlackLoginController extends Controller
{
    /**
     * Display the login page
     *
     * @return RedirectResponse|\Illuminate\View\View
     */
    public function login()
    {
        // If already authenticated, redirect to home
        if (Auth::check()) {
            return redirect()->intended(route('home'));
        }

        return view('auth.login');
    }

    /**
     * Display the mobile login page
     *
     * @return RedirectResponse|\Illuminate\View\View
     */
    public function mobile()
    {
        // If already authenticated, redirect to home
        if (Auth::check()) {
            return redirect()->intended(route('home'));
        }

        return view('auth.login');
    }

    /**
     * Redirect to Slack OAuth
     *
     * @param string|null $workspace
     * @return RedirectResponse
     */
    public function redirect(?string $workspace = 'web'): RedirectResponse
    {
        $isMobile = $workspace === 'mobile';
        
        $url = 'https://slack.com/openid/connect/authorize?' . http_build_query([
            'scope' => 'openid,email,profile',
            'response_type' => 'code',
            'redirect_uri' => config('services.slack.redirect_uri') . ($isMobile ? '/mobile' : ''),
            'client_id' => config('services.slack.client_id'),
        ]);

        return redirect($url);
    }

    /**
     * Handle Slack OAuth callback
     *
     * @param Request $request
     * @param string|null $workspace
     * @return RedirectResponse|\Illuminate\View\View
     */
    public function callback(Request $request, ?string $workspace = 'web')
    {
        $isMobile = $workspace === 'mobile';
        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('login')->with('error', 'Authorization code not provided');
        }

        try {
            // Exchange code for token
            $response = Http::asForm()->post('https://slack.com/api/openid.connect.token', [
                'client_id' => config('services.slack.client_id'),
                'client_secret' => config('services.slack.client_secret'),
                'code' => $code,
                'redirect_uri' => config('services.slack.redirect_uri') . ($isMobile ? '/mobile' : ''),
            ]);

            $data = $response->json();

            if (!$response->successful() || !($data['ok'] ?? false)) {
                return redirect()->route('login')->with('error', 'Slack sign in failed');
            }

            // Parse the JWT token
            $parser = new Parser(new JoseEncoder());
            /** @var \Lcobucci\JWT\Token\Plain $jwt */
            $jwt = $parser->parse($data['id_token']);

            $slackUserId = $jwt->claims()->get('https://slack.com/user_id');
            $slackTeamId = $jwt->claims()->get('https://slack.com/team_id');

            // Verify team ID
            if (config('services.slack.team_id') !== $slackTeamId) {
                return redirect()->route('login')
                    ->with('error', 'You need to gib or recieve something to be able to sign in...');
            }

            // Find user
            $user = User::where('slack_user_id', $slackUserId)
                ->with('apiToken')
                ->first();

            if (!$user) {
                return redirect()->route('login')
                    ->with('error', 'You need to gib or recieve something to be able to sign in...');
            }

            if ($isMobile) {
                // For mobile, return view with token
                return view('auth.mobile', ['token' => $user->apiToken->token]);
            }

            // For web, log in the user
            Auth::login($user);

            return redirect()->intended(route('home'));

        } catch (Throwable $e) {
            report($e);
            return redirect()->route('login')->with('error', 'Slack sign in failed');
        }
    }

    /**
     * Log out the user
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}