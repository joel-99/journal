<?php

namespace eLife\Journal\Controller;

use DateTimeImmutable;
use eLife\ApiClient\ApiClient\ArticlesClient;
use eLife\ApiClient\ApiClient\SubjectsClient;
use eLife\ApiClient\Exception\BadResponse;
use eLife\ApiClient\MediaType;
use eLife\ApiClient\Result;
use eLife\Patterns\ViewModel\Author;
use eLife\Patterns\ViewModel\AuthorList;
use eLife\Patterns\ViewModel\BackgroundImage;
use eLife\Patterns\ViewModel\ContentHeaderArticle;
use eLife\Patterns\ViewModel\Date;
use eLife\Patterns\ViewModel\Institution;
use eLife\Patterns\ViewModel\InstitutionList;
use eLife\Patterns\ViewModel\Link;
use eLife\Patterns\ViewModel\Meta;
use eLife\Patterns\ViewModel\SubjectList;
use GuzzleHttp\Promise\FulfilledPromise;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use function GuzzleHttp\Promise\all;

final class ArticlesController extends Controller
{
    public function latestVersionAction(int $volume, string $id) : Response
    {
        $arguments = $this->defaultPageArguments();

        $arguments['article'] = $this->get('elife.api_client.articles')
            ->getArticleLatestVersion([
                'Accept' => [
                    new MediaType(ArticlesClient::TYPE_ARTICLE_POA, 1),
                    new MediaType(ArticlesClient::TYPE_ARTICLE_VOR, 1),
                ],
            ], $id)
            ->otherwise(function (Throwable $e) {
                if ($e instanceof BadResponse && 404 === $e->getResponse()->getStatusCode()) {
                    throw new NotFoundHttpException('Article not found', $e);
                }
            })
            ->then(function (Result $result) use ($volume) {
                if ($volume !== $result['volume']) {
                    throw new NotFoundHttpException('Incorrect volume');
                }

                return $result;
            });

        $subjects = $arguments['article']
            ->then(function (Result $result) {
                if (empty($result['subjects'])) {
                    return new FulfilledPromise([]);
                }

                $return = [];
                foreach ($result['subjects'] as $id) {
                    $return[] = $this->get('elife.api_client.subjects')
                        ->getSubject(['Accept' => new MediaType(SubjectsClient::TYPE_SUBJECT, 1)],
                            $id);
                }

                return all($return);
            });

        $arguments['contentHeader'] = all(['article' => $arguments['article'], 'subjects' => $subjects])
            ->then(function (array $results) {
                $article = $results['article'];

                $subjects = array_map(function (Result $subject) {
                    return new Link($subject['name'],
                        $this->get('router')->generate('subject', ['id' => $subject['id']]));
                }, $results['subjects']);

                $onBehalfOf = null;

                $authors = array_merge(...array_map(function (array $author) use (&$onBehalfOf) {
                    $authors = [];

                    $authorOnBehalfOf = null;
                    if (!empty($author['onBehalfOf'])) {
                        $authorOnBehalfOf = end($author['onBehalfOf']['name']);
                    }

                    if (null !== $onBehalfOf && $onBehalfOf !== $authorOnBehalfOf) {
                        $authors[] = Author::asText('on behalf of '.$onBehalfOf);
                    }

                    $onBehalfOf = $authorOnBehalfOf;

                    switch ($type = $author['type'] ?? 'unknown') {
                        case 'person':
                            $authors[] = Author::asText($author['name']['preferred']);
                            break;
                        case 'group':
                            $authors[] = Author::asText($author['name']);
                            break;
                        default:
                            throw new \RuntimeException('Unknown type '.$type);
                    }

                    return $authors;
                }, $results['article']['authors']));

                if (null !== $onBehalfOf) {
                    $authors[] = Author::asText('on behalf of '.$onBehalfOf);
                }

                $institutions = array_map(function (string $name) {
                    return new Institution($name);
                }, array_values(array_unique(array_merge(...array_map(function (array $author) {
                    $institutions = [];
                    foreach ($author['affiliations'] ?? [] as $affiliation) {
                        $name = end($affiliation['name']);
                        if (!empty($affiliation['address']['components']['country'])) {
                            $name .= ', '.$affiliation['address']['components']['country'];
                        }
                        $institutions[] = $name;
                    }

                    return $institutions;
                }, $results['article']['authors'])))));

                $authors = AuthorList::asList($authors);
                $institutions = !empty($institutions) ? new InstitutionList($institutions) : null;

                switch ($article['type']) {
                    case 'research-advance':
                    case 'research-article':
                    case 'research-exchange':
                    case 'replication-study':
                    case 'short-report':
                    case 'tools-resources':
                        return ContentHeaderArticle::research(
                            $article['title'],
                            $authors,
                            Meta::withText(
                                ucfirst(str_replace('-', ' ', $article['type'])),
                                new Date(DateTimeImmutable::createFromFormat(DATE_ATOM, $article['published']))
                            ),
                            new SubjectList(...$subjects),
                            $institutions
                        );
                }

                return ContentHeaderArticle::magazine(
                    $article['title'],
                    $article['impactStatement'],
                    $authors,
                    null,
                    new SubjectList(...$subjects),
                    Meta::withText(
                        ucfirst(str_replace('-', ' ', $article['type'])),
                        new Date(DateTimeImmutable::createFromFormat(DATE_ATOM, $article['published']))
                    ),
                    $institutions,
                    false,
                    $article['image'] ? new BackgroundImage(
                        $article['image']['sizes']['2:1'][900],
                        $article['image']['sizes']['2:1'][1800]
                    ) : null
                );
            });

        return new Response($this->get('templating')->render('::article.html.twig', $arguments));
    }
}