<?php use App\Core\View; ?>
<div class="auth-card card">
    <h1>Sign in</h1>
    <form action="/login" method="post" class="form" novalidate>
        <?= csrf_field() ?>
        <?= View::partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => old('email'), 'autocomplete' => 'email', 'extra' => 'autofocus']) ?>
        <?= View::partial('field', ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'autocomplete' => 'current-password']) ?>
        <button type="submit" class="btn btn-block">Sign in</button>
    </form>
    <p class="muted">New here? <a href="/register">Create an account</a></p>
</div>
