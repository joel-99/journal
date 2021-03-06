<?php

namespace test\eLife\Journal\Controller;

final class ResourcesControllerTest extends PageTestCase
{
    /**
     * @test
     */
    public function it_displays_the_resources_page()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/resources');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Resources', $crawler->filter('.content-header__title')->text());

        $majorSections = $crawler->filter('h2.article-section__header_text');

        $this->assertCount(6, $majorSections);

        $this->assertSame(
            [
                'Posters and handouts',
                'Presentations',
                'Videos',
                'Images',
                'The eLife logo',
                'Brand guide',
            ],
            array_map('trim', $majorSections->extract(['_text']))
        );
    }

    /**
     * @test
     */
    public function it_has_metadata()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', $this->getUrl().'?foo');

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $this->assertSame('Resources | eLife', $crawler->filter('title')->text());
        $this->assertSame('/resources', $crawler->filter('link[rel="canonical"]')->attr('href'));
        $this->assertSame('http://localhost/resources', $crawler->filter('meta[property="og:url"]')->attr('content'));
        $this->assertSame('Resources', $crawler->filter('meta[property="og:title"]')->attr('content'));
        $this->assertSame('A collection of resources, from posters, videos, images, presentations and more, to the brand behind eLife.', $crawler->filter('meta[property="og:description"]')->attr('content'));
        $this->assertSame('A collection of resources, from posters, videos, images, presentations and more, to the brand behind eLife.', $crawler->filter('meta[name="description"]')->attr('content'));
        $this->assertSame('summary', $crawler->filter('meta[name="twitter:card"]')->attr('content'));
        $this->assertEmpty($crawler->filter('meta[property="og:image"]'));
        $this->assertEmpty($crawler->filter('meta[name="dc.identifier"]'));
        $this->assertEmpty($crawler->filter('meta[name="dc.relation.ispartof"]'));
        $this->assertEmpty($crawler->filter('meta[name="dc.title"]'));
        $this->assertEmpty($crawler->filter('meta[name="dc.description"]'));
        $this->assertEmpty($crawler->filter('meta[name="dc.date"]'));
        $this->assertEmpty($crawler->filter('meta[name="dc.rights"]'));
    }

    protected function getUrl() : string
    {
        return '/resources';
    }
}
