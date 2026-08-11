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
        $syncFrom = $from === null ? false : DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $from);
        $lastSync = $last === null ? false : DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $last);
        $syncUntil = $until === null ? false : DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $until);

        // konto_type.sync_from is nullable, and "clone false" is a fatal error - so fall
        // back to the epoch and let the bank decide how far back it will go.
        if ($syncFrom === false) {
            $syncFrom = date_create('1970-01-01');
        }

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
