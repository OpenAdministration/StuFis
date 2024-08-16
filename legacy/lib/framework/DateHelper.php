<?php

namespace framework;

use DateTime;

class DateHelper
{
    /**
     * @return array [DateTime, DateTime]
     */
    public static function fromUntilLast(?string $from, ?string $until, ?string $last): array
    {
        $syncFrom = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $from);
        $lastSync = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $last);
        $syncUntil = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $until);

        // set default for lastsync if unset
        if ($lastSync === false) {
            $lastSync = clone $syncFrom;
        }

        // if unset or in the future, cut it down to now - some banks do not like dates in the future
        if ($syncUntil === false || $syncUntil > date_create()) {
            $syncUntil = date_create();
        }

        // find older date
        $startDate = max($lastSync, $syncFrom);

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
