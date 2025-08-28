<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;

class SearchBox extends Component
{
    public string $query = '';
    public array $results = [];

    public function updatedQuery()
    {
        $this->searchByPart();
    }

    public function submitSearch()
    {
        // dd(['component' => 'SearchBox (part)', 'query' => $this->query]); // debug
        $this->searchByPart();
    }

    public function searchByPart(): void
    {
        $query = trim($this->query);
        $this->results = $this->searchByPartQuery($query);
    }

    public function selectItem(string $value)
    {
        return redirect()->route('search.result', ['sku' => $value]);
    }

    public function searchByPartQuery(string $query): array
    {
        $sku = $this->cleanInput($query);

        // البحث بالرقم (prefix-only)
        $results = Product::where('sku', 'like', "{$sku}%")
            ->select('id', 'sku', 'label_en', 'label_ar')
            ->limit(50)
            ->get();

        // fallback → البحث بالاسم
        if ($results->isEmpty()) {
            $results = $this->searchByPartNameQuery($query);
        }

        return $results->toArray();
    }

    private function searchByPartNameQuery(string $query)
    {
        $normalized = $this->normalizeArabic($query);
        $words = array_filter(preg_split('/\s+/', trim($normalized)));

        // لو ما فيه كلمات نرجع فاضي
        if (empty($words)) {
            return collect();
        }

        // نبدأ من كل الكلمات (AND) ثم نقلص تدريجياً
        for ($i = count($words); $i > 0; $i--) {
            // ناخذ أول $i كلمات
            $subset = array_slice($words, 0, $i);

            $results = Product::query()
                ->where(function ($q) use ($subset) {
                    foreach ($subset as $word) {
                        $word = trim($word);
                        if ($word === '') continue;

                        $q->where(function ($sub) use ($word) {
                            $sub->where('label_ar', 'like', "%{$word}%")
                                ->orWhere('label_en', 'like', "%{$word}%");
                        });
                    }
                })
                ->select('id', 'sku', 'label_en', 'label_ar')
                ->limit(50)
                ->get();

            if ($results->isNotEmpty()) {
                return $results;
            }
        }

        return collect();
    }


    private function normalizeArabic(string $text): string
    {
        $replacements = [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
            'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي', 'ة' => 'ه',
            'َ' => '', 'ً' => '', 'ُ' => '', 'ٌ' => '',
            'ِ' => '', 'ٍ' => '', 'ْ' => '', 'ّ' => '', 'ٰ' => '',
        ];

        return strtr($text, $replacements);
    }

    private function cleanInput(?string $input): string
    {
        // يحذف المسافات والشرطات والنقاط والفواصل
        return strtoupper(preg_replace('/[\s\-.,]+/', '', trim((string) $input)));
    }

    public function render()
    {
        return view('livewire.search-box');
    }
}
