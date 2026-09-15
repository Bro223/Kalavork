<div class="admin-header">
    <h1><?= $contact ? 'Edit Contact' : 'Add Contact' ?></h1>
    <a href="/manage/crm" class="btn btn-outline">← All Contacts</a>
</div>

<form method="POST" action="/manage/crm/save" class="card">
    <?= CSRF::field() ?>
    <?php if ($contact): ?>
    <input type="hidden" name="contact_id" value="<?= Template::e($contact['id']) ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required value="<?= Template::e($contact['name'] ?? $_POST['name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="contact" <?= ($contact['status'] ?? 'contact') === 'contact' ? 'selected' : '' ?>>Contact (not bought yet)</option>
                <option value="active_buyer" <?= ($contact['status'] ?? '') === 'active_buyer' ? 'selected' : '' ?>>Active Buyer</option>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?= Template::e($contact['email'] ?? $_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="phone">Phone *</label>
            <input type="tel" id="phone" name="phone" required value="<?= Template::e($contact['phone'] ?? $_POST['phone'] ?? '') ?>">
        </div>
    </div>

    <h4 style="margin-top:1rem">Address</h4>
    <div class="form-row">
        <div class="form-group">
            <label for="street">Street</label>
            <input type="text" id="street" name="street" value="<?= Template::e($contact['address']['street'] ?? $_POST['street'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" value="<?= Template::e($contact['address']['city'] ?? $_POST['city'] ?? '') ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="postal">Postal Code</label>
            <input type="text" id="postal" name="postal" value="<?= Template::e($contact['address']['postal'] ?? $_POST['postal'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="country">Country</label>
            <input type="text" id="country" name="country" value="<?= Template::e($contact['address']['country'] ?? $_POST['country'] ?? 'Estonia') ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="notes">Admin Notes</label>
        <textarea id="notes" name="notes" rows="4" placeholder="Private notes from calls, meetings, etc."><?= Template::e($contact['notes'] ?? $_POST['notes'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><?= $contact ? 'Update Contact' : 'Create Contact' ?></button>
</form>

<?php if ($contact): ?>
<form method="POST" action="/manage/crm/delete" style="margin-top:1rem" onsubmit="return confirm('Delete this contact? This cannot be undone.')">
    <?= CSRF::field() ?>
    <input type="hidden" name="contact_id" value="<?= Template::e($contact['id']) ?>">
    <button type="submit" class="btn btn-danger">Delete Contact</button>
</form>
<?php endif; ?>
