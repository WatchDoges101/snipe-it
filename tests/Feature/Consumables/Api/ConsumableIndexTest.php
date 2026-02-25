<?php

namespace Tests\Feature\Consumables\Api;

use App\Models\Actionlog;
use App\Models\Company;
use App\Models\Consumable;
use App\Models\User;
use Tests\TestCase;

class ConsumableIndexTest extends TestCase
{
    public function testConsumableIndexAdheresToCompanyScoping()
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();

        $consumableA = Consumable::factory()->for($companyA)->create();
        $consumableB = Consumable::factory()->for($companyB)->create();

        $superUser = $companyA->users()->save(User::factory()->superuser()->make());
        $userInCompanyA = $companyA->users()->save(User::factory()->viewConsumables()->make());
        $userInCompanyB = $companyB->users()->save(User::factory()->viewConsumables()->make());

        $this->settings->disableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.consumables.index'))
            ->assertResponseContainsInRows($consumableA)
            ->assertResponseContainsInRows($consumableB);

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.consumables.index'))
            ->assertResponseContainsInRows($consumableA)
            ->assertResponseContainsInRows($consumableB);

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.consumables.index'))
            ->assertResponseContainsInRows($consumableA)
            ->assertResponseContainsInRows($consumableB);

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.consumables.index'))
            ->assertResponseContainsInRows($consumableA)
            ->assertResponseContainsInRows($consumableB);

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.consumables.index'))
            ->assertResponseContainsInRows($consumableA)
            ->assertResponseDoesNotContainInRows($consumableB);

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.consumables.index'))
            ->assertResponseDoesNotContainInRows($consumableA)
            ->assertResponseContainsInRows($consumableB);
    }

    public function testConsumableIndexReturnsExpectedSearchResults()
    {
        Consumable::factory()->count(10)->create();
        Consumable::factory()->count(1)->create(['name' => 'My Test Consumable']);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(
                route('api.consumables.index', [
                    'search' => 'My Test Consumable',
                    'sort' => 'name',
                    'order' => 'asc',
                    'offset' => '0',
                    'limit' => '20',
                ]))
            ->assertOk()
            ->assertJsonStructure([
                'total',
                'rows',
            ])
            ->assertJson([
                'total' => 1,
            ]);

    }

    public function testCanSortConsumablesByPurchaseCost()
    {
        $user = User::factory()->viewConsumables()->create();

        $lowerCost = Consumable::factory()->create([
            'name' => 'Lower Consumable Cost',
            'purchase_cost' => 5,
        ]);

        $higherCost = Consumable::factory()->create([
            'name' => 'Higher Consumable Cost',
            'purchase_cost' => 15,
        ]);

        $response = $this->actingAsForApi($user)
            ->getJson(route('api.consumables.index', [
                'sort' => 'purchase_cost',
                'order' => 'asc',
            ]))
            ->assertOk();

        $rowIds = collect($response->json('rows'))->pluck('id')->all();

        $this->assertTrue(array_search($lowerCost->id, $rowIds) < array_search($higherCost->id, $rowIds));
    }

    public function testCanSortConsumablesByTotalCostUsingReplenishCost()
    {
        $user = User::factory()->viewConsumables()->create();

        $lowerTotal = Consumable::factory()->create([
            'name' => 'Lower Consumable Total',
            'purchase_cost' => 100,
        ]);

        $higherTotal = Consumable::factory()->create([
            'name' => 'Higher Consumable Total',
            'purchase_cost' => 10,
        ]);

        Actionlog::factory()->create([
            'item_id' => $lowerTotal->id,
            'item_type' => Consumable::class,
            'created_by' => $user->id,
            'action_type' => 'update',
            'note' => 'Consumable replenished (test)',
            'log_meta' => json_encode([
                'replenish_total_cost' => [
                    'new' => 20,
                ],
            ]),
        ]);

        Actionlog::factory()->create([
            'item_id' => $higherTotal->id,
            'item_type' => Consumable::class,
            'created_by' => $user->id,
            'action_type' => 'update',
            'note' => 'Consumable replenished (test)',
            'log_meta' => json_encode([
                'replenish_total_cost' => [
                    'new' => 200,
                ],
            ]),
        ]);

        $response = $this->actingAsForApi($user)
            ->getJson(route('api.consumables.index', [
                'sort' => 'total_cost',
                'order' => 'asc',
            ]))
            ->assertOk();

        $rowIds = collect($response->json('rows'))->pluck('id')->all();

        $this->assertTrue(array_search($lowerTotal->id, $rowIds) < array_search($higherTotal->id, $rowIds));
    }
}
