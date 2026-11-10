<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UserPasswordResetLinkController extends Controller
{
    /**
     * Email a password reset link to the given user.
     */
    public function __invoke(User $user): RedirectResponse
    {
        Gate::authorize('sendPasswordResetLink', $user);

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('The email could not be sent. Please try again in a moment.')]);

            return back();
        }

        Inertia::flash('toast', $status === Password::RESET_LINK_SENT
            ? ['type' => 'success', 'message' => __('Password reset link sent.')]
            : ['type' => 'error', 'message' => __($status)]);

        return back();
    }
}
