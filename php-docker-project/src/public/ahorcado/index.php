<?php
declare(strict_types=1);

require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Game.php';
require_once __DIR__ . '/WordProvider.php';
require_once __DIR__ . '/Renderer.php';

$WORDS_FILE   = __DIR__ . '/palabras.txt';
$MAX_ATTEMPTS = 6;

$storage = new Storage('ahorcado');

$action = $_POST['action'] ?? null;
if ($action === 'reset') {
  $storage->reset();
  header('Location: ' . ($_SERVER['PHP_SELF'] ?? '/'));
  exit;
}

$state = $storage->get('state', null);

if ($state !== null) {
    // Restaurar desde estado previo
    $game = new Game('', $MAX_ATTEMPTS, $state);
} else {
    // Nueva partida con palabra aleatoria del .txt (o fallback)
    try {
        $provider = new WordProvider($WORDS_FILE);
        $word     = $provider->randomWord();
    } catch (Throwable $e) {
        // Si no hay fichero o está vacío, usa una palabra por defecto
        $word = 'MANGO';
    }
    $game = new Game($word, $MAX_ATTEMPTS);
    $storage->set('state', $game->toState());
}

// ---- PROCESAR JUGADA (si llega una letra) ----
if ($action === 'guess') {
    $letter = $_POST['letra'] ?? '';
    // La validación fina la hace Game; aquí solo recortamos espacios
    $letter = trim($letter);
    if ($letter !== '') {
        $game->guessLetter($letter);
        $storage->set('state', $game->toState());
    }
}

// ---- PREPARAR DATOS PARA LA VISTA ----
$renderer   = new Renderer();
$masked     = $game->getMaskedWord();
$attempts   = $game->getAttemptsLeft();
$used       = $game->getUsedLetters();
$won        = $game->isWon();
$lost       = $game->isLost();
$ascii      = $renderer->ascii($attempts);
$usedString = empty($used) ? '—' : implode(', ', $used);

// ---- HTML ----
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Ahorcado</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: monospace; padding: 1rem;">
  <h1>Juego del Ahorcado</h1>

  <?= $ascii ?>

  <p><strong>Palabra:</strong> <?= htmlspecialchars($masked, ENT_QUOTES, 'UTF-8') ?></p>
  <p><strong>Intentos restantes:</strong> <?= (int)$attempts ?></p>
  <p><strong>Letras usadas:</strong> <?= htmlspecialchars($usedString, ENT_QUOTES, 'UTF-8') ?></p>

  <?php if ($won): ?>
    <h2>🎉 ¡Has ganado!</h2>
    <p>La palabra era: <strong><?= htmlspecialchars($game->getWord(), ENT_QUOTES, 'UTF-8') ?></strong></p>
  <?php elseif ($lost): ?>
    <h2>💀 Has perdido</h2>
    <p>La palabra era: <strong><?= htmlspecialchars($game->getWord(), ENT_QUOTES, 'UTF-8') ?></strong></p>
  <?php else: ?>
    <form method="post" style="margin-top:1rem;">
      <input type="hidden" name="action" value="guess">
      <label>
        Letra:
        <input
          type="text"
          name="letra"
          maxlength="1"
          required
          pattern="[A-Za-zÑñÁÉÍÓÚÜáéíóúü]"
          title="Introduce una letra (A-Z o Ñ)"
          autofocus
        >
      </label>
      <button type="submit">Probar</button>
    </form>
  <?php endif; ?>

  <form method="post" style="margin-top:1rem;">
    <input type="hidden" name="action" value="reset">
    <button type="submit">Nueva partida</button>
  </form>
</body>
</html>
