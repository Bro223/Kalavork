<?php
/**
 * Rate limiter for forms (contact, order, login).
 * Filesystem-backed, no Redis/DB needed.
 */

class RateLimitExceededException extends \RuntimeException
{
    public function __construct(int $retryAfterSeconds = 60)
    {
        parent::__construct('Too many requests. Please try again later.', 429);
        $this->retryAfter = $retryAfterSeconds;
    }

    public int $retryAfter;
}

class RateLimiter
{
    private string $bucket;
    private int $window;
    private int $maxAttempts;
    private string $identifier;

    /**
     * @param string $bucket   Unique name for the action (e.g. 'contact', 'login')
     * @param int    $window   Time window in seconds (default 60)
     * @param int    $max      Max attempts in that window (default 5)
     */
    public function __construct(string $bucket, ?int $window = null, ?int $max = null)
    {
        $this->bucket     = $bucket;
        $this->window     = $window ?? RATE_LIMIT_WINDOW;
        $this->maxAttempts = $max    ?? RATE_LIMIT_MAX;
        $this->identifier = $this->buildIdentifier();
    }

    /**
     * Check the rate limit. Throws if exceeded.
     */
    public function checkOrFail(): void
    {
        $hits = $this->getHits();

        // Purge old entries outside the window
        $cutoff = time() - $this->window;
        $recent = array_values(array_filter($hits, fn(int $ts) => $ts > $cutoff));

        if (count($recent) >= $this->maxAttempts) {
            $retryAfter = ($recent[0] + $this->window) - time();
            throw new RateLimitExceededException(max(1, $retryAfter));
        }
    }

    /**
     * Record a hit for this identifier.
     */
    public function record(): void
    {
        $hits = $this->getHits();
        $hits[] = time();

        // Keep only recent entries to avoid file bloat
        $cutoff = time() - ($this->window * 2);
        $hits = array_values(array_filter($hits, fn(int $ts) => $ts > $cutoff));

        $this->writeHits($hits);
    }

    /**
     * How many attempts remain in the current window.
     */
    public function remaining(): int
    {
        $hits = $this->getHits();
        $cutoff = time() - $this->window;
        $recent = array_values(array_filter($hits, fn(int $ts) => $ts > $cutoff));
        return max(0, $this->maxAttempts - count($recent));
    }

    private function buildIdentifier(): string
    {
        // Combine IP + bucket so different actions don't share the same counter
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        // Hash to avoid storing raw IPs in filenames (GDPR-friendly)
        return hash('sha256', $ip . ':' . $this->bucket);
    }

    private function getHits(): array
    {
        $file = $this->filePath();
        if (!file_exists($file)) {
            return [];
        }
        $content = @file_get_contents($file);
        if ($content === false || trim($content) === '') {
            return [];
        }
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function writeHits(array $hits): void
    {
        $dir = dirname($this->filePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        @file_put_contents(
            $this->filePath(),
            json_encode($hits),
            LOCK_EX
        );
    }

    private function filePath(): string
    {
        // Store outside the web root (data/ is already above doc root)
        return DATA_PATH . '/.ratelimit/' . $this->identifier . '.json';
    }
}
