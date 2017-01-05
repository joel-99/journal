<?php

namespace eLife\Journal\ViewModel\Converter;

use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\ApiSdk\Model\Subject;
use eLife\Journal\Helper\ArticleType;
use eLife\Patterns\ViewModel;

final class ArticleMetaConverter implements ViewModelConverter
{
    /**
     * @param ArticleVersion $object
     */
    public function convert($object, string $viewModel = null, array $context = []) : ViewModel
    {
        $tags = [new ViewModel\Link(ArticleType::singular($object->getType()))];

        $tags = array_merge($tags, $object->getSubjects()->map(function (Subject $subject) {
            return new ViewModel\Link($subject->getName());
        })->toArray());

        if ($object instanceof ArticleVoR) {
            $tags = array_merge($tags, $object->getKeywords()->map(function (string $keyword) {
                return new ViewModel\Link($keyword);
            })->toArray());
        }

        $groups = ['Categories and tags' => $tags];

        if ($object->getResearchOrganisms()) {
            $title = 'Research organism';
            if (count($object->getResearchOrganisms()) > 1) {
                $title .= 's';
            }

            $groups[$title] = array_map(function (string $keyword) {
                return new ViewModel\Link($keyword);
            }, $object->getResearchOrganisms());
        }

        return new ViewModel\ArticleMeta($groups);
    }

    public function supports($object, string $viewModel = null, array $context = []) : bool
    {
        return $object instanceof ArticleVersion && ViewModel\ArticleMeta::class === $viewModel;
    }
}