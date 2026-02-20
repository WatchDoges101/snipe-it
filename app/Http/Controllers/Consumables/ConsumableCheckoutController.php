<?php

namespace App\Http\Controllers\Consumables;

use App\Events\CheckoutableCheckedOut;
use App\Helpers\Helper;
use App\Http\Controllers\CheckInOutRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConsumableCheckoutRequest;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use \Illuminate\Contracts\View\View;
use \Illuminate\Http\RedirectResponse;

class ConsumableCheckoutController extends Controller
{
    use CheckInOutRequest;

    /**
     * Return a view to checkout a consumable to a user.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @see ConsumableCheckoutController::store() method that stores the data.
     * @since [v1.0]
     * @param int $id
     */
    public function create($id) : View | RedirectResponse
    {

        if ($consumable = Consumable::find($id)) {

            $this->authorize('checkout', $consumable);

            // Make sure the category is valid
            if ($consumable->category) {

                // Make sure there is at least one available to checkout
                if ($consumable->numRemaining() <= 0){
                    return redirect()->route('consumables.index')
                        ->with('error', trans('admin/consumables/message.checkout.unavailable', ['requested' => 1, 'remaining' => $consumable->numRemaining()]));
                }

                // Return the checkout view
                return view('consumables/checkout', compact('consumable'));
            }

            // Invalid category
            return redirect()->route('consumables.edit', ['consumable' => $consumable->id])
                ->with('error', trans('general.invalid_item_category_single', ['type' => trans('general.consumable')]));
        }

        // Not found
        return redirect()->route('consumables.index')->with('error', trans('admin/consumables/message.does_not_exist'));

    }

    /**
     * Saves the checkout information
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @see ConsumableCheckoutController::create() method that returns the form.
     * @since [v1.0]
     * @param int $consumableId
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(ConsumableCheckoutRequest $request, $consumableId)
    {
        if (is_null($consumable = Consumable::with('users')->find($consumableId))) {
            return redirect()->route('consumables.index')->with('error', trans('admin/consumables/message.not_found'));
        }

        $this->authorize('checkout', $consumable);

        // If the quantity is not present in the request or is not a positive integer, set it to 1
        $quantity = $request->input('checkout_qty');
        if (!isset($quantity) || !ctype_digit((string)$quantity) || $quantity <= 0) {
            $quantity = 1;
        }

        // Make sure there is at least one available to checkout
        if ($consumable->numRemaining() <= 0 || $quantity > $consumable->numRemaining()) {
            return redirect()->route('consumables.index')->with('error', trans('admin/consumables/message.checkout.unavailable', ['requested' => $quantity, 'remaining' => $consumable->numRemaining() ]));
        }

        $target = $this->determineCheckoutTarget();

        if (!$target) {
            return redirect()->route('consumables.checkout.show', $consumable)->with('error', trans('admin/consumables/message.checkout.user_does_not_exist'))->withInput();
        }

        if ((Setting::getSettings()->full_multiple_companies_support) && ($target instanceof Asset) && ($consumable->company_id !== $target->company_id)) {
            return redirect()->route('consumables.checkout.show', $consumable)->with('error', trans('general.error_user_company'))->withInput();
        }

        // Update the consumable data
        $consumable->assigned_to = $target->id;

        for ($i = 0; $i < $quantity; $i++){
            $consumable->consumableAssignments()->create([
                'consumable_id' => $consumable->id,
                'created_by' => auth()->id(),
                'assigned_to' => $target->id,
                'assigned_type' => $target::class,
                'note' => $request->input('note'),
            ]);
        }

        $consumable->checkout_qty = $quantity;

        event(new CheckoutableCheckedOut(
            $consumable,
            $target,
            auth()->user(),
            $request->input('note'),
            [],
            $consumable->checkout_qty,
        ));

        if ($target instanceof Asset) {
            $request->request->add(['assigned_asset' => $target->id]);
            $request->request->add(['checkout_to_type' => 'asset']);
        } else {
            $request->request->add(['assigned_user' => $target->id]);
            $request->request->add(['checkout_to_type' => 'user']);
        }

        session()->put(['redirect_option' => $request->input('redirect_option'), 'checkout_to_type' => $request->input('checkout_to_type')]);


        // Redirect to the new consumable page
        return Helper::getRedirectOption($request, $consumable->id, 'Consumables')
            ->with('success', trans('admin/consumables/message.checkout.success'));
    }
}
