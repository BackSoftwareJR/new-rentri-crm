<?php

namespace App\Http\Livewire;

use App\Domain\Audit\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class TimelineWidget extends Component
{
    public string $subjectType;

    public int $subjectId;

    public ?string $title = null;

    public function mount(Model $subject, ?string $title = null): void
    {
        $this->subjectType = $subject::class;
        $this->subjectId = (int) $subject->getKey();
        $this->title = $title;
    }

    public function render(ActivityLogService $audit): View
    {
        return view('livewire.timeline-widget', [
            'events' => $audit->forSubject($this->subjectType, $this->subjectId),
            'audit'  => $audit,
        ]);
    }
}
