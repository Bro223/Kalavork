<?php
$availableLanguages = $languages['available'] ?? AVAILABLE_LANGUAGES;
$heroEnabled = !empty($home_settings['hero_background_enabled']);
$heroImage = $home_settings['hero_background_image'] ?? 'backgroung.webp';
?>

<div class="admin-header">
    <h1>🧩 Content</h1>
    <a href="/" target="_blank" class="btn btn-outline">View Homepage</a>
</div>

<form method="post" action="/manage/content/save" enctype="multipart/form-data" class="admin-form admin-form-wide">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

    <div class="admin-card">
        <h2>Homepage Background</h2>
        <div class="content-editor-grid">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="hero_background_enabled" value="1" <?= $heroEnabled ? 'checked' : '' ?>>
                    Show background image in hero
                </label>
            </div>
            <div class="form-group">
                <label>Current / Existing Image</label>
                <select name="hero_background_image">
                    <option value="">No image</option>
                    <?php foreach ($asset_images as $image): ?>
                    <option value="<?= Template::e($image) ?>" <?= $heroImage === $image ? 'selected' : '' ?>>
                        <?= Template::e($image) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Upload Replacement</label>
                <input type="file" name="hero_background_upload" accept="image/*">
            </div>
        </div>

        <?php if ($heroImage): ?>
        <div class="image-preview-row">
            <img src="/assets/img/<?= Template::e($heroImage) ?>" alt="">
            <div>
                <strong><?= Template::e($heroImage) ?></strong>
                <label><input type="checkbox" name="remove_hero_background" value="1"> Remove background from homepage</label>
                <label><input type="checkbox" name="delete_removed_hero_background" value="1"> Delete image file if removed and unused</label>
                <label><input type="checkbox" name="delete_old_hero_background" value="1"> Delete old image file when replacement upload succeeds</label>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="admin-card">
        <h2>Why Choose Us</h2>
        <?php foreach ($availableLanguages as $language): ?>
        <section class="content-language-block">
            <h3><?= strtoupper($language) ?></h3>
            <div id="why-<?= Template::e($language) ?>" class="content-repeat-list">
                <?php $items = $why_us_list[$language] ?? ['']; ?>
                <?php if (empty($items)) $items = ['']; ?>
                <?php foreach ($items as $item): ?>
                <div class="content-repeat-row">
                    <textarea name="why_us_list[<?= Template::e($language) ?>][]" rows="2"><?= Template::e($item) ?></textarea>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeContentRow(this)">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline" onclick="addWhyRow('<?= Template::e($language) ?>')">+ Add Why Item</button>
        </section>
        <?php endforeach; ?>
    </div>

    <div class="admin-card">
        <h2>FAQ</h2>
        <?php foreach ($availableLanguages as $language): ?>
        <section class="content-language-block">
            <h3><?= strtoupper($language) ?></h3>
            <div id="faq-<?= Template::e($language) ?>" class="content-repeat-list">
                <?php $items = $faq_items[$language] ?? [['question' => '', 'answer' => '']]; ?>
                <?php if (empty($items)) $items = [['question' => '', 'answer' => '']]; ?>
                <?php foreach ($items as $item): ?>
                <div class="content-repeat-row faq-repeat-row">
                    <input type="text" name="faq_question[<?= Template::e($language) ?>][]" value="<?= Template::e($item['question'] ?? '') ?>" placeholder="Question">
                    <textarea name="faq_answer[<?= Template::e($language) ?>][]" rows="3" placeholder="Answer"><?= Template::e($item['answer'] ?? '') ?></textarea>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeContentRow(this)">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline" onclick="addFaqRow('<?= Template::e($language) ?>')">+ Add Q&A</button>
        </section>
        <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary">Save Content</button>
</form>

<style>
/* Custom Dropdown Styling */
select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.25rem;
    padding-right: 2.5rem;
    padding-left: 0.75rem;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    font-family: inherit;
    background-color: white;
    color: #1f2937;
    cursor: pointer;
    transition: all 0.2s ease;
}

select:hover {
    border-color: #9ca3af;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

select option {
    padding: 0.5rem;
    background-color: white;
    color: #1f2937;
}

select option:checked {
    background: linear-gradient(#3b82f6, #3b82f6);
    background-color: #3b82f6;
    color: white;
}

select:disabled {
    background-color: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
}
</style>

<script>
function removeContentRow(button) {
    button.closest('.content-repeat-row').remove();
}

function addWhyRow(language) {
    const container = document.getElementById('why-' + language);
    const row = document.createElement('div');
    row.className = 'content-repeat-row';
    row.innerHTML = '<textarea name="why_us_list[' + language + '][]" rows="2"></textarea>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="removeContentRow(this)">Remove</button>';
    container.appendChild(row);
}

function addFaqRow(language) {
    const container = document.getElementById('faq-' + language);
    const row = document.createElement('div');
    row.className = 'content-repeat-row faq-repeat-row';
    row.innerHTML = '<input type="text" name="faq_question[' + language + '][]" placeholder="Question">' +
        '<textarea name="faq_answer[' + language + '][]" rows="3" placeholder="Answer"></textarea>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="removeContentRow(this)">Remove</button>';
    container.appendChild(row);
}
</script>
