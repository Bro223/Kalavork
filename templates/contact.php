<div class="contact-section">
    <h2><?= $t('contact_title') ?></h2>
    <p class="intro"><?= $t('contact_text') ?></p>

    <?php if ($success ?? false): ?>
    <div class="alert alert-success"><?= $t('contact_success') ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['contact_error'])): ?>
    <div class="alert alert-danger"><?= Template::e($_SESSION['contact_error']) ?></div>
    <?php unset($_SESSION['contact_error']); endif; ?>

    <form method="POST" action="<?= Template::e(Router::url('/contact', $lang)) ?>">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label for="name"><?= $t('form_name') ?></label>
            <input type="text" id="name" name="name" required
                   placeholder="<?= $t('form_name_placeholder') ?>"
                   value="<?= Template::e($_POST['name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="email"><?= $t('form_email') ?></label>
            <input type="email" id="email" name="email" required
                   placeholder="<?= $t('form_email_placeholder') ?>"
                   value="<?= Template::e($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="message"><?= $t('form_message') ?></label>
            <textarea id="message" name="message" required
                      placeholder="<?= $t('form_message_placeholder') ?>"><?= Template::e($_POST['message'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <div class="g-recaptcha" data-sitekey="<?= Template::e($recaptcha_site_key ?? '') ?>"></div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">
            <?= $t('form_submit') ?>
        </button>
    </form>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</div>
