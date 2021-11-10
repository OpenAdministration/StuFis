<?php

namespace framework;

use DateTime;

class DateHelper
{
    public static function fromUntilLast(?string $from, ?string $until, ?string $last)
    {
        $syncFrom = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $from);
        $lastSync = DateTime::createFromFormat(DBConnector::SQL_DATETIME_FORMAT, $last);
        $syncUntil = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $until);

        // set default for lastsync
        if ($lastSync === false) {
            $lastSync = clone $syncFrom;
        }

        if ($syncUntil === false) {
            $syncUntil = date_create();
        }

        if ($syncUntil->diff(date_create())->invert === -1) { // if in the future
            $syncUntil = date_create();
        }

        //find earliest
        if ($syncFrom->diff($lastSync)->invert === 0) { //if last sync is older
            $startDate = $lastSync;
        } else {
            $startDate = $syncFrom;
        }

        return [$startDate, $syncUntil];
    }

    public static function fromDb(?string $sqlDateString): DateTime
    {
        if (is_null($sqlDateString)) {
            return date_create();
        }
        return DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $sqlDateString);
    }
}
