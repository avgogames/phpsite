<?php use App\Core\View; ?>
<div class="auth-card card">
    <h1>Create an account</h1>
    <form action="/register" method="post" class="form" novalidate>
        <?= csrf_field() ?>
        <?= View::partial('field', ['name' => 'name', 'label' => 'Full name', 'value' => old('name'), 'autocomplete' => 'name', 'extra' => 'autofocus']) ?>
        <?= View::partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => old('email'), 'autocomplete' => 'email']) ?>
        <?= View::partial('field', ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'At least 10 characters.', 'extra' => 'minlength="10"']) ?>
        <?= View::partial('field', ['name' => 'password_confirmation', 'label' => 'Confirm password', 'type' => 'password', 'autocomplete' => 'new-password']) ?>
        <button type="submit" class="btn btn-block">Create account</button>
    </form>
    <p class="muted">Already have an account? <a href="/login">Sign in</a></p>
</div>
