<?php

if (!function_exists('formatirajDatum')) {
    /**
     * Formatira različite tipove ulaza u dd.mm.yyyy format (ili prosleđeni format).
     *
     * @param mixed $value Datum kao string, timestamp ili DateTimeInterface.
     */
    function formatirajDatum($value, string $placeholder = '—', string $format = 'd.m.Y'): string
    {
        if ($value === null) {
            return $placeholder;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($format);
        }

        $stringValue = trim((string)$value);

        if ($stringValue === '' || $stringValue === '0000-00-00' || $stringValue === '0000-00-00 00:00:00') {
            return $placeholder;
        }

        // Ako je prosleđen timestamp
        if (ctype_digit($stringValue)) {
            $timestamp = (int)$stringValue;
            if ($timestamp <= 0) {
                return $placeholder;
            }

            return date($format, $timestamp);
        }

        try {
            $date = new DateTimeImmutable($stringValue);
            return $date->format($format);
        } catch (Exception $e) {
            $timestamp = strtotime($stringValue);

            return $timestamp ? date($format, $timestamp) : $placeholder;
        }
    }
}

if (!function_exists('formatirajDatumVreme')) {
    /**
     * Formatira datum sa vremenom u formatu dd.mm.yyyy HH:ii.
     *
     * @param mixed $value Datum/vreme kao string, timestamp ili DateTimeInterface.
     */
    function formatirajDatumVreme($value, string $placeholder = '—', string $format = 'd.m.Y H:i'): string
    {
        return formatirajDatum($value, $placeholder, $format);
    }
}

