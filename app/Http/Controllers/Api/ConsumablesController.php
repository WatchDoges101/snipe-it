<?php

namespace App\Http\Controllers\Api;

use App\Events\CheckoutableCheckedOut;
use App\Helpers\Helper;
use App\Http\Controllers\CheckInOutRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsumableRequest;
use App\Http\Transformers\ActionlogsTransformer;
use App\Http\Transformers\ConsumablesTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\ImageUploadRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConsumablesController extends Controller
{
    use CheckInOutRequest;

    /**
     * Display a listing of the resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     */
    public function index(Request $request) : array
    {
        $this->authorize('index', Consumable::class);

        $consumables = Consumable::with('company', 'location', 'category', 'supplier', 'manufacturer')
            ->withCount('consumableAssignments as consumables_users_count');

        // This array is what determines which fields should be allowed to be sorted on ON the table itself.
        // These must match a column on the consumables table directly.
        $allowed_columns = [
            'id',
            'name',
            'order_number',
            'min_amt',
            'purchase_date',
            'purchase_cost',
            'company',
            'category',
            'model_number',
            'item_no',
            'manufacturer',
            'location',
            'qty',
            'image',
            // These are *relationships* so we wouldn't normally include them in this array,
            // since they would normally create a `column not found` error,
            // BUT we account for them in the ordering switch down at the end of this method
            // DO NOT ADD ANYTHING TO THIS LIST WITHOUT CHECKING THE ORDERING SWITCH BELOW!
            'company',
            'location',
            'category',
            'supplier',
            'manufacturer',
        ];


        $filter = [];

        if ($request->filled('filter')) {
            $filter = json_decode($request->input('filter'), true);

            $filter = array_filter($filter, function ($key) use ($allowed_columns) {
                return in_array($key, $allowed_columns);
            }, ARRAY_FILTER_USE_KEY);

        }

        if ((! is_null($filter)) && (count($filter)) > 0) {
            $consumables->ByFilter($filter);
        } elseif ($request->filled('search')) {
            $consumables->TextSearch($request->input('search'));
        }


        if ($request->filled('name')) {
            $consumables->where('name', '=', $request->input('name'));
        }

        if ($request->filled('company_id')) {
            $consumables->where('consumables.company_id', '=', $request->input('company_id'));
        }

        if ($request->filled('order_number')) {
            $consumables->where('consumables.order_number', '=', $request->input('order_number'));
        }

        if ($request->filled('category_id')) {
            $consumables->where('category_id', '=', $request->input('category_id'));
        }

        if ($request->filled('model_number')) {
            $consumables->where('model_number','=',$request->input('model_number'));
        }

        if ($request->filled('manufacturer_id')) {
            $consumables->where('manufacturer_id', '=', $request->input('manufacturer_id'));
        }

        if ($request->filled('supplier_id')) {
            $consumables->where('supplier_id', '=', $request->input('supplier_id'));
        }

        if ($request->filled('location_id')) {
            $consumables->where('location_id','=',$request->input('location_id'));
        }

        if ($request->filled('notes')) {
            $consumables->where('notes','=',$request->input('notes'));
        }


        // Make sure the offset and limit are actually integers and do not exceed system limits
        $offset = ($request->input('offset') > $consumables->count()) ? $consumables->count() : app('api_offset_value');
        $limit = app('api_limit_value');
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';

        switch ($request->input('sort')) {
            case 'category':
                $consumables = $consumables->OrderCategory($order);
                break;
            case 'location':
                $consumables = $consumables->OrderLocation($order);
                break;
            case 'manufacturer':
                $consumables = $consumables->OrderManufacturer($order);
                break;
            case 'company':
                $consumables = $consumables->OrderCompany($order);
                break;
            case 'remaining':
                $consumables = $consumables->OrderRemaining($order);
                break;
            case 'order_amount':
                $consumables = $consumables->OrderAmount($order);
                break;
            case 'total_cost':
                $consumables = $consumables->OrderTotalCost($order);
                break;
            case 'supplier':
                $consumables = $consumables->OrderSupplier($order);
                break;
            case 'created_by':
                $consumables = $consumables->OrderByCreatedBy($order);
                break;
            default:
                $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'created_at';
                $consumables = $consumables->orderBy($sort, $order);
                break;
        }

        $total = $consumables->count();
        $consumables = $consumables->skip($offset)->take($limit)->get();

        return (new ConsumablesTransformer)->transformConsumables($consumables, $total);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \App\Http\Requests\ImageUploadRequest $request
     */
    public function store(StoreConsumableRequest $request) : JsonResponse
    {
        $this->authorize('create', Consumable::class);
        $consumable = new Consumable;
        $consumable->fill($request->all());
        $consumable = $request->handleImages($consumable);

        if ($consumable->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $consumable, trans('admin/consumables/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $consumable->getErrors()));
    }

    /**
     * Display the specified resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param  int $id
     */
    public function show(Consumable $consumable) : array
    {
        $this->authorize('view', Consumable::class);
        $consumable->load('users');

        return (new ConsumablesTransformer)->transformConsumable($consumable);
    }

    /**
     * Update the specified resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \App\Http\Requests\ImageUploadRequest $request
     * @param  int $id
     */
    public function update(StoreConsumableRequest $request, Consumable $consumable) : JsonResponse
    {
        $this->authorize('update', Consumable::class);
        $consumable->fill($request->all());
        $consumable = $request->handleImages($consumable);
        
        if ($consumable->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $consumable, trans('admin/consumables/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $consumable->getErrors()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  int $id
     */
    public function destroy(Consumable $consumable) : JsonResponse
    {
        $this->authorize('delete', Consumable::class);
        $this->authorize('delete', $consumable);
        $consumable->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/consumables/message.delete.success')));
    }

    /**
    * Returns a JSON response containing details on the users associated with this consumable.
    *
    * @author [A. Gianotto] [<snipe@snipe.net>]
    * @see \App\Http\Controllers\Consumables\ConsumablesController::getView() method that returns the form.
    * @since [v1.0]
    * @param int $consumableId
     */
    public function getDataView(Consumable $consumable) : array
    {
        $consumable->load(['consumableAssignments'=> function ($query) {
            $query->UserAssigned();
            $query->orderBy($query->getModel()->getTable().'.created_at', 'DESC');
        },
        'consumableAssignments.adminuser'=> function ($query) {
        },
        'consumableAssignments.user'=> function ($query) {
        },
        ]);

        $this->authorize('view', Consumable::class);
        $rows = [];

        foreach ($consumable->consumableAssignments as $consumable_assignment) {
            $rows[] = [
                'avatar' => ($consumable_assignment->user) ? e($consumable_assignment->user->present()->gravatar) : '',
                'user' => ($consumable_assignment->user) ? [
                    'id' => (int) $consumable_assignment->user->id,
                    'name'=> e($consumable_assignment->user->display_name),
                ] : null,
                'created_at' => Helper::getFormattedDateObject($consumable_assignment->created_at, 'datetime'),
                'note' => ($consumable_assignment->note) ? e($consumable_assignment->note) : null,
                'created_by' => ($consumable_assignment->adminuser) ? [
                    'id' => (int) $consumable_assignment->adminuser->id,
                    'name'=> e($consumable_assignment->adminuser->display_name),
                ] : null,
            ];
        }

        $consumableCount = $consumable->users->count();
        $data = ['total' => $consumableCount, 'rows' => $rows];

        return $data;
    }

    /**
    * Returns a JSON response containing checkout history for this consumable.
    */
    public function getAssignmentHistory(Request $request, Consumable $consumable) : JsonResponse | array
    {
        $this->authorize('view', Consumable::class);

        $actionlogs = Actionlog::with('item', 'user', 'adminuser', 'target', 'location')
            ->where('item_id', '=', $consumable->id)
            ->where('item_type', '=', Consumable::class)
            ->where('action_type', '=', 'checkout');

        if ($request->filled('search')) {
            $actionlogs = $actionlogs->TextSearch($request->input('search'));
        }

        $allowedColumns = [
            'id',
            'created_at',
            'created_by',
            'action_type',
            'note',
            'action_date',
        ];

        $total = $actionlogs->count();
        $offset = ($request->input('offset') > $total) ? $total : app('api_offset_value');
        $limit = app('api_limit_value');
        $order = ($request->input('order') == 'asc') ? 'asc' : 'desc';

        switch ($request->input('sort')) {
            case 'created_by':
                $actionlogs->OrderByCreatedBy($order);
                break;
            default:
                $sort = in_array($request->input('sort'), $allowedColumns) ? $request->input('sort') : 'action_logs.created_at';
                $actionlogs = $actionlogs->orderBy($sort, $order);
                break;
        }

        $actionlogs = $actionlogs->skip($offset)->take($limit)->get();

        return response()->json((new ActionlogsTransformer)->transformActionlogs($actionlogs, $total), 200, ['Content-Type' => 'application/json;charset=utf8'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Checkout a consumable
     *
     * @author [A. Gutierrez] [<andres@baller.tv>]
     * @param int $id
     * @since [v4.9.5]
     */
    public function checkout(Request $request, Consumable $consumable) : JsonResponse
    {
        $consumable->load('users');
        $this->authorize('checkout', $consumable);
        $consumable->checkout_qty = (int) $request->input('checkout_qty', 1);

        $this->validateCheckoutRequest($consumable);
        $target = $this->resolveCheckoutTargetOrFail($request);

        $consumable->assigned_to = $target->id;

        $this->createCheckoutAssignments($consumable, $target, $request->input('note'));

        event(new CheckoutableCheckedOut(
            $consumable,
            $target,
            auth()->user(),
            $request->input('note'),
            [],
            $consumable->checkout_qty,
        ));

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/consumables/message.checkout.success')));

    }

    private function validateCheckoutRequest(Consumable $consumable): void
    {
        if ($consumable->numRemaining() <= 0) {
            throw new HttpResponseException(response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/consumables/message.checkout.unavailable'))));
        }

        if (!$consumable->category) {
            throw new HttpResponseException(response()->json(Helper::formatStandardApiResponse('error', null, trans('general.invalid_item_category_single', ['type' => trans('general.consumable')]))));
        }

        if ($consumable->checkout_qty > $consumable->numRemaining()) {
            throw new HttpResponseException(response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/consumables/message.checkout.unavailable', ['requested' => $consumable->checkout_qty, 'remaining' => $consumable->numRemaining() ]))));
        }
    }

    private function resolveCheckoutTargetOrFail(Request $request): mixed
    {
        $target = $this->resolveCheckoutTarget($request);

        if ($target) {
            return $target;
        }

        throw new HttpResponseException(response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/consumables/message.checkout.user_does_not_exist'))));
    }

    private function resolveCheckoutTarget(Request $request): mixed
    {
        if ($request->filled('checkout_to_type')) {
            return $this->determineCheckoutTarget();
        }

        if ($request->filled('assigned_user') || $request->filled('assigned_asset')) {
            if ($request->filled('assigned_asset')) {
                $request->request->add(['checkout_to_type' => 'asset']);
                return Asset::find($request->input('assigned_asset'));
            }

            $request->request->add(['checkout_to_type' => 'user']);
            return User::find($request->input('assigned_user'));
        }

        if ($request->filled('assigned_to')) {
            $request->request->add([
                'checkout_to_type' => 'user',
                'assigned_user' => $request->input('assigned_to'),
            ]);

            return User::find($request->input('assigned_to'));
        }

        return null;
    }

    private function createCheckoutAssignments(Consumable $consumable, mixed $target, ?string $note): void
    {
        for ($i = 0; $i < $consumable->checkout_qty; $i++) {
            $consumable->consumableAssignments()->create([
                'consumable_id' => $consumable->id,
                'created_by' => auth()->id(),
                'assigned_to' => $target->id,
                'assigned_type' => $target::class,
                'note' => $note,
            ]);
        }
    }

    /**
    * Gets a paginated collection for the select2 menus
    *
    * @see \App\Http\Transformers\SelectlistTransformer
    */
    public function selectlist(Request $request) : array
    {
        $consumables = Consumable::select([
            'consumables.id',
            'consumables.name',
        ]);

        if ($request->filled('search')) {
            $consumables = $consumables->where('consumables.name', 'LIKE', '%'.$request->input('search').'%');
        }

        $consumables = $consumables->orderBy('name', 'ASC')->paginate(50);

        return (new SelectlistTransformer)->transformSelectlist($consumables);
    }
}
