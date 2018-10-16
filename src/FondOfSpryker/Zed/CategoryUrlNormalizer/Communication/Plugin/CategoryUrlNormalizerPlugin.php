<?php

namespace FondOfSpryker\Zed\CategoryUrlNormalizer\Communication\Plugin;

use Generated\Shared\Transfer\LocaleTransfer;
use Spryker\Zed\Category\Business\Generator\UrlPathGenerator;
use Spryker\Zed\Category\Dependency\Plugin\CategoryUrlPathPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

class CategoryUrlNormalizerPlugin extends AbstractPlugin implements CategoryUrlPathPluginInterface
{
    protected const DEFAULT_TRANSLITERATION_RULE = 'Any-Latin; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove;';
    protected const DEFAULT_UNICODE_REPLACE =
        [
            'Ä' => 'Ae',
            'ä' => 'ae',
            'Ü' => 'Ue',
            'ü' => 'ue',
            'Ö' => 'Oe',
            'ö' => 'oe',
            '´' => ' ',
            "'" => ' ',
            '"'=> ' ',
        ];

    /**
     * @var string|null
     */
    protected $transliterationRule;

    /**
     * @var string[]|null
     */
    protected $unicodeReplacements;

    /**
     * @param null|array $unicodeReplacements
     * @param null|string $transliterationRule
     */
    public function __construct(?array $unicodeReplacements = null, ?string $transliterationRule = null)
    {
        $this->unicodeReplacements = $unicodeReplacements;
        $this->transliterationRule = $transliterationRule;
    }

    /**
     * @inheritdoc
     */
    public function update(array $paths, LocaleTransfer $localeTransfer)
    {
        foreach ($paths as $index => $category) {
            if (! $this->hasCategoryName($category)) {
                continue;
            }

            $categoryName = $this->getCategoryName($category);
            if (! is_string($categoryName)) {
                continue;
            }

            $categoryName = $this->normalize($categoryName);
            $paths[$index] = $this->replaceCategoryName($category, $categoryName);
        }

        return $paths;
    }

    /**
     * @param string $categoryName
     *
     * @return string
     */
    protected function normalize(string $categoryName) : string
    {
        $categoryName = $this->replaceUnicodes($categoryName);
        $categoryName = $this->transliterate($categoryName);

        return $categoryName;
    }

    /**
     * @param array $category
     * @param $categoryName
     *
     * @return array
     */
    protected function replaceCategoryName(array $category, $categoryName): array
    {
        $category[UrlPathGenerator::CATEGORY_NAME] = $categoryName;

        return $category;
    }

    /**
     * @param array $category
     *
     * @return bool
     */
    protected function hasCategoryName(array $category) : bool
    {
        return array_key_exists(UrlPathGenerator::CATEGORY_NAME, $category);
    }

    /**
     * @param array $category
     *
     * @return string
     */
    protected function getCategoryName(array $category) : string
    {
        return $category[UrlPathGenerator::CATEGORY_NAME];
    }

    /**
     * @return string[]
     */
    protected function getUnicodeReplacements() : array
    {
        return $this->unicodeReplacements ?? static::DEFAULT_UNICODE_REPLACE;
    }

    /**
     * @param string $categoryName
     *
     * @return string
     */
    protected function replaceUnicodes(string $categoryName): string
    {
        return strtr($categoryName, $this->getUnicodeReplacements());
    }

    /**
     * @return string
     */
    protected function getTransliterationRule() : string
    {
        return $this->transliterationRule ?? static::DEFAULT_TRANSLITERATION_RULE;
    }

    /**
     * @param string $categoryName
     *
     * @return string
     */
    protected function transliterate(string $categoryName): string
    {
        return \Transliterator::create($this->getTransliterationRule())->transliterate($categoryName);
    }
}
