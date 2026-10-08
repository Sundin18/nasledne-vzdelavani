<?php

namespace Tests\Feature;

use Tests\TestCase;

class PageTest extends TestCase
{
    public function test_about_page_is_linked_from_the_footer(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('pages.show', 'o-aplikaci'));
    }

    public function test_about_page_renders_markdown_content(): void
    {
        $this->get(route('pages.show', 'o-aplikaci'))
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('O aplikaci')
            ->assertSee('<h2>Pro účastníky</h2>', false);
    }

    public function test_unknown_page_returns_not_found(): void
    {
        $this->get(route('pages.show', 'neexistuje'))->assertNotFound();
    }
}
