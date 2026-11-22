<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Collection;

class IcsService
{
    // Generate RFC 5545 .ics calendar content for the given events
    public function generate(Collection $events, string $host = 'iis.local'): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//IIS FIT VUT//Sprava udalosti//CS',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        $dtStamp = now()->utc()->format('Ymd\THis\Z');

        foreach ($events as $event) {
            $location = $event->venue
                ? trim($event->venue->name . ', ' . $event->venue->street . ', ' . $event->venue->city)
                : '';

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:event-' . $event->id . '@' . $host;
            $lines[] = 'DTSTAMP:' . $dtStamp;
            $lines[] = 'DTSTART:' . $event->starts_at->copy()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:' . $event->ends_at->copy()->utc()->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:' . $this->escapeText($event->title);
            $lines[] = 'DESCRIPTION:' . $this->escapeText($event->description);
            if ($location !== '') {
                $lines[] = 'LOCATION:' . $this->escapeText($location);
            }
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    private function escapeText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(["\r\n", "\r", "\n"], '\n', $text);
        $text = str_replace([',', ';'], ['\,', '\;'], $text);

        return $text;
    }
}
