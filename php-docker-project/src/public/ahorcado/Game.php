<?php

class Game {
    /** Palabra objetivo en MAYÚSCULAS, sin tildes. Puede contener Ñ. */
    private string $word;

    /** Número máximo de intentos al empezar la partida. */
    private int $maxAttempts;

    /** Intentos restantes (no baja de 0). */
    private int $attemptsLeft;

    /** Letras usadas (MAYÚSCULAS, sin tildes, únicas, tamaño 1). */
    private array $usedLetters = [];

    /**
     * Crea partida nueva o restaura desde estado previo.
     * - Si $state es null → partida nueva con $word y $maxAttempts.
     * - Si $state tiene datos → ignora $word/$maxAttempts y Restaura.
     */
    public function __construct(string $word, int $maxAttempts = 6, ?array $state = null) {
        if ($state !== null) {
            $this->restoreFromState($state);
            return;
        }

        $word = self::normalizeWord($word);
        if ($word === '') {
            throw new InvalidArgumentException('La palabra no puede estar vacía.');
        }

        $this->word        = $word;
        $this->maxAttempts = max(1, $maxAttempts);
        $this->attemptsLeft = $this->maxAttempts;
        $this->usedLetters = [];
    }

    /**
     * Procesa una letra.
     * Reglas:
     * - Si la partida terminó, no hace nada.
     * - Normaliza y valida la letra (1 carácter A-Z o Ñ).
     * - Si ya estaba usada, no descuenta intento.
     * - Si no está en la palabra, descuenta 1 intento (mínimo 0).
     */
    public function guessLetter(string $letter): void {
        if ($this->isWon() || $this->isLost()) {
            return;
        }

        $letter = self::normalizeLetter($letter);
        if ($letter === null) {
            // Letra inválida (número, símbolo, vacío, más de 1 char)
            return;
        }

        if (in_array($letter, $this->usedLetters, true)) {
            return; // Repetida: no cambia nada
        }

        $this->usedLetters[] = $letter;

        if (strpos($this->word, $letter) === false) {
            $this->attemptsLeft = max(0, $this->attemptsLeft - 1);
        }
    }

    /** Devuelve la palabra enmascarada, p. ej.: " _ A _ A _ O " */
    public function getMaskedWord(): string {
        $chars = self::splitChars($this->word);
        $mask = [];

        foreach ($chars as $ch) {
            $mask[] = in_array($ch, $this->usedLetters, true) ? $ch : '_';
        }

        return ' ' . implode(' ', $mask) . ' ';
    }

    public function getAttemptsLeft(): int {
        return $this->attemptsLeft;
    }

    /** Devuelve las letras usadas (puedes ordenarlas si prefieres). */
    public function getUsedLetters(): array {
        return $this->usedLetters;
    }

    /** Gana cuando TODAS las letras distintas de la palabra han sido usadas. */
    public function isWon(): bool {
        $unique = array_values(array_unique(self::splitChars($this->word)));
        $missing = array_diff($unique, $this->usedLetters);
        return count($missing) === 0;
    }

    /** Pierde cuando no quedan intentos y no ha ganado. */
    public function isLost(): bool {
        return $this->attemptsLeft === 0 && !$this->isWon();
    }

    /** Devuelve la palabra completa (útil para mostrar al final). */
    public function getWord(): string {
        return $this->word;
    }

    /**
     * Serializa el estado mínimo para guardarlo en sesión.
     * Este array es el que debes meter en $_SESSION['ahorcado']['state'].
     */
    public function toState(): array {
        return [
            'word'         => $this->word,
            'maxAttempts'  => $this->maxAttempts,
            'attemptsLeft' => $this->attemptsLeft,
            'usedLetters'  => $this->usedLetters,
        ];
    }

    /**
     * (Interno) Restaura el juego desde un array de estado.
     * Valida y corrige inconsistencias básicas.
     */
    private function restoreFromState(array $state): void {
        $word        = isset($state['word']) ? (string)$state['word'] : '';
        $maxAtt      = isset($state['maxAttempts']) ? (int)$state['maxAttempts'] : 6;
        $left        = isset($state['attemptsLeft']) ? (int)$state['attemptsLeft'] : $maxAtt;
        $used        = isset($state['usedLetters']) && is_array($state['usedLetters']) ? $state['usedLetters'] : [];

        $word = self::normalizeWord($word);
        if ($word === '') {
            throw new InvalidArgumentException('Estado inválido: palabra vacía.');
        }

        $maxAtt = max(1, $maxAtt);
        $left   = max(0, min($left, $maxAtt));

        // Normaliza cada letra usada y quita duplicados/invalidas
        $normUsed = [];
        foreach ($used as $l) {
            $n = self::normalizeLetter((string)$l);
            if ($n !== null && !in_array($n, $normUsed, true)) {
                $normUsed[] = $n;
            }
        }

        $this->word = $word;
        $this->maxAttempts = $maxAtt;
        $this->attemptsLeft = $left;
        $this->usedLetters = $normUsed;
    }

    // =========================
    // Helpers de normalización
    // =========================

    /** Normaliza una palabra: quita tildes, mantiene Ñ, mayúsculas y filtra a [A-ZÑ]. */
    private static function normalizeWord(string $s): string {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        $s = self::stripAccentsKeepEnye($s);
        $s = strtoupper($s);
        // Filtra solo letras A-Z y Ñ
        $s = preg_replace('/[^A-ZÑ]/u', '', $s) ?? '';
        return $s;
    }

    /**
     * Normaliza una letra y valida que sea exactamente un carácter A-Z o Ñ.
     * Devuelve la letra normalizada o null si no es válida.
     */
    private static function normalizeLetter(string $s): ?string {
        $s = trim($s);
        // Si te llega "ab" o vacío, inválido
        if (mb_strlen($s, 'UTF-8') !== 1) {
            return null;
        }
        $s = self::stripAccentsKeepEnye($s);
        $s = strtoupper($s);
        if (!preg_match('/^[A-ZÑ]$/u', $s)) {
            return null;
        }
        return $s;
    }

    /**
     * Quita acentos (ÁÉÍÓÚÜ áéíóúü) y mantiene Ñ/ñ → Ñ.
     * No requiere extensiones externas.
     */
    private static function stripAccentsKeepEnye(string $s): string {
        // Primero mapea manualmente vocales acentuadas y diéresis
        $map = [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U',
            'À'=>'A','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U',
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u',
            'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
            // Mantener ñ/Ñ tal cual (no convertir a n)
        ];
        $s = strtr($s, $map);
        return $s;
    }

    /** Separa una cadena UTF-8 en caracteres individuales (incluye Ñ). */
    private static function splitChars(string $s): array {
        // PREG_SPLIT_NO_EMPTY evita cadenas vacías al principio/fin
        $arr = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        return $arr === false ? [] : $arr;
    }
}
