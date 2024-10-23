<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LateClockInEvent
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $attendance;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

}
