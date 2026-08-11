<?php

namespace App\Services\Tachograph;

use Carbon\Carbon;

class TachographC1BParser
{
    public function parse($binary, $filename = null)
    {
        $strings = $this->printableStrings($binary);
        $cardNumber = $this->findCardNumber($strings, $filename);
        list($lastName, $firstName) = $this->findDriverName($strings, $cardNumber);
        $vehiclePlates = $this->findVehiclePlates($strings);
        $issuingAuthority = $this->findIssuingAuthority($strings);

        $records = $this->findBestDailyActivityChain($binary);
        $sessions = $this->buildDrivingSessions($records, $cardNumber);
        $dailySummaries = $this->buildDailySummaries($records, $cardNumber);

        return [
            'card_number' => $cardNumber,
            'driver_last_name' => $lastName,
            'driver_first_name' => $firstName,
            'vehicle_plates' => $vehiclePlates,
            'issuing_authority' => $issuingAuthority,
            'activity_from' => count($records) ? $records[0]['date'] : null,
            'activity_to' => count($records) ? $records[count($records) - 1]['date'] : null,
            'records_count' => count($records),
            'daily_summaries' => $dailySummaries,
            'drives' => $sessions,
        ];
    }

    private function printableStrings($binary)
    {
        preg_match_all('/[\x20-\x7E]{4,}/', $binary, $matches);
        return array_map(function ($value) {
            return trim($value);
        }, $matches[0]);
    }

    private function findCardNumber(array $strings, $filename)
    {
        foreach ($strings as $value) {
            if (preg_match('/\b\d{14,20}\b/', $value, $match)) {
                return $match[0];
            }
        }

        if ($filename && preg_match('/(\d{14,20})/', $filename, $match)) {
            return $match[1];
        }

        return null;
    }

    private function findDriverName(array $strings, $cardNumber)
    {
        $clean = array_values(array_filter($strings, function ($value) {
            if (preg_match('/^\d+$/', $value)) {
                return false;
            }

            if (preg_match('/^[A-Z][A-Z \'-]{2,}$/', $value) !== 1) {
                return false;
            }

            return stripos($value, 'IMPRIMERIE') === false
                && stripos($value, 'PREFECTURE') === false;
        }));

        if ($cardNumber) {
            $cardIndex = array_search($cardNumber, $strings, true);
            if ($cardIndex !== false) {
                $afterCard = array_slice($strings, $cardIndex + 1, 8);
                $names = array_values(array_filter($afterCard, function ($value) {
                    return preg_match('/^[A-Z][A-Z \'-]{2,}$/', $value) === 1
                        && stripos($value, 'IMPRIMERIE') === false
                        && stripos($value, 'PREFECTURE') === false;
                }));

                if (count($names) >= 2) {
                    return [trim($names[0]), trim($names[1])];
                }
            }
        }

        return [isset($clean[0]) ? trim($clean[0]) : null, isset($clean[1]) ? trim($clean[1]) : null];
    }

    private function findVehiclePlates(array $strings)
    {
        $plates = [];

        foreach ($strings as $value) {
            $value = strtoupper(trim($value));
            if (preg_match('/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', $value)) {
                $plates[] = $value;
            }
        }

        return array_values(array_unique($plates));
    }

    private function findIssuingAuthority(array $strings)
    {
        foreach ($strings as $value) {
            $value = trim($value);
            if (stripos($value, 'PREFECTURE') !== false) {
                return $value;
            }
        }

        return null;
    }

    private function findBestDailyActivityChain($binary)
    {
        $length = strlen($binary);
        $best = [];

        for ($offset = 0; $offset < $length - 12; $offset++) {
            $chain = [];
            $cursor = $offset;

            while ($cursor < $length - 12) {
                $record = $this->readDailyRecord($binary, $cursor);
                if (!$record) {
                    break;
                }

                $chain[] = $record;
                $cursor += $record['length'];

                if (count($chain) > 5000) {
                    break;
                }
            }

            if (count($chain) > count($best)) {
                $best = $chain;
            } elseif (count($chain) === count($best) && count($chain) && count($best)) {
                if ($chain[count($chain) - 1]['date'] > $best[count($best) - 1]['date']) {
                    $best = $chain;
                }
            }
        }

        return $best;
    }

    private function readDailyRecord($binary, $offset)
    {
        $fileLength = strlen($binary);
        if ($offset + 12 > $fileLength) {
            return null;
        }

        $recordLength = $this->uint16($binary, $offset);
        if ($recordLength < 10 || $recordLength > 1000 || $offset + $recordLength > $fileLength) {
            return null;
        }

        if (($recordLength - 10) % 2 !== 0) {
            return null;
        }

        $timestamp = $this->uint32($binary, $offset + 2);
        try {
            $day = Carbon::createFromTimestampUTC($timestamp);
        } catch (\Exception $exception) {
            return null;
        }

        if ((int) $day->format('H') !== 0 || (int) $day->format('i') !== 0 || (int) $day->format('s') !== 0) {
            return null;
        }

        if ($day->lt(Carbon::create(2006, 1, 1, 0, 0, 0, 'UTC')) || $day->gt(Carbon::create(2035, 1, 1, 0, 0, 0, 'UTC'))) {
            return null;
        }

        $changes = [];
        for ($position = $offset + 10; $position < $offset + $recordLength; $position += 2) {
            $value = $this->uint16($binary, $position);
            $minute = $value & 0x07ff;
            $activity = ($value >> 11) & 0x03;

            if ($minute > 1439) {
                return null;
            }

            $changes[] = [
                'minute' => $minute,
                'activity' => $activity,
            ];
        }

        usort($changes, function ($a, $b) {
            return $a['minute'] <=> $b['minute'];
        });

        return [
            'length' => $recordLength,
            'date' => $day->format('Y-m-d'),
            'distance' => $this->uint16($binary, $offset + 8),
            'changes' => $changes,
        ];
    }

    private function buildDrivingSessions(array $records, $cardNumber)
    {
        $sessions = [];
        $gapLimitMinutes = 15;

        foreach ($records as $record) {
            $current = null;
            $drivingMinutes = 0;
            $changes = $record['changes'];

            for ($index = 0; $index < count($changes) - 1; $index++) {
                $change = $changes[$index];
                $next = $changes[$index + 1];

                if ($next['minute'] <= $change['minute']) {
                    continue;
                }

                if ((int) $change['activity'] !== 3) {
                    continue;
                }

                $start = $change['minute'];
                $end = $next['minute'];

                if ($current && $start - $current['end_minute'] <= $gapLimitMinutes) {
                    $current['end_minute'] = $end;
                } else {
                    if ($current) {
                        $sessions[] = $this->formatSession($record, $current, $drivingMinutes, $cardNumber);
                    }

                    $current = [
                        'start_minute' => $start,
                        'end_minute' => $end,
                    ];
                    $drivingMinutes = 0;
                }

                $drivingMinutes += $end - $start;
            }

            if ($current) {
                $sessions[] = $this->formatSession($record, $current, $drivingMinutes, $cardNumber);
            }
        }

        return array_values(array_filter($sessions, function ($session) {
            return $session['duration_minutes'] > 0;
        }));
    }

    private function buildDailySummaries(array $records, $cardNumber)
    {
        $summaries = [];

        foreach ($records as $record) {
            $minutes = [
                'driving_minutes' => 0,
                'work_minutes' => 0,
                'availability_minutes' => 0,
                'rest_minutes' => 0,
                'unknown_minutes' => 0,
            ];

            $changes = $record['changes'];

            for ($index = 0; $index < count($changes); $index++) {
                $change = $changes[$index];
                $nextMinute = isset($changes[$index + 1]) ? $changes[$index + 1]['minute'] : 1440;

                if ($nextMinute <= $change['minute']) {
                    continue;
                }

                $duration = $nextMinute - $change['minute'];

                if ((int) $change['activity'] === 3) {
                    $minutes['driving_minutes'] += $duration;
                } elseif ((int) $change['activity'] === 2) {
                    $minutes['work_minutes'] += $duration;
                } elseif ((int) $change['activity'] === 1) {
                    $minutes['availability_minutes'] += $duration;
                } elseif ((int) $change['activity'] === 0) {
                    $minutes['rest_minutes'] += $duration;
                } else {
                    $minutes['unknown_minutes'] += $duration;
                }
            }

            $summaries[] = array_merge($minutes, [
                'source_date' => $record['date'],
                'daily_distance_km' => $record['distance'],
                'summary_uid' => sha1($cardNumber . '|' . $record['date']),
            ]);
        }

        return $summaries;
    }

    private function formatSession(array $record, array $session, $drivingMinutes, $cardNumber)
    {
        $timezone = config('app.timezone', 'Europe/Paris');
        $start = Carbon::parse($record['date'], $timezone)->startOfDay()->addMinutes($session['start_minute']);
        $end = Carbon::parse($record['date'], $timezone)->startOfDay()->addMinutes($session['end_minute']);

        $uid = sha1(implode('|', [
            $cardNumber,
            $record['date'],
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            $drivingMinutes,
        ]));

        return [
            'source_date' => $record['date'],
            'started_at' => $start->format('Y-m-d H:i:s'),
            'ended_at' => $end->format('Y-m-d H:i:s'),
            'duration_minutes' => $drivingMinutes,
            'daily_distance_km' => $record['distance'],
            'activity_uid' => $uid,
        ];
    }

    private function uint16($binary, $offset)
    {
        return unpack('n', substr($binary, $offset, 2))[1];
    }

    private function uint32($binary, $offset)
    {
        return unpack('N', substr($binary, $offset, 4))[1];
    }
}
