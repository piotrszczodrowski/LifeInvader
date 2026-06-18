<?php

namespace App\Core;

class CsvExporter
{
    /**
     * Uniwersalna metoda do eksportu dowolnej tablicy danych do formatu CSV
     * Używamy średnika (;) jako separatora, by polski Excel domyślnie dobrze czytał kolumny
     */
    public static function export(string $filename, array $headers, array $data): void
    {
        // 1. Wymuszenie na przeglądarce pobrania pliku (zamiast wyświetlania go w oknie)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // 2. Otwarcie strumienia wyjściowego PHP (najbardziej wydajna pamięciowo metoda)
        $output = fopen('php://output', 'w');

        // Opcjonalnie: Dodanie BOM dla poprawnego kodowania polskich znaków (UTF-8) w starych Excelach
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // 3. Wpisanie nagłówków kolumn
        fputcsv($output, $headers, ';');

        // 4. Wpisanie wierszy z danymi
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        // 5. Zamknięcie strumienia i przerwanie dalszego ładowania strony (żeby HTML się nie dokleił)
        fclose($output);
        exit;
    }
}