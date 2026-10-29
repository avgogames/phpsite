<div class="empty-state">
    <h1>Something went wrong</h1>
    <p>Sorry, we hit an unexpected error. It has been logged and we will look into it.</p>
    <a class="btn" href="/">Go home</a>
    <?php if (!empty($detail)): ?>
        <pre class="debug"><?= e($detail) ?></pre>
    <?php endif ?>
</div>
