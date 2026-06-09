<article class="blog-detail">
    <h1><?= Template::e($post_title ?? '') ?></h1>

    <div class="meta" style="margin-bottom:1.5rem">
        <?= Template::date($post['published_at'] ?? '') ?>
        <?php if (!empty($post['category'])): ?> &middot; <?= Template::e($post['category']) ?><?php endif; ?>
    </div>

    <?php if (!empty($post['image'])): ?>
    <img src="/assets/img/<?= Template::e($post['image']) ?>" alt="" style="width:100%;max-height:400px;object-fit:cover;border-radius:8px;margin-bottom:1.5rem">
    <?php endif; ?>

    <div class="content" style="line-height:1.8;font-size:1.05rem">
        <?= nl2br(Template::e($post_content ?? '')) ?>
    </div>

    <?php if (!empty($post['tags'])): ?>
    <div class="tags" style="margin-top:2rem">
        <?php foreach ($post['tags'] as $tag): ?>
        <span style="display:inline-block;background:var(--primary);color:#fff;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.85rem;margin:0.25rem"><?= Template::e(trim($tag)) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</article>
