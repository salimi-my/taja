<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\UserIndexRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a paginated, searchable and filterable list of users.
     */
    public function index(UserIndexRequest $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'roles' => $request->array('roles'),
            'status' => $request->array('status'),
            'start_date' => $request->string('start_date')->toString(),
            'end_date' => $request->string('end_date')->toString(),
            'sort' => $request->string('sort', 'created_at')->toString(),
            'direction' => $request->string('direction', 'desc')->toString(),
            'per_page' => $request->integer('per_page', 10),
        ];

        $users = User::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $term = '%'.addcslashes($filters['search'], '%_\\').'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($filters['roles'] !== [], fn (Builder $query) => $query->whereIn('role', $filters['roles']))
            ->when(count($filters['status']) === 1, fn (Builder $query) => in_array('verified', $filters['status'], true)
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at'))
            ->when($filters['start_date'] !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['start_date']))
            ->when($filters['end_date'] !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['end_date']))
            ->when(
                $filters['direction'] === 'desc',
                fn (Builder $query) => $query->orderByDesc($filters['sort']),
                fn (Builder $query) => $query->orderBy($filters['sort']),
            )
            ->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('users/index', [
            'users' => UserResource::collection($users),
            'filters' => $filters,
            'roles' => UserRole::options(),
            'perPageOptions' => UserIndexRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        $user->markEmailAsVerified();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return back();
    }

    /**
     * Update the given user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->except('password'));

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return back();
    }

    /**
     * Delete the given user.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return back();
    }
}
