<?php
declare(strict_types=1);

class WordProvider {
    private string $filePath;
    
    private ?array $wordsCache = null;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    /** Devuelve una palabra aleatoria (MAYÚSCULAS, sin tildes, mantiene Ñ). */
    public function randomWord(): string {
        if ($this->wordsCache === null) {
            $this->loadWords();
        }

        if (empty($this->wordsCache)) {
            throw new RuntimeException("No hay palabras válidas en: {$this->filePath}");
        }

        $idx = random_int(0, count($this->wordsCache) - 1);
        return $this->wordsCache[$idx];
    }

    // -------------------
    // Internos / helpers
    // -------------------

    /** Carga y normaliza todas las palabras desde el fichero (cachea en memoria). */
    private function loadWords(): void {
        if (!is_file($this->filePath) || !is_readable($this->filePath)) {
            throw new RuntimeException("No se puede leer el fichero de palabras: {$this->filePath}");
        }

        // Lee líneas (sin saltos); si falla, devuelve false → lo tratamos como array vacío
        $lines = @file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Error leyendo el fichero: {$this->filePath}");
        }

        $words = [];
        foreach ($lines as $line) {
            $line = trim($line);

            // (Opcional) permite comentarios con '#'
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $norm = self::normalizeWord($line); // MAYÚSCULAS, sin tildes, solo [A-ZÑ]
            if ($norm !== '') {
                $words[] = $norm;
            }
        }

        // Elimina duplicados y reindexa
        $words = array_values(array_unique($words));

        $this->wordsCache = $words;
    }

    /** Normaliza palabra: quita tildes, mantiene Ñ, mayúsculas y filtra a [A-ZÑ]. */
    private static function normalizeWord(string $s): string {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        $s = self::stripAccentsKeepEnye($s);
        $s = mb_strtoupper($s, 'UTF-8');

        // Filtra solo letras A-Z y Ñ (quita espacios, guiones, etc.)
        $s = preg_replace('/[^A-ZÑ]/u', '', $s) ?? '';

        return $s;
    }

    /** Quita acentos (ÁÉÍÓÚÜ/áéíóúü) y mantiene Ñ/ñ → ñ/Ñ según mayúsculas luego. */
    private static function stripAccentsKeepEnye(string $s): string {
        // Mapeo manual suficiente para español
        $map = [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U',
            'À'=>'A','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U',
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u',
            'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
            // Ñ/ñ se mantienen tal cual; el strtoupper posterior hará Ñ
        ];
        return strtr($s, $map);
    }
}
