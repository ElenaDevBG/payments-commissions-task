<?php

namespace App\Traits;

use Carbon\Carbon;

trait DateTrait
{
    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = Carbon::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    private function findFirstAndLastDatesOfWeek($date)
    {
        $dateObject = Carbon::createFromFormat('Y-m-d', $date);

        return [
            'monday' => $dateObject->startOfWeek()->format('Y-m-d'),
            'sunday' => $dateObject->endOfWeek()->format('Y-m-d')
        ];
    }
}
