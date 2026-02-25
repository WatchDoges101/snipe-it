<?php

namespace Tests\Feature\Components\Api;

use App\Models\Company;
use App\Models\Component;
use App\Models\User;
use Tests\TestCase;

class ComponentIndexTest extends TestCase
{
    public function testComponentIndexAdheresToCompanyScoping()
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();

        $componentA = Component::factory()->for($companyA)->create();
        $componentB = Component::factory()->for($companyB)->create();

        $superUser = $companyA->users()->save(User::factory()->superuser()->make());
        $userInCompanyA = $companyA->users()->save(User::factory()->viewComponents()->make());
        $userInCompanyB = $companyB->users()->save(User::factory()->viewComponents()->make());

        $this->settings->disableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA)
            ->assertResponseContainsInRows($componentB);

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA)
            ->assertResponseContainsInRows($componentB);

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA)
            ->assertResponseContainsInRows($componentB);

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA)
            ->assertResponseContainsInRows($componentB);

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.components.index'))
            ->assertResponseContainsInRows($componentA)
            ->assertResponseDoesNotContainInRows($componentB);

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.components.index'))
            ->assertResponseDoesNotContainInRows($componentA)
            ->assertResponseContainsInRows($componentB);
    }

    public function testCanSortComponentsByTotalCost()
    {
        $user = User::factory()->viewComponents()->create();

        $cheaperTotal = Component::factory()->create([
            'name' => 'Cheaper Component Total',
            'qty' => 2,
            'purchase_cost' => 10,
        ]);

        $moreExpensiveTotal = Component::factory()->create([
            'name' => 'More Expensive Component Total',
            'qty' => 3,
            'purchase_cost' => 20,
        ]);

        $response = $this->actingAsForApi($user)
            ->getJson(route('api.components.index', [
                'sort' => 'total_cost',
                'order' => 'asc',
            ]))
            ->assertOk();

        $rowIds = collect($response->json('rows'))->pluck('id')->all();

        $this->assertTrue(array_search($cheaperTotal->id, $rowIds) < array_search($moreExpensiveTotal->id, $rowIds));
    }
}
