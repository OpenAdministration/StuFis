<?php

namespace framework;

use DateTime;

class DateHelper
{
    /**
     * @return array{0: ?DateTime, 1: DateTime} start date (null = let the bank decide how far
     *                                          back it goes) and end date
     */
    public static function fromUntilLast(?string $from, ?string $until, ?string $last): array
    {
        $syncFrom = $from === null ? false : DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $from);
        $lastSync = $last === null ? false : DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $last);
        $syncUntil = $until === null ? false : DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $until);

        // if unset or in the future, cut it down to now - some banks do not like dates in the future
        if ($syncUntil === false || $syncUntil > date_create()) {
            $syncUntil = date_create();
        }

        // konto_type.sync_from is nullable (three of six accounts here have no start date),
        // and "clone false" on it is a fatal error. Rather than inventing a date - banks
        // are picky about them and only retain a limited history anyway - no start date is
        // reported at all, which leaves the range to the bank's own default.
        if ($syncFrom === false && $lastSync === false) {
            return [null, $syncUntil];
        }

        // set default for lastsync if unset
        if ($lastSync === false) {
            $lastSync = clone $syncFrom;
        }
        if ($syncFrom === false) {
            $syncFrom = clone $lastSync;
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
