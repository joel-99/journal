<?php

namespace test\eLife\Journal\ViewModel\Converter;

use eLife\Journal\ViewModel\Converter\BlogArticleTeaserConverter;
use eLife\Patterns\ViewModel\Teaser;

final class BlogArticleTeaserConverterTest extends ModelConverterTestCase
{
    protected $models = ['blog-article'];
    protected $viewModelClasses = [Teaser::class];

    /**
     * @before
     */
    public function setUpConverter()
    {
        $this->converter = new BlogArticleTeaserConverter($this->stubUrlGenerator());
    }
}
