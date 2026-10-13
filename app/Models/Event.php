<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\EventObserver;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property-read int|null $tasks_count
 * @property-read int|null $completed_tasks_count
 * @property-read int|null $overdue_tasks_count
 * @property-read Collection<int, Task> $tasks
 */
#[ObservedBy(EventObserver::class)]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

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
}
