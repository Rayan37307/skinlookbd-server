<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'action', 'changes', 'ip_address'])]
class AuditLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public static function record(User $actor, string $action, Model $auditable, array $changes = []): self
    {
        $log = new self([
            'user_id' => $actor->id,
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
        ]);
        $log->auditable()->associate($auditable);
        $log->save();

        return $log;
    }
}
