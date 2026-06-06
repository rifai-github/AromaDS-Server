<?php

namespace App\Services;

use App\Models\MobileSyncLog;
use Illuminate\Http\Request;

class MobileSyncLogService
{
    public function record(
        Request $request,
        string $action,
        ?int $jobScheduleId = null,
        ?int $jobScheduleRoomId = null,
        string $status = 'synced',
        ?string $errorMessage = null
    ): ?MobileSyncLog {
        $hasClientMetadata = collect([
            'client_clicked_at',
            'client_delivery_mode',
            'client_queued_at',
            'client_synced_at',
            'idempotency_key',
        ])->contains(fn ($key) => $request->filled($key));

        if (! $hasClientMetadata) {
            return null;
        }

        $payload = [
            'user_id' => optional($request->user())->id,
            'job_schedule_id' => $jobScheduleId,
            'job_schedule_room_id' => $jobScheduleRoomId,
            'action' => $action,
            'idempotency_key' => $request->input('idempotency_key'),
            'client_clicked_at' => $this->dateOrNull($request->input('client_clicked_at')),
            'client_delivery_mode' => $request->input('client_delivery_mode'),
            'client_queued_at' => $this->dateOrNull($request->input('client_queued_at')),
            'client_synced_at' => $this->dateOrNull($request->input('client_synced_at')),
            'server_received_at' => now(),
            'sync_status' => $status,
            'payload_hash' => $this->payloadHash($request),
            'error_message' => $errorMessage,
        ];

        if ($payload['idempotency_key']) {
            return MobileSyncLog::updateOrCreate(
                ['idempotency_key' => $payload['idempotency_key']],
                $payload
            );
        }

        return MobileSyncLog::create($payload);
    }

    private function dateOrNull(mixed $value): mixed
    {
        if (! $value) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function payloadHash(Request $request): string
    {
        $payload = $request->except([
            'photo',
            'photos',
            'before_photos',
            'after_photos',
            'pic_photo',
            'signature',
        ]);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
