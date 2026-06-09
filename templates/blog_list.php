<h2><?= $t('nav_blog') ?></h2>

<?php if (empty($posts)): ?>
<p style="text-align:center;padding:3rem;color:var(--text-muted)"><?= $t('blog_empty') ?></p>
<?php else: ?>
<div class="blog-grid">
    <?php foreach ($posts as $p):
        $pTitle = $p['title'][$lang] ?? ($p['title']['et'] ?? '');
        $pExcerpt = $p['meta_description'][$lang] ?? ($p['meta_description']['et'] ?? '');
    ?>
    <article class="blog-card">
        <?php if (!empty($p['image'])): ?>
        <img class="blog-card-img" src="/assets/img/<?= Template::e($p['image']) ?>" alt="" loading="lazy">
        <?php endif; ?>
        <div class="blog-card-body">
            <h3><a href="<?= Template::e(Router::url('/blog/' . ($p['slug'] ?? $p['id']), $lang)) ?>"><?= Template::e($pTitle) ?></a></h3>
            <div class="meta">
                <?= Template::date($p['published_at'] ?? '') ?>
                <?php if (!empty($p['category'])): ?> &middot; <?= Template::e($p['category']) ?><?php endif; ?>
            </div>
            <p class="excerpt"><?= Template::e(mb_substr($pExcerpt, 0, 150)) ?></p>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
