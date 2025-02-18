<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Actions\Action;
use http\Client\Curl\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->to(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        $response = Http::post(env('GRAPHQL_ENDPOINT'), [
            'query' => '
                mutation login($input: LoginInput!) {
                    login(input: $input) {
                        tokenType
                        expiresIn
                        accessToken
                        refreshToken
                    }
                }
            ',
            'variables' => [
                'input' => [
                    'email' => $data['email'],
                    'password' => $data['password'],
                ],
            ],
        ]);

        $body = $response->json();

        if (isset($body['errors'])) {
            throw ValidationException::withMessages([
                'data.email' => __('Invalid email or password'),
            ]);
        }

        $token = $body['data']['login']['accessToken'];

        // Lưu token vào session hoặc cookie
        session(['accessToken' => $token]);

        $userResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post(env('GRAPHQL_ENDPOINT'), [
            'query' => '
            query me {
                me {
                    id
                    email
                    name
                }
            }
        ',
        ]);

        $userData = $userResponse->json();

        if (!isset($userData['data']['me'])) {
            throw ValidationException::withMessages([
                'data.email' => __('Failed to retrieve user data'),
            ]);
        }

        $userId = $userData['data']['me']['id'];

        // 👉 Kiểm tra xem user có trong database không
        $user = \App\Models\User::where('email', $data['email'])->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'data.email' => __('User not found in local database'),
            ]);
        }

        // 👉 Đăng nhập user vào Laravel
        Auth::login($user);

        // 👉 Kiểm tra quyền truy cập panel
        if (($user instanceof FilamentUser) && !$user->canAccessPanel(Filament::getCurrentPanel())) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        // Chuyển hướng sau khi đăng nhập thành công
        return app(LoginResponse::class);
    }

    public function form(Form $form): Form
    {
        return $form;
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('filament-panels::pages/auth/login.form.remember.label'));
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }
}
