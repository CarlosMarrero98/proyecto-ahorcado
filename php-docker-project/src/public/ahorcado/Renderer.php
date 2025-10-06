<?php
declare(strict_types=1);

// src/Renderer.php
final class Renderer {
    /**
     * Frames ASCII indexados por intentos restantes (de 6 a 0).
     * Si usas otro número de intentos, igualmente se "degrada" al bajar de 6.
     */
    private const FRAMES = [
        6 => <<<'TXT'
 +---+
 |   |
 |
 |
 |
======
TXT,
        5 => <<<'TXT'
 +---+
 |   |
 |   O
 |
 |
======
TXT,
        4 => <<<'TXT'
 +---+
 |   |
 |   O
 |   |
 |
======
TXT,
        3 => <<<'TXT'
 +---+
 |   |
 |   O
 |  /|
 |
======
TXT,
        2 => <<<'TXT'
 +---+
 |   |
 |   O
 |  /|\
 |
======
TXT,
        1 => <<<'TXT'
 +---+
 |   |
 |   O
 |  /|\
 |  /
======
TXT,
        0 => <<<'TXT'
 +---+
 |   |
 |   O
 |  /|\
 |  / \
======
TXT,
    ];

    /**
     * Devuelve el dibujo del ahorcado según intentos restantes.
     * - Recibe el número de intentos que quedan (p. ej., 6..0).
     * - Hace "clamp" por si llega un valor fuera de rango.
     * - Envueltos en <pre> para monoespaciado.
     */
    public function ascii(int $attemptsLeft): string
    {
        // Asegura rango 0..6
        if ($attemptsLeft > 6) {
            $attemptsLeft = 6;
        } elseif ($attemptsLeft < 0) {
            $attemptsLeft = 0;
        }

        $frame = self::FRAMES[$attemptsLeft] ?? self::FRAMES[0];
        return "<pre>{$frame}</pre>";
    }
}
