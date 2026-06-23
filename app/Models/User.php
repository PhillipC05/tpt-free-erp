<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Network\UserProfile;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'magic_link_token', 'magic_link_expires_at', 'totp_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('assigned_at', 'assigned_by', 'expires_at')
            ->withTimestamps();
    }

    public function hasRole(string|array $role): bool
    {
        $roles = is_array($role) ? $role : [$role];
        $activeRoles = $this->getActiveRoleNames();

        foreach ($roles as $r) {
            if (in_array($r, $activeRoles, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return in_array($permission, $this->getPermissionNames(), true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /** @return string[] */
    private function getActiveRoleNames(): array
    {
        return $this->roles()
            ->where(fn ($q) => $q->whereNull('user_roles.expires_at')->orWhere('user_roles.expires_at', '>', now()))
            ->pluck('roles.name')
            ->all();
    }

    /** @return string[] */
    private function getPermissionNames(): array
    {
        $cacheKey = "user_permissions_{$this->id}";

        return cache()->remember($cacheKey, now()->addMinutes(5), function () {
            return \Illuminate\Support\Facades\DB::table('permissions as p')
                ->join('role_permissions as rp', 'p.id', '=', 'rp.permission_id')
                ->join('roles as r', 'r.id', '=', 'rp.role_id')
                ->join('user_roles as ur', 'r.id', '=', 'ur.role_id')
                ->where('ur.user_id', $this->id)
                ->whereNull('ur.deleted_at')
                ->where(fn ($q) => $q->whereNull('ur.expires_at')->orWhere('ur.expires_at', '>', now()))
                ->pluck('p.name')
                ->all();
        });
    }

    public function flushPermissionCache(): void
    {
        cache()->forget("user_permissions_{$this->id}");
    }
}
