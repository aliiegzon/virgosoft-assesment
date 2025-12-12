<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of tries the job has before failing
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The jobs maximum execution time in seconds
     *
     * @var int
     */
    public int $timeout = 1200;

    /**
     * Execute the job.
     */
    abstract public function handle();

    /**
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error($exception->getMessage());
    }
}
