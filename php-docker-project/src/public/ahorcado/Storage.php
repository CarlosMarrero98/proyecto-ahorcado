<?php
declare(strict_types=1);

class Storage {
    private string $key;

    public function __construct(string $key = 'ahorcado') {
        $this->key = $key;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (headers_sent($file, $line)) {
                throw new RuntimeException("No se puede iniciar la sesión: cabeceras ya enviadas en $file:$line");
            }
            session_start();
        }

        if (!isset($_SESSION[$this->key]) || !is_array($_SESSION[$this->key])) {
            $_SESSION[$this->key] = [];
        }
    }

    /** Lee un valor del espacio de sesión, o $default si no existe. */
    public function get(string $name, $default = null) {
        return array_key_exists($name, $_SESSION[$this->key])
            ? $_SESSION[$this->key][$name]
            : $default;
    }

    /** Escribe un valor en el espacio de sesión. */
    public function set(string $name, $value): void {
        $_SESSION[$this->key][$name] = $value;
    }

    /** ¿Existe la clave en el espacio de sesión? */
    public function has(string $name): bool {
        return array_key_exists($name, $_SESSION[$this->key]);
    }

    /** Elimina una clave concreta del espacio de sesión. */
    public function remove(string $name): void {
        unset($_SESSION[$this->key][$name]);
    }

    /** Vacía por completo el espacio de sesión (útil para "Nueva partida"). */
    public function reset(): void {
        $_SESSION[$this->key] = [];
    }

    /** Devuelve todo el espacio (útil para depuración). */
    public function all(): array {
        return $_SESSION[$this->key];
    }
}
