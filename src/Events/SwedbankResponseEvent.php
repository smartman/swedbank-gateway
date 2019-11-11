<?php

namespace Smartman\Swedbank\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Smartman\Swedbank\SwedbankRequest;

class SwedbankResponseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $swedbank_request;
    public $success;

    public function __construct(SwedbankRequest $swedbank_request, $success)
    {
        $this->swedbank_request = $swedbank_request;
        $this->success          = $success;
    }

}
