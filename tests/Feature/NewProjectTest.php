<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class NewProjectTest extends TestCase
{
    // refreshes (and seeds) the database before running this tests
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_project_create_button(): void
    {
        $this->loginAsUser();
        $this->visit('/')
            ->click('new-project-button')
            ->seePageIs('/projekt/create');
    }

    public function test_project_create_is_fillable(): void
    {
        $this->loginAsUser();
        // call route without the iframe / layout http://localhost:8000/projekt/create?testing=1
        $response = $this->visitRoute('new-project', ['testing' => 1]);
        $this->seeStatusCode(200);

        $response
            ->seeText('neues Projekt anlegen')
            ->seeElement('input', ['type' => 'text', 'name' => 'name'])
            ->type('Project Name', 'name')
            // add more stuff from the form @ /legacy/lib/forms/projekte/ProjektHandler.php -> render()
        ;
    }

}
