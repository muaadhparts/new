<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;

class SearchBox extends Component
{
    public string $query = '';
    public array $results = [];
    public bool $notFound = false;
    public string $userMessage = '';
    public int $notFoundDelayMs = 1000;

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
        $this->userMessage = '';
        $this->notFound = false;

        $results = Product::where('sku', 'like', "{$sku}%")
            ->select('id', 'sku', 'name', 'label_en', 'label_ar')
            ->limit(50)
            ->get();

        if ($results->isEmpty()) {
            // fallback للبحث بالاسم (contains + AND)
            $results = $this->searchByPartNameQuery($query);
        }

        if ($results->isEmpty()) {
            $this->notFound = true;
            $this->userMessage = __('No matching parts found. If you are unsure of the exact part number or name, please switch to the VIN tab and search using your vehicle\'s VIN.');
        }

        // dd($results->toArray()); // debug
        return $results->toArray();
    }

    private function searchByPartNameQuery(string $query)
    {
        $normalized = $this->normalizeArabic($query);
        $words = preg_split('/\s+/', trim($normalized));

        // بحث بكل الكلمات (AND)
        $results = Product::query()
            ->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $word = trim($word);
                    if ($word === '') continue;

                    $q->where(function ($sub) use ($word) {
                        $sub->where('label_ar', 'like', "%{$word}%")
                           ->orWhere('label_en', 'like', "%{$word}%")
                           ->orWhere('name', 'like', "%{$word}%");
                    });
                }
            })
            ->select('id', 'sku', 'name', 'label_en', 'label_ar')
            ->limit(50)
            ->get();

        // fallback (OR) إذا لم يُعثر على شيء ومعنا أكثر من كلمة
        if ($results->isEmpty() && count(array_filter($words)) > 1) {
            $results = Product::query()
                ->where(function ($q) use ($words) {
                    foreach ($words as $word) {
                        $word = trim($word);
                        if ($word === '') continue;

                        $q->orWhere('label_ar', 'like', "%{$word}%")
                          ->orWhere('label_en', 'like', "%{$word}%")
                          ->orWhere('name', 'like', "%{$word}%");
                    }
                })
                ->select('id', 'sku', 'name', 'label_en', 'label_ar')
                ->limit(50)
                ->get();
        }

        return $results;
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
        // dd('render: SearchBox (part)'); // debug
        return view('livewire.search-box');
    }
}
