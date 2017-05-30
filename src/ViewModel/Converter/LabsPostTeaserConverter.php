<?php

namespace eLife\Journal\ViewModel\Converter;

use Cocur\Slugify\SlugifyInterface;
use eLife\ApiSdk\Model\LabsPost;
use eLife\Patterns\ViewModel;
use eLife\Patterns\ViewModel\Link;
use eLife\Patterns\ViewModel\Teaser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LabsPostTeaserConverter implements ViewModelConverter
{
    use CreatesDate;
    use CreatesTeaserImage;

    private $urlGenerator;
    private $slugify;

    public function __construct(UrlGeneratorInterface $urlGenerator, SlugifyInterface $slugify)
    {
        $this->urlGenerator = $urlGenerator;
        $this->slugify = $slugify;
    }

    /**
     * @param LabsPost $object
     */
    public function convert($object, string $viewModel = null, array $context = []) : ViewModel
    {
        return Teaser::main(
            $object->getTitle(),
            $this->urlGenerator->generate('labs-post', ['id' => $object->getId(), 'slug' => $this->slugify->slugify($object->getTitle())]),
            $object->getImpactStatement(),
            null,
            null,
            $this->bigTeaserImage($object),
            ViewModel\TeaserFooter::forNonArticle(
                ViewModel\Meta::withLink(
                    new Link('Labs', $this->urlGenerator->generate('labs')),
                    $this->simpleDate($object, ['date' => 'published'] + $context)
                )
            )
        );
    }

    public function supports($object, string $viewModel = null, array $context = []) : bool
    {
        return $object instanceof LabsPost && ViewModel\Teaser::class === $viewModel && empty($context['variant']);
    }
}
