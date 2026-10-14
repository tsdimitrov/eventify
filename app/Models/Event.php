<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\EventObserver;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $description
 * @property CarbonImmutable $event_date
 * @property string $type
 * @property string $status
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read int|null $tasks_count
 * @property-read int|null $completed_tasks_count
 * @property-read int|null $overdue_tasks_count
 * @property-read Collection<int, Task> $tasks
 *
 * @method static Builder<static> upcoming()
 * @method static Builder<static> past()
 * @method static Builder<static> drafts()
 * @method static Builder<static> cancelled()
 */
#[ObservedBy(EventObserver::class)]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'event_date',
        'type',
        'status',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the tasks associated with the event.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope a query to only include upcoming events.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->where('event_date', '>=', now())
            ->orderBy('event_date', 'asc');
    }

    /**
     * Scope a query to only include past events.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->where('event_date', '<', now())
            ->orderBy('event_date', 'desc');
    }

    /**
     * Scope a query to only include draft events.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('status', 'draft')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to only include cancelled events.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled')
            ->orderBy('event_date', 'desc');
    }
}
