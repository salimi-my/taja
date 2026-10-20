<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The number of days shown in the signups chart.
     */
    private const SIGNUP_CHART_DAYS = 14;

    /**
     * Display the dashboard with generic user statistics.
     */
    public function __invoke(Request $request): Response
    {
        $startOfChart = now()->subDays(self::SIGNUP_CHART_DAYS - 1)->startOfDay();

        $signupsByDay = User::query()
            ->where('created_at', '>=', $startOfChart)
            ->get(['created_at'])
            ->countBy(fn (User $user): string => $user->created_at->toDateString());

        $signups = collect(range(0, self::SIGNUP_CHART_DAYS - 1))
            ->map(function (int $offset) use ($startOfChart, $signupsByDay): array {
                $date = $startOfChart->copy()->addDays($offset);

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('M j'),
                    'signups' => $signupsByDay->get($date->toDateString(), 0),
                ];
            })
            ->all();

        return Inertia::render('dashboard', [
            'stats' => [
                'total_users' => User::count(),
                'verified_users' => User::whereNotNull('email_verified_at')->count(),
                'admin_users' => User::where('role', UserRole::Admin)->count(),
                'new_users' => User::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            ],
            'signups' => $signups,
            'recentUsers' => UserResource::collection(User::latest()->latest('id')->limit(6)->get()),
        ]);
    }
}
