<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirestoreService;
use App\Services\DateTimeService;

class AutoPublishDailyLights extends Command
{
    protected $signature = 'daily-lights:auto-publish';
    protected $description = 'Auto-publish scheduled daily lights whose publish date+time has passed';

    public function handle(FirestoreService $firestore): int
    {
        // Per-date log file (same file as notification cron, same day)
        $today   = now('UTC')->format('Y-m-d');
        $logPath = storage_path("logs/cron-{$today}.log");
        $monolog = new \Monolog\Logger('cron');
        $monolog->pushHandler(new \Monolog\Handler\StreamHandler($logPath, \Monolog\Level::Debug));
        $log = new \Illuminate\Log\Logger($monolog);

        $log->info('AutoPublish cron started', ['utc_now' => now('UTC')->toIso8601String()]);

        try {
            $items = $firestore->getDailyLights();
        } catch (\Exception $e) {
            $log->error('AutoPublish cron: failed to fetch daily lights', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }

        $scheduled = array_filter($items, fn($d) =>
            ($d['fields']['status']['stringValue'] ?? '') === 'scheduled'
        );

        $log->info('AutoPublish cron: documents checked', [
            'total'     => count($items),
            'scheduled' => count($scheduled),
        ]);

        $count = 0;

        foreach ($scheduled as $id => $data) {
            $fields = $data['fields'];

            try {
                $utcDatetime = $fields['publishDateTimeUtc']['stringValue'] ?? '';

                // Fallback to legacy date+time fields for old records
                if (!$utcDatetime) {
                    $rawDate = $fields['date']['stringValue'] ?? '';
                    $rawTime = $fields['publishTime']['stringValue'] ?? '';
                    if (!$rawDate) {
                        $log->info("AutoPublish [{$id}]: skipped — no publishDateTimeUtc and no legacy date field");
                        continue;
                    }
                    $utcDatetime = DateTimeService::brazilToUtc($rawDate, $rawTime ?: null);
                }

                if (DateTimeService::isUtcInThePast($utcDatetime)) {
                    if ($firestore->updateDailyLightStatus($id, 'published')) {
                        $count++;
                        $log->info("AutoPublish [{$id}]: SUCCESS — status set to published", [
                            'publishDateTimeUtc' => $utcDatetime,
                        ]);
                        $this->info("Published: {$id}");
                    } else {
                        $log->error("AutoPublish [{$id}]: FAILED — Firestore update returned false");
                    }
                } else {
                    $log->info("AutoPublish [{$id}]: skipped — publish time not yet passed", [
                        'publishDateTimeUtc' => $utcDatetime,
                        'utc_now'            => now('UTC')->toIso8601String(),
                    ]);
                }
            } catch (\Exception $e) {
                $log->error("AutoPublish [{$id}]: exception", ['error' => $e->getMessage()]);
                $this->error("Error processing {$id}: " . $e->getMessage());
            }
        }

        $log->info('AutoPublish cron finished', ['published_count' => $count]);
        $this->info("Done. {$count} daily light(s) auto-published.");
        return Command::SUCCESS;
    }
}
