<div class="admin-header">
    <h1>📝 Blog Posts</h1>
    <a href="/manage/blog/new" class="btn btn-primary">+ New Post</a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title (ET)</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Category</th>
                <th>Published</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($posts)): ?>
            <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted)">No posts yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td><strong><?= Template::e($p['title']['et'] ?? $p['id']) ?></strong></td>
                <td><?= Template::e($p['slug'] ?? $p['id']) ?></td>
                <td>
                    <span class="badge <?= ($p['status'] ?? 'draft') === 'published' ? 'badge-success' : 'badge-processing' ?>">
                        <?= $p['status'] ?? 'draft' ?>
                    </span>
                </td>
                <td><?= Template::e($p['category'] ?? '') ?></td>
                <td><?= !empty($p['published_at']) ? Template::date($p['published_at'], 'd.m.Y H:i') : '—' ?></td>
                <td>
                    <a href="/manage/blog/<?= $p['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <form method="post" action="/manage/blog/delete" class="inline-form" onsubmit="return confirm('Delete this post?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
