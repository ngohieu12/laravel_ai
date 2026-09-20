<?php

namespace Tests\Unit\Support;

use App\Models\Post;
use App\Support\KeywordExtractor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class KeywordExtractorTest extends TestCase
{
    private KeywordExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new KeywordExtractor;
    }

    #[Test]
    public function test_drops_vietnamese_stop_words_and_folds_diacritics(): void
    {
        $keywords = $this->extractor->extract('Hướng dẫn học lập trình Python cho người mới bắt đầu');

        $this->assertSame(['huong' => 1, 'hoc' => 1, 'lap' => 1, 'python' => 1, 'bat' => 1], $keywords);
    }

    #[Test]
    public function test_keeps_short_technical_acronyms(): void
    {
        $keywords = $this->extractor->extract('Tích hợp AI, API và IoT vào ứng dụng Laravel');

        $this->assertArrayHasKey('ai', $keywords);
        $this->assertArrayHasKey('api', $keywords);
        $this->assertArrayHasKey('iot', $keywords);
        $this->assertArrayHasKey('laravel', $keywords);
    }

    #[Test]
    public function test_ignores_numbers_and_strips_html_tags(): void
    {
        $keywords = $this->extractor->extract('<p>Năm 2026 có 10 xu hướng <strong>cloud</strong> đáng chú ý</p>');

        $this->assertArrayNotHasKey('2026', $keywords);
        $this->assertArrayNotHasKey('10', $keywords);
        $this->assertArrayNotHasKey('p', $keywords);
        $this->assertArrayNotHasKey('strong', $keywords);
        $this->assertArrayHasKey('huong', $keywords);
        $this->assertArrayHasKey('cloud', $keywords);
    }

    #[Test]
    public function test_returns_empty_list_for_blank_input(): void
    {
        $this->assertSame([], $this->extractor->extract(null));
        $this->assertSame([], $this->extractor->extract('   '));
        $this->assertSame([], $this->extractor->extract('<p></p>'));
    }

    #[Test]
    public function test_ranks_title_keywords_above_body_keywords(): void
    {
        $post = new Post([
            'title' => 'Học Laravel nâng cao',
            'summary' => 'Tổng hợp kiến thức Laravel dành cho lập trình viên.',
            'category' => 'cong-nghe',
            'content' => '<p>Python và Docker cũng được nhắc tới trong bài, python docker python.</p>',
        ]);

        $keywords = $this->extractor->keywordsForPost($post);

        $this->assertSame('laravel', $keywords[0]);
        $this->assertContains('python', $keywords);
        $this->assertLessThanOrEqual(KeywordExtractor::MAX_KEYWORDS_PER_POST, count($keywords));
    }

    #[Test]
    public function test_indexes_keywords_per_post_id(): void
    {
        $first = (new Post(['title' => 'Học Laravel', 'summary' => '', 'category' => 'tech', 'content' => '']))->forceFill(['id' => 1]);
        $second = (new Post(['title' => 'Học Python', 'summary' => '', 'category' => 'tech', 'content' => '']))->forceFill(['id' => 2]);

        $keywords = $this->extractor->keywordsForPosts([$first, $second]);

        $this->assertSame([1, 2], $keywords->keys()->all());
        $this->assertContains('laravel', $keywords[1]);
        $this->assertContains('python', $keywords[2]);
    }
}
