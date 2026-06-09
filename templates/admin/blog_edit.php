<div class="admin-header">
    <h1><?= $post ? 'Edit Post' : 'New Post' ?></h1>
    <a href="/manage/blog" class="btn btn-outline">← Back to Blog</a>
</div>

<div class="admin-card">
    <form method="post" action="/manage/blog/save" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="post_id" value="<?= Template::e($post_id ?? '') ?>">

        <!-- Multilingual Title -->
        <?php foreach ($languages['available'] as $l): ?>
        <div class="form-group">
            <label>Title (<?= strtoupper($l) ?>)</label>
            <input type="text" name="title_<?= $l ?>" value="<?= Template::e($post['title'][$l] ?? '') ?>">
        </div>
        <?php endforeach; ?>

        <!-- Slug -->
        <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" value="<?= Template::e($post['slug'] ?? '') ?>" placeholder="Auto-generated from title if blank">
        </div>

        <!-- Multilingual Meta Description -->
        <?php foreach ($languages['available'] as $l): ?>
        <div class="form-group">
            <label>Meta Description (<?= strtoupper($l) ?>)</label>
            <input type="text" name="meta_description_<?= $l ?>" value="<?= Template::e($post['meta_description'][$l] ?? '') ?>">
        </div>
        <?php endforeach; ?>

        <!-- Multilingual Content -->
        <?php foreach ($languages['available'] as $l): ?>
        <div class="form-group">
            <label>Content (<?= strtoupper($l) ?>)</label>
            <textarea name="content_<?= $l ?>" rows="8"><?= Template::e($post['content'][$l] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>

        <!-- Metadata -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem">
            <div class="form-group">
                <label>Image Filename</label>
                <input type="text" name="image" value="<?= Template::e($post['image'] ?? '') ?>" placeholder="e.g. blog-image.webp">
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" value="<?= Template::e($post['category'] ?? '') ?>">
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem">
            <div class="form-group">
                <label>Tags (comma-separated)</label>
                <input type="text" name="tags" value="<?= Template::e(implode(', ', $post['tags'] ?? [])) ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:1rem">💾 Save Post</button>
    </form>
</div>

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
