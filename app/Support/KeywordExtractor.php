<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Extracts analysable keywords from Vietnamese blog content.
 *
 * Keywords are derived from the post title, summary, category and content — no
 * extra input is required from authors. Text is lowercased, diacritics are
 * folded, stop words are dropped and the remaining tokens are counted.
 *
 * The stop word list only contains function words: content words such as
 * "hoc", "lap", "trinh", "huong", "nghe", "xu" are kept because they are what
 * describes a topic to a Vietnamese reader.
 */
class KeywordExtractor
{
    /**
     * Weight applied to each source when ranking a post's keywords.
     */
    private const TITLE_WEIGHT = 3;

    private const SUMMARY_WEIGHT = 2;

    private const CONTENT_WEIGHT = 1;

    /**
     * Maximum number of keywords kept for a single post.
     */
    public const MAX_KEYWORDS_PER_POST = 20;

    /**
     * Vietnamese + generic English stop words (already diacritic-folded and lowercased).
     *
     * @var string[]
     */
    private const STOP_WORDS = [
        'a', 'ai', 'alo', 'anh', 'ao', 'ay', 'ba', 'bac', 'ban', 'bang', 'bao', 'bay', 'ben', 'bi', 'bo',
        'boi', 'bon', 'buoc', 'ca', 'cac', 'cai', 'can', 'cang', 'cap', 'cau', 'chang', 'chi', 'chia',
        'chiec', 'chinh', 'cho', 'chu', 'chua', 'chuc', 'chut', 'chung', 'co', 'con', 'cu', 'cua', 'cung',
        'cuoi', 'da', 'dai', 'dan', 'dang', 'danh', 'dau', 'day', 'de', 'dem', 'den', 'dieu', 'do', 'doi',
        'don', 'dong', 'du', 'dua', 'dung', 'duoc', 'duoi', 'em', 'ghe', 'gi', 'gian', 'gio', 'giua',
        'goi', 'gom', 'ha', 'hai', 'han', 'hang', 'hau', 'hay', 'het', 'ho', 'hoi', 'hon', 'hop', 'ke',
        'kem', 'khi', 'khien', 'khong', 'kia', 'kinh', 'ky', 'la', 'lac', 'lai', 'lam', 'lan', 'lao',
        'lay', 'len', 'lien', 'lieu', 'lo', 'loc', 'loi', 'lon', 'long', 'lop', 'lot', 'luon', 'luong',
        'luu', 'ma', 'mai', 'man', 'mac', 'may', 'men', 'met', 'minh', 'mo', 'moi', 'mon', 'mong', 'mot',
        'muon', 'muoi', 'na', 'nai', 'nam', 'nan', 'nao', 'nap', 'nay', 'nem', 'nen', 'neu', 'nga', 'ngan',
        'ngay', 'ngoai', 'ngoi', 'nguoi', 'nguyen', 'nha', 'nhan', 'nhap', 'nhat', 'nho', 'nhu', 'nhung',
        'nhuong', 'noi', 'non', 'nua', 'nui', 'nuoc', 'o', 'ong', 'qua', 'quanh', 'quang', 'quay', 'quen',
        'quyet', 'quyen', 'quy', 'ra', 'rang', 'rat', 'ren', 'rieng', 'ro', 'roi', 'rong', 'rua', 'rui',
        'rut', 'sa', 'sai', 'sang', 'sanh', 'sao', 'sap', 'sau', 'se', 'sen', 'si', 'sinh', 'so', 'song',
        'su', 'sua', 'suc', 'sung', 'suot', 'ta', 'tai', 'tam', 'tan', 'tang', 'tao', 'tat', 'tau', 'tay',
        'te', 'tem', 'ten', 'teo', 'tha', 'thach', 'thai', 'tham', 'than', 'thang', 'thanh', 'thao', 'thap',
        'that', 'thay', 'the', 'them', 'then', 'theo', 'thep', 'thi', 'thich', 'thien', 'thiet', 'thinh',
        'tho', 'thoi', 'thom', 'thon', 'thong', 'thu', 'thua', 'thuan', 'thuc', 'thue', 'thuong', 'thy',
        'ti', 'tia', 'tin', 'tinh', 'to', 'toa', 'toan', 'toc', 'toi', 'tom', 'ton', 'tong', 'tra', 'trai',
        'trang', 'tran', 'trao', 'tre', 'tren', 'trieu', 'trinh', 'tro', 'trong', 'truyen', 'truoc', 'trung',
        'truong', 'tu', 'tua', 'tuc', 'tui', 'tum', 'tung', 'tuoi', 'tuong', 'tuyen', 'tuy', 'ty', 'u',
        'ua', 'uc', 'ung', 'uoc', 'uong', 'uy', 'uyen', 'va', 'vao', 'van', 'vang', 'vat', 'vay', 've',
        'ven', 'vet', 'vi', 'via', 'viec', 'vien', 'vinh', 'vo', 'voc', 'voi', 'von', 'vong', 'vu', 'vua',
        'vuc', 'vui', 'vun', 'vuong', 'xa', 'xac', 'xam', 'xanh', 'xao', 'xay', 'xen', 'xeo', 'xet', 'xi',
        'xia', 'xin', 'xinh', 'xo', 'xoa', 'xoai', 'xoay', 'xoc', 'xoi', 'xon', 'xong', 'xop', 'xot',
        'xu', 'xua', 'xuc', 'xung', 'xuoi', 'xuyen', 'y', 'ye', 'yen', 'yeu', 'about', 'above', 'after',
        'again', 'all', 'also', 'and', 'any', 'are', 'around', 'back', 'be', 'because', 'been', 'before',
        'being', 'below', 'between', 'both', 'but', 'come', 'could', 'did', 'does', 'down', 'each', 'every',
        'find', 'first', 'for', 'from', 'get', 'give', 'go', 'had', 'has', 'have', 'here', 'how', 'into',
        'is', 'it', 'its', 'just', 'know', 'like', 'look', 'made', 'make', 'more', 'most', 'must', 'my',
        'need', 'never', 'new', 'news', 'next', 'not', 'now', 'of', 'off', 'on', 'once', 'one', 'only',
        'or', 'other', 'others', 'our', 'out', 'over', 'own', 'put', 'same', 'see', 'should', 'show',
        'some', 'such', 'take', 'their', 'there', 'these', 'they', 'thing', 'things', 'this', 'those',
        'through', 'too', 'two', 'under', 'up', 'use', 'used', 'using', 'very', 'was', 'way', 'we', 'well',
        'were', 'what', 'when', 'where', 'which', 'while', 'who', 'whom', 'why', 'will', 'with', 'without',
        'would', 'you', 'your',
    ];

    /**
     * Short tokens that carry real meaning (mostly tech acronyms).
     *
     * @var string[]
     */
    private const SHORT_TOKEN_ALLOWLIST = [
        'ai', 'api', 'iot', 'seo', 'css', 'js', 'ts', 'db', 'ml', 'llm', 'gpt', 'k8s',
        'dev', 'web', 'ui', 'ux', 'it', 'saas', 'crud', 'html', 'http', 'json', 'sql',
        'nosql', 'app', 'ide', 'cdn', 'rest', 'git', 'ops', 'sdk', 'aws', 'gcp',
    ];

    /**
     * Count keyword frequency inside a free-form text.
     *
     * @return array<string, int> keyword => occurrences
     */
    public function extract(?string $text): array
    {
        $counts = [];

        foreach (VietnameseText::tokens($text) as $token) {
            if (! $this->isKeyword($token)) {
                continue;
            }

            $counts[$token] = ($counts[$token] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Rank the keywords that best describe a post.
     *
     * @return string[] ordered list of keywords, most representative first
     */
    public function keywordsForPost(Post $post): array
    {
        $scores = [];

        $sources = [
            [$post->title, self::TITLE_WEIGHT],
            [$post->summary, self::SUMMARY_WEIGHT],
            [$post->category, self::SUMMARY_WEIGHT],
            [$post->content, self::CONTENT_WEIGHT],
        ];

        foreach ($sources as [$text, $weight]) {
            foreach ($this->extract($text) as $keyword => $count) {
                $scores[$keyword] = ($scores[$keyword] ?? 0) + ($count * $weight);
            }
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, self::MAX_KEYWORDS_PER_POST);
    }

    /**
     * Keywords for many posts at once, keyed by post id.
     *
     * @param  iterable<Post>  $posts
     * @return Collection<int, string[]>
     */
    public function keywordsForPosts(iterable $posts): Collection
    {
        $keywords = [];

        foreach ($posts as $post) {
            $keywords[$post->id] = $this->keywordsForPost($post);
        }

        return collect($keywords);
    }

    /**
     * Query builder limited to posts whose title/summary/category/content
     * contains the given keyword as a whole word (diacritic-insensitive).
     */
    public function postsMatchingKeyword(string $keyword, bool $publishedOnly = true): Builder
    {
        $folded = mb_strtolower(VietnameseText::toAscii(trim($keyword)), 'UTF-8');

        if ($folded === '') {
            return Post::query()->whereRaw('1 = 0');
        }

        $base = Post::query()->when($publishedOnly, fn (Builder $query) => $query->published());

        $ids = $base->get(['id', 'title', 'summary', 'category', 'content'])
            ->filter(function (Post $post) use ($folded): bool {
                $tokens = VietnameseText::tokens(
                    $post->title.' '.$post->summary.' '.$post->category.' '.strip_tags((string) $post->content)
                );

                return in_array($folded, $tokens, true);
            })
            ->pluck('id')
            ->all();

        return Post::query()->whereKey($ids);
    }

    /**
     * Does the token qualify as an analysable keyword?
     */
    private function isKeyword(string $token): bool
    {
        if (in_array($token, self::SHORT_TOKEN_ALLOWLIST, true)) {
            return true;
        }

        if (in_array($token, self::stopWords(), true)) {
            return false;
        }

        return mb_strlen($token) >= 3;
    }

    /**
     * @return string[]
     */
    private static function stopWords(): array
    {
        return self::STOP_WORDS;
    }
}
