<?php foreach (\App\Core\Session::messages() as $message): ?>
    <div class="flash flash-<?= e($message['type']) ?>" role="<?= $message['type'] === 'error' ? 'alert' : 'status' ?>">
        <?= e($message['text']) ?>
    </div>
<?php endforeach ?>
